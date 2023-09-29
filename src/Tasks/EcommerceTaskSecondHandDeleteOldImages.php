<?php

namespace Sunnysideup\EcommerceSecondHandProduct\Tasks;

use Exception;
use SilverStripe\Assets\File;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Environment;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use SilverStripe\Versioned\Versioned;
use Sunnysideup\EcommerceSecondHandProduct\Model\SecondHandArchive;
use Sunnysideup\EcommerceSecondHandProduct\SecondHandProduct;

class EcommerceTaskSecondHandDeleteOldImages extends BuildTask
{
    protected $title = 'Delete old images';

    protected $description = 'Go through all archived second hand images that are older than three months and delete the related images.';

    public function run($request)
    {
        Environment::increaseTimeLimitTo(600);
        $archivedProducts = SecondHandArchive::get()
            ->filter(['Created:LessThan' => date('Y-m-d', strtotime('-90 days')) . ' 00:00:00', 'ImageID:GreaterThan' => 0])
            ->limit(300);
        foreach ($archivedProducts as $archivedProduct) {
            DB::alteration_message('Deleting images for: ' . $archivedProduct->Title . ' - ' . $archivedProduct->InternalItemID);
            if(SecondHandProduct::get()->filter(['InternalItemID' => $archivedProduct->InternalItemID])->exists()) {
                DB::alteration_message('ERROR - product exists for: ' . $archivedProduct->Title . ' - ' . $archivedProduct->InternalItemID);
            } else {
                $this->delete_file($archivedProduct->Image());
                foreach($archivedProduct->AdditionalImages() as $image) {
                    $this->delete_file($image);
                }
                $archivedProduct->ImageID = 0;
                $archivedProduct->write();
            }
        }

        DB::alteration_message(' ================= Completed =================  ');
    }
    public static function delete_file($file)
    {
        if ($file || ! ($file instanceof File)) {
            $file = File::get()->byID($file);
        }
        if ($file) {
            $fileName = $file->getFilename();
            $id = $file->ID;

            try {
                $file->deleteFile();
            } catch (Exception $exception) {
                DB::alteration_message('Caught exception: ' . $exception->getMessage(), 'deleted');
            }
            $file->deleteFromStage(Versioned::DRAFT);
            $file->deleteFromStage(Versioned::LIVE);
            $fullName = Controller::join_links(ASSETS_PATH, $fileName);
            if (file_exists($fullName)) {
                unlink($fullName);
                if (file_exists($fullName)) {
                    user_error('Could not delete file...' . $fullName);
                } else {
                    DB::query('DELETE FROM File WHERE ID = ' . $id . ' LIMIT 1');
                    DB::query('DELETE FROM File_Live WHERE ID = ' . $id . ' LIMIT 1');
                    DB::query('DELETE FROM File_Versions WHERE RecordID = ' . $id);
                }
            }
        } else {
            //user_error(PHP_EOL . 'ERROR: could not find file to delete ' . PHP_EOL);
        }
    }

}
