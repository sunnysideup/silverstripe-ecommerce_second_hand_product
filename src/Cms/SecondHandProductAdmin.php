<?php

namespace Sunnysideup\EcommerceSecondHandProduct\Cms;














use Sunnysideup\EcommerceSecondHandProduct\SecondHandProduct;
use Sunnysideup\EcommerceSecondHandProduct\Model\SecondHandArchive;
use Sunnysideup\GoogleAddressField\GoogleAddressField;
use SilverStripe\View\Requirements;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Forms\GridField\GridField;
use Sunnysideup\EcommerceSecondHandProduct\Forms\Gridfield\Configs\GridFieldEditOriginalPageConfigSecondHandPage;
use SilverStripe\Forms\GridField\GridFieldExportButton;
use SilverStripe\CMS\Controllers\CMSMain;
use SilverStripe\Security\Member;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Versioned\Versioned;
use Sunnysideup\Ecommerce\Cms\ModelAdminEcommerceBaseClass;



/**
 * @description: for the management of Product and Product Groups only
 *
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: cms
 **/

class SecondHandProductAdmin extends ModelAdminEcommerceBaseClass
{
    private static $menu_priority = 3.2;

    private static $url_segment = 'secondhandproducts';

    private static $menu_title = 'Second Hand';

    private static $managed_models = array(
        SecondHandProduct::class,
        SecondHandArchive::class
    );

    private static $allowed_actions = array(
        "editinsitetree",
        "ItemEditForm",
        "archive" => true,
        "restore" => true,
    );

    /**
     * standard SS variable
     * @var String
     */
    private static $menu_icon = "ecommerce/images/icons/product-file.gif";


    public function getEditForm($id = null, $fields = null)
    {
        foreach (GoogleAddressField::js_requirements() as $jsFile) {
            Requirements::javascript($jsFile);
        }
        $form = parent::getEditForm();
        if (singleton($this->modelClass) instanceof SiteTree) {
            if ($gridField = $form->Fields()->dataFieldByName($this->sanitiseClassName($this->modelClass))) {
                if ($gridField instanceof GridField) {
                    $gridField->setConfig(GridFieldEditOriginalPageConfigSecondHandPage::create()); 
                    $gridField->getConfig()->addComponent($exportButton = new GridFieldExportButton('buttons-before-left'));
                    $exportButton->setExportColumns(singleton($this->modelClass)->exportFields());
                }
            }
        }
        return $form;
    }


    public function doCancel($data, $form)
    {
        return $this->redirect(singleton(CMSMain::class)->Link());
    }

    public function archive($request)
    {
        if (isset($_GET['productid'])) {
            $id = intval($_GET['productid']);
            if ($id) {
                $secondHandProduct = SecondHandProduct::get()->byID($id);
                $currentMember = Member::currentUser();
                $secondHandProduct->ArchivedByID = $currentMember->ID;
                $internalItemID = $secondHandProduct->InternalItemID;

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD:  Object:: (case sensitive)
  * NEW:  SilverStripe\\Core\\Injector\\Injector::inst()-> (COMPLEX)
  * EXP: Check if this is the right implementation, this is highly speculative.
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
                if (is_a($secondHandProduct, SilverStripe\Core\Injector\Injector::inst()->getCustomClass(SiteTree::class))) {
                    $secondHandProduct->write();
                    $secondHandProduct->publishRecursive();
                    $secondHandProduct->deleteFromStage('Live');
                    $secondHandProduct->deleteFromStage('Stage');
                } else if($secondHandProduct) {
                    $secondHandProduct->write();
                    $secondHandProduct->delete();
                }
                //after deleting the product redirect to the archived page
                $archivedProduct = SecondHandArchive::get()->filter(['InternalItemID' => $internalItemID])->first();
                if ($archivedProduct) {
                    $this->getResponse()->addHeader(
                        'X-Status',
                        rawurlencode(_t(
                            'CMSMain.RESTORED',
                            "Archived '{title}' successfully",
                            array('title' => $archivedProduct->Title)
                        ))
                    );
                    $cmsEditLink = '/admin/secondhandproducts/SecondHandArchive/EditForm/field/SecondHandArchive/item/'.$archivedProduct->ID.'/edit';
                    return Controller::curr()->redirect($cmsEditLink);
                }
            }
        }
        return new HTTPResponse("ERROR!", 400);
    }

    public function restore($request)
    {
        if (isset($_GET['productid'])) {
            $id = intval($_GET['productid']);
            if ($id) {
                $restoredPage = Versioned::get_latest_version(SiteTree::class, $id);
                $parentID = $restoredPage->ParentID;
                if ($parentID) {
                    var_dump($parentID);
                    $this->ensureParentHasVersion($parentID);
                    if (!$restoredPage) {
                        return new HTTPResponse("SiteTree #$id not found", 400);
                    }
                    $restoredPage = $restoredPage->doRestoreToStage();
                    //$restoredPage->doPublish();
                    $this->getResponse()->addHeader(
                        'X-Status',
                        rawurlencode(_t(
                            'CMSMain.RESTORED',
                            "Restored '{title}' successfully",
                            array('title' => $restoredPage->Title)
                        ))
                    );
                    $cmsEditLink = '/admin/secondhandproducts/SecondHandProduct/EditForm/field/SecondHandProduct/item/'.$id.'/edit';
                    return Controller::curr()->redirect($cmsEditLink);
                } else {
                    return new HTTPResponse("Parent Page #$parentID is missing", 400);
                }
            }
        }
        return new HTTPResponse("ERROR!", 400);
    }

    /**
     * little hack to fix parent if it is not versioned into versions table
     */
    public function ensureParentHasVersion($parentID)
    {
        $parentPage = Versioned::get_latest_version(SiteTree::class, $parentID);
        if (!$parentPage) {
            $parentPage = SiteTree::get()->byID($parentID);
            if ($parentPage) {
                $parentPage->writeToStage('Stage');
                $parentPage->publish('Stage', 'Live', true);
            }
        }
    }
}

