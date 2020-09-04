<?php

namespace Sunnysideup\EcommerceSecondHandProduct\Cms;

use SilverStripe\Admin\ModelAdmin;
use SilverStripe\CMS\Controllers\CMSMain;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldExportButton;
use SilverStripe\Security\Member;
use SilverStripe\Versioned\Versioned;
use SilverStripe\View\Requirements;
use Sunnysideup\Ecommerce\Api\ClassHelpers;
use Sunnysideup\Ecommerce\Cms\EcommerceModelAdminTrait;
use Sunnysideup\Ecommerce\Config\EcommerceConfigClassNames;
use Sunnysideup\EcommerceSecondHandProduct\Forms\Gridfield\Configs\GridFieldEditOriginalPageConfigSecondHandPage;
use Sunnysideup\EcommerceSecondHandProduct\Model\SecondHandArchive;
use Sunnysideup\EcommerceSecondHandProduct\SecondHandProduct;
use Sunnysideup\GoogleAddressField\GoogleAddressField;

/**
 * @description: for the management of Product and Product Groups only
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: cms
 **/

class SecondHandProductAdmin extends ModelAdmin
{
    use EcommerceModelAdminTrait;

    private static $menu_priority = 3.2;

    private static $url_segment = 'secondhandproducts';

    private static $menu_title = 'Second Hand';

    private static $managed_models = [
        SecondHandProduct::class,
        SecondHandArchive::class,
    ];

    private static $allowed_actions = [
        'editinsitetree',
        'ItemEditForm',
        'archive' => true,
        'restore' => true,
    ];

    /**
     * standard SS variable
     * @var string
     */
    private static $menu_icon = 'vendor/sunnysideup/ecommerce/client/images/icons/product-file.gif';

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
                if (is_a($secondHandProduct, EcommerceConfigClassNames::getName(SiteTree::class))) {
                    $secondHandProduct->write();
                    $secondHandProduct->publishRecursive();
                    $secondHandProduct->deleteFromStage('Live');
                    $secondHandProduct->deleteFromStage('Stage');
                } elseif ($secondHandProduct) {
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
                            ['title' => $archivedProduct->Title]
                        ))
                    );
                    $classURLSegment = ClassHelpers::sanitise_class_name(SecondHandArchive::class);
                    $cmsEditLink = '/admin/secondhandproducts/' . $classURLSegment . '/EditForm/field/' . $classURLSegment . '/item/' . $archivedProduct->ID . '/edit';
                    return Controller::curr()->redirect($cmsEditLink);
                }
            }
        }
        return new HTTPResponse('ERROR!', 400);
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
                    if (! $restoredPage) {
                        return new HTTPResponse("SiteTree #${id} not found", 400);
                    }
                    $restoredPage = $restoredPage->doRestoreToStage();
                    //$restoredPage->doPublish();
                    $this->getResponse()->addHeader(
                        'X-Status',
                        rawurlencode(_t(
                            'CMSMain.RESTORED',
                            "Restored '{title}' successfully",
                            ['title' => $restoredPage->Title]
                        ))
                    );
                    $cmsEditLink = '/admin/secondhandproducts/SecondHandProduct/EditForm/field/SecondHandProduct/item/' . $id . '/edit';
                    return Controller::curr()->redirect($cmsEditLink);
                }
                return new HTTPResponse("Parent Page #${parentID} is missing", 400);
            }
        }
        return new HTTPResponse('ERROR!', 400);
    }

    /**
     * little hack to fix parent if it is not versioned into versions table
     */
    public function ensureParentHasVersion($parentID)
    {
        $parentPage = Versioned::get_latest_version(SiteTree::class, $parentID);
        if (! $parentPage) {
            $parentPage = SiteTree::get()->byID($parentID);
            if ($parentPage) {
                $parentPage->writeToStage('Stage');
                $parentPage->publish('Stage', 'Live', true);
            }
        }
    }
}
