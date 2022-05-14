<?php

namespace Sunnysideup\EcommerceSecondHandProduct\Tasks;

use SilverStripe\Core\Environment;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use SilverStripe\Versioned\Versioned;
use Sunnysideup\Ecommerce\Model\OrderItem;
use Sunnysideup\EcommerceSecondHandProduct\SecondHandProduct;

class EcommerceTaskSecondHandSoldCodes extends BuildTask
{
    protected $title = 'Get a list of all second hand products sold';

    protected $description = '';

    protected $fix = true;

    protected $forSale = false;

    public function run($request)
    {
        Environment::increaseTimeLimitTo(600);
        echo '<p><a href="/dev/tasks/Sunnysideup-EcommerceSecondHandProduct-Tasks-EcommerceTaskSecondCheckSoldItems">Check Products Codes</a></p>';
        DB::alteration_message(' ================= Sold =================  ');
        $ids = OrderItem::get()->filter(['BuyableClassName' => SecondHandProduct::class])->column('BuyableID');
        $products = SecondHandProduct::get()->filterAny(['AllowPurchase' => 0, 'ID' => $ids]);
        foreach ($products as $product) {
            if ($product->AllowPurchase) {
                DB::alteration_message('<a href="/' . $product->CMSEditLink() . '">ERROR WITH ' . $product->InternalItemID . ' | ' . $product->Title . '</a>', 'deleted');
                $this->markAsSold($product);
            } else {
                DB::alteration_message($product->InternalItemID);
            }
        }

        DB::alteration_message(' ================= For Sale =================  ');
    }

    protected function markAsSold($product)
    {
        if ($this->fix) {
            $product->AllowPurchase = 0;
            $product->writeToStage(Versioned::DRAFT);
            $product->publishRecursive();
        }
    }
}
