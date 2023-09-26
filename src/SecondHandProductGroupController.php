<?php

namespace Sunnysideup\EcommerceSecondHandProduct;

use SilverStripe\Core\Config\Config;
use Sunnysideup\Ecommerce\Pages\ProductGroup;
use Sunnysideup\Ecommerce\Pages\ProductGroupController;

/**
 * Class \Sunnysideup\EcommerceSecondHandProduct\SecondHandProductGroupController
 *
 * @property \Sunnysideup\EcommerceSecondHandProduct\SecondHandProductGroup $dataRecord
 * @method \Sunnysideup\EcommerceSecondHandProduct\SecondHandProductGroup data()
 * @mixin \Sunnysideup\EcommerceSecondHandProduct\SecondHandProductGroup
 */
class SecondHandProductGroupController extends ProductGroupController
{
    protected function init()
    {
        Config::modify()->set(
            ProductGroup::class,
            'base_buyable_class',
            SecondHandProduct::class
        );
        parent::init();
    }
}
