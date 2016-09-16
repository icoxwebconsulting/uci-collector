<?php

namespace Collector\Entity;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Class CollectorCommand
 *
 * @package Collector
 * @ODM\Document(db="collector-command")
 */
class CollectorCommand
{
    /**
     * @ODM\Id
     */
    private $id;

    /**
     * @ODM\Field(type="string")
     */
    private $name;

    /**
     * @ODM\Field(type="boolean")
     */
    private $running;

    /**
     * @ODM\Field(type="date")
     */
    private $lastRun;

    /**
     * @ODM\Field(type="string")
     */
    private $lastYear;

    /**
     * @ODM\Field(type="string")
     */
    private $lastQuarter;

    /**
     * @ODM\Field(type="string")
     */
    private $lastFile;

    /**
     * @return string
     */
    public function getId():string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName():string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * @return bool
     */
    public function getRunning():bool
    {
        return $this->running;
    }

    /**
     * @param bool $running
     */
    public function setRunning(bool $running)
    {
        $this->running = $running;
    }

    /**
     * @return \DateTime
     */
    public function getLastRun():\DateTime
    {
        return $this->lastRun;
    }

    /**
     * @param \DateTime $lastRun
     */
    public function setLastRun(\DateTime $lastRun)
    {
        $this->lastRun = $lastRun;
    }

    /**
     * @return string
     */
    public function getLastYear():string
    {
        return $this->lastYear;
    }

    /**
     * @param string $lastYear
     */
    public function setLastYear(string $lastYear)
    {
        $this->lastYear = $lastYear;
    }

    /**
     * @return string
     */
    public function getLastQuarter():string
    {
        return $this->lastQuarter;
    }

    /**
     * @param string $lastQuarter
     */
    public function setLastQuarter(string $lastQuarter)
    {
        $this->lastQuarter = $lastQuarter;
    }

    /**
     * @return string
     */
    public function getLastFile():string
    {
        return $this->lastFile;
    }

    /**
     * @param string $lastFile
     */
    public function setLastFile(string $lastFile)
    {
        $this->lastFile = $lastFile;
    }
}