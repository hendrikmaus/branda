<?php
/**
 * @author    Hendrik Maus <aidentailor@gmail.com>
 * @since     2016-08-05
 * @copyright 2016 (c) Hendrik Maus
 * @license   All rights reserved.
 * @package   branda
 */

namespace Hmaus\Branda;

use Hmaus\Spas\Validator\AddValidatorsPass;
use Psr\Log\LogLevel;
use Symfony\Bridge\Monolog\Handler\ConsoleHandler;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

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
    }

    private function setupCommands()
    {
        $this->add($this->container->get('hmaus.branda.command.mock'));
    }

    public function run(InputInterface $input = null, OutputInterface $output = null)
    {
        $logger = new ConsoleLogger($output, [
            LogLevel::NOTICE => OutputInterface::VERBOSITY_NORMAL,
            LogLevel::INFO => OutputInterface::VERBOSITY_NORMAL,
        ]);
        $this->container->set('hmaus.branda.logger', $logger);

        // make sure to compile the container so compiler passes run
        $this->container->compile();

        return parent::run($input, $output);
    }
}