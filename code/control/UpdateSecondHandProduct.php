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
            $secondHandProduct = DataObject::get_one('SecondHandProduct', ['InternalItemID' => $internalItemID]);
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
            $secondHandProduct = DataObject::get_one('SecondHandProduct', ['InternalItemID' => $internalItemID]);
            if(!$secondHandProduct){
                $secondHandProduct = Versioned::get_one_by_stage('SecondHandProduct', 'Stage', ['InternalItemID' => $internalItemID]);
            }
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
        $code = $this->request->param('ID');
        return ControllerPermissionChecker::permissionCheck($codesWithIPs, $code);
    }
}
