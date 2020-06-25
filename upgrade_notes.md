2020-06-25 02:30

# running php upgrade upgrade see: https://github.com/silverstripe/silverstripe-upgrader
cd /var/www/upgrades/ecommerce_second_hand_product
php /var/www/ss3/upgrader/vendor/silverstripe/upgrader/bin/upgrade-code upgrade /var/www/upgrades/ecommerce_second_hand_product/ecommerce_second_hand_product  --root-dir=/var/www/upgrades/ecommerce_second_hand_product --write -vvv
Writing changes for 22 files
Running upgrades on "/var/www/upgrades/ecommerce_second_hand_product/ecommerce_second_hand_product"
[2020-06-25 14:30:07] Applying RenameClasses to _config.php...
[2020-06-25 14:30:07] Applying ClassToTraitRule to _config.php...
[2020-06-25 14:30:07] Applying RenameClasses to EcommerceSecondHandProductTest.php...
[2020-06-25 14:30:07] Applying ClassToTraitRule to EcommerceSecondHandProductTest.php...
[2020-06-25 14:30:07] Applying RenameClasses to ExportSecondHandProducts.php...
[2020-06-25 14:30:07] Applying ClassToTraitRule to ExportSecondHandProducts.php...
[2020-06-25 14:30:07] Applying RenameClasses to ControllerPermissionChecker.php...
[2020-06-25 14:30:07] Applying ClassToTraitRule to ControllerPermissionChecker.php...
[2020-06-25 14:30:07] Applying RenameClasses to UpdateSecondHandProduct.php...
[2020-06-25 14:30:07] Applying ClassToTraitRule to UpdateSecondHandProduct.php...
[2020-06-25 14:30:07] Applying RenameClasses to SecondHandProductGroup.php...
[2020-06-25 14:30:07] Applying ClassToTraitRule to SecondHandProductGroup.php...
[2020-06-25 14:30:07] Applying RenameClasses to SecondHandArchive.php...
[2020-06-25 14:30:07] Applying ClassToTraitRule to SecondHandArchive.php...
[2020-06-25 14:30:07] Applying RenameClasses to SecondHandEcommerceConfigExtension.php...
[2020-06-25 14:30:07] Applying ClassToTraitRule to SecondHandEcommerceConfigExtension.php...
[2020-06-25 14:30:07] Applying RenameClasses to OrderStep_DisableSecondHandProduct.php...
[2020-06-25 14:30:07] Applying ClassToTraitRule to OrderStep_DisableSecondHandProduct.php...
[2020-06-25 14:30:07] Applying RenameClasses to OrderStep_RemoveSecondHandProduct.php...
[2020-06-25 14:30:07] Applying ClassToTraitRule to OrderStep_RemoveSecondHandProduct.php...
[2020-06-25 14:30:07] Applying RenameClasses to GridFieldAddNewButtonOriginalPageSecondHandProduct.php...
[2020-06-25 14:30:07] Applying ClassToTraitRule to GridFieldAddNewButtonOriginalPageSecondHandProduct.php...
[2020-06-25 14:30:07] Applying RenameClasses to GridFieldEditOriginalPageConfigSecondHandPage.php...
[2020-06-25 14:30:07] Applying ClassToTraitRule to GridFieldEditOriginalPageConfigSecondHandPage.php...
[2020-06-25 14:30:07] Applying RenameClasses to SecondHandProductGroup_Controller.php...
[2020-06-25 14:30:07] Applying ClassToTraitRule to SecondHandProductGroup_Controller.php...
[2020-06-25 14:30:07] Applying RenameClasses to SecondHandProductAdmin.php...
[2020-06-25 14:30:07] Applying ClassToTraitRule to SecondHandProductAdmin.php...
[2020-06-25 14:30:07] Applying RenameClasses to CMSPageAddController_SecondHandProducts.php...
[2020-06-25 14:30:07] Applying ClassToTraitRule to CMSPageAddController_SecondHandProducts.php...
[2020-06-25 14:30:07] Applying RenameClasses to EcommerceTaskCreateSecondHandProductManager.php...
[2020-06-25 14:30:07] Applying ClassToTraitRule to EcommerceTaskCreateSecondHandProductManager.php...
[2020-06-25 14:30:07] Applying RenameClasses to EcommerceTaskSecondHandPublishAll.php...
[2020-06-25 14:30:07] Applying ClassToTraitRule to EcommerceTaskSecondHandPublishAll.php...
[2020-06-25 14:30:07] Applying RenameClasses to SecondHandProduct.php...
[2020-06-25 14:30:07] Applying ClassToTraitRule to SecondHandProduct.php...
[2020-06-25 14:30:07] Applying RenameClasses to SecondHandProduct_Controller.php...
[2020-06-25 14:30:07] Applying ClassToTraitRule to SecondHandProduct_Controller.php...
[2020-06-25 14:30:07] Applying RenameClasses to SecondHandProductValue.php...
[2020-06-25 14:30:07] Applying ClassToTraitRule to SecondHandProductValue.php...
[2020-06-25 14:30:07] Applying UpdateConfigClasses to config.yml...
[2020-06-25 14:30:07] Applying UpdateConfigClasses to routes.yml...
modified:	_config.php
@@ -1,5 +1,8 @@
 <?php

+use Sunnysideup\EcommerceSecondHandProduct\Cms\CMSPageAddController_SecondHandProducts;
+use SilverStripe\Admin\CMSMenu;

-CMSMenu::remove_menu_item('CMSPageAddController_SecondHandProducts');

+CMSMenu::remove_menu_item(CMSPageAddController_SecondHandProducts::class);
+

modified:	tests/EcommerceSecondHandProductTest.php
@@ -1,4 +1,6 @@
 <?php
+
+use SilverStripe\Dev\SapphireTest;

 class EcommerceSecondHandProductTest extends SapphireTest
 {

modified:	src/Control/ExportSecondHandProducts.php
@@ -2,16 +2,29 @@

 namespace Sunnysideup\EcommerceSecondHandProduct\Control;

-use Controller;
-use SecondHandProduct;
-use Injector;
-use Config;
-use SecondHandArchive;
-use SecondHandProductGroup;
-use Director;
-use SS_List;
-use DataObject;
-use Folder;
+
+
+
+
+
+
+
+
+
+
+use Sunnysideup\EcommerceSecondHandProduct\SecondHandProduct;
+use SilverStripe\Core\Injector\Injector;
+use Sunnysideup\EcommerceSecondHandProduct\SecondHandProductGroup;
+use SilverStripe\Core\Config\Config;
+use Sunnysideup\EcommerceSecondHandProduct\Control\ExportSecondHandProducts;
+use Sunnysideup\EcommerceSecondHandProduct\Model\SecondHandArchive;
+use SilverStripe\Control\Director;
+use SilverStripe\ORM\SS_List;
+use SilverStripe\ORM\DataObject;
+use SilverStripe\Assets\Folder;
+use Sunnysideup\Ecommerce\Filesystem\ProductImage;
+use SilverStripe\Control\Controller;
+



@@ -112,9 +125,9 @@
         $count = 0;
         $doNotCopy = $this->Config()->get('do_not_copy');
         $parentURLSegmentField = $this->Config()->get('url_segment_of_parent_field_name');
-        $singleton = Injector::inst()->get('SecondHandProductGroup');
+        $singleton = Injector::inst()->get(SecondHandProductGroup::class);
         $rootSecondHandPage = $singleton->BestRootParentPage();
-        $relations = Config::inst()->get('ExportSecondHandProducts', 'relationships_to_include_with_products');
+        $relations = Config::inst()->get(ExportSecondHandProducts::class, 'relationships_to_include_with_products');
         if ($rootSecondHandPage) {
             foreach ($products as $product) {
                 $productData = $product->toMap();
@@ -150,9 +163,9 @@
         $count = 0;
         $doNotCopy = $this->Config()->get('do_not_copy');
         $parentURLSegmentField = $this->Config()->get('url_segment_of_parent_field_name');
-        $singleton = Injector::inst()->get('SecondHandProductGroup');
+        $singleton = Injector::inst()->get(SecondHandProductGroup::class);
         $rootSecondHandPage = $singleton->BestRootParentPage();
-        $relations = Config::inst()->get('ExportSecondHandProducts', 'relationships_to_include_with_groups');
+        $relations = Config::inst()->get(ExportSecondHandProducts::class, 'relationships_to_include_with_groups');
         if ($rootSecondHandPage) {
             foreach ($groups as $group) {
                 if (! $group->RootParent) {
@@ -254,7 +267,7 @@
     {
         $err = 0;
         $array = [];
-        $folderName = Config::inst()->get('ExportSecondHandProducts', 'folder_for_second_hand_images');
+        $folderName = Config::inst()->get(ExportSecondHandProducts::class, 'folder_for_second_hand_images');
         $folder = Folder::find_or_make($folderName);
         $secondHandProducts = SecondHandProduct::get()->filter(array('AllowPurchase' => 1))->exclude(array('ImageID' => 0));
         foreach ($secondHandProducts as $secondHandProduct) {
@@ -289,7 +302,7 @@
                     $image->Name = $name;
                     $image->FileName = $fileName;
                     $image->Title = $title;
-                    $image->ClassName = 'ProductImage';
+                    $image->ClassName = ProductImage::class;
                     $image->write();
                     $newAbsoluteLocation = Director::baseFolder().'/'.$image->FileName;
                     if (! file_exists($newAbsoluteLocation)) {

modified:	src/Control/ControllerPermissionChecker.php
@@ -2,9 +2,13 @@

 namespace Sunnysideup\EcommerceSecondHandProduct\Control;

-use Controller;
-use EcommerceCountry;
-use Permission;
+
+
+
+use Sunnysideup\Ecommerce\Model\Address\EcommerceCountry;
+use SilverStripe\Security\Permission;
+use SilverStripe\Control\Controller;
+


 class ControllerPermissionChecker extends Controller

modified:	src/Control/UpdateSecondHandProduct.php
@@ -2,10 +2,17 @@

 namespace Sunnysideup\EcommerceSecondHandProduct\Control;

-use Controller;
-use Convert;
-use DataObject;
-use Versioned;
+
+
+
+
+use SilverStripe\Core\Convert;
+use Sunnysideup\EcommerceSecondHandProduct\SecondHandProduct;
+use SilverStripe\ORM\DataObject;
+use SilverStripe\Versioned\Versioned;
+use SilverStripe\CMS\Model\SiteTree;
+use SilverStripe\Control\Controller;
+



@@ -39,7 +46,7 @@
         $otherID = $request->param("OtherID");
         if (isset($otherID)) {
             $internalItemID = Convert::raw2sql($otherID);
-            $secondHandProduct = DataObject::get_one('SecondHandProduct', ['InternalItemID' => $internalItemID]);
+            $secondHandProduct = DataObject::get_one(SecondHandProduct::class, ['InternalItemID' => $internalItemID]);
             if ($secondHandProduct) {
                 $unpublished = $secondHandProduct->deleteFromStage('Live');
             }
@@ -54,9 +61,9 @@
         if (isset($otherID)) {
             $archived = null;
             $internalItemID = Convert::raw2sql($otherID);
-            $secondHandProduct = DataObject::get_one('SecondHandProduct', ['InternalItemID' => $internalItemID]);
+            $secondHandProduct = DataObject::get_one(SecondHandProduct::class, ['InternalItemID' => $internalItemID]);
             if (!$secondHandProduct) {
-                $secondHandProduct = Versioned::get_one_by_stage('SecondHandProduct', 'Stage', ['InternalItemID' => $internalItemID]);
+                $secondHandProduct = Versioned::get_one_by_stage(SecondHandProduct::class, 'Stage', ['InternalItemID' => $internalItemID]);
             }

 /**
@@ -67,7 +74,7 @@
   * EXP: Check if this is the right implementation, this is highly speculative.
   * ### @@@@ STOP REPLACEMENT @@@@ ###
   */
-            if (is_a($secondHandProduct, SilverStripe\Core\Injector\Injector::inst()->getCustomClass('SiteTree'))) {
+            if (is_a($secondHandProduct, SilverStripe\Core\Injector\Injector::inst()->getCustomClass(SiteTree::class))) {
                 $archived = $secondHandProduct->deleteFromStage('Live');
                 $archived = $secondHandProduct->deleteFromStage('Stage');
             } elseif (! is_null($secondHandProduct)) {

modified:	src/SecondHandProductGroup.php
@@ -2,10 +2,17 @@

 namespace Sunnysideup\EcommerceSecondHandProduct;

-use ProductGroup;
-use CheckboxField;
-use SiteTree;
-use DataObject;
+
+
+
+
+use Sunnysideup\EcommerceSecondHandProduct\SecondHandProductGroup;
+use Sunnysideup\EcommerceSecondHandProduct\SecondHandProduct;
+use SilverStripe\Forms\CheckboxField;
+use SilverStripe\CMS\Model\SiteTree;
+use SilverStripe\ORM\DataObject;
+use Sunnysideup\Ecommerce\Pages\ProductGroup;
+



@@ -30,8 +37,8 @@
     );

     private static $allowed_children = array(
-        'SecondHandProductGroup',
-        'SecondHandProduct'
+        SecondHandProductGroup::class,
+        SecondHandProduct::class
     );

     private static $icon = 'ecommerce_second_hand_product/images/treeicons/SecondHandProductGroup';
@@ -119,7 +126,7 @@
     public function BestRootParentPage()
     {
         $obj = DataObject::get_one(
-            'SecondHandProductGroup',
+            SecondHandProductGroup::class,
             array('RootParent' => 1)
         );
         if ($obj) {
@@ -136,7 +143,7 @@
      */
     protected function getBuyableClassName()
     {
-        return 'SecondHandProduct';
+        return SecondHandProduct::class;
     }
 }


modified:	src/Model/SecondHandArchive.php
@@ -2,13 +2,22 @@

 namespace Sunnysideup\EcommerceSecondHandProduct\Model;

-use DataObject;
-use Member;
-use Permission;
-use EcommerceConfig;
-use Config;
-use EcommerceCMSButtonField;
-use ReadonlyField;
+
+
+
+
+
+
+
+use SilverStripe\Security\Member;
+use Sunnysideup\EcommerceSecondHandProduct\SecondHandProduct;
+use Sunnysideup\Ecommerce\Config\EcommerceConfig;
+use SilverStripe\Security\Permission;
+use SilverStripe\Core\Config\Config;
+use Sunnysideup\Ecommerce\Forms\Fields\EcommerceCMSButtonField;
+use SilverStripe\Forms\ReadonlyField;
+use SilverStripe\ORM\DataObject;
+



@@ -141,7 +150,7 @@
     public function canView($member = null, $context = [])
     {
         return Permission::check(
-            EcommerceConfig::get('SecondHandProduct', 'second_hand_admin_permission_code')
+            EcommerceConfig::get(SecondHandProduct::class, 'second_hand_admin_permission_code')
         );
     }


modified:	src/Model/SecondHandEcommerceConfigExtension.php
@@ -2,9 +2,14 @@

 namespace Sunnysideup\EcommerceSecondHandProduct\Model;

-use DataExtension;
-use FieldList;
-use TreeDropdownField;
+
+
+
+use SilverStripe\CMS\Model\SiteTree;
+use SilverStripe\Forms\FieldList;
+use SilverStripe\Forms\TreeDropdownField;
+use SilverStripe\ORM\DataExtension;
+



@@ -26,7 +31,7 @@
 class SecondHandEcommerceConfigExtension extends DataExtension
 {
     private static $has_one = array(
-        "SecondHandExplanationPage" => 'SiteTree'
+        "SecondHandExplanationPage" => SiteTree::class
     );

     /**
@@ -40,7 +45,7 @@
             TreeDropdownField::create(
                 'SecondHandExplanationPageID',
                 'Second Hand Explanation Page',
-                'SiteTree'
+                SiteTree::class
             )
         );
         return $fields;

modified:	src/Model/Process/OrderStep_DisableSecondHandProduct.php
@@ -2,10 +2,16 @@

 namespace Sunnysideup\EcommerceSecondHandProduct\Model\Process;

-use OrderStep;
-use OrderStepInterface;
-use Order;
-use SecondHandProduct;
+
+
+
+
+use Sunnysideup\Ecommerce\Model\Order;
+use Sunnysideup\EcommerceSecondHandProduct\SecondHandProduct;
+use SilverStripe\CMS\Model\SiteTree;
+use Sunnysideup\Ecommerce\Model\Process\OrderStep;
+use Sunnysideup\Ecommerce\Interfaces\OrderStepInterface;
+


 class OrderStep_DisableSecondHandProduct extends OrderStep implements OrderStepInterface
@@ -59,7 +65,7 @@
         foreach ($order->Buyables() as $buyable) {
             if ($buyable instanceof SecondHandProduct) {
                 $buyable->AllowPurchase = 0;
-                if (is_a($buyable, Object::getCustomClass('SiteTree'))) {
+                if (is_a($buyable, Object::getCustomClass(SiteTree::class))) {
                     $buyable->writeToStage('Stage');
                     $buyable->publish('Stage', 'Live');
                 } else {

modified:	src/Model/Process/OrderStep_RemoveSecondHandProduct.php
@@ -2,11 +2,18 @@

 namespace Sunnysideup\EcommerceSecondHandProduct\Model\Process;

-use OrderStep;
-use OrderStepInterface;
-use Order;
-use SecondHandProduct;
-use Member;
+
+
+
+
+
+use Sunnysideup\Ecommerce\Model\Order;
+use Sunnysideup\EcommerceSecondHandProduct\SecondHandProduct;
+use SilverStripe\Security\Member;
+use SilverStripe\CMS\Model\SiteTree;
+use Sunnysideup\Ecommerce\Model\Process\OrderStep;
+use Sunnysideup\Ecommerce\Interfaces\OrderStepInterface;
+


 class OrderStep_RemoveSecondHandProduct extends OrderStep implements OrderStepInterface
@@ -61,7 +68,7 @@
             if ($buyable instanceof SecondHandProduct) {
                 $currentMember = Member::currentUser();
                 $buyable->ArchivedByID = $order->MemberID;
-                if (is_a($buyable, Object::getCustomClass('SiteTree'))) {
+                if (is_a($buyable, Object::getCustomClass(SiteTree::class))) {
                     $buyable->write();
                     $buyable->doPublish();
                     $buyable->deleteFromStage('Live');

modified:	src/Forms/Gridfield/GridFieldAddNewButtonOriginalPageSecondHandProduct.php
@@ -2,10 +2,17 @@

 namespace Sunnysideup\EcommerceSecondHandProduct\Forms\Gridfield;

-use GridFieldAddNewButtonOriginalPage;
-use ArrayData;
-use Config;
-use Injector;
+
+
+
+
+use SilverStripe\Core\Config\Config;
+use Sunnysideup\EcommerceSecondHandProduct\Cms\CMSPageAddController_SecondHandProducts;
+use SilverStripe\View\ArrayData;
+use SilverStripe\Core\Injector\Injector;
+use Sunnysideup\EcommerceSecondHandProduct\SecondHandProductGroup;
+use Sunnysideup\Ecommerce\Forms\Gridfield\GridFieldAddNewButtonOriginalPage;
+


 /**
@@ -40,7 +47,7 @@
         }

         $data = new ArrayData(array(
-            'NewLink' => '/admin/'.Config::inst()->get('CMSPageAddController_SecondHandProducts', 'url_segment').'/'.$getSegment,
+            'NewLink' => '/admin/'.Config::inst()->get(CMSPageAddController_SecondHandProducts::class, 'url_segment').'/'.$getSegment,
             'ButtonName' => $this->buttonName,
         ));

@@ -65,7 +72,7 @@
      */
     public function BestRootParentPage()
     {
-        $singleton = Injector::inst()->get('SecondHandProductGroup');
+        $singleton = Injector::inst()->get(SecondHandProductGroup::class);

         return $singleton->BestRootParentPage();
     }

modified:	src/Forms/Gridfield/Configs/GridFieldEditOriginalPageConfigSecondHandPage.php
@@ -2,8 +2,14 @@

 namespace Sunnysideup\EcommerceSecondHandProduct\Forms\Gridfield\Configs;

-use GridFieldConfig_RecordEditor;
-use GridFieldAddNewButtonOriginalPageSecondHandProduct;
+
+
+use SilverStripe\Forms\GridField\GridFieldDeleteAction;
+use SilverStripe\Forms\GridField\GridFieldAddNewButton;
+use Sunnysideup\Ecommerce\Forms\Gridfield\GridFieldAddNewButtonOriginalPage;
+use Sunnysideup\EcommerceSecondHandProduct\Forms\Gridfield\GridFieldAddNewButtonOriginalPageSecondHandProduct;
+use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
+

 /**
  * @author nicolaas <github@sunnysideup.co.nz>
@@ -17,9 +23,9 @@
     {
         parent::__construct($itemsPerPage);
         $this
-            ->removeComponentsByType('GridFieldDeleteAction')
-            ->removeComponentsByType('GridFieldAddNewButton')
-            ->removeComponentsByType('GridFieldAddNewButtonOriginalPage')
+            ->removeComponentsByType(GridFieldDeleteAction::class)
+            ->removeComponentsByType(GridFieldAddNewButton::class)
+            ->removeComponentsByType(GridFieldAddNewButtonOriginalPage::class)
             ->addComponent(new GridFieldAddNewButtonOriginalPageSecondHandProduct());
     }
 }

modified:	src/SecondHandProductGroup_Controller.php
@@ -2,14 +2,25 @@

 namespace Sunnysideup\EcommerceSecondHandProduct;

-use ProductGroupController;
-use Config;
-use FieldList;
-use TextField;
-use FormAction;
-use RequiredFields;
-use Form;
-use Convert;
+
+
+
+
+
+
+
+
+use SilverStripe\Core\Config\Config;
+use Sunnysideup\Ecommerce\Pages\ProductGroup;
+use Sunnysideup\EcommerceSecondHandProduct\SecondHandProduct;
+use SilverStripe\Forms\TextField;
+use SilverStripe\Forms\FieldList;
+use SilverStripe\Forms\FormAction;
+use SilverStripe\Forms\RequiredFields;
+use SilverStripe\Forms\Form;
+use SilverStripe\Core\Convert;
+use Sunnysideup\Ecommerce\Pages\ProductGroupController;
+


 class SecondHandProductGroupController extends ProductGroupController
@@ -30,9 +41,9 @@
     protected function init()
     {
         Config::modify()->update(
-            'ProductGroup',
+            ProductGroup::class,
             'base_buyable_class',
-            'SecondHandProduct'
+            SecondHandProduct::class
         );
         parent::init();
         $this->showFullList = true;

modified:	src/Cms/SecondHandProductAdmin.php
@@ -2,19 +2,34 @@

 namespace Sunnysideup\EcommerceSecondHandProduct\Cms;

-use ModelAdminEcommerceBaseClass;
-use GoogleAddressField;
-use Requirements;
-use SiteTree;
-use GridField;
-use GridFieldEditOriginalPageConfigSecondHandPage;
-use GridFieldExportButton;
-use SecondHandProduct;
-use Member;
-use SecondHandArchive;
-use Controller;
-use SS_HTTPResponse;
-use Versioned;
+
+
+
+
+
+
+
+
+
+
+
+
+
+use Sunnysideup\EcommerceSecondHandProduct\SecondHandProduct;
+use Sunnysideup\EcommerceSecondHandProduct\Model\SecondHandArchive;
+use Sunnysideup\GoogleAddressField\GoogleAddressField;
+use SilverStripe\View\Requirements;
+use SilverStripe\CMS\Model\SiteTree;
+use SilverStripe\Forms\GridField\GridField;
+use Sunnysideup\EcommerceSecondHandProduct\Forms\Gridfield\Configs\GridFieldEditOriginalPageConfigSecondHandPage;
+use SilverStripe\Forms\GridField\GridFieldExportButton;
+use SilverStripe\CMS\Controllers\CMSMain;
+use SilverStripe\Security\Member;
+use SilverStripe\Control\Controller;
+use SilverStripe\Control\HTTPResponse;
+use SilverStripe\Versioned\Versioned;
+use Sunnysideup\Ecommerce\Cms\ModelAdminEcommerceBaseClass;
+


 /**
@@ -35,8 +50,8 @@
     private static $menu_title = 'Second Hand';

     private static $managed_models = array(
-        'SecondHandProduct',
-        'SecondHandArchive'
+        SecondHandProduct::class,
+        SecondHandArchive::class
     );

     private static $allowed_actions = array(
@@ -74,7 +89,7 @@

     public function doCancel($data, $form)
     {
-        return $this->redirect(singleton('CMSMain')->Link());
+        return $this->redirect(singleton(CMSMain::class)->Link());
     }

     public function archive($request)
@@ -95,7 +110,7 @@
   * EXP: Check if this is the right implementation, this is highly speculative.
   * ### @@@@ STOP REPLACEMENT @@@@ ###
   */
-                if (is_a($secondHandProduct, SilverStripe\Core\Injector\Injector::inst()->getCustomClass('SiteTree'))) {
+                if (is_a($secondHandProduct, SilverStripe\Core\Injector\Injector::inst()->getCustomClass(SiteTree::class))) {
                     $secondHandProduct->write();
                     $secondHandProduct->doPublish();
                     $secondHandProduct->deleteFromStage('Live');
@@ -120,7 +135,7 @@
                 }
             }
         }
-        return new SS_HTTPResponse("ERROR!", 400);
+        return new HTTPResponse("ERROR!", 400);
     }

     public function restore($request)
@@ -128,13 +143,13 @@
         if (isset($_GET['productid'])) {
             $id = intval($_GET['productid']);
             if ($id) {
-                $restoredPage = Versioned::get_latest_version("SiteTree", $id);
+                $restoredPage = Versioned::get_latest_version(SiteTree::class, $id);
                 $parentID = $restoredPage->ParentID;
                 if ($parentID) {
                     var_dump($parentID);
                     $this->ensureParentHasVersion($parentID);
                     if (!$restoredPage) {
-                        return new SS_HTTPResponse("SiteTree #$id not found", 400);
+                        return new HTTPResponse("SiteTree #$id not found", 400);
                     }
                     $restoredPage = $restoredPage->doRestoreToStage();
                     //$restoredPage->doPublish();
@@ -149,11 +164,11 @@
                     $cmsEditLink = '/admin/secondhandproducts/SecondHandProduct/EditForm/field/SecondHandProduct/item/'.$id.'/edit';
                     return Controller::curr()->redirect($cmsEditLink);
                 } else {
-                    return new SS_HTTPResponse("Parent Page #$parentID is missing", 400);
+                    return new HTTPResponse("Parent Page #$parentID is missing", 400);
                 }
             }
         }
-        return new SS_HTTPResponse("ERROR!", 400);
+        return new HTTPResponse("ERROR!", 400);
     }

     /**
@@ -161,7 +176,7 @@
      */
     public function ensureParentHasVersion($parentID)
     {
-        $parentPage = Versioned::get_latest_version("SiteTree", $parentID);
+        $parentPage = Versioned::get_latest_version(SiteTree::class, $parentID);
         if (!$parentPage) {
             $parentPage = SiteTree::get()->byID($parentID);
             if ($parentPage) {

modified:	src/Cms/CMSPageAddController_SecondHandProducts.php
@@ -2,23 +2,42 @@

 namespace Sunnysideup\EcommerceSecondHandProduct\Cms;

-use CMSPageAddController;
-use DBField;
-use FieldList;
-use LiteralField;
-use DropdownField;
-use SecondHandProductGroup;
-use OptionsetField;
-use FormAction;
+
+
+
+
+
+
+
+
 use CMSForm;
-use SiteTree;
-use ProductGroup;
-use Member;
-use Security;
-use ValidationException;
-use Controller;
-use ArrayList;
-use ClassInfo;
+
+
+
+
+
+
+
+
+use SilverStripe\ORM\FieldType\DBField;
+use SilverStripe\Forms\LiteralField;
+use Sunnysideup\EcommerceSecondHandProduct\SecondHandProductGroup;
+use SilverStripe\Forms\DropdownField;
+use SilverStripe\Forms\OptionsetField;
+use SilverStripe\Forms\FieldList;
+use SilverStripe\Forms\FormAction;
+use SilverStripe\CMS\Model\SiteTree;
+use Sunnysideup\Ecommerce\Pages\ProductGroup;
+use SilverStripe\Security\Member;
+use SilverStripe\Security\Security;
+use SilverStripe\ORM\ValidationException;
+use SilverStripe\Control\Controller;
+use Sunnysideup\EcommerceSecondHandProduct\Cms\SecondHandProductAdmin;
+use SilverStripe\ORM\ArrayList;
+use Sunnysideup\EcommerceSecondHandProduct\SecondHandProduct;
+use SilverStripe\Core\ClassInfo;
+use SilverStripe\CMS\Controllers\CMSPageAddController;
+



@@ -228,7 +247,7 @@

     public function doCancel($data, $form)
     {
-        return $this->redirect(singleton('SecondHandProductAdmin')->Link());
+        return $this->redirect(singleton(SecondHandProductAdmin::class)->Link());
     }

     /**
@@ -247,7 +266,7 @@
   * EXP: Check if this is the right implementation, this is highly speculative.
   * ### @@@@ STOP REPLACEMENT @@@@ ###
   */
-        $productClass = SilverStripe\Core\Injector\Injector::inst()->getCustomClass('SecondHandProduct');
+        $productClass = SilverStripe\Core\Injector\Injector::inst()->getCustomClass(SecondHandProduct::class);
         $acceptedClasses = ClassInfo::subclassesFor($productClass);
         foreach ($pageTypes as $type) {
             if (in_array($type->ClassName, $acceptedClasses)) {

modified:	src/Tasks/EcommerceTaskCreateSecondHandProductManager.php
@@ -2,10 +2,16 @@

 namespace Sunnysideup\EcommerceSecondHandProduct\Tasks;

-use BuildTask;
-use Injector;
+
+
 use db;
-use EcommerceConfig;
+
+use SilverStripe\Core\Injector\Injector;
+use Sunnysideup\PermissionProvider\Api\PermissionProviderFactory;
+use Sunnysideup\EcommerceSecondHandProduct\SecondHandProduct;
+use Sunnysideup\Ecommerce\Config\EcommerceConfig;
+use SilverStripe\Dev\BuildTask;
+



@@ -25,17 +31,17 @@

     public function run($request)
     {
-        $permissionProviderFactory = Injector::inst()->get('PermissionProviderFactory');
+        $permissionProviderFactory = Injector::inst()->get(PermissionProviderFactory::class);
         db::alteration_message('========================== <br />creating second hand products sales manager', 'created');
-        $email = EcommerceConfig::get('SecondHandProduct', 'second_hand_admin_user_email');
+        $email = EcommerceConfig::get(SecondHandProduct::class, 'second_hand_admin_user_email');
         if (! $email) {
             $email = 'secondhandproducts@'.$_SERVER['HTTP_HOST'];
         }
-        $firstName = EcommerceConfig::get('SecondHandProduct', 'second_hand_admin_user_first_name');
+        $firstName = EcommerceConfig::get(SecondHandProduct::class, 'second_hand_admin_user_first_name');
         if (!$firstName) {
             $firstName = 'Second Hand';
         }
-        $surname = EcommerceConfig::get('SecondHandProduct', 'second_hand_admin_user_surname');
+        $surname = EcommerceConfig::get(SecondHandProduct::class, 'second_hand_admin_user_surname');
         if (!$surname) {
             $surname = 'Sales';
         }
@@ -48,11 +54,11 @@
         db::alteration_message('================================<br />creating shop admin group ', 'created');

         $permissionProviderFactory->CreateGroup(
-            $code = EcommerceConfig::get('SecondHandProduct', 'second_hand_admin_group_code'),
-            $name = EcommerceConfig::get('SecondHandProduct', 'second_hand_admin_group_name'),
+            $code = EcommerceConfig::get(SecondHandProduct::class, 'second_hand_admin_group_code'),
+            $name = EcommerceConfig::get(SecondHandProduct::class, 'second_hand_admin_group_name'),
             $parentGroup = null,
-            $permissionCode = EcommerceConfig::get('SecondHandProduct', 'second_hand_admin_permission_code'),
-            $roleTitle = EcommerceConfig::get('SecondHandProduct', 'second_hand_admin_role_title'),
+            $permissionCode = EcommerceConfig::get(SecondHandProduct::class, 'second_hand_admin_permission_code'),
+            $roleTitle = EcommerceConfig::get(SecondHandProduct::class, 'second_hand_admin_role_title'),
             $otherPermissionCodes = array(),
             $member
         );

modified:	src/Tasks/EcommerceTaskSecondHandPublishAll.php
@@ -2,10 +2,14 @@

 namespace Sunnysideup\EcommerceSecondHandProduct\Tasks;

-use BuildTask;
+
 use Environment;
-use SecondHandProduct;
-use DB;
+
+
+use Sunnysideup\EcommerceSecondHandProduct\SecondHandProduct;
+use SilverStripe\ORM\DB;
+use SilverStripe\Dev\BuildTask;
+


 class EcommerceTaskSecondHandPublishAll extends BuildTask

modified:	src/SecondHandProduct.php
@@ -2,32 +2,65 @@

 namespace Sunnysideup\EcommerceSecondHandProduct;

-use Product;
-use PermissionProvider;
-use Member;
-use Config;
-use Permission;
-use EcommerceConfig;
-use Versioned;
-use SecondHandArchive;
-use CheckboxField;
-use TextField;
-use DropdownField;
-use ReadonlyField;
-use DBField;
-use NumericField;
-use DateField;
-use UploadField;
-use TextareaField;
-use HeaderField;
-use GoogleAddressField;
-use Controller;
-use EcommerceCMSButtonField;
-use GridFieldConfig_RecordViewer;
-use GridField;
-use SS_Datetime;
-use Injector;
-use DB;
+
+
+
+
+
+
+
+
+
+
+
+
+
+
+
+
+
+
+
+
+
+
+
+
+
+
+use Sunnysideup\EcommerceSecondHandProduct\SecondHandProduct;
+use SilverStripe\Security\Member;
+use SilverStripe\Core\Config\Config;
+use Sunnysideup\Ecommerce\Config\EcommerceConfig;
+use SilverStripe\Security\Permission;
+use SilverStripe\Versioned\Versioned;
+use Sunnysideup\EcommerceSecondHandProduct\Model\SecondHandArchive;
+use SilverStripe\Forms\CheckboxField;
+use SilverStripe\Forms\TextField;
+use SilverStripe\Forms\DropdownField;
+use SilverStripe\ORM\FieldType\DBBoolean;
+use SilverStripe\ORM\FieldType\DBField;
+use SilverStripe\Forms\ReadonlyField;
+use SilverStripe\Forms\NumericField;
+use SilverStripe\Forms\DateField;
+use SilverStripe\Assets\Image;
+use SilverStripe\AssetAdmin\Forms\UploadField;
+use SilverStripe\Forms\TextareaField;
+use SilverStripe\Forms\HeaderField;
+use Sunnysideup\GoogleAddressField\GoogleAddressField;
+use SilverStripe\Control\Controller;
+use Sunnysideup\Ecommerce\Forms\Fields\EcommerceCMSButtonField;
+use SilverStripe\Forms\GridField\GridFieldConfig_RecordViewer;
+use SilverStripe\Forms\GridField\GridField;
+use Sunnysideup\EcommerceSecondHandProduct\Cms\SecondHandProductAdmin;
+use SilverStripe\ORM\FieldType\DBDatetime;
+use SilverStripe\Core\Injector\Injector;
+use Sunnysideup\PermissionProvider\Api\PermissionProviderFactory;
+use SilverStripe\ORM\DB;
+use SilverStripe\ORM\FieldType\DBDate;
+use Sunnysideup\Ecommerce\Pages\Product;
+use SilverStripe\Security\PermissionProvider;
+



@@ -93,7 +126,7 @@
     );

     private static $has_one = [
-        'BasedOn' => 'SecondHandProduct',
+        'BasedOn' => SecondHandProduct::class,
         'ArchivedBy' => Member::class,
     ];

@@ -184,7 +217,7 @@

     public function getSellerSummary()
     {
-        $list = Config::inst()->get('SecondHandProduct', 'seller_summary_detail_fields');
+        $list = Config::inst()->get(SecondHandProduct::class, 'seller_summary_detail_fields');
         $array = [];
         foreach ($list as $field) {
             if (trim($this->$field)) {
@@ -251,7 +284,7 @@
     public function canCreate($member = null, $context = [])
     {
         return Permission::check(
-            EcommerceConfig::get('SecondHandProduct', 'second_hand_admin_permission_code'),
+            EcommerceConfig::get(SecondHandProduct::class, 'second_hand_admin_permission_code'),
             'any',
             $member
         );
@@ -264,7 +297,7 @@
     public function canPublish($member = null)
     {
         return Permission::check(
-            EcommerceConfig::get('SecondHandProduct', 'second_hand_admin_permission_code'),
+            EcommerceConfig::get(SecondHandProduct::class, 'second_hand_admin_permission_code'),
             'any',
             $member
         );
@@ -277,7 +310,7 @@
     public function canEdit($member = null, $context = [])
     {
         return Permission::check(
-            EcommerceConfig::get('SecondHandProduct', 'second_hand_admin_permission_code'),
+            EcommerceConfig::get(SecondHandProduct::class, 'second_hand_admin_permission_code'),
             'any',
             $member
         );
@@ -290,7 +323,7 @@
     public function canDelete($member = null, $context = [])
     {
         return Permission::check(
-            EcommerceConfig::get('SecondHandProduct', 'second_hand_admin_permission_code'),
+            EcommerceConfig::get(SecondHandProduct::class, 'second_hand_admin_permission_code'),
             'any',
             $member
         );
@@ -353,7 +386,7 @@
         $fields->addFieldsToTab(
             'Root.Main',
             array(
-                ReadonlyField::create('CanBeSold', "For Sale", DBField::create_field('Boolean', $this->canPurchase())->Nice()),
+                ReadonlyField::create('CanBeSold', "For Sale", DBField::create_field(DBBoolean::class, $this->canPurchase())->Nice()),
                 ReadonlyField::create('CreatedNice', "First Entered", $this->getCreatedNice()),
                 TextField::create('InternalItemID', "Product Code"),

@@ -401,7 +434,7 @@
                 $contentField = TextField::create("ShortDescription", "Description"),
                 $boughtDate = DateField::create('DateItemWasBought', 'Date this item was bought'),
                 $soldDate = DateField::create('DateItemWasSold', 'Date this item was sold'),
-                $mainImageField = UploadField::create("Image", "Main Product Image"),
+                $mainImageField = UploadField::create(Image::class, "Main Product Image"),
                 $additionalImagesField = UploadField::create("AdditionalImages", "More Images"),
                 $metaFieldDesc = TextareaField::create("MetaDescription", 'Meta Description')
             )
@@ -482,7 +515,7 @@
             )
         );

-        if (class_exists('GoogleAddressField')) {
+        if (class_exists(GoogleAddressField::class)) {
             $mappingArray = $this->Config()->get('fields_to_google_geocode_conversion');
             if (is_array($mappingArray) && count($mappingArray)) {
                 $fields->addFieldToTab(
@@ -504,7 +537,7 @@
                 );
                 $geocodingField->setFieldMap($mappingArray);

-                $country_code = Config::inst()->get('SecondHandProduct', 'country_code');
+                $country_code = Config::inst()->get(SecondHandProduct::class, 'country_code');
                 if ($country_code) {
                     $geocodingField->setRestrictToCountryCode($country_code);
                 }
@@ -534,7 +567,7 @@
             )
         );
         if ($this->BasedOnID) {
-            $list = Config::inst()->get('SecondHandProduct', 'seller_summary_detail_fields');
+            $list = Config::inst()->get(SecondHandProduct::class, 'seller_summary_detail_fields');
             $labels = $this->FieldLabels();
             foreach ($list as $listField) {
                 $fields->replaceField(
@@ -593,7 +626,7 @@
     public function CMSEditLink()
     {
         return Controller::join_links(
-            singleton('SecondHandProductAdmin')->Link(),
+            singleton(SecondHandProductAdmin::class)->Link(),

 /**
   * ### @@@@ START REPLACEMENT @@@@ ###
@@ -634,7 +667,7 @@
         if ($this->HasBeenSold()) {
             return false;
         }
-        $embargoDays = Config::inst()->get('SecondHandProduct', 'embargo_number_of_days');
+        $embargoDays = Config::inst()->get(SecondHandProduct::class, 'embargo_number_of_days');
         if (intval($embargoDays) > 0) {
             if ($this->DateItemWasBought) {
                 $date = $this->DateItemWasBought;
@@ -666,13 +699,13 @@
         if ($this->BasedOnID) {
             $basedOn = $this->BasedOn();
             if ($basedOn && $basedOn->exists()) {
-                $list = Config::inst()->get('SecondHandProduct', 'seller_summary_detail_fields');
+                $list = Config::inst()->get(SecondHandProduct::class, 'seller_summary_detail_fields');
                 foreach ($list as $field) {
                     $this->$field = $basedOn->$field;
                 }
             }
         }
-        $list = Config::inst()->get('SecondHandProduct', 'seller_summary_detail_fields');
+        $list = Config::inst()->get(SecondHandProduct::class, 'seller_summary_detail_fields');

         //set the IternatlItemID if it doesn't already exist
         if (! $this->InternalItemID) {
@@ -688,7 +721,7 @@
         // Save the date when the product was sold.
         if ($this->HasBeenSold()) {
             if (! $this->DateItemWasSold) {
-                $this->DateItemWasSold = SS_Datetime::now()->Rfc2822();
+                $this->DateItemWasSold = DBDatetime::now()->Rfc2822();
             }
         }
         parent::onBeforeWrite();
@@ -706,10 +739,10 @@

     public function providePermissions()
     {
-        $perms[EcommerceConfig::get('SecondHandProduct', 'second_hand_admin_permission_code')] = array(
-            'name' => EcommerceConfig::get('SecondHandProduct', 'second_hand_admin_permission_title'),
+        $perms[EcommerceConfig::get(SecondHandProduct::class, 'second_hand_admin_permission_code')] = array(
+            'name' => EcommerceConfig::get(SecondHandProduct::class, 'second_hand_admin_permission_title'),
             'category' => 'E-commerce',
-            'help' => EcommerceConfig::get('SecondHandProduct', 'second_hand_admin_permission_help'),
+            'help' => EcommerceConfig::get(SecondHandProduct::class, 'second_hand_admin_permission_help'),
             'sort' => 250,
         );
         return $perms;
@@ -718,23 +751,23 @@
     public function requireDefaultRecords()
     {
         parent::requireDefaultRecords();
-        $permissionProviderFactory = Injector::inst()->get('PermissionProviderFactory');
+        $permissionProviderFactory = Injector::inst()->get(PermissionProviderFactory::class);
         $member = $permissionProviderFactory->CreateDefaultMember(
-            EcommerceConfig::get('SecondHandProduct', 'second_hand_admin_user_email'),
-            EcommerceConfig::get('SecondHandProduct', 'second_hand_admin_user_firstname'),
-            EcommerceConfig::get('SecondHandProduct', 'second_hand_admin_user_surname'),
-            EcommerceConfig::get('SecondHandProduct', 'second_hand_admin_user_password')
+            EcommerceConfig::get(SecondHandProduct::class, 'second_hand_admin_user_email'),
+            EcommerceConfig::get(SecondHandProduct::class, 'second_hand_admin_user_firstname'),
+            EcommerceConfig::get(SecondHandProduct::class, 'second_hand_admin_user_surname'),
+            EcommerceConfig::get(SecondHandProduct::class, 'second_hand_admin_user_password')
         );
         $permissionProviderFactory->CreateGroup(
-            $code = EcommerceConfig::get('SecondHandProduct', 'second_hand_admin_group_code'),
-            $name = EcommerceConfig::get('SecondHandProduct', 'second_hand_admin_group_name'),
+            $code = EcommerceConfig::get(SecondHandProduct::class, 'second_hand_admin_group_code'),
+            $name = EcommerceConfig::get(SecondHandProduct::class, 'second_hand_admin_group_name'),
             $parentGroup = null,
             $permissionCode = EcommerceConfig::get(
-                'SecondHandProduct',
+                SecondHandProduct::class,
                 'second_hand_admin_permission_code'
             ),
             $roleTitle = EcommerceConfig::get(
-                'SecondHandProduct',
+                SecondHandProduct::class,
                 'second_hand_admin_permission_title'
             ),
             $permissionArray = array(
@@ -765,7 +798,7 @@
     {
         parent::populateDefaults();
         if (! $this->DateItemWasBought) {
-            $this->DateItemWasBought = SS_Datetime::now()->Rfc2822();
+            $this->DateItemWasBought = DBDatetime::now()->Rfc2822();
         }
     }

@@ -777,7 +810,7 @@
         } else {
             $date = $this->Created;
         }
-        return $date.' = '.DBField::create_field('Date', $date)->Ago();
+        return $date.' = '.DBField::create_field(DBDate::class, $date)->Ago();
     }
 }


Warnings for src/SecondHandProduct.php:
 - src/SecondHandProduct.php:404 Renaming ambiguous string Image to SilverStripe\Assets\Image

modified:	src/SecondHandProduct_Controller.php
@@ -2,12 +2,19 @@

 namespace Sunnysideup\EcommerceSecondHandProduct;

-use ProductController;
-use Permission;
-use Security;
-use ArrayList;
-use DBField;
-use ArrayData;
+
+
+
+
+
+
+use SilverStripe\Security\Permission;
+use SilverStripe\Security\Security;
+use SilverStripe\ORM\ArrayList;
+use SilverStripe\ORM\FieldType\DBField;
+use SilverStripe\View\ArrayData;
+use Sunnysideup\Ecommerce\Pages\ProductController;
+


 class SecondHandProductController extends ProductController

modified:	src/Reports/SecondHandProductValue.php
@@ -2,19 +2,23 @@

 namespace Sunnysideup\EcommerceSecondHandProduct\Reports;

-use SS_Report;
+
 use Currency;
-use SecondHandProduct;
+
+use Sunnysideup\EcommerceSecondHandProduct\SecondHandProduct;
+use SilverStripe\Forms\GridField\GridFieldExportButton;
+use SilverStripe\Reports\Report;



-class SecondHandProductValue extends SS_Report
+
+class SecondHandProductValue extends Report
 {
     /**
      * The class of object being managed by this report.
      * Set by overriding in your subclass.
      */
-    protected $dataClass = 'SecondHandProduct';
+    protected $dataClass = SecondHandProduct::class;

     /**
      * @return string
@@ -86,7 +90,7 @@
     {
         $field = parent::getReportField();
         $config = $field->getConfig();
-        $exportButton = $config->getComponentByType('GridFieldExportButton');
+        $exportButton = $config->getComponentByType(GridFieldExportButton::class);
         $exportButton->setExportColumns($field->getColumns());

         return $field;

modified:	_config/config.yml
@@ -7,25 +7,18 @@
   - 'cms/*'
   - 'ecommerce/*'
 ---
-
-EcommerceDBConfig:
+Sunnysideup\Ecommerce\Model\Config\EcommerceDBConfig:
   extensions:
-    - SecondHandEcommerceConfigExtension
-
-SecondHandProductGroup:
-  base_buyable_class: SecondHandProduct
+    - Sunnysideup\EcommerceSecondHandProduct\Model\SecondHandEcommerceConfigExtension
+Sunnysideup\EcommerceSecondHandProduct\SecondHandProductGroup:
+  base_buyable_class: Sunnysideup\EcommerceSecondHandProduct\SecondHandProduct
   sort_options:
     default:
       Title: 'Default Order - (Most Recent)'
-      SQL: "\"Created\" DESC"
-
-EcommerceRole:
+      SQL: '"Created" DESC'
+Sunnysideup\Ecommerce\Model\Extensions\EcommerceRole:
   admin_role_permission_codes:
     - CMS_ACCESS_SecondHandProductAdmin
-
-
-
-
 ---
 Only:
   moduleexists: 'grouped-cms-menu'
@@ -33,5 +26,5 @@
 SilverStripe\Admin\LeftAndMain:
   menu_groups:
     Shop:
-      - SecondHandProductAdmin
+      - Sunnysideup\EcommerceSecondHandProduct\Cms\SecondHandProductAdmin


modified:	_config/routes.yml
@@ -7,9 +7,8 @@
   - 'cms/*'
   - 'ecommerce/*'
 ---
-
 SilverStripe\Control\Director:
   rules:
-    'secondhandproductlist//$Action/$ID/$OtherID': 'ExportSecondHandProducts'
-    'updatesecondhandproduct//$Action/$ID/$OtherID': 'UpdateSecondHandProduct'
+    secondhandproductlist//$Action/$ID/$OtherID: Sunnysideup\EcommerceSecondHandProduct\Control\ExportSecondHandProducts
+    updatesecondhandproduct//$Action/$ID/$OtherID: Sunnysideup\EcommerceSecondHandProduct\Control\UpdateSecondHandProduct


Writing changes for 22 files
✔✔✔