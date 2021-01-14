<?php

namespace Sunnysideup\EcommerceSecondHandProduct;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\ORM\DataObject;
use Sunnysideup\Ecommerce\Pages\ProductGroup;

class SecondHandProductGroup extends ProductGroup
{
    private static $table_name = 'SecondHandProductGroup';

    private static $db = [
        'RootParent' => 'Boolean',
    ];

    private static $allowed_children = [
        SecondHandProductGroup::class,
        SecondHandProduct::class,
    ];

    private static $icon = 'sunnysideup/ecommerce_second_hand_product: client/images/treeicons/SecondHandProductGroup-file.gif';

    /**
     * Standard SS variable.
     */
    private static $singular_name = 'Second Hand Product Holder';

    /**
     * Standard SS variable.
     */
    private static $plural_name = 'Second Hand Product Holders';

    /**
     * Standard SS variable.
     *
     * @var string
     */
    private static $description = 'A product category page specifically for second had products';

    public function i18n_singular_name()
    {
        return self::$singular_name;
    }

    public function i18n_plural_name()
    {
        return self::$plural_name;
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->addFieldToTab(
            'Root.SecondHand',
            ReadonlyField::create(
                'RootParentNice',
                _t('SecondHandProductGroup.LANDING_PAGE', 'Landing Page')
            )->setValue($this->dbObject('RootParent')->Nice())
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
     * @return SecondHandProductGroup
     */
    public function BestRootParentPage()
    {
        $obj = DataObject::get_one(
            SecondHandProductGroup::class,
            ['RootParent' => 1]
        );
        if ($obj) {
            return $obj;
        }
        return SecondHandProductGroup::get()->first();
    }

    /**
     * Level is within SiteTree hierarchy
     * @return bool
     */
    protected function hasOtherSecondHandProductGroupsOnThisLevel()
    {
        if (! $this->ParentID) {
            $this->ParentID = 0;
        }
        return SecondHandProductGroup::get()
            ->filter(['ParentID' => $this->ParentID])
            ->exclude(['ID' => $this->ID])
            ->count() > 0 ? true : false;
    }

    /**
     * Returns the class we are working with.
     *
     * @return string
     */
    public function getBuyableClassName() : string
    {
        return SecondHandProduct::class;
    }
}
