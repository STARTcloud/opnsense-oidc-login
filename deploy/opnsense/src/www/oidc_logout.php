<?php

require_once("config.inc");
require_once("auth.inc");

use OPNsense\OIDCLogin\Client;
use OPNsense\OIDCLogin\Sessions;

$client = new Client();
$sessions = new Sessions();
$handle = (string)($_COOKIE[Sessions::COOKIE] ?? '');
$entry = $sessions->find($handle);
$sessions->forget($handle);
setcookie(Sessions::COOKIE, '', [
    'expires' => 1,
    'path' => '/',
    'secure' => $client->protocol() == 'https',
    'httponly' => true,
    'samesite' => 'Lax',
]);
$target = '/';

try {
    if ($entry !== null && $client->enabled() && $client->setting('ProviderLogout') === '1') {
        $document = $client->discovery();
        try {
            $client->revoke($document, (string)($entry['access_token'] ?? ''));
        } catch (\Throwable $error) {
            auth_log(sprintf("Web GUI OIDC token revocation error from %s: %s", $_SERVER['REMOTE_ADDR'], $error->getMessage()));
        }
        $target = $client->endSessionUrl($document, (string)($entry['id_token'] ?? '')) ?? '/';
    }
} catch (\Throwable $error) {
    auth_log(sprintf("Web GUI OIDC sign-out error from %s: %s", $_SERVER['REMOTE_ADDR'], $error->getMessage()));
}
header('Location: ' . $target);
exit;
