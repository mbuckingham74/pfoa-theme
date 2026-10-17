# Verified theme deployment (DEV)

`scripts/deploy-theme.sh` is the canonical release entry point. It builds from
one committed source snapshot and installs only the ZIP produced by that build's
mandatory regression gate. It accepts **DEV only**; host, container, site URLs,
WordPress root and theme slug are fixed in `scripts/theme_deploy.py`.

The initial implementation checkpoint authorizes local and simulated tests only.
The first real DEV deployment and rollback rehearsal require separate
user authorization. These commands are provided for that rehearsal.

## Commands

Run from the theme repository on `compat-managed-projection-assembly`:

```bash
cd /Users/michaelbuckingham/Documents/my-apps/pfoa-theme
bash scripts/deploy-theme.sh --environment dev --commit HEAD --deploy
```

Validation only (no theme installation):

```bash
cd /Users/michaelbuckingham/Documents/my-apps/pfoa-theme
bash scripts/deploy-theme.sh --environment dev --commit HEAD --validate-only
```

`--environment`, `--commit`, and exactly one mode are required. A full commit SHA
can replace `HEAD`; the ref is resolved once before taking the snapshot. Selected
source must contain the same deployment, build and gate scripts as the executing
checkout. Tracked changes must be committed first. Untracked files are omitted;
existing working-tree ZIPs are never considered. Theme version is read from
committed `pfoa-theme/style.css`; this implementation leaves 0.1.157 unchanged.

**Validation accesses DEV.** It verifies identity, creates and checks a backup,
and transfers the verified package to private staging directories. It stops
before WP-CLI installation and marks installation/readback `SKIPPED`. It does
not authorize or predict a successful installation. A later `--deploy` invocation
builds and verifies its own fresh package and backup.

Local dependencies: Python 3.9+, Bash, Git, PHP, zip, unzip, OpenSSH. Remote host:
Bash, GNU `timeout`, Docker, `sha256sum`, and write access to the SSH user's home.
Container: Bash, GNU `timeout`, PHP, tar and the existing WP-CLI PHAR. The SSH user
must have Docker access. Known-host verification and batch authentication are
mandatory; populate/verify the host key through the usual trusted setup first.
The wrapper does not disable host-key checking or invoke sudo interactively.

Fixed DEV identity:

| Field | Expected value |
| --- | --- |
| SSH | `michael@100.120.233.4` |
| Container | `pfoa-dev-wordpress` |
| WordPress root | `/var/www/html` |
| WP-CLI | `php /var/www/html/wp-cli.phar` |
| Database `home` and `siteurl` | `https://dev.safehavenpfoa.org` (trailing slash normalized) |
| Active stylesheet and template | `pfoa-theme` |
| Installed theme directory | `/var/www/html/wp-content/themes/pfoa-theme` |

Multisite, child themes, symlinked theme trees and unexpected URLs/paths fail
closed. No production target or target override is supported. Plugins, including
Cat Profiles, are outside this deployment; its accepted 0.1.133 baseline is not
installed or changed by this wrapper.

## Verification stages

1. Resolve the selected commit, require clean tracked source, and extract a Git
   archive into a new private temporary directory. Reject committed `dist/`
   output and unsafe/symlink members. Retain the source archive's checksum.
2. Require the executing deployment/build/gate scripts to match the selected
   commit. Run `scripts/build-zip.sh` inside the fresh snapshot. That build runs
   `scripts/verify-theme.sh` before creating any ZIP.
3. Require all eight baseline fixtures from `441d05a`, and discover additional
   `theme-adoption-*.php`, `theme-adopted-gallery-*.php` and
   `theme-recently-adopted-*.php` fixtures. Missing, symlinked or failing mandatory
   fixtures stop packaging. Additional matching fixtures must also pass.
4. Require exactly one new versioned ZIP, validate CRCs and member paths, reject
   duplicates/symlinks, compare the embedded version, and compare every packaged
   file's SHA-256 and the full file set with the build's source projection.
   Check that the source did not change during verification/build. Retain a
   read-only copy of this exact ZIP and its SHA-256.
5. Read actual WordPress database options, active theme, version and complete
   installed-file manifest. Identity is checked without a `--url` override.
6. Acquire the shared DEV deployment lock. Archive the current theme to tar.gz,
   check its checksum in the container, copy it to the host and locally, check
   checksum equality, and compare its complete file manifest with the preflight
   manifest. No installation occurs without a verified backup.
7. Transfer the retained ZIP to the host, verify SHA-256, transfer it to the
   container, and verify SHA-256 again. Reread identity and the installed theme
   manifest to ensure the backup still describes the current installation.
8. In deploy mode, recheck package SHA-256 inside the same container command that
   invokes `wp theme install <local-zip> --force`. The already-active theme is
   replaced; no theme activation request is needed.
9. Independently reread active slug/template, version and every installed file.
   Exact file-set and byte parity are required; missing, extra or changed files
   fail verification. Emit `DEPLOYED_VERIFIED` only after all checks pass.

The Python package projection mirrors the existing exclusions in `build-zip.sh`.
Keep both policies synchronized when changing package exclusions; the workflow
fixture compares a real build with that projection. A stale ZIP with the correct
filename/version and valid CRCs still fails source parity.

## Timeouts and failures

Every subprocess has a hard deadline: 120 seconds per operation and 300 seconds
for verification/build by default. `--timeout N` and `--build-timeout N` accept
1–1800 seconds. Remote SSH calls have a 20-second transport/cleanup allowance;
remote host shells and Docker clients have shorter deadlines, and work inside
the container has its own deadline. Killing a Docker client alone would leave
its container process running, so both layers are bounded.

On local timeout/interruption the entire subprocess process group is killed,
reaped and checked for live members. SSH multiplexing is disabled so a persistent
control socket cannot outlive a run. The wrapper does not retry failed commands.
It makes no further remote calls after an SSH command fails or times out. In
that case it retains the lock, records uncertain remote state, and relies on the
independent remote deadlines before an operator inspects the host/container.
A retained lock is never automatically stolen.

All failures exit nonzero and name the stage. A JSON failure receipt records the
error, whether installation was attempted, backup paths/checksums if available,
cleanup state, log path and recovery guidance. A failed installation or readback
means DEV may contain a partially replaced or unverified theme. Inspect the
receipt/log and restore the verified backup if necessary. Rollback is manual;
the wrapper never silently reports success or automatically restores an older
release.

WP-CLI's ZIP replacement is **not a filesystem-atomic directory swap**. This
wrapper makes the verification/package decision indivisible, but a forced
WordPress installation can be interrupted during replacement. A working backup
and a separately rehearsed recovery procedure remain necessary. The lock
serializes this wrapper's runs; external administrators and other deployment
mechanisms must also avoid changing the theme during the operation. The design
assumes a trusted checkout, authenticated SSH endpoint and trusted remote root.
It does not defend against a privileged process rewriting files between a hash
check and an install.

## Backups and rollback

Every validated/deployed run retains:

- Container: `/tmp/pfoa-theme-deploy-<run-id>/theme-before.tar.gz` (private staging).
- Host: `$HOME/.local/state/pfoa-theme-deploy/<run-id>/theme-before.tar.gz`, plus
  `theme-before.tar.gz.sha256` (durable host copy).
- Local: `deployments/<run-id>/theme-before.tar.gz` and its checksum in the receipt.

The archive includes the full installed theme directory, including file modes
and ownership. Its file manifest must match the preflight installed tree. A
backup captures theme files only, not the database, uploads or plugin state.
No automatic retention pruning is implemented. Keep the host/local backup and
receipt until the release has been accepted; container `/tmp` is not durable.

For a separately authorized rollback:

1. Select the failed deployment's receipt. Confirm its target, original version,
   `backup.verified == true`, `backup.sha256`, and host backup path. Check
   `receipt.json.sha256` locally and compare the backup's checksum with that
   receipt. Do not trust a sidecar alone if the backup/receipt provenance is in
   doubt; these checksums are integrity checks, not digital signatures.
2. If `remote_state_uncertain` or a lock is retained, wait beyond the recorded
   operation deadlines and inspect the specific process tree on the host and
   inside the container. Terminate any remaining run-related process tree and
   verify its clients/sockets are gone before issuing another Docker operation.
   The lock owner is in
   `$HOME/.local/state/pfoa-theme-deploy/dev.lock/owner`. Preserve the owner value
   in the incident record. Remove that lock only after its owner is resolved,
   then acquire the same lock for rollback. Do not bypass an active deployment.
3. Reverify DEV database `home`/`siteurl` and active stylesheet/template using the
   same WP-CLI identity checks described above. Confirm the backup manifest and
   tar listing contain only regular files/directories under `pfoa-theme/`.
4. Copy the host backup into private container staging with bounded `docker cp`;
   verify its SHA-256 in the container against the recorded value.
5. During a maintenance window, move the failed theme directory aside (retain
   it for diagnosis), then extract the backup into `wp-content/themes`. Use
   the same filesystem to allow the move to be atomic. Extracting directly over
   a failed tree can leave extra files, so restore to an absent theme directory.
6. Clear WordPress theme caches (`wp eval 'wp_clean_themes_cache(true);'`), then
   reread the active slug/template/version and compare **every** restored file
   hash and file name with `backup.files` in the receipt. Verify adoption badges
   and both galleries in a browser. Release the rollback lock only after the
   restoration's state is known; retain a rollback incident record.

Example bounded host-side restore commands, **after** the identity, lock and
backup checks above (substitute the reviewed backup path, SHA and run ID):

```bash
# Run in the authorized SSH session. All Docker clients and container work
# have independent hard limits. Do not paste placeholder values unchanged.
set -euo pipefail
run_id='<reviewed-run-id>'
backup="$HOME/.local/state/pfoa-theme-deploy/$run_id/theme-before.tar.gz"
expected_sha='<backup.sha256-from-reviewed-receipt>'
restore_dir="/tmp/pfoa-theme-rollback-$run_id"
timeout --signal=KILL 35s docker exec --user 0 pfoa-dev-wordpress \
  timeout --signal=KILL 30s mkdir -m 700 "$restore_dir"
timeout --signal=KILL 35s docker cp "$backup" "pfoa-dev-wordpress:$restore_dir/theme-before.tar.gz"
timeout --signal=KILL 35s docker exec --user 0 pfoa-dev-wordpress \
  timeout --signal=KILL 30s php -r \
  'if (hash_file("sha256", $argv[1]) !== $argv[2]) { exit(1); }' \
  "$restore_dir/theme-before.tar.gz" "$expected_sha"
# Stop immediately if any checksum/command above fails.
timeout --signal=KILL 65s docker exec --user 0 pfoa-dev-wordpress \
  timeout --signal=KILL 60s bash -euc \
  'mv /var/www/html/wp-content/themes/pfoa-theme "$1/failed-theme";
   tar -xzf "$1/theme-before.tar.gz" -C /var/www/html/wp-content/themes' \
  rollback "$restore_dir"
timeout --signal=KILL 35s docker exec --user 0 pfoa-dev-wordpress \
  timeout --signal=KILL 30s php /var/www/html/wp-cli.phar \
  --path=/var/www/html --allow-root --skip-plugins --skip-themes \
  eval 'wp_clean_themes_cache(true);'
# Required next: active-theme/version and full backup.files parity readback.
```

Run these commands in a shell with `set -euo pipefail`. On any failure, stop and
inspect before proceeding. An SSH command that invokes them must also have a
hard local timeout longer than the nested deadlines. No rollback was executed
as part of the implementation checkpoint.

## Receipt format

Generated artifacts are ignored under `deployments/<UTC-run-id>/`. A run produces
`receipt.json`, `receipt.json.sha256`, `deployment.log`, the retained verified
ZIP, `package-files.json`, and the verified backup if that stage was reached.
Receipt updates are atomic JSON replacements. The checksum sidecar covers the
final exact JSON bytes; preserve them without reformatting.

Schema version 1 includes:

| Field | Meaning |
| --- | --- |
| `run_id`, `started_at`, `finished_at`, `mode`, `status` | Run identity, UTC times and outcome |
| `commit_sha`, `source_archive_sha256`, `verification_scripts` | Immutable Git source identity and gate/build/deployer hashes |
| `target` | Fixed DEV host/container/root/site/slug expectations |
| `package` | Retained path, version, SHA-256, file count and manifest checksum |
| `checks` | Ordered stage results (`PASS`, `FAIL`, or explicitly `SKIPPED`) |
| `before`, `after` | Observed site identity, active version and file hashes |
| `installed_version` | Set only following successful post-install verification |
| `backup` | Container/host/local paths, SHA-256, original version/file hashes and verified flag |
| `installation_attempted` | True immediately before the install command is submitted |
| `failure`, `recovery`, `log`, `log_sha256` | Actionable failure stage/detail and evidence |
| `remote_state_uncertain`, `remote_lock_retained`, `process_cleanup` | Recovery/timeout state, when applicable |
| `timeouts_seconds` | Configured operation/build deadlines |

Successful statuses are `VALIDATED_NOT_DEPLOYED` and `DEPLOYED_VERIFIED`. `FAILED`
always exits nonzero. Invalid command syntax exits before a run is created.

## Isolated verification

```bash
bash scripts/verify-theme.sh
bash tests/theme-deploy-workflow-fixture.sh
```

The workflow fixture builds actual theme source and runs the real wrapper in
fresh temporary Git repositories, including paths with spaces. SSH, SCP, Docker
and WordPress are isolated doubles; real Bash/PHP/tar/checksum commands execute
against temporary host/container trees. No DEV access occurs. Evidence is retained
under `deployments/workflow-fixtures-<id>/`, with one receipt/log per scenario and
`results.json` summarizing outcomes. Test receipts refer to temporary fixture
commits/paths; copied artifacts are retained for inspection, not deployment.

Covered failures include removal of each of the eight mandatory fixtures,
actual badge regressions, failed mandatory/newly discovered fixtures, stale ZIP
content, committed build residue, dirty tracked source, host/container corruption and corruption immediately before installation,
wrong URLs/multisite, failed backup/transfers/installation, local build/transfer
process-tree deadlines, container installation deadline, wrong active slug/version,
extra installed files, and final receipt/package/backup checksum integrity.

The first authorized DEV rehearsal should run validation, review its identity
and backup, perform a verified deployment, compare real installed-file parity,
inspect the badge presentation at Adopted 2026 and Recently Adopted, restore the
recorded backup in a maintenance window, and prove restored version/file parity.
Record real host/container prerequisites, elapsed times, permissions and receipt
locations before accepting the wrapper for routine use.

WP-CLI reference: [local ZIP installation and `--force`](https://developer.wordpress.org/cli/commands/theme/install/),
[PHP evaluation](https://developer.wordpress.org/cli/commands/eval/).
