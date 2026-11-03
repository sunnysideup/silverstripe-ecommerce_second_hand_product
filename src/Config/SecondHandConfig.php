<?php

namespace Sunnysideup\EcommerceSecondHandProduct\Config;

use SilverStripe\Core\Environment;
use SilverStripe\Core\Config\Config;
use Sunnysideup\EcommerceSecondHandProduct\Control\UpdateSecondHandProduct;

abstract class SecondHandConfig
{
    private const string ENV_SS_SECOND_HAND_SECRET_CODE = 'SS_SECOND_HAND_SECRET_CODE';

    /**
     * Optional separate secret for write actions (archive / removefromsale).
     * If not set, the normal secret is used for write actions as well.
     */
    private const string ENV_SS_SECOND_HAND_SECRET_CODE_WRITE = 'SS_SECOND_HAND_SECRET_CODE_WRITE';

    public static function getSecretCodesFor(string $className): array
    {
        // new way
        // the update controller only has write actions so it uses the write secret (if set).
        if (is_a($className, UpdateSecondHandProduct::class, true)) {
            $writeSecretCode = Environment::getEnv(self::ENV_SS_SECOND_HAND_SECRET_CODE_WRITE);
            if ($writeSecretCode) {
                return [
                    $writeSecretCode => '*',
                ];
            }
        }

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
