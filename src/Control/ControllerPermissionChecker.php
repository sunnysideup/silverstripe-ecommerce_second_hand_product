<?php

namespace Sunnysideup\EcommerceSecondHandProduct\Control;




use Sunnysideup\Ecommerce\Model\Address\EcommerceCountry;
use SilverStripe\Security\Permission;
use SilverStripe\Control\Controller;



class ControllerPermissionChecker extends Controller
{


    /**
     * checks that the url is contains the secret code and is coming from the correct IP address (if not set to wildcard)
     * @var array $codesWithIPs
     * @var array $code - ID parameter in URL
     * @return Boolean
     */
    public static function permissionCheck($codesWithIPs, $code)
    {
        //with a code you do not have to be logged in ...
        if (count($codesWithIPs)) {
            $ip = EcommerceCountry::get_ip();
            if ($code) {
                $testIP = isset($codesWithIPs[$code]) ? $codesWithIPs[$code] : false;
                if ($testIP) {
                    if ($testIP === $ip || $testIP === '*') {
                        return true;
                    }
                }
            }
        }
        return Permission::check('ADMIN');
    }
}

