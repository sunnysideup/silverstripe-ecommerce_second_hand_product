<?php

namespace Sunnysideup\EcommerceSecondHandProduct\Control;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Environment;
use SilverStripe\Security\Permission;
use SilverStripe\Security\SecurityToken;
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
     * null = not checked yet.
     */
    private ?bool $secondHandAuthenticatedByToken = null;

    /**
     * @return bool
     */
    public function MyPermissionCheck(): bool
    {
        // the endpoint is disabled entirely if no secret is configured.
        if (empty(SecondHandConfig::getSecretCodesFor(get_class($this)))) {
            return false;
        }

        if ($this->MyTokenCheck()) {
            return true;
        }

        return Permission::check('ADMIN');
    }

    /**
     * true if the request is authenticated using the secret code (Bearer token)
     * rather than a logged-in user session.
     */
    public function MyTokenCheck(): bool
    {
        if (null === $this->secondHandAuthenticatedByToken) {
            $codesWithIPs = SecondHandConfig::getSecretCodesFor(get_class($this));
            $this->secondHandAuthenticatedByToken = empty($codesWithIPs) ? false : ControllerPermissionChecker::tokenCheck($codesWithIPs, $this->extractSecretCode());
        }

        return $this->secondHandAuthenticatedByToken;
    }

    /**
     * Session based (logged-in member) authentication is open to CSRF,
     * so write actions must carry a valid security token unless authenticated by secret code.
     */
    protected function hasValidWriteAuthentication(HTTPRequest $request): bool
    {
        if ($this->MyTokenCheck()) {
            return true;
        }

        return Permission::check('ADMIN') && SecurityToken::inst()->checkRequest($request);
    }

    private function extractSecretCode(): ?string
    {
        $authorizationHeader = (string) $this->getRequest()->getHeader('Authorization');
        if (preg_match('/Bearer\s(\S+)/', $authorizationHeader, $matches)) {
            return $matches[1];
        }
        return null;
    }
}
