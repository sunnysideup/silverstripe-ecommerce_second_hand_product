<?php

namespace Sunnysideup\EcommerceSecondHandProduct\Model;

use SilverStripe\Core\Extension;
use Sunnysideup\Ecommerce\Model\Config\EcommerceDBConfig;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TreeDropdownField;

/**
 * <a href="$EcomConfig.SecondHandExplanationPage.Link">$EcomConfig.SecondHandExplanationPage.Title</a>.
 *
 * @property EcommerceDBConfig|SecondHandEcommerceConfigExtension $owner
 * @property int $SecondHandExplanationPageID
 * @method SiteTree SecondHandExplanationPage()
 */
class SecondHandEcommerceConfigExtension extends Extension
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
