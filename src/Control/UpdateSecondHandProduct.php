<?php

namespace Sunnysideup\EcommerceSecondHandProduct\Control;

use SilverStripe\Control\Controller;
use SilverStripe\Core\Convert;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;
use Sunnysideup\EcommerceSecondHandProduct\Api\SecondHandProductActions;
use Sunnysideup\EcommerceSecondHandProduct\SecondHandProduct;

class UpdateSecondHandProduct extends Controller
{
    private static $allowed_actions = [
        'removefromsale' => '->MyPermissionCheck',
        'archive' => '->MyPermissionCheck',
    ];

    /**
     * make the page less easy to access
     * (but still accessible)
     * - code => ip address.
     *
     * @var array[string]
     */
    private static $secret_codes = [];

    public function removefromsale($request)
    {
        $unpublished = false;
        $otherID = $request->param('OtherID');
        if (! empty($otherID)) {
            $internalItemID = Convert::raw2sql($otherID);
            if($internalItemID) {
                // do not use caching here - ...
                // $secondHandProduct = DataObject::get_one(SecondHandProduct::class, ['InternalItemID' => $internalItemID]);
                $secondHandProduct = SecondHandProduct::get()->filter(['InternalItemID' => $internalItemID])->first();
                if ($secondHandProduct) {
                    $secondHandProduct->AllowPurchase = 0;
                    $secondHandProduct->ShowInMenus = 0;
                    $secondHandProduct->ShowInSearch = 0;
                    $secondHandProduct->writeToStage(Versioned::DRAFT);

                    //no need to publish recursively here to reduct time!
                    $secondHandProduct->publishSingle();
                }
            }
        }

        return json_encode($unpublished);
    }

    public function archive($request)
    {
        $archived = false;
        $otherID = $request->param('OtherID');
        if (! empty($otherID)) {
            $internalItemID = Convert::raw2sql($otherID);
            $secondHandProduct = SecondHandProduct::get()->filter(['InternalItemID' => $internalItemID])->first();
            if ($secondHandProduct) {
                $archived = SecondHandProductActions::archive($secondHandProduct->ID);
            }
        }

        return json_encode($archived);
    }

    /**
     * @return bool
     */
    public function MyPermissionCheck()
    {
        $codesWithIPs = $this->Config()->get('secret_codes');
        $code = $this->request->param('ID');

        return ControllerPermissionChecker::permissionCheck($codesWithIPs, $code);
    }

    protected function init()
    {
        parent::init();
        if (! $this->MyPermissionCheck()) {
            die('you do not have access');
        }
    }
}
