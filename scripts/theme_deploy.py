#!/usr/bin/env python3
"""Verified DEV ZIP deployment. Invoked by deploy-theme.sh; stdlib only."""
import argparse
import fnmatch
import hashlib
import json
import os
from pathlib import Path, PurePosixPath
import re
import shlex
import shutil
import signal
import stat
import subprocess
import sys
import tarfile
import tempfile
import time
from datetime import datetime, timezone
import uuid
import zipfile

TARGET = {
    "environment": "dev", "ssh": "michael@100.120.233.4",
    "container": "pfoa-dev-wordpress", "wordpress_root": "/var/www/html",
    "home": "https://dev.safehavenpfoa.org", "siteurl": "https://dev.safehavenpfoa.org",
    "stylesheet": "pfoa-theme", "template": "pfoa-theme",
}
REQUIRED = ("style.css", "functions.php", "theme.json", "index.php", "header.php",
            "footer.php", "front-page.php", "page.php", "template_builder.php",
            "single.php", "home.php", "archive.php", "search.php", "404.php")
# Mirror build-zip.sh's release projection; workflow fixtures enforce equality.
EXCLUDES = """
._* */._* __MACOSX __MACOSX/* */__MACOSX/* .DS_Store */.DS_Store
.git */.git .git/* */.git/* .gitignore */.gitignore .env */.env .env.* */.env.*
.idea/* */.idea/* .vscode/* */.vscode/* .settings/* */.settings/* .github/* */.github/*
.gitlab/* */.gitlab/* docs/* */docs/* *.sublime-* *.code-workspace Dockerfile */Dockerfile
docker-compose* */docker-compose* Makefile */Makefile
*.sql *.sql.* *.sqlite *.sqlite3 *.db *.dump *.tmp *.temp *.bak *.backup *.orig *.rej
*~ .#* *.swp *.swo *.log *.log.* npm-debug.log* yarn-debug.log* yarn-error.log*
pnpm-debug.log* *.map *.zip *.tar *.tar.gz *.tgz
*.pem *.key *.crt *.cer *.der *.p12 *.pfx *.secret *.secrets *.token *.credentials
*.password credentials.* */credentials.* secrets.* */secrets.* secret.* */secret.*
id_rsa id_rsa.* */id_rsa */id_rsa.*
node_modules/* */node_modules/* vendor/* */vendor/* bower_components/* */bower_components/*
.cache/* */.cache/* .npm/* */.npm/* .yarn/* */.yarn/* .pnpm-store/* */.pnpm-store/*
.parcel-cache/* */.parcel-cache/* .sass-cache/* */.sass-cache/* .vite/* */.vite/*
.webpack/* */.webpack/* .next/* */.next/* .nuxt/* */.nuxt/* coverage/* */coverage/*
test/* */test/* tests/* */tests/* __tests__/* */__tests__/* __snapshots__/* */__snapshots__/*
test-results/* */test-results/* playwright-report/* */playwright-report/*
.phpunit.result.cache */.phpunit.result.cache .eslintcache */.eslintcache
.stylelintcache */.stylelintcache phpunit.xml */phpunit.xml phpunit.xml.dist
*/phpunit.xml.dist junit.xml */junit.xml *.test.* *.spec.* *.snap *.cache
""".split()

# Read actual database options without --url overriding target identity. Skip
# plugins/themes so broken theme code cannot prevent recovery/readback.
IDENTITY_PHP = r'''
$t = wp_get_theme();
$r = array('home'=>rtrim(get_option('home'), '/'),
    'siteurl'=>rtrim(get_option('siteurl'), '/'), 'multisite'=>is_multisite(),
    'stylesheet'=>get_stylesheet(), 'template'=>get_template(),
    'version'=>$t->get('Version'), 'theme_directory'=>get_stylesheet_directory());
if (is_link($r['theme_directory']) || !is_dir($r['theme_directory'])) {
    WP_CLI::error('Theme directory missing or symlinked');
}
$r['files'] = array();
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
    $r['theme_directory'], FilesystemIterator::SKIP_DOTS));
foreach ($it as $f) {
    if ($f->isLink() || !$f->isFile()) WP_CLI::error('Nonregular theme member');
    $n = substr($f->getPathname(), strlen($r['theme_directory']) + 1);
    $r['files'][$n] = hash_file('sha256', $f->getPathname());
}
ksort($r['files']);
echo json_encode($r, JSON_UNESCAPED_SLASHES);
'''


def digest(path):
    h = hashlib.sha256()
    with open(path, "rb") as stream:
        for block in iter(lambda: stream.read(1024 * 1024), b""):
            h.update(block)
    return h.hexdigest()


def version(data):
    found = re.findall(r"^\s*Version:\s*(\S+)\s*$", data.decode("utf-8"), re.M)
    if len(found) != 1 or not re.fullmatch(r"[0-9]+(?:\.[0-9]+)+(?:[-.][A-Za-z0-9]+)*", found[0]):
        raise RuntimeError("style.css must contain one valid theme Version header")
    return found[0]


def atomic_json(path, data):
    temporary = path.with_suffix(".tmp")
    temporary.write_text(json.dumps(data, indent=2, sort_keys=True) + "\n")
    temporary.replace(path)


class Deployment:
    def __init__(self, args):
        self.args = args
        self.repo = Path(__file__).resolve().parent.parent
        self.run_id = datetime.now(timezone.utc).strftime("%Y%m%dT%H%M%SZ-") + uuid.uuid4().hex[:12]
        self.output = (self.repo / "deployments" / self.run_id)
        self.output.mkdir(parents=True, mode=0o700)
        self.log_path = self.output / "deployment.log"
        self.log = self.log_path.open("wb")
        self.receipt_path = self.output / "receipt.json"
        self.receipt = {
            "schema_version": 1, "run_id": self.run_id,
            "started_at": datetime.now(timezone.utc).isoformat(),
            "mode": "validate-only" if args.validate_only else "deploy",
            "status": "RUNNING", "target": TARGET, "checks": [],
            "log": str(self.log_path), "installation_attempted": False,
            "timeouts_seconds": {"operation": args.timeout, "build": args.build_timeout},
        }
        self.stage = "initialization"
        self.locked = False
        self.remote_uncertain = False
        self.host_dir = None
        self.container_dir = "/tmp/pfoa-theme-deploy-" + self.run_id
        self.save()

    def save(self):
        atomic_json(self.receipt_path, self.receipt)
        # A sidecar checks accidental corruption; it is not a signature.
        self.receipt_path.with_suffix(".json.sha256").write_text(
            digest(self.receipt_path) + "  receipt.json\n")

    def check(self, name, **details):
        self.receipt["checks"].append({"stage": name, "result": "PASS", **details})
        self.save()
        print("PASS: " + name, flush=True)

    def run(self, stage, argv, *, cwd=None, timeout=None):
        self.stage = stage
        limit = timeout or self.args.timeout
        self.log.write(("\n[" + stage + "] " + shlex.join(map(str, argv)) + "\n").encode())
        self.log.flush()
        proc = subprocess.Popen(argv, cwd=cwd, stdin=subprocess.DEVNULL,
                                stdout=subprocess.PIPE, stderr=subprocess.PIPE,
                                start_new_session=True)
        try:
            out, err = proc.communicate(timeout=limit)
        except (subprocess.TimeoutExpired, KeyboardInterrupt, SystemExit) as exc:
            # Kill the entire process group, including descendants holding pipes
            # open after their parent exits. Never retry an unresolved client.
            try:
                os.killpg(proc.pid, signal.SIGKILL)
            except ProcessLookupError:
                pass
            out, err = proc.communicate(timeout=5)
            self.log.write(out + err)
            self.log.flush()
            self.receipt["process_cleanup"] = {"stage": stage, "process_group": proc.pid,
                                                "signal": "SIGKILL", "parent_reaped": True}
            # Verify there are no live members (zombies cannot own sockets).
            members = subprocess.run(["ps", "-axo", "pgid=,stat=,pid="],
                                     capture_output=True, timeout=5, check=True).stdout.decode()
            live = [line for line in members.splitlines() if line.split()[0] == str(proc.pid)
                    and not line.split()[1].startswith("Z")]
            if live:
                raise RuntimeError("Timeout cleanup incomplete; live process group: " + repr(live))
            self.receipt["process_cleanup"]["no_live_group_members"] = True
            if isinstance(exc, subprocess.TimeoutExpired):
                raise RuntimeError(f"{stage}: timed out after {limit}s; process group killed and reaped") from exc
            raise
        self.log.write(out + err)
        self.log.flush()
        if proc.returncode:
            detail = (err or out).decode(errors="replace")[-3000:].strip()
            raise RuntimeError(f"{stage}: command exited {proc.returncode}: {detail}")
        return out.decode("utf-8").strip()

    def ssh(self, stage, script):
        # Each Docker client has a host deadline; container work has its own
        # deadline because killing docker exec alone does not stop its process.
        self.remote_uncertain = True
        result = self.run(stage, ["ssh", "-T", "-o", "BatchMode=yes", "-o",
            "StrictHostKeyChecking=yes", "-o", "ConnectTimeout=10", "-o",
            "ControlMaster=no", "-o", "ControlPath=none", "-o",
            "ServerAliveInterval=5", "-o", "ServerAliveCountMax=2", TARGET["ssh"],
            "timeout --signal=KILL " + str(self.args.timeout + 10) + "s bash -c " +
            shlex.quote("set -euo pipefail\n" + script)], timeout=self.args.timeout + 20)
        self.remote_uncertain = False
        return result

    def docker(self, *args):
        return shlex.join(["timeout", "--signal=KILL", str(self.args.timeout + 5) + "s", "docker", *args])

    def container(self, *args):
        return self.docker("exec", "--user", "0", TARGET["container"], "timeout",
                           "--signal=KILL", str(self.args.timeout) + "s", *args)

    def wp(self, *args):
        return self.container("php", TARGET["wordpress_root"] + "/wp-cli.phar",
            "--path=" + TARGET["wordpress_root"], "--allow-root", "--skip-plugins",
            "--skip-themes", "--skip-packages", "--no-color", *args)

    def identity(self, stage):
        data = json.loads(self.ssh(stage, self.wp("eval", IDENTITY_PHP)))
        for key in ("home", "siteurl", "stylesheet", "template"):
            if data.get(key) != TARGET[key]:
                raise RuntimeError(f"{stage}: incorrect target {key}: expected {TARGET[key]!r}, got {data.get(key)!r}")
        if data.get("multisite") is not False:
            raise RuntimeError("Multisite is unsupported; refusing ambiguous target")
        expected_directory = TARGET["wordpress_root"] + "/wp-content/themes/pfoa-theme"
        if data.get("theme_directory") != expected_directory:
            raise RuntimeError("Unexpected installed theme directory: " + str(data.get("theme_directory")))
        if not data.get("version") or not isinstance(data.get("files"), dict) or not data["files"]:
            raise RuntimeError("Incomplete WordPress identity/file manifest")
        return data

    def hash_remote(self, stage, path, container=False):
        if container:
            command = self.container("php", "-r", "echo hash_file('sha256', $argv[1]);", path)
        else:
            command = "sha256sum -- " + shlex.quote(path)
        result = self.ssh(stage, command).split()[0]
        if not re.fullmatch("[0-9a-f]{64}", result):
            raise RuntimeError(stage + ": invalid SHA-256 readback")
        return result

    def equal_hash(self, stage, path, expected, container=False):
        actual = self.hash_remote(stage, path, container)
        if actual != expected:
            raise RuntimeError(f"{stage}: checksum mismatch: expected {expected}, got {actual}")
        self.check(stage, sha256=actual)

    def scp(self, stage, source, destination):
        self.run(stage, ["scp", "-q", "-o", "BatchMode=yes", "-o",
            "StrictHostKeyChecking=yes", "-o", "ConnectTimeout=10", "-o",
            "ControlMaster=no", "-o", "ControlPath=none", source, destination])

    def snapshot_and_build(self, work):
        commit = self.run("resolve committed source", ["git", "rev-parse", "--verify",
            "--end-of-options", self.args.commit + "^{commit}"], cwd=self.repo)
        self.receipt["commit_sha"] = commit
        if self.run("tracked source cleanliness", ["git", "status", "--porcelain", "--untracked-files=no"], cwd=self.repo):
            raise RuntimeError("Tracked changes present; commit the implementation before deployment")
        # git archive cannot include an old working-tree ZIP or untracked files.
        archive = work / "source.tar"
        self.run("capture committed snapshot", ["git", "archive", "--format=tar", "--output=" + str(archive), commit], cwd=self.repo)
        self.receipt["source_archive_sha256"] = digest(archive)
        source = work / "source"
        source.mkdir()
        with tarfile.open(archive) as tf:
            for member in tf.getmembers():
                path = PurePosixPath(member.name)
                if path.is_absolute() or ".." in path.parts or not (member.isdir() or member.isfile()):
                    raise RuntimeError("Snapshot contains a nonregular/unsafe member: " + member.name)
            tf.extractall(source)
        if (source / "dist").exists():
            raise RuntimeError("Stale build output committed in snapshot: dist must be absent")
        theme = source / "pfoa-theme"
        for relative in ("scripts/deploy-theme.sh", "scripts/theme_deploy.py",
                         "scripts/verify-theme.sh", "scripts/build-zip.sh"):
            if not (source / relative).is_file() or digest(source / relative) != digest(self.repo / relative):
                raise RuntimeError("Selected commit must contain the executing deployment/gate scripts: " + relative)
        source_hashes = {p.relative_to(source).as_posix(): digest(p)
                         for p in source.rglob("*") if p.is_file()}
        expected_version = version((theme / "style.css").read_bytes())
        expected = {}
        for path in sorted(theme.rglob("*")):
            if path.is_file():
                relative = path.relative_to(theme).as_posix()
                if not any(fnmatch.fnmatchcase(relative, pat) for pat in EXCLUDES):
                    expected[relative] = digest(path)
        # Build calls the mandatory gate; run from the immutable extracted tree.
        self.run("mandatory verification and fresh packaging", ["bash", str(source / "scripts/build-zip.sh")],
                 cwd=source, timeout=self.args.build_timeout)
        after_hashes = {p.relative_to(source).as_posix(): digest(p)
                        for p in source.rglob("*") if p.is_file() and p.relative_to(source).parts[0] != "dist"}
        if after_hashes != source_hashes:
            raise RuntimeError("Committed snapshot changed during verification/build")
        self.check("mandatory verification and fresh packaging")
        self.stage = "package validation"
        packages = list((source / "dist").iterdir())
        candidate = source / "dist" / ("pfoa-theme-" + expected_version + ".zip")
        if packages != [candidate] or not candidate.is_file() or candidate.is_symlink():
            raise RuntimeError("Expected exactly one fresh versioned ZIP; stale/ambiguous output refused")
        manifest = {}
        with zipfile.ZipFile(candidate) as zf:
            if zf.testzip() is not None:
                raise RuntimeError("ZIP integrity test failed")
            for entry in zf.infolist():
                p = PurePosixPath(entry.filename)
                if (p.is_absolute() or ".." in p.parts or str(p) != entry.filename
                    or len(p.parts) < 2 or p.parts[0] != "pfoa-theme" or entry.is_dir()
                    or stat.S_ISLNK(entry.external_attr >> 16)):
                    raise RuntimeError("Unsafe or unexpected ZIP member: " + entry.filename)
                name = str(PurePosixPath(*p.parts[1:]))
                if name in manifest:
                    raise RuntimeError("Duplicate ZIP member: " + name)
                manifest[name] = hashlib.sha256(zf.read(entry)).hexdigest()
            if version(zf.read("pfoa-theme/style.css")) != expected_version:
                raise RuntimeError("Embedded theme version mismatch")
        if manifest != expected or not all(name in manifest for name in REQUIRED):
            raise RuntimeError("Stale or mismatched ZIP: packaged files differ from verified source projection")
        self.package = self.output / candidate.name
        shutil.copyfile(candidate, self.package)
        self.package.chmod(0o400)
        self.package_hash = digest(self.package)
        if digest(candidate) != self.package_hash:
            raise RuntimeError("Package checksum mismatch while retaining verified artifact")
        self.manifest = manifest
        atomic_json(self.output / "package-files.json", manifest)
        self.receipt["package"] = {"path": str(self.package), "sha256": self.package_hash,
            "version": expected_version, "file_count": len(manifest),
            "manifest_sha256": digest(self.output / "package-files.json")}
        self.receipt["verification_scripts"] = {
            name: digest(self.repo / "scripts" / name)
            for name in ("verify-theme.sh", "build-zip.sh", "theme_deploy.py")}
        self.check("ZIP integrity, embedded version and committed-source parity", sha256=self.package_hash)

    def remote_prepare(self):
        self.before = self.identity("target identity before backup")
        self.receipt["before"] = self.before
        self.check("DEV site identity and active theme")
        self.stage = "acquire DEV deployment lock"
        # No automatic lock stealing; an interrupted deploy must be inspected.
        script = 'umask 077\nbase="$HOME/.local/state/pfoa-theme-deploy"\nmkdir -p "$base"\n'
        script += 'mkdir "$base/dev.lock" || { echo "DEV deployment lock exists; inspect its owner before retrying" >&2; exit 1; }\n'
        script += 'printf "%s\\n" ' + shlex.quote(self.run_id) + ' > "$base/dev.lock/owner"\n'
        script += 'mkdir "$base/' + self.run_id + '"\nprintf "%s" "$base/' + self.run_id + '"'
        self.locked = True
        self.host_dir = self.ssh(self.stage, script)
        if not re.fullmatch(r"/[A-Za-z0-9/_.-]+", self.host_dir):
            raise RuntimeError("Unsafe host staging directory readback")
        self.receipt["host_directory"] = self.host_dir
        self.receipt["container_directory"] = self.container_dir
        self.save()
        self.ssh("prepare private container staging", self.container("mkdir", "-m", "700", self.container_dir))
        self.backup()

    def backup(self):
        self.stage = "backup installed theme"
        backup = self.container_dir + "/theme-before.tar.gz"
        host_backup = self.host_dir + "/theme-before.tar.gz"
        self.receipt["backup"] = {"container_path": backup, "host_path": host_backup,
            "local_path": str(self.output / "theme-before.tar.gz"), "verified": False,
            "version": self.before["version"], "files": self.before["files"]}
        self.save()
        self.ssh(self.stage, self.container("env", "COPYFILE_DISABLE=1", "tar", "-czf", backup, "-C",
            TARGET["wordpress_root"] + "/wp-content/themes", "pfoa-theme"))
        backup_hash = self.hash_remote("container backup checksum", backup, True)
        self.receipt["backup"]["sha256"] = backup_hash
        self.save()
        self.ssh("retain backup on host", self.docker("cp", TARGET["container"] + ":" + backup, host_backup))
        self.equal_hash("host backup checksum", host_backup, backup_hash)
        local = self.output / "theme-before.tar.gz"
        self.scp("retain backup locally", TARGET["ssh"] + ":" + host_backup, str(local))
        if digest(local) != backup_hash:
            raise RuntimeError("Local backup checksum mismatch")
        actual = {}
        with tarfile.open(local) as tf:
            for item in tf.getmembers():
                p = PurePosixPath(item.name)
                if p.is_absolute() or ".." in p.parts or p.parts[0] != "pfoa-theme":
                    raise RuntimeError("Unsafe backup member: " + item.name)
                if item.isdir():
                    continue
                name = str(PurePosixPath(*p.parts[1:]))
                if not item.isfile() or name in actual:
                    raise RuntimeError("Nonregular or duplicate backup member: " + item.name)
                actual[name] = hashlib.sha256(tf.extractfile(item).read()).hexdigest()
        if actual != self.before["files"]:
            raise RuntimeError("Backup file parity failed; installed theme changed during backup")
        self.receipt["backup"]["verified"] = True
        self.ssh("write host backup checksum", "printf '%s  theme-before.tar.gz\\n' " +
                 shlex.quote(backup_hash) + " > " + shlex.quote(self.host_dir + "/theme-before.tar.gz.sha256"))
        self.check("checksummed backup and pre-install file parity", sha256=backup_hash)

    def transfer_and_install(self):
        self.stage = "local package checksum before transfer"
        if digest(self.package) != self.package_hash:
            raise RuntimeError("Verified package checksum mismatch before transfer")
        host_package = self.host_dir + "/theme.zip"
        container_package = self.container_dir + "/theme.zip"
        self.scp("transfer verified ZIP to host", str(self.package), TARGET["ssh"] + ":" + host_package)
        self.equal_hash("host package checksum", host_package, self.package_hash)
        self.ssh("transfer verified ZIP to container", self.docker("cp", host_package,
                 TARGET["container"] + ":" + container_package))
        self.equal_hash("container package checksum", container_package, self.package_hash, True)
        current = self.identity("target identity immediately before installation")
        if current != self.before:
            raise RuntimeError("Target/theme changed after backup; refusing installation")
        self.check("target identity and backup still current")
        if self.args.validate_only:
            self.receipt["checks"].extend([
                {"stage": "WP-CLI installation", "result": "SKIPPED", "reason": "validate-only"},
                {"stage": "installed version and file parity", "result": "SKIPPED", "reason": "validate-only"}])
            self.receipt["status"] = "VALIDATED_NOT_DEPLOYED"
            return
        # Check and install in the same container command, with no version/path
        # selection or archive rebuild between verification and WP-CLI.
        hash_code = "if (hash_file('sha256', $argv[1]) !== $argv[2]) {fwrite(STDERR, 'Package checksum mismatch before installation'); exit(1);}"
        install = self.container("bash", "-c", shlex.join(["php", "-r", hash_code,
            container_package, self.package_hash]) + " && " + shlex.join(["php",
            TARGET["wordpress_root"] + "/wp-cli.phar", "--path=" + TARGET["wordpress_root"],
            "--allow-root", "--skip-plugins", "--skip-themes", "--skip-packages", "--no-color",
            "theme", "install", container_package, "--force"]))
        self.receipt["installation_attempted"] = True
        self.save()
        self.ssh("WP-CLI installation", install)
        self.check("WP-CLI installation")
        after = self.identity("post-install active theme and file readback")
        self.receipt["after"] = after
        self.save()
        if after["version"] != self.receipt["package"]["version"]:
            raise RuntimeError("Installed active version mismatch: expected " + self.receipt["package"]["version"] + ", got " + after["version"])
        if after["files"] != self.manifest:
            missing = sorted(set(self.manifest) - set(after["files"]))
            extra = sorted(set(after["files"]) - set(self.manifest))
            changed = sorted(k for k in set(self.manifest) & set(after["files"]) if self.manifest[k] != after["files"][k])
            raise RuntimeError(f"Installed file parity failed; missing={missing}, extra={extra}, changed={changed}")
        self.check("active theme slug, version and exact installed-file parity", version=after["version"])
        self.receipt["installed_version"] = after["version"]
        self.receipt["status"] = "DEPLOYED_VERIFIED"

    def release_lock(self):
        # Skip all remote work after a lost/timed-out SSH operation. Its remote
        # hard deadlines will expire; retain lock and report uncertain state.
        if self.locked and not self.remote_uncertain:
            self.ssh("release DEV deployment lock", 'base="$HOME/.local/state/pfoa-theme-deploy/dev.lock"\n'
                'test "$(cat "$base/owner")" = ' + shlex.quote(self.run_id) + '\nrm "$base/owner"\nrmdir "$base"')
            self.locked = False

    def execute(self):
        code = 0
        try:
            for dependency in ("bash", "git", "php", "zip", "unzip", "ssh", "scp"):
                if shutil.which(dependency) is None:
                    raise RuntimeError("Missing required command: " + dependency)
            self.check("explicit DEV selection", **TARGET)
            with tempfile.TemporaryDirectory(prefix="pfoa-verified-build-") as temp:
                self.snapshot_and_build(Path(temp))
            self.remote_prepare()
            self.transfer_and_install()
        except (Exception, KeyboardInterrupt) as exc:
            code = 1
            self.receipt["status"] = "FAILED"
            self.receipt["failure"] = {"stage": self.stage, "message": str(exc) or "Interrupted"}
            self.receipt["checks"].append({"stage": self.stage, "result": "FAIL", "detail": str(exc)})
            print(f"FAILED [{self.stage}]: {exc}", file=sys.stderr)
        finally:
            try:
                self.release_lock()
            except (Exception, KeyboardInterrupt) as exc:
                code = 1
                self.receipt["status"] = "FAILED"
                self.receipt["lock_cleanup_error"] = str(exc)
                print("FAILED [lock cleanup]: " + str(exc), file=sys.stderr)
            self.receipt["remote_state_uncertain"] = self.remote_uncertain
            self.receipt["remote_lock_retained"] = self.locked
            self.receipt["finished_at"] = datetime.now(timezone.utc).isoformat()
            if code:
                self.receipt["recovery"] = (
                    "Stop. Inspect the retained log and backup. Do not retry while a remote operation or lock is unresolved. "
                    "Remote Docker clients and container commands have independent hard deadlines. "
                    "If installation was attempted, verify DEV state and use the documented checksummed rollback procedure; "
                    "rollback is never automatic.")
            self.log.close()
            self.receipt["log_sha256"] = digest(self.log_path)
            self.save()
            print("Receipt: " + str(self.receipt_path), flush=True)
        return code


def main():
    parser = argparse.ArgumentParser(description="Build and verify an immutable committed theme ZIP for DEV only")
    parser.add_argument("--environment", required=True, choices=["dev"])
    parser.add_argument("--commit", required=True, help="committed source ref/SHA, resolved once")
    mode = parser.add_mutually_exclusive_group(required=True)
    mode.add_argument("--validate-only", action="store_true", help="verify, back up and transfer; never install")
    mode.add_argument("--deploy", action="store_true", help="install only after all preflight checks")
    parser.add_argument("--timeout", type=int, default=120, help="per-operation deadline in seconds (default: 120)")
    parser.add_argument("--build-timeout", type=int, default=300, help="build/gate deadline in seconds (default: 300)")
    args = parser.parse_args()
    if not 1 <= args.timeout <= 1800 or not 1 <= args.build_timeout <= 1800:
        parser.error("timeouts must be between 1 and 1800 seconds")
    def interrupted(signum, frame):
        raise KeyboardInterrupt("Terminated")
    signal.signal(signal.SIGTERM, interrupted)
    return Deployment(args).execute()


if __name__ == "__main__":
    sys.exit(main())
