<?php

namespace Sunnysideup\EcommerceSecondHandProduct\Tasks;

use Symfony\Component\Console\Input\InputInterface;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Command\Command;
use Exception;
use SilverStripe\Core\Environment;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use SilverStripe\Versioned\Versioned;
use Sunnysideup\EcommerceSecondHandProduct\SecondHandProduct;

class EcommerceTaskSecondHandPublishAll extends BuildTask
{
    protected string $title = '(Re)publish all second hand products';

    protected static string $description = 'Go through all second hand products that are for sale and re-publish them...';

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        Environment::increaseTimeLimitTo(600);
        $products = SecondHandProduct::get()->filter(['AllowPurchase' => 1]);
        foreach ($products as $product) {
            DB::alteration_message('Publish: ' . $product->Title . ' - ' . $product->InternalItemID);

            try {
                $product->writeToStage(Versioned::DRAFT);
                $product->publishRecursive();
            } catch (Exception $exception) {
                DB::alteration_message('Caught exception, could not publish ' . $exception->getMessage(), 'deleted');
            }
        }
        DB::alteration_message(' ================= Completed =================  ');
        return Command::SUCCESS;
    }
}
