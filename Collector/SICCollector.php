<?php

namespace Collector;

use Doctrine\ODM\MongoDB\DocumentManager;
use Monolog\Logger;

/**
 * Class SICCollector
 *
 * @package Collector
 */
class SICCollector
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
     * SICCollector constructor.
     *
     * @param DocumentManager $dm
     * @param Logger $logger
     */
    public function __construct(DocumentManager $dm, Logger $logger)
    {
        $this->dm = $dm;
        $this->logger = $logger;
    }

    public function run()
    {
        $this->logger->info('Process Starting');
        $edgar = new EDGAR();
        $sicCodes = $edgar->getSICCodes();
        foreach ($sicCodes as $item) {
            $sic = new SIC($item['code'], $item['office'], $item['title']);
            $this->dm->persist($sic);
        }
        $this->dm->flush();
        $this->logger->info(sprintf('Process End with %s sic collected', count($sicCodes)));
    }
}