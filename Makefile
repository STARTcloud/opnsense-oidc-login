.PHONY: lint lint-php lint-xml lint-yaml lint-json lint-md lint-actions

lint: lint-php lint-xml lint-yaml lint-json lint-md lint-actions

lint-php:
	@echo "==> lint-php (php -l)"
	@if command -v php >/dev/null 2>&1; then \
		PHP=php; \
	elif command -v docker >/dev/null 2>&1; then \
		PHP="docker run --rm -v $$(pwd):/app -w /app php:cli php"; \
	else \
		echo "lint-php: install PHP locally or have Docker available — neither found"; exit 1; \
	fi; \
	find deploy/opnsense -name '*.php' -print0 | xargs -0 -n1 $$PHP -l

lint-xml:
	@echo "==> lint-xml (xmllint --noout)"
	@command -v xmllint >/dev/null 2>&1 || \
		{ echo "xmllint not found — install libxml2-utils (Linux) or libxml2 (macOS/FreeBSD)"; exit 1; }
	find deploy -name '*.xml' -print0 | xargs -0 xmllint --noout

lint-yaml:
	@echo "==> lint-yaml (yamllint)"
	@command -v yamllint >/dev/null 2>&1 || \
		{ echo "yamllint not found — pip install yamllint"; exit 1; }
	yamllint -c .yamllint.yaml .github/ .yamllint.yaml

lint-json:
	@echo "==> lint-json (jq empty)"
	@command -v jq >/dev/null 2>&1 || { echo "jq not found"; exit 1; }
	@find . -name '*.json' -not -path './.git/*' | \
		while read -r f; do \
			echo "  $$f"; \
			jq empty "$$f" >/dev/null || { echo "    INVALID"; exit 1; }; \
		done

lint-md:
	@echo "==> lint-md (markdownlint-cli2)"
	@if command -v markdownlint-cli2 >/dev/null 2>&1; then \
		markdownlint-cli2 "**/*.md" "!CHANGELOG.md"; \
	elif command -v npx >/dev/null 2>&1; then \
		npx --yes markdownlint-cli2 "**/*.md" "!CHANGELOG.md"; \
	else \
		echo "markdownlint-cli2 not found — install via 'npm install -g markdownlint-cli2' or have npx available"; exit 1; \
	fi

lint-actions:
	@echo "==> lint-actions (actionlint)"
	@command -v actionlint >/dev/null 2>&1 || \
		{ echo "actionlint not found — go install github.com/rhysd/actionlint/cmd/actionlint@latest"; exit 1; }
	actionlint
