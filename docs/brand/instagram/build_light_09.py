# Edge Notes 09 (light theme, 6 slides): "The 5 stages of a losing trade" — one chart, revealed stage by stage.
# Visual-first: a giant face, one word, one short quote and the same chart with the P&L at that stage. Stage 5 is the twist.
import pathlib
OUT = pathlib.Path(__file__).parent
base = (OUT / 'edge-notes-01-v2-light.html').read_text()
css = base[base.index('<style>') + 7:base.index('</style>')]
N = 6
TAG = 'EDGE NOTES · 09'
extra = '''
.page { display: none; }
h1.hook { font-size: 108px; line-height: .96; letter-spacing: -.045em; margin: 48px 0 0; color: var(--strong); }
.st { display: flex; align-items: center; gap: 30px; margin-top: 40px; }
.face { flex: none; font-size: 200px; line-height: 1; }
.st small { display: block; font: 600 24px Mono, monospace; letter-spacing: .16em; color: #5232d4; }
.st h2 { font-size: 112px; line-height: .95; letter-spacing: -.05em; margin: 6px 0 0; color: var(--strong); }
.quote { font-family: InstrSerif, serif; font-style: italic; font-size: 70px; line-height: 1.05; color: var(--strong); margin: 26px 0 0; }
.quote::before { content: "“"; color: #8a5ddc; } .quote::after { content: "”"; color: #8a5ddc; }
.viz { margin-top: auto; padding: 18px 18px 6px; }
.foot { margin-top: 0; padding-top: 22px; }
.fix { display: flex; align-items: center; gap: 18px; margin-top: 20px; padding: 20px 26px; border-radius: 20px; background: #fff; border: 1.5px solid var(--line); font-size: 34px; font-weight: 700; color: var(--strong); letter-spacing: -.02em; }
.fix span { flex: none; font: 700 19px Mono, monospace; letter-spacing: .14em; color: #fff; background: var(--up); border-radius: 10px; padding: 8px 12px; }
'''
def dots(i): return '<div class="dots">' + ''.join(f'<i{" class=on" if j == i else ""}></i>' for j in range(1, N + 1)) + '</div>'
def top(): return f'<div class="top"><div class="word">journzey</div><div class="tag">{TAG}</div></div>'
def foot(i, left): return f'<div class="foot"><div class="swipe">{left}</div>{dots(i)}</div>'
CTA = '<div class="cta">journzey is coming soon <span>Follow for launch day 🔔</span></div>'
ARR = ' <span class="grad" style="font-size:40px">→</span>'

def stage(n, face, word, quote, i, left, h=690):
    return f'''{top()}<div class="st"><div class="face">{face}</div><div><small>STAGE {n} OF 5</small><h2>{word}</h2></div></div>
 <p class="quote">{quote}</p>
 <div class="card viz"><svg id="c{i}" width="900" height="{h}"></svg></div>{foot(i + 1, left)}'''

pages = [
 f'''{top()}<h1 class="hook">The 5 stages<br><span class="serif grad" style="font-weight:400">of a losing trade.</span></h1>
 <div class="card viz"><svg id="c0" width="900" height="760"></svg></div>{foot(1, 'Which one are you in?' + ARR)}''',
 stage(1, '🙂', 'Denial.', 'Just a pullback.', 1, 'Next' + ARR),
 stage(2, '🙏', 'Bargaining.', 'Back to entry. Please.', 2, 'Next' + ARR),
 stage(3, '😡', 'Anger.', 'The market is rigged.', 3, 'Next' + ARR),
 stage(4, '😐', 'Acceptance.', 'Fine. Close it.', 4, 'Wait. There’s a 5th' + ARR),
 f'''{top()}<div class="st" style="margin-top:30px"><div class="face">🤡</div><div><small>STAGE 5 OF 5</small><h2>Do it<br>again.</h2></div></div>
 <div class="card viz" style="margin-top:22px"><svg id="c5" width="900" height="500"></svg></div>
 <div class="fix"><span>THE FIX</span>2 losses → close the app.</div>
 {CTA}<div class="foot" style="padding-top:16px"><div class="swipe" style="font-size:28px">Send this to someone in stage 2 😭</div>{dots(6)}</div>''',
]

JS = r'''
function rng(seed){ return () => (seed = (seed * 16807) % 2147483647) / 2147483647; }
// one trade: flat → long entry → dip → weak bounce → crash → grind → closed → re-entry 2× → lower
const DR = [.4,.6,-.3,.5,.8, -1.2,-1.6,-.8,-1.4, .9,1.2,.6,.8, -2.6,-3.2,-2.2,-2.8,-1.2, -.8,-1.4,-.6,-1.6,-1, .6, -1.4,-2,-1.2,-2.4,-1.8];
const r=rng(29); let p=4200; const C=DR.map(d=>{ const o=p, c=o+d*2.2+(r()-0.5)*1.4; const h=Math.max(o,c)+r()*1.6, l=Math.min(o,c)-r()*1.6; p=c; return {o,c,h,l}; });
const ENTRY=4, E=C[ENTRY].c, CLOSE=22, RE=23;
const S = [null, 8, 12, 17, 22];                 // candle index where each stage happens
const K = 480 / (E - C[CLOSE].c);                  // $ per point, so the closed loss is −$480
const pnl = i => Math.round((C[i].c - E) * K / 10) * 10;
const money = v => (v < 0 ? '−$' : '+$') + Math.abs(v).toLocaleString('en-US');
function frame(svg, n){ const W=+svg.getAttribute('width'), H=+svg.getAttribute('height'), top=60, bot=H-40, left=8, right=16;
  const hi=Math.max(...C.map(k=>k.h))+2, lo=Math.min(...C.map(k=>k.l))-2;
  const y=v=>top+(hi-v)/(hi-lo)*(bot-top), step=(W-left-right)/n, x=i=>left+i*step+step/2; return {W,H,top,bot,y,x,bw:step*.62}; }
function pill(x, y, t, col, anchor, fs){ fs=fs||22; const w=t.length*fs*.58+32; const xx=anchor==='end'?x-w:anchor==='mid'?x-w/2:x; return `<rect x="${xx}" y="${y-fs}" width="${w}" height="${fs*2}" rx="12" fill="${col}"/><text x="${xx+w/2}" y="${y+fs*.36}" font-size="${fs}" fill="#fff" text-anchor="middle" style="font-family:Jakarta;font-weight:800">${t}</text>`; }
function chart(id, upto, opts){ opts=opts||{}; const svg=document.getElementById(id); const n=opts.n||C.length; const F=frame(svg,n); let s='';
  for(let t=0;t<=4;t++){ const yy=F.top+(F.bot-F.top)*t/4; s+=`<line x1="8" x2="${F.W-8}" y1="${yy}" y2="${yy}" stroke="#eeebf8" stroke-width="1.5"/>`; }
  s+=`<line x1="${F.x(ENTRY)}" x2="${F.W-8}" y1="${F.y(E)}" y2="${F.y(E)}" stroke="#6541e9" stroke-width="3" stroke-dasharray="10 8"/>`;
  s+=pill(F.W-10, F.y(E)-30, 'BUY', '#6541e9', 'end', 20);
  C.slice(0,n).forEach((k,i)=>{ const fut=i>upto, col=fut?'#e4e1ef':(k.c>=k.o?'#059669':'#dc2626'), yo=F.y(k.o), yc=F.y(k.c);
    s+=`<line x1="${F.x(i)}" x2="${F.x(i)}" y1="${F.y(k.h)}" y2="${F.y(k.l)}" stroke="${col}" stroke-width="2.4"/><rect x="${F.x(i)-F.bw/2}" y="${Math.min(yo,yc)}" width="${F.bw}" height="${Math.max(3,Math.abs(yc-yo))}" rx="2.5" fill="${col}"/>`; });
  if (opts.extra) s+=opts.extra(F);
  s+=`<text x="16" y="34" font-size="20" fill="#8f8ca8" style="font-family:Mono">XAUUSD 15m</text><text x="${F.W-12}" y="${F.H-10}" font-size="18" fill="#8f8ca8" text-anchor="end">illustration</text>`;
  svg.innerHTML=s; return F; }
function marker(F, i, label, col, side){ return `<circle cx="${F.x(i)}" cy="${F.y(C[i].c)}" r="13" fill="${col}" stroke="#fff" stroke-width="4"/>`+pill(F.x(i)+(side==='end'?-18:18), F.y(C[i].c)+52, label, col, side==='end'?'end':'start', 26); }
// cover: whole story, faces on the chart, stage 5 hidden
chart('c0', C.length, { extra: F => {
  const faces=['','🙂','🙏','😡','😐']; let s='';
  [1,2,3,4].forEach(k=>{ const i=S[k], above=false; const yy=above?F.y(C[i].h)-70:F.y(C[i].l)+74;
    s+=`<circle cx="${F.x(i)}" cy="${yy}" r="46" fill="#fff" stroke="#e5e1f5" stroke-width="3"/><text x="${F.x(i)}" y="${yy+20}" font-size="54" text-anchor="middle">${faces[k]}</text><text x="${F.x(i)+40}" y="${yy-34}" font-size="24" fill="#5232d4" style="font-family:Mono;font-weight:700">${k}</text>`; });
  const i=C.length-1, yy=F.y(C[RE].h)-80;
  s+=`<circle cx="${F.x(i)-20}" cy="${yy}" r="46" fill="url(#g)" /><text x="${F.x(i)-20}" y="${yy+20}" font-size="54" fill="#fff" text-anchor="middle" style="font-family:Jakarta;font-weight:800">?</text><text x="${F.x(i)+20}" y="${yy-34}" font-size="24" fill="#5232d4" style="font-family:Mono;font-weight:700">5</text>`;
  return `<defs><linearGradient id="g" x1="0" x2="1"><stop offset="0" stop-color="#6541e9"/><stop offset="1" stop-color="#feb2b4"/></linearGradient></defs>`+s; } });
// stage slides: chart revealed up to that stage, P&L at that moment
chart('c1', S[1], { extra: F => marker(F, S[1], money(pnl(S[1])), '#dc2626') });
chart('c2', S[2], { extra: F => `<path d="M${F.x(S[2])},${F.y(C[S[2]].c)-16} C${F.x(S[2])+60},${F.y(E)+60} ${F.x(S[2])+120},${F.y(E)+20} ${F.x(S[2])+170},${F.y(E)+4}" fill="none" stroke="#8a5ddc" stroke-width="4" stroke-dasharray="10 8"/>`+pill(F.x(S[2])+180, F.y(E)+44, 'please 🙏', '#8a5ddc', 'start', 22)+marker(F, S[2], money(pnl(S[2])), '#dc2626', 'end') });
chart('c3', S[3], { extra: F => { const st=[E-8,E-16,E-26]; let s=''; st.forEach((v,k)=>{ s+=`<line x1="${F.x(ENTRY)}" x2="${F.x(S[3])+40}" y1="${F.y(v)}" y2="${F.y(v)}" stroke="${k===2?'#dc2626':'#f1a3a3'}" stroke-width="2.5" stroke-dasharray="6 6"/>`; s+=pill(F.x(ENTRY)-4, F.y(v), k?'SL moved':'SL', k===2?'#dc2626':'#f1a3a3', 'end', 18); }); return s+marker(F, S[3], money(pnl(S[3])), '#dc2626', 'end'); } });
chart('c4', S[4], { extra: F => `<line x1="${F.x(S[4])}" x2="${F.x(S[4])}" y1="${F.top}" y2="${F.bot}" stroke="#15132a" stroke-width="2" stroke-dasharray="4 6"/>`+marker(F, S[4], 'closed ' + money(pnl(S[4])), '#991b1b', 'end') });
// stage 5: re-entry at double size right after closing; price keeps falling
chart('c5', C.length, { extra: F => { const yy=F.y(C[RE].c); return `<line x1="${F.x(RE)}" x2="${F.W-8}" y1="${yy}" y2="${yy}" stroke="#dc2626" stroke-width="3" stroke-dasharray="10 8"/>`+pill(F.x(RE)-20, yy-80, 'BUY AGAIN · 2× lots 🤡', '#dc2626', 'end', 22)+`<circle cx="${F.x(S[4])}" cy="${F.y(C[S[4]].c)}" r="11" fill="#991b1b" stroke="#fff" stroke-width="4"/>`; } });
'''
html = f'''<!doctype html><html><head><meta charset="utf-8"><title>{TAG}</title><style>{css}{extra}</style></head><body><div class="glow"></div>
{''.join(f'<div class="page" id="p{i + 1}">{p}</div>' for i, p in enumerate(pages))}<script>{JS}</script></body></html>'''
(OUT / 'edge-notes-09-light.html').write_text(html)
print('ok')
