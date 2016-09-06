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
        $this->logger->info('Collecting SICs from DB');
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
        $this->logger->info('Collecting full index');
        $years = $edgar->listDirs('/edgar/full-index');
        $this->logger->info(sprintf('Full index has %s years', count($years)));
        $i = 0;
        foreach ($years as $year) {
            $this->logger->info(sprintf('Collecting Quarters for %s year', $year));
            $quarters = $edgar->listDirs($year);
            $this->logger->info(sprintf('Year %s has %s quarters', $year, count($quarters)));
            foreach ($quarters as $quarter) {
                $this->logger->info(sprintf('Collecting company zip for quarter %s', $quarter));
                $data = $edgar->getZipContent('company', $quarter);
                $items = $data['content'];
                $this->logger->info(sprintf('Quarter %s has %s company files', $quarter, count($items)));
                foreach ($items as $item) {
                    $fileName = $item['fileName'];
                    $this->logger->info(sprintf('Collecting header file for %s', $fileName));
                    $data = $edgar->getHeader($fileName);
                    $company = Company::createFromArray($data, $availableSICS);
                    if ($company) {
                        $this->logger->info(
                            sprintf(
                                'Header file for %s contains valid company %s data',
                                $fileName,
                                $company->getConformedName()
                            )
                        );

                        $this->logger->info(
                            sprintf('Find if company %s already exist in UCI db', $company->getConformedName())
                        );
                        $existingCompany = $this->dm->getRepository('Collector\Company')->findOneBy(
                            array('cik' => $company->getCIK())
                        );

                        if ($existingCompany) {
                            $this->logger->info(
                                sprintf(
                                    'Company %s already exist in UCI db with id %s',
                                    $company->getConformedName(),
                                    $existingCompany->getId()
                                )
                            );
                            Company::updateFromArray($existingCompany, $data, $availableSICS);
                        } else {
                            $this->dm->persist($company);
                        }
                    } else {
                        $this->logger->info(
                            sprintf('Header file for %s does not contains a valid company data', $fileName)
                        );
                    }
                }
                $this->logger->info(sprintf('Saving companies for quarter %s', $quarter));
                $this->dm->flush();
            }
            $i++;
            if ($i > 3) {
                die();
            }
        }
        $this->logger->info('Process End');
    }
}