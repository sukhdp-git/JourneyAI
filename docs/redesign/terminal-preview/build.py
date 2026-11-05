# Builds the terminal redesign preview: index.html (open locally) and artifact.html (fragment for the preview link).
import base64, json, pathlib
HERE = pathlib.Path(__file__).parent
ns = {'pathlib': pathlib, '__file__': str(HERE.parents[1] / 'brand/logo/build_owl.py')}
exec((HERE.parents[1] / 'brand/logo/build_owl.py').read_text().split('def svg(')[0], ns)
def eye(x, flip):
    s = -1 if flip else 1
    return (f'<circle cx="{x}" cy="180" r="40" fill="#fff"/><g class="pupil">'
            f'<circle cx="{x+s*3}" cy="183" r="27" fill="url(#iris)"/><circle cx="{x+s*3}" cy="183" r="27" fill="none" stroke="#3a1f92" stroke-width="2.5" opacity=".6"/>'
            f'<circle cx="{x+s*4}" cy="184" r="14.5" fill="#15132a"/><circle cx="{x+s*4-8*s}" cy="172" r="8.5" fill="#fff"/><circle cx="{x+s*4+9*s}" cy="193" r="4" fill="#fff" opacity=".9"/></g>')
ns['eye'] = eye
owl = f'<svg viewBox="20 40 360 330" role="img" aria-label="journzey owl"><g filter="url(#sh)">{ns["owl"](False)}</g></svg>'
def font(n, f):
    b = base64.b64encode((HERE / 'fonts' / f).read_bytes()).decode()
    return f'@font-face{{font-family:"{n}";src:url(data:font/woff2;base64,{b}) format("woff2");font-weight:200 800;font-style:normal;font-display:swap}}'
fonts = '\n'.join([font('Bricolage', 'bricolage.woff2'), font('Geist', 'geist.woff2'), font('GeistMono', 'geist-mono.woff2')])
icons = json.loads((HERE / 'icons.json').read_text())
css = (HERE / 'style.css').read_text().replace('{{FONTS}}', fonts)
body = (HERE / 'body.html').read_text().replace('{{DEFS}}', ns['DEFS']).replace('{{OWLP}}', owl)
dom = (HERE / 'domain.json').read_text()
js = (HERE / 'app.js').read_text().replace('{{HOME}}', (HERE / 'home.js').read_text().replace('{{DOMAIN}}', dom)).replace('{{ICONS}}', json.dumps(icons))
frag = f'<title>journzey Terminal Redesign</title>\n<meta name="claude:viewport-fit-sides" content="cover">\n<style>\n{css}\n</style>\n{body}\n<script>\n{js}\n</script>\n'
(HERE / 'artifact.html').write_text(frag)
(HERE / 'index.html').write_text('<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">' + frag.replace('</style>', '</style></head><body>', 1).replace('<title>', '<title>', 1) + '</body></html>')
print('ok', len(frag) // 1024, 'KB')
