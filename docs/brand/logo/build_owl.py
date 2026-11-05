# journzey owl ("Journo") — polished master mascot + exports (profile picture, sticker, favicon, lockups).
import pathlib
OUT = pathlib.Path(__file__).parent
DEFS = '''<defs>
<linearGradient id="body" x1=".2" y1="0" x2=".8" y2="1"><stop offset="0" stop-color="#9b73f2"/><stop offset=".55" stop-color="#6541e9"/><stop offset="1" stop-color="#43249f"/></linearGradient>
<linearGradient id="wing" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#7a52e6"/><stop offset="1" stop-color="#3a1f92"/></linearGradient>
<linearGradient id="mask" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#f6f2ff"/><stop offset="1" stop-color="#d9ccff"/></linearGradient>
<linearGradient id="belly" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#f8f5ff"/><stop offset="1" stop-color="#e2d8ff"/></linearGradient>
<linearGradient id="iris" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#8a5ddc"/><stop offset="1" stop-color="#d96fb0"/></linearGradient>
<linearGradient id="beak" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#ffd0b0"/><stop offset="1" stop-color="#ff9f9f"/></linearGradient>
<linearGradient id="tuft" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#b08cf6"/><stop offset="1" stop-color="#5232d4"/></linearGradient>
<linearGradient id="chart" x1="0" y1="1" x2="1" y2="0"><stop offset="0" stop-color="#6541e9"/><stop offset="1" stop-color="#e85fa8"/></linearGradient>
<linearGradient id="brand" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#6541e9"/><stop offset=".55" stop-color="#b17ace"/><stop offset="1" stop-color="#feb2b4"/></linearGradient>
<radialGradient id="rim" cx=".3" cy=".18" r=".7"><stop offset="0" stop-color="#fff" stop-opacity=".38"/><stop offset="1" stop-color="#fff" stop-opacity="0"/></radialGradient>
<radialGradient id="bgc" cx=".5" cy=".32" r=".85"><stop offset="0" stop-color="#31266f"/><stop offset="1" stop-color="#0d0a26"/></radialGradient>
<clipPath id="bellyclip"><ellipse cx="200" cy="270" rx="68" ry="60"/></clipPath>
<filter id="sh" x="-20%" y="-20%" width="140%" height="150%"><feDropShadow dx="0" dy="9" stdDeviation="9" flood-color="#000" flood-opacity=".38"/></filter>
<filter id="glow" x="-50%" y="-50%" width="200%" height="200%"><feGaussianBlur stdDeviation="3.2" result="b"/><feMerge><feMergeNode in="b"/><feMergeNode in="SourceGraphic"/></feMerge></filter>
<filter id="outline" x="-15%" y="-15%" width="130%" height="130%"><feMorphology in="SourceAlpha" operator="dilate" radius="9" result="d"/><feFlood flood-color="#fff"/><feComposite in2="d" operator="in" result="w"/><feDropShadow dx="0" dy="5" stdDeviation="5" flood-opacity=".3"/><feMerge><feMergeNode/><feMergeNode in="SourceGraphic"/></feMerge></filter>
</defs>'''

def eye(x, flip):
    s = -1 if flip else 1
    return f'''<circle cx="{x}" cy="180" r="40" fill="#fff"/>
<circle cx="{x+s*3}" cy="183" r="27" fill="url(#iris)"/><circle cx="{x+s*3}" cy="183" r="27" fill="none" stroke="#3a1f92" stroke-width="2.5" opacity=".6"/>
<circle cx="{x+s*4}" cy="184" r="14.5" fill="#15132a"/>
<circle cx="{x+s*4-8*s}" cy="172" r="8.5" fill="#fff"/><circle cx="{x+s*4+9*s}" cy="193" r="4" fill="#fff" opacity=".9"/>
<path d="M{x-40} 180 A40 40 0 0 1 {x+40} 180" fill="none" stroke="#3a1f92" stroke-width="0"/>'''

def owl(chart=True):
    return f'''<g>
<!-- tufts -->
<path d="M108 138 L124 62 L176 110 Z" fill="url(#tuft)"/><path d="M292 138 L276 62 L224 110 Z" fill="url(#tuft)"/>
<path d="M121 112 L129 80 L156 104 Z" fill="url(#beak)" opacity=".95"/><path d="M279 112 L271 80 L244 104 Z" fill="url(#beak)" opacity=".95"/>
<!-- body -->
<path d="M200 92 C128 92 90 150 90 218 C90 296 138 338 200 338 C262 338 310 296 310 218 C310 150 272 92 200 92 Z" fill="url(#body)"/>
<path d="M200 92 C128 92 90 150 90 218 C90 296 138 338 200 338 C262 338 310 296 310 218 C310 150 272 92 200 92 Z" fill="url(#rim)"/>
<!-- wings -->
<path d="M100 206 C62 226 54 284 82 322 Q92 332 104 328 Q112 316 114 300 Q118 262 114 224 Z" fill="url(#wing)"/>
<path d="M300 206 C338 226 346 284 318 322 Q308 332 296 328 Q288 316 286 300 Q282 262 286 224 Z" fill="url(#wing)"/>
<path d="M84 262 Q100 270 106 292 M80 238 Q100 244 110 262" fill="none" stroke="#a581f5" stroke-width="4" stroke-linecap="round" opacity=".7"/>
<path d="M316 262 Q300 270 294 292 M320 238 Q300 244 290 262" fill="none" stroke="#a581f5" stroke-width="4" stroke-linecap="round" opacity=".7"/>
<!-- belly + feather scallops -->
<ellipse cx="200" cy="270" rx="68" ry="60" fill="url(#belly)"/>
<g clip-path="url(#bellyclip)" fill="none" stroke="#c9b9f5" stroke-width="3" stroke-linecap="round" opacity=".5">
{''.join(f'<path d="M{x} {y} q15 15 30 0"/>' for y, xs in ((300, (136,166,196,226)), (278, (151,181,211)), (256, (136,166,196,226))) for x in xs)}
</g>
{'<g transform="translate(-9 8) scale(.94)" transform-origin="200 270"><g stroke-linecap="round" stroke-linejoin="round" fill="none"><polyline points="158,298 184,274 202,288 233,255" stroke="#fff" stroke-width="19" opacity=".92"/><polyline points="158,298 184,274 202,288 233,255" stroke="url(#chart)" stroke-width="10"/></g><path d="M254 234 L222 244 L244 266 Z" fill="#fff" stroke="#fff" stroke-width="15" stroke-linejoin="round" opacity=".92"/><path d="M254 234 L222 244 L244 266 Z" fill="#e85fa8" stroke="#e85fa8" stroke-width="5" stroke-linejoin="round"/></g>' if chart else ''}
<!-- face mask -->
<path d="M200 140 C176 126 148 128 130 146 C108 170 116 214 160 224 C180 227 194 218 200 208 C206 218 220 227 240 224 C284 214 292 170 270 146 C252 128 224 126 200 140 Z" fill="url(#mask)"/>
{eye(160, False)}{eye(240, True)}
<!-- brows -->
<path d="M116 148 Q148 122 190 144 Q150 136 116 148 Z" fill="#2a1766"/><path d="M284 148 Q252 122 210 144 Q250 136 284 148 Z" fill="#2a1766"/>
<!-- beak -->
<path d="M184 208 Q200 200 216 208 Q216 226 200 240 Q184 226 184 208 Z" fill="url(#beak)"/><path d="M190 210 Q200 206 210 210" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" opacity=".6"/>
<!-- feet -->
<g fill="url(#beak)"><path d="M162 332 q-4 18 6 20 q8 0 10 -20 Z"/><path d="M180 332 q0 16 8 18 q8 -2 8 -18 Z"/><path d="M238 332 q4 18 -6 20 q-8 0 -10 -20 Z"/><path d="M220 332 q0 16 -8 18 q-8 -2 -8 -18 Z"/></g>
</g>'''

def svg(inner, vb='0 0 400 400', size=400): return f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="{vb}" width="{size}" height="{size}">{DEFS}{inner}</svg>'
MASCOT = owl()
PROFILE = f'<circle cx="200" cy="200" r="200" fill="url(#bgc)"/><circle cx="200" cy="200" r="190" fill="none" stroke="url(#brand)" stroke-width="6"/><g filter="url(#sh)" transform="translate(200 204) scale(1.04) translate(-200 -214)">{MASCOT}</g>'
(OUT / 'owl-mascot.svg').write_text(svg(f'<g filter="url(#sh)">{MASCOT}</g>', '20 40 360 330'))
(OUT / 'owl-profile.svg').write_text(svg(PROFILE))
(OUT / 'owl-sticker.svg').write_text(svg(f'<g filter="url(#outline)" transform="translate(200 200) scale(.92) translate(-200 -200)">{MASCOT}</g>'))
(OUT / 'owl-favicon.svg').write_text(svg(f'<rect width="400" height="400" rx="92" fill="url(#bgc)"/><g transform="translate(200 214) scale(1.02) translate(-200 -214)">{owl(False)}</g>'))

def lock(dark):
    ink = '#ffffff' if dark else '#15132a'
    bg = '#0b0920' if dark else '#ffffff'
    return f'''<div class="lock" style="background:{bg}"><span class="m">{svg(f'<g filter="url(#sh)">{MASCOT}</g>', '20 40 360 330')}</span><span class="t" style="color:{ink}">journzey<em>.ai</em><small style="color:{'#9d9abb' if dark else '#636080'}">Trade your plan. Prove it with data.</small></span></div>'''
PAGE = lambda body, w, h, bg='transparent': f'''<!doctype html><html><head><meta charset="utf-8"><style>
@font-face{{font-family:J;src:url(file:///home/user/JourneyAI/public/assets/fonts/plus-jakarta-sans.woff2);font-weight:200 800}}
@font-face{{font-family:I;src:url(file:///home/user/JourneyAI/public/assets/fonts/instrument-serif-italic.woff2);font-style:italic}}
*{{box-sizing:border-box;margin:0}}body{{width:{w}px;height:{h}px;background:{bg};font-family:J,sans-serif;display:flex;align-items:center;justify-content:center}}
.lock{{display:flex;align-items:center;gap:34px;padding:56px 84px;border-radius:0;width:100%;height:100%}}.m svg{{width:300px;height:280px;display:block}}
.t{{font-weight:760;font-size:128px;letter-spacing:-.045em;line-height:1}}.t em{{font-style:normal;background:linear-gradient(100deg,#6541e9,#d96fb0);-webkit-background-clip:text;color:transparent}}
.t small{{display:block;margin-top:14px;font:italic 400 38px I,serif;letter-spacing:0}}
svg{{display:block}}</style></head><body>{body}</body></html>'''
(OUT / '_p_profile.html').write_text(PAGE(svg(PROFILE, size=1080).replace('width="1080" height="1080"', 'width="1080" height="1080"'), 1080, 1080))
(OUT / '_p_sticker.html').write_text(PAGE(svg(f'<g filter="url(#outline)" transform="translate(200 200) scale(.92) translate(-200 -200)">{MASCOT}</g>', size=1080), 1080, 1080))
(OUT / '_p_favicon.html').write_text(PAGE((OUT / 'owl-favicon.svg').read_text().replace('width="400" height="400"', 'width="512" height="512"'), 512, 512))
(OUT / '_p_lock_dark.html').write_text(PAGE(lock(True), 1500, 520))
(OUT / '_p_lock_light.html').write_text(PAGE(lock(False), 1500, 520))
sheet = f'''<div style="display:grid;grid-template-columns:1fr 1fr;gap:28px;padding:44px;width:1500px;background:#0a0820">
<div style="grid-column:1/3;color:#fff;font-size:42px;font-weight:760;letter-spacing:-.03em">Journo the Owl <span style="font:italic 400 48px I,serif;color:#feb2b4">v2 · clean arrow</span></div>
<div style="background:#100d2b;border-radius:26px;padding:26px;display:flex;gap:26px;align-items:center">{svg(PROFILE, size=330)}<div style="color:#b9b6d2;font-size:17px;line-height:1.6"><b style="color:#fff;font-size:21px">Profile picture</b><br>Round crop for Instagram, YouTube, X, Telegram. Shown small, it still reads clearly.<div style="display:flex;gap:14px;align-items:center;margin-top:18px">{svg(PROFILE, size=96)}{svg(PROFILE, size=56)}{svg(PROFILE, size=36)}</div></div></div>
<div style="background:#100d2b;border-radius:26px;padding:26px;display:flex;gap:26px;align-items:center"><div style="background:repeating-conic-gradient(#1b1840 0% 25%,#13102e 0% 50%) 0 0/28px 28px;border-radius:20px;padding:12px">{svg(f'<g filter="url(#outline)" transform="translate(200 200) scale(.92) translate(-200 -200)">{MASCOT}</g>', size=300)}</div><div style="color:#b9b6d2;font-size:17px;line-height:1.6"><b style="color:#fff;font-size:21px">Sticker / reel overlay</b><br>White outline and soft shadow, transparent background: drop it on any video or story.<div style="margin-top:18px;display:flex;gap:16px;align-items:center">{svg((OUT / 'owl-favicon.svg').read_text().split('>',1)[1].rsplit('</svg>',1)[0], size=64)}<span style="font-size:15px">Website favicon / app icon</span></div></div></div>
<div style="grid-column:1/3;border-radius:26px;overflow:hidden;display:grid;grid-template-columns:1fr 1fr;height:230px"><div style="background:#0b0920;display:flex;align-items:center;gap:20px;padding:0 40px"><span style="width:150px;display:block">{svg(f'<g filter="url(#sh)">{MASCOT}</g>', '20 40 360 330', 150)}</span><span style="color:#fff;font-weight:760;font-size:64px;letter-spacing:-.045em">journzey<span style="color:#b17ace">.ai</span></span></div><div style="background:#fff;display:flex;align-items:center;gap:20px;padding:0 40px"><span style="width:150px;display:block">{svg(f'<g filter="url(#sh)">{MASCOT}</g>', '20 40 360 330', 150)}</span><span style="color:#15132a;font-weight:760;font-size:64px;letter-spacing:-.045em">journzey<span style="color:#8a5ddc">.ai</span></span></div></div></div>'''
(OUT / '_p_sheet.html').write_text(PAGE(sheet, 1588, 1000, '#0a0820').replace('height:1000px', 'height:auto;min-height:1000px'))
print('ok')
