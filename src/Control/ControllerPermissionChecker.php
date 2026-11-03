<?php

namespace Sunnysideup\EcommerceSecondHandProduct\Control;

use Psr\SimpleCache\CacheInterface;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Security\Permission;
use Sunnysideup\Ecommerce\Model\Address\EcommerceCountry;

/**
 * Class \Sunnysideup\EcommerceSecondHandProduct\Control\ControllerPermissionChecker
 *
 */
class ControllerPermissionChecker extends Controller
{
    private const MAX_FAILURES = 20;

    private const FAILURE_WINDOW_IN_SECONDS = 600;

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
        if (self::tokenCheck($codesWithIPs, $code)) {
            return true;
        }

        return Permission::check('ADMIN');
    }

    /**
     * Only checks the secret code (Bearer token) - not the logged in user.
     * Throws a 429 after too many failed attempts from one IP address.
     */
    public static function tokenCheck($codesWithIPs, $code): bool
    {
        //with a code you do not have to be logged in ...
        if (! is_array($codesWithIPs) || 0 === count($codesWithIPs) || ! $code) {
            return false;
        }

        $ip = EcommerceCountry::get_ip();
        $cache = self::getCache();
        $cacheKey = 'secondhandfail' . md5($ip);
        $failures = (int) $cache->get($cacheKey, 0);
        if ($failures >= self::MAX_FAILURES) {
            throw new HTTPResponse_Exception('Too many failed attempts, please try again later', 429);
        }

        $matched = false;
        foreach ($codesWithIPs as $knownCode => $testIP) {
            if (hash_equals((string) $knownCode, (string) $code)) {
                if ($testIP && ($testIP === $ip || '*' === $testIP)) {
                    $matched = true;
                }
            }
        }

        if (! $matched) {
            $cache->set($cacheKey, $failures + 1, self::FAILURE_WINDOW_IN_SECONDS);
        }

        return $matched;
    }

    protected static function getCache(): CacheInterface
    {
        return Injector::inst()->get(CacheInterface::class . '.secondHandThrottle');
    }
}
