<?php

namespace Jh\WorkflowTest;

use Jh\Workflow\Application;
use Jh\Workflow\Command\Magento;
use Jh\Workflow\CommandLine;
use Jh\Workflow\NullLogger;
use PHPUnit\Framework\TestCase;
use React\EventLoop\StreamSelectLoop;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

class ApplicationTest extends TestCase
{
    /**
     * @return void
     */
    public function testIfCommandDoesNotExistFallBackIsInvoked()
    {
        $app = new Application;
        $app->setAutoExit(false);
        $app->add(new class extends Command {
            protected function configure()
            {
                $this->setName('some-command');
            }
        });

        $fallback = new Magento(new CommandLine(new StreamSelectLoop, new NullLogger, new ConsoleOutput));
        $fallback->setCode(function (InputInterface $input) {
            static::assertTrue($input->hasArgument('cmd'));
            static::assertEquals($input->getArgument('cmd'), 'some-command-that-does-not-exist');
        });

        $app->add($fallback);

        $_SERVER['argv'] = ['workflow', 'some-command-that-does-not-exist', 'arg1'];

        $exitCode = $app->run();

        static::assertEquals(0, $exitCode);
    }
}
