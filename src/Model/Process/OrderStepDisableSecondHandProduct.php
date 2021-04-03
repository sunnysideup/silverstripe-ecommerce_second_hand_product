<?php

namespace Sunnysideup\EcommerceSecondHandProduct\Model\Process;

use SilverStripe\CMS\Model\SiteTree;
use Sunnysideup\Ecommerce\Config\EcommerceConfigClassNames;
use Sunnysideup\Ecommerce\Interfaces\OrderStepInterface;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Model\Process\OrderStep;
use Sunnysideup\EcommerceSecondHandProduct\SecondHandProduct;

class OrderStepDisableSecondHandProduct extends OrderStep implements OrderStepInterface
{
    /**
     * The OrderStatusLog that is relevant to the particular step.
     *
     * @var string
     */
    protected $relevantLogEntryClassName = OrderStepDisableSecondHandProduct::class;

    private static $defaults = [
        'CustomerCanEdit' => 0,
        'CustomerCanPay' => 0,
        'CustomerCanCancel' => 0,
        'Name' => 'Disable Second Hand Product',
        'Code' => 'DISABLE_SECOND_HAND_PRODUCT',
        'ShowAsInProcessOrder' => 1,
    ];

    public function HideFromEveryone()
    {
        return true;
    }

    /**
     * Can run this step once any items have been submitted.
     * makes sure the step is ready to run.... (e.g. check if the order is ready to be emailed as receipt).
     * should be able to run this function many times to check if the step is ready.
     *
     * @see Order::doNextStatus
     *
     * @return bool - true if the current step is ready to be run...
     **/
    public function initStep(Order $order): bool
    {
        return true;
    }

    /**
     * Add a member to the order - in case he / she is not a shop admin.
     *
     * @return bool - true if run correctly.
     **/
    public function doStep(Order $order): bool
    {
        foreach ($order->Buyables() as $buyable) {
            if ($buyable instanceof SecondHandProduct) {
                $buyable->AllowPurchase = 0;
                if (is_a($buyable, EcommerceConfigClassNames::getName(SiteTree::class))) {
                    $buyable->writeToStage('Stage');
                    $buyable->publish('Stage', 'Live');
                } else {
                    $buyable->write();
                }
            }
        }
        return true;
    }

    /**
     * Explains the current order step.
     *
     * @return string
     */
    protected function myDescription()
    {
        return _t('OrderStep.DISABLESECONDHANDPRODUCT_DESCRIPTION', 'Disallow second hand products from being sold more than once.');
    }
}
