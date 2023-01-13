<?php

namespace Sunnysideup\EcommerceSecondHandProduct\Tasks;

use SilverStripe\Core\Environment;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use Sunnysideup\EcommerceSecondHandProduct\Api\SecondHandProductActions;
use Sunnysideup\EcommerceSecondHandProduct\Model\SecondHandArchive;
use Sunnysideup\EcommerceSecondHandProduct\SecondHandProduct;

class EcommerceTaskSecondHandRemoveOldies extends BuildTask
{
    private const DAYS_AGO = 360;
    protected $title = 'Remove old second hand products that are not for sale';

    protected $description = 'Go through all the second hand products that are not for sale and entered more than year ago and archives them.';

    public function run($request)
    {
        Environment::increaseTimeLimitTo(600);
        $timeFilter = [
            'Created:LessThan' => date('Y-m-d', strtotime('-' . self::DAYS_AGO . ' days')) . ' 00:00:00',
        ];
        $filter = ['AllowPurchase' => 0] + $timeFilter;
        DB::alteration_message('Filter is: ' . print_r($filter, 1));
        $products = SecondHandProduct::get()->filter($filter)->limit(300);

        foreach ($products as $product) {
            DB::alteration_message(
                '
                Archiving: ' . $product->Title .
                ' - ' . $product->InternalItemID .
                ' - ' . ($product->AllowPurchase ? 'YES' : 'NO')
            );

            try {
                $this->autoArchiveProduct($product);
            } catch (\Exception $exception) {
                DB::alteration_message('Caught exception, could not delete item ' . $exception->getMessage(), 'deleted');
            }
        }

        DB::alteration_message(' ================= Completed =================  ');
    }

    protected function autoArchiveProduct(SecondHandProduct $obj)
    {
        $archivedRecord = SecondHandProductActions::archive($obj->ID);
        if ($archivedRecord && $archivedRecord instanceof SecondHandArchive) {
            $archivedRecord->AutoArchive = true;
            $archivedRecord->write();
        } else {
            user_error('Could not archive ' . $obj->InternalItemID);
        }
    }
}
