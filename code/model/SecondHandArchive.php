<?php


class SecondHandArchive extends DataObject
{
    private static $db = array(
        'Title' => 'Varchar(255)',
        'InternalItemID' => 'Varchar(50)',
        'SerialNumber' => 'VarChar(50)',
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
        'Description' => 'VarChar(255)',
        'SellersName' =>  'VarChar(50)',
        'SellersPhone' =>  'VarChar(30)',
        'SellersEmail' =>  'VarChar(255)',
        'SellersAddress' =>  'VarChar(255)',
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
    public function canCreate($member = null)
    {
        return false;
    }


    /**
     * stadard SS method
     * @return Boolean
     */
    public function canEdit($member = null)
    {
        return false;
    }

    /**
     * stadard SS method
     * @return Boolean
     */
    public function canView($member = null)
    {
        return Permission::check(
            EcommerceConfig::get('SecondHandProduct', 'second_hand_admin_permission_code')
        );
    }

    /**
     * stadard SS method
     * @return Boolean
     */
    public function canDelete($member = null)
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

    /**
     * stadard SS method
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
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
