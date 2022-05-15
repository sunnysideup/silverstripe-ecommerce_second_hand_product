<?php

namespace Sunnysideup\EcommerceSecondHandProduct\Control;

use SilverStripe\Control\Controller;
use SilverStripe\Security\Permission;
use Sunnysideup\Ecommerce\Model\Address\EcommerceCountry;

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
        if (count($codesWithIPs)) {
            $ip = EcommerceCountry::get_ip();
            if ($code) {
                $testIP = isset($codesWithIPs[$code]) ? $codesWithIPs[$code] : false;
                if ($testIP) {
                    if ($testIP === $ip || '*' === $testIP) {
                        return true;
                    }
                }
            }
        }

        return Permission::check('ADMIN');
    }
}
