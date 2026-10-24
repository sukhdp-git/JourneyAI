# Trader Notes 07 (dark, 8 slides): 6 mind traps that empty trading accounts — what each looks like on a chart, and the fix.
# Reuses the CSS and chart helpers of build_dark.py (read as text, so the older posts are not regenerated).
import pathlib, re
OUT = pathlib.Path(__file__).parent
src = (OUT / 'build_dark.py').read_text()
FONTS = '../../../../public/assets/fonts'
CSS = re.search(r"CSS = '''(.*?)'''", src, re.S).group(1).replace('FONTS', FONTS)
HELPERS = re.search(r"JS = r'''(.*?)// VWAP", src, re.S).group(1)
N = 8
T = 'TRADER NOTES · 07'
CSS += '''
.trap { display:flex; align-items:center; gap:16px; margin-top:auto; font:600 22px Mono, monospace; letter-spacing:.14em; color:#c4b5ff; }
.trap .n { flex:none; width:58px; height:58px; border-radius:16px; display:grid; place-items:center; color:#fff; background:var(--brand); font:700 30px Mono, monospace; letter-spacing:0; }
.ttl { font-size:72px; line-height:1.02; letter-spacing:-.04em; font-weight:700; color:var(--strong); margin:16px 0 18px; }
.says { font-family:InstrSerif,serif; font-style:italic; font-size:46px; line-height:1.15; color:var(--text); margin:0 0 24px; }
.says::before { content:"“"; color:#9d7cff; } .says::after { content:"”"; color:#9d7cff; }
.card { padding:18px 18px 10px; }
.fix { margin-top:22px; display:flex; gap:20px; align-items:flex-start; padding:24px 28px; border-radius:22px; background:rgb(52 211 153 / .08); border:1.5px solid rgb(52 211 153 / .35); }
.fix i { font:700 20px Mono, monospace; font-style:normal; letter-spacing:.12em; color:var(--up); padding-top:6px; flex:none; }
.fix b { display:block; font-size:32px; line-height:1.25; color:var(--strong); letter-spacing:-.01em; }
.fix small { display:block; font-size:25px; line-height:1.35; color:var(--muted); margin-top:6px; }
.tiles { display:grid; grid-template-columns:repeat(5,1fr); gap:16px; padding:16px 10px 10px; }
.tiles div { border-radius:20px; padding:20px 10px; text-align:center; background:var(--panel2); border:1.5px solid var(--line); }
.tiles b { display:block; font:700 44px Mono, monospace; } .tiles small { display:block; font:500 20px Mono, monospace; color:var(--muted); margin-top:8px; }
.tiles .L b { color:var(--down); } .tiles .Q { border:2px dashed #9d7cff; } .tiles .Q b { color:#c4b5ff; }
.lots { display:grid; grid-template-columns:repeat(5,1fr); gap:16px; align-items:end; height:190px; padding:0 10px 6px; }
.lots i { display:block; border-radius:12px 12px 4px 4px; background:linear-gradient(180deg,#f87171,#7f1d1d); }
.lots span { display:flex; flex-direction:column; justify-content:flex-end; height:100%; gap:8px; font:600 20px Mono, monospace; color:var(--muted); text-align:center; }
.cap { display:flex; justify-content:space-between; font:500 20px Mono, monospace; color:var(--faint); padding:6px 12px 8px; }
.grid40 { display:grid; grid-template-columns:repeat(10,1fr); gap:10px; padding:14px 10px; }
.grid40 i { height:52px; border-radius:10px; background:rgb(248 113 113 / .75); }
.grid40 i.w { background:rgb(52 211 153 / .85); } .grid40 i.hl { outline:3px solid #feb2b4; outline-offset:2px; }
.split { display:grid; grid-template-columns:1fr 1fr; gap:16px; padding:6px 10px 12px; }
.split div { border-radius:20px; padding:22px; text-align:center; background:var(--panel2); border:1.5px solid var(--line); }
.split b { display:block; font:700 60px Mono, monospace; line-height:1.1; } .split small { display:block; font-size:22px; color:var(--muted); margin-top:6px; }
.big { font-size:80px; }
.ttl { font-size:88px; } .says { font-size:58px; } .fix b { font-size:40px; } .fix { padding:28px 32px; align-items:center; } .fix i { padding-top:0; font-size:24px; }
.tiles b { font-size:56px; } .lots { height:250px; } .split b { font-size:80px; } .grid40 i { height:60px; }
.rules b { font-size:42px; }
'''
def who(): return f'<div class="who"><div class="av">j</div><div><b>journzey</b><span>@journzey</span></div><div class="tag">{T}</div></div>'
def foot(i, left=''): return f'<div class="foot"><div class="swipe">{left}</div><div class="count">{i} / {N}</div></div>'
def head(k, name, says): return f'<div class="trap"><span class="n">{k}</span>MIND TRAP {k} OF 6</div><div class="ttl">{name}</div><p class="says">{says}</p>'
def fix(b, small=''): return f'<div class="fix"><i>FIX</i><span><b>{b}</b></span></div>'
CTA = '<div class="cta">journzey is coming soon <span>Follow for launch day 🔔</span></div>'

# recency-bias grid: 40 trades, 17 wins (42.5%), the last 5 all wins
pattern = 'LWLLWLLWLLWWLLLWLLWLLWLLLWLLLWLLLLWWWWW'
pattern = pattern + 'W' * (40 - len(pattern))
wins = pattern.count('W')
grid = ''.join(f'<i class="{"w" if c == "W" else ""}{" hl" if k >= 35 else ""}"></i>' for k, c in enumerate(pattern))

pages = [
 f'''{who()}<div class="big" style="font-size:96px">It's not your strategy.<br><span class="grad">It's these 6 thoughts.</span><small>Mind traps that empty accounts 🔖</small></div>{foot(1, 'Swipe →')}''',
 f'''{who()}{head(1, 'Loss aversion', 'It will come back.')}
 <div class="card"><svg id="t1" width="890" height="520"></svg></div>
 {fix('Never move your stop away from price.')}{foot(2, 'Next trap →')}''',
 f'''{who()}{head(2, 'Cutting winners', 'Take it before it’s gone.')}
 <div class="card"><svg id="t2" width="890" height="520"></svg></div>
 {fix('Partial at 1R. Let the rest run.')}{foot(3, 'Next trap →')}''',
 f'''{who()}{head(3, "Gambler's fallacy", "I'm due a win.")}
 <div class="card"><div class="tiles"><div class="L"><b>L</b><small>−1R</small></div><div class="L"><b>L</b><small>−1R</small></div><div class="L"><b>L</b><small>−1R</small></div><div class="L"><b>L</b><small>−1R</small></div><div class="Q"><b>?</b><small>still 50/50</small></div></div>
 <div class="lots"><span><i style="height:16%"></i>0.50</span><span><i style="height:16%"></i>0.50</span><span><i style="height:32%"></i>1.00</span><span><i style="height:64%"></i>2.00</span><span><i style="height:100%"></i>4.00</span></div>
 <div class="cap"><span>lot size</span><span>illustration</span></div></div>
 {fix('Same size after a loss. Always.')}{foot(4, 'Next trap →')}''',
 f'''{who()}{head(4, 'Recency bias', 'This setup never loses.')}
 <div class="card"><div class="split"><div><b style="color:var(--up)">100%</b><small>last 5 trades</small></div><div><b style="color:var(--down)">{wins / 40 * 100:.1f}%</b><small>all 40 trades</small></div></div><div class="grid40">{grid}</div><div class="cap"><span>same setup · 40 trades</span><span>example</span></div></div>
 {fix('Judge a setup on 30+ trades, not 5.')}{foot(5, 'Next trap →')}''',
 f'''{who()}{head(5, 'Anchoring', "I'll close at breakeven.")}
 <div class="card"><svg id="t5" width="890" height="520"></svg></div>
 {fix('Would you open it now? No → close it.')}{foot(6, 'Last trap →')}''',
 f'''{who()}{head(6, 'Confirmation bias', 'Everyone online is bullish too.')}
 <div class="card"><svg id="t6" width="890" height="520"></svg></div>
 {fix('Write 1 reason it could fail. Then enter.')}{foot(7, 'How to catch them →')}''',
 f'''{who()}<div class="quote" style="margin-top:auto;font-size:80px">"Name your trap.<br><span class="grad">Then fix it."</span></div>
 <ol class="rules" style="margin-top:50px">
  <li><span class="n">1</span><span><b>Tag your emotion on every trade.</b></span></li>
  <li><span class="n">2</span><span><b>After 20 trades, find the costliest tag.</b></span></li>
  <li><span class="n">3</span><span><b>Fix only that one this month.</b></span></li>
 </ol>
 <ul class="pts"><li><b>Which trap is yours?</b> Comment 1–6 👇</li></ul>
 {CTA}{foot(8)}''',
]

JS = HELPERS + r'''
function hline(F, p, col, label, dash, side){ const yy=F.y(p); return `<line x1="4" x2="${F.W-6}" y1="${yy}" y2="${yy}" stroke="${col}" stroke-width="2.5" stroke-dasharray="${dash||'9 7'}"/>`+pill(side==='left'?12:F.W-10, yy, label, col, side==='left'?'start':'end'); }
function note(F){ return `<text x="${F.W-8}" y="${F.H-10}" font-size="17" fill="#67648a" text-anchor="end">illustration</text>`; }
// 1 · loss aversion: stop is hit, trader holds, loss keeps growing
(function(){ const svg=document.getElementById('t1'); if(!svg) return;
  const dr=[1.2,1.5,.8,1,.4,-.2,.6,-1.2,-1.6,-1,-.6,-1.8,-2.4,-1.2,-.4,-1.6,-2,-1.4,-.8,-1.6,-2.2,-1,-.4,-1.2];
  const c=candles(dr,4120,5,2.4); const e=c[6].c, stop=e-5;
  const F=frame(svg,c,24,[e+3,stop]); let s=grid(F,24);
  s+=hline(F,e,'#7c5cff','entry')+hline(F,stop,'#f87171','your stop · −1R');
  const hit=c.findIndex((k,i)=>i>6&&k.l<stop); s+=drawC(F,c);
  s+=`<circle cx="${F.x(hit)}" cy="${F.y(stop)}" r="14" fill="none" stroke="#f87171" stroke-width="4"/>`+pill(F.x(hit)+26, F.y(stop)+52, 'stop hit… but moved', '#b91c1c');
  const last=c[c.length-1].c, r=((last-e)/5).toFixed(1); s+=pill(F.W-10, F.y(last)-34, `still holding: ${r}R`, '#7f1d1d', 'end');
  s+=note(F); svg.innerHTML=s; })();
// 2 · fear of giving it back: closed at +0.4R, price runs to the 3R target
(function(){ const svg=document.getElementById('t2'); if(!svg) return;
  const dr=[-.6,-.4,.2,-.3,.4,.8,.6,-.5,-.4,.9,1.2,.7,-.3,1.4,1.6,.8,1.2,-.4,1.5,1.8,1,1.3,.6,.4];
  const c=candles(dr,4120,9,2.0); const e=c[5].c, stop=e-4, tp=e+12;
  const F=frame(svg,c,24,[stop-1,tp+2]); let s=grid(F,24);
  s+=hline(F,tp,'#34d399','target · 3R',null,'left')+hline(F,e,'#7c5cff','entry');
  s+=drawC(F,c); const xi=8; s+=`<circle cx="${F.x(xi)}" cy="${F.y(e+1.6)}" r="14" fill="none" stroke="#feb2b4" stroke-width="4"/>`+pill(F.x(xi)+24, F.y(e+1.6)+48, 'you closed: +0.4R', '#b0558f');
  const hit=c.findIndex((k,i)=>i>xi&&k.h>=tp); if(hit>0) s+=pill(F.x(hit)-14, F.y(tp)-30, 'target hit without you', '#047857', 'end');
  s+=note(F); svg.innerHTML=s; })();
// 5 · anchoring: price never returns to the entry
(function(){ const svg=document.getElementById('t5'); if(!svg) return;
  const dr=[.4,.8,.2,-1.6,-2.2,-1.2,-.4,.6,.3,-.8,-.4,.5,-.2,-.6,.4,-.3,-.9,-.5,.3,-.4,-1,-.6,.2,-.5];
  const c=candles(dr,4140,13,2.2); const e=c[2].c;
  const F=frame(svg,c,24,[e+3]); let s=grid(F,24);
  s+=`<rect x="4" y="${F.y(e)}" width="${F.W-30}" height="10" fill="#7c5cff" opacity=".12"/>`+hline(F,e,'#7c5cff','your entry');
  s+=drawC(F,c); const mid=14, hiM=Math.max(...c.slice(6).map(k=>k.h));
  s+=pill(F.x(mid)-120, F.y(hiM)-40, 'waiting for breakeven…', '#5b3fd6');
  s+=pill(F.W-10, F.y(c[c.length-1].c)+44, 'still waiting', '#3a3766', 'end');
  s+=note(F); svg.innerHTML=s; })();
// 6 · confirmation bias: support breaks while the feed stays bullish
(function(){ const svg=document.getElementById('t6'); if(!svg) return;
  const dr=[1,1.4,.8,1.2,.4,-.6,.3,-.8,.5,-.4,.2,-.6,-.2,-2.4,-3,-1.6,-.6,-1.8,-2.2,-1,-.4,-1.4];
  const c=candles(dr,4100,17,2.2); const sup=Math.min(...c.slice(4,13).map(k=>k.l));
  const F=frame(svg,c,24); let s=grid(F,24); s+=hline(F,sup,'#9d9abb','support','6 6','left'); s+=drawC(F,c);
  const brk=c.findIndex((k,i)=>i>12&&k.c<sup); s+=`<circle cx="${F.x(brk)}" cy="${F.y(sup)}" r="14" fill="none" stroke="#f87171" stroke-width="4"/>`+pill(F.x(brk)+24, F.y(sup)+46, 'support broke', '#b91c1c');
  const chip=(x,y,t)=>`<g transform="translate(${x},${y})"><rect width="${t.length*12+44}" height="44" rx="14" fill="#14142c" stroke="#262448" stroke-width="2"/><text x="16" y="29" font-size="19" fill="#dedcec" style="font-family:Jakarta;font-weight:600">${t}</text></g>`;
  s+=chip(F.W-340, 24, '🚀 "can\'t go lower"')+chip(F.W-300, 80, '💎 "buy the dip"')+chip(F.W-360, 136, '📈 "100% bullish"');
  s+=note(F); svg.innerHTML=s; })();
'''
html = f'<!doctype html><html><head><meta charset="utf-8"><title>{T}</title><style>{CSS}</style></head><body><div class="glow"></div><div class="grain"></div>' + ''.join(f'<div class="page" id="p{i + 1}">{p}</div>' for i, p in enumerate(pages)) + f'<script>{JS}</script></body></html>'
(OUT / 'trader-notes-07.html').write_text(html)
print('ok', wins, 'wins of 40')
