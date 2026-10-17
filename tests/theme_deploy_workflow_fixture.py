#!/usr/bin/env python3
"""Exercise the real deployment entry point with isolated transport/WP doubles."""
import hashlib
import json
import os
from pathlib import Path
import shutil
import subprocess
import sys
import tempfile
import uuid

ROOT = Path(__file__).resolve().parent.parent
REPORT = ROOT / "deployments" / ("workflow-fixtures-" + uuid.uuid4().hex[:12])
REPORT.mkdir(parents=True)
MANDATORY = [
    "theme-adoption-badge-conditional-fixture.php",
    "theme-adoption-event-gallery-integrity-fixture.php",
    "theme-adoption-event-legacy-anchor-fixture.php",
    "theme-adoption-event-senior-shadow-fixture.php",
    "theme-adoption-universal-green-regression-fixture.php",
    "theme-adopted-gallery-presentation-fixture.php",
    "theme-recently-adopted-currency-fixture.php",
    "theme-recently-adopted-selection-fixture.php",
]
DOUBLE = r'''#!/usr/bin/env python3
import json, os, pathlib, shutil, subprocess, sys, time
state = pathlib.Path(os.environ['PFOA_TEST_STATE'])
kind = pathlib.Path(sys.argv[0]).name
scenario = os.environ['PFOA_TEST_SCENARIO']
a = sys.argv[1:]
with (state / 'events.jsonl').open('a') as f:
    f.write(json.dumps({'tool':kind, 'args':a}) + '\n')
if kind == 'ssh':
    assert 'michael@100.120.233.4' in a
    assert 'StrictHostKeyChecking=yes' in a and 'ControlMaster=no' in a
    env = dict(os.environ, HOME=str(state / 'home'))
    sys.exit(subprocess.call(['bash', '-c', a[-1]], env=env))
if kind == 'scp':
    assert 'StrictHostKeyChecking=yes' in a
    if scenario == 'transfer-failed' and a[-1].endswith('theme.zip'):
        print('simulated SCP transport failure', file=sys.stderr); sys.exit(33)
    if scenario == 'transfer-timeout' and a[-1].endswith('theme.zip'):
        child = subprocess.Popen(['sleep','60'])
        (state / 'child.pid').write_text(str(child.pid))
        child.wait()
    def local(p):
        if ':' in p:
            host, p = p.split(':',1); assert host == 'michael@100.120.233.4'
        return p
    shutil.copyfile(local(a[-2]), local(a[-1]))
    if scenario == 'host-corrupt' and a[-1].endswith('theme.zip'):
        with open(local(a[-1]), 'ab') as f: f.write(b'corrupt transfer')
    sys.exit(0)
assert kind == 'docker'
container = state / 'container'
def mapped(s):
    return s.replace('/var/www/html', str(container / 'www')).replace(
        '/tmp/pfoa-theme-deploy-', str(container / 'tmp' / 'pfoa-theme-deploy-'))
if a[0] == 'cp':
    def local(p):
        if p.startswith('pfoa-dev-wordpress:'): p = p.split(':',1)[1]
        return mapped(p)
    if scenario == 'docker-transfer-failed' and a[1].endswith('theme.zip'):
        print('simulated docker cp failure', file=sys.stderr); sys.exit(34)
    shutil.copyfile(local(a[1]), local(a[2]))
    if scenario == 'container-corrupt' and a[1].endswith('theme.zip'):
        with open(local(a[2]), 'ab') as f: f.write(b'corrupt container transfer')
    sys.exit(0)
assert a[:4] == ['exec','--user','0','pfoa-dev-wordpress']
cmd = [mapped(x) for x in a[4:]]
is_install = 'theme install' in ' '.join(cmd)
if is_install:
    with (state / 'events.jsonl').open('a') as f: f.write('{"tool":"install-command"}\n')
    if scenario == 'install-timeout':
        # Execute INSIDE the generated container timeout, rather than hanging
        # the docker client, proving the container deadline is effective.
        cmd = cmd[:3] + ['bash','-c','sleep 60 & echo $! > "$PFOA_TEST_STATE/child.pid"; wait']
if scenario == 'backup-failed' and 'tar' in cmd:
    print('simulated backup failure', file=sys.stderr); sys.exit(38)
result = subprocess.run(cmd, capture_output=True)
sys.stdout.buffer.write(result.stdout.replace(str(container / 'www').encode(), b'/var/www/html'))
sys.stderr.buffer.write(result.stderr)
if scenario == 'late-container-corrupt' and 'eval' in cmd and list((container / 'tmp').glob('*/theme.zip')):
    for zip_path in (container / 'tmp').glob('*/theme.zip'):
        with zip_path.open('ab') as f: f.write(b'changed after transfer verification')
sys.exit(result.returncode)
'''
WP = r'''<?php
// Only loaded in the test container tree. Execute the wrapper's actual eval
// code against deterministic WordPress API doubles and real filesystem hashes.
function scenario() { return getenv('PFOA_TEST_SCENARIO'); }
function installed() { return file_exists(getenv('PFOA_TEST_STATE') . '/installed'); }
function get_option($name) {
    if (scenario() === 'wrong-target' && $name === 'home') return 'https://safehavenpfoa.org';
    if (scenario() === 'wrong-siteurl' && $name === 'siteurl') return 'https://wrong.example';
    return 'https://dev.safehavenpfoa.org/';
}
function is_multisite() { return scenario() === 'multisite'; }
function get_stylesheet() { return scenario() === 'wrong-active' && installed() ? 'other-theme' : 'pfoa-theme'; }
function get_template() { return 'pfoa-theme'; }
function get_stylesheet_directory() { return __DIR__ . '/wp-content/themes/pfoa-theme'; }
class WP_CLI { public static function error($message) { fwrite(STDERR, $message); exit(1); } }
class FixtureTheme {
    public function get($field) {
        if (scenario() === 'wrong-version' && installed()) return '0.0.0';
        preg_match('/^\s*Version:\s*(\S+)/m', file_get_contents(get_stylesheet_directory() . '/style.css'), $m);
        return $m[1];
    }
}
function wp_get_theme() { return new FixtureTheme(); }
$args = array_values(array_filter(array_slice($argv,1), function($s) { return strpos($s,'--') !== 0; }));
if ($args[0] === 'eval') { eval($args[1]); exit(0); }
if ($args[0] !== 'theme' || $args[1] !== 'install') WP_CLI::error('Unexpected WP command');
file_put_contents(getenv('PFOA_TEST_STATE') . '/events.jsonl', json_encode(array('tool'=>'install')) . "\n", FILE_APPEND);
if (scenario() === 'install-failed') WP_CLI::error('simulated WP-CLI install failure: filesystem not writable');
$d = get_stylesheet_directory();
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($d, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
foreach ($it as $f) { if ($f->isDir()) rmdir($f->getPathname()); else unlink($f->getPathname()); }
rmdir($d);
exec('unzip -q ' . escapeshellarg($args[2]) . ' -d ' . escapeshellarg(dirname($d)), $out, $status);
if ($status) WP_CLI::error('unzip failed');
file_put_contents(getenv('PFOA_TEST_STATE') . '/installed', 'yes');
if (scenario() === 'wrong-files') file_put_contents($d . '/unexpected.php', '<?php // stale');
echo 'Success: Installed 1 of 1 themes.';
'''


def call(argv, cwd=None, env=None, timeout=45):
    proc = subprocess.Popen(argv, cwd=cwd, env=env, start_new_session=True,
                            stdout=subprocess.PIPE, stderr=subprocess.STDOUT)
    try:
        output = proc.communicate(timeout=timeout)[0]
    except subprocess.TimeoutExpired:
        os.killpg(proc.pid, 9)
        proc.communicate(timeout=5)
        raise AssertionError("Fixture subprocess exceeded its hard deadline: " + repr(argv))
    return proc.returncode, output.decode(errors="replace")


def must(argv, cwd=None):
    code, out = call(argv, cwd=cwd)
    assert code == 0, out
    return out.strip()


def commit(repo):
    must(['git','add','-A'], repo)
    must(['git','-c','user.name=Deployment Fixture','-c','user.email=fixture@example.invalid',
          'commit','--quiet','--allow-empty','-m','isolated workflow fixture'], repo)
    return must(['git','rev-parse','HEAD'], repo)


results = []
with tempfile.TemporaryDirectory(prefix='pfoa-workflow-fixtures-') as temp:
    temp = str(Path(temp).resolve())
    base = Path(temp) / 'base'
    base.mkdir()
    for name in ('scripts','tests','pfoa-theme'):
        shutil.copytree(ROOT / name, base / name, ignore=shutil.ignore_patterns('__pycache__'))
    shutil.copyfile(ROOT / '.gitignore', base / '.gitignore')
    must(['git','init','--quiet'], base)
    commit(base)

    def exercise(name, *, scenario='success', mutate=None, mode='--deploy', failure=None,
                 attempted=False, reached=None, options=(), dirty=False):
        case = Path(temp) / name
        repo = case / 'repo with spaces'
        shutil.copytree(base, repo)
        state = case / 'state'
        state.mkdir()
        (state / 'home').mkdir()
        (state / 'container/tmp').mkdir(parents=True)
        themes = state / 'container/www/wp-content/themes'
        themes.mkdir(parents=True)
        shutil.copytree(ROOT / 'pfoa-theme', themes / 'pfoa-theme')
        # Different installed version/content prove backup != fresh package.
        style = themes / 'pfoa-theme/style.css'
        style.write_text(style.read_text().replace('Version: 0.1.157', 'Version: 0.1.156'))
        (state / 'container/www/wp-cli.phar').write_text(WP)
        if mutate:
            mutate(repo)
        sha = commit(repo)
        if dirty:
            (repo / 'pfoa-theme/functions.php').open('a').write('\n// uncommitted\n')
        bindir = case / 'bin'
        bindir.mkdir()
        for tool in ('ssh','scp','docker'):
            path = bindir / tool
            path.write_text(DOUBLE)
            path.chmod(0o755)
        env = dict(os.environ, PATH=str(bindir) + os.pathsep + os.environ['PATH'],
                   PFOA_TEST_STATE=str(state), PFOA_TEST_SCENARIO=scenario)
        code, output = call(['bash',str(repo / 'scripts/deploy-theme.sh'), '--environment','dev',
             '--commit','HEAD',mode,'--timeout','10','--build-timeout','30',*options], cwd=repo, env=env)
        receipts = list((repo / 'deployments').glob('*/receipt.json'))
        assert len(receipts) == 1, (name, output)
        path = receipts[0]
        receipt = json.loads(path.read_text())
        checksum = path.with_suffix('.json.sha256').read_text().split()[0]
        assert checksum == hashlib.sha256(path.read_bytes()).hexdigest(), name + ': receipt checksum'
        assert receipt['commit_sha'] == sha and receipt['target']['ssh'] == 'michael@100.120.233.4'
        assert receipt['log_sha256'] == hashlib.sha256(Path(receipt['log']).read_bytes()).hexdigest()
        events = [json.loads(s) for s in (state / 'events.jsonl').read_text().splitlines()] if (state / 'events.jsonl').exists() else []
        installs = [e for e in events if e['tool'] == 'install']
        submitted = [e for e in events if e['tool'] == 'install-command']
        assert bool(submitted) == attempted, (name, output)
        assert bool(installs) == (attempted if reached is None else reached), (name, output)
        assert receipt['installation_attempted'] == attempted, (name, receipt)
        if failure:
            assert code != 0 and receipt['status'] == 'FAILED', (name, output)
            detail = receipt['failure']['message']
            assert failure.lower() in (detail + output + Path(receipt['log']).read_text()).lower(), (name, detail, output)
            assert receipt['recovery'] and receipt['failure']['stage'], name + ': actionable report'
        else:
            assert code == 0, (name, output, Path(receipt['log']).read_text())
            assert receipt['status'] == ('VALIDATED_NOT_DEPLOYED' if mode == '--validate-only' else 'DEPLOYED_VERIFIED')
            package = Path(receipt['package']['path'])
            assert hashlib.sha256(package.read_bytes()).hexdigest() == receipt['package']['sha256']
            assert receipt['backup']['verified'] and receipt['backup']['version'] == '0.1.156'
            assert hashlib.sha256(Path(receipt['backup']['local_path']).read_bytes()).hexdigest() == receipt['backup']['sha256']
            if attempted:
                assert receipt['after']['files'] == json.loads((path.parent / 'package-files.json').read_text())
                assert receipt['installed_version'] == '0.1.157'
            else:
                assert sum(c['result'] == 'SKIPPED' for c in receipt['checks']) == 2
        if name.startswith('missing-') or name in ('badge-regression','extra-fixture-failure','failed-mandatory'):
            assert not events, name + ': failing gate reached remote commands'
            assert 'package' not in receipt, name + ': failing gate produced package'
        pidfile = state / 'child.pid'
        if pidfile.exists():
            child = pidfile.read_text().strip()
            code_ps, stat_ps = call(['ps','-o','stat=','-p',child], timeout=5)
            assert code_ps != 0 or stat_ps.strip().startswith('Z'), name + ': live child remains'
        evidence = REPORT / name
        shutil.copytree(path.parent, evidence)
        (evidence / 'console.log').write_text(output)
        (evidence / 'events.json').write_text(json.dumps(events, indent=2))
        results.append({'case':name,'result':'PASS','status':receipt['status'],
                        'installation_attempted':attempted,'failure':receipt.get('failure')})
        print('PASS: ' + name, flush=True)

    exercise('verified-deploy', attempted=True)
    exercise('validation-only', mode='--validate-only')
    for fixture in MANDATORY:
        exercise('missing-' + fixture, mutate=lambda repo, f=fixture: (repo / 'tests' / f).unlink(),
                 failure='mandatory theme verification fixture missing')

    def regress(repo):
        path = repo / 'pfoa-theme/functions.php'
        text = path.read_text()
        assert text.count('pfoa-adoption-event-bonded-badge') >= 2
        path.write_text(text.replace('pfoa-adoption-event-bonded-badge','pfoa-adoption-event-together-badge'))
    exercise('badge-regression', mutate=regress, failure='Bonded Pair')
    exercise('failed-mandatory', mutate=lambda repo: (repo / 'tests' / MANDATORY[0]).write_text('<?php fwrite(STDERR,"mandatory fixture failed"); exit(9);'), failure='mandatory fixture failed')
    exercise('extra-fixture-failure', mutate=lambda repo: (repo / 'tests/theme-adoption-extra-fixture.php').write_text('<?php fwrite(STDERR,"discovered fixture failed"); exit(8);'), failure='discovered fixture failed')

    def stale(repo):
        # Replace a freshly generated ZIP with an older-content package having
        # the same filename/version and valid CRCs. Source must reject it.
        path = repo / 'scripts/build-zip.sh'
        with path.open('a') as stream:
            stream.write('''\npython3 - "$archive" <<'EDITZIP'
import sys, zipfile, os
p = sys.argv[1]
with zipfile.ZipFile(p) as src, zipfile.ZipFile(p + '.new', 'w') as dst:
    for n in src.namelist():
        dst.writestr(n, b'<?php // stale package' if n.endswith('/functions.php') else src.read(n))
os.replace(p + '.new', p)
EDITZIP
''')
    exercise('stale-valid-zip', mutate=stale, failure='Stale or mismatched ZIP')
    def committed_dist(repo):
        (repo / 'dist').mkdir()
        (repo / 'dist/stale.txt').write_text('stale')
    exercise('committed-stale-dist', mutate=committed_dist, failure='Stale build output committed')
    exercise('tracked-dirty-source', dirty=True, failure='Tracked changes present')
    exercise('host-checksum-mismatch', scenario='host-corrupt', failure='checksum mismatch')
    exercise('container-checksum-mismatch', scenario='container-corrupt', failure='checksum mismatch')
    exercise('checksum-mismatch-before-install', scenario='late-container-corrupt', failure='checksum mismatch before installation', attempted=True, reached=False)
    exercise('wrong-target', scenario='wrong-target', failure='incorrect target home')
    exercise('wrong-siteurl', scenario='wrong-siteurl', failure='incorrect target siteurl')
    exercise('multisite-target', scenario='multisite', failure='Multisite is unsupported')
    exercise('backup-failure', scenario='backup-failed', failure='simulated backup failure')
    exercise('failed-transfer', scenario='transfer-failed', failure='simulated SCP transport failure')
    exercise('failed-container-transfer', scenario='docker-transfer-failed', failure='simulated docker cp failure')
    exercise('failed-install', scenario='install-failed', failure='filesystem not writable', attempted=True)
    exercise('wrong-version-readback', scenario='wrong-version', failure='active version mismatch', attempted=True)
    exercise('wrong-active-readback', scenario='wrong-active', failure='incorrect target stylesheet', attempted=True)
    exercise('wrong-file-readback', scenario='wrong-files', failure='Installed file parity failed', attempted=True)
    def slow_build(repo):
        path = repo / 'scripts/build-zip.sh'
        path.write_text(path.read_text().replace('set -euo pipefail', 'set -euo pipefail\nsleep 60 & echo "$!" > "$PFOA_TEST_STATE/child.pid"; wait', 1))
    exercise('build-timeout', mutate=slow_build, options=('--build-timeout','1'), failure='timed out')
    exercise('transfer-timeout', scenario='transfer-timeout', options=('--timeout','1'), failure='timed out')
    exercise('container-install-timeout', scenario='install-timeout', options=('--timeout','1'), failure='exited', attempted=True, reached=False)

atomic = {'result':'PASS','cases':results,'count':len(results),'network':'isolated test doubles only'}
(REPORT / 'results.json').write_text(json.dumps(atomic, indent=2) + '\n')
print(f'PASS: all {len(results)} deployment workflow scenarios; evidence: {REPORT}')
