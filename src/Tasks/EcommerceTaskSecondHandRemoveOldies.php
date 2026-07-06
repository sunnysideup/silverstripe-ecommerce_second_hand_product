<?php

namespace Sunnysideup\EcommerceSecondHandProduct\Tasks;

use Exception;
use SilverStripe\Core\Environment;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use SilverStripe\PolyExecution\PolyOutput;
use Sunnysideup\EcommerceSecondHandProduct\Api\SecondHandProductActions;
use Sunnysideup\EcommerceSecondHandProduct\Model\SecondHandArchive;
use Sunnysideup\EcommerceSecondHandProduct\SecondHandProduct;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

class EcommerceTaskSecondHandRemoveOldies extends BuildTask
{
    private const int DAYS_AGO = 360;

    protected string $title = 'Remove old second hand products that are not for sale';

    protected static string $description = 'Go through all the second hand products that are not for sale and entered more than year ago and archives them.';

    protected static string $commandName = 'ecommerce:secondhand:removeoldies';

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        Environment::increaseTimeLimitTo(600);
        $timeFilter = [
            'Created:LessThan' => date('Y-m-d', strtotime('-' . self::DAYS_AGO . ' days')) . ' 00:00:00',
        ];
        $filter = ['AllowPurchase' => 0] + $timeFilter;
        $output->writeln('Filter is: ' . print_r($filter, 1));
        $products = SecondHandProduct::get()->filter($filter)->limit(300);
        foreach ($products as $product) {
            $output->writeln(
                '
                Archiving: ' . $product->Title .
                ' - ' . $product->InternalItemID .
                ' - ' . ($product->AllowPurchase ? 'YES' : 'NO')
            );

            try {
                $this->autoArchiveProduct($product);
            } catch (Exception $exception) {
                $output->writeln('<error>Caught exception, could not delete item ' . $exception->getMessage() . '</error>');
            }
        }

        $output->writeln(' ================= Completed =================  ');
        return Command::SUCCESS;
    }

    protected function autoArchiveProduct(SecondHandProduct $obj)
    {
        $archivedRecord = SecondHandProductActions::archive($obj->ID);
        if ($archivedRecord && $archivedRecord instanceof SecondHandArchive) {
            $archivedRecord->AutoArchive = true;
            $archivedRecord->write();
        } else {
            // @TODO (SS6 upgrade)
            user_error('Could not archive ' . $obj->InternalItemID);
        }
    }
}
