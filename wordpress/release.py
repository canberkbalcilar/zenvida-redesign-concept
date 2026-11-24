"""Release a new ZenVida Concept version.

    python3 -I wordpress/release.py 1.7.1

1. Write the changelog entry in zenvida-concept/readme.txt first ("= 1.7.1 =" + "* " lines).
2. This script sets the version in style.css, functions.php, blueprint.json and index.html,
   writes update.json (what installed sites read to offer the update) and rebuilds the zip.
3. Commit and push; GitHub Pages serves the new update.json within a few minutes.
"""
import json
import os
import re
import sys
import zipfile

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
WP = os.path.join(ROOT, 'wordpress')
THEME = os.path.join(WP, 'zenvida-concept')
BASE = 'https://canberkbalcilar.github.io/zenvida-redesign-concept/wordpress/'


def read(p):
    with open(p, encoding='utf-8') as f:
        return f.read()


def write(p, s):
    with open(p, 'w', encoding='utf-8') as f:
        f.write(s)


new = sys.argv[1] if len(sys.argv) > 1 else ''
if not re.fullmatch(r'\d+\.\d+\.\d+', new):
    sys.exit('usage: release.py X.Y.Z')

old = re.search(r'^Version:\s*(\S+)', read(os.path.join(THEME, 'style.css')), re.M).group(1)
readme = read(os.path.join(THEME, 'readme.txt'))
log = readme.split('== Changelog ==', 1)[1]
notes = []
for m in re.finditer(r'^= (\d+\.\d+\.\d+) =\n((?:\* .*\n?)+)', log, re.M):
    notes.append({'version': m.group(1), 'items': [l[2:].strip() for l in m.group(2).splitlines() if l.startswith('* ')]})
if not notes or notes[0]['version'] != new:
    sys.exit('add "= %s =" at the top of the readme.txt changelog first' % new)

if old != new:
    for rel, pat in [
        ('wordpress/zenvida-concept/style.css', r'^(Version:\s*)\S+'),
        ('wordpress/zenvida-concept/functions.php', r"(define\( 'ZV_VERSION', ')[^']+"),
        ('wordpress/blueprint.json', r'(zenvida-concept\.zip\?v=)[\d.]+'),
        ('index.html', r'(blueprint\.json\?v=)[\d.]+'),
    ]:
        p = os.path.join(ROOT, rel)
        s, n = re.subn(pat, lambda m: m.group(1) + new, read(p), flags=re.M)
        assert n, rel
        write(p, s)

style = read(os.path.join(THEME, 'style.css'))
head = lambda k: (re.search(r'^%s:\s*(.+)$' % k, style, re.M) or [None, ''])[1].strip()
info = {
    'version': new,
    'package': BASE + 'zenvida-concept.zip?v=' + new,
    'requires': head('Requires at least'),
    'requires_php': head('Requires PHP'),
    'tested': head('Tested up to'),
    'notes': notes[:6],
}
write(os.path.join(WP, 'update.json'), json.dumps(info, indent=2, ensure_ascii=False) + '\n')

zp = os.path.join(WP, 'zenvida-concept.zip')
if os.path.exists(zp):
    os.remove(zp)
with zipfile.ZipFile(zp, 'w', zipfile.ZIP_DEFLATED) as z:
    for d, dirs, files in os.walk(THEME):
        dirs[:] = sorted(x for x in dirs if not x.startswith('.'))
        for f in sorted(files):
            if f.startswith('.'):
                continue
            full = os.path.join(d, f)
            z.write(full, os.path.relpath(full, WP))
print('%s -> %s, %d files in zip' % (old, new, len(zipfile.ZipFile(zp).namelist())))
