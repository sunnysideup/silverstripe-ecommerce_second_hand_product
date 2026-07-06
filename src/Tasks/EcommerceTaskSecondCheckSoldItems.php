<?php

namespace Sunnysideup\EcommerceSecondHandProduct\Tasks;

use Symfony\Component\Console\Input\InputInterface;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Command\Command;
use SilverStripe\Core\Environment;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use SilverStripe\Versioned\Versioned;
use Sunnysideup\EcommerceSecondHandProduct\SecondHandProduct;

class EcommerceTaskSecondCheckSoldItems extends BuildTask
{
    protected string $title = 'Check second hand sold items';

    protected static string $description = 'Enter product codes for sale to check if they have been marked as sold.';

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        Environment::increaseTimeLimitTo(600);
        DB::alteration_message(' ================= Started =================  ');
        echo '<p>' . $this->description . '</p>';
        $errors = [];
        if (isset($_POST['codes'])) {
            $codes = $_POST['codes'];
            $codesArray = explode('|||', str_replace(["\n", "\t", "\r", ','], '|||', $codes));
            foreach ($codesArray as $key => $code) {
                $code = trim((string) $code);
                if ($code !== '' && $code !== '0') {
                    $forSaleProduct = SecondHandProduct::get()->filter(['InternalItemID' => $code, 'AllowPurchase' => 1])->first();
                    if ($forSaleProduct) {
                        if (! empty($_POST['markassold'])) {
                            $forSaleProduct->AllowPurchase = false;
                        }

                        if ($forSaleProduct->DateItemWasSold || ! empty($_POST['markassold'])) {
                            $forSaleProduct->writeToStage(Versioned::DRAFT);
                            $forSaleProduct->publishRecursive();
                        }

                        if ($forSaleProduct->AllowPurchase) {
                            DB::alteration_message('<a href="/' . $forSaleProduct->getCMSEditLink() . '">ERROR WITH ' . $code . ' | ' . $forSaleProduct->Title . '</a>', 'deleted');
                        }
                    }
                } else {
                    unset($codesArray[$key]);
                }
            }

            DB::alteration_message(' ================= Completed =================  ');
            DB::alteration_message('OK: ' . print_r(implode(', ', $codesArray), 1));
            $output->writeln('<p><a href="/dev/tasks/Sunnysideup-EcommerceSecondHandProduct-Tasks-EcommerceTaskSecondCheckSoldItems?">again?</a></p>');
        } else {
            $output->writeln('            <form method="post">                <h2>Paste Codes Below, separated by new line, tab or comma</h2>                <textarea name="codes" rows=30 cols=100></textarea>                                                <input type="checkbox" name="markassold" value="1" /> mark as sold                                                <input type="submit" value="check" />            </form>                ');
        }

        $output->writeln('<p><a href="/dev/tasks/Sunnysideup-EcommerceSecondHandProduct-Tasks-EcommerceTaskSecondHandSoldCodes">Get a list of items sold on this site</a></p>');
        return Command::SUCCESS;
    }
}
