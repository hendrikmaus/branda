<?php

namespace Hmaus\Branda\Command;

use Hmaus\Branda\Server\ReactProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @codeCoverageIgnore
 */
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
                    new InputOption('type', 't', InputOption::VALUE_REQUIRED, 'Description type', 'apib'),
                    new InputOption('file', 'f', InputOption::VALUE_REQUIRED, 'Api to mock'),
                ]
            )
            ->setName('mock')
            ->setDescription('Mock given API')
            ->setHelp(
                <<<'EOF'
Branda's <info>%command.name%</info> runs a React PHP Server;
it responds with your documented example payloads and headers.

<comment>Quick Start:</comment>
    <info>bin/branda mock --file "your-service.apib"</info>

<comment>Important Note:</comment>
As of now, branda comes bundled with a parser for <info>API Blueprint</info>; hence
the only supported value for the <info>type</info> option is <info>"apib"</info>.

To implement another parser, please refer to the respective guide on:
<fg=blue>https://github.com/hendrikmaus/branda</>
EOF
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->newLine();

        $type = $this->getType($input);
        $inputPath = $this->getInputPth($input);

        $parser = $this->container->get(
            sprintf('hmaus.branda.api_description.%s', $type)
        );

        $this->container->get('hmaus.branda.server.mock_server')->serve(
            $input->getArgument('address'),
            $input->getOption('port'),
            $io,
            $parser->parse($inputPath),
            $this->container->get('hmaus.branda.matching.matching_service'),
            $this->container->get('hmaus.branda.server.react_provider')
        );

        return 0;
    }

    /**
     * @param InputInterface $input
     * @return mixed
     */
    private function getType(InputInterface $input)
    {
        $type = $input->getOption('type');

        if ($type !== 'apib') {
            throw new InvalidOptionException(
                sprintf('Given input type "%s" is not supported at this moment.', $type)
            );
        }

        if (!$this->container->has(sprintf('hmaus.branda.api_description.%s', $type))) {
            throw new InvalidOptionException(
                sprintf('Given input type "%s" does not meet any parser implementation', $type)
            );
        }

        return $type;
    }

    /**
     * @param InputInterface $input
     * @return mixed
     */
    private function getInputPth(InputInterface $input)
    {
        $inputPath = $input->getOption('file');

        if (!$this->container->get('hmaus.branda.filesystem')->exists($inputPath)) {
            throw new InvalidOptionException(
                sprintf('Given input file "%s" does not exist.', $inputPath)
            );
        }

        return $inputPath;
    }
}