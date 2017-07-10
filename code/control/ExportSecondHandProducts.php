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
        'groups' => '->MyPermissionCheck',
        'images' => '->MyPermissionCheck'
    );

    /**
     * where will the data be saved (if any)
     * @var string
     */
    private static $location_to_save_contents = '';

    /**
     * where will the data be saved (if any)
     * @var string
     */
    private static $folder_for_second_hand_images = 'second-hand-images';

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

    function images()
    {

        $err = 0;
        $array = array();
        $folderName = Config::inst()->get('ExportSecondHandProducts', 'folder_for_second_hand_images');
        $folder = Folder::find_or_make($folderName);
        $seondHandProducts = SecondHandProduct::get()->where('ImageID IS NOT NULL and ImageID > 0');
        foreach($seondHandProducts as $secondHandProduct) {
            $arrayInner = array();
            if($secondHandProduct->ImageID) {
                $image = $secondHandProduct->Image(); //see Product::has_one()
                if($image && $image->exists()) {
                    $arrayInner[$image->ID] = $image;
                }
            }
            $otherImages = $secondHandProduct->AdditionalImages(); //see Product::many_many()
            foreach($otherImages as $otherImage) {
                if($otherImage && $otherImage->exists()) {
                    $arrayInner[$otherImage->ID] = $otherImage;
                }
            }
            $count = 0;
            foreach($arrayInner as $imageID => $image) {
                $fileName = $image->FileName;
                $oldFileLocationAbsolute = Director::baseFolder().'/'.$image->FileName;
                if(file_exists($oldFileLocationAbsolute)) {
                    $extension = pathinfo($image->FileName, PATHINFO_EXTENSION);
                    if(!$extension) {
                        $extension = 'jpg';
                    } else {
                        $extension = strtolower($extension);
                    }
                    $name = $secondHandProduct->InternalItemID.'_'.$count.'.'.$extension;
                    $fileName = $folder->FileName.$name;
                    $title =  $secondHandProduct->Title. ' #'.($count + 1);
                    $image->ParentID = $folder->ID;
                    $image->Name = $name;
                    $image->FileName = $fileName;
                    $image->Title = $title;
                    $image->ClassName = 'Product_Image';
                    $image->write();
                    $newAbsoluteLocation = Director::baseFolder().'/'.$image->FileName;
                    if(! file_exists($newAbsoluteLocation)) {
                        $err++;
                    } else {
                        $array[$secondHandProduct->InternalItemID][] = $name;
                    }
                } else {
                    $err++;
                }
                $count++;
            }
        }
        return $this->returnJSONorFile($array, 'images');
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
