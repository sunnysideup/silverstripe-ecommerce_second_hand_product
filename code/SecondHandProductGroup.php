<?php


class SecondHandProductGroup extends ProductGroup
{

    private static $allowed_children = array(
        'SecondHandProductGroup',
        'SecondHandProduct'
    );

    private static $icon = 'ecommerce_second_hand_product/images/treeicons/SecondHandProductGroup';

    /**
     * Standard SS variable.
     */
    private static $singular_name = 'Second Hand Product Holder';
    public function i18n_singular_name()
    {
        return self::$singular_name;
    }

    /**
     * Standard SS variable.
     */
    private static $plural_name = 'Second Hand Product Holders';
    public function i18n_plural_name()
    {
        return self::$plural_name;
    }

    /**
     * Standard SS variable.
     *
     * @var string
     */
    private static $description = 'A product category page specifically for second had products';

}

class SecondHandProductGroup_Controller extends ProductGroup_Controller
{

    private static $allowed_actions = array(
        'SearchSecondHandProducts',
        'search'
    );

    function init()
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
        if($page) {
            return $this->redirect($this->link('search').'?searchterm='.$data['searchterm']);
        }
    }

    function search($request)
    {
        $term = Convert::raw2sql($request->param('searchterm'));

    }

    function HasSearchFilterAndSort()
    {
        return true;
    }

}
