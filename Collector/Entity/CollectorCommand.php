<?php

namespace Collector\Entity;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Class CollectorCommand
 *
 * @package Collector
 * @ODM\Document(db="uci")
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
     * @ODM\Field(type="int")
     */
    private $lastCount;

    /**
     * CollectorCommand constructor.
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
        $this->running = false;
    }

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
    public function isRunning():bool
    {
        if ($this->running === null) {
            $this->running = false;
        }

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
        if ($this->lastYear === null) {
            $this->lastYear = '1000';
        }

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
        if ($this->lastQuarter === null) {
            $this->lastQuarter = 'QT0';
        }

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
     * @return int
     */
    public function getLastCount():int
    {
        if ($this->lastCount === null) {
            $this->lastCount = 0;
        }

        return $this->lastCount;
    }

    /**
     * @param int $lastCount
     */
    public function setLastCount(int $lastCount)
    {
        $this->lastCount = $lastCount;
    }
}