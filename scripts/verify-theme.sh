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
# Exits nonzero if php is missing, if no fixtures are found, or if any
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

shopt -s nullglob
# shellcheck disable=SC2206
fixtures=(
	$tests_dir/theme-adoption-*.php
	$tests_dir/theme-adopted-gallery-*.php
	$tests_dir/theme-recently-adopted-*.php
)
shopt -u nullglob

# De-duplicate + deterministic order (globs are disjoint, but a future
# regression fixture must never run twice or slip through unsorted).
# shellcheck disable=SC2207
fixtures=( $(printf '%s\n' "${fixtures[@]:-}" | LC_ALL=C sort -u) )

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
