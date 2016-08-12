<?php

use Collector\Collector;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CollectorCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('collector:run')
            ->setDescription('Collect companies info.')
            ->setHelp("This command allows you to Collect companies info from the edgar database");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $collector = new Collector();
        $collector->run();
    }
}