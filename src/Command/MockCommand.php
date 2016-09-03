<?php

namespace Hmaus\Branda\Command;

use Hmaus\DrafterPhp\Drafter;
use Hmaus\Spas\Parser\Apib\ApibParsedRequestsProvider;
use Hmaus\SpasParser\ParsedRequest;
use Hmaus\SpasParser\SpasRequest;
use Hmaus\SpasParser\SpasResponse;
use React\EventLoop\Factory;
use React\Http\Request;
use React\Http\Response;
use React\Socket\Server;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MockCommand extends Command
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct();

        $this->container = $container;
    }

    protected function configure()
    {
        $this
            ->setDefinition(
                [
                    new InputArgument('address', InputArgument::OPTIONAL, 'Address', '127.0.0.1'),
                    new InputOption('port', 'p', InputOption::VALUE_REQUIRED, 'Address port number', '8000'),
                    new InputOption(
                        'type',
                        't',
                        InputOption::VALUE_REQUIRED,
                        'API description type, e.g. `apib`',
                        'apib'
                    ),
                    new InputOption('file', 'f', InputOption::VALUE_REQUIRED, 'Api to mock'),
                ]
            )
            ->setName('mock')
            ->setDescription('Mock given API')
            ->setHelp(
                <<<'EOF'
<info>%command.name%</info> runs a React PHP Server.

It will try to match your request to a transaction defined in the given
API description.

It will respond with all defined headers and the defined payload, if any.
EOF
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $io->newLine();

        $type = $input->getOption('type');

        if ($type !== 'apib') {
            throw new InvalidOptionException(
                sprintf('Given input type "%s" is not supported at this moment.', $type)
            );
        }

        $inputPath = $input->getOption('file');

        if (!$this->container->get('hmaus.branda.filesystem')->exists($inputPath)) {
            throw new InvalidOptionException(
                sprintf('Given input file "%s" does not exist.', $inputPath)
            );
        }

        $io->write('[info] Parsing document ... ');
        $drafter = new Drafter('vendor/bin/drafter');
        $rawParseResult = $drafter
            ->input($inputPath)
            ->format('json')
            ->type('refract')
            ->run();
        $io->writeln('done');

        $io->writeln('[info] Adding routes:');
        $requestProvider = new ApibParsedRequestsProvider();
        $parsedRequests = $requestProvider->parse(
            json_decode($rawParseResult, true)
        );

        $address = $input->getArgument('address');
        $port = $input->getOption('port');

        foreach ($parsedRequests as $request) {
            $io->write('[add route] ');
            $io->write(sprintf('<info>%s</info> ', $request->getMethod()));
            $io->write(sprintf('<comment>%s</comment> ', $request->getHref()));
            $io->write(sprintf('<fg=blue>%s</>', $this->getLastNamePart($request->getName())));
            $io->newLine();
        }
        $io->newLine();

        /**
         * @param Request $request
         * @param Response $response
         */
        $app = function ($request, $response) use ($parsedRequests, $io) {

            $queryString = '';
            foreach ($request->getQuery() as $key => $value) {
                $queryString .= sprintf('&%s=%s', $key, $value);
            }

            $io->write('[request] ');
            $io->write(sprintf('<info>%s</info> ', $request->getMethod()));
            if (!$queryString) {
                $io->write(sprintf('<comment>%s</comment> ', $request->getPath()));
            } else {
                $io->write(sprintf('<comment>%s?%s</comment> ', $request->getPath(), substr($queryString, 1)));
            }
            $io->newLine();

            /** @var ParsedRequest $match */
            $match = $this->match($request, $parsedRequests);

            $response->writeHead(
                $match->getResponse()->getStatusCode(),
                $match->getResponse()->getHeaders()->all()
            );

            $response->end(
                $match->getResponse()->getBody() ?? ''
            );

            /*
             * todo this will also look like it matched a mismatch
             *      [request] PUT /?hh=
             *      [matched] PUT  No match found
             */
            $io->write('<info>[matched]</info> ');
            $io->write(sprintf('<info>%s</info> ', $match->getMethod()));
            $io->write(sprintf('<comment>%s</comment> ', $match->getHref()));
            $io->write(sprintf('<fg=blue>%s</>', $this->getLastNamePart($match->getName())));
            $io->newLine(2);
        };

        $loop = Factory::create();
        $socket = new Server($loop);
        $http = new \React\Http\Server($socket, $loop);

        $http->on('request', $app);

        $io->success(sprintf('Mock server running on http://%s:%d', $address, $port));
        $io->comment('Quit the server with CONTROL-C.');

        $socket->listen($port, $address);
        $loop->run();

        return 0;
    }

    /**
     * @param string $name
     * @param string $delimiter
     * @return string
     */
    private function getLastNamePart(string $name, $delimiter = ' > ')
    {
        if (!$name) {
            return '';
        }

        $parts = explode($delimiter, $name);
        return array_pop($parts);
    }

    /**
     * @param Request $request
     * @param ParsedRequest[] $parsedRequests
     * @return ParsedRequest
     */
    private function match(Request $request, array $parsedRequests)
    {
        $matchingService = $this->container->get('hmaus.branda.matching.matching_service');

        foreach ($parsedRequests as $parsedRequest) {
            $match = $matchingService->match($request, $parsedRequest);

            if (!$match) {
                continue;
            }

            return $parsedRequest;
        }

        return $this->mismatch($request);
    }

    /**
     * @return SpasRequest
     */
    private function mismatch(Request $httpRequest)
    {
        $response = new SpasResponse();
        $response->setStatusCode(404);
        $response->setBody("404 - No matching resource in API description\n");
        $response->getHeaders()->set('Content-Type', 'text/plain');

        $request = new SpasRequest();
        $request->setResponse($response);
        $request->setMethod($httpRequest->getMethod());
        $request->setName('No match found');

        return $request;
    }
}