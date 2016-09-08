<?php

namespace Collector;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Class SIC
 *
 * @package Collector
 * @ODM\Document(db="uci")
 */
class SIC
{
    /**
     * @ODM\Id
     */
    private $id;

    /**
     * @ODM\Field(type="string")
     */
    private $code;

    /**
     * @ODM\Field(type="string")
     */
    private $office;

    /**
     * @ODM\Field(type="string")
     */
    private $title;

    /**
     * SIC constructor.
     *
     * @param string $code
     * @param string $office
     * @param string $title
     */
    public function __construct(string $code, string $office, string $title)
    {
        $this->code = $code;
        $this->office = $office;
        $this->title = $title;
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
    public function getCode():string
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getOffice():string
    {
        return $this->office;
    }

    /**
     * @return string
     */
    public function getTitle():string
    {
        return $this->title;
    }
}