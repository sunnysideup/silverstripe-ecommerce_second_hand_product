<?php


class ExportSecondHandProducts extends Controller
{
    private static $do_not_copy = array(
        'ClassName',
        'ParentID',
        'Created',
        'HasBrokenFile',
        'HasBrokenLink',
        'HasBrokenFile',
        'ImageID',
        'SellersIDPhotocopy',
        'SellersIDType',
        'SerialNumber',
        'BasedOnID',
        'Version',
        'FullSiteTreeSort',
        'FullName',
        'ID',
        'RecordClassName',
        'parser'
    );

    private static $allowed_actions = array(
        'products' => '->MyPermissionCheck',
        'groups' => '->MyPermissionCheck'
    );

    /**
     * where will the data be saved (if any)
     * @var string
     */
    private static $location_to_save_contents = '';

    /**
     * make the page less easy to access
     * (but still accessible)
     * - code => ip address
     * @var string
     */
    private static $secret_codes = array();

    /**
     *
     * @var string
     */
    private static $url_segment_of_parent_field_name = 'ParentURLSegmentForImportExport';

    function products()
    {
        $array = array();
        $products = SecondHandProduct::get()->filter(array('AllowPurchase' => 1));
        $count = 0;
        $doNotCopy = $this->Config()->get('do_not_copy');
        $parentURLSegmentField = $this->Config()->get('url_segment_of_parent_field_name');
        foreach($products as $product) {
            $array[$count] = $product->toMap();
            foreach($doNotCopy as $field) {
                unset($array[$count][$field]);
            }
            if($parent = $product->Parent()) {
                $array[$count][$parentURLSegmentField] = $parent->URLSegment;
            }
            $count++;
        }
        $json = json_encode($array);
        $fileName = $this->Config()->get('location_to_save_contents');
        if($fileName) {
            $fileNameFull = Director::baseFolder().'/'.$fileName;
            file_put_contents($fileNameFull, $json);
            die('COMPLETED');
        } else {
            return $json;
        }
    }

    function groups ()
    {
        $array = array();
        $groups = SecondHandProductGroup::get()->exclude(array('RootParent' => 1));
        $count = 0;
        $doNotCopy = $this->Config()->get('do_not_copy');
        $parentURLSegmentField = $this->Config()->get('url_segment_of_parent_field_name');
        foreach($groups as $group) {
            $array[$count] = $group->toMap();
            foreach($doNotCopy as $field) {
                unset($array[$count][$field]);
            }
            if($parent = $group->Parent()) {
                $array[$count][$parentURLSegmentField] = $parent->URLSegment;
            }
            $count++;
        }
        $json = json_encode($array);
        $fileName = $this->Config()->get('location_to_save_contents');
        if($fileName) {
            $fileName = str_replace('.json', '.groups.json', $fileName);
            $fileNameFull = Director::baseFolder().'/'.$fileName;
            file_put_contents($fileNameFull, $json);
            die('COMPLETED');
        } else {
            return $json;
        }
    }

    /**
     * @return Boolean
     */
    function MyPermissionCheck()
    {
        $codesWithIPs = $this->Config()->get('secret_codes');
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
