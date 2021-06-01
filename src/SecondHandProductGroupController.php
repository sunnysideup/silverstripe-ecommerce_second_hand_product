<?php

namespace Sunnysideup\EcommerceSecondHandProduct;

use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Forms\TextField;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Pages\ProductGroup;
use Sunnysideup\Ecommerce\Pages\ProductGroupController;

class SecondHandProductGroupController extends ProductGroupController
{
    private static $allowed_actions = [
        'SearchSecondHandProducts',
        'search',
    ];

    public function SearchSecondHandProducts()
    {
        $fields = new FieldList(
            new TextField('searchterm', 'Keyword', isset($_GET['searchterm']) ? $_GET['searchterm'] : '')
        );
        $actions = new FieldList(
            new FormAction('doSearchSecondHandProducts', 'Search')
        );
        $validator = new RequiredFields('searchterm');
        $form = Form::create($this, 'SearchSecondHandProducts', $fields, $actions, $validator);
        $form->setFormMethod('GET');
        $form->disableSecurityToken();

        return $form;
    }

    public function doSearchSecondHandProducts($data, $form)
    {
        $page = SecondHandProductGroup::get()->first();
        if ($page) {
            return $this->redirect($this->link('search') . '?searchterm=' . $data['searchterm']);
        }
    }

    public function search($request)
    {
        //uncompleted
    }

    protected function init()
    {
        Config::modify()->update(
            ProductGroup::class,
            'base_buyable_class',
            SecondHandProduct::class
        );
        parent::init();
        $this->showFullList = true;
    }

    /**
     * Do we show all products on one page?
     *
     * @return bool
     */
    public function IsShowFullList() : bool
    {
        return true;
    }

    protected function MaxNumberOfProductsPerPageAbsolute(): int
    {
        return EcommerceConfig::get(SecondHandProductGroup::class, 'maximum_number_of_products_to_list') + 1;
    }
}
