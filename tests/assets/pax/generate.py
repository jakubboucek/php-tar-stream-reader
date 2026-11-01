"""Regenerate fixtures with GNU tar; pass its command as arguments (default: tar)."""
import hashlib
import io
import json
from pathlib import Path
import subprocess
import sys
import tarfile
import tempfile

command = sys.argv[1:] or ['tar']
version = subprocess.check_output(command + ['--version'], text=True).splitlines()[0]
assert 'GNU tar' in version, version
output = Path(__file__).resolve().parent
long_name = 'long-' + 'a' * 110 + '-ž\nname.txt'
contents = {'first.txt': b'abc', long_name: b'long filename\n', 'last.txt': b'xyz'}
manifest = {}
with tempfile.TemporaryDirectory(prefix='tar-pax-fixtures-', dir='/tmp') as tmp:
    source = Path(tmp)
    for name, data in contents.items():
        (source / name).write_bytes(data)
    cases = {
        'local-path': (list(contents), []),
        'local-size': (['first.txt', 'last.txt'], ['size:=3']),
        'global-comment': (['first.txt', 'last.txt'], ['comment=fixture']),
        'global-size': (['first.txt', 'last.txt'], ['size=3']),
        'global-path': (['first.txt'], ['path=renamed.txt']),
    }
    for boundary in (511, 512, 513, 1024):
        cases[f'padding-{boundary}'] = (['first.txt', 'last.txt'], ['comment:=' + 'a' * (boundary - 24 - len(str(boundary - 14)))])
    for name, (names, options) in cases.items():
        target = source / (name + '.tar')
        pax = ['exthdr.name=%d/PaxHeaders/%f', 'globexthdr.name=GlobalHead', 'delete=atime', 'delete=ctime'] + options
        subprocess.run(command + ['--format=posix', '--blocking-factor=1', '--mtime=@0.25', '--owner=0', '--group=0',
            '--pax-option=' + ','.join(pax), '-cf', str(target), '-C', str(source)] + names, check=True)
        data = target.read_bytes()
        records = []
        offset = 0
        while any(data[offset:offset + 512]):
            header = data[offset:offset + 512]
            size = int(header[124:136].rstrip(b'\0 '), 8)
            kind = chr(header[156])
            if kind in ('x', 'g'):
                records.append((kind, size))
            offset += 512 + (size + 511) // 512 * 512
        assert records, name
        assert records[0][0] == ('g' if name.startswith('global-') else 'x'), records
        if name.startswith('padding-'):
            assert records == [('x', int(name[8:]))] * 2, records
        expected = []
        with tarfile.open(fileobj=io.BytesIO(data)) as archive:
            members = archive.getmembers()
            assert len(members) == len(names)
            for member, original in zip(members, names):
                expected_name = 'renamed.txt' if name == 'global-path' else original
                assert member.name == expected_name
                assert member.size == len(contents[original])
                if name in ('local-size', 'global-size'):
                    assert member.pax_headers['size'] == '3'
                if name == 'global-path' or (name == 'local-path' and original == long_name):
                    assert member.pax_headers['path'] == expected_name
                if name == 'global-comment':
                    assert member.pax_headers['comment'] == 'fixture'
                assert archive.extractfile(member).read() == contents[original]
                expected.append({'name': expected_name, 'size': member.size,
                    'sha256': hashlib.sha256(contents[original]).hexdigest()})
        (output / target.name).write_bytes(data)
        manifest[name] = expected
(output / 'manifest.json').write_text(json.dumps(manifest, ensure_ascii=False, indent=2) + '\n')
print('Generated and independently verified fixtures with ' + version)
