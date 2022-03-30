<?php

namespace Sunnysideup\EcommerceSecondHandProduct\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;

use SilverStripe\Core\Environment;
use Sunnysideup\EcommerceSecondHandProduct\SecondHandProduct;

use Sunnysideup\Ecommerce\Model\OrderItem;

class EcommerceTaskSecondCheckSoldItems extends BuildTask
{
    protected $title = 'Check second hand sold items';

    protected $description = 'Enter product codes for sale to check if they have been marked as sold.';

    public function run($request)
    {
        Environment::increaseTimeLimitTo(600);
        DB::alteration_message(' ================= Started =================  ');
        echo '<p>'.$this->description.'</p>';
        $errors = [];
        if(isset($_POST['codes'])) {
            $codes = $_POST['codes'];
            $codesArray = explode("|||", str_replace(array("\n", "\t", "\r", ","), "|||", $codes));
            foreach($codesArray as $key => $code) {
                $code = trim($code);
                if($code) {
                    $forSale = SecondHandProduct::get()->filter(['InternalItemID' => $code, 'AllowPurchase' => 1])->first();
                    if($forSale) {
                        DB::alteration_message('ERROR WITH '.$code . ' | '.$forSale->Title, 'deleted');
                    }
                } else {
                    unset($codesArray[$key]);
                }
            }
            DB::alteration_message(' ================= Completed =================  ');
            DB::alteration_message('OK: '.print_r(implode(', ', $codesArray),1));
            echo '<p><a href="/dev/tasks/Sunnysideup-EcommerceSecondHandProduct-Tasks-EcommerceTaskSecondCheckSoldItems?">again?</a></p>';
        } else {
            echo '
            <form method="post">
                <h2>Paste Codes Below, separated by new line, tab or comma</h2>
                <textarea name="codes" rows=30 cols=100></textarea>
                <br />
                <br />
                <input type="submit" value="check" />
            </form>
                ';
        }

        echo '<p><a href="/dev/tasks/Sunnysideup-EcommerceSecondHandProduct-Tasks-EcommerceTaskSecondHandSoldCodes">Get a list of items sold on this site</a></p>';

    }
}
