<?php

use Interop\Container\ContainerInterface;
use Jh\Workflow\Application;
use Jh\Workflow\Command;
use Jh\Workflow\CommandLine;
use Jh\Workflow\Config\ConfigGeneratorFactory;
use Jh\Workflow\Config\M1ConfigGenerator;
use Jh\Workflow\Config\M2ConfigGenerator;
use Jh\Workflow\Files;
use Jh\Workflow\NewProject\DetailsGatherer;
use Jh\Workflow\NewProject\Step;
use Jh\Workflow\NewProject\StepRunner;
use Jh\Workflow\NewProject\TemplateWriter;
use Jh\Workflow\NullLogger;
use Jh\Workflow\WatchFactory;
use React\EventLoop\LoopInterface;
use React\EventLoop\StreamSelectLoop;
use Rx\Scheduler;
use Rx\Scheduler\EventLoopScheduler;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ProcessBuilder;

return [
    Application::class => function (ContainerInterface $c) {
        $app = new Application('JH Workflow Tool');
        $app->getDefinition()->addOption(new InputOption('--debug', null, InputOption::VALUE_NONE, 'Debug Mode'));

        $app->add($c->get(Command\Start::class));
        $app->add($c->get(Command\Stop::class));
        $app->add($c->get(Command\Down::class));
        $app->add($c->get(Command\Restart::class));
        $app->add($c->get(Command\Build::class));
        $app->add($c->get(Command\Up::class));
        $app->add($c->get(Command\Magento::class));
        $app->add($c->get(Command\MagentoFullInstall::class));
        $app->add($c->get(Command\MagentoInstall::class));
        $app->add($c->get(Command\MagentoConfigure::class));
        $app->add($c->get(Command\MagentoCompile::class));
        $app->add($c->get(Command\MagentoModuleEnable::class));
        $app->add($c->get(Command\MagentoModuleDisable::class));
        $app->add($c->get(Command\MagentoSetupUpgrade::class));
        $app->add($c->get(Command\Pull::class));
        $app->add($c->get(Command\Push::class));
        $app->add($c->get(Command\Watch::class));
        $app->add($c->get(Command\Sync::class));
        $app->add($c->get(Command\ComposerUpdate::class));
        $app->add($c->get(Command\ComposerInstall::class));
        $app->add($c->get(Command\ComposerRequire::class));
        $app->add($c->get(Command\Sql::class));
        $app->add($c->get(Command\Ssh::class));
        $app->add($c->get(Command\NginxReload::class));
        $app->add($c->get(Command\XdebugLoopback::class));
        $app->add($c->get(Command\NewProject::class));
        $app->add($c->get(Command\Php::class));
        $app->add($c->get(Command\Exec::class));
        $app->add($c->get(Command\Delete::class));
        $app->add($c->get(Command\DatabaseDump::class));
        $app->add($c->get(Command\GenerateConfig::class));
        $app->add($c->get(Command\VarnishEnable::class));
        $app->add($c->get(Command\VarnishDisable::class));

        $eventLoop = $c->get(LoopInterface::class);

        Scheduler::setDefaultFactory(function () use ($eventLoop) {
            return new EventLoopScheduler($eventLoop);
        });

        register_shutdown_function(function () use ($eventLoop) {
             $eventLoop->run();
        });

        return $app;
    },
    InputInterface::class => function () {
        return new ArgvInput();
    },
    OutputInterface::class => function () {
        return new ConsoleOutput;
    },
    ProcessBuilder::class  => DI\object(),
    TemplateWriter::class  => DI\object(),
    DetailsGatherer::class => DI\object(),
    LoopInterface::class => function () {
        return new StreamSelectLoop;
    },
    CommandLine::class  => function (ContainerInterface $c) {
        if (in_array('--debug', $GLOBALS['argv'], true)) {
            $logger = new \Jh\Workflow\Logger($c->get(OutputInterface::class));
        } else {
            $logger = new NullLogger;
        }

        return new CommandLine($c->get(LoopInterface::class), $logger, $c->get(OutputInterface::class));
    },
    WatchFactory::class => function (ContainerInterface $c) {
        return new WatchFactory($c->get(LoopInterface::class));
    },
    Files::class => function (ContainerInterface $c) {
        return new Files($c->get(CommandLine::class), $c->get(OutputInterface::class));
    },

    \Psr\Log\LoggerInterface::class => function (ContainerInterface $c) {
        return new \Jh\Workflow\Logger($c->get(OutputInterface::class));
    },

    // Commands
    Command\Build::class              => DI\object(),
    Command\Magento::class            => DI\object(),
    Command\MagentoFullInstall::class => DI\object(),
    Command\MagentoInstall::class     => DI\object(),
    Command\MagentoConfigure::class   => DI\object(),
    Command\Pull::class               => DI\object(),
    Command\Push::class               => DI\object(),
    Command\Start::class              => DI\object(),
    Command\Stop::class               => DI\object(),
    Command\Down::class               => DI\object(),
    Command\Up::class                 => DI\object(),
    Command\Watch::class              => function (ContainerInterface $c) {
        return new Command\Watch($c->get(WatchFactory::class), $c->get(Files::class));
    },
    Command\Sync::class               => DI\object(),
    Command\ComposerUpdate::class     => DI\object(),
    Command\Sql::class                => DI\object(),
    Command\NginxReload::class        => DI\object(),
    Command\XdebugLoopback::class     => DI\object(),
    Command\Ssh::class                => DI\object(),
    Command\NewProject::class         => DI\object(),
    Command\Php::class                => DI\object(),
    Command\Exec::class               => DI\object(),
    Command\GenerateConfig::class     => DI\object(),

    // Config Generation
    ConfigGeneratorFactory::class => DI\object(),
    M1ConfigGenerator::class => DI\object(),
    M2ConfigGenerator::class => DI\object(),

    // New Project Steps
    StepRunner::class => function (ContainerInterface $c) {
        return new StepRunner($c->get('steps'));
    },
    Step\CreateProject::class => DI\object(),
    Step\GitInit::class       => DI\object(),
    Step\AuthJson::class      => DI\object(),
    Step\ComposerJson::class  => DI\object(),
    Step\Docker::class        => DI\object(),
    Step\PrTemplate::class    => DI\object(),
    Step\Readme::class        => DI\object(),
    Step\CircleCI::class      => DI\object(),
    Step\Capistrano::class    => DI\object(),
    Step\PhpStorm::class      => DI\object(),
    Step\GitCommit::class     => DI\object(),

    'steps' => function (ContainerInterface $c) {
        return [
            $c->get(Step\CreateProject::class),
            $c->get(Step\GitInit::class),
            $c->get(Step\AuthJson::class),
            $c->get(Step\ComposerJson::class),
            $c->get(Step\Docker::class),
            $c->get(Step\PrTemplate::class),
            $c->get(Step\Readme::class),
            $c->get(Step\CircleCI::class),
            $c->get(Step\Capistrano::class),
            $c->get(Step\PhpStorm::class),
            $c->get(Step\GitCommit::class),
        ];
    }
];
