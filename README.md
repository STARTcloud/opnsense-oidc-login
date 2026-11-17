# OPNsense OIDC Login

**OPNsense OIDC Login** adds a sign-in link to the OPNsense web GUI login page. The link signs a local user in through an OpenID Provider with the [OpenID Connect Core 1.0](https://openid.net/specs/openid-connect-core-1_0.html) authorization code flow and PKCE. It is an OPNsense plugin, `os-oidc-login`.

Only users that already exist on the firewall can sign in. Their groups and privileges stay as configured. Password sign-in, SSH and the console are unchanged.

## Getting started

OPNsense, from the STARTcloud repository:

```sh
fetch -o /usr/local/etc/ssl/pkg-repo/opnsense.pub https://opnsense.startcloud.com/pubkey.pem
```

`/usr/local/etc/pkg/repos/startcloud-public.conf`:

```text
startcloud-public: {
  url:            "https://opnsense.startcloud.com/public/opnsense/${ABI}",
  mirror_type:    "none",
  signature_type: "pubkey",
  pubkey:         "/usr/local/etc/ssl/pkg-repo/opnsense.pub",
  enabled:        yes,
  priority:       100
}
```

```sh
pkg update -f
pkg install os-oidc-login
```

Set the provider under **System, Access, OIDC Login**, enable the button and save. The login page then shows the sign-in button below the password form.

## Provider

The provider publishes OpenID Connect Discovery metadata with an `authorization_endpoint` and a `token_endpoint`, both https.

The client is confidential (`client_secret_basic`), holds the grant type `authorization_code` with PKCE `S256`, and is granted the configured scopes, which contain `openid`. Its redirect URI is the firewall's own address followed by `/oidc_callback.php`, for example `https://firewall.example.org/oidc_callback.php`; every name the web GUI is reached by needs its own entry.

The provider publishes a `jwks_uri` with an RSA signing key and signs ID tokens with `RS256`, `RS384` or `RS512`.

| Provider registration | Value |
| --- | --- |
| Redirect URI | `https://firewall.example.org/oidc_callback.php` |
| Post-logout redirect URI | `https://firewall.example.org/` |
| Back-channel logout URI | `https://firewall.example.org/oidc_backchannel.php` |

## Sign-in

1. `/oidc_login.php` stores a state, a nonce and a PKCE verifier in the session and sends the browser to the authorization endpoint.
2. The provider returns the browser to `/oidc_callback.php` with a code.
3. The plugin exchanges the code at the token endpoint over verified https and reads the ID token from that response.
4. The ID token's signature is verified against the provider's keys, and its issuer, audience, authorized party, expiry, nonce and access token hash are checked. An `iss` parameter on the callback must name the configured issuer.
5. The username claim is read from the ID token, or from the UserInfo endpoint when the ID token does not carry it; the UserInfo subject must equal the ID token's.
6. The value of the username claim is looked up among the user mappings. An enabled mapping to an enabled local user gets a web GUI session, exactly as after a password sign-in. Anything else returns the browser to the login page, which says whether the sign-in was refused or failed, and writes one line with the reason to the audit log.

## Sign-out

A session started through the link is remembered under `/var/db/oidclogin` with the provider's session identifier.

- **From the firewall**: when that session ends on the firewall, the login page sends the browser to `/oidc_logout.php`, which revokes the access token and ends the session at the provider's `end_session_endpoint`. The provider returns the browser to the login page.
- **From the provider**: the provider posts a logout token to `/oidc_backchannel.php`. Its signature, issuer, audience and logout event are checked, and every firewall session of that provider session is ended.

## Configuration

| Setting | Meaning |
| --- | --- |
| Enabled | Show the sign-in button on the login page |
| Button text | The text on the button (default `Single Sign-On`) |
| Primary button | Draw the button in the theme's primary colour |
| Hide password login | Hide the username and password form; `?local` on the login page's address shows it again |
| Sign in automatically | Send the browser to the provider as soon as the login page opens; not after a refused or failed sign-in, and not with `?local` |
| Issuer | The provider's issuer identifier, an https URL |
| Client ID | The client registered at the provider |
| Client secret | Sent to the token endpoint as `client_secret_basic`; stored in the firewall configuration |
| Scopes | Space-separated scopes requested; contains `openid` (default `openid profile email`) |
| Username claim | The ID token claim looked up among the user mappings (default `email`) |
| Prompt | What the provider asks at every sign-in: its own choice, credentials, or which account |
| Sign out at the provider | End the provider session when the firewall session ends (default on) |
| Users | Local user name to the provider account it signs in as; a provider account without an entry is refused |

**Test provider** on the settings page reads the saved issuer's discovery document and signing keys and reports the result.

## Building from source

The package builds inside an `opnsense/plugins` checkout, see [deploy/opnsense/README.md](deploy/opnsense/README.md).

```sh
make lint
```

## Contributing

Contributions welcome, see [CONTRIBUTING.md](CONTRIBUTING.md).

## License

GPL-3.0, see [LICENSE.md](LICENSE.md).
