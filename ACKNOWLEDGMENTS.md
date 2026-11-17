# Acknowledgments

OPNsense OIDC Login is built using many excellent open-source projects and tools. We are grateful to the developers and communities behind these projects.

## Core Dependencies

### Runtime Dependencies

**OPNsense** - The firewall platform and plugin framework this plugin runs in

- Website: [opnsense.org](https://opnsense.org/)
- License: BSD-2-Clause
- Usage: Web GUI, model/view/controller framework, the single sign-on provider hook on the login page

**PHP** with **cURL** and **OpenSSL** - As shipped by OPNsense

- Website: [php.net](https://www.php.net/)
- License: PHP License
- Usage: The sign-in pages and the calls to the OpenID Provider

### Development and Release Tooling

**opnsense/plugins** - The plugin build framework

- Repository: [github.com/opnsense/plugins](https://github.com/opnsense/plugins)
- License: BSD-2-Clause
- Usage: `Mk/plugins.mk` builds the package

**release-please** - Automated releases from conventional commits

- Repository: [github.com/googleapis/release-please](https://github.com/googleapis/release-please)
- License: Apache-2.0
- Usage: Versioning, changelog, and GitHub releases

## Platform and Ecosystem

**OpenID Foundation** - The OpenID Connect Core specification this plugin implements

- Website: [openid.net](https://openid.net/)

**FreeBSD** - The operating system and package format

- Website: [freebsd.org](https://www.freebsd.org/)

**GitHub** - Code hosting, issue tracking, CI/CD

- Website: [github.com](https://github.com/)

## Community and Inspiration

**PAM CIBA** - The repository this one takes its community files and release flow from

- Repository: [github.com/STARTcloud/pam-ciba](https://github.com/STARTcloud/pam-ciba)

---

## Disclaimer

This acknowledgment file may not be exhaustive. If you believe a project or individual should be acknowledged here, please let us know by opening an issue or contributing to this file.

All trademarks and registered trademarks mentioned herein are the property of their respective owners.
