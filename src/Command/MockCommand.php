<?php

namespace Hmaus\Branda\Command;

use Hmaus\DrafterPhp\Drafter;
use Hmaus\Reynaldo\Parser\RefractParser;
use Hmaus\Spas\Parser\Apib\ApibParsedRequestsProvider;
use Hmaus\SpasParser\Parser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
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
            ->setName('mock')
            ->setDescription('Mock given API')
            ->addOption(
                'input',
                'i',
                InputOption::VALUE_REQUIRED,
                'Path to the input file to use'
            )
            ->addOption(
                'type',
                't',
                InputOption::VALUE_REQUIRED,
                'Type of API Description, e.g. `apib`'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $type = $input->getOption('type');

        if ($type !== 'apib') {
            throw new InvalidOptionException(
                sprintf('Given input type "%s" is not supported at this moment.', $type)
            );
        }

        $inputPath = $input->getOption('input');

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

        dump($parsedRequests[0]);

        // todo now how to create dynamic routes form the parsed Requests?

        return 0;
    }
}