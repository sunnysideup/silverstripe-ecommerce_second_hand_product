<?php

namespace Sunnysideup\EcommerceSecondHandProduct\Tasks;

use Exception;
use SilverStripe\Assets\File;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Environment;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use SilverStripe\PolyExecution\PolyOutput;
use SilverStripe\Versioned\Versioned;
use Sunnysideup\EcommerceSecondHandProduct\Model\SecondHandArchive;
use Sunnysideup\EcommerceSecondHandProduct\SecondHandProduct;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

class EcommerceTaskSecondHandDeleteOldImages extends BuildTask
{
    protected string $title = 'Delete old images';

    protected static string $description = 'Go through all archived second hand images that are older than three months and delete the related images.';

    protected static string $commandName = 'ecommerce:secondhand:deleteoldimages';

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        Environment::increaseTimeLimitTo(600);
        $archivedProducts = SecondHandArchive::get()
            ->filter(['Created:LessThan' => date('Y-m-d', strtotime('-90 days')) . ' 00:00:00', 'ImageID:GreaterThan' => 0])
            ->limit(300);
        foreach ($archivedProducts as $archivedProduct) {
            $output->writeln('Deleting images for: ' . $archivedProduct->Title . ' - ' . $archivedProduct->InternalItemID);
            if(SecondHandProduct::get()->filter(['InternalItemID' => $archivedProduct->InternalItemID])->exists()) {
                $output->writeln('<error>ERROR - product exists for: ' . $archivedProduct->Title . ' - ' . $archivedProduct->InternalItemID . '</error>');
            } else {
                self::delete_file($archivedProduct->Image(), $output);
                foreach($archivedProduct->AdditionalImages() as $image) {
                    self::delete_file($image, $output);
                }

                $archivedProduct->ImageID = 0;
                $archivedProduct->write();
            }
        }

        $output->writeln(' ================= Completed =================  ');
        return Command::SUCCESS;
    }

    public static function delete_file($file, PolyOutput $output)
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
                $output->writeln('<error>Caught exception: ' . $exception->getMessage() . '</error>');
            }

            $file->deleteFromStage(Versioned::DRAFT);
            $file->deleteFromStage(Versioned::LIVE);
            $fullName = Controller::join_links(ASSETS_PATH, $fileName);
            if (file_exists($fullName)) {
                unlink($fullName);
                if (file_exists($fullName)) {
                    // @TODO (SS6 upgrade)
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
