<?php

namespace Jh\Workflow\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

/**
 * @author Michael Woodward <michael@wearejh.com>
 */
class Watch extends Command implements CommandInterface
{
    use ProcessRunnerTrait;

    public function __construct(ProcessBuilder $processBuilder)
    {
        parent::__construct();
        $this->processBuilder = $processBuilder;
    }

    public function configure()
    {
        $this
            ->setName('watch')
            ->setDescription('Keeps track of filesystem changes, piping the changes to the sync command');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $watches  = ['./app', './pub', './composer.json'];
        $excludes = ['.docker', '.*__jp*', '.swp', '.swpx'];

        $output->writeln("<info>Watching for file changes...</info>");

        $command = sprintf(
            'fswatch -r %s -e "%s" | xargs -n1 -I {} workflow sync {}',
            implode(' ', $watches),
            implode('|', $excludes)
        );

        $this->runProcessShowingOutput($output, explode(' ', $command));
    }
}
