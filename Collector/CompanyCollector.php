<?php

namespace Collector;

use Collector\Entity\CollectorCommand;
use Collector\Entity\Company;
use Collector\Entity\GeoLocation;
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
     * How many to retrieve before save them to db
     */
    const BATCH = 10;

    /**
     * Name of the command
     */
    const NAME = 'company';

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var GMaps
     */
    private $gmaps;

    /**
     * @var EDGAR
     */
    private $edgar;

    /**
     * @var CollectorCommand
     */
    private $collector;

    /**
     * @return CollectorCommand
     */
    private function getCollectorEntity():CollectorCommand
    {
        $collector = $this->dm->getRepository('Collector\Entity\CollectorCommand')->findOneBy(
            array('name' => self::NAME)
        );

        if (!$collector) {
            $collector = new CollectorCommand(self::NAME);
            $this->dm->persist($collector);
            $this->dm->flush();
        }

        return $collector;
    }

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
        $this->gmaps = new GMaps();
        $this->edgar = new EDGAR();
        $this->collector = $this->getCollectorEntity();
    }

    /**
     * @return array
     */
    private function getSICs():array
    {
        $this->logger->info('Collecting SICs from DB');
        $sics = $this->dm->getRepository('Collector\Entity\SIC')->findAll();
        $availableSICS = array_reduce(
            $sics,
            function ($carry, $current) {
                $carry[$current->getCode()] = $current;

                return $carry;
            },
            array()
        );

        return $availableSICS;
    }

    /**
     * @param Company $company
     * @return Company|null
     */
    private function locateCompany(Company $company)
    {
        $address = $company->getBusinessAddress()->getFullAddress();

        $this->logger->debug(sprintf('Retrieve geo location for address %s', $address));

        $geoLocation = $this->gmaps->getLocation($address);

        if (array_key_exists('latitude', $geoLocation) &&
            $geoLocation['latitude'] &&
            array_key_exists('longitude', $geoLocation) &&
            $geoLocation['longitude']
        ) {
            $geoLocation = new GeoLocation($geoLocation['latitude'], $geoLocation['longitude']);
            $company->setGeoLocation($geoLocation);

            return $company;
        } else {
            $this->logger->debug('Company address was not converted to geo location');
        }

        return null;
    }

    /**
     * @param array $data
     * @param array $availableSICS
     * @return Company|null
     */
    private function createCompany(array $data, array $availableSICS)
    {
        $company = Company::createFromArray($data, $availableSICS);
        if ($company) {
            $company = $this->locateCompany($company);

            if ($company) {
                return $company;
            }
        }

        return null;
    }

    /**
     * @param Company $company
     * @param array $companies
     * @param array $data
     * @param array $availableSICS
     */
    private function store(Company $company, array &$companies, array $data, array $availableSICS)
    {
        $this->logger->debug(
            sprintf('Find if company %s already exist in UCI db or loaded', $company->getConformedName())
        );

        // look on db
        $existingCompany = $this->dm->getRepository('Collector\Entity\Company')->findOneBy(
            array('cik' => $company->getCIK())
        );

        // look on loaded
        if (!$existingCompany && array_key_exists($company->getCIK(), $companies)) {
            $existingCompany = $companies[$company->getCIK()];
        }

        if ($existingCompany) {
            $this->logger->debug(sprintf('Company %s already exist in UCI db', $company->getConformedName()));
            $company = Company::updateFromArray($existingCompany, $data, $availableSICS);
            $this->locateCompany($company);
        } else {
            $this->dm->persist($company);
            $companies[$company->getCIK()] = $company;
        }
    }

    /**
     * @param array $item
     * @param array $availableSICS
     * @param array $companies
     */
    private function parseItem(array $item, array $availableSICS, array &$companies)
    {
        $fileName = $item['fileName'];
        $this->logger->debug(sprintf('Collecting header file for %s', $fileName));
        $data = $this->edgar->getHeader($fileName);
        $company = $this->createCompany($data, $availableSICS);

        if ($company) {
            $this->logger->debug(
                sprintf(
                    'Header file for %s contains valid company %s data',
                    $fileName,
                    $company->getConformedName()
                )
            );

            $this->store($company, $companies, $data, $availableSICS);

        } else {
            $this->logger->debug(
                sprintf('Header file for %s does not contains a valid company data', $fileName)
            );
        }
    }

    /**
     * @param array $items
     * @param array $availableSICS
     * @param array $companies
     * @param string $quarter
     */
    private function parseItems(array $items, array $availableSICS, array &$companies, string $quarter)
    {
        $this->logger->info(sprintf('Quarter %s has %s company files', $quarter, count($items)));

        $items = array_slice($items, $this->collector->getLastCount());

        foreach ($items as $item) {
            $this->parseItem($item, $availableSICS, $companies);

            if (count($companies) == self::BATCH) {
                $this->logger->info(sprintf('Saving batch of %s companies for quarter %s', self::BATCH, $quarter));
                $this->collector->setLastCount($this->collector->getLastCount() + self::BATCH);
                $this->dm->flush();
                $companies = array();
            }
        }
    }

    /**
     * @param $year
     * @return string
     */
    private function getYearFromYearString($year):string
    {
        return substr($year, strlen($year) - 4);
    }

    /**
     * @param $quarter
     * @return string
     */
    private function getQuarterFromQuarterString($quarter):string
    {
        return substr($quarter, strlen($quarter) - 4);
    }


    /**
     * @param string $year
     * @param array $availableSICS
     * @param array $companies
     */
    private function getQuarters(string $year, array $availableSICS, array &$companies)
    {
        $this->logger->info(sprintf('Collecting Quarters for %s year', $year));
        $quarters = $this->edgar->listDirs($year);
        $this->logger->info(sprintf('Year %s has %s quarters', $year, count($quarters)));

        // remove already processed quarters
        $quarters = array_reduce(
            $quarters,
            function ($carry, $current) {
                if ($this->getQuarterFromQuarterString($current) >= $this->collector->getLastQuarter()) {
                    $carry[] = $current;
                }

                return $carry;
            },
            array()
        );

        foreach ($quarters as $quarter) {
            $this->collector->setLastQuarter($this->getQuarterFromQuarterString($quarter));
            $this->dm->flush();

            $this->logger->info(sprintf('Collecting company zip for quarter %s', $quarter));
            $data = $this->edgar->getZipContent('company', $quarter);
            if (!empty($data)) {
                $items = $data['content'];
                $this->parseItems($items, $availableSICS, $companies, $quarter);
            }
        }
    }

    /**
     * @param array $availableSICS
     * @param array $companies
     */
    private function getYears(array $availableSICS, array &$companies)
    {
        $this->logger->info('Collecting full index');
        $years = $this->edgar->listDirs('/edgar/full-index');
        $this->logger->info(sprintf('Full index has %s years', count($years)));

        // remove already processed years
        $years = array_reduce(
            $years,
            function ($carry, $current) {
                if ($this->getYearFromYearString($current) >= $this->collector->getLastYear()) {
                    $carry[] = $current;
                }

                return $carry;
            },
            array()
        );

        foreach ($years as $year) {
            $this->collector->setLastYear($this->getYearFromYearString($year));
            $this->dm->flush();
            $this->getQuarters($year, $availableSICS, $companies);
        }
    }

    /**
     * Run
     */
    public function run()
    {
        $this->logger->info('Starting Process');

        // prevent failure
        if ($this->collector->isRunning() && $this->collector->getLastRun()->diff(new \DateTime())->days > 10) {
            $this->collector->setRunning(false);
        }

        if (!$this->collector->isRunning()) {
            $this->collector->setRunning(true);
            $this->collector->setLastRun(new \DateTime());
            $this->dm->flush();

            $availableSICS = $this->getSICs();

            // memory storage
            $companies = array();

            $this->getYears($availableSICS, $companies);
        } else {
            $this->logger->info('Process Already Running');
        }

        $this->collector->setRunning(false);
        $this->collector->setLastRun(new \DateTime());
        $this->dm->flush();

        $this->logger->info('Process End');
    }
}