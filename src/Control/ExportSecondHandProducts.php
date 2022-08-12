<?php

namespace Sunnysideup\EcommerceSecondHandProduct\Control;

use SilverStripe\Assets\Folder;
use SilverStripe\Assets\Image;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\Director;

use SilverStripe\Control\Middleware\HTTPCacheControlMiddleware;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\SS_List;
use SilverStripe\ORM\DataList;
use SilverStripe\Versioned\Versioned;
use Sunnysideup\EcommerceSecondHandProduct\Model\SecondHandArchive;

use SilverStripe\CMS\Model\SiteTree;
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
        'Lat',
        'Lng',
        'Lng',
        'ExcludeFromSearchEngines',
        'FooterMenuOrder',
        'RootParent',
    ];

    /**
     * in the following format:.
     *
     * AnyRelationA => (ForeignDBField1, ForeignDBField2, etc...)
     * AnyRelationB => (ForeignDBField1, ForeignDBField2, etc...)
     * d
     *
     * @var array
     */
    private static $relationships_to_include_with_groups = [];

    /**
     * in the following format:.
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
     * where will the data be saved (if any).
     *
     * @var string
     */
    private static $location_to_save_contents = '';

    /**
     * make the page less easy to access
     * (but still accessible)
     * - code => ip address.
     *
     * @var array[string]
     */
    private static $secret_codes = [];

    /**
     * @var string
     */
    private static $url_segment_of_parent_field_name = 'ParentURLSegmentForImportExport';

    public function products()
    {
        $withImageData = false;
        $additionalData = [];
        if (! empty($_GET['withimagedata'])) {
            $withImageData = true;
            $imageData = self::get_image_array(true);
            foreach(array_keys($withImageData) as $internalItemID) {
                $additionalData[$internalItemID] = array_sum($imageData[$internalItemID]);
            }
        }
        $list = SecondHandProduct::get()->filter(['AllowPurchase' => 1]);
        $relations = Config::inst()->get(ExportSecondHandProducts::class, 'relationships_to_include_with_products');
        return $this->returnJSONorFile($this->createList($list, $relations, $additionalData), 'groups');
    }

    public function groups()
    {
        $list = SecondHandProductGroup::get()->exclude(['RootParent' => true]);
        $relations = Config::inst()->get(ExportSecondHandProducts::class, 'relationships_to_include_with_groups');
        $this->returnJSONorFile($this->createList($list, $relations), 'groups');
    }

    protected function createList(DataList $list, array $relations, ?array $additionalData = [])
    {
        $array = [];
        $count = 0;
        $doNotCopy = $this->Config()->get('do_not_copy');
        $parentURLSegmentField = $this->Config()->get('url_segment_of_parent_field_name');
        $rootSecondHandPage = Injector::inst()->get(SecondHandProductGroup::class)->BestRootParentPage();
        $relations = Config::inst()->get(ExportSecondHandProducts::class, 'relationships_to_include_with_groups');
        if ($rootSecondHandPage) {
            foreach ($list as $page) {
                $array[$count] = $page->toMap();
                foreach ($doNotCopy as $field) {
                    unset($array[$count][$field]);
                }

                $parent = $page->getParent();
                if ($parent && $parent instanceof SiteTree && $parent->exists()) {
                    $array[$count][$parentURLSegmentField] = ($parent->ID === $rootSecondHandPage->ID ? false : $parent->CleanURLSegment());
                    $array[$count]['ParentTitle'] = ($parent->ID === $rootSecondHandPage->ID ? false : $parent->Title);
                }

                $array[$count] += $this->addRelations($page, $relations);
                $array[$count] += $additionalData;

                //next one
                ++$count;
            }
        }

        return $this->returnJSONorFile($array, 'groups');
    }



    public function images()
    {
        $array = self::get_image_array(false);

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

    /**
     * images file name and size are separated by *
     * @param  boolean $imageSizesOnly
     * @param  boolean $getIds
     * @return array
     */
    public static function get_image_array(?bool $imageSizesOnly = false, ?bool $getIds = false): array
    {
        $array = [];
        $folderName = Config::inst()->get(SecondHandProduct::class, 'folder_for_second_hand_images');
        $folder = Folder::find_or_make($folderName);
        $secondHandProducts = SecondHandProduct::get()
            ->filter(['AllowPurchase' => 1])
            ->exclude(['ImageID' => 0])
        ;
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

            foreach ($arrayInner as $imageID => $image) {
                if ($image->ParentID !== $folder->ID) {
                    if($secondHandProduct->IsPublished()) {
                        $secondHandProduct->writeToStage(Versioned::DRAFT);
                        $secondHandProduct->publishRecursive();
                        $image = Image::get()->byID($image->ID);
                    }
                }

                $filename = $image->getFileName();
                $location = Controller::join_links(ASSETS_PATH, $filename);
                if (! isset($array[$secondHandProduct->InternalItemID])) {
                    $array[$secondHandProduct->InternalItemID] = [];
                }

                if ($getIds) {
                    $array[$secondHandProduct->InternalItemID][$imageID] = filesize($location);
                } elseif (file_exists($location)) {
                    if ($imageSizesOnly) {
                        $array[$secondHandProduct->InternalItemID][] = filesize($location);
                    } else {
                        $array[$secondHandProduct->InternalItemID][] = $image->Name . '*' . filesize($location);
                    }
                }
            }
        }

        return $array;
    }

    protected function init()
    {
        parent::init();
        if (! $this->MyPermissionCheck()) {
            die('you do not have access');
        }
    }

    protected function returnJSONorFile($array, $filenameAppendix = '')
    {
        if (Director::isDev()) {
            $json = json_encode($array, JSON_PRETTY_PRINT);
        } else {
            $json = json_encode($array);
            $json = str_replace(["\t", "\r", "\n"], [' ', ' ', ' '], $json);
            $json = preg_replace('#\s\s+#', ' ', $json);
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
        $response = (new HTTPResponse($json));
        $response->addHeader('Content-Type', 'application/json; charset="utf-8"');
        $response->addHeader('Pragma', 'no-cache');
        $response->addHeader('cache-control', 'no-cache, no-store, must-revalidate');
        $response->addHeader('Access-Control-Allow-Origin', '*');
        $response->addHeader('Expires', 0);
        HTTPCacheControlMiddleware::singleton()
                   ->disableCache();
        $response->output();
        die();
    }

    /**
     * @param DataObject $currentObject the object we are exporting
     * @param array      $relations     the array of fields to be added
     *
     * @return array
     */
    protected function addRelations($currentObject, array $relations) : array
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

                    ++$count;
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
}
