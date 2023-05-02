<?php

namespace Sunnysideup\EcommerceSecondHandProduct\Model;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TreeDropdownField;
use SilverStripe\ORM\DataExtension;

/**
 * <a href="$EcomConfig.SecondHandExplanationPage.Link">$EcomConfig.SecondHandExplanationPage.Title</a>.
 *
 * @property \Sunnysideup\Ecommerce\Model\Config\EcommerceDBConfig|\Sunnysideup\EcommerceSecondHandProduct\Model\SecondHandEcommerceConfigExtension $owner
 * @property int $SecondHandExplanationPageID
 * @method \SilverStripe\CMS\Model\SiteTree SecondHandExplanationPage()
 */
class SecondHandEcommerceConfigExtension extends DataExtension
{
    private static $has_one = [
        'SecondHandExplanationPage' => SiteTree::class,
    ];

    /**
     * Update Fields.
     */
    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldToTab(
            'Root.SecondHand',
            TreeDropdownField::create(
                'SecondHandExplanationPageID',
                'Second Hand Explanation Page',
                SiteTree::class
            )
        );
    }
}
