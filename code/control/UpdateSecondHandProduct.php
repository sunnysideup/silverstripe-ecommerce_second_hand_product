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
        $unpublished = false;
        $otherID = $request->param("OtherID");
        if(isset($otherID)) {
            $internalItemID = Convert::raw2sql($otherID);
            $secondHandProduct = SecondHandProduct::get()->filter(['internalItemID' => $internalItemID])->first();
            if($secondHandProduct){
                $unpublished = $secondHandProduct->deleteFromStage('Live');
            }
        }
        return json_encode($unpublished);
    }

    function archive($request)
    {
        $archived = false;
        $otherID = $request->param("OtherID");
        if(isset($otherID)) {
            $internalItemID = Convert::raw2sql($otherID);
            $secondHandProduct = SecondHandProduct::get()->filter(['internalItemID' => $internalItemID])->first();
            if (is_a($secondHandProduct, Object::getCustomClass('SiteTree'))) {
                $archived = $secondHandProduct->deleteFromStage('Live');
                $archived = $secondHandProduct->deleteFromStage('Stage');
            } else {
                $archived = $secondHandProduct->delete();
            }
        }
        return json_encode($archived);
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
