<?php

/**
 * @description: for the management of Product and Product Groups only
 *
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: cms
 **/

class SecondHandProductAdmin extends ModelAdminEcommerceBaseClass {

    private static $menu_priority = 3.2;

    private static $url_segment = 'secondhandproducts';

    private static $menu_title = 'Second Hand';

    private static $managed_models = array(
        'SecondHandProduct',
        'SecondHandArchive'
    );

    private static $allowed_actions = array(
        "editinsitetree",
        "ItemEditForm"
    );

    /**
     * standard SS variable
     * @var String
     */
    private static $menu_icon = "ecommerce/images/icons/product-file.gif";


    function getEditForm($id = null, $fields = null){
        foreach(GoogleAddressField::js_requirements() as $jsFile) {
            Requirements::javascript($jsFile);
        }
        $form = parent::getEditForm();
        if(singleton($this->modelClass) instanceof SiteTree) {
            if($gridField = $form->Fields()->dataFieldByName($this->sanitiseClassName($this->modelClass))) {
                if($gridField instanceof GridField) {
                    $gridField->setConfig(GridFieldEditOriginalPageConfigSecondHandPage::create());
                }
            }
        }
        return $form;
    }


    public function doCancel($data, $form) {
        return $this->redirect(singleton('CMSMain')->Link());
    }

}
