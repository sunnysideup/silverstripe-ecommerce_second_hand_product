<?php

declare(strict_types=1);

namespace Sunnysideup\EcommerceSecondHandProduct;

use Override;
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
    #[Override]
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

    #[Override]
    public function ShowFilterLinks(): bool
    {
        return false;
    }
}
