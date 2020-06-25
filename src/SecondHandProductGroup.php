<?php

namespace Sunnysideup\EcommerceSecondHandProduct;





use Sunnysideup\EcommerceSecondHandProduct\SecondHandProductGroup;
use Sunnysideup\EcommerceSecondHandProduct\SecondHandProduct;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\ORM\DataObject;
use Sunnysideup\Ecommerce\Pages\ProductGroup;




class SecondHandProductGroup extends ProductGroup
{

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * OLD: private static $db (case sensitive)
  * NEW: 
    private static $table_name = '[SEARCH_REPLACE_CLASS_NAME_GOES_HERE]';

    private static $db (COMPLEX)
  * EXP: Check that is class indeed extends DataObject and that it is not a data-extension!
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
    
    private static $table_name = 'SecondHandProductGroup';

    private static $db = array(
        'RootParent' => 'Boolean'
    );

    private static $allowed_children = array(
        SecondHandProductGroup::class,
        SecondHandProduct::class
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

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->addFieldToTab(
            'Root.SecondHand',
            CheckboxField::create(
                'RootParent',
                _t('SecondHandProductGroup.LANDING_PAGE', 'Landing Page')
            )
        );
        return $fields;
    }

    /**
     * Event handler called before writing to the database.
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        if ($this->ParentID) {
            $parent = SiteTree::get()->byID($this->ParentID);
            if ($parent) {
                if ($parent instanceof SecondHandProductGroup) {
                    $this->RootParent = false;
                } else {
                    if (! $this->hasOtherSecondHandProductGroupsOnThisLevel()) {
                        $this->RootParent = true;
                    }
                }
            }
        } else {
            if (! $this->hasOtherSecondHandProductGroupsOnThisLevel()) {
                $this->RootParent = true;
            }
        }
    }

    /**
     * Level is within SiteTree hierarchy
     * @return boolean
     */
    protected function hasOtherSecondHandProductGroupsOnThisLevel()
    {
        if (!$this->ParentID) {
            $this->ParentID = 0;
        }
        return SecondHandProductGroup::get()
            ->filter(array('ParentID' => $this->ParentID))
            ->exclude(array('ID' => $this->ID))
            ->count() > 0 ? true : false;
    }

    /**
     * @return SecondHandProductGroup
     */
    public function BestRootParentPage()
    {
        $obj = DataObject::get_one(
            SecondHandProductGroup::class,
            array('RootParent' => 1)
        );
        if ($obj) {
            return $obj;
        } else {
            return SecondHandProductGroup::get()->first();
        }
    }

    /**
     * Returns the class we are working with.
     *
     * @return string
     */
    protected function getBuyableClassName()
    {
        return SecondHandProduct::class;
    }
}

