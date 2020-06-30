<?php

namespace Sunnysideup\EcommerceSecondHandProduct\Control;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Convert;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;
use Sunnysideup\Ecommerce\Config\EcommerceConfigClassNames;
use Sunnysideup\EcommerceSecondHandProduct\SecondHandProduct;

class UpdateSecondHandProduct extends Controller
{
    private static $allowed_actions = [
        'unpublish' => '->MyPermissionCheck',
        'archive' => '->MyPermissionCheck',
    ];

    /**
     * make the page less easy to access
     * (but still accessible)
     * - code => ip address
     * @var string
     */
    private static $secret_codes = [];

    public function init()
    {
        parent::init();
        if (! $this->MyPermissionCheck()) {
            die('you do not have access');
        }
    }

    public function unpublish($request)
    {
        $unpublished = false;
        $otherID = $request->param('OtherID');
        if (isset($otherID)) {
            $internalItemID = Convert::raw2sql($otherID);
            $secondHandProduct = DataObject::get_one(SecondHandProduct::class, ['InternalItemID' => $internalItemID]);
            if ($secondHandProduct) {
                $unpublished = $secondHandProduct->deleteFromStage('Live');
            }
        }
        return json_encode($unpublished);
    }

    public function archive($request)
    {
        $archived = false;
        $otherID = $request->param('OtherID');
        if (isset($otherID)) {
            $archived = null;
            $internalItemID = Convert::raw2sql($otherID);
            $secondHandProduct = DataObject::get_one(SecondHandProduct::class, ['InternalItemID' => $internalItemID]);
            if (! $secondHandProduct) {
                $secondHandProduct = Versioned::get_one_by_stage(SecondHandProduct::class, 'Stage', ['InternalItemID' => $internalItemID]);
            }
            if (is_a($secondHandProduct, EcommerceConfigClassNames::getName(SiteTree::class))) {
                $archived = $secondHandProduct->deleteFromStage('Live');
                $archived = $secondHandProduct->deleteFromStage('Stage');
            } elseif ($secondHandProduct !== null) {
                $archived = $secondHandProduct->delete();
            }
        }
        return json_encode($archived);
    }

    /**
     * @return boolean
     */
    public function MyPermissionCheck()
    {
        $codesWithIPs = $this->Config()->get('secret_codes');
        $code = $this->request->param('ID');
        return ControllerPermissionChecker::permissionCheck($codesWithIPs, $code);
    }
}
