<?php

namespace Sunnysideup\EcommerceSecondHandProduct;

use ProductGroupController;
use Config;
use FieldList;
use TextField;
use FormAction;
use RequiredFields;
use Form;
use Convert;


class SecondHandProductGroupController extends ProductGroupController
{
    private static $allowed_actions = array(
        'SearchSecondHandProducts',
        'search'
    );


/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * OLD:     public function init() (ignore case)
  * NEW:     protected function init() (COMPLEX)
  * EXP: Controller init functions are now protected  please check that is a controller.
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
    protected function init()
    {
        Config::modify()->update(
            'ProductGroup',
            'base_buyable_class',
            'SecondHandProduct'
        );
        parent::init();
        $this->showFullList = true;
    }

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
            return $this->redirect($this->link('search').'?searchterm='.$data['searchterm']);
        }
    }

    public function search($request)
    {
        $term = Convert::raw2sql($request->param('searchterm'));
        //uncompleted
    }

    public function HasSearchFilterAndSort()
    {
        return true;
    }
}
