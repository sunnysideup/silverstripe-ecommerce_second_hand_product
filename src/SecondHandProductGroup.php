<?php

namespace Sunnysideup\EcommerceSecondHandProduct;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\ORM\DataObject;
use Sunnysideup\Ecommerce\Pages\ProductGroup;

class SecondHandProductGroup extends ProductGroup
{
    protected static $main_second_hand_page_cache = null;

    protected static $list_of_filters = [];

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

    public static function main_second_hand_page()
    {
        if (! isset(self::$main_second_hand_page_cache)) {
            self::$main_second_hand_page_cache = SecondHandProductGroup::get()->first()->TopParentGroup();
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
     * Returns the class we are working with.
     *
     * @return string
     */
    public function getBuyableClassName(): string
    {
        return SecondHandProduct::class;
    }

    /**
     * @return GroupedList|null
     */
    public function ListOfFilters()
    {
        if (! isset(self::$list_of_filters[$this->ID])) {
            self::$list_of_filters[$this->ID] = null;
            $mainSecondHandPageID = self::main_second_hand_page_id();
            if ($this->ID === $mainSecondHandPageID) {
                $stage = $this->getStage();
                self::$list_of_filters = [];
                $productIDs = $this->getProductsThatCanBePurchasedArray();
                if (! $productIDs || ! count($productIDs)) {
                    $productIDs = [0 => 0];
                }
                $sql = '
                    SELECT "ProductGroupID"
                    FROM "Product_ProductGroups"
                        INNER JOIN "Product' . $stage . '"
                            ON "Product' . $stage . '"."ID" = "Product_ProductGroups"."ProductID"
                        INNER JOIN SecondHandProduct' . $stage . '
                            ON SecondHandProduct' . $stage . '.ID = "Product_ProductGroups"."ProductID"
                        INNER JOIN "SiteTree' . $stage . '"
                            ON "SiteTree' . $stage . '"."ID" = "Product_ProductGroups"."ProductID"
                    WHERE
                        "ProductID" IN (' . implode(',', $productIDs) . ') AND
                        "AllowPurchase" = 1 AND
                        ' . $this->getThresholdSQL() . '
                    GROUP BY ProductGroupID;
                ';
                $rows = DB::query($sql);
                $idArray = [];
                foreach ($rows as $row) {
                    $idArray[$row['ProductGroupID']] = $row['ProductGroupID'];
                }
                $secondHandProducts = SecondHandProduct::get()->column('ParentID');
                foreach ($secondHandProducts as $parentID) {
                    $idArray[$parentID] = $parentID;
                }

                $firstList = ProductGroup::get()
                    ->filter(['ID' => $idArray]);
                $uselessParents = [];
                foreach ($firstList as $item) {
                    if (isset($idArray[$item->ParentID]) || isset($uselessParents[$item->ParentID])) {
                        //do nothing
                    } else {
                        if ($item->isSecondLevelProductGroup()) {
                            $idArray[$item->ParentID] = $item->ParentID;
                        } else {
                            $uselessParents[$item->ParentID] = $item->ParentID;
                        }
                    }
                }
                $list = ProductGroup::get()
                    ->filter(['ID' => $idArray])
                    ->exclude(['ID' => self::main_second_hand_page_id()])
                    ->sort('IF("ClassName" = \'' . BrandPage::class . '\', 1, 0) ASC, Title ASC');
                self::$list_of_filters[$this->ID] = GroupedList::create(
                    $list
                );
            }
        }
        return self::$list_of_filters[$this->ID];
    }

    /**
     * returns a list of Product IDs for Second Hand Products
     * linked to this Second Hand Product Group
     * @return string
     */
    public function SecondHandProductAlsoShowProductsIDs()
    {
        if (! isset(self::$_page_cache['SecondHandProductAlsoShowProductsIDs'][$this->ID])) {
            $stage = $this->getStage();
            self::$_page_cache['SecondHandProductAlsoShowProductsIDs'] = [];
            $tresholdSQL = SecondHandProduct::get_treshold_sql();
            $sql = '
                SELECT ProductID
                FROM Product_ProductGroups
                    INNER JOIN SecondHandProduct' . $stage . '
                        ON SecondHandProduct' . $stage . '.ID = Product_ProductGroups.ProductID
                    INNER JOIN Product' . $stage . '
                        ON Product' . $stage . '.ID = Product_ProductGroups.ProductID
                    INNER JOIN "SiteTree' . $stage . '"
                        ON "SiteTree' . $stage . '"."ID" = Product_ProductGroups.ProductID
                WHERE
                    ProductGroupID = ' . $this->ID . ' AND
                    AllowPurchase = 1 AND
                    ' . $tresholdSQL . '
            ';
            $rows = DB::query($sql);
            $idArray = [];
            foreach ($rows as $row) {
                $idArray[$row['ProductID']] = $row['ProductID'];
            }
            if ($this instanceof SecondHandProductGroup) {
                $children = $this->ChildCategoriesBasedOnProducts(99)->column('ID');
                $children += [$this->ID => $this->ID];
                $sql = '
                    SELECT Product' . $stage . '.ID
                    FROM Product' . $stage . '
                        INNER JOIN SecondHandProduct' . $stage . '
                            ON SecondHandProduct' . $stage . '.ID = Product' . $stage . '.ID
                        INNER JOIN "SiteTree' . $stage . '"
                            ON "SiteTree' . $stage . '"."ID" = "Product' . $stage . '"."ID"
                    WHERE
                        ParentID IN (' . implode(',', $children) . ') AND
                        AllowPurchase = 1 AND
                        ' . $tresholdSQL . ';';

                $rows = DB::query($sql);
                // $myProductArray = $this->ProductsShowable($tresholdSQL)->column('ID');
                foreach ($rows as $row) {
                    $idArray[$row['ID']] = $row['ID'];
                }
            }
            self::$_page_cache['SecondHandProductAlsoShowProductsIDs'][$this->ID] = implode(',', $idArray);
        }
        return self::$_page_cache['SecondHandProductAlsoShowProductsIDs'][$this->ID];
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
}
