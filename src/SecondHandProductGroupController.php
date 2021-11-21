<?php

namespace Sunnysideup\EcommerceSecondHandProduct;

use SilverStripe\Core\Config\Config;

use SilverStripe\ORM\ArrayList;
use Sunnysideup\Ecommerce\Pages\ProductGroup;
use Sunnysideup\Ecommerce\Pages\ProductGroupController;

class SecondHandProductGroupController extends ProductGroupController
{
    protected function init()
    {
        Config::modify()->update(
            ProductGroup::class,
            'base_buyable_class',
            SecondHandProduct::class
        );
        parent::init();
    }

}
