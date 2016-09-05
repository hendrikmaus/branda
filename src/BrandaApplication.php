<?php

namespace Hmaus\Branda;

use Hmaus\Branda\Matching\CompilerPass\MatcherCompilerPass;
use Hmaus\Spas\Validator\AddValidatorsPass;
use Psr\Log\LogLevel;
use Symfony\Bridge\Monolog\Handler\ConsoleHandler;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

/**
 * @codeCoverageIgnore
 */
class BrandaApplication extends Application
{
    /**
     * @var ContainerBuilder
     */
    private $container;

    public function __construct()
    {
        parent::__construct();

        $this->initContainer();
        $this->setupCommands();
    }

    private function initContainer()
    {
        $this->container = new ContainerBuilder();
        $loader = new XmlFileLoader($this->container, new FileLocator(__DIR__));
        $loader->load('Resources/config/services.xml');
        $loader->load('Resources/config/commands.xml');
        $loader->load('Resources/config/matchers.xml');

        $this->container->addCompilerPass(new MatcherCompilerPass());
    }

    private function setupCommands()
    {
        $this->add($this->container->get('hmaus.branda.command.mock'));
    }

    public function run(InputInterface $input = null, OutputInterface $output = null)
    {
        if (null === $input) {
            $input = new ArgvInput();
        }

        if (null === $output) {
            $output = new ConsoleOutput();
        }

        $logger = new ConsoleLogger(
            $output,
            [
                LogLevel::NOTICE => OutputInterface::VERBOSITY_NORMAL,
                LogLevel::INFO => OutputInterface::VERBOSITY_NORMAL,
            ]
        );
        $this->container->set('hmaus.branda.logger', $logger);

        $io = new SymfonyStyle($input, $output);
        $this->container->set('hmaus.branda.io', $io);

        // make sure to compile the container so compiler passes run
        $this->container->compile();

        return parent::run($input, $output);
    }
}