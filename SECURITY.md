# Security Policy

## Reporting a Vulnerability

**Please do not report security vulnerabilities through public GitHub issues.**

If you discover a security vulnerability in OPNsense OIDC Login, please report it responsibly:

### Preferred Method: Security Advisory

1. Go to the [GitHub Security Advisory page](https://github.com/STARTcloud/opnsense-oidc-login/security/advisories)
2. Click "Report a vulnerability"
3. Fill out the advisory form with detailed information
4. Submit the advisory

### What to Include

Please provide as much information as possible:

- **Description** of the vulnerability
- **Steps to reproduce** the issue
- **Potential impact** of the vulnerability
- **Affected versions** (if known)
- **Suggested fix** (if you have one)
- **Your contact information** for follow-up questions

## Response Process

Due to limited development resources, please understand that:

- **Initial Response**: We aim to acknowledge receipt within 48-72 hours
- **Assessment**: Initial assessment will be completed within 1 week
- **Resolution**: Timeline depends on severity and complexity, typically 1-4 weeks
- **Disclosure**: Coordinated disclosure after fix is available

### Severity Levels

- **Critical**: Immediate attention (RCE, privilege escalation)
- **High**: Quick response needed (authentication bypass, data exposure)
- **Medium**: Standard timeline (DoS, information disclosure)
- **Low**: Lower priority (minor information leaks)

## Security Considerations for OPNsense OIDC Login

OPNsense OIDC Login decides whether a web GUI session starts, so please pay special attention to:

### High-Risk Areas

- **Sign-in decision**: Anything that starts a session without an ID token from the configured provider for an enabled local user
- **Token validation**: Signature, issuer, audience, nonce or expiry checks that can be bypassed, on the ID token or the logout token
- **Session store**: Anything that reads or forges an entry under `/var/db/oidclogin`, or ends a session the logout token does not name
- **Provider trust**: Discovery or the code exchange over anything but verified https
- **State handling**: A callback accepted without the state and PKCE verifier of the session that started it
- **Redirects**: A return address that leaves the firewall

### Configuration Security

- **Default configurations**: Insecure defaults
- **User mapping**: A claim value reaching a local user it does not name

## Best Practices for Users

To maintain security:

1. **Keep Updated**: Always run the latest release
2. **Protect the client secret**: It is stored in the firewall configuration and in its backups
3. **Monitor Logs**: Watch the audit log for sign-in errors

## Security Tooling in CI

Every change runs through:

- **php -l**: syntax check of every PHP file
- **xmllint**, **yamllint**, **jq**: well-formedness of models, forms, workflows and JSON
- **actionlint**: workflow checks, with shellcheck on inline scripts
- **Dependabot**: dependency update automation

## Acknowledgments

We appreciate the security research community's efforts in making OPNsense OIDC Login more secure. Responsible disclosure helps protect all users.

### Hall of Fame

Contributors who responsibly report security vulnerabilities will be acknowledged here (with their permission):

- *No vulnerabilities reported yet*

## Updates to This Policy

This security policy may be updated as the project evolves. Check back periodically for changes.

---

**Remember**: Security is a shared responsibility. Your vigilance and responsible reporting help keep everyone safe.
