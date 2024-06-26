<?php

namespace Sunnysideup\EcommerceSecondHandProduct\Control;

use SilverStripe\Assets\Folder;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\Middleware\HTTPCacheControlMiddleware;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\SS_List;
use Sunnysideup\EcommerceSecondHandProduct\SecondHandProduct;
use Sunnysideup\EcommerceSecondHandProduct\SecondHandProductGroup;

/**
 * Class \Sunnysideup\EcommerceSecondHandProduct\Control\ExportSecondHandProducts
 *
 */
class ExportSecondHandProducts extends Controller
{
    public const SIZE_SEPARATOR = '*';

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
        'RootParent',
        'EquivalentNewProductID'
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
        $additionalData = [];
        if (! empty($_GET['withimagedata'])) {
            $imageData = self::get_image_array(true);
            foreach (array_keys($imageData) as $internalItemID) {
                $additionalData[$internalItemID] = array_sum($imageData[$internalItemID]);
            }
        }
        $list = SecondHandProduct::get()->filter(['AllowPurchase' => 1]);
        $relations = Config::inst()->get(ExportSecondHandProducts::class, 'relationships_to_include_with_products');

        return $this->createList($list, $relations, $additionalData);
    }

    public function groups()
    {
        $list = SecondHandProductGroup::get()->exclude(['RootParent' => true]);
        $relations = Config::inst()->get(ExportSecondHandProducts::class, 'relationships_to_include_with_groups');

        return $this->createList($list, $relations);
    }

    public function images()
    {
        $array = self::get_image_array();

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
     * images file name and size are separated by *.
     *
     * @param bool $imageSizesOnly
     * @param bool $getIds
     */
    public static function get_image_array(?bool $imageSizesOnly = false, ?bool $getIds = false): array
    {
        $array = [];
        $secondHandProducts = SecondHandProduct::get()
            ->filter(['AllowPurchase' => 1])
        ;
        $folder = null;
        foreach ($secondHandProducts as $secondHandProduct) {
            if (! $folder) {
                $folderName = $secondHandProduct->getFolderName();
                if(!$folderName) {
                    $folderName = 'second-hand-images';
                }
                $folder = Folder::find_or_make($folderName);
            }
            if ($folder) {
                $arrayInner = $secondHandProduct->getArrayOfImages();
                foreach ($arrayInner as $imageID => $image) {
                    // if ($image->ParentID !== $folder->ID) {
                    //     $secondHandProduct->fixImageFileNames();
                    // }

                    $filename = $image->getFilename();
                    $location = Controller::join_links(ASSETS_PATH, $filename);
                    if (! isset($array[$secondHandProduct->InternalItemID])) {
                        $array[$secondHandProduct->InternalItemID] = [];
                    }
                    $fileSize = 0;
                    if (file_exists($location)) {
                        $fileSize = filesize($location);
                    }
                    if ($getIds) {
                        $array[$secondHandProduct->InternalItemID][$imageID] = $image->Name . self::SIZE_SEPARATOR . $fileSize;
                    } elseif ($imageSizesOnly) {
                        $array[$secondHandProduct->InternalItemID][] = $fileSize;
                    } else {
                        $array[$secondHandProduct->InternalItemID][] = $image->Name . self::SIZE_SEPARATOR . $fileSize;
                    }
                }
            }
        }

        return $array;
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
            $json = str_replace(["\t", "\r", "\n"], [' ', ' ', ' '], (string) $json);
            $json = preg_replace('#\s\s+#', ' ', (string) $json);
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
            ->disableCache()
        ;
        $response->output();
        die();
    }

    /**
     * @param DataObject $currentObject the object we are exporting
     * @param array      $relations     the array of fields to be added
     */
    protected function addRelations($currentObject, array $relations): array
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
