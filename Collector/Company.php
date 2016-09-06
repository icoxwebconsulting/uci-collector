<?php

namespace Collector;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Class Company
 *
 * @package Collector
 * @ODM\Document(db="uci")
 */
class Company
{
    /**
     * @ODM\Id
     */
    private $id;

    /**
     * @ODM\Field(type="string")
     */
    private $cik;

    /**
     * @ODM\Field(type="string")
     */
    private $conformedName;

    /**
     * @ODM\Field(type="string")
     */
    private $irsNumber;

    /**
     * @ODM\Field(type="string")
     */
    private $stateOfIncorporation;

    /**
     * @ODM\Field(type="string")
     */
    private $fiscalEndYear;

    /**
     * @ODM\EmbedOne(targetDocument="Address")
     */
    private $businessAddress;

    /**
     * @ODM\EmbedOne(targetDocument="Address")
     */
    private $mailAddress;

    /**
     * @ODM\EmbedOne(targetDocument="Owner")
     */
    private $owner;

    /**
     * @ODM\ReferenceOne(targetDocument="SIC")
     */
    private $assignedSIC;

    /**
     * @param Company $company
     * @param array $data
     * @param array $availableSICs
     */
    static public function updateFromArray(Company $company, array $data, array $availableSICs)
    {
        $newCompany = self::createFromArray($data, $availableSICs);
        $company->setAssignedSIC($newCompany->getAssignedSIC());
        $company->setConformedName($newCompany->getConformedName());
        $company->setIRSNumber($newCompany->getIRSNumber());
        $company->setFiscalEndYear($newCompany->getFiscalEndYear());
        $company->setBusinessAddress($newCompany->getBusinessAddress());
        $company->setMailAddress($newCompany->getMailAddress());
        $company->setOwner($newCompany->getOwner());
    }

    /**
     * @param array $data
     * @param array $availableSICs
     * @return Company|null
     */
    static public function createFromArray(array $data, array $availableSICs)
    {
        $companyData = array();
        $businessAddressData = array();
        $mailAddressData = array();
        $ownerData = array();
        $ownerAddressData = array();

        if (array_key_exists('SEC-HEADER', $data)) {
            $data = $data['SEC-HEADER'];

            if (array_key_exists('FILER', $data)) {
                $data = $data['FILER'];
                $companyData = array_key_exists('COMPANY-DATA', $data) ? $data['COMPANY-DATA'] : array();
                $businessAddressData = array_key_exists(
                    'BUSINESS-ADDRESS',
                    $data
                ) ? $data['BUSINESS-ADDRESS'] : array();
                $mailAddressData = array_key_exists('MAIL-ADDRESS', $data) ? $data['MAIL-ADDRESS'] : array();
            } elseif (array_key_exists('ISSUER', $data)) {
                // split inner owner data if exist
                $ownerRawData = array();
                if (array_key_exists('REPORTING-OWNER', $data)) {
                    $ownerRawData = $data['REPORTING-OWNER'];
                }

                // company data
                $data = $data['ISSUER'];
                $companyData = array_key_exists('COMPANY-DATA', $data) ? $data['COMPANY-DATA'] : array();
                $businessAddressData = array_key_exists(
                    'BUSINESS-ADDRESS',
                    $data
                ) ? $data['BUSINESS-ADDRESS'] : array();
                $mailAddressData = array_key_exists('MAIL-ADDRESS', $data) ? $data['MAIL-ADDRESS'] : array();

                // owner data
                $ownerData = array_key_exists(
                    'REPORTING-OWNER',
                    $ownerRawData
                ) ? $ownerRawData['REPORTING-OWNER'] : array();
                $ownerAddressData = array_key_exists(
                    'MAIL-ADDRESS',
                    $ownerRawData
                ) ? $ownerRawData['MAIL-ADDRESS'] : array();
            }

            if (!empty($companyData) &&
                (array_key_exists('CIK', $companyData) && $companyData['CIK']) &&
                (array_key_exists('ASSIGNED-SIC', $companyData) &&
                    $companyData['ASSIGNED-SIC'] &&
                    $availableSICs[$companyData['ASSIGNED-SIC']]) &&
                (!empty($businessAddressData) || !empty($mailAddressData))
            ) {
                $sic = $availableSICs[$companyData['ASSIGNED-SIC']];
                $businessAddress = Address::createFromArray($businessAddressData);
                $mailAddress = Address::createFromArray($mailAddressData);
                $owner = Owner::createFromArray($ownerData, $ownerAddressData);

                $company = new Company(
                    array_key_exists('CIK', $companyData) ? $companyData['CIK'] : null,
                    array_key_exists('CONFORMED-NAME', $companyData) ? $companyData['CONFORMED-NAME'] : null,
                    $sic,
                    array_key_exists('IRS-NUMBER', $companyData) ? $companyData['IRS-NUMBER'] : null,
                    array_key_exists(
                        'STATE-OF-INCORPORATION',
                        $companyData
                    ) ? $companyData['STATE-OF-INCORPORATION'] : null,
                    array_key_exists('FISCAL-YEAR-END', $companyData) ? $companyData['FISCAL-YEAR-END'] : null,
                    $businessAddress,
                    $mailAddress,
                    $owner
                );

                return $company;
            }
        }
    }

    /**
     * Company constructor.
     *
     * @param string|null $cik
     * @param string|null $conformedName
     * @param SIC|null $assignedSIC
     * @param string|null $irsNumber
     * @param string|null $stateOfIncorporation
     * @param string|null $fiscalEndYear
     * @param Address $businessAddress
     * @param Address $mailAddress
     * @param Owner $owner
     */
    public function __construct(
        string $cik,
        string $conformedName,
        SIC $assignedSIC,
        string $irsNumber = null,
        string $stateOfIncorporation = null,
        string $fiscalEndYear = null,
        Address $businessAddress,
        Address $mailAddress,
        Owner $owner
    ) {
        $this->cik = $cik;
        $this->conformedName = $conformedName;
        $this->assignedSIC = $assignedSIC;
        $this->irsNumber = $irsNumber;
        $this->stateOfIncorporation = $stateOfIncorporation;
        $this->fiscalEndYear = $fiscalEndYear;
        $this->businessAddress = $businessAddress;
        $this->mailAddress = $mailAddress;
        $this->owner = $owner;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
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
    public function getConformedName()
    {
        return $this->conformedName;
    }

    /**
     * @return mixed
     */
    public function getIRSNumber()
    {
        return $this->irsNumber;
    }

    /**
     * @return mixed
     */
    public function getStateOfIncorporation()
    {
        return $this->stateOfIncorporation;
    }

    /**
     * @return mixed
     */
    public function getFiscalEndYear()
    {
        return $this->fiscalEndYear;
    }

    /**
     * @return mixed
     */
    public function getBusinessAddress()
    {
        return $this->businessAddress;
    }

    /**
     * @return mixed
     */
    public function getMailAddress()
    {
        return $this->mailAddress;
    }

    /**
     * @return mixed
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @return mixed
     */
    public function getAssignedSIC()
    {
        return $this->assignedSIC;
    }

    /**
     * @param string $conformedName
     */
    public function setConformedName(string $conformedName)
    {
        $this->conformedName = $conformedName;
    }

    /**
     * @param string $irsNumber
     */
    public function setIRSNumber(string $irsNumber)
    {
        $this->irsNumber = $irsNumber;
    }

    /**
     * @param string $stateOfIncorporation
     */
    public function setStateOfIncorporation(string $stateOfIncorporation)
    {
        $this->stateOfIncorporation = $stateOfIncorporation;
    }

    /**
     * @param string $fiscalEndYear
     */
    public function setFiscalEndYear(string $fiscalEndYear)
    {
        $this->fiscalEndYear = $fiscalEndYear;
    }

    /**
     * @param Address $businessAddress
     */
    public function setBusinessAddress(Address $businessAddress)
    {
        $this->businessAddress = $businessAddress;
    }

    /**
     * @param Address $mailAddress
     */
    public function setMailAddress(Address $mailAddress)
    {
        $this->mailAddress = $mailAddress;
    }

    /**
     * @param Owner $owner
     */
    public function setOwner(Owner $owner)
    {
        $this->owner = $owner;
    }

    /**
     * @param SIC $assignedSIC
     */
    public function setAssignedSIC(SIC $assignedSIC)
    {
        $this->assignedSIC = $assignedSIC;
    }
}