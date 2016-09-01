<?php


class SecondHandProduct extends Product implements PermissionProvider {

    private static $can_be_root = false;

    /**
     * stadard SS declaration
     * @var Array
     */
    private static $db = array (
        "PurchasePrice" => "Currency",
        "ProductQuality" => "ENUM('1, 2, 3, 4, 5, 6, 7, 8, 9, 10','10')",
        "IncludesBoxOrCase" => "ENUM('No, Box, Case, Both','No')",
        "OriginalManual" => "Boolean",
        "SerialNumber" => "VarChar(50)",
        "SellersName" =>  "VarChar(50)",
        "SellersPhone" =>  'VarChar(30)',
        "SellersEmail" =>  "VarChar(255)",
        "SellersAddress" =>  "VarChar(255)",
        'SellersAddress2' => 'Varchar(255)',
        'SellersCity' => 'Varchar(100)',
        'SellersPostalCode' => 'Varchar(50)',
        'SellersRegionCode' => 'Varchar(100)',
        'SellersCountry' => 'Varchar(50)'
    );

    private static $defaults = array(
        'ShowInMenus' => false
    );

    private static $indexes = array(
        'SerialNumber' => true
    );

    private static $second_hand_admin_group_code = 'second-hand-managers';

    private static $second_hand_admin_group_name = 'Second Hand Product Managers';

    private static $second_hand_admin_role_title = 'Second Hand Product Management';

    private static $second_hand_admin_permission_code = 'CMS_ACCESS_SECOND_HAND_PRODUCTS';

    private static $second_hand_admin_permission_title = 'Second Hand Product Manager';

    private static $second_hand_admin_permission_help = 'Manages the second product products';

    private static $second_hand_admin_user_email = '';

    private static $second_hand_admin_user_firstname = '';

    private static $second_hand_admin_user_surname = '';

    private static $second_hand_admin_user_password = "";

    /**
     * stadard SS declaration
     * @var String
     */
    private static $icon = "ecommerce_second_hand_product/images/treeicons/SecondHandProduct";

    /**
     * Standard SS variable.
     */
    private static $singular_name = 'SecondHand Product';
    public function i18n_singular_name()
    {
        return self::$singular_name;
    }

    /**
     * Standard SS variable.
     */
    private static $plural_name = 'SecondHand Products';
    public function i18n_plural_name()
    {
        return self::$plural_name;
    }

    /**
     * stadard SS declaration
     * @var String
     */
    private static $description = "This page displays a single secondhand product that can only be sold once";

    /**
     * stadard SS method
     * @return Boolean
     */
    public function canCreate($member = null) {
        return true;
    }

    /**
     * stadard SS method
     * @return Boolean
     */
    public function canPublish($member = null) {
        return true;
    }

    /**
     * stadard SS method
     * @return Boolean
     */
    public function canEdit($member = null) {
        return true;
    }

    /**
     * stadard SS method
     * @return Boolean
     */
    public function canDelete($member = null) {
        return true;
    }

    public function onBeforeDelete() {
        parent::onBeforeDelete();
        SecondHandArchive::create_from_page($this);
    }

    /**
     * stadard SS method
     * @return FieldList
     */
    public function getCMSFields() {
        $fields = parent::getCMSFields();
        //remove all unneccessary fields and tabs
        $fields->removeByName("AlsoShowHere");
        $fields->removeByName("Tax");
        $fields->removeByName("Links");
        $fields->removeByName("Details");
        $fields->removeByName("Images");
        $fields->removeFieldFromTab('Root', 'Title');
        $fields->removeFieldFromTab('Root', 'URLSegment');
        $fields->removeFieldFromTab('Root', 'MenuTitle');
        $fields->removeFieldFromTab('Root', 'ShortDescription');
        $fields->removeFieldFromTab('Root', 'Content');
        $fields->removeFieldFromTab('Root', 'Metadata');
        $fields->removeFieldFromTab('Root', 'AddToCartLink');

        $fields->addFieldsToTab(
            'Root.Main',
            array(
                $allowPurchaseField = CheckboxField::create("AllowPurchase", "<strong>Allow product to be purchased</strong>"),
                $featuredProductField = CheckboxField::create('FeaturedProduct', _t('Product.FEATURED', '<strong>Featured Product</strong>')),
                TextField::create('Title', 'Product Title'),
            )
        );
        $secondhandProductCategories = SecondHandProductGroup::get();
        if($secondhandProductCategories->count()){
            $fields->addFieldToTab(
                'Root.Main',
                $categoryField = DropdownField::create(
                    'ParentID',
                    'Product Category',
                    $secondhandProductCategories->map()
                )
            );
        }
        $fields->addFieldsToTab(
            'Root.Main',
            array(
                TextField::create('InternalItemID', "Product Code"),
                $salePriceField = NumericField::create('Price', 'Sale Price'),
                $purchasePriceField = NumericField::create('PurchasePrice', 'Purchase Price'),
                $serialNumberField = TextField::create('SerialNumber', 'Serial Number'),
                $productQualityField = DropdownField::create(
                    "ProductQuality",
                    "Product Condition/Quality",
                    $this->dbObject('ProductQuality')->enumValues()
                ),
                $boxOrCaseField = DropdownField::create(
                    "IncludesBoxOrCase",
                    "Includes Box/Case",
                    $this->dbObject('IncludesBoxOrCase')->enumValues()
                ),
                $originalManualField = CheckboxField::create("OriginalManual", "Includes Original Manual"),
                $contentField = TextAreaField::create("ShortDescription", "Description"),
                $mainImageField = UploadField::create("Image", "Main Product Image"),
                $additionalImagesField = UploadField::create("AdditionalImages", "More Images"),
            )
        );
        //set right titles and descriptions
        $featuredProductField->setDescription('If this box is ticked then this product will appear in the "Featured Products" box on the home page');
        $allowPurchaseField->setDescription("This box must be ticked to allow a customer to purchase it");
        $salePriceField->setRightTitle("Selling price");
        $purchasePriceField->setRightTitle("Price paid for the product");
        $serialNumberField->setRightTitle("Enter the serial number of the product here");
        $originalManualField->setDescription("Tick this box if the product includes the original manual, otherwise leave it empty");
        $boxOrCaseField->setRightTitle("Does this product come with a box, case or both?");
        $contentField->setRightTitle("Optional text only description, the maximum length of this description is 255 characters.");
        $qualityFieldDescription = "A <strong>Condition Rating Page</strong> has yet to be setup";
        $obj = $this->EcomConfig()->SecondHandExplanationPage();
        if($obj->exists()){
            $qualityFieldDescription = 'An explanation of the ratings scale can be found by clicking this <a href="' . $obj->Link() . '">link</a>';
        }
        $productQualityField->setRightTitle($qualityFieldDescription);
        $mainImageField->setRightTitle(
            "<strong>Upload the main image for the product here.</strong><br>
            Recommended size: 810px wide x 418px high - but you can choose any width up to 810px, height must
            ALWAYS BE 418px. Should be provided to FTP data upload as productcode.jpg - e.g. 1003040.jpg.
            Images should be compressed up to 50%."
        );
        $additionalImagesField->setRightTitle(
            "<strong>Upload additional images here, you can upload as many as you want.</strong><br>
            Recommended size: 810px wide x 418px high - but you can choose any width up to 810px, height must
            ALWAYS BE 418px. Should be provided to FTP data upload as productcode.jpg - e.g. 1003040.jpg.
            Images should be compressed up to 50%."
        );
        //replace InternalItemID field with a read only field
        $fields->replaceField(
            'InternalItemID',
            $fields->dataFieldByName('InternalItemID')->performReadonlyTransformation()
        );

        $fields->addFieldsToTab(
            'Root.SellersDetails',
            array(
                HeaderField::create('SellersDetails', 'Enter the details of the person who the product was purchased from'),
                TextField::create('SellersName', 'Name'),
                TextField::create('SellersPhone', 'Phone'),
                TextField::create('SellersEmail', 'Email Address')
            )
        );

        if (class_exists('GoogleAddressField')) {
            $mappingArray = $this->Config()->get('fields_to_google_geocode_conversion');
            if (is_array($mappingArray) && count($mappingArray)) {
                $fields->addFieldToTab(
                    'Root.SellersDetails',
                    $geocodingField = new GoogleAddressField(
                        'SellersAddressGeocodingField',
                        _t('OrderAddress.FIND_ADDRESS', 'Find address'),
                        Session::get('SellersAddressGeocodingFieldValue')
                    )
                );
                $geocodingField->setFieldMap($mappingArray);
                //$geocodingField->setAlwaysShowFields(true);
            }
        }

        $fields->addFieldsToTab(
            'Root.SellersDetails',
            array (
                TextField::create('SellersAddress', 'Address'),
                TextField::create('SellersAddress2', 'Suburb'),
                TextField::create('SellersCity', 'City/Town'),
                TextField::create('SellersPostalCode', 'Postal Code'),
                TextField::create('SellersRegionCode', 'Region Code'),
                TextField::create('SellersCountry', 'Country'),
            )
        );
        //add all fields to the main tab
        $fields->addFieldToTab(
            'Root.SellersDetails',
            EcommerceCMSButtonField::create(
                'PrintView',
                $this->PrintView(),
                'Print Details',
                $newWindow = true
            )
        );
        return $fields;
    }

    function CMSEditLink()
    {
        return Controller::join_links(
            singleton('SecondHandProductAdmin')->Link(),
            $this->ClassName,
            'EditForm',
            'field',
            $this->ClassName,
            'item',
            $this->ID,
            'edit'
        );
    }

    function getSettingsFields()
    {
        $fields = parent::getSettingsFields();
        $fields->removeByName('ParentID');
        return $fields;
    }

    public function canPurchase(Member $member = NULL, $checkPrice = true) {
        //to do - Nicolaas to review this code
        $orderItems = OrderItem::get()->filter(
            array("BuyableID" => $this->ID, "BuyableClassName" => $this->ClassName)
        );
        if($orderItems->count()){
            foreach($orderItems as $item){
                $order = $item->Order();
                if($order && $order->IsSubmitted()) {
                    return false;
                }
            }
        }
        return parent::canPurchase($member, $checkPrice);
    }

    function onBeforeWrite()
    {
        parent::onBeforeWrite();
        $this->URLSegment = $this->generateURLSegment($this->Title);
        //set the IternatlItemID if it doesn't already exist
        if( ! $this->InternalItemID) {
            //todo - this may need improvement
            $this->InternalItemID = "S-H-".strtoupper(substr(md5(microtime()),rand(0,26),5));
        }
    }

    public function SecondHandProductQualityPercentage() {
        return $this->ProductQuality * 10;
    }

    function InternalItemIDNice(){
        return $this->InternalItemID;
    }

    function providePermissions()
    {
        $perms[EcommerceConfig::get('SecondHandProduct', 'second_hand_admin_permission_code')] = array(
            'name' => EcommerceConfig::get('SecondHandProduct', 'second_hand_admin_permission_title'),
            'category' => 'E-commerce',
            'help' => EcommerceConfig::get('SecondHandProduct', 'second_hand_admin_permission_help'),
            'sort' => 250,
        );
        return $perms;
    }

    function requireDefaultRecords()
    {
        parent::requireDefaultRecords();
        $permissionProviderFactory = Injector::inst()->get('PermissionProviderFactory');
        $member = $permissionProviderFactory->CreateDefaultMember(
            EcommerceConfig::get('SecondHandProduct', 'second_hand_admin_user_email'),
            EcommerceConfig::get('SecondHandProduct', 'second_hand_admin_user_firstname'),
            EcommerceConfig::get('SecondHandProduct', 'second_hand_admin_user_surname'),
            EcommerceConfig::get('SecondHandProduct', 'second_hand_admin_user_password')
        );
        $permissionProviderFactory->CreateGroup(
            $code = EcommerceConfig::get('SecondHandProduct', 'second_hand_admin_group_code'),
            $name = EcommerceConfig::get('SecondHandProduct', 'second_hand_admin_group_name'),
            $parentGroup = null,
            $permissionCode = EcommerceConfig::get(
                'SecondHandProduct',
                'second_hand_admin_permission_code'
            ),
            $roleTitle = EcommerceConfig::get(
                'SecondHandProduct',
                'second_hand_admin_permission_title'
            ),
            $permissionArray = array(
                'SITETREE_VIEW_ALL',
                'CMS_ACCESS_SecondHandProductAdmin'
            ),
            $member
        );
    }
}

class SecondHandProduct_Controller extends Product_Controller {

    private static $allowed_actions = array(
        'printview' => 'CMS_ACCESS_SECOND_HAND_PRODUCTS'
    );

    function printview()
    {
        return $this->renderWith('SecondHandProduct_printview');
    }

    function ListOfFieldsForPrinting()
    {
        $al = ArrayList::create();
        $fieldsWeNeed = array(
            'Title' => 'Product',
            'InternalItemID' => 'Code',
            'PurchasePrice' => 'Bought from customer: ',
            'Price' => 'We are selling for',
            "ProductQuality" => 'Product Quality',
            "IncludesBoxOrCase" => 'Box and/or Case Included',
            "OriginalManual" => 'Original Manual Included',
            "SerialNumber" => 'Serial Number',
            "SellersDetails" => '</br><span style="font-size: 1.5em;">Sellers Details</span>',
            "SellersName" =>  "Name",
            "SellersPhone" =>  'Phone',
            "SellersEmail" =>  "Email",
            "SellersAddress" =>  "Address",
            'SellersAddress2' => 'Suburb',
            'SellersCity' => 'City',
            'SellersPostalCode' => 'Postal Code',
            'SellersRegionCode' => 'Region',
            'SellersCountry' => 'Country'
        );
        $fields = $this->dataRecord->db();
        foreach($fieldsWeNeed as $key => $description) {
            if(isset($fields[$key])){
                $type = preg_replace('/\(.*\)/', '', $fields[$key]);
                $dbField = DBField::create_field($type, $this->$key);
                if($dbField->hasMethod('Nice')) {
                    $value = $dbField->Nice();
                } else {
                    $value = $dbField->Raw();
                }
            }
            else {
                $value = "";
            }
            $al->push(
                ArrayData::create(
                    array(
                        'Key' => $description,
                        'Value' => $value
                    )
                )
            );
        }
        return $al;
    }

}
