<?php

namespace Sunnysideup\EcommerceSecondHandProduct\Cms;

use SilverStripe\Admin\ModelAdmin;
use SilverStripe\CMS\Controllers\CMSMain;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldExportButton;
use SilverStripe\View\Requirements;
use Sunnysideup\Ecommerce\Traits\EcommerceModelAdminTrait;
use Sunnysideup\EcommerceSecondHandProduct\Api\SecondHandProductActions;
use Sunnysideup\EcommerceSecondHandProduct\Forms\Gridfield\Configs\GridFieldEditOriginalPageConfigSecondHandPage;
use Sunnysideup\EcommerceSecondHandProduct\Model\SecondHandArchive;
use Sunnysideup\EcommerceSecondHandProduct\Model\SecondHandForSaleList;
use Sunnysideup\EcommerceSecondHandProduct\SecondHandProduct;
use Sunnysideup\EcommerceSecondHandProduct\SecondHandProductGroup;
use Sunnysideup\GoogleAddressField\GoogleAddressField;

/**
 * Class \Sunnysideup\EcommerceSecondHandProduct\Cms\SecondHandProductAdmin
 *
 */
class SecondHandProductAdmin extends ModelAdmin
{
    use EcommerceModelAdminTrait;

    private static $menu_priority = 3.2;

    private static $url_segment = 'secondhandproducts';

    private static $menu_title = 'Second Hand';

    private static $managed_models = [
        SecondHandProduct::class,
        SecondHandProductGroup::class,
        SecondHandArchive::class,
        SecondHandForSaleList::class,
    ];

    private static $allowed_actions = [
        'editinsitetree',
        'ItemEditForm',
        'archive' => true,
        'restore' => true,
    ];

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $menu_icon = 'vendor/sunnysideup/ecommerce/client/images/icons/product-file.gif';

    public function getEditForm($id = null, $fields = null)
    {
        foreach (GoogleAddressField::js_requirements() as $jsFile) {
            Requirements::javascript($jsFile);
        }

        $form = parent::getEditForm();
        if (singleton($this->modelClass) instanceof SecondHandProduct) {
            $gridField = $form->Fields()->dataFieldByName($this->sanitiseClassName($this->modelClass));
            if ($gridField) {
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
            $id = (int) $_GET['productid'];
            if ($id) {
                $archivedProduct = SecondHandProductActions::archive($id);
                //after deleting the product redirect to the archived page
                if ($archivedProduct) {
                    $this->getResponse()->addHeader(
                        'X-Status',
                        rawurlencode(_t(
                            'CMSMain.RESTORED',
                            "Archived '{title}' successfully",
                            ['title' => $archivedProduct->Title]
                        ))
                    );
                    return Controller::curr()->redirect($archivedProduct->CMSEditLink());
                }
            }
        }

        return new HTTPResponse('ERROR!', 400);
    }

    public function restore($request)
    {
        if (isset($_GET['productid'])) {
            $id = (int) $_GET['productid'];
            if ($id) {
                $restoredPage = SecondHandProductActions::restore($id);
                if ($restoredPage) {
                    $this->getResponse()->addHeader(
                        'X-Status',
                        rawurlencode(_t(
                            'CMSMain.RESTORED',
                            "Restored '{title}' successfully",
                            ['title' => $restoredPage->Title]
                        ))
                    );
                    $cmsEditLink = $restoredPage->CMSEditLink();

                    return Controller::curr()->redirect($cmsEditLink);
                }

                return new HTTPResponse("Parent Page #{$id} is missing", 400);
            }
        }

        return new HTTPResponse('ERROR!', 400);
    }
}
