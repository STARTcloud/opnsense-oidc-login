<?php

namespace OPNsense\OIDCLogin;

use OPNsense\Core\Config;

class Client
{
    private const ALGORITHMS = [
        'RS256' => OPENSSL_ALGO_SHA256,
        'RS384' => OPENSSL_ALGO_SHA384,
        'RS512' => OPENSSL_ALGO_SHA512,
    ];

    private const HASHES = ['RS256' => 'sha256', 'RS384' => 'sha384', 'RS512' => 'sha512'];

    private const LOGOUT_EVENT = 'http://schemas.openid.net/event/backchannel-logout';

    private $model;

    public function __construct()
    {
        $this->model = new OIDCLogin();
    }

    public function enabled(): bool
    {
        return $this->setting('Enabled') === '1';
    }

    public function setting(string $name): string
    {
        return trim((string)$this->model->general->$name);
    }

    public function protocol(): string
    {
        return (string)Config::getInstance()->object()->system->webgui->protocol;
    }

    public function startSession(): void
    {
        if (session_status() == PHP_SESSION_NONE) {
            $current = session_get_cookie_params();
            session_set_cookie_params(
                $current["lifetime"],
                $current["path"],
                null,
                $this->protocol() == "https",
                true
            );
            session_set_cookie_params(["SameSite" => "Lax"]);
            session_start();
        }
    }

    public function baseUri(): string
    {
        return $this->protocol() . '://' . $_SERVER['HTTP_HOST'];
    }

    public function redirectUri(): string
    {
        return $this->baseUri() . '/oidc_callback.php';
    }

    public function discovery(): array
    {
        $issuer = rtrim($this->setting('Issuer'), '/');
        if ($issuer === '' || $this->setting('ClientId') === '') {
            throw new \RuntimeException('issuer or client id not set');
        }
        $document = $this->request($issuer . '/.well-known/openid-configuration');
        if (rtrim((string)($document['issuer'] ?? ''), '/') !== $issuer) {
            throw new \RuntimeException('discovery document names another issuer');
        }
        foreach (['authorization_endpoint', 'token_endpoint', 'jwks_uri'] as $endpoint) {
            if (strpos((string)($document[$endpoint] ?? ''), 'https://') !== 0) {
                throw new \RuntimeException($endpoint . ' missing or not https');
            }
        }
        return $document;
    }

    public function keys(array $document): array
    {
        $keys = [];
        foreach ((array)($this->request($document['jwks_uri'])['keys'] ?? []) as $key) {
            if (is_array($key) && ($key['kty'] ?? '') === 'RSA' && ($key['use'] ?? 'sig') === 'sig') {
                $keys[] = $key;
            }
        }
        if (empty($keys)) {
            throw new \RuntimeException('provider publishes no RSA signing key');
        }
        return $keys;
    }

    public function authorizationUrl(array $document, string $state, string $nonce, string $verifier): string
    {
        $challenge = $this->encode(hash('sha256', $verifier, true));
        $query = [
            'response_type' => 'code',
            'client_id' => $this->setting('ClientId'),
            'redirect_uri' => $this->redirectUri(),
            'scope' => $this->setting('Scopes'),
            'state' => $state,
            'nonce' => $nonce,
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
        ];
        if ($this->setting('Prompt') !== '') {
            $query['prompt'] = $this->setting('Prompt');
        }
        return $document['authorization_endpoint'] . '?' . http_build_query($query);
    }

    public function exchange(array $document, string $code, string $verifier): array
    {
        return $this->request(
            $document['token_endpoint'],
            [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $this->redirectUri(),
                'code_verifier' => $verifier,
            ],
            $this->credentials()
        );
    }

    public function claims(array $document, array $tokens, string $nonce): array
    {
        $token = (string)($tokens['id_token'] ?? '');
        if ($token === '') {
            throw new \RuntimeException('token response carries no ID token');
        }
        [$header, $claims] = $this->verify($document, $token);
        $this->audience($claims, 'ID token');
        if ((int)($claims['exp'] ?? 0) <= time()) {
            throw new \RuntimeException('ID token expired');
        }
        if (!hash_equals($nonce, (string)($claims['nonce'] ?? ''))) {
            throw new \RuntimeException('ID token nonce mismatch');
        }
        if (isset($claims['at_hash']) && !empty($tokens['access_token'])) {
            $digest = hash(self::HASHES[$header['alg']], (string)$tokens['access_token'], true);
            if (!hash_equals($this->encode(substr($digest, 0, intdiv(strlen($digest), 2))), (string)$claims['at_hash'])) {
                throw new \RuntimeException('access token does not match the ID token');
            }
        }
        return $claims;
    }

    public function logoutClaims(array $document, string $token): array
    {
        [, $claims] = $this->verify($document, $token);
        $this->audience($claims, 'logout token');
        if ((int)($claims['iat'] ?? 0) > time() + 300 || (int)($claims['exp'] ?? PHP_INT_MAX) <= time()) {
            throw new \RuntimeException('logout token is not current');
        }
        if (!is_array($claims['events'] ?? null) || !array_key_exists(self::LOGOUT_EVENT, $claims['events'])) {
            throw new \RuntimeException('logout token carries no logout event');
        }
        if (isset($claims['nonce'])) {
            throw new \RuntimeException('logout token carries a nonce');
        }
        if (empty($claims['sid']) && empty($claims['sub'])) {
            throw new \RuntimeException('logout token names no session and no subject');
        }
        return $claims;
    }

    public function userinfo(array $document, array $tokens, string $subject): array
    {
        $endpoint = (string)($document['userinfo_endpoint'] ?? '');
        $token = (string)($tokens['access_token'] ?? '');
        if (strpos($endpoint, 'https://') !== 0 || $token === '') {
            return [];
        }
        $info = $this->request($endpoint, null, null, $token);
        if ($subject === '' || (string)($info['sub'] ?? '') !== $subject) {
            throw new \RuntimeException('UserInfo names another subject');
        }
        return $info;
    }

    public function revoke(array $document, string $token): void
    {
        $endpoint = (string)($document['revocation_endpoint'] ?? '');
        if (strpos($endpoint, 'https://') === 0 && $token !== '') {
            $this->request($endpoint, ['token' => $token, 'token_type_hint' => 'access_token'], $this->credentials());
        }
    }

    public function endSessionUrl(array $document, string $idToken): ?string
    {
        $endpoint = (string)($document['end_session_endpoint'] ?? '');
        if (strpos($endpoint, 'https://') !== 0) {
            return null;
        }
        $query = [
            'client_id' => $this->setting('ClientId'),
            'post_logout_redirect_uri' => $this->baseUri() . '/',
        ];
        if ($idToken !== '') {
            $query['id_token_hint'] = $idToken;
        }
        return $endpoint . '?' . http_build_query($query);
    }

    public function localUser(string $subject): ?string
    {
        $name = '';
        foreach ($this->model->users->user->iterateItems() as $mapping) {
            if ((string)$mapping->enabled === '1' && $subject !== '' && (string)$mapping->subject === $subject) {
                $name = (string)$mapping->username;
                break;
            }
        }
        if ($name === '') {
            return null;
        }
        foreach (Config::getInstance()->object()->system->user as $user) {
            if ((string)$user->name === $name) {
                return empty((string)$user->disabled) ? $name : null;
            }
        }
        return null;
    }

    private function credentials(): string
    {
        $secret = (string)Config::getInstance()->object()->OPNsense->oidclogin->general->ClientSecret;
        return rawurlencode($this->setting('ClientId')) . ':' . rawurlencode($secret);
    }

    private function audience(array $claims, string $kind): void
    {
        $client = $this->setting('ClientId');
        if (rtrim((string)($claims['iss'] ?? ''), '/') !== rtrim($this->setting('Issuer'), '/')) {
            throw new \RuntimeException($kind . ' names another issuer');
        }
        if (!in_array($client, (array)($claims['aud'] ?? []), true)) {
            throw new \RuntimeException($kind . ' is for another client');
        }
        if (isset($claims['azp']) && (string)$claims['azp'] !== $client) {
            throw new \RuntimeException($kind . ' is authorized for another client');
        }
    }

    private function verify(array $document, string $token): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new \RuntimeException('token is not a signed JWT');
        }
        $header = json_decode($this->decode($parts[0]), true);
        $claims = json_decode($this->decode($parts[1]), true);
        if (!is_array($header) || !is_array($claims)) {
            throw new \RuntimeException('token is not JSON');
        }
        $algorithm = (string)($header['alg'] ?? '');
        if (!isset(self::ALGORITHMS[$algorithm])) {
            throw new \RuntimeException('token signed with unsupported algorithm ' . preg_replace('/[^A-Za-z0-9]/', '', $algorithm));
        }
        $signature = $this->decode($parts[2]);
        foreach ($this->keys($document) as $key) {
            if (isset($header['kid'], $key['kid']) && $header['kid'] !== $key['kid']) {
                continue;
            }
            $pem = $this->pem($this->decode((string)($key['n'] ?? '')), $this->decode((string)($key['e'] ?? '')));
            if (openssl_verify($parts[0] . '.' . $parts[1], $signature, $pem, self::ALGORITHMS[$algorithm]) === 1) {
                return [$header, $claims];
            }
        }
        throw new \RuntimeException('token signature does not verify against the provider keys');
    }

    private function pem(string $modulus, string $exponent): string
    {
        $key = $this->der("\x30", $this->integer($modulus) . $this->integer($exponent));
        $info = $this->der("\x30", hex2bin('300d06092a864886f70d0101010500') . $this->der("\x03", "\x00" . $key));
        return "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($info), 64, "\n") . "-----END PUBLIC KEY-----\n";
    }

    private function integer(string $bytes): string
    {
        $bytes = ltrim($bytes, "\x00");
        if ($bytes === '' || ord($bytes[0]) > 0x7f) {
            $bytes = "\x00" . $bytes;
        }
        return $this->der("\x02", $bytes);
    }

    private function der(string $tag, string $content): string
    {
        $length = strlen($content);
        if ($length < 0x80) {
            return $tag . chr($length) . $content;
        }
        $bytes = ltrim(pack('N', $length), "\x00");
        return $tag . chr(0x80 | strlen($bytes)) . $bytes . $content;
    }

    private function encode(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }

    private function decode(string $text): string
    {
        return (string)base64_decode(strtr($text, '-_', '+/'));
    }

    private function request(string $url, ?array $form = null, ?string $credentials = null, ?string $bearer = null): array
    {
        $headers = ['Accept: application/json'];
        if ($bearer !== null) {
            $headers[] = 'Authorization: Bearer ' . $bearer;
        }
        $handle = curl_init($url);
        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => $headers,
        ]);
        if ($form !== null) {
            curl_setopt($handle, CURLOPT_POST, true);
            curl_setopt($handle, CURLOPT_POSTFIELDS, http_build_query($form));
        }
        if ($credentials !== null) {
            curl_setopt($handle, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($handle, CURLOPT_USERPWD, $credentials);
        }
        $body = curl_exec($handle);
        $status = (int)curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $error = curl_error($handle);
        curl_close($handle);
        if ($body === false || $status !== 200) {
            $reason = is_string($body) ? (string)(json_decode($body, true)['error'] ?? '') : '';
            throw new \RuntimeException(trim(sprintf(
                '%s answered %d %s %s',
                parse_url($url, PHP_URL_HOST),
                $status,
                preg_replace('/[^A-Za-z0-9_]/', '', $reason),
                $error
            )));
        }
        if (trim($body) === '') {
            return [];
        }
        $data = json_decode($body, true);
        if (!is_array($data)) {
            throw new \RuntimeException(parse_url($url, PHP_URL_HOST) . ' answered something other than JSON');
        }
        return $data;
    }
}
