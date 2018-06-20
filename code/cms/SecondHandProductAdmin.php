<?php

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
        'SecondHandProduct',
        'SecondHandArchive'
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
                }
            }
        }
        return $form;
    }


    public function doCancel($data, $form)
    {
        return $this->redirect(singleton('CMSMain')->Link());
    }

    public function archive($request)
    {
        if (isset($_GET['productid'])) {
            $id = intval($_GET['productid']);
            if ($id) {
                $secondHandProduct = SecondHandProduct::get()->byID($id);
                $internalItemID = $secondHandProduct->InternalItemID;
                if (is_a($secondHandProduct, Object::getCustomClass('SiteTree'))) {
                    $secondHandProduct->deleteFromStage('Live');
                    $secondHandProduct->deleteFromStage('Stage');
                } else {
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
        return new SS_HTTPResponse("ERROR!", 400);
    }

    public function restore($request)
    {
        if (isset($_GET['productid'])) {
            $id = intval($_GET['productid']);
            if ($id) {
                $restoredPage = Versioned::get_latest_version("SiteTree", $id);
                if (!$restoredPage) {
                    return new SS_HTTPResponse("SiteTree #$id not found", 400);
                }
                $restoredPage = $restoredPage->doRestoreToStage();
                $restoredPage->doPublish();
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
            }
        }
        return new SS_HTTPResponse("ERROR!", 400);
    }
}
