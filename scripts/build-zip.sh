#!/usr/bin/env bash

set -euo pipefail

script_dir="$(CDPATH='' cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
repo_dir="$(dirname -- "$script_dir")"
theme_dir="$repo_dir/pfoa-theme"
dist_dir="$repo_dir/dist"

if [[ ! -d "$theme_dir" ]]; then
	echo "Error: theme directory not found: $theme_dir" >&2
	exit 1
fi

required_files=(
	style.css
	functions.php
	theme.json
	index.php
	header.php
	footer.php
	front-page.php
	page.php
	template_builder.php
	single.php
	home.php
	archive.php
	search.php
	404.php
)

for required_file in "${required_files[@]}"; do
	if [[ ! -f "$theme_dir/$required_file" ]]; then
		echo "Error: required theme file is missing: $required_file" >&2
		exit 1
	fi
done

if ! command -v zip >/dev/null 2>&1; then
	echo "Error: the zip command is required to build the theme archive." >&2
	exit 1
fi

version="$(sed -nE 's/^Version:[[:space:]]*//p' "$theme_dir/style.css" | head -n 1 | tr -d '\r')"
if [[ -z "$version" ]]; then
	echo "Error: could not read a Version value from style.css." >&2
	exit 1
fi

mkdir -p "$dist_dir"
archive="$dist_dir/pfoa-theme-$version.zip"
build_dir="$(mktemp -d "${TMPDIR:-/tmp}/pfoa-theme-build.XXXXXX")"
trap 'rm -rf "$build_dir"' EXIT HUP INT TERM

stage_dir="$build_dir/pfoa-theme"
mkdir -p "$stage_dir"

while IFS= read -r -d '' source_file; do
	relative_file="${source_file#"$theme_dir/"}"

	case "$relative_file" in
		# Repository, environment, editor, and local development metadata.
		.DS_Store|*/.DS_Store|.git|*/.git|.git/*|*/.git/*|.gitignore|*/.gitignore|.env|*/.env|.env.*|*/.env.*|.idea/*|*/.idea/*|.vscode/*|*/.vscode/*|.settings/*|*/.settings/*|.github/*|*/.github/*|.gitlab/*|*/.gitlab/*|docs/*|*/docs/*|*.sublime-*|*.code-workspace|Dockerfile|*/Dockerfile|docker-compose*|*/docker-compose*|Makefile|*/Makefile)
			continue
			;;
		# Database dumps, temporary files, backups, and development logs.
		*.sql|*.sql.*|*.sqlite|*.sqlite3|*.db|*.dump|*.tmp|*.temp|*.bak|*.backup|*.orig|*.rej|*~|.#*|*.swp|*.swo|*.log|*.log.*|npm-debug.log*|yarn-debug.log*|yarn-error.log*|pnpm-debug.log*|*.map|*.zip|*.tar|*.tar.gz|*.tgz)
			continue
			;;
		# Obvious secrets, private keys, and certificates.
		*.pem|*.key|*.crt|*.cer|*.der|*.p12|*.pfx|*.secret|*.secrets|*.token|*.credentials|*.password|credentials.*|*/credentials.*|secrets.*|*/secrets.*|secret.*|*/secret.*|id_rsa|id_rsa.*|*/id_rsa|*/id_rsa.*)
			continue
			;;
		# Dependency directories, caches, coverage, and test artifacts.
		node_modules/*|*/node_modules/*|vendor/*|*/vendor/*|bower_components/*|*/bower_components/*|.cache/*|*/.cache/*|.npm/*|*/.npm/*|.yarn/*|*/.yarn/*|.pnpm-store/*|*/.pnpm-store/*|.parcel-cache/*|*/.parcel-cache/*|.sass-cache/*|*/.sass-cache/*|.vite/*|*/.vite/*|.webpack/*|*/.webpack/*|.next/*|*/.next/*|.nuxt/*|*/.nuxt/*|coverage/*|*/coverage/*|test/*|*/test/*|tests/*|*/tests/*|__tests__/*|*/__tests__/*|__snapshots__/*|*/__snapshots__/*|test-results/*|*/test-results/*|playwright-report/*|*/playwright-report/*|.phpunit.result.cache|*/.phpunit.result.cache|.eslintcache|*/.eslintcache|.stylelintcache|*/.stylelintcache|phpunit.xml|*/phpunit.xml|phpunit.xml.dist|*/phpunit.xml.dist|junit.xml|*/junit.xml|*.test.*|*.spec.*|*.snap|*.cache)
			continue
			;;
	esac

	destination_file="$stage_dir/$relative_file"
	mkdir -p "$(dirname -- "$destination_file")"
	cp "$source_file" "$destination_file"
done < <(find "$theme_dir" -type f -print0)

rm -f "$archive"
(cd "$build_dir" && zip -qr "$archive" pfoa-theme)

echo "Created $archive"
