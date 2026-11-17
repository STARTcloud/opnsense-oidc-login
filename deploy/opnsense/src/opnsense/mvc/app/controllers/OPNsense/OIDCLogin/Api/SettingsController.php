<?php
namespace OPNsense\OIDCLogin\Api;

use \OPNsense\Base\ApiMutableModelControllerBase;

class SettingsController extends ApiMutableModelControllerBase
{
    protected static $internalModelClass = 'OPNsense\OIDCLogin\OIDCLogin';
    protected static $internalModelName = 'oidclogin';

    public function testAction()
    {
        try {
            $client = new \OPNsense\OIDCLogin\Client();
            $document = $client->discovery();
            return [
                'status' => 'ok',
                'message' => sprintf(
                    '%s answers and publishes %d signing key(s).',
                    parse_url($document['issuer'], PHP_URL_HOST),
                    count($client->keys($document))
                ),
            ];
        } catch (\Throwable $error) {
            return ['status' => 'failed', 'message' => $error->getMessage()];
        }
    }

    public function searchUserAction()
    {
        return $this->searchBase("users.user", null, "username");
    }

    public function getUserAction($uuid = null)
    {
        return $this->getBase("user", "users.user", $uuid);
    }

    public function addUserAction()
    {
        return $this->addBase("user", "users.user");
    }

    public function setUserAction($uuid)
    {
        return $this->setBase("user", "users.user", $uuid);
    }

    public function delUserAction($uuid)
    {
        return $this->delBase("users.user", $uuid);
    }

    public function toggleUserAction($uuid, $enabled = null)
    {
        return $this->toggleBase("users.user", $uuid, $enabled);
    }
}
