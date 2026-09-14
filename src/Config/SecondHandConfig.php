<?php

namespace Sunnysideup\EcommerceSecondHandProduct\Config;

use SilverStripe\Core\Environment;
use SilverStripe\Core\Config\Config;

abstract class SecondHandConfig
{
    private const string ENV_SS_SECOND_HAND_SECRET_CODE = 'SS_SECOND_HAND_SECRET_CODE';

    public static function getSecretCodesFor(string $className): array
    {
        // new way
        $secretCode = Environment::getEnv(self::ENV_SS_SECOND_HAND_SECRET_CODE);
        if ($secretCode) {
            return [
                $secretCode => '*',
            ];
        }
        // old way
        $codes = Config::inst()->get($className, 'secret_codes');
        return $codes ?? [];
    }
}
