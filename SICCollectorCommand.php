<?php

use Collector\SICCollector;
use Doctrine\ODM\MongoDB\DocumentManager;
use Monolog\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SICCollectorCommand
 */
class SICCollectorCommand extends Command
{
    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * SICCollectorCommand constructor.
     *
     * @param DocumentManager $dm
     * @param Logger $logger
     * @param string|null $name
     */
    public function __construct(DocumentManager $dm, Logger $logger, string $name = null)
    {
        parent::__construct($name);
        $this->dm = $dm;
        $this->logger = $logger;
    }

    protected function configure()
    {
        $this
            ->setName('collector:sic:run')
            ->setDescription('Collect sic codes.')
            ->setHelp("This command allows you to Collect sic codes from the edgar database");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $collector = new SICCollector($this->dm, $this->logger);
        $collector->run();
    }
}