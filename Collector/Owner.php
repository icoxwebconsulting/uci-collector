<?php

namespace Collector;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Class Owner
 *
 * @package Collector
 * @ODM\EmbeddedDocument
 */
class Owner
{
    /**
     * @ODM\Field(type="string")
     */
    private $conformedName;

    /**
     * @ODM\Field(type="string")
     */
    private $cik;

    /**
     * @ODM\EmbedOne(targetDocument="Address")
     */
    private $mailAddress;

    /**
     * @param $ownerData
     * @param $ownerAddressData
     * @return Owner
     */
    static public function createFromArray($ownerData, $ownerAddressData):Owner
    {
        $street1 = array_key_exists('STREET1', $ownerAddressData) ? $ownerAddressData['STREET1'] : null;
        $street2 = array_key_exists('STREET2', $ownerAddressData) ? $ownerAddressData['STREET1'] : null;
        $city = array_key_exists('CITY', $ownerAddressData) ? $ownerAddressData['CITY'] : null;
        $state = array_key_exists('STATE', $ownerAddressData) ? $ownerAddressData['STATE'] : null;
        $zip = array_key_exists('ZIP', $ownerAddressData) ? $ownerAddressData['ZIP'] : null;
        $phone = array_key_exists('PHONE', $ownerAddressData) ? $ownerAddressData['PHONE'] : null;

        $address = new Address($street1, $street2, $city, $state, $zip, $phone);

        $conformedName = array_key_exists('CONFORMED-NAME', $ownerData) ? $ownerData['CONFORMED-NAME'] : null;
        $cik = array_key_exists('CIK', $ownerData) ? $ownerData['CIK'] : null;

        $owner = new Owner($conformedName, $cik, $address);

        return $owner;
    }

    /**
     * Owner constructor.
     *
     * @param string|null $conformedName
     * @param string|null $cik
     * @param Address|null $mailAddress
     */
    public function __construct(string $conformedName = null, string $cik = null, Address $mailAddress = null)
    {
        $this->conformedName = $conformedName;
        $this->cik = $cik;
        $this->mailAddress = $mailAddress;
    }

    /**
     * @return mixed
     */
    public function getConformedName()
    {
        return $this->conformedName;
    }

    /**
     * @return mixed
     */
    public function getCIK()
    {
        return $this->cik;
    }

    /**
     * @return mixed
     */
    public function getMailAddress()
    {
        return $this->mailAddress;
    }
}