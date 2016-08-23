<?php

namespace Collector;

use Doctrine\ODM\MongoDB\DocumentManager;

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
     * SICCollector constructor.
     *
     * @param DocumentManager $dm
     */
    public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
    }

    public function run()
    {
        $edgar = new EDGAR();
        $sicCodes = $edgar->getSICCodes();
        foreach ($sicCodes as $item) {
            $sic = new SIC($item['code'], $item['office'], $item['title']);
            $this->dm->persist($sic);
        }
        $this->dm->flush();
    }
}