<?php

namespace Sunnysideup\EcommerceSecondHandProduct\Model;

use DataObject;
use Member;
use Permission;
use EcommerceConfig;
use Config;
use EcommerceCMSButtonField;
use ReadonlyField;



class SecondHandArchive extends DataObject
{

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * OLD: private static $db (case sensitive)
  * NEW: 
    private static $table_name = '[SEARCH_REPLACE_CLASS_NAME_GOES_HERE]';

    private static $db (COMPLEX)
  * EXP: Check that is class indeed extends DataObject and that it is not a data-extension!
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
    
    private static $table_name = 'SecondHandArchive';

    private static $db = array(
        'Title' => 'Varchar(255)',
        'InternalItemID' => 'Varchar(50)',
        'SerialNumber' => 'Varchar(50)',
        'DateItemWasBought' => 'Date',
        'DateItemWasSold' => 'Date',
        'ProductQuality' => 'ENUM("1, 2, 3, 4, 5, 6, 7, 8, 9, 10","10")',
        'SoldOnBehalf' => 'Boolean',
        'PurchasePrice' => 'Currency',
        'Price' => 'Currency',
        'SoldPrice' => 'Currency',
        'IncludesBoxOrCase' => 'ENUM("No, Box, Case, Both","No")',
        'OriginalManual' => 'Boolean',
        'PageID' => 'Int',
        'Description' => 'Varchar(255)',
        'SellersName' =>  'Varchar(50)',
        'SellersPhone' =>  'Varchar(30)',
        'SellersEmail' =>  'Varchar(255)',
        'SellersAddress' =>  'Varchar(255)',
        'SellersAddress2' => 'Varchar(255)',
        'SellersCity' => 'Varchar(100)',
        'SellersPostalCode' => 'Varchar(50)',
        'SellersRegionCode' => 'Varchar(100)',
        'SellersCountry' => 'Varchar(50)',
        'SellersIDType' => 'ENUM(",Drivers Licence, Firearms Licence, Passport","")',
        'SellersIDNumber' => 'Varchar(50)',
        'SellersDateOfBirth' => 'Date',
        'SellersIDExpiryDate' => 'Date',
        'SellersIDPhotocopy' => 'Boolean'
    );

    private static $has_one = [
        'ArchivedBy' => Member::class,
    ];

    public static function create_from_page($page)
    {
        if ($page->InternalItemID) {
            $filter = array(
                'InternalItemID' => $page->InternalItemID
            );
        } else {
            $filter = array(
                'PageID' => $page->ID
            );
        }
        $obj = SecondHandArchive::get()->filter($filter)->first();
        if (! $obj) {
            $obj = SecondHandArchive::create($filter);
        }
        $obj->Title = $page->Title;
        $obj->Price = $page->Price;
        $obj->InternalItemID = $page->InternalItemID;
        $obj->SerialNumber = $page->SerialNumber;
        $obj->DateItemWasBought = $page->DateItemWasBought;
        $obj->DateItemWasSold = $page->DateItemWasSold;
        $obj->ProductQuality = $page->ProductQuality;
        $obj->SoldOnBehalf = $page->SellingOnBehalf;
        $obj->PurchasePrice = $page->PurchasePrice;
        $obj->SoldPrice = $page->SoldPrice;
        $obj->IncludesBoxOrCase = $page->IncludesBoxOrCase;
        $obj->OriginalManual = $page->OriginalManual;
        $obj->PageID = $page->ID;
        $obj->Description = $page->ShortDescription;
        $obj->ArchivedByID = $page->ArchivedByID;

        //sellers details
        $obj->SellersName = $page->SellersName;
        $obj->SellersPhone = $page->SellersPhone;
        $obj->SellersEmail = $page->SellersEmail;
        $obj->SellersAddress = $page->SellersAddress;
        $obj->SellersAddress2 = $page->SellersAddress2;
        $obj->SellersCity = $page->SellersCity;
        $obj->SellersPostalCode = $page->SellersPostalCode;
        $obj->SellersRegionCode = $page->SellersRegionCode;
        $obj->SellersCountry = $page->SellersCountry;
        $obj->SellersIDType = $page->SellersIDType;
        $obj->SellersIDNumber = $page->SellersIDNumber;
        $obj->SellersDateOfBirth = $page->SellersDateOfBirth;
        $obj->SellersIDExpiryDate = $page->SellersIDExpiryDate;
        $obj->SellersIDPhotocopy = $page->SellersIDPhotocopy;

        $obj->write();
        return $obj;
    }


    /**
     * stadard SS method
     * @return Boolean
     */
    public function canCreate($member = null, $context = [])
    {
        return false;
    }


    /**
     * stadard SS method
     * @return Boolean
     */
    public function canEdit($member = null, $context = [])
    {
        return false;
    }

    /**
     * stadard SS method
     * @return Boolean
     */
    public function canView($member = null, $context = [])
    {
        return Permission::check(
            EcommerceConfig::get('SecondHandProduct', 'second_hand_admin_permission_code')
        );
    }

    /**
     * stadard SS method
     * @return Boolean
     */
    public function canDelete($member = null, $context = [])
    {
        return false;
    }


    private static $singular_name = 'Archived Second Hand Product';

    public function i18n_singular_name()
    {
        return self::$singular_name;
    }

    private static $plural_name = 'Archived Second Hand Products';

    public function i18n_plural_name()
    {
        return self::$plural_name;
    }

    private static $indexes = array(
        'PageID' => true,
        'InternalItemID' => true
    );

    private static $default_sort = array(
        'LastEdited' => 'DESC'
    );

    private static $summary_fields = array(
        'Title' => 'Title',
        'InternalItemID' => 'Code',
        'SerialNumber' => 'Serial',
        'DateItemWasBought' => 'Date Entered',
        'DateItemWasSold' => 'Date Sold',
        'ProductQuality' => 'Quality',
        'SoldOnBehalf.Nice' => 'On Behalf',
        'PurchasePrice' => 'Purchase Price',
        'Price' => 'Sale/Ticket Price',
        'SoldPrice' => 'Sold Price',
    );

    private static $field_labels = array(
        'Title' => 'Title',
        'Price' => 'Sale Price',
        'InternalItemID' => 'Code',
        'PurchasePrice' => 'Purchase Price',
        'ProductQuality' => 'Quality',
        'IncludesBoxOrCase' => 'Includes',
        'OriginalManual' => 'Has Manual',
        'SerialNumber' => 'Serial Number'
    );

    private static $searchable_fields = array(
        'Title' => 'PartialMatchFilter',
        'Price' => 'ExactMatchFilter',
        'InternalItemID' => 'PartialMatchFilter',
        'PurchasePrice' => 'ExactMatchFilter',
        'ProductQuality' => 'ExactMatchFilter',
        'IncludesBoxOrCase' => 'ExactMatchFilter',
        'OriginalManual' => 'ExactMatchFilter',
        'SerialNumber' => 'PartialMatchFilter'
    );

    private static $show_restore_button = false;

    /**
     * stadard SS method
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        if(Config::inst()->get(SecondHandArchive::class, 'show_restore_button')){
            $fields->addFieldsToTab(
                'Root.Main',
                [
                    EcommerceCMSButtonField::create(
                        'RestoreButton',
                        '/admin/secondhandproducts/SecondHandProduct/restore/?productid=' . $this->PageID,
                        _t('SecondHandArchive.RESTORE_BUTTON', 'Restore Product')
                    )
                ]
            );
        }
        $fields->addFieldsToTab(
            'Root.Main',
            [
                ReadonlyField::create('Created', 'Created')
            ]
        );
        $fields->addFieldsToTab(
            'Root.SellersDetails',
            [
                $fields->dataFieldByName("SellersName"),
                $fields->dataFieldByName("SellersPhone"),
                $fields->dataFieldByName("SellersEmail"),
                $fields->dataFieldByName("SellersAddress"),
                $fields->dataFieldByName("SellersAddress2"),
                $fields->dataFieldByName("SellersCity"),
                $fields->dataFieldByName("SellersPostalCode"),
                $fields->dataFieldByName("SellersRegionCode"),
                $fields->dataFieldByName("SellersCountry"),
                $fields->dataFieldByName("SellersIDType"),
                $fields->dataFieldByName("SellersIDNumber"),
                $fields->dataFieldByName("SellersDateOfBirth"),
                $fields->dataFieldByName("SellersIDExpiryDate"),
                $fields->dataFieldByName("SellersIDPhotocopy")
            ]
        );
        return $fields;
    }
}

