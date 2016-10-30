<?php

class EcommerceTaskSecondHandPublishAll extends BuildTask
{
    protected $title = '(Re)publish all second hand products';

    protected $description = 'Go through all second hand products that are for sale and re-publish them...';

    public function run($request)
    {
        increase_time_limit_to(600);
        $products = SecondHandProduct::get()->filter(array('AllowPurchase' => 1));
        foreach($products as $product) {
            DB::alteration_message('Publish: '.$product->Title);
            $product->writeToStage('Stage');
            $product->publish('Stage', 'Live');
        }

    }
}
