<?php

namespace Sunnysideup\EcommerceSecondHandProduct\Tasks;

use SilverStripe\Core\Environment;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use SilverStripe\PolyExecution\PolyOutput;
use SilverStripe\Versioned\Versioned;
use Sunnysideup\Ecommerce\Model\OrderItem;
use Sunnysideup\EcommerceSecondHandProduct\SecondHandProduct;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

class EcommerceTaskSecondHandSoldCodes extends BuildTask
{
    protected string $title = 'Get a list of all second hand products sold';

    protected static string $description = 'Shows a list of all second hand products sold. Can also fix items that are sold but still marked as for sale.';

    protected static string $commandName = 'ecommerce:secondhand:soldcodes';

    public function getOptions(): array
    {
        return [
            new InputOption('fix', 'f', InputOption::VALUE_NONE, 'Mark items as sold'),
        ];
    }

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        Environment::increaseTimeLimitTo(600);
        $output->writeForHtml('<p><a href="/dev/tasks/Sunnysideup-EcommerceSecondHandProduct-Tasks-EcommerceTaskSecondCheckSoldItems">Check Products Codes</a></p>');
        $output->writeln(' ================= Sold =================  ');
        $ids = OrderItem::get()->filter(['BuyableClassName' => SecondHandProduct::class])->column('BuyableID');
        $products = SecondHandProduct::get()->filterAny(['AllowPurchase' => 0, 'ID' => $ids]);
        foreach ($products as $product) {
            if ($product->AllowPurchase) {
                $output->writeln('<error><a href="/' . $product->getCMSEditLink() . '">ERROR WITH ' . $product->InternalItemID . ' | ' . $product->Title . '</a></error>');
                if ($input->getOption('fix')) {
                    $this->markAsSold($product);
                }
            } else {
                $output->writeln($product->InternalItemID);
            }
        }

        $output->writeln(' ================= For Sale =================  ');
        return Command::SUCCESS;
    }

    protected function markAsSold($product)
    {
        $product->AllowPurchase = 0;
        $product->writeToStage(Versioned::DRAFT);
        $product->publishRecursive();
    }
}
