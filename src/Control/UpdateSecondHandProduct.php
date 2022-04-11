<?php

namespace Sunnysideup\EcommerceSecondHandProduct\Control;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Convert;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;
use Sunnysideup\Ecommerce\Config\EcommerceConfigClassNames;
use Sunnysideup\EcommerceSecondHandProduct\SecondHandProduct;

use Sunnysideup\EcommerceSecondHandProduct\Api\SecondHandProductActions;

class UpdateSecondHandProduct extends Controller
{
    private static $allowed_actions = [
        'unpublish' => '->MyPermissionCheck',
        'archive' => '->MyPermissionCheck',
    ];

    /**
     * make the page less easy to access
     * (but still accessible)
     * - code => ip address.
     *
     * @var string
     */
    private static $secret_codes = [];

    public function unpublish($request)
    {
        $unpublished = false;
        $otherID = $request->param('OtherID');
        if (!empty($otherID)) {
            $internalItemID = Convert::raw2sql($otherID);
            $secondHandProduct = DataObject::get_one(SecondHandProduct::class, ['InternalItemID' => $internalItemID]);
            if ($secondHandProduct) {
                $secondHandProduct->AllowPurchase = 0;
                $secondHandProduct->ShowInMenus = 0;
                $secondHandProduct->ShowInSearch = 0;
                $secondHandProduct->writeToStage(Versioned::DRAFT);
                $secondHandProduct->publishRecursive();
            }
        }

        return json_encode($unpublished);
    }

    public function archive($request)
    {
        $archived = false;
        $otherID = $request->param('OtherID');
        if (!empty($otherID)) {
            $internalItemID = Convert::raw2sql($otherID);
            $secondHandProduct = DataObject::get_one(SecondHandProduct::class, ['InternalItemID' => $internalItemID]);
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
