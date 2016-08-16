<?php


class SecondHandArchive extends DataObject
{
    private static $db = array(
        'Title' => 'Varchar(255)',
        'Price' => 'Currency',
        'InternalItemID' => 'Varchar(50)',
        'PurchasePrice' => 'Currency',
        'ProductQuality' => 'ENUM("1, 2, 3, 4, 5, 6, 7, 8, 9, 10","10")',
        'IncludesBoxOrCase' => 'ENUM("No, Box, Case, Both","No")',
        'OriginalManual' => 'Boolean',
        'SerialNumber' => 'VarChar(50)',
        'PageID' => 'Int'
    );

    public static function create_from_page($page)
    {
        if($page->InternalItemID) {
            $filter = array(
                'InternalItemID' => $page->InternalItemID
            );
        } else {
            $filter = array(
                'PageID' => $page->ID
            );
        }
        $obj = SecondHandArchive::get()->filter($filter)->first()
        if( ! $obj) {
            $obj = SecondHandArchive::create($filter);
        }
        $obj->Title = $page->Title;
        $obj->Price = $page->Price;
        $obj->InternalItemID = $page->InternalItemID;
        $obj->PageID = $page->ID;
        $obj->PurchasePrice = $page->PurchasePrice;
        $obj->ProductQuality = $page->ProductQuality;
        $obj->IncludesBoxOrCase = $page->IncludesBoxOrCase;
        $obj->OriginalManual = $page->OriginalManual;
        $obj->SerialNumber = $page->SerialNumber;
        $obj->write();
        return $obj;
    }


    private static $singular_name = 'Archived Second Hand Product';

    function i18n_singular_name() { return self::$singular_name;}

    private static $plural_name = 'Archived Second Hand Products';

    function i18n_plural_name() { return self::$plural_name;}

    private static $indexes = array(
        'PageID' => true,
        'InternalItemID' => true
    );

    private static $default_sort = array(
        'Title' => 'ASC'
    );

    private static $summary_fields = array(
        'Title' => 'Title',
        'Price' => 'Sale Price',
        'InternalItemID' => 'Code',
        'PurchasePrice' => 'Purchase Price',
        'ProductQuality' => 'Quality',
        'IncludesBoxOrCase' => 'Includes',
        'OriginalManual.Nice' => 'Has Manual',
        'SerialNumber' => 'Serial Number'
    );

    private static $field_labels = array(
        'Title' => 'Title',
        'Price' => 'Sale Price',
        'InternalItemID' => 'Code',
        'PurchasePrice' => 'Purcahse Price',
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
}
