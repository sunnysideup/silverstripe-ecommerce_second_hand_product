<?php
namespace Sunnysideup\EcommerceSecondHandProduct\Api;

use SilverStripe\Admin\ModelAdmin;
use SilverStripe\CMS\Controllers\CMSMain;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldExportButton;
use SilverStripe\Security\Security;
use SilverStripe\Versioned\Versioned;
use SilverStripe\View\Requirements;
use Sunnysideup\Ecommerce\Api\ClassHelpers;
use Sunnysideup\Ecommerce\Config\EcommerceConfigClassNames;
use Sunnysideup\Ecommerce\Traits\EcommerceModelAdminTrait;
use Sunnysideup\EcommerceSecondHandProduct\Forms\Gridfield\Configs\GridFieldEditOriginalPageConfigSecondHandPage;
use Sunnysideup\EcommerceSecondHandProduct\Model\SecondHandArchive;
use Sunnysideup\EcommerceSecondHandProduct\SecondHandProduct;
use Sunnysideup\GoogleAddressField\GoogleAddressField;
class SecondHandProductActions
{
    public static function archive(int $id) : ?SecondHandArchive
    {
        $secondHandProduct = SecondHandProduct::get_by_id($id);
        $currentMember = Security::getCurrentUser();
        $secondHandProduct->ArchivedByID = $currentMember->ID;
        $internalItemID = $secondHandProduct->InternalItemID;
        if (is_a($secondHandProduct, EcommerceConfigClassNames::getName(SiteTree::class))) {
            $secondHandProduct->write();
            $secondHandProduct->publishRecursive();
            $secondHandProduct->deleteFromStage('Live');
            $secondHandProduct->deleteFromStage('Stage');
        } elseif ($secondHandProduct) {
            $secondHandProduct->write();
            $secondHandProduct->delete();
        }
        return SecondHandArchive::get()->filter(['InternalItemID' => $internalItemID])->first();
    }

    public static function restore(int $id)
    {
        $restoredPage = Versioned::get_latest_version(SiteTree::class, $id);
        $parentID = $restoredPage->ParentID;
        if ($parentID) {
            $this->ensureParentHasVersion($parentID);
            if (! $restoredPage) {
                return new HTTPResponse("SiteTree #{$id} not found", 400);
            }
            $restoredPage = $restoredPage->doRestoreToStage();
            return $restoredPage;
        }
    }

    /**
     * little hack to fix parent if it is not versioned into versions table.
     *
     * @param mixed $parentID
     */
    public function ensureParentHasVersion($parentID)
    {
        $parentPage = Versioned::get_latest_version(SiteTree::class, $parentID);
        if (! $parentPage) {
            $parentPage = SiteTree::get_by_id($parentID);
            if ($parentPage) {
                $parentPage->writeToStage(Versioned::DRAFT);
                $parentPage->publishRecursive();
            }
        }
    }

}
