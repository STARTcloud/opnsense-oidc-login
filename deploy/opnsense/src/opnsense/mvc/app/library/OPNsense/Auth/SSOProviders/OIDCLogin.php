<?php

namespace OPNsense\Auth\SSOProviders;

use OPNsense\Core\SanitizeFilter;
use OPNsense\OIDCLogin\OIDCLogin as OIDCLoginModel;
use OPNsense\OIDCLogin\Sessions;

class OIDCLogin implements ISSOContainer
{
    public function listProviders(): \Generator
    {
        $model = new OIDCLoginModel();
        if ((string)$model->general->Enabled === '1') {
            $uri = '/oidc_login.php';
            $redir = (new SanitizeFilter())->sanitize($_GET['url'] ?? '', 'local_uri');
            if (!empty($redir)) {
                $uri .= '?redir=' . rawurlencode($redir);
            }
            $messages = [
                'refused' => gettext('Sign-in refused: this account is not mapped to a user of this firewall.'),
                'failed' => gettext('Sign-in failed. The reason is in the audit log.'),
            ];
            $html = '';
            $local = isset($_GET['local']);
            $error = (string)($_GET['oidc_error'] ?? '');
            if ((string)$model->general->HideLocal === '1' && !$local) {
                $html .= '<style>#iform{display:none;}</style>';
            }
            if (isset($messages[$error])) {
                $html .= '<p class="text-danger" style="margin-top:15px;">' . htmlspecialchars($messages[$error]) . '</p>';
            }
            $html .= sprintf(
                '<a class="btn %s btn-block" style="margin-top:15px;" href="%s">%s</a>',
                (string)$model->general->PrimaryButton === '1' ? 'btn-primary' : 'btn-default',
                htmlspecialchars($uri),
                htmlspecialchars((string)$model->general->Name)
            );
            $handle = (string)($_COOKIE[Sessions::COOKIE] ?? '');
            if (
                (string)$model->general->ProviderLogout === '1' && empty($_SESSION['Username']) &&
                (new Sessions())->find($handle) !== null
            ) {
                $html .= '<meta http-equiv="refresh" content="0;url=/oidc_logout.php">';
            } elseif (
                (string)$model->general->AutoSignIn === '1' && !$local && $error === '' &&
                empty($_SESSION['Username']) && ($_SERVER['REQUEST_METHOD'] ?? '') === 'GET'
            ) {
                $html .= sprintf('<meta http-equiv="refresh" content="0;url=%s">', htmlspecialchars($uri));
            }
            yield new Provider([
                'id' => 'oidclogin',
                'appcode' => 'oidclogin',
                'name' => (string)$model->general->Name,
                'login_uri' => '/oidc_login.php',
                'service' => 'WebGui',
                'html_content' => $html,
            ]);
        }
    }
}
