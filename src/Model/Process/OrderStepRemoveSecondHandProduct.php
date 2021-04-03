<?php

namespace Sunnysideup\EcommerceSecondHandProduct\Model\Process;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Security\Member;
use Sunnysideup\Ecommerce\Config\EcommerceConfigClassNames;
use Sunnysideup\Ecommerce\Interfaces\OrderStepInterface;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Model\Process\OrderStep;
use Sunnysideup\EcommerceSecondHandProduct\SecondHandProduct;

class OrderStepRemoveSecondHandProduct extends OrderStep implements OrderStepInterface
{
    /**
     * The OrderStatusLog that is relevant to the particular step.
     *
     * @var string
     */
    protected $relevantLogEntryClassName = OrderStepRemoveSecondHandProduct::class;

    private static $defaults = [
        'CustomerCanEdit' => 0,
        'CustomerCanPay' => 0,
        'CustomerCanCancel' => 0,
        'Name' => 'Remove Second Hand Product',
        'Code' => 'REMOVE_SECOND_HAND_PRODUCT',
        'ShowAsInProcessOrder' => 1,
    ];

    public function HideFromEveryone(): bool
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
                $buyable->ArchivedByID = $order->MemberID;
                if (is_a($buyable, EcommerceConfigClassNames::getName(SiteTree::class))) {
                    $buyable->write();
                    $buyable->publishRecursive();
                    $buyable->deleteFromStage('Live');
                    $buyable->deleteFromStage('Stage');
                } else {
                    $buyable->write();
                    $buyable->delete();
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
        return _t('OrderStep.REMOVESECONDHANDPRODUCT_DESCRIPTION', 'Remove second hand products once the sale has been confirmed.');
    }
}
