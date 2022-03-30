<?php

namespace Sunnysideup\EcommerceSecondHandProduct\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;

use SilverStripe\Core\Environment;

use SilverStripe\Versioned\Versioned;
use Sunnysideup\EcommerceSecondHandProduct\SecondHandProduct;

class EcommerceTaskSecondHandPublishAll extends BuildTask
{
    protected $title = '(Re)publish all second hand products';

    protected $description = 'Go through all second hand products that are for sale and re-publish them...';

    public function run($request)
    {
        Environment::increaseTimeLimitTo(600);
        $products = SecondHandProduct::get();
        foreach ($products as $product) {
            DB::alteration_message('Publish: ' . $product->Title);
            $product->writeToStage(Versioned::DRAFT);
            $product->publishRecursive();
        }
        DB::alteration_message(' ================= Completed =================  ');
    }
}
