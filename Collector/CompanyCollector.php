<?php

namespace Collector;

use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * Class CompanyCollector
 *
 * @package Collector
 */
class CompanyCollector
{
    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * CompanyCollector constructor.
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
        $years = $edgar->listDirs('/edgar/full-index');
        foreach ($years as $year) {
            $quarters = $edgar->listDirs($year);
            foreach ($quarters as $quarter) {
                $data = $edgar->getZipContent('company', $quarter);
                foreach ($data['content'] as $item) {
                    print_r($edgar->getHeader($item['fileName']));
                }
                die();
            }
        }
    }
}