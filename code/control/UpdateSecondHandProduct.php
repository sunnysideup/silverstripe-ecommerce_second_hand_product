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
    private static $secret_codes = array();

    function init()
    {
        parent::init();
        if(! $this->MyPermissionCheck()) {
            die('you do not have access');
        }
    }

    function unpublish($request)
    {
        $otherID = $request->param("OtherID");
        if(isset($otherID)) {
            $internalItemID = Convert::raw2sql($otherID);
            $secondHandProduct = SecondHandProduct::get()->filter(['internalItemID' => $internalItemID])->first();
            if($secondHandProduct){
                $secondHandProduct->deleteFromStage('Live');
            }
        }
    }

    function archive($request)
    {
        $otherID = $request->param("OtherID");
        if(isset($otherID)) {
            $internalItemID = Convert::raw2sql($otherID);
            $secondHandProduct = SecondHandProduct::get()->filter(['internalItemID' => $internalItemID])->first();
            if (is_a($secondHandProduct, Object::getCustomClass('SiteTree'))) {
                $secondHandProduct->deleteFromStage('Live');
                $secondHandProduct->deleteFromStage('Stage');
            } else {
                $secondHandProduct->delete();
            }
        }
    }

    /**
     * @return Boolean
     */
    function MyPermissionCheck()
    {
        $codesWithIPs = $this->Config()->get('secret_codes');

        //with a code you do not have to be logged in ...
        if(count($codesWithIPs)) {
            $ip = EcommerceCountry::get_ip();
            $code = $this->request->param('ID');
            if($code) {
                $testIP = isset($codesWithIPs[$code]) ? $codesWithIPs[$code] : false;
                if($testIP) {
                    if($testIP === $ip || $testIP === '*') {
                        return true;
                    }
                }
            }
        }
        return Permission::check('ADMIN');
    }
}
