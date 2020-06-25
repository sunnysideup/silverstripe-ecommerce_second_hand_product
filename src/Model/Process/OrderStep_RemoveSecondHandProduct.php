<?php

namespace Sunnysideup\EcommerceSecondHandProduct\Model\Process;

use OrderStep;
use OrderStepInterface;
use Order;
use SecondHandProduct;
use Member;


class OrderStep_RemoveSecondHandProduct extends OrderStep implements OrderStepInterface
{
    public function HideFromEveryone()
    {
        return true;
    }

    private static $defaults = array(
        'CustomerCanEdit' => 0,
        'CustomerCanPay' => 0,
        'CustomerCanCancel' => 0,
        'Name' => 'Remove Second Hand Product',
        'Code' => 'REMOVE_SECOND_HAND_PRODUCT',
        'ShowAsInProcessOrder' => 1,
    );

    /**
     * The OrderStatusLog that is relevant to the particular step.
     *
     * @var string
     */
    protected $relevantLogEntryClassName = 'OrderStatusLog_RemoveSecondHandProduct';

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
                $currentMember = Member::currentUser();
                $buyable->ArchivedByID = $order->MemberID;
                if (is_a($buyable, Object::getCustomClass('SiteTree'))) {
                    $buyable->write();
                    $buyable->doPublish();
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
        return _t('OrderStep.REMOVESECONDHANDPRODUCT_DESCRIPTION', 'Remove second hand products once the sale has been confirmed.');
    }
}

