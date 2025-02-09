<?php

namespace Sunnysideup\EcommerceSecondHandProduct;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\ReadonlyField;
use Sunnysideup\Ecommerce\Pages\ProductGroup;

/**
 * Class \Sunnysideup\EcommerceSecondHandProduct\SecondHandProductGroup
 *
 * @property bool $RootParent
 */
class SecondHandProductGroup extends ProductGroup
{
    /**
     * @var string
     */
    protected static $groups_to_show_first = ProductGroup::class;

    protected static $main_second_hand_page_cache;

    protected static $list_of_filters = [];

    protected static $_page_cache_ids = [];

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

    private static $maximum_number_of_products_to_list = 1000;

    public static function main_second_hand_page()
    {
        if (! isset(self::$main_second_hand_page_cache)) {
            self::$main_second_hand_page_cache = SecondHandProductGroup::get()->first()?->TopParentGroup();
            if (! self::$main_second_hand_page_cache) {
                self::$main_second_hand_page_cache = Injector::inst()->get(SecondHandProductGroup::class)->BestRootParentPage();
            }
        }

        return self::$main_second_hand_page_cache;
    }

    public static function main_second_hand_page_id(): int
    {
        $page = self::main_second_hand_page();

        return $page ? $page->ID : 0;
    }

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
     * @return SecondHandProductGroup
     */
    public function BestRootParentPage()
    {
        /** @var SecondHandProductGroup $obj */
        $obj = SecondHandProductGroup::get_one(
            SecondHandProductGroup::class,
            ['RootParent' => 1]
        );
        if (! $obj) {
            /** @var SecondHandProductGroup $obj */
            $obj = SecondHandProductGroup::get()->first();
        }
        // @return SecondHandProductGroup
        return $obj;
    }

    /**
     * Returns the class we are working with.
     */
    public function getBuyableClassName(): string
    {
        return SecondHandProduct::class;
    }

    /**
     * Event handler called before writing to the database.
     */
    protected function onBeforeWrite()
    {
        parent::onBeforeWrite();
        $this->RootParent = false;
        if ($this->ParentID) {
            $parent = SiteTree::get_by_id($this->ParentID);
            if ($parent) {
                if ($parent instanceof SecondHandProductGroup) {
                    $this->RootParent = false;
                } elseif (! $this->hasOtherSecondHandProductGroupsOnThisLevel()) {
                    $this->RootParent = true;
                }
            }
        } elseif (! $this->hasOtherSecondHandProductGroupsOnThisLevel()) {
            $this->RootParent = true;
        }
    }

    protected function hasOtherSecondHandProductGroupsOnThisLevel(): bool
    {
        return SecondHandProductGroup::get()
            ->filter(['ParentID' => $this->ParentID ?: 0])
            ->exclude(['ID' => $this->ID])
            ->exists()
        ;
    }
}
