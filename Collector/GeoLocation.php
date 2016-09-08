<?php

namespace Collector;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Class GeoLocation
 *
 * @package Collector
 * @ODM\EmbeddedDocument
 */
class GeoLocation
{
    /**
     * @ODM\Field(type="string")
     */
    private $latitude;

    /**
     * @ODM\Field(type="string")
     */
    private $longitude;

    /**
     * GeoLocation constructor.
     *
     * @param string $latitude
     * @param string $longitude
     */
    public function __construct(
        string $latitude,
        string $longitude
    ) {
        $this->latitude = $latitude;
        $this->longitude = $longitude;
    }

    /**
     * @return string
     */
    public function getLatitude():string
    {
        return $this->latitude;
    }

    /**
     * @return string
     */
    public function getLongitude():string
    {
        return $this->longitude;
    }
}