# OPNsense plugin (`os-oidc-login`)

MVC plugin that adds an OpenID Connect sign-in link to the web GUI login page. It ships PHP only and depends on nothing beyond OPNsense itself.

## Layout

```text
deploy/opnsense/
├── Makefile                              plugin metadata
├── pkg-descr
└── src/
    ├── opnsense/mvc/app/
    │   ├── models/OPNsense/OIDCLogin/
    │   │   ├── OIDCLogin.{php,xml}       settings
    │   │   ├── ACL/ACL.xml
    │   │   └── Menu/Menu.xml             System, Access, OIDC Login
    │   ├── controllers/OPNsense/OIDCLogin/
    │   │   ├── IndexController.php
    │   │   ├── Api/SettingsController.php
    │   │   └── forms/{general,dialogUser}.xml
    │   ├── views/OPNsense/OIDCLogin/index.volt
    │   └── library/OPNsense/
    │       ├── Auth/SSOProviders/OIDCLogin.php   the link on the login page
    │       ├── OIDCLogin/Client.php              discovery, code exchange, token verification
    │       └── OIDCLogin/Sessions.php            sessions started through the link
    └── www/
        ├── oidc_login.php                starts the sign-in
        ├── oidc_callback.php             finishes it and starts the session
        ├── oidc_logout.php               ends the session at the provider
        └── oidc_backchannel.php          receives the provider's logout token
```

## Build

```sh
git clone https://github.com/opnsense/plugins.git /local/opnsense-plugins
cp -R deploy/opnsense /local/opnsense-plugins/security/oidc-login
cd /local/opnsense-plugins/security/oidc-login
make package
```

Output: `work/pkg/os-oidc-login-<version>.pkg`.

## Install on an OPNsense firewall

See the [README](../../README.md).
