<?php

namespace Sunnysideup\EcommerceSecondHandProduct\Tasks;

use SilverStripe\Core\Environment;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use SilverStripe\PolyExecution\PolyOutput;
use SilverStripe\Versioned\Versioned;
use Sunnysideup\EcommerceSecondHandProduct\SecondHandProduct;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

class EcommerceTaskSecondCheckSoldItems extends BuildTask
{
    protected string $title = 'Check second hand sold items';

    protected static string $description = 'Enter product codes for sale to check if they have been marked as sold.';

    protected static string $commandName = 'ecommerce:secondhand:checksolditems';

    public function getOptions(): array
    {
        return [
            new InputOption('codes', 'c', InputOption::VALUE_REQUIRED, 'Product codes separated by new line, tab or comma'),
            new InputOption('markassold', 'm', InputOption::VALUE_NONE, 'Mark as sold'),
        ];
    }

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        Environment::increaseTimeLimitTo(600);
        $output->writeln(' ================= Started =================  ');
        $output->writeln('<p>' . $this->description . '</p>');
        $errors = [];
        $codes = $input->getOption('codes');
        if ($codes) {
            $codesArray = explode('|||', str_replace(["\n", "\t", "\r", ','], '|||', $codes));
            foreach ($codesArray as $key => $code) {
                $code = trim((string) $code);
                if ($code !== '' && $code !== '0') {
                    $forSaleProduct = SecondHandProduct::get()->filter(['InternalItemID' => $code, 'AllowPurchase' => 1])->first();
                    if ($forSaleProduct) {
                        if ($input->getOption('markassold')) {
                            $forSaleProduct->AllowPurchase = false;
                        }

                        if ($forSaleProduct->DateItemWasSold || $input->getOption('markassold')) {
                            $forSaleProduct->writeToStage(Versioned::DRAFT);
                            $forSaleProduct->publishRecursive();
                        }

                        if ($forSaleProduct->AllowPurchase) {
                            $output->writeln('<error><a href="/' . $forSaleProduct->getCMSEditLink() . '">ERROR WITH ' . $code . ' | ' . $forSaleProduct->Title . '</a></error>');
                        }
                    }
                } else {
                    unset($codesArray[$key]);
                }
            }

            $output->writeln(' ================= Completed =================  ');
            $output->writeln('OK: ' . print_r(implode(', ', $codesArray), 1));
            $output->writeForHtml('<p><a href="/dev/tasks/Sunnysideup-EcommerceSecondHandProduct-Tasks-EcommerceTaskSecondCheckSoldItems?">again?</a></p>');
        } else {
            $output->writeForHtml('
            <form method="post">
                <h2>Paste Codes Below, separated by new line, tab or comma</h2>
                <textarea name="codes" rows=30 cols=100></textarea>
                <input type="checkbox" name="markassold" value="1" /> mark as sold
                <input type="submit" value="check" />
            </form>
            ');
        }

        $output->writeForHtml('<p><a href="/dev/tasks/Sunnysideup-EcommerceSecondHandProduct-Tasks-EcommerceTaskSecondHandSoldCodes">Get a list of items sold on this site</a></p>');
        return Command::SUCCESS;
    }
}
