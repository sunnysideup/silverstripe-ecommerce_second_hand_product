<?php

namespace Sunnysideup\EcommerceSecondHandProduct\Control;

use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Environment;
use SilverStripe\GraphQL\Controller;
use Sunnysideup\EcommerceSecondHandProduct\Control\ExportSecondHandProducts;
use Sunnysideup\EcommerceSecondHandProduct\Config\SecondHandConfig;
use Sunnysideup\EcommerceSecondHandProduct\Control\ControllerPermissionChecker;

trait SecondHandControllerTrait
{
    private const string ENV_SS_SECOND_HAND_SECRET_CODE = 'SS_SECOND_HAND_SECRET_CODE';

    protected function init()
    {
        parent::init();

        if (! $this->MyPermissionCheck()) {
            throw new HTTPResponse_Exception('You do not have access', 401);
        }
    }

    /**
     * @return bool
     */
    public function MyPermissionCheck(): bool
    {
        $codesWithIPs = SecondHandConfig::getSecretCodesFor(get_class($this));
        if (empty($codesWithIPs)) {
            return false;
        }

        $code = $this->extractSecretCode();
        return ControllerPermissionChecker::permissionCheck($codesWithIPs, $code);
    }

    private function extractSecretCode(): ?string
    {
        $authorizationHeader = $this->getRequest()->getHeader('Authorization');
        if (preg_match('/Bearer\s(\S+)/', $authorizationHeader, $matches)) {
            return $matches[1];
        }
        return null;
    }
}
