<?php

namespace Sunnysideup\EcommerceSecondHandProduct\Model;

use SilverStripe\Assets\Image;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\ReadonlyField;

use SilverStripe\Forms\GridField\GridFieldDataColumns;

use SilverStripe\Forms\HeaderField;
use SilverStripe\ORM\DataObject;

use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use Sunnysideup\Ecommerce\Api\ClassHelpers;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Forms\Fields\EcommerceCMSButtonField;
use Sunnysideup\EcommerceSecondHandProduct\SecondHandProduct;

use Sunnysideup\Vardump\ArrayToTable;

class SecondHandArchive extends DataObject
{
    private static $table_name = 'SecondHandArchive';

    private static $db = [
        'Title' => 'Varchar(255)',
        'InternalItemID' => 'Varchar(50)',
        'SerialNumber' => 'Varchar(50)',
        'DateItemWasBought' => 'Date',
        'DateItemWasSold' => 'Date',
        'ProductQuality' => 'Enum("1, 2, 3, 4, 5, 6, 7, 8, 9, 10","10")',
        'SoldOnBehalf' => 'Boolean',
        'PurchasePrice' => 'Currency',
        'Price' => 'Currency',
        'SoldPrice' => 'Currency',
        'IncludesBoxOrCase' => 'Enum("No, Box, Case, Both","No")',
        'OriginalManual' => 'Boolean',
        'PageID' => 'Int',
        'Description' => 'Varchar(255)',
        'SellersName' => 'Varchar(50)',
        'SellersPhone' => 'Varchar(30)',
        'SellersEmail' => 'Varchar(255)',
        'SellersAddress' => 'Varchar(255)',
        'SellersAddress2' => 'Varchar(255)',
        'SellersCity' => 'Varchar(100)',
        'SellersPostalCode' => 'Varchar(50)',
        'SellersRegionCode' => 'Varchar(100)',
        'SellersCountry' => 'Varchar(50)',
        'SellersIDType' => 'Enum(",Drivers Licence, Firearms Licence, Passport","")',
        'SellersIDNumber' => 'Varchar(50)',
        'SellersDateOfBirth' => 'Date',
        'SellersIDExpiryDate' => 'Date',
        'SellersIDPhotocopy' => 'Boolean',
        'OriginalItemLastEdited' => 'DBDatetime',
        'OriginalItemCreated' => 'DBDatetime',
        'AutoArchived' => 'Boolean',
    ];

    private static $has_one = [
        'ArchivedBy' => Member::class,
        'Image' => Image::class,
    ];

    private static $many_many = [
        'AdditionalImages' => Image::class,
    ];

    private static $singular_name = 'Archived Second Hand Product';

    private static $plural_name = 'Archived Second Hand Products';

    private static $indexes = [
        'PageID' => true,
        'InternalItemID' => true,
    ];

    private static $default_sort = [
        'LastEdited' => 'DESC',
    ];

    private static $summary_fields = [
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
    ];

    private static $field_labels = [
        'Title' => 'Title',
        'Price' => 'Sale Price',
        'InternalItemID' => 'Code',
        'PurchasePrice' => 'Purchase Price',
        'ProductQuality' => 'Quality',
        'IncludesBoxOrCase' => 'Includes',
        'OriginalManual' => 'Has Manual',
        'SerialNumber' => 'Serial Number',
    ];

    private static $searchable_fields = [
        'Title' => 'PartialMatchFilter',
        'Price' => [
            'title' => 'Sale Price',
            'field' => NumericField::class,
            'filter' => 'ExactMatchFilter',
        ],
        'InternalItemID' => 'PartialMatchFilter',
        'PurchasePrice' => [
            'title' => 'Purchase Price',
            'field' => NumericField::class,
            'filter' => 'ExactMatchFilter',
        ],
        'ProductQuality' => 'ExactMatchFilter',
        'IncludesBoxOrCase' => 'ExactMatchFilter',
        'OriginalManual' => 'ExactMatchFilter',
        'SerialNumber' => 'PartialMatchFilter',
    ];

    private static $show_restore_button = false;

    public static function create_from_page($page)
    {
        $filter = $page->InternalItemID ? [
            'InternalItemID' => $page->InternalItemID,
        ] : [
            'PageID' => $page->ID,
        ];
        $obj = SecondHandArchive::get()->filter($filter)->first();
        if (! $obj) {
            $obj = SecondHandArchive::create($filter);
        }

        $obj->Title = $page->Title;
        $obj->OriginalItemCreated = $page->Created;
        $obj->OriginalItemLastEdited = $page->LastEdited;
        $obj->Price = $page->Price;
        $obj->InternalItemID = $page->InternalItemID;
        $obj->PageID = $page->ID;
        $obj->SerialNumber = $page->SerialNumber;
        $obj->DateItemWasBought = $page->DateItemWasBought;
        $obj->DateItemWasSold = $page->DateItemWasSold;
        $obj->ProductQuality = $page->ProductQuality;
        $obj->SoldOnBehalf = $page->SellingOnBehalf;
        $obj->PurchasePrice = $page->PurchasePrice;
        $obj->SoldPrice = $page->SoldPrice;
        $obj->IncludesBoxOrCase = $page->IncludesBoxOrCase;
        $obj->OriginalManual = $page->OriginalManual;
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
        $obj->ImageID = $page->ImageID;
        $obj->write();
        foreach ($page->AdditionalImages() as $image) {
            $obj->AdditionalImages()->add($image);
        }

        return $obj;
    }

    /**
     * stadard SS method.
     *
     * @param null|mixed $member
     * @param mixed      $context
     *
     * @return bool
     */
    public function canCreate($member = null, $context = [])
    {
        return false;
    }

    /**
     * stadard SS method.
     *
     * @param null|mixed $member
     * @param mixed      $context
     *
     * @return bool
     */
    public function canEdit($member = null, $context = [])
    {
        return false;
    }

    /**
     * stadard SS method.
     *
     * @param null|mixed $member
     * @param mixed      $context
     *
     * @return bool
     */
    public function canView($member = null, $context = [])
    {
        return Permission::check(
            EcommerceConfig::get(SecondHandProduct::class, 'second_hand_admin_permission_code')
        );
    }

    /**
     * stadard SS method.
     *
     * @param null|mixed $member
     *
     * @return bool
     */
    public function canDelete($member = null)
    {
        return false;
    }

    public function i18n_singular_name()
    {
        return self::$singular_name;
    }

    public function i18n_plural_name()
    {
        return self::$plural_name;
    }

    public function CMSEditLink(): string
    {
        return $this->ModelAdminLink();
    }

    public function ModelAdminLink(): string
    {
        //admin/secondhandproducts/Sunnysideup-EcommerceSecondHandProduct-Model-SecondHandArchive/EditForm/field/Sunnysideup-EcommerceSecondHandProduct-Model-SecondHandArchive/item/7760/edit
        $classURLSegment = ClassHelpers::sanitise_class_name(SecondHandArchive::class);

        return '/admin/secondhandproducts/' . $classURLSegment . '/EditForm/field/' . $classURLSegment . '/item/' . $this->ID . '/edit';
    }

    /**
     * stadard SS method.
     *
     * @return \SilverStripe\Forms\FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        if (Config::inst()->get(SecondHandArchive::class, 'show_restore_button')) {
            $classURLSegment = ClassHelpers::sanitise_class_name(SecondHandArchive::class);
            $fields->addFieldsToTab(
                'Root.Main',
                [
                    EcommerceCMSButtonField::create(
                        'RestoreButton',
                        SecondHandProduct::restore_link($this->PageID),
                        _t('SecondHandArchive.RESTORE_BUTTON', 'Restore Product')
                    ),
                ]
            );
        }
        $fields->dataFieldByName('AdditionalImages')
            ->getConfig()
            ->getComponentByType(GridFieldDataColumns::class)
            ->setDisplayFields(['CMSTumbnail'],);
        $fields->addFieldsToTab(
            'Root.SellersDetails',
            [
                $fields->dataFieldByName('SellersName'),
                $fields->dataFieldByName('SellersPhone'),
                $fields->dataFieldByName('SellersEmail'),
                $fields->dataFieldByName('SellersAddress'),
                $fields->dataFieldByName('SellersAddress2'),
                $fields->dataFieldByName('SellersCity'),
                $fields->dataFieldByName('SellersPostalCode'),
                $fields->dataFieldByName('SellersRegionCode'),
                $fields->dataFieldByName('SellersCountry'),
                $fields->dataFieldByName('SellersIDType'),
                $fields->dataFieldByName('SellersIDNumber'),
                $fields->dataFieldByName('SellersDateOfBirth'),
                $fields->dataFieldByName('SellersIDExpiryDate'),
                $fields->dataFieldByName('SellersIDPhotocopy'),
            ]
        );
        $archivedByLink = '';
        if($this->ArchivedByID) {
            $archivedByLink = DBField::create_field(
                'HTMLText',
                '<a href="/admin/security/EditForm/field/Members/item/'.$this->ArchivedByID.'/edit">View archiver details</a>'
            );
        }
        $fields->addFieldsToTab(
            'Root.History',
            [
                HeaderField::create(
                    'ArchiveHistory',
                    'Archive History'
                ),
                ReadonlyField::create(
                    'Created',
                    'Archived'
                ),
                $fields->dataFieldByName('AutoArchived')->setTitle('Archived by system?'),
                $fields->dataFieldByName('ArchivedByID')->setDescription($archivedByLink),
                HeaderField::create(
                    'ProductHistory',
                    'Product History (not all information may be available)'
                ),
                $fields->dataFieldByName('OriginalItemCreated')->setTitle('Product Created')->setDescription(''),
                $fields->dataFieldByName('OriginalItemLastEdited')->setTitle('Product Last Edited')->setDescription(''),
                LiteralField::create(
                    'ChangeHistory',
                    '<h2>Selected History</h2><p>Only shows available history.</p>'.
                    '<blockquote>'.ArrayToTable::convert($this->getHistoryData()).'</blockquote>'
                )
            ]
        );
        $currentProduct = SecondHandProduct::get()->filter(['InternalItemID' => $this->InternalItemID])->first();
        if($currentProduct) {
            $fields->addFieldsToTab(
                'Root.Error',
                [
                    LiteralField::create(
                        'LiveProduct',
                        '<h2>There is a live product with the same code: <a href="'.$currentProduct->CMSEditLink().'">'.$currentProduct->Title.'</a></h2>'
                    )
                ]
            );
        }
        return $fields;
    }

    public function getHistoryData(?string $code = '') : array
    {
        $obj = DataObject::get_one(SecondHandProduct::class);
        $array = [];
        if ($obj && $this->InternalItemID) {
            $array = $obj->getHistoryData($this->InternalItemID);
        }

        return $array;
    }
}
