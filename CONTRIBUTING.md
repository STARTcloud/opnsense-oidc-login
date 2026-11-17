# Contributing to OPNsense OIDC Login

Thank you for your interest in contributing to OPNsense OIDC Login! We welcome contributions from the community as they are essential for the project's continued growth and development.

## Important Note on Resources

OPNsense OIDC Login is maintained with limited development resources. **Community contributions directly impact the pace of feature development and bug fixes.** The more the community contributes, the faster the project can grow and improve.

## How to Contribute

### Reporting Issues

Before creating an issue, please:

1. **Search existing issues** to avoid duplicates
2. **Use the appropriate issue template** (bug report, feature request, etc.)
3. **Provide detailed information** to help us understand and prioritize the issue
4. **Include system information** (OPNsense version, plugin version, the OpenID Provider)

### Submitting Pull Requests

We appreciate all pull requests! To ensure smooth collaboration:

1. **Fork the repository** and create your feature branch from `main`
2. **Follow the existing code style**: `make lint` is the arbiter and runs in CI
3. **Update documentation** if your changes affect the API or configuration
4. **Use conventional commit messages** (`fix: ...`, `feat: ...`); releases are automated from them
5. **Fill out the pull request template** completely

### Development Setup

1. Clone your fork of the repository
2. Run the linters: `make lint`
3. Build the package inside an `opnsense/plugins` checkout, see [deploy/opnsense/README.md](deploy/opnsense/README.md)
4. Install the package on a test firewall and sign in through the link on the login page

### Code Style Guidelines

- `make lint` must pass
- Follow the layout and naming of the OPNsense plugin framework
- Write sign-in diagnostics to the audit log through `auth_log`, never to the page
- Keep dependencies minimal; the plugin uses only what OPNsense ships

### What We're Looking For

**High Impact Contributions:**

- Bug fixes (especially those affecting system stability)
- Security improvements
- Performance optimizations
- Documentation improvements

**Feature Contributions:**

- Provider compatibility
- Settings page improvements
- Better error handling

## Response Times and Review Process

Due to limited development resources:

- **Issue responses**: We aim to acknowledge new issues within a few days
- **Pull request reviews**: Reviews may take weeks depending on complexity and current workload
- **Feature requests**: Prioritized based on community needs and available resources
- **Documentation updates**: Generally reviewed quickly as they're high-impact, low-risk

## Getting Help

If you need help with contributing:

- **GitHub Discussions**: Ask questions about development
- **Issues**: Use the "question" template for specific inquiries

## Recognition

All contributors are recognized in our [AUTHORS.md](AUTHORS.md) file. We appreciate every contribution, from small bug fixes to major features!

## Code of Conduct

Please note that this project follows our [Code of Conduct](CODE_OF_CONDUCT.md). By participating, you agree to abide by its terms.

## License

By contributing to OPNsense OIDC Login, you agree that your contributions will be licensed under the [GPL-3.0 License](LICENSE.md).

---

**Remember**: Your contributions directly influence the project's development speed and capabilities. Thank you for helping make OPNsense OIDC Login better for everyone!
