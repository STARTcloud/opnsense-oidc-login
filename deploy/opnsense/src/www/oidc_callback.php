<?php

require_once("config.inc");
require_once("auth.inc");

use OPNsense\Core\ACL;
use OPNsense\OIDCLogin\Client;
use OPNsense\OIDCLogin\Sessions;

$client = new Client();
$client->startSession();
$outcome = 'failed';

try {
    $pending = $_SESSION['oidclogin'] ?? null;
    unset($_SESSION['oidclogin']);
    if (!$client->enabled()) {
        throw new \RuntimeException('sign-in link is disabled');
    }
    if (!is_array($pending) || !hash_equals((string)$pending['state'], (string)($_GET['state'] ?? ''))) {
        throw new \RuntimeException('state mismatch');
    }
    if (isset($_GET['iss']) && rtrim((string)$_GET['iss'], '/') !== rtrim($client->setting('Issuer'), '/')) {
        throw new \RuntimeException('authorization response names another issuer');
    }
    if (empty($_GET['code'])) {
        throw new \RuntimeException('provider answered ' . preg_replace(
            '/[^A-Za-z0-9_ .-]/',
            '',
            (string)($_GET['error'] ?? 'no code') . ' ' . (string)($_GET['error_description'] ?? '')
        ));
    }
    $document = $client->discovery();
    $tokens = $client->exchange($document, (string)$_GET['code'], (string)$pending['verifier']);
    $claims = $client->claims($document, $tokens, (string)$pending['nonce']);
    $subject = (string)($claims['sub'] ?? '');
    $session = (string)($claims['sid'] ?? '');
    $claim = $client->setting('UsernameClaim');
    if (!isset($claims[$claim])) {
        $claims += $client->userinfo($document, $tokens, $subject);
    }
    $claimed = is_scalar($claims[$claim] ?? null) ? (string)$claims[$claim] : '';
    $username = $client->localUser($claimed);
    if ($username === null) {
        $outcome = 'refused';
        throw new \RuntimeException(sprintf(
            "no enabled local user mapped to '%s' (claims: %s)",
            preg_replace('/[^A-Za-z0-9_@.-]/', '', $claimed),
            preg_replace('/[^A-Za-z0-9_ ]/', '', implode(' ', array_keys($claims)))
        ));
    }
    session_regenerate_id();
    $_SESSION['Username'] = $username;
    $_SESSION['last_access'] = time();
    $_SESSION['protocol'] = $client->protocol();
    $handle = (new Sessions())->remember([
        'session' => session_id(),
        'sid' => $session,
        'sub' => $subject,
        'id_token' => (string)$tokens['id_token'],
        'access_token' => (string)($tokens['access_token'] ?? ''),
    ]);
    setcookie(Sessions::COOKIE, $handle, [
        'path' => '/',
        'secure' => $client->protocol() == 'https',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    if (!isset($config['system']['webgui']['quietlogin'])) {
        auth_log(sprintf("Successful login for user '%s' from: %s", $username, $_SERVER['REMOTE_ADDR']), LOG_NOTICE);
    }
    $target = (string)$pending['redir'];
    if ($target === '') {
        $target = '/' . (new ACL())->getLandingPage($username);
    }
    session_write_close();
    header('Location: ' . $target);
} catch (\Throwable $error) {
    auth_log(sprintf("Web GUI OIDC sign-in error from %s: %s", $_SERVER['REMOTE_ADDR'], $error->getMessage()));
    session_write_close();
    header('Location: /?oidc_error=' . $outcome);
}
exit;
