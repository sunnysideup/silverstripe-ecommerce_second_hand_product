<?php

namespace Sunnysideup\EcommerceSecondHandProduct\Api;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Security\Security;
use SilverStripe\Versioned\Versioned;
use Sunnysideup\EcommerceSecondHandProduct\Model\SecondHandArchive;
use Sunnysideup\EcommerceSecondHandProduct\SecondHandProduct;

class SecondHandProductActions
{
    public static function archive(int $id): ?SecondHandArchive
    {
        $secondHandProduct = SecondHandProduct::get_by_id($id);
        if ($secondHandProduct) {
            $currentMember = Security::getCurrentUser();
            if ($currentMember) {
                $secondHandProduct->ArchivedByID = $currentMember->ID;
            }

            $internalItemID = $secondHandProduct->InternalItemID;
            if ($secondHandProduct->hasMethod('publishRecursive')) {
                $secondHandProduct->writeToStage(Versioned::DRAFT);
                $secondHandProduct->publishRecursive();
                $secondHandProduct->deleteFromStage(Versioned::DRAFT);
                $secondHandProduct->deleteFromStage(Versioned::LIVE);
                $secondHandProduct->delete();
            } elseif ($secondHandProduct) {
                $secondHandProduct->write();
                $secondHandProduct->delete();
            }
        }

        return SecondHandArchive::get()->filter(['InternalItemID' => $internalItemID])->first();
    }

    public static function restore(int $id)
    {
        $restoredPage = Versioned::get_latest_version(SiteTree::class, $id);
        $parentID = $restoredPage->ParentID;
        if ($parentID) {
            self::ensureParentHasVersion($parentID);
            if (! $restoredPage) {
                return new HTTPResponse("SiteTree #{$id} not found", 400);
            }

            return $restoredPage->doRestoreToStage();
        }
    }

    /**
     * little hack to fix parent if it is not versioned into versions table.
     *
     * @param mixed $parentID
     */
    protected static function ensureParentHasVersion($parentID)
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
