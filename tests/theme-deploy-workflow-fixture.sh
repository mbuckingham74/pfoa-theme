#!/usr/bin/env bash
# All SSH/SCP/Docker/WP installation is isolated; this never contacts DEV.
set -euo pipefail
script_dir="$(CDPATH='' cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
exec python3 "$script_dir/theme_deploy_workflow_fixture.py"
