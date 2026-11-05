# journzey mascot logo concepts: four vector mascots (SVG) + a presentation sheet (HTML → PNG).
import pathlib
OUT = pathlib.Path(__file__).parent
DEFS = '''<defs>
<linearGradient id="vio" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#8a5ddc"/><stop offset="1" stop-color="#5232d4"/></linearGradient>
<linearGradient id="brand" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#6541e9"/><stop offset=".55" stop-color="#b17ace"/><stop offset="1" stop-color="#feb2b4"/></linearGradient>
<linearGradient id="pink" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#feb2b4"/><stop offset="1" stop-color="#d996c1"/></linearGradient>
<linearGradient id="grn" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#34d399"/><stop offset="1" stop-color="#059669"/></linearGradient>
<radialGradient id="bgc" cx=".5" cy=".35" r=".8"><stop offset="0" stop-color="#2a2160"/><stop offset="1" stop-color="#100c2b"/></radialGradient>
<filter id="sh" x="-20%" y="-20%" width="140%" height="150%"><feDropShadow dx="0" dy="8" stdDeviation="8" flood-color="#000" flood-opacity=".35"/></filter>
</defs>'''
BG = '<circle cx="200" cy="200" r="196" fill="url(#bgc)"/><circle cx="200" cy="200" r="196" fill="none" stroke="url(#brand)" stroke-width="5"/>'
EYE = lambda x, y, r=30, px=3, py=3: f'<circle cx="{x}" cy="{y}" r="{r}" fill="#fff"/><circle cx="{x+px}" cy="{y+py}" r="{r*0.52:.1f}" fill="#15132a"/><circle cx="{x+px+r*0.2:.1f}" cy="{y+py-r*0.2:.1f}" r="{r*0.17:.1f}" fill="#fff"/>'

OWL = f'''<g filter="url(#sh)">
<path d="M112 132 L128 66 L172 108 Z" fill="url(#vio)"/><path d="M288 132 L272 66 L228 108 Z" fill="url(#vio)"/>
<path d="M120 112 L130 82 L152 104 Z" fill="url(#pink)"/><path d="M280 112 L270 82 L248 104 Z" fill="url(#pink)"/>
<path d="M200 96 C132 96 96 150 96 214 C96 292 142 332 200 332 C258 332 304 292 304 214 C304 150 268 96 200 96 Z" fill="url(#vio)"/>
<path d="M96 214 C70 232 66 282 96 304 C112 276 112 246 108 220 Z" fill="#5232d4"/><path d="M304 214 C330 232 334 282 304 304 C288 276 288 246 292 220 Z" fill="#5232d4"/>
<ellipse cx="200" cy="268" rx="66" ry="58" fill="#efe9ff"/>
<polyline points="162,292 180,274 194,284 214,252 238,258" fill="none" stroke="#6541e9" stroke-width="7" stroke-linecap="round" stroke-linejoin="round"/>
<path d="M226 244 L242 256 L224 264 Z" fill="#6541e9"/>
<circle cx="160" cy="178" r="46" fill="#efe9ff"/><circle cx="240" cy="178" r="46" fill="#efe9ff"/>
{EYE(160,178,36,4,3)}{EYE(240,178,36,-4,3)}
<path d="M120 138 Q150 122 186 140" fill="none" stroke="#15132a" stroke-width="7" stroke-linecap="round"/><path d="M280 138 Q250 122 214 140" fill="none" stroke="#15132a" stroke-width="7" stroke-linecap="round"/>
<path d="M186 206 L214 206 L200 232 Z" fill="url(#pink)"/>
<path d="M170 332 q-2 14 8 16 q10 0 10 -16 Z M230 332 q2 14 -8 16 q-10 0 -10 -16 Z" fill="#feb2b4"/>
</g>'''

FOX = f'''<g filter="url(#sh)">
<path d="M70 170 L96 54 L176 112 L224 112 L304 54 L330 170 C336 232 292 300 200 340 C108 300 64 232 70 170 Z" fill="url(#vio)"/>
<path d="M102 126 L106 82 L146 112 Z" fill="url(#pink)"/><path d="M298 126 L294 82 L254 112 Z" fill="url(#pink)"/>
<path d="M96 196 C130 214 168 236 200 300 C232 236 270 214 304 196 C300 252 262 304 200 340 C138 304 100 252 96 196 Z" fill="#fff"/>
<path d="M176 112 L224 112 L214 150 L200 164 L186 150 Z" fill="#8a5ddc" opacity=".55"/>
<path d="M118 178 Q146 150 182 186 Q150 210 118 178 Z" fill="#fff"/><path d="M282 178 Q254 150 218 186 Q250 210 282 178 Z" fill="#fff"/>
<ellipse cx="156" cy="182" rx="15" ry="17" fill="#15132a"/><ellipse cx="244" cy="182" rx="15" ry="17" fill="#15132a"/>
<circle cx="161" cy="175" r="5.5" fill="#fff"/><circle cx="249" cy="175" r="5.5" fill="#fff"/>
<path d="M112 160 Q150 138 190 170" fill="none" stroke="#15132a" stroke-width="7" stroke-linecap="round"/><path d="M288 160 Q250 138 210 170" fill="none" stroke="#15132a" stroke-width="7" stroke-linecap="round"/>
<path d="M184 296 Q200 286 216 296 Q208 312 200 316 Q192 312 184 296 Z" fill="#15132a"/>
<path d="M168 322 Q200 346 232 322" fill="none" stroke="#15132a" stroke-width="6" stroke-linecap="round"/>
</g>'''

CANDLE = f'''<g filter="url(#sh)">
<line x1="200" y1="46" x2="200" y2="102" stroke="#34d399" stroke-width="12" stroke-linecap="round"/>
<line x1="200" y1="300" x2="200" y2="352" stroke="#34d399" stroke-width="12" stroke-linecap="round"/>
<rect x="116" y="96" width="168" height="212" rx="52" fill="url(#grn)"/>
<rect x="132" y="108" width="136" height="60" rx="30" fill="#fff" opacity=".12"/>
<path d="M104 196 q-34 4 -38 38" fill="none" stroke="#34d399" stroke-width="16" stroke-linecap="round"/><path d="M296 196 q34 4 38 38" fill="none" stroke="#34d399" stroke-width="16" stroke-linecap="round"/>
<path d="M118 118 Q200 78 282 118" fill="none" stroke="#fff" stroke-width="0"/>
<path d="M126 118 Q160 82 206 96 Q172 112 126 118 Z" fill="#6541e9"/><path d="M174 90 Q230 70 276 112 Q232 100 174 90 Z" fill="#8a5ddc"/>
{EYE(166,180,28,3,3)}{EYE(234,180,28,-3,3)}
<path d="M168 236 Q200 270 232 236 Q200 246 168 236 Z" fill="#15132a"/><path d="M186 250 Q200 262 214 250 Q200 256 186 250 Z" fill="#feb2b4"/>
<ellipse cx="140" cy="230" rx="14" ry="9" fill="#feb2b4" opacity=".75"/><ellipse cx="260" cy="230" rx="14" ry="9" fill="#feb2b4" opacity=".75"/>
</g>'''

BULL = f'''<g filter="url(#sh)">
<path d="M126 150 C86 138 56 108 56 56" fill="none" stroke="url(#pink)" stroke-width="26" stroke-linecap="round"/><path d="M274 150 C314 138 344 108 344 56" fill="none" stroke="url(#pink)" stroke-width="26" stroke-linecap="round"/>
<ellipse cx="96" cy="176" rx="34" ry="18" transform="rotate(-24 96 176)" fill="#5232d4"/><ellipse cx="304" cy="176" rx="34" ry="18" transform="rotate(24 304 176)" fill="#5232d4"/>
<path d="M200 98 C136 98 112 148 116 214 C120 262 150 292 200 296 C250 292 280 262 284 214 C288 148 264 98 200 98 Z" fill="url(#vio)"/>
<path d="M150 252 C150 224 250 224 250 252 C250 316 150 316 150 252 Z" fill="url(#pink)"/>
<ellipse cx="178" cy="274" rx="9" ry="12" fill="#15132a"/><ellipse cx="222" cy="274" rx="9" ry="12" fill="#15132a"/>
<path d="M176 300 Q176 330 200 332 Q224 330 224 300" fill="none" stroke="#f5c542" stroke-width="7" stroke-linecap="round"/>
<path d="M138 180 Q160 170 186 184 L182 208 Q158 214 138 204 Z" fill="#fff"/><path d="M262 180 Q240 170 214 184 L218 208 Q242 214 262 204 Z" fill="#fff"/>
<circle cx="164" cy="196" r="10" fill="#15132a"/><circle cx="236" cy="196" r="10" fill="#15132a"/><circle cx="168" cy="192" r="3.5" fill="#fff"/><circle cx="240" cy="192" r="3.5" fill="#fff"/>
<path d="M134 168 Q160 158 192 172" fill="none" stroke="#15132a" stroke-width="8" stroke-linecap="round"/><path d="M266 168 Q240 158 208 172" fill="none" stroke="#15132a" stroke-width="8" stroke-linecap="round"/><path d="M180 296 Q200 306 220 296" fill="none" stroke="none"/>
<path d="M200 112 L214 142 L200 160 L186 142 Z" fill="#b17ace" opacity=".6"/>
</g>'''

M = [('owl', 'Journo the Owl', 'The wise night trader', 'Owl = patience, wisdom and trading at night. The rising line on the belly says "your data". Friendly and trustworthy; the safest, most "brand-like" choice.', OWL),
     ('fox', 'Zey the Fox', 'The sharp, clever trader', 'Fox = clever and quick. Clean geometric face, confident look. Reads as a modern tech brand and works very small as a profile picture.', FOX),
     ('candle', 'Candy the Candle', 'The friendly candlestick', 'A candlestick with a face: instantly says "trading" even to a stranger, and the most Gen-Z, shareable, sticker-ready of the four.', CANDLE),
     ('bull', 'Bolt the Bull', 'The strong, steady bull', 'Bull = markets and strength, calm and steady, with discipline in the eyes. Familiar to every trader, so it feels established at first sight.', BULL)]

def svg(body, bg=True): return f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 400" width="400" height="400">{DEFS}{BG if bg else ""}{body}</svg>'
for k, n, t, d, body in M:
    (OUT / f'mascot-{k}.svg').write_text(svg(body))
    (OUT / f'mascot-{k}-transparent.svg').write_text(svg(body, False))

cards = ''
for k, n, t, d, body in M:
    cards += f'''<article><div class="pic">{svg(body)}</div><div class="side"><h2>{n}</h2><b>{t}</b><p>{d}</p>
<div class="prev"><span class="ig">{svg(body)}</span><span class="lock"><i class="mk">{svg(body, False)}</i><u>journzey<em>.ai</em></u></span></div></div></article>'''
html = f'''<!doctype html><html><head><meta charset="utf-8"><style>
@font-face{{font-family:J;src:url(file:///home/user/JourneyAI/public/assets/fonts/plus-jakarta-sans.woff2);font-weight:200 800}}
@font-face{{font-family:I;src:url(file:///home/user/JourneyAI/public/assets/fonts/instrument-serif-italic.woff2);font-style:italic}}
*{{box-sizing:border-box}}body{{margin:0;padding:48px;background:#0a0820;color:#dedcec;font-family:J,sans-serif;width:1700px}}
h1{{font-size:44px;margin:0 0 6px;color:#fff;letter-spacing:-.03em}}h1 em{{font:italic 400 1.1em I,serif;background:linear-gradient(100deg,#8a5ddc,#feb2b4);-webkit-background-clip:text;color:transparent}}
.sub{{color:#9d9abb;margin:0 0 34px;font-size:18px}}
.grid{{display:grid;grid-template-columns:1fr 1fr;gap:26px}}
article{{display:flex;gap:26px;padding:26px;border-radius:26px;background:#100d2b;border:1px solid #262448}}
.pic svg{{width:300px;height:300px;display:block}}.side{{flex:1;min-width:0}}
h2{{margin:4px 0 2px;font-size:26px;color:#fff}}b{{color:#feb2b4;font-size:14px;letter-spacing:.08em;text-transform:uppercase}}
p{{margin:12px 0;font-size:15px;line-height:1.5;color:#b9b6d2}}
.prev{{display:flex;align-items:center;gap:22px;margin-top:16px}}.ig svg{{width:84px;height:84px;display:block;border-radius:50%}}
.lock{{display:flex;align-items:center;gap:10px;background:#fff;border-radius:16px;padding:10px 18px}}.mk svg{{width:46px;height:46px;display:block}}
.lock u{{text-decoration:none;font-weight:750;font-size:26px;color:#15132a;letter-spacing:-.03em}}.lock em{{font-style:normal;color:#8a5ddc}}
</style></head><body><h1>journzey <em>mascot</em> concepts</h1><p class="sub">Four options in your violet → pink brand colours. Each is shown big, as an Instagram profile picture (round) and next to the wordmark.</p>
<div class="grid">{cards}</div></body></html>'''
(OUT / 'mascot-sheet.html').write_text(html)
print('ok')
