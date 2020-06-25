<?php

namespace Sunnysideup\EcommerceSecondHandProduct\Forms\Gridfield;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\View\ArrayData;
use Sunnysideup\Ecommerce\Forms\Gridfield\GridFieldAddNewButtonOriginalPage;
use Sunnysideup\EcommerceSecondHandProduct\Cms\CMSPageAddController_SecondHandProducts;
use Sunnysideup\EcommerceSecondHandProduct\SecondHandProductGroup;

/**
 * Provides the entry point to editing a single record presented by the
 * {@link GridField}.
 *
 * Doesn't show an edit view on its own or modifies the record, but rather
 * relies on routing conventions established in {@link getColumnContent()}.
 *
 * The default routing applies to the {@link GridFieldDetailForm} component,
 * which has to be added separately to the {@link GridField} configuration.
 */
class GridFieldAddNewButtonOriginalPageSecondHandProduct extends GridFieldAddNewButtonOriginalPage
{
    public function getHTMLFragments($gridField)
    {
        $singleton = singleton($gridField->getModelClass());

        if (! $singleton->canCreate()) {
            return [];
        }

        if (! $this->buttonName) {
            // provide a default button name, can be changed by calling {@link setButtonName()} on this component
            $objectName = $singleton->i18n_singular_name();
            $this->buttonName = _t('GridField.Add_USING_PAGES_SECTION', 'Add {name}', ['name' => $objectName]);
        }

        $getSegment = '';
        if ($page = $this->BestRootParentPage()) {
            $getSegment = '?ParentID=' . $page->ID;
        }

        $data = new ArrayData([
            'NewLink' => '/admin/' . Config::inst()->get(CMSPageAddController_SecondHandProducts::class, 'url_segment') . '/' . $getSegment,
            'ButtonName' => $this->buttonName,
        ]);

        return [

            /**
             * ### @@@@ START REPLACEMENT @@@@ ###
             * WHY: automated upgrade
             * OLD: ->RenderWith( (ignore case)
             * NEW: ->RenderWith( (COMPLEX)
             * EXP: Check that the template location is still valid!
             * ### @@@@ STOP REPLACEMENT @@@@ ###
             */
            $this->targetFragment => $data->RenderWith('GridFieldAddNewbutton'),
        ];
    }

    /**
     * finds the most likely root parent for the shop.
     *
     * @return SiteTree | NULL
     */
    public function BestRootParentPage()
    {
        $singleton = Injector::inst()->get(SecondHandProductGroup::class);

        return $singleton->BestRootParentPage();
    }
}
