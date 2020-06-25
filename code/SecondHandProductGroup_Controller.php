<?php

class SecondHandProductGroup_Controller extends ProductGroup_Controller
{
    private static $allowed_actions = array(
        'SearchSecondHandProducts',
        'search'
    );

    public function init()
    {
        Config::inst()->update(
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
