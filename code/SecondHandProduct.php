<?php


class SecondHandProduct extends Product implements PermissionProvider {

    private static $can_be_root = false;

    /**
     * halt purchase for ... number of days 
     * from the day of creation.
     * @var int
     */
    private static $embargo_number_of_days = 0;

    /**
     * Restrict GoogleAddressField to a specific Country
     * E.g. for New Zealand, $country_code =  'NZ'
     * @var string
     */
    private static $country_code = null;
    /**
     * stadard SS declaration
     * @var Array
     */
    private static $db = array (
        'PurchasePrice' => 'Currency',
        'ProductQuality' => 'ENUM("1, 2, 3, 4, 5, 6, 7, 8, 9, 10","10")',
        'IncludesBoxOrCase' => "ENUM('No, Box, Case, Both','No')",
        'SellingOnBehalf' => 'Boolean',
        'OriginalManual' => 'Boolean',
        'DateItemWasBought' => 'Date', 
        'DateItemWasSold' => 'Date', 
        'SerialNumber' => 'VarChar(50)',
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

    private static $has_one = array(
        'BasedOn' => 'SecondHandProduct'
    );
    
    private static $default_sort = array(
        'Created' => 'DESC'
    );
   
    private static $defaults = array(
        'ShowInMenus' => false
    );

    private static $indexes = array(
        'SerialNumber' => true
    );
    
    private static $casting = array(
        'SellersSummary' => 'Varchar'
    );
    
    private static $seller_summary_detail_fields = array(
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
        'SellersIDPhotocopy'
    );

    public function getSellerSummary()
    {
        $list = Config::inst()->get('SecondHandProduct', 'seller_summary_detail_fields');
        $array = array();
        foreach($list as $field) {
            if(trim($this->$field)){
                $array[] = $this->$field;
            }
        }
        return implode('; ', $array);
    }
    
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
     * standard SS declaration
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
     * standard SS declaration
     * @var String
     */
    private static $description = "This page displays a single secondhand product that can only be sold once";

    /**
     * standard SS method
     * @return Boolean
     */
    public function canCreate($member = null) {
        return Permission::check(
            EcommerceConfig::get('SecondHandProduct', 'second_hand_admin_permission_code'),
            'any',
            $member
        );
    }

    /**
     * standard SS method
     * @return Boolean
     */
    public function canPublish($member = null) {
        return Permission::check(
            EcommerceConfig::get('SecondHandProduct', 'second_hand_admin_permission_code'),
            'any',
            $member
        );

    }

    /**
     * standard SS method
     * @return Boolean
     */
    public function canEdit($member = null) {
        return Permission::check(
            EcommerceConfig::get('SecondHandProduct', 'second_hand_admin_permission_code'),
            'any',
            $member
        );

    }

    /**
     * standard SS method
     * @return Boolean
     */
    public function canDelete($member = null) {
        return Permission::check(
            EcommerceConfig::get('SecondHandProduct', 'second_hand_admin_permission_code'),
            'any',
            $member
        );
    }

    public function onBeforeDelete() {
        SecondHandArchive::create_from_page($this);
        parent::onBeforeDelete();
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
                $sellinOnBehalf = CheckboxField::create("SellingOnBehalf", "<strong>Selling on behalf</strong>"),
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
                $contentField = TextField::create("ShortDescription", "Description"),
                $boughtDate = DateField::create('DateItemWasBought','Date this item was bought'),
                DateField_Disabled::create('DateItemWasSold','Date this item was sold'),
                $mainImageField = UploadField::create("Image", "Main Product Image"),
                $additionalImagesField = UploadField::create("AdditionalImages", "More Images"),
            )
        );
        
        //set right titles and descriptions
        $featuredProductField->setDescription('If this box is ticked then this product will appear in the "Featured Products" box on the home page');
        $allowPurchaseField->setDescription("This box must be ticked to allow a customer to purchase it");
        $sellinOnBehalf->setDescription('This box must be ticked if this product is being sold on behalf');
        $salePriceField->setRightTitle("Selling price");
        $purchasePriceField->setRightTitle("Price paid for the product");
        $serialNumberField->setRightTitle("Enter the serial number of the product here");
        $originalManualField->setDescription("Tick this box if the product includes the original manual, otherwise leave it empty");
        $boxOrCaseField->setRightTitle("Does this product come with a box, case or both?");
        $contentField->setRightTitle("Optional text only description, the maximum length of this description is 255 characters.");
        $contentField->setMaxLength(255);
        $qualityFieldDescription = "A <strong>Condition Rating Page</strong> has yet to be setup";
        $obj = $this->EcomConfig()->SecondHandExplanationPage();
        if($obj->exists()){
            $qualityFieldDescription = 'An explanation of the ratings scale can be found by clicking this <a href="' . $obj->Link() . '">link</a>';
        }
        $productQualityField->setRightTitle($qualityFieldDescription);
        $boughtDate->setRightTitle('Date Format (dd-mm-YYYY). Example: 3rd of May 1992 should be entered as 03-05-1992');
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

        $lastEditedItems = SecondHandProduct::get()->sort('Created','DESC')->limit(100);
        
        $lastItems = array(
            0 => '--- not based on previous sale ---'
        );
        
        foreach($lastEditedItems as $lastEditedItem){
            $details = $lastEditedItem->getSellerSummary();
            if($details) {
                $lastItems[$lastEditedItem->ID] = $details;
            }
        }
        
        $fields->addFieldsToTab(
            'Root.SellersDetails',
            array(
                HeaderField::create('SellersDetails', 'Enter the details of the person who the product was purchased from'),
                DropdownField::create(
                    'BasedOnID',
                    'Autocomplete from saved items',
                    $lastItems),
                TextField::create('SellersName', 'Name'),
                TextField::create('SellersPhone', 'Phone'),
                TextField::create('SellersEmail', 'Email Address'),
                DropdownField::create(
                    'SellersIDType',
                    'ID Type',
                    $this->dbObject('SellersIDType')->enumValues()),
                TextField::create('SellersIDNumber', 'ID Number'),
                DateField::create('SellersDateOfBirth', 'Date of Birth'),
                DateField::create('SellersIDExpiryDate', 'ID Expiry Date'),
                CheckboxField::create('SellersIDPhotocopy', 'ID Photocopy')
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
                
                $country_code = Config::inst()->get('SecondHandProduct', 'country_code');
                if ($country_code){
                    $geocodingField->setRestrictToCountryCode($country_code);
                }
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
                $this->getPrintLink(),
                'Print Details',
                $newWindow = true
            )
        );
        if($this->BasedOnID) {
            $list = Config::inst()->get('SecondHandProduct', 'seller_summary_detail_fields');
            $labels = $this->FieldLabels();
            foreach($list as $listField){
                $fields->replaceField(
                    $listField, 
                    ReadonlyField::create(
                        $listField, 
                        $fields->dataFieldByName($listField)->Title()
                    )
                );
            }
            $fields->removeByName('SellersAddressGeocodingField');
        }
        $fields->addFieldToTab(
            'Root.Categorisation',
            $this->getProductGroupsTableField()
        );        
        return $fields;
    }

    public function getPrintLink() {
        return $this->link('printview');
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
                if($order && $order->IsSubmitted() && !$order->IsCancelled()) {
                    return false;
                }
            }
        }
        $embargoDays = Config::inst()->get('SecondHandProduct', 'embargo_number_of_days');
        if(intval($embargoDays) > 0) {
            $daysOld = (time() - strtotime($this->DateItemWasBought)) / 60 / 60 / 24;
            if($daysOld <= $embargoDays) {
                return false;
            }
        }
        return parent::canPurchase($member, $checkPrice);
    }

    function onBeforeWrite()
    {
        if($this->BasedOnID){
            $basedOn = $this->BasedOn();
            if($basedOn && $basedOn->exists()){
                $list = Config::inst()->get('SecondHandProduct', 'seller_summary_detail_fields');
                foreach($list as $field){
                    $this->$field = $basedOn->$field; 
                }
            }
        }       
        $list = Config::inst()->get('SecondHandProduct', 'seller_summary_detail_fields');
        
        //set the IternatlItemID if it doesn't already exist
        if( ! $this->InternalItemID) {
            //todo - this may need improvement
            $this->InternalItemID = "S-H-".strtoupper(substr(md5(microtime()),rand(0,26),5));
        }
        $this->URLSegment = $this->generateURLSegment($this->Title."-".$this->InternalItemID);
        
        // Save the date when the product was sold.
        if (! $this->AllowPurchase){
            $this->DateItemWasSold = SS_Datetime::now()->Rfc2822();
        }
                    
        parent::onBeforeWrite();
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
                'CMS_ACCESS_SecondHandProductAdmin',
            ),
            $member
        );
    }

    function onAferSubmit($order)
    {
        DB::query('Update \"Product\" SET AllowPurchase = 0 WHERE \"Product\".\"ID\" = '.$this->ID);
        DB::query('Update \"Product_Live\" SET AllowPurchase = 0 WHERE \"Product_Live\".\"ID\" = '.$this->ID);
        $this->writeToStage('Stage');
        $this->doPublish();
    }


    /**
     * adds created as a summary field as we are sorting by created
     * @return array
     */
    public function summaryFields() {
        $fields = parent::summaryFields();
        $fields['Created'] = 'Created';
        return $fields;
    }
    
    
    public function populateDefaults() {
        parent::populateDefaults();
        if(! $this->DateItemWasBought){
            $this->DateItemWasBought = SS_Datetime::now()->Rfc2822();
        }
    }    

}

class SecondHandProduct_Controller extends Product_Controller {

    private static $allowed_actions = array(
        'printview' => true
    );

    function printview()
    {
        if(!Permission::check('CMS_ACCESS_SECOND_HAND_PRODUCTS')){
            return Security::permissionFailure($this, 'You do not have access to this feature, please login first.');
        }
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
