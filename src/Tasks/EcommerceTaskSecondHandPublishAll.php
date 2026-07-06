<?php

declare(strict_types=1);

namespace Sunnysideup\EcommerceSecondHandProduct\Tasks;

use Exception;
use SilverStripe\Core\Environment;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use SilverStripe\PolyExecution\PolyOutput;
use SilverStripe\Versioned\Versioned;
use Sunnysideup\EcommerceSecondHandProduct\SecondHandProduct;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

class EcommerceTaskSecondHandPublishAll extends BuildTask
{
    protected string $title = '(Re)publish all second hand products';

    protected static string $description = 'Go through all second hand products that are for sale and re-publish them...';

    protected static string $commandName = 'ecommerce:secondhand:publishall';

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        Environment::increaseTimeLimitTo(600);
        $products = SecondHandProduct::get()->filter(['AllowPurchase' => 1]);
        foreach ($products as $product) {
            $output->writeln('Publish: ' . $product->Title . ' - ' . $product->InternalItemID);

            try {
                $product->writeToStage(Versioned::DRAFT);
                $product->publishRecursive();
            } catch (Exception $exception) {
                $output->writeln('<error>Caught exception, could not publish ' . $exception->getMessage() . '</error>');
            }
        }

        $output->writeln(' ================= Completed =================  ');
        return Command::SUCCESS;
    }
}
