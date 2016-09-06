<?php

namespace Collector;

use Doctrine\ODM\MongoDB\DocumentManager;
use Monolog\Logger;

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
     * @var Logger
     */
    private $logger;

    /**
     * CompanyCollector constructor.
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
        $this->logger->info('Starting Process');
        $sics = $this->dm->getRepository('Collector\SIC')->findAll();
        $availableSICS = array_reduce(
            $sics,
            function ($carry, $current) {
                $carry[$current->getCode()] = $current;

                return $carry;
            },
            array()
        );

        $edgar = new EDGAR();
        $years = $edgar->listDirs('/edgar/full-index');
        $i = 0;
        foreach ($years as $year) {
            $quarters = $edgar->listDirs($year);
            foreach ($quarters as $quarter) {
                $data = $edgar->getZipContent('company', $quarter);
                foreach ($data['content'] as $item) {
                    $data = $edgar->getHeader($item['fileName']);
                    $company = Company::buildFromArray($data, $availableSICS);
                    if ($company) {
                        echo 'saving company'.$company->getConformedName().PHP_EOL;
                        $this->dm->persist($company);
                    } else {
                        echo 'skipping company'.PHP_EOL;
                    }
                }
            }
            $i++;
            echo $i.PHP_EOL;
            if ($i > 9) {
                $this->dm->flush();
                echo 'done'.PHP_EOL;
                die();
            }
        }
        $this->dm->flush();
        $this->logger->info('Process End');
    }
}