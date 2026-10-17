#!/usr/bin/env bash
#
# Mandatory theme verification entry point (PFOA 0.1.157 gate).
#
# Runs ALL badge/presentation fixtures and propagates nonzero exit on any
# failure, so future releases are blocked on badge regressions:
#   - tests/theme-adoption-*.php            (badge-conditional + any
#     theme-adoption-universal-green-regression fixture from parallel lanes)
#   - tests/theme-adopted-gallery-*.php     (presentation contract)
#   - tests/theme-recently-adopted-*.php    (selection + currency)
#
# Called early by scripts/build-zip.sh BEFORE zip creation; aborts the
# build on failure ("Theme verification failed — refusing to build").
#
# Run with:
#   bash scripts/verify-theme.sh; echo $?
#
# Exits nonzero if php or a mandatory baseline fixture is missing, or if any
# fixture fails. Each failure names the failing fixture file so the
# broken invariant is traceable via that fixture's own FAIL diagnostic.

set -euo pipefail

script_dir="$(CDPATH='' cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
repo_dir="$(dirname -- "$script_dir")"
tests_dir="$repo_dir/tests"

if ! command -v php >/dev/null 2>&1; then
	echo "Error: the php command is required to run theme verification fixtures." >&2
	exit 1
fi

# The eight fixtures present at regression gate 441d05a are mandatory.
# Discovery alone would silently weaken the gate when a file is deleted.
mandatory_fixtures=(
	theme-adoption-badge-conditional-fixture.php
	theme-adoption-event-gallery-integrity-fixture.php
	theme-adoption-event-legacy-anchor-fixture.php
	theme-adoption-event-senior-shadow-fixture.php
	theme-adoption-universal-green-regression-fixture.php
	theme-adopted-gallery-presentation-fixture.php
	theme-recently-adopted-currency-fixture.php
	theme-recently-adopted-selection-fixture.php
)
missing=0
for fixture in "${mandatory_fixtures[@]}"; do
	if [[ ! -f "$tests_dir/$fixture" || -L "$tests_dir/$fixture" ]]; then
		echo "Error: mandatory theme verification fixture missing or symlinked: $fixture" >&2
		missing=1
	fi
done
if (( missing )); then
	exit 1
fi

shopt -s nullglob
# shellcheck disable=SC2206
fixtures=(
	"$tests_dir"/theme-adoption-*.php
	"$tests_dir"/theme-adopted-gallery-*.php
	"$tests_dir"/theme-recently-adopted-*.php
)
shopt -u nullglob

# The patterns are disjoint; Bash expands each in deterministic name order.

if (( ${#fixtures[@]} == 0 )); then
	echo "Error: no theme verification fixtures found in $tests_dir (expected tests/theme-adoption-*.php, tests/theme-adopted-gallery-*.php, tests/theme-recently-adopted-*.php)." >&2
	exit 1
fi

echo "Theme verification: running ${#fixtures[@]} fixture(s) from $tests_dir"

failures=0
failed_files=()
for fixture in "${fixtures[@]}"; do
	echo "--- $fixture"
	if php "$fixture"; then
		echo "PASS: $fixture"
	else
		echo "FAIL: $fixture (failing invariant/test — see FAIL diagnostic above)" >&2
		failures=$(( failures + 1 ))
		failed_files+=( "$fixture" )
	fi
done

if (( failures > 0 )); then
	echo "Error: theme verification failed — $failures of ${#fixtures[@]} fixture(s) failed:" >&2
	printf '  - %s\n' "${failed_files[@]}" >&2
	exit 1
fi

echo "Theme verification passed: all ${#fixtures[@]} fixture(s) green."
