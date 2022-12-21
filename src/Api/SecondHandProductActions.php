<?php

namespace Sunnysideup\EcommerceSecondHandProduct\Api;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Security\Security;
use SilverStripe\Versioned\Versioned;
use Sunnysideup\EcommerceSecondHandProduct\Model\SecondHandArchive;
use Sunnysideup\EcommerceSecondHandProduct\SecondHandProduct;
use SilverStripe\ORM\DB;

use Exception;


class SecondHandProductActions
{


    public static function quick_enable($buyable, ?string $databaseName = '')
    {
        return self::quick_disable_or_enable(true, $buyable, null, $databaseName);
    }

    public static function quick_disable($buyable, ?int $archivedByID = 0, ?string $databaseName = '')
    {
        self::quick_disable_or_enable(false, $buyable, $archivedByID, $databaseName);
    }

    public static function quick_disable_or_enable(bool $enable, $buyable, ?int $archivedByID = 0, ?string $databaseName = '')
    {
        if ($buyable && $buyable->exist() && $buyable->InternalItemID) {

            $dbName = $databaseName ? $databaseName : DB::get_conn()->getSelectedDatabase();

            foreach(['', '_Live'] as $ext) {
                if($enable === false) {
                    $archivedByLine = '';
                    if ($archivedByID) {
                        $archivedByLine = 'SecondHandProduct'.$ext.'.ArchivedByID = '.$archivedByID.',';
                    }
                    $dateItemSoldLine = '';
                    if ($enable !== true) {
                        $dateItemSoldLine = '' . $dbName . '."SecondHandProduct'.$ext.'"."DateItemWasSold" = \''.date('Y-m-d').'\',';
                    }
                } else {
                    $archivedByLine = 'SecondHandProduct'.$ext.'.ArchivedByID = 0,';
                    $dateItemSoldLine = '' . $dbName . '."SecondHandProduct'.$ext.'"."DateItemWasSold" = \'\',';
                }
                $zeroOrOne = 0;
                if($enable) {
                    $zeroOrOne = 1;
                }
                $sql = '
                    UPDATE ' . $dbName . '.Product'.$ext.'
                        INNER JOIN ' . $dbName . '.SiteTree'.$ext.'
                            ON ' . $dbName . '.SiteTree'.$ext.'.ID = ' . $dbName . '.Product'.$ext.'.ID
                        INNER JOIN ' . $dbName . '.SecondHandProduct'.$ext.'
                            ON ' . $dbName . '.SecondHandProduct'.$ext.'.ID = ' . $dbName . '.Product'.$ext.'.ID

                    SET
                        '.$dateItemSoldLine.'
                        '.$archivedByLine.'
                        ' . $dbName . '."Product'.$ext.'"."FeaturedProduct" = '.($zerOrOne ? 0 : 0).',
                        ' . $dbName . '."Product'.$ext.'"."AllowPurchase" = '.$zerOrOne.',
                        ' . $dbName . '."SiteTree'.$ext.'"."ShowInMenus" = '.$zerOrOne.',
                        ' . $dbName . '."SiteTree'.$ext.'"."ShowInSearch" = '.$zerOrOne.'

                    WHERE
                        ' . $dbName . '."Product'.$ext.'"."InternalItemID" = \'' . $buyable->InternalItemID . '\'
                    LIMIT 1;
                ';
                DB::query($sql);
            }
        }
    }


    public static function archive(int $id): ?SecondHandArchive
    {
        $secondHandProduct = SecondHandProduct::get_by_id($id);
        if ($secondHandProduct) {
            $internalItemID = $secondHandProduct->InternalItemID;
            try {
                $secondHandProduct->doArchive();
            } catch (Exception $e) {
                user_error('Could not archive '.$secondHandProduct->Title. ' - '.$secondHandProduct->InternalItemID . ' please check dates', E_USER_ERROR);
            }

            return SecondHandArchive::get()->filter(['InternalItemID' => $internalItemID])->first();
        }

        return null;
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
