<?php

namespace Sunnysideup\EcommerceSecondHandProduct\Tasks;

use Symfony\Component\Console\Input\InputInterface;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Command\Command;
use SilverStripe\Core\Environment;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use SilverStripe\Versioned\Versioned;
use Sunnysideup\Ecommerce\Model\OrderItem;
use Sunnysideup\EcommerceSecondHandProduct\SecondHandProduct;

class EcommerceTaskSecondHandSoldCodes extends BuildTask
{
    protected string $title = 'Get a list of all second hand products sold';

    protected static string $description = '';

    protected $fix = true;

    protected $forSale = false;

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        Environment::increaseTimeLimitTo(600);
        $output->writeln('<p><a href="/dev/tasks/Sunnysideup-EcommerceSecondHandProduct-Tasks-EcommerceTaskSecondCheckSoldItems">Check Products Codes</a></p>');
        DB::alteration_message(' ================= Sold =================  ');
        $ids = OrderItem::get()->filter(['BuyableClassName' => SecondHandProduct::class])->column('BuyableID');
        $products = SecondHandProduct::get()->filterAny(['AllowPurchase' => 0, 'ID' => $ids]);
        foreach ($products as $product) {
            if ($product->AllowPurchase) {
                DB::alteration_message('<a href="/' . $product->getCMSEditLink() . '">ERROR WITH ' . $product->InternalItemID . ' | ' . $product->Title . '</a>', 'deleted');
                $this->markAsSold($product);
            } else {
                DB::alteration_message($product->InternalItemID);
            }
        }

        DB::alteration_message(' ================= For Sale =================  ');
        return Command::SUCCESS;
    }

    protected function markAsSold($product)
    {
        if ($this->fix) {
            $product->AllowPurchase = 0;
            $product->writeToStage(Versioned::DRAFT);
            $product->publishRecursive();
        }
    }
}
