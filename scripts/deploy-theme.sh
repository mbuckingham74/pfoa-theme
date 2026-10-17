#!/usr/bin/env bash
# Canonical entry point. Python's standard library supplies portable process
# group deadlines, JSON receipts and ZIP inspection on macOS and Linux.
set -euo pipefail
script_dir="$(CDPATH='' cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
if ! command -v python3 >/dev/null 2>&1; then
    echo "Error: python3 is required for verified theme deployment." >&2
    exit 1
fi
exec python3 "$script_dir/theme_deploy.py" "$@"
