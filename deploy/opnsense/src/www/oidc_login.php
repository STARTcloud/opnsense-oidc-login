<?php

require_once("config.inc");
require_once("auth.inc");

use OPNsense\Core\SanitizeFilter;
use OPNsense\OIDCLogin\Client;

$client = new Client();
$client->startSession();

try {
    if (!$client->enabled()) {
        throw new \RuntimeException('sign-in link is disabled');
    }
    $document = $client->discovery();
    $state = bin2hex(random_bytes(16));
    $nonce = bin2hex(random_bytes(16));
    $verifier = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    $_SESSION['oidclogin'] = [
        'state' => $state,
        'nonce' => $nonce,
        'verifier' => $verifier,
        'redir' => (string)(new SanitizeFilter())->sanitize($_GET['redir'] ?? '', 'local_uri'),
    ];
    session_write_close();
    header('Location: ' . $client->authorizationUrl($document, $state, $nonce, $verifier));
} catch (\Throwable $error) {
    auth_log(sprintf("Web GUI OIDC sign-in error from %s: %s", $_SERVER['REMOTE_ADDR'], $error->getMessage()));
    session_write_close();
    header('Location: /?oidc_error=failed');
}
exit;
