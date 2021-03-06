<?php

namespace Sunnysideup\EcommerceSecondHandProduct\Control;

use SilverStripe\Assets\Folder;
use SilverStripe\Assets\Image;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\SS_List;
use Sunnysideup\EcommerceSecondHandProduct\Model\SecondHandArchive;
use Sunnysideup\EcommerceSecondHandProduct\SecondHandProduct;
use Sunnysideup\EcommerceSecondHandProduct\SecondHandProductGroup;

class ExportSecondHandProducts extends Controller
{
    private static $do_not_copy = [
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
        'parser',
    ];

    /**
     * in the following format:
     *
     * AnyRelationA => (ForeignDBField1, ForeignDBField2, etc...)
     * AnyRelationB => (ForeignDBField1, ForeignDBField2, etc...)
     * d
     * @var array
     */
    private static $relationships_to_include_with_groups = [];

    /**
     * in the following format:
     *
     * AnyRelationA => (ForeignDBField1, ForeignDBField2, etc...)
     * AnyRelationB => (ForeignDBField1, ForeignDBField2, etc...)
     *
     * @var array
     */
    private static $relationships_to_include_with_products = [
        'ProductGroups' => ['URLSegment'],
    ];

    private static $allowed_actions = [
        'products' => '->MyPermissionCheck',
        'groups' => '->MyPermissionCheck',
        'images' => '->MyPermissionCheck',
    ];

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
    private static $secret_codes = [];

    /**
     * @var string
     */
    private static $url_segment_of_parent_field_name = 'ParentURLSegmentForImportExport';

    public function init()
    {
        parent::init();
        if (! $this->MyPermissionCheck()) {
            die('you do not have access');
        }
    }

    public function products()
    {
        $withImageData = false;
        if (! empty($_GET['withimagedata'])) {
            $withImageData = true;
            $imageData = $this->getImageArray(true);
        }
        $array = [];
        $products = SecondHandProduct::get()->filter(['AllowPurchase' => 1]);
        $count = 0;
        $doNotCopy = $this->Config()->get('do_not_copy');
        $parentURLSegmentField = $this->Config()->get('url_segment_of_parent_field_name');
        $singleton = Injector::inst()->get(SecondHandProductGroup::class);
        $rootSecondHandPage = $singleton->BestRootParentPage();
        $relations = Config::inst()->get(ExportSecondHandProducts::class, 'relationships_to_include_with_products');
        if ($rootSecondHandPage) {
            foreach ($products as $product) {
                $productData = $product->toMap();
                $archivedVersion = SecondHandArchive::get()->filter(['InternalItemID' => $product->InternalItemID])->first();
                $productData['HasBeenArchived'] = $archivedVersion ? true : false;
                $array[$count] = $productData;
                foreach ($doNotCopy as $field) {
                    unset($array[$count][$field]);
                }
                if ($parent = $product->Parent()) {
                    if ($parent->ID === $rootSecondHandPage->ID) {
                        $array[$count][$parentURLSegmentField] = false;
                    } else {
                        $array[$count][$parentURLSegmentField] = $parent->URLSegment;
                    }
                }
                $array[$count] += $this->addRelations($product, $relations);
                if ($withImageData && isset($imageData[$product->InternalItemID])) {
                    $array[$count]['ImagesFileSize'] = $imageData[$product->InternalItemID];
                }
                //next one
                $count++;
            }
        }

        return $this->returnJSONorFile($array, '');
    }

    public function groups()
    {
        $array = [];
        $groups = SecondHandProductGroup::get();
        $count = 0;
        $doNotCopy = $this->Config()->get('do_not_copy');
        $parentURLSegmentField = $this->Config()->get('url_segment_of_parent_field_name');
        $singleton = Injector::inst()->get(SecondHandProductGroup::class);
        $rootSecondHandPage = $singleton->BestRootParentPage();
        $relations = Config::inst()->get(ExportSecondHandProducts::class, 'relationships_to_include_with_groups');
        if ($rootSecondHandPage) {
            foreach ($groups as $group) {
                if (! $group->RootParent) {
                    $array[$count] = $group->toMap();
                    foreach ($doNotCopy as $field) {
                        unset($array[$count][$field]);
                    }
                    if ($parent = $group->Parent()) {
                        if ($parent->ID === $rootSecondHandPage->ID) {
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

    public function images()
    {
        $array = $this->getImageArray(false);
        return $this->returnJSONorFile($array, 'images');
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

    protected function returnJSONorFile($array, $filenameAppendix = '')
    {
        if (Director::isDev()) {
            $json = json_encode($array, JSON_PRETTY_PRINT);
        } else {
            $json = json_encode($array);
            $json = str_replace(["\t", "\r", "\n"], [' ', ' ', ' '], $json);
            $json = preg_replace('/\s\s+/', ' ', $json);
        }
        $fileName = $this->Config()->get('location_to_save_contents');
        if ($fileName) {
            if ($filenameAppendix) {
                $fileName = str_replace('.json', '.' . $filenameAppendix . '.json', $fileName);
            }
            $fileNameFull = Director::baseFolder() . '/' . $fileName;
            file_put_contents($fileNameFull, $json);
            die('COMPLETED');
        }
        $this->response->addHeader('Content-Type', 'application/json');
        return $json;
    }

    /**
     * @param DataObject $currentObject the object we are exporting
     * @param array $relations  the array of fields to be added
     *
     * @return array
     */
    protected function addRelations($currentObject, $relations)
    {
        $dataToBeAdded = [];
        foreach ($relations as $myField => $relFields) {
            $innerDataToBeAdded = [];
            $relData = $currentObject->{$myField}();
            if ($relData instanceof SS_List) {
                $count = 0;
                foreach ($relData as $relItem) {
                    foreach ($relFields as $relField) {
                        $innerDataToBeAdded[$count][$relField] = $relItem->{$relField};
                    }
                    $count++;
                }
            } elseif ($relData instanceof DataObject) {
                foreach ($relFields as $relField) {
                    $innerDataToBeAdded = [$relField => $relData->{$relField}];
                }
            }
            //do nothing

            $dataToBeAdded[$myField] = $innerDataToBeAdded;
        }

        return $dataToBeAdded;
    }

    protected function getImageArray($imageSizesOnly = false)
    {
        $array = [];
        $folderName = Config::inst()->get(ExportSecondHandProducts::class, 'folder_for_second_hand_images');
        $folder = Folder::find_or_make($folderName);
        $secondHandProducts = SecondHandProduct::get()->filter(['AllowPurchase' => 1])->exclude(['ImageID' => 0]);
        foreach ($secondHandProducts as $secondHandProduct) {
            $arrayInner = [];
            if ($secondHandProduct->ImageID) {
                $image = $secondHandProduct->Image(); //see Product::has_one()
                if ($image && $image->exists()) {
                    $arrayInner[$image->ID] = $image;
                }
            }
            $otherImages = $secondHandProduct->AdditionalImages(); //see Product::many_many()
            foreach ($otherImages as $otherImage) {
                if ($otherImage && $otherImage->exists()) {
                    $arrayInner[$otherImage->ID] = $otherImage;
                }
            }
            $count = 0;
            foreach ($arrayInner as $image) {
                $fileName = $image->FileName;
                $oldFileLocationAbsolute = Director::baseFolder() . '/' . $image->FileName;
                if (file_exists($oldFileLocationAbsolute)) {
                    $extension = pathinfo($image->FileName, PATHINFO_EXTENSION);
                    if (! $extension) {
                        $extension = 'jpg';
                    } else {
                        $extension = strtolower($extension);
                    }
                    $name = $secondHandProduct->InternalItemID . '_' . $count . '.' . $extension;
                    $fileName = $folder->FileName . $name;
                    $title = $secondHandProduct->Title . ' #' . ($count + 1);
                    $image->ParentID = $folder->ID;
                    $image->Name = $name;
                    $image->FileName = $fileName;
                    $image->Title = $title;
                    $image->ClassName = Image::class;
                    $image->write();
                    $newAbsoluteLocation = Director::baseFolder() . '/' . $image->FileName;
                    if (file_exists($newAbsoluteLocation)) {
                        if ($imageSizesOnly) {
                            if (! isset($array[$secondHandProduct->InternalItemID])) {
                                $array[$secondHandProduct->InternalItemID] = 0;
                            }
                            $array[$secondHandProduct->InternalItemID] += filesize($newAbsoluteLocation);
                        } else {
                            if (! isset($array[$secondHandProduct->InternalItemID])) {
                                $array[$secondHandProduct->InternalItemID] = [];
                            }
                            $array[$secondHandProduct->InternalItemID][] = $name;
                        }
                    }
                }
                $count++;
            }
        }

        return $array;
    }
}
