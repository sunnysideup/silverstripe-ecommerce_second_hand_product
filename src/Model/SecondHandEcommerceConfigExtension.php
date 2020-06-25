<?php

namespace Sunnysideup\EcommerceSecondHandProduct\Model;




use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TreeDropdownField;
use SilverStripe\ORM\DataExtension;




/**
 *
 * <a href="$EcomConfig.SecondHandExplanationPage.Link">$EcomConfig.SecondHandExplanationPage.Title</a>
 *
 *
 */

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD:  extends DataExtension (ignore case)
  * NEW:  extends DataExtension (COMPLEX)
  * EXP: Check for use of $this->anyVar and replace with $this->anyVar[$this->owner->ID] or consider turning the class into a trait
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
class SecondHandEcommerceConfigExtension extends DataExtension
{
    private static $has_one = array(
        "SecondHandExplanationPage" => SiteTree::class
    );

    /**
     * Update Fields
     * @return FieldList
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
        return $fields;
    }
}

