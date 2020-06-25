<?php

namespace Sunnysideup\EcommerceSecondHandProduct\Model\Process;





use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\EcommerceSecondHandProduct\SecondHandProduct;
use SilverStripe\CMS\Model\SiteTree;
use Sunnysideup\Ecommerce\Model\Process\OrderStep;
use Sunnysideup\Ecommerce\Interfaces\OrderStepInterface;



class OrderStep_DisableSecondHandProduct extends OrderStep implements OrderStepInterface
{
    public function HideFromEveryone()
    {
        return true;
    }

    private static $defaults = array(
        'CustomerCanEdit' => 0,
        'CustomerCanPay' => 0,
        'CustomerCanCancel' => 0,
        'Name' => 'Disable Second Hand Product',
        'Code' => 'DISABLE_SECOND_HAND_PRODUCT',
        'ShowAsInProcessOrder' => 1,
    );

    /**
     * The OrderStatusLog that is relevant to the particular step.
     *
     * @var string
     */
    protected $relevantLogEntryClassName = 'OrderStatusLog_DisableSecondHandProduct';

    /**
     * Can run this step once any items have been submitted.
     * makes sure the step is ready to run.... (e.g. check if the order is ready to be emailed as receipt).
     * should be able to run this function many times to check if the step is ready.
     *
     * @see Order::doNextStatus
     *
     * @param Order object
     *
     * @return bool - true if the current step is ready to be run...
     **/
    public function initStep(Order $order)
    {
        return true;
    }

    /**
     * Add a member to the order - in case he / she is not a shop admin.
     *
     * @param Order object
     *
     * @return bool - true if run correctly.
     **/
    public function doStep(Order $order)
    {
        foreach ($order->Buyables() as $buyable) {
            if ($buyable instanceof SecondHandProduct) {
                $buyable->AllowPurchase = 0;
                if (is_a($buyable, Object::getCustomClass(SiteTree::class))) {
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
     * go to next step if order has been submitted.
     *
     * @param Order $order
     *
     * @return OrderStep | Null	(next step OrderStep)
     **/
    public function nextStep(Order $order)
    {
        return parent::nextStep($order);
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

