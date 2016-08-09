<?php


class SecondHandArchive extends DataObject
{
    private static $db = array(
        'Title' => 'Varchar(255)',
        'Price' => 'Currency',
        'PurchasePrice' => 'Currency',
        'ProductQuality' => 'ENUM("1, 2, 3, 4, 5, 6, 7, 8, 9, 10","10")',
        'IncludesBoxOrCase' => 'ENUM("No, Box, Case, Both","No")',
        'OriginalManual' => 'Boolean',
        'SerialNumber' => 'VarChar(50)',
        'PageID' => 'Int'
    );

    public static function create_from_page($page)
    {
        $filter = array(
            'PageID' => $page->ID
        );
        if(SecondHandArchive::get()->filter($filter)->count()) {
            $obj = SecondHandArchive::get()->filter($filter)->first();
        } else {
            $obj = SecondHandArchive::create($filter);
        }
        $obj->Title = $page->Title;
        $obj->Price = $page->Price;
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
        'PageID' => true
    );

    private static $default_sort = array(
        'Title' => 'ASC'
    );

    private static $summary_fields = array(
        'Title' => 'Title',
        'Price' => 'Sale Price',
        'PurchasePrice' => 'Purcahse Price',
        'ProductQuality' => 'Quality',
        'IncludesBoxOrCase' => 'Includes',
        'OriginalManual.Nice' => 'Has Manual',
        'SerialNumber' => 'Serial Number'
    );

    private static $field_labels = array(
        'Title' => 'Title',
        'Price' => 'Sale Price',
        'PurchasePrice' => 'Purcahse Price',
        'ProductQuality' => 'Quality',
        'IncludesBoxOrCase' => 'Includes',
        'OriginalManual' => 'Has Manual',
        'SerialNumber' => 'Serial Number'
    );

    private static $searchable_fields = array(
        'Title' => 'PartialMatchFilter',
        'Price' => 'ExactMatchFilter',
        'PurchasePrice' => 'ExactMatchFilter',
        'ProductQuality' => 'ExactMatchFilter',
        'IncludesBoxOrCase' => 'ExactMatchFilter',
        'OriginalManual' => 'ExactMatchFilter',
        'SerialNumber' => 'PartialMatchFilter'
    );
}
