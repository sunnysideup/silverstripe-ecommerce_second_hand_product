<?php

namespace Sunnysideup\EcommerceSecondHandProduct\Control;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;
use Sunnysideup\EcommerceSecondHandProduct\Api\SecondHandProductActions;
use Sunnysideup\EcommerceSecondHandProduct\SecondHandProduct;

/**
 * Class \Sunnysideup\EcommerceSecondHandProduct\Control\UpdateSecondHandProduct
 *
 */
class UpdateSecondHandProduct extends Controller
{
    use SecondHandControllerTrait;

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
        $this->requireWriteAuthentication($request);
        $unpublished = false;
        $otherID = $request->param('OtherID');
        if (! empty($otherID)) {
            $internalItemID = (string) $otherID;
            if ($internalItemID) {
                // do not use caching here - ...
                // $secondHandProduct = DataObject::get_one(SecondHandProduct::class, ['InternalItemID' => $internalItemID]);
                $secondHandProduct = SecondHandProduct::get()->filter(['InternalItemID' => $internalItemID])->first();
                if ($secondHandProduct) {
                    $secondHandProduct->AllowPurchase = 0;
                    $secondHandProduct->ShowInMenus = 0;
                    $secondHandProduct->ShowInSearch = 0;
                    $secondHandProduct->writeToStage(Versioned::DRAFT);

                    //no need to publish recursive here to reduce time!
                    $unpublished = (bool) $secondHandProduct->publishSingle();
                }
            }
        }

        return json_encode($unpublished);
    }

    public function archive($request)
    {
        $this->requireWriteAuthentication($request);
        $archived = false;
        $otherID = $request->param('OtherID');
        if (! empty($otherID)) {
            $internalItemID = (string) $otherID;
            $secondHandProduct = SecondHandProduct::get()->filter(['InternalItemID' => $internalItemID])->first();
            if ($secondHandProduct) {
                $archived = SecondHandProductActions::archive($secondHandProduct->ID);
            }
        }

        return json_encode($archived);
    }

    /**
     * Requests authenticated by secret code (Bearer header) are fine.
     * Requests authenticated by a logged-in session need a valid security token (CSRF).
     */
    protected function requireWriteAuthentication($request): void
    {
        if (! $this->hasValidWriteAuthentication($request)) {
            throw new HTTPResponse_Exception('Invalid or missing security token', 403);
        }
    }
}
