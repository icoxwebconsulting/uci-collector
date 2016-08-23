<?php

use Collector\SICCollector;
use Doctrine\ODM\MongoDB\DocumentManager;
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
     * CompanyCollectorCommand constructor.
     *
     * @param DocumentManager $dm
     * @param null|string $name
     */
    public function __construct(DocumentManager $dm, string $name = null)
    {
        parent::__construct($name);
        $this->dm = $dm;
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
        $collector = new SICCollector($this->dm);
        $collector->run();
    }
}