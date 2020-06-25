<?php

namespace Sunnysideup\EcommerceSecondHandProduct\Tasks;

use BuildTask;
use Environment;
use SecondHandProduct;
use DB;


class EcommerceTaskSecondHandPublishAll extends BuildTask
{
    protected $title = '(Re)publish all second hand products';

    protected $description = 'Go through all second hand products that are for sale and re-publish them...';

    public function run($request)
    {
        Environment::increaseTimeLimitTo(600);
        $products = SecondHandProduct::get();
        foreach ($products as $product) {
            DB::alteration_message('Publish: '.$product->Title);
            $product->writeToStage('Stage');
            $product->publish('Stage', 'Live');
        }
        DB::alteration_message(' ================= Completed =================  ');
    }
}

