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

    /**
     * in the following format:
     *
     * AnyRelationA => (ForeignDBField1, ForeignDBField2, etc...)
     * AnyRelationB => (ForeignDBField1, ForeignDBField2, etc...)
     * d
     * @var array
     */
    private static $relationships_to_include_with_groups = array();


    /**
     * in the following format:
     *
     * AnyRelationA => (ForeignDBField1, ForeignDBField2, etc...)
     * AnyRelationB => (ForeignDBField1, ForeignDBField2, etc...)
     *
     * @var array
     */
    private static $relationships_to_include_with_products = array(
        'ProductGroups' => array('URLSegment')
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

    function init()
    {
        parent::init();
        if(!$this->MyPermissionCheck()) {
            die('you do not have access');
        }
    }

    function products()
    {
        $array = array();
        $products = SecondHandProduct::get()->filter(array('AllowPurchase' => 1));
        $count = 0;
        $doNotCopy = $this->Config()->get('do_not_copy');
        $parentURLSegmentField = $this->Config()->get('url_segment_of_parent_field_name');
        $singleton = Injector::inst()->get('SecondHandProductGroup');
        $rootSecondHandPage = $singleton->BestRootParentPage();
        $relations = Config::inst()->get('ExportSecondHandProducts', 'relationships_to_include_with_products');
        if($rootSecondHandPage) {
            foreach($products as $product) {
                $array[$count] = $product->toMap();
                foreach($doNotCopy as $field) {
                    unset($array[$count][$field]);
                }
                if($parent = $product->Parent()) {
                    if($parent->ID === $rootSecondHandPage->ID) {
                        $array[$count][$parentURLSegmentField] = false;
                    } else {
                        $array[$count][$parentURLSegmentField] = $parent->URLSegment;
                    }
                }
                $array[$count] += $this->addRelations($product, $relations);

                //next one
                $count++;
            }
        }

        return $this->returnJSONorFile($array, '');

    }

    function groups ()
    {
        $array = array();
        $groups = SecondHandProductGroup::get();
        $count = 0;
        $doNotCopy = $this->Config()->get('do_not_copy');
        $parentURLSegmentField = $this->Config()->get('url_segment_of_parent_field_name');
        $singleton = Injector::inst()->get('SecondHandProductGroup');
        $rootSecondHandPage = $singleton->BestRootParentPage();
        $relations = Config::inst()->get('ExportSecondHandProducts', 'relationships_to_include_with_groups');
        if($rootSecondHandPage) {
            foreach($groups as $group) {
                if(! $group->RootParent) {
                    $array[$count] = $group->toMap();
                    foreach($doNotCopy as $field) {
                        unset($array[$count][$field]);
                    }
                    if($parent = $group->Parent()) {
                        if($parent->ID === $rootSecondHandPage->ID) {
                            $array[$count][$parentURLSegmentField] = false;
                        } else {
                            $array[$count][$parentURLSegmentField] = $parent->URLSegment;
                        }
                    }
                    $array[$count] += $this->addRelations($group, $relations);

                    //next one
                    $count++;
                }

            }
        }

        return $this->returnJSONorFile($array, 'groups');
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

    protected function returnJSONorFile($array, $filenameAppendix = '')
    {
        if(Director::isDev()) {
            $json = json_encode($array, JSON_PRETTY_PRINT);
        } else {
            $json = json_encode($array);
            $json = str_replace(array("\t", "\r", "\n"), array(" ", " ", " "), $json);
            $json = preg_replace('/\s\s+/', ' ', $json);
        }
        $fileName = $this->Config()->get('location_to_save_contents');
        if($fileName) {
            if($filenameAppendix) {
                $fileName = str_replace('.json', '.'.$filenameAppendix.'.json', $fileName);
            }
            $fileNameFull = Director::baseFolder().'/'.$fileName;
            file_put_contents($fileNameFull, $json);
            die('COMPLETED');
        } else {
            $this->response->addHeader('Content-Type', 'application/json');
            return $json;
        }
    }

    /**
     * @param DataObject $currentObject the object we are exporting
     * @param array $relations  the array of fields to be added
     *
     * @return array
     */
    protected function addRelations($currentObject, $relations)
    {
        $dataToBeAdded = array();
        foreach($relations as $myField => $relFields) {
            $innerDataToBeAdded = array();
            $relData = $currentObject->$myField();
            if($relData instanceof SS_List) {
                $count = 0;
                foreach($relData as $relItem) {
                    foreach($relFields as $relField) {
                        $innerDataToBeAdded[$count][$relField] = $relItem->$relField;
                    }
                    $count++;
                }
            } elseif($relData instanceof DataObject) {
                foreach($relFields as $relField) {
                    $innerDataToBeAdded = array($relField => $relData->$relField);
                }
            } else {
                //do nothing
            }
            $dataToBeAdded[$myField] = $innerDataToBeAdded;
        }

        return $dataToBeAdded;
    }

}
