<?php

namespace Collector\Entity;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Class Address
 *
 * @package Collector
 * @ODM\EmbeddedDocument
 */
class Address
{
    /**
     * @ODM\Field(type="string")
     */
    private $street1;

    /**
     * @ODM\Field(type="string")
     */
    private $street2;

    /**
     * @ODM\Field(type="string")
     */
    private $city;

    /**
     * @ODM\Field(type="string")
     */
    private $state;

    /**
     * @ODM\Field(type="string")
     */
    private $zip;

    /**
     * @ODM\Field(type="string")
     */
    private $phone;

    /**
     * Address constructor.
     *
     * @param string|null $street1
     * @param string|null $street2
     * @param string|null $city
     * @param string|null $state
     * @param string|null $zip
     * @param string|null $phone
     */
    public function __construct(
        string $street1 = null,
        string $street2 = null,
        string $city = null,
        string $state = null,
        string $zip = null,
        string $phone = null
    ) {
        $this->street1 = $street1;
        $this->street2 = $street2;
        $this->city = $city;
        $this->state = $state;
        $this->zip = $zip;
        $this->phone = $phone;
    }

    /**
     * @param array $data
     * @return Address
     */
    static public function createFromArray(array $data):Address
    {
        $street1 = array_key_exists('STREET1', $data) ? $data['STREET1'] : null;
        $street2 = array_key_exists('STREET2', $data) ? $data['STREET2'] : null;
        $city = array_key_exists('CITY', $data) ? $data['CITY'] : null;
        $state = array_key_exists('STATE', $data) ? $data['STATE'] : null;
        $zip = array_key_exists('ZIP', $data) ? $data['ZIP'] : null;
        $phone = array_key_exists('PHONE', $data) ? $data['PHONE'] : null;

        $address = new Address($street1, $street2, $city, $state, $zip, $phone);

        return $address;
    }

    /**
     * @return string
     */
    public function getStreet1()
    {
        return $this->street1;
    }

    /**
     * @return string
     */
    public function getStreet2()
    {
        return $this->street2;
    }

    /**
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @return string
     */
    public function getZip()
    {
        return $this->zip;
    }

    /**
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @return string
     */
    public function getFullAddress():string
    {
        return sprintf('%s %s, %s, %s', $this->street1, $this->street2, $this->city, $this->state);
    }
}