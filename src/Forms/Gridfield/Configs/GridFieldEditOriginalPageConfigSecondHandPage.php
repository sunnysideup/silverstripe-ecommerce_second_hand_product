<?php

namespace Sunnysideup\EcommerceSecondHandProduct\Forms\Gridfield\Configs;



use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use Sunnysideup\Ecommerce\Forms\Gridfield\GridFieldAddNewButtonOriginalPage;
use Sunnysideup\EcommerceSecondHandProduct\Forms\Gridfield\GridFieldAddNewButtonOriginalPageSecondHandProduct;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;


/**
 * @author nicolaas <github@sunnysideup.co.nz>
 */
class GridFieldEditOriginalPageConfigSecondHandPage extends GridFieldConfig_RecordEditor
{
    /**
     * @param int $itemsPerPage - How many items per page should show up
     */
    public function __construct($itemsPerPage = null)
    {
        parent::__construct($itemsPerPage);
        $this
            ->removeComponentsByType(GridFieldDeleteAction::class)
            ->removeComponentsByType(GridFieldAddNewButton::class)
            ->removeComponentsByType(GridFieldAddNewButtonOriginalPage::class)
            ->addComponent(new GridFieldAddNewButtonOriginalPageSecondHandProduct());
    }
}

