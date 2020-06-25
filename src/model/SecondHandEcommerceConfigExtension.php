<?php


/**
 *
 * <a href="$EcomConfig.SecondHandExplanationPage.Link">$EcomConfig.SecondHandExplanationPage.Title</a>
 *
 *
 */
class SecondHandEcommerceConfigExtension extends DataExtension
{
    private static $has_one = array(
        "SecondHandExplanationPage" => 'SiteTree'
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
                'SiteTree'
            )
        );
        return $fields;
    }
}

