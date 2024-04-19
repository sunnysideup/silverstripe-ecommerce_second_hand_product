<?php

namespace Sunnysideup\EcommerceSecondHandProduct;

use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\DateField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordViewer;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\FieldType\DBBoolean;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\Security\Group;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use SilverStripe\Versioned\Versioned;
use Sunnysideup\Ecommerce\Api\ClassHelpers;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Forms\Fields\EcommerceCMSButtonField;
use Sunnysideup\Ecommerce\Model\Extensions\EcommerceRole;
use Sunnysideup\Ecommerce\Pages\Product;
use Sunnysideup\EcommerceSecondHandProduct\Api\CodeGenerator;
use Sunnysideup\EcommerceSecondHandProduct\Cms\SecondHandProductAdmin;
use Sunnysideup\EcommerceSecondHandProduct\Model\SecondHandArchive;
use Sunnysideup\GoogleAddressField\GoogleAddressField;
use Sunnysideup\PermissionProvider\Api\PermissionProviderFactory;
use Sunnysideup\PermissionProvider\Interfaces\PermissionProviderFactoryProvider;
use Page;
use SilverStripe\Control\Director;
use SilverStripe\ORM\FieldType\DBDate;

/**
 * Class \Sunnysideup\EcommerceSecondHandProduct\SecondHandProduct
 *
 * @property string $CUS
 * @property string $SPC
 * @property float $MGP
 * @property float $ZOR
 * @property bool $FPS
 * @property bool $SPE
 * @property bool $IOS
 * @property bool $PRO
 * @property bool $FER
 * @property bool $POA
 * @property bool $PHT
 * @property string $NotForSaleMessage
 * @property bool $KeepRecordEvenThoughItIsNotForSale
 * @property int $RangeParentID
 * @property float $SoldPrice
 * @property float $PurchasePrice
 * @property string $ProductQuality
 * @property string $IncludesBoxOrCase
 * @property bool $SellingOnBehalf
 * @property bool $OriginalManual
 * @property string $DateItemWasBought
 * @property string $DateItemWasSold
 * @property string $SerialNumber
 * @property string $SellersName
 * @property string $SellersPhone
 * @property string $SellersEmail
 * @property string $SellersAddress
 * @property string $SellersAddress2
 * @property string $SellersCity
 * @property string $SellersPostalCode
 * @property string $SellersRegionCode
 * @property string $SellersCountry
 * @property string $SellersIDType
 * @property string $SellersIDNumber
 * @property string $SellersDateOfBirth
 * @property string $SellersIDExpiryDate
 * @property bool $SellersIDPhotocopy
 * @property int $BasedOnID
 * @property int $ArchivedByID
 * @method \Sunnysideup\EcommerceSecondHandProduct\SecondHandProduct BasedOn()
 * @method \SilverStripe\Security\Member ArchivedBy()
 */
class SecondHandProduct extends Product implements PermissionProviderFactoryProvider
{
    /**
     * @var string
     */
    protected static $treshold_sql_cache = '';

    private static $can_be_root = false;

    /**
     * halt purchase for ... number of days
     * from the day of creation.
     *
     * @var int
     */
    private static $embargo_number_of_days = 0;

    /**
     * halt purchase for ... number of days
     * from the day of creation.
     *
     * @var int
     */
    private static $max_number_of_days_for_sale = 9999;

    /**
     * Restrict GoogleAddressField to a specific Country
     * E.g. for New Zealand, $country_code =  'NZ'.
     *
     * @var string
     */
    private static $country_code;

    /**
     * stadard SS declaration.
     *
     * @var string
     */
    private static $table_name = 'SecondHandProduct';

    /**
     * place to save images and other files...
     *
     * @var string
     */
    private static $folder_name_for_images = 'second-hand-images';

    private static $db = [
        'SoldPrice' => 'Currency',
        'PurchasePrice' => 'Currency',
        'ProductQuality' => 'Enum("1, 2, 3, 4, 5, 6, 7, 8, 9, 10","10")',
        'IncludesBoxOrCase' => "Enum('No, Box, Case, Both','No')",
        'SellingOnBehalf' => 'Boolean',
        'OriginalManual' => 'Boolean',
        'DateItemWasBought' => 'Date',
        'DateItemWasSold' => 'Date',
        'SerialNumber' => 'Varchar(50)',
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
    ];

    private static $has_one = [
        'BasedOn' => SecondHandProduct::class,
        'ArchivedBy' => Member::class,
        'EquivalentNewProduct' => Product::class,
    ];

    private static $default_sort = [
        'ID' => 'DESC',
    ];

    private static $defaults = [
        'ShowInMenus' => false,
    ];

    private static $indexes = [
        'SerialNumber' => true,
    ];

    private static $casting = [
        'SellersSummary' => 'Varchar',
        'CreatedNice' => 'Varchar',
    ];

    /**
     * Standard SS variable.
     */
    private static $summary_fields = [];

    private static $seller_summary_detail_fields = [
        'SellersName',
        'SellersPhone',
        'SellersEmail',
        'SellersAddress',
        'SellersAddress2',
        'SellersCity',
        'SellersPostalCode',
        'SellersRegionCode',
        'SellersCountry',
        'SellersIDType',
        'SellersIDNumber',
        'SellersDateOfBirth',
        'SellersIDExpiryDate',
        'SellersIDPhotocopy',
    ];

    private static $searchable_fields = [
        'FullName' => [
            'title' => 'Keyword',
            'field' => TextField::class,
        ],
        'Price' => [
            'title' => 'Price',
            'field' => NumericField::class,
        ],
        'InternalItemID' => [
            'title' => 'Internal Item ID',
            'filter' => 'PartialMatchFilter',
        ],
        'SellersName' => [
            'title' => 'Sellers Name',
            'filter' => 'PartialMatchFilter',
        ],
        'SellersPhone' => [
            'title' => 'Sellers Phone',
            'filter' => 'PartialMatchFilter',
        ],
        'SellersEmail' => [
            'title' => 'Sellers Email',
            'filter' => 'PartialMatchFilter',
        ],
        'AllowPurchase',
        'PurchasePrice' => [
            'title' => 'Purchase Price',
            'field' => NumericField::class,
            'filter' => 'ExactMatchFilter',
        ],
        'SerialNumber' => 'PartialMatchFilter',
    ];

    private static $second_hand_admin_group_code = 'second-hand-managers';

    private static $second_hand_admin_group_name = 'Second Hand Product Managers';

    private static $second_hand_admin_role_title = 'Second Hand Product Management';

    private static $second_hand_admin_permission_code = 'CMS_ACCESS_SECOND_HAND_PRODUCTS';

    private static $second_hand_admin_permission_title = 'Second Hand Product Manager';

    private static $second_hand_admin_permission_help = 'Manages the second product products';

    private static $second_hand_admin_user_email = '';

    private static $second_hand_admin_user_firstname = '';

    private static $second_hand_admin_user_surname = '';

    private static $second_hand_admin_user_password = '';

    /**
     * standard SS declaration.
     *
     * @var string
     */
    private static $icon = 'sunnysideup/ecommerce_second_hand_product: client/images/treeicons/SecondHandProduct-file.gif';

    /**
     * Standard SS variable.
     */
    private static $singular_name = 'Second-Hand Product';

    /**
     * Standard SS variable.
     */
    private static $plural_name = 'Second-Hand Products';

    /**
     * standard SS declaration.
     *
     * @var string
     */
    private static $description = 'This page displays a single second-hand product that can only be sold once';

    public function SummaryFields()
    {
        return [
            'Image.CMSThumbnail' => 'Image',
            'Title' => 'Title',
            'InternalItemID' => 'Code',
            'Price' => 'Price',
            'AllowPurchaseNice' => 'For Sale',
            'CreatedNice' => 'Entered',
        ];
    }

    public function getSellerSummary()
    {
        $list = Config::inst()->get(SecondHandProduct::class, 'seller_summary_detail_fields');
        $array = [];
        foreach ($list as $field) {
            if (trim((string) $this->{$field})) {
                $array[] = $this->{$field};
            }
        }

        return implode('; ', $array);
    }

    public function i18n_singular_name()
    {
        return self::$singular_name;
    }

    public function i18n_plural_name()
    {
        return self::$plural_name;
    }

    /**
     * standard SS method.
     *
     * @param null|mixed $member
     * @param mixed      $context
     *
     * @return bool
     */
    public function canCreate($member = null, $context = [])
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if (null !== $extended) {
            return $extended;
        }

        return Permission::check(
            EcommerceConfig::get(SecondHandProduct::class, 'second_hand_admin_permission_code'),
            'any',
            $member
        );
    }

    /**
     * standard SS method.
     *
     * @param null|mixed $member
     *
     * @return bool
     */
    public function canPublish($member = null)
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if (null !== $extended) {
            return $extended;
        }

        return Permission::check(
            EcommerceConfig::get(SecondHandProduct::class, 'second_hand_admin_permission_code'),
            'any',
            $member
        );
    }

    /**
     * standard SS method.
     *
     * @param null|mixed $member
     * @param mixed      $context
     *
     * @return bool
     */
    public function canEdit($member = null, $context = [])
    {
        if(Director::isDev()) {
            return true;
        }
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if (null !== $extended) {
            return $extended;
        }

        return Permission::check(
            EcommerceConfig::get(SecondHandProduct::class, 'second_hand_admin_permission_code'),
            'any',
            $member
        );
    }

    /**
     * standard SS method.
     *
     * @param null|mixed $member
     *
     * @return bool
     */
    public function canDelete($member = null)
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if (null !== $extended) {
            return $extended;
        }

        return Permission::check(
            EcommerceConfig::get(SecondHandProduct::class, 'second_hand_admin_permission_code'),
            'any',
            $member
        );
    }

    public function onBeforeDelete()
    {
        SecondHandArchive::create_from_page($this);
        if (!$this->ArchivedByID) {
            $currentMember = Security::getCurrentUser();
            if ($currentMember) {
                $this->ArchivedByID = $currentMember->ID;
            }
        }

        parent::onBeforeDelete();
    }

    /**
     * stadard SS method.
     *
     * @return \SilverStripe\Forms\FieldList
     */
    public function getCMSFields()
    {
        $fields = Page::getCMSFields();

        //remove all unneccessary fields and tabs
        $fields->removeByName(
            [
                'AlsoShowHere',
                'Tax',
                'Links',
                'Details',
                'Images',
                'ApplicableDiscountCoupons',
            ]
        );
        $fields->removeFieldsFromTab(
            'Root',
            [
                'Title',
                'ShortDescription',
                'Content',
                'Metadata',
                'AddToCartLink',
                'Price',
                'AlsoShowHere',
            ]
        );

        $fields->dataFieldByName('URLSegment')->setReadonly(true);
        $qualityFieldDescription = DBField::create_field(
            'HTMLText',
            'A <strong>Condition Rating Page</strong> has yet to be setup'
        );
        $obj = EcommerceConfig::inst()->SecondHandExplanationPage();
        if ($obj->exists()) {
            $qualityFieldDescription = 'An explanation of the ratings scale can be found by clicking this <a href="' . $obj->Link() . '">link</a>';
        }
        $fields->addFieldsToTab(
            'Root.Main',
            [

                $allowPurchaseField = CheckboxField::create(
                    'AllowPurchase',
                    DBField::create_field(
                        'HTMLText',
                        '<strong>Allow product to be purchased</strong>'
                    )
                )
                    ->setDescription('This box must be ticked to allow a customer to purchase it'),
                CheckboxField::create(
                    'SellingOnBehalf',
                    DBField::create_field(
                        'HTMLText',
                        '<strong>Selling on behalf</strong>'
                    )
                )
                    ->setDescription('This box must be ticked if this product is being sold on behalf'),
                CheckboxField::create(
                    'FeaturedProduct',
                    DBField::create_field(
                        'HTMLText',
                        _t('Product.FEATURED', '<strong>Featured Product</strong>')
                    )
                )
                    ->setDescription('If this box is ticked then this product will appear in the "Featured Products" box on the home page'),
                TextField::create('Title', 'Product Title'),
                ReadonlyField::create('CanBeSold', 'For Sale', DBField::create_field(DBBoolean::class, $this->canPurchase())->Nice()),
                ReadonlyField::create('CreatedNice', 'First Entered', $this->getCreatedNice()),
                TextField::create('InternalItemID', 'Product Code')->setReadonly(true),
                DropdownField::create(
                    'EquivalentNewProductID',
                    'New product version',
                    Product::get()
                        ->sort('Title', 'ASC')
                        ->map('ID', 'FullName')
                )
                    ->setEmptyString('--- select identical new product (if any) ---'),
                $purchasePriceField = NumericField::create('PurchasePrice', 'Purchase Price')
                    ->setScale(2)
                    ->setDescription('Price paid for the product'),
                NumericField::create('Price', 'Sale Price')
                    ->setScale(2)
                    ->setDescription('Selling price'),
                NumericField::create('SoldPrice', 'Sold Price')
                    ->setScale(2)
                    ->setDescription('The price that the product actually sold for'),
                TextField::create('SerialNumber', 'Serial Number')
                    ->setDescription('Enter the serial number of the product here'),
                DropdownField::create(
                    'ProductQuality',
                    'Product Condition/Quality',
                    $this->dbObject('ProductQuality')->enumValues()
                )
                    ->setDescription($qualityFieldDescription),
                DropdownField::create(
                    'IncludesBoxOrCase',
                    'Includes Box/Case',
                    $this->dbObject('IncludesBoxOrCase')->enumValues()
                )
                    ->setDescription('Does this product come with a box, case or both?'),
                CheckboxField::create('OriginalManual', 'Includes Original Manual')
                    ->setDescription('Tick this box if the product includes the original manual, otherwise leave it empty'),
                TextField::create('ShortDescription', 'Description')
                    ->setMaxLength(255)
                    ->setDescription('Optional text only description, the maximum length of this description is 255 characters.'),
                DateField::create('DateItemWasBought', 'Date this item was bought')
                    ->setDescription('Date Format (dd-mm-YYYY). Example: 3rd of May 1992 should be entered as 03-05-1992'),
                DateField::create('DateItemWasSold', 'Date this item was sold')
                    ->setDisabled(true),
                UploadField::create('Image', 'Main Product Image')
                    ->setDescription(
                        DBField::create_field(
                            'HTMLText',
                            '<strong>Upload the main image for the product here.</strong><br>
                            Recommended size: 810px wide x 418px high - but you can choose any width up to 810px, height must
                            ALWAYS BE 418px.
                            Name should be ' . $this->InternalItemID . '_1.jpg'
                        )
                    ),
                UploadField::create('AdditionalImages', 'More Images')
                    ->setDescription(
                        DBField::create_field(
                            'HTMLText',
                            '<strong>Upload the main image for the product here.</strong><br>
                        Recommended size: 810px wide x 418px high - but you can choose any width up to 810px, height must
                        ALWAYS BE 418px.
                        Name should be ' . $this->InternalItemID . '_[2,3,4,5, etc...].jpg'
                        )
                    ),
                TextareaField::create('MetaDescription', 'Meta Description'),
            ]
        );


        //replace InternalItemID field with a read only field

        // $lastEditedItems = SecondHandArchive::get()->sort('ID', 'DESC')->map('ID', 'InternalItemID');

        // $lastItems = [
        //     0 => '--- not based on previous sale ---',
        // ];

        $fields->addFieldsToTab(
            'Root.SellersDetails',
            [
                HeaderField::create('SellersDetails', 'Enter the details of the person who the product was purchased from'),
                // DropdownField::create(
                //     'BasedOnID',
                //     'Autocomplete from saved items',
                //     $lastItems
                // ),
                TextField::create('SellersName', 'Name'),
                TextField::create('SellersPhone', 'Phone'),
                TextField::create('SellersEmail', 'Email Address'),
                DropdownField::create(
                    'SellersIDType',
                    'ID Type',
                    $this->dbObject('SellersIDType')->enumValues()
                ),
                TextField::create('SellersIDNumber', 'ID Number'),
                DateField::create('SellersDateOfBirth', 'Date of Birth'),
                DateField::create('SellersIDExpiryDate', 'ID Expiry Date'),
                CheckboxField::create('SellersIDPhotocopy', 'ID Photocopy'),
            ]
        );


        if (class_exists(GoogleAddressField::class)) {
            $mappingArray = $this->Config()->get('fields_to_google_geocode_conversion');
            if (is_array($mappingArray) && count($mappingArray)) {
                $fields->addFieldToTab(
                    'Root.SellersDetails',
                    $geocodingField = new GoogleAddressField(
                        'SellersAddressGeocodingField',
                        _t('OrderAddress.FIND_ADDRESS', 'Find address'),
                        Controller::curr()->getRequest()->getSession()->get('SellersAddressGeocodingFieldValue')
                    )
                );
                $geocodingField->setFieldMap($mappingArray);

                $country_code = Config::inst()->get(SecondHandProduct::class, 'country_code');
                if ($country_code) {
                    $geocodingField->setRestrictToCountryCode($country_code);
                }

                //$geocodingField->setAlwaysShowFields(true);
            }
        }


        $fields->addFieldsToTab(
            'Root.SellersDetails',
            [
                TextField::create('SellersAddress', 'Address'),
                TextField::create('SellersAddress2', 'Suburb'),
                TextField::create('SellersCity', 'City/Town'),
                TextField::create('SellersPostalCode', 'Postal Code'),
                TextField::create('SellersRegionCode', 'Region Code'),
                TextField::create('SellersCountry', 'Country'),
            ]
        );

        //add all fields to the main tab
        $fields->addFieldToTab(
            'Root.SellersDetails',
            EcommerceCMSButtonField::create(
                'PrintView',
                $this->getPrintLink(),
                'Print Details',
                $newWindow = true
            )
        );
        // if ($this->BasedOnID) {
        //     $list = Config::inst()->get(SecondHandProduct::class, 'seller_summary_detail_fields');
        //     foreach ($list as $listField) {
        //         $fields->replaceField(
        //             $listField,
        //             ReadonlyField::create(
        //                 $listField,
        //                 $fields->dataFieldByName($listField)->Title()
        //             )
        //         );
        //     }

        //     $fields->removeByName('SellersAddressGeocodingField');
        // }

        $fields->addFieldToTab(
            'Root.Under',
            $categoriesTable = $this->getProductGroupsTableField()
        );

        // If the product has been sold all fields should be disabled
        // Only the shop administrator is allowed to undo this.
        $fields->insertAfter(
            'AllowPurchase',
            EcommerceCMSButtonField::create(
                'ArchiveButton',
                $this->ArchiveLink(),
                _t('SecondHandProduct.ARCHIVE_BUTTON', 'Archive Product')
            )
        );

        if ($this->HasBeenSold()) {
            $fields = $fields->makeReadonly();
            $fields->replaceField($categoriesTable->Name, $categoriesTable);
            $categoriesTable->setConfig(GridFieldConfig_RecordViewer::create());
        }

        if ($this->canEdit()) {
            $fields->replaceField(
                'AllowPurchase',
                CheckboxField::create(
                    'AllowPurchase',
                    DBField::create_field(
                        'HTMLText',
                        '<strong>Allow product to be purchased</strong>'
                    )
                )
            );
            $fields->replaceField('DateItemWasSold', DateField::create('DateItemWasSold', 'Date this item was sold'));
        }

        $fields->addFieldsToTab(
            'Root.Status',
            [
                ReadonlyField::create('IsForSaleRightNow', 'Is listed', ($this->canPurchase() ? 'YES' : 'NO'))
                    ->setDescription('This is based on the three fields below'),
                ReadonlyField::create('isListedNice', 'Can be listed', ($this->isListed() ? 'YES IS LISTED ON SITE' : 'NO, NOT LISTED ON SITE')),
                ReadonlyField::create('isUnderEmbargoNice', 'Is under embargo', ($this->isUnderEmbargo() ? 'YES - STILL WAITING' : 'NO LONGER')),
                ReadonlyField::create('HasBeenSoldNice', 'Has been sold', ($this->HasBeenSold() ? 'YES' : 'NO')),
                ReadonlyField::create('DidNotSellNice', 'Did not sell?', ($this->didNotSell() ? 'YES - NEVER SOLD' : 'SO FAR SO GOOD - NOT PASSED USED BY DATE')),
                // ReadonlyField::create('Sql', 'Sql', $this->get_treshold_sql()),
            ]
        );
        $secondhandProductCategories = SecondHandProductGroup::get();
        if ($secondhandProductCategories->exists()) {
            $fields->addFieldToTab(
                'Root.Main',
                $categoryField = DropdownField::create(
                    'ParentID',
                    'Product Category',
                    $secondhandProductCategories->map()
                )
            );
        }
        return $fields;
    }

    public function ArchiveLink(): string
    {
        $classURLSegment = ClassHelpers::sanitise_class_name(SecondHandProduct::class);

        return '/admin/secondhandproducts/' . $classURLSegment . '/archive/?productid=' . $this->ID;
    }

    public static function restore_link(int $id): string
    {
        $classURLSegment = ClassHelpers::sanitise_class_name(SecondHandProduct::class);

        return '/admin/secondhandproducts/' . $classURLSegment . '/restore/?productid=' . $id;
    }

    public function ModelAdminLink(): string
    {
        return $this->CMSEditLink();
    }

    public function getPrintLink()
    {
        return $this->link('printview');
    }

    public function CMSEditLink($action = null)
    {
        return Injector::inst()->get(SecondHandProductAdmin::class)->getCMSEditLinkForManagedDataObject($this);
    }

    public function getSettingsFields()
    {
        $fields = parent::getSettingsFields();
        $fields->removeByName('ParentID');

        return $fields;
    }

    /**
     * products that can be sold SQL.
     */
    public static function get_treshold_sql(): string
    {
        if ('' === self::$treshold_sql_cache) {
            $tableName = Injector::inst()->get(SecondHandProduct::class)->stageTableDefault();
            $daysMin = (int) Config::inst()->get(SecondHandProduct::class, 'embargo_number_of_days');
            $minThreshold = date(
                'Y-m-d H:i:s',
                strtotime('-' . $daysMin . ' days', DBDatetime::now()->getTimestamp())
            );
            $daysMax = (int) Config::inst()->get(SecondHandProduct::class, 'max_number_of_days_for_sale');
            $maxThreshold = date(
                'Y-m-d H:i:s',
                strtotime('-' . $daysMax . ' days', DBDatetime::now()->getTimestamp())
            );
            self::$treshold_sql_cache = '
                (
                    "AllowPurchase" = 1 AND
                    "' . $tableName . '"."DateItemWasBought" IS NOT NULL AND
                    "' . $tableName . '"."DateItemWasBought" <= \'' . $minThreshold . '\' AND
                    "' . $tableName . '"."DateItemWasBought" > \'' . $maxThreshold . '\'
                )
            ';
        }

        return self::$treshold_sql_cache;
    }

    public function canPurchase(Member $member = null, $checkPrice = true)
    {
        if ($this->DateItemWasSold) {
            return false;
        }

        if ($this->isUnderEmbargo()) {
            return false;
        }

        if (!$this->isListed()) {
            return false;
        }

        return parent::canPurchase($member, $checkPrice);
    }

    public function IsSecondHandProduct(): bool
    {
        return true;
    }

    public function isListed(): bool
    {
        return (bool) SecondHandProduct::get()
            ->where(self::get_treshold_sql())
            ->byId($this->ID);
    }

    /**
     * this is the same as the.
     *
     * @return bool [description]
     */
    public function isUnderEmbargo(): bool
    {
        $embargoDays = Config::inst()->get(SecondHandProduct::class, 'embargo_number_of_days');
        if ((int) $embargoDays > 0) {
            $date = $this->DateItemWasBought ? $this->DateItemWasBought : $this->Created;
            $createdDate = strtotime((string) $date);
            $daysOld = (time() - $createdDate) / (60 * 60 * 24);
            if ($daysOld <= $embargoDays) {
                return true;
            }
        }

        return false;
    }

    public function didNotSell(): bool
    {
        $daysMax = (int) Config::inst()->get(SecondHandProduct::class, 'max_number_of_days_for_sale');
        $shouldBeListedAfterTs = strtotime('-' . $daysMax . ' days', DBDatetime::now()->getTimestamp());
        $listedTs = strtotime((string) $this->Created);

        return $listedTs < $shouldBeListedAfterTs;
    }

    public function HasBeenSold(): bool
    {
        return $this->DateItemWasSold ? true : parent::HasBeenSold();
    }

    public function SecondHandProductQualityPercentage()
    {
        return $this->ProductQuality * 10;
    }

    public function InternalItemIDNice()
    {
        return $this->InternalItemID;
    }

    public function canView($member = null)
    {
        if (Permission::check('CMS_ACCESS_SecondHandProductAdmin')) {
            return true;
        }

        return parent::canView($member);
    }

    public static function permission_provider_factory_runner(): Group
    {
        return PermissionProviderFactory::inst()
            ->setParentGroup(EcommerceRole::get_category())

            ->setEmail(EcommerceConfig::get(SecondHandProduct::class, 'second_hand_admin_user_email'))
            ->setFirstName(EcommerceConfig::get(SecondHandProduct::class, 'second_hand_admin_user_firstname'))
            ->setSurname(EcommerceConfig::get(SecondHandProduct::class, 'second_hand_admin_user_surname'))
            ->setPassword(EcommerceConfig::get(SecondHandProduct::class, 'second_hand_admin_user_password'))
            ->setGroupName(EcommerceConfig::get(SecondHandProduct::class, 'second_hand_admin_group_name'))
            ->setCode(EcommerceConfig::get(SecondHandProduct::class, 'second_hand_admin_group_code'))
            ->setPermissionCode(EcommerceConfig::get(SecondHandProduct::class, 'second_hand_admin_permission_code'))
            ->setRoleTitle(EcommerceConfig::get(SecondHandProduct::class, 'second_hand_admin_permission_title'))

            ->setPermissionArray(
                [
                    'SITETREE_VIEW_ALL',
                    'SITETREE_EDIT_ALL',
                    'CMS_ACCESS_SecondHandProductAdmin',
                    'CMS_ACCESS_Sunnysideup\\EcommerceSecondHandProduct\\Cms\\SecondHandProductAdmin',
                ]
            )

            ->setSort(250)
            ->setDescription(EcommerceConfig::get(SecondHandProduct::class, 'second_hand_admin_permission_help'))

            ->CreateGroupAndMember();
    }

    public function exportFields()
    {
        $fields = $this->summaryFields();
        unset($fields['Image.CMSThumbnail']);

        return $fields;
    }

    public function populateDefaults()
    {
        if (!$this->DateItemWasBought) {
            $this->DateItemWasBought = DBDatetime::now()->Rfc2822();
        }

        return parent::populateDefaults();
    }

    public function getCreatedNice()
    {
        $date = $this->DateItemWasBought ? $this->DateItemWasBought : $this->Created;
        if (!$this->DateItemWasBought || (strtotime((string) $this->DateItemWasBought) > (strtotime('now') - (7 * 86400)))) {
            $date = $this->Created;
        }

        return $date . ' = ' . DBField::create_field(DBDatetime::class, $date)->Ago();
    }

    /**
     * By default we search for products that are allowed to be purchased only
     * standard SS method.
     *
     * @param null|mixed $_params
     *
     * @return \SilverStripe\Forms\FieldList
     */
    public function scaffoldSearchFields($_params = null)
    {
        $fields = parent::scaffoldSearchFields($_params);
        $fields->fieldByName('AllowPurchase')->setValue('');

        return $fields;
    }

    protected function onBeforeWrite()
    {
        // set this first!
        if (!$this->InternalItemID) {
            $this->InternalItemID = 'S-H-' . CodeGenerator::generate();
            $x = 0;
            while ($this->anotherOneWithThisCodeExists() && $x < 50) {
                $this->InternalItemID = 'S-H-' . CodeGenerator::generate();
                ++$x;
            }
        }

        // then the URL Segment!
        $this->URLSegment = $this->generateURLSegment($this->Title . '-' . $this->InternalItemID);

        // get parent values
        parent::onBeforeWrite();

        // now we can do other stuff.
        if ($this->exists()) {
            if ($this->HasBeenSold()) {
                $this->AllowPurchase = 0;
                if (!$this->DateItemWasSold) {
                    $this->DateItemWasSold = DBDatetime::now()->Format(DBDate::ISO_DATE);
                }
            } else {
                $this->ArchivedByID = 0;

                if ($this->BasedOnID && $this->BasedOnID !== $this->ID) {
                    $basedOn = $this->BasedOn();
                    if ($basedOn && $basedOn->exists()) {
                        $list = Config::inst()->get(SecondHandProduct::class, 'seller_summary_detail_fields');
                        foreach ($list as $field) {
                            $this->{$field} = $basedOn->{$field};
                        }
                    }
                }
            }
        }

        if ($this->Title && strlen((string) $this->MetaDescription) < 22) {
            $this->MetaDescription = 'Second Hand Product: ' . $this->Title;
        }

        // Save the date when the product was sold.

        // must be after parent::onBeforeWrite
        if (!$this->DateItemWasBought && $this->Created) {
            $this->DateItemWasBought = $this->Created;
        }

        // fix the images if it is still worth fixing!
    }

    protected function anotherOneWithThisCodeExists(): bool
    {
        if (!$this->InternalItemID) {
            return true;
        }

        $a = SecondHandProduct::get()->filter(['InternalItemID' => $this->InternalItemID])->exclude(['ID' => $this->ID])->exists();
        $b = SecondHandArchive::get()->filter(['InternalItemID' => $this->InternalItemID])->exists();

        return $a || $b;
    }


    public function getMinValueInOrder(): float
    {
        return 1;
    }

    public function getMaxValueInOrder(): float
    {
        return 1;
    }
}
