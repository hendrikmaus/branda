<?php

namespace Hmaus\Branda\Command;

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
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputPath = $input->getOption('input');

        if (!$this->container->get('hmaus.branda.filesystem')->exists($inputPath)) {
            throw new InvalidOptionException(
                sprintf('Given input file "%s" does not exist.', $inputPath)
            );
        }

        // todo mock the heck

        return 0;
    }
}