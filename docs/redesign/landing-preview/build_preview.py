# Builds the landing-page redesign preview: index.html (open locally) and artifact.html (fragment for the preview link).
import base64, pathlib, re
HERE = pathlib.Path(__file__).parent
LOGO = HERE.parent.parent / 'brand' / 'logo' / 'build_owl.py'
ns = {'pathlib': pathlib, '__file__': str(LOGO)}
exec(LOGO.read_text().split('def svg(')[0], ns)      # DEFS, eye(), owl()

def eye(x, flip):                                   # same owl eye, but the iris group can follow the pointer
    s = -1 if flip else 1
    return (f'<circle cx="{x}" cy="180" r="40" fill="#fff"/><g class="pupil">'
            f'<circle cx="{x+s*3}" cy="183" r="27" fill="url(#iris)"/><circle cx="{x+s*3}" cy="183" r="27" fill="none" stroke="#3a1f92" stroke-width="2.5" opacity=".6"/>'
            f'<circle cx="{x+s*4}" cy="184" r="14.5" fill="#15132a"/><circle cx="{x+s*4-8*s}" cy="172" r="8.5" fill="#fff"/><circle cx="{x+s*4+9*s}" cy="193" r="4" fill="#fff" opacity=".9"/></g>')
ns['eye'] = eye
owl_svg = f'<svg viewBox="20 40 360 330" role="img" aria-label="journzey owl"><g filter="url(#sh)">{ns["owl"](False)}</g></svg>'

def font(name, file):
    b = base64.b64encode((HERE / 'fonts' / file).read_bytes()).decode()
    return f'@font-face{{font-family:"{name}";src:url(data:font/woff2;base64,{b}) format("woff2");font-weight:200 800;font-style:normal;font-display:swap}}'
fonts = '\n'.join([font('Bricolage', 'bricolage.woff2'), font('Geist', 'geist.woff2'), font('GeistMono', 'geist-mono.woff2')])

src = (HERE / 'page.src.html').read_text()
for k, v in {'{{FONTS}}': fonts, '{{DEFS}}': ns['DEFS'], '{{OWL_NAV}}': owl_svg, '{{OWL_NAV2}}': owl_svg, '{{OWL_HERO}}': owl_svg, '{{OWL_CTA}}': owl_svg}.items():
    src = src.replace(k, v)
(HERE / 'artifact.html').write_text(src)
head, body = src.split('</style>', 1)
head += '</style>'
(HERE / 'index.html').write_text(f'<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">{head}</head><body>{body}</body></html>')
print('ok', len(src) // 1024, 'KB')
