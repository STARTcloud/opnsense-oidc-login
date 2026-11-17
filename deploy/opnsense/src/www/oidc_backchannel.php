<?php

require_once("config.inc");
require_once("auth.inc");

use OPNsense\OIDCLogin\Client;
use OPNsense\OIDCLogin\Sessions;

header('Cache-Control: no-store');

try {
    $client = new Client();
    if (!$client->enabled()) {
        throw new \RuntimeException('sign-in link is disabled');
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['logout_token'])) {
        throw new \RuntimeException('no logout token posted');
    }
    $claims = $client->logoutClaims($client->discovery(), (string)$_POST['logout_token']);
    $ended = (new Sessions())->end((string)($claims['sid'] ?? ''), (string)($claims['sub'] ?? ''));
    auth_log(sprintf("Web GUI OIDC back-channel logout from %s ended %d session(s)", $_SERVER['REMOTE_ADDR'], $ended), LOG_NOTICE);
    http_response_code(200);
} catch (\Throwable $error) {
    auth_log(sprintf("Web GUI OIDC back-channel logout error from %s: %s", $_SERVER['REMOTE_ADDR'], $error->getMessage()));
    http_response_code(400);
}
exit;
