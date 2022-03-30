<?php

namespace Sunnysideup\EcommerceSecondHandProduct\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;

use SilverStripe\Core\Environment;
use Sunnysideup\EcommerceSecondHandProduct\SecondHandProduct;

use Sunnysideup\Ecommerce\Model\OrderItem;

class EcommerceTaskSecondHandSoldCodes extends BuildTask
{
    protected $title = 'Get a list of all second hand products sold';

    protected $description = '';

    public function run($request)
    {
        Environment::increaseTimeLimitTo(600);
        echo '<p><a href="/dev/tasks/Sunnysideup-EcommerceSecondHandProduct-Tasks-EcommerceTaskSecondCheckSoldItems">Check Products Codes</a></p>';
        DB::alteration_message(' ================= Sold =================  ');
        $ids = OrderItem::get()->filter(['BuyableClassName' => SecondHandProduct::class])->column('BuyableID');
        $products = SecondHandProduct::get()->filterAny(['AllowPurchase' => 0, 'ID' => $ids]);
        foreach ($products as $product) {
            DB::alteration_message($product->InternalItemID);
        }
        DB::alteration_message(' ================= For Sale =================  ');
        $products = SecondHandProduct::get()->exclude(['ID' => $ids]);
        foreach ($products as $product) {
            DB::alteration_message($product->InternalItemID);
        }
    }
}
