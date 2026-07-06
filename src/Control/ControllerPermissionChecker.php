<?php

namespace Sunnysideup\EcommerceSecondHandProduct\Control;

use SilverStripe\Control\Controller;
use SilverStripe\Security\Permission;
use Sunnysideup\Ecommerce\Model\Address\EcommerceCountry;

/**
 * Class \Sunnysideup\EcommerceSecondHandProduct\Control\ControllerPermissionChecker
 *
 */
class ControllerPermissionChecker extends Controller
{
    /**
     * checks that the url is contains the secret code and is coming from the correct IP address (if not set to wildcard).
     *
     * @param mixed $codesWithIPs
     * @param mixed $code
     *
     * @return bool
     */
    public static function permissionCheck($codesWithIPs, $code)
    {
        //with a code you do not have to be logged in ...
        if (count($codesWithIPs) > 0) {
            $ip = EcommerceCountry::get_ip();
            if ($code) {
                $testIP = $codesWithIPs[$code] ?? false;
                if ($testIP && ($testIP === $ip || '*' === $testIP)) {
                    return true;
                }
            }
        }

        return Permission::check('ADMIN');
    }
}
