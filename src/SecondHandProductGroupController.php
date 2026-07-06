<?php

declare(strict_types=1);

namespace Sunnysideup\EcommerceSecondHandProduct;

use SilverStripe\Core\Config\Config;
use Sunnysideup\Ecommerce\Pages\ProductGroup;
use Sunnysideup\Ecommerce\Pages\ProductGroupController;

/**
 * Class \Sunnysideup\EcommerceSecondHandProduct\SecondHandProductGroupController
 *
 * @property SecondHandProductGroup $dataRecord
 * @method SecondHandProductGroup data()
 * @mixin SecondHandProductGroup
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

    public function IsSecondHandSection(): bool
    {
        return true;
    }

    public function ShowFilterLinks(): bool
    {
        return false;
    }
}
