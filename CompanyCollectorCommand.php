<?php

use Collector\CompanyCollector;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CompanyCollectorCommand
 */
class CompanyCollectorCommand extends Command
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
            ->setName('collector:companies:run')
            ->setDescription('Collect companies info.')
            ->setHelp("This command allows you to Collect companies info from the edgar database");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $collector = new CompanyCollector($this->dm);
        $collector->run();
    }
}