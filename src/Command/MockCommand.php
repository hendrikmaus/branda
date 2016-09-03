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

        $drafter = new Drafter('vendor/bin/drafter');

        $rawParseResult = $drafter
            ->input($inputPath)
            ->format('json')
            ->type('refract')
            ->run();

        $requestProvider = new ApibParsedRequestsProvider();
        $parsedRequests = $requestProvider->parse(
            json_decode($rawParseResult, true)
        );

        $address = $input->getArgument('address');
        $port = $input->getOption('port');

        $io->block('branda is booting ...', null, 'fg=black;bg=green', ' ', true);

        foreach ($parsedRequests as $request) {
            if ($request->getMethod() === 'GET') {
                $contentType = $request->getResponse()->getHeaders()->get('content-type');
            } else {
                $contentType = $request->getHeaders()->get('content-type');
            }

            $io->write(
                sprintf(
                    '%s %s (%s)',
                    $request->getMethod(),
                    $request->getHref(),
                    $contentType
                )
            );
            $io->newLine();
        }
        $io->newLine();

        /**
         * @param Request $request
         * @param Response $response
         */
        $app = function ($request, $response) use ($parsedRequests, $io) {
            /** @var ParsedRequest $match */
            $match = $this->match($request, $parsedRequests);

            $response->writeHead(
                $match->getResponse()->getStatusCode(),
                $match->getResponse()->getHeaders()->all()
            );

            $response->end(
                $match->getResponse()->getBody() ?? ''
            );

            $io->writeln(
                sprintf('%s %s', $request->getMethod(), $match->getHref())
            );
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

        return $this->mismatch();
    }

    /**
     * @return SpasRequest
     */
    private function mismatch()
    {
        $response = new SpasResponse();
        $response->setStatusCode(404);
        $response->setBody("404 - No matching resource in API description\n");
        $response->getHeaders()->set('Content-Type', 'text/plain');

        $request = new SpasRequest();
        $request->setResponse($response);

        return $request;
    }
}