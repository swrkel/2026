"""
Structural sanity check for the Poultry module.

There is no PHP runtime in this sandbox, so this cannot replace `php -l`. It
catches the class of errors that actually arise from generating a lot of files:
unbalanced braces, a namespace that disagrees with the directory, a class name
that disagrees with the filename, a view referenced but never written, and -
most important for this build - any leak of core imports outside the two
gateway files.

Run `php -l` on the real server as well before enabling the module.
"""
import os, re, sys
from collections import defaultdict

ROOT = 'Modules/Poultry'
GATEWAYS = {'Services/StockGateway.php', 'Services/LedgerGateway.php'}

errors, warnings = [], []

def rel(p):
    return os.path.relpath(p, ROOT)

php_files, blade_files = [], []
for dirpath, _, filenames in os.walk(ROOT):
    for fn in filenames:
        full = os.path.join(dirpath, fn)
        if fn.endswith('.blade.php'):
            blade_files.append(full)
        elif fn.endswith('.php'):
            php_files.append(full)

def strip_php(src):
    """Remove strings and comments so brace counting is not fooled by them."""
    out, i, n = [], 0, len(src)
    while i < n:
        c = src[i]
        if c == '/' and i + 1 < n and src[i+1] == '/':
            while i < n and src[i] != '\n': i += 1
        elif c == '#':
            while i < n and src[i] != '\n': i += 1
        elif c == '/' and i + 1 < n and src[i+1] == '*':
            i += 2
            while i + 1 < n and not (src[i] == '*' and src[i+1] == '/'): i += 1
            i += 2
        elif c in '"\'':
            quote = c; i += 1
            while i < n:
                if src[i] == '\\': i += 2; continue
                if src[i] == quote: i += 1; break
                i += 1
        else:
            out.append(c); i += 1
    return ''.join(out)

# ---------- PHP files ----------
for path in php_files:
    r = rel(path)
    src = open(path, encoding='utf-8').read()

    if not src.lstrip().startswith('<?php'):
        errors.append('%s: does not open with <?php' % r)

    if '?>' in src:
        warnings.append('%s: contains a closing ?> tag' % r)

    clean = strip_php(src)
    for open_c, close_c, label in [('{', '}', 'braces'), ('(', ')', 'parens'), ('[', ']', 'brackets')]:
        if clean.count(open_c) != clean.count(close_c):
            errors.append('%s: unbalanced %s (%d open, %d close)'
                          % (r, label, clean.count(open_c), clean.count(close_c)))

    # namespace must match directory
    m = re.search(r'^namespace\s+([^;]+);', src, re.M)
    if m:
        ns = m.group(1).strip()
        expected_dir = os.path.dirname(r).replace('/', '\\')
        expected = 'Modules\\Poultry' + ('\\' + expected_dir if expected_dir else '')
        if ns != expected:
            errors.append('%s: namespace %s, expected %s' % (r, ns, expected))
    elif not any(x in path for x in ('/Migrations/', '/Config/', '/Routes/', '/lang/')):
        warnings.append('%s: no namespace declared' % r)

    # class name must match filename
    cm = re.search(r'^(?:abstract\s+|final\s+)?class\s+(\w+)', src, re.M)
    if cm:
        base = os.path.basename(path)[:-4]
        if '/Migrations/' in path:
            # Laravel convention: 2026_08_14_100001_create_x_table.php -> CreateXTable
            stem = re.sub(r'^\d{4}_\d{2}_\d{2}_\d{6}_', '', base)
            expected = ''.join(p.title() for p in stem.split('_'))
        else:
            expected = base
        if cm.group(1) != expected:
            errors.append('%s: class %s does not match filename (expected %s)'
                          % (r, cm.group(1), expected))

    # core coupling: only the two gateways may reference App\
    if re.search(r'(use\s+App\\|[\'"]App\\\\Utils|\\\\App\\\\)', src):
        if r not in GATEWAYS:
            errors.append('%s: references core App\\ outside the gateway seam' % r)

# ---------- Blade files ----------
view_names = set()
for path in blade_files:
    r = rel(path)
    name = r[len('Resources/views/'):-len('.blade.php')].replace('/', '.')
    view_names.add(name)

directive_pairs = [
    ('@if', '@endif'), ('@foreach', '@endforeach'), ('@forelse', '@endforelse'),
    ('@section', '@endsection'), ('@php', '@endphp'),
]
for path in blade_files:
    r = rel(path)
    src = open(path, encoding='utf-8').read()
    src = re.sub(r'\{\{--.*?--\}\}', '', src, flags=re.S)

    if src.count('{{') != src.count('}}'):
        errors.append('%s: unbalanced {{ }}' % r)

    for opener, closer in directive_pairs:
        n_open = len(re.findall(re.escape(opener) + r'\s*[\(\s]', src))
        n_close = len(re.findall(re.escape(closer) + r'\b', src))
        # @section('x', 'y') and @section('x') in a layout are self closing
        if opener == '@section':
            n_open = len(re.findall(r"@section\s*\(\s*'[^']+'\s*\)", src))
        if opener == '@if':
            n_open += len(re.findall(r'@elseif\s*\(', src)) * 0
        if n_open != n_close:
            warnings.append('%s: %s/%s count %d/%d' % (r, opener, closer, n_open, n_close))

# every poultry:: view referenced must exist
ref_re = re.compile(r"poultry::([a-z_0-9]+(?:\.[a-z_0-9]+)+)")
for path in php_files + blade_files:
    src = open(path, encoding='utf-8').read()
    for ref in ref_re.findall(src):
        if ref.startswith('lang.'):
            continue
        if ref not in view_names:
            errors.append('%s: references missing view poultry::%s' % (rel(path), ref))

# ---------- routes vs controllers ----------
routes_src = open(os.path.join(ROOT, 'Routes/web.php'), encoding='utf-8').read()
controller_methods = defaultdict(set)
for path in php_files:
    if '/Http/Controllers/' not in path:
        continue
    cls = os.path.basename(path)[:-4]
    src = open(path, encoding='utf-8').read()
    for m in re.findall(r'public function (\w+)\s*\(', src):
        controller_methods[cls].add(m)

for ctrl, method in re.findall(r"'(\w+Controller)@(\w+)'", routes_src):
    if ctrl not in controller_methods:
        errors.append('Routes: controller %s not found' % ctrl)
    elif method not in controller_methods[ctrl]:
        errors.append('Routes: %s@%s not defined' % (ctrl, method))

for res_ctrl in re.findall(r"Route::resource\([^,]+,\s*'(\w+Controller)'", routes_src):
    if res_ctrl not in controller_methods:
        errors.append('Routes: resource controller %s not found' % res_ctrl)
    else:
        for needed in ['index', 'create', 'store', 'edit', 'update', 'destroy']:
            if needed not in controller_methods[res_ctrl]:
                warnings.append('Routes: %s missing resource method %s' % (res_ctrl, needed))

# ---------- lang keys ----------
lang_path = os.path.join(ROOT, 'Resources/lang/en/lang.php')
lang_src = open(lang_path, encoding='utf-8').read()
defined_keys = set(re.findall(r"^\s*'([a-z_0-9]+)'\s*=>", lang_src, re.M))
used_keys = set()
for path in blade_files + php_files:
    src = open(path, encoding='utf-8').read()
    used_keys |= set(re.findall(r"poultry::lang\.([a-z_0-9]+)", src))

missing = sorted(used_keys - defined_keys)
for key in missing:
    errors.append("lang: key '%s' used but not defined" % key)
unused = sorted(defined_keys - used_keys)

# ---------- migrations ----------
tables = set()
for path in php_files:
    if '/Migrations/' not in path:
        continue
    src = open(path, encoding='utf-8').read()
    tables |= set(re.findall(r"Schema::create\('(\w+)'", src))
    if 'foreign(' in src:
        errors.append('%s: declares a foreign key constraint' % rel(path))

# entity $table values must exist among created tables (own tables only)
for path in php_files:
    if '/Entities/' not in path or '/Shared/' in path:
        continue
    src = open(path, encoding='utf-8').read()
    m = re.search(r"protected \$table\s*=\s*'(\w+)'", src)
    if m and m.group(1) not in tables:
        errors.append('%s: $table %s has no migration' % (rel(path), m.group(1)))

# ---------- report ----------
print('Files: %d PHP, %d Blade' % (len(php_files), len(blade_files)))
print('Tables created: %d' % len(tables))
print('Views: %d, lang keys defined: %d, used: %d' % (len(view_names), len(defined_keys), len(used_keys)))
if unused:
    print('Lang keys defined but unused: %d' % len(unused))
print()

if errors:
    print('ERRORS (%d)' % len(errors))
    for e in errors:
        print('  x ' + e)
else:
    print('No structural errors.')

if warnings:
    print()
    print('WARNINGS (%d)' % len(warnings))
    for w in warnings[:40]:
        print('  ! ' + w)

sys.exit(1 if errors else 0)
