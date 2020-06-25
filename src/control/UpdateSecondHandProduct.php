<?php


class UpdateSecondHandProduct extends Controller
{
    private static $allowed_actions = array(
        'unpublish' => '->MyPermissionCheck',
        'archive' => '->MyPermissionCheck'
    );


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
        $otherID = $request->param("OtherID");
        if (isset($otherID)) {
            $internalItemID = Convert::raw2sql($otherID);
            $secondHandProduct = DataObject::get_one('SecondHandProduct', ['InternalItemID' => $internalItemID]);
            if ($secondHandProduct) {
                $unpublished = $secondHandProduct->deleteFromStage('Live');
            }
        }
        return json_encode($unpublished);
    }

    public function archive($request)
    {
        $archived = false;
        $otherID = $request->param("OtherID");
        if (isset($otherID)) {
            $archived = null;
            $internalItemID = Convert::raw2sql($otherID);
            $secondHandProduct = DataObject::get_one('SecondHandProduct', ['InternalItemID' => $internalItemID]);
            if (!$secondHandProduct) {
                $secondHandProduct = Versioned::get_one_by_stage('SecondHandProduct', 'Stage', ['InternalItemID' => $internalItemID]);
            }

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD:  Object:: (case sensitive)
  * NEW:  SilverStripe\\Core\\Injector\\Injector::inst()-> (COMPLEX)
  * EXP: Check if this is the right implementation, this is highly speculative.
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
            if (is_a($secondHandProduct, SilverStripe\Core\Injector\Injector::inst()->getCustomClass('SiteTree'))) {
                $archived = $secondHandProduct->deleteFromStage('Live');
                $archived = $secondHandProduct->deleteFromStage('Stage');
            } elseif (! is_null($secondHandProduct)) {
                $archived = $secondHandProduct->delete();
            }
        }
        return json_encode($archived);
    }

    /**
     * @return Boolean
     */
    public function MyPermissionCheck()
    {
        $codesWithIPs = $this->Config()->get('secret_codes');
        $code = $this->request->param('ID');
        return ControllerPermissionChecker::permissionCheck($codesWithIPs, $code);
    }
}

