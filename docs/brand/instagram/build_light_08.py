# Edge Notes 08 (light theme, 8 slides): "Trader → English" — a sarcastic dictionary of things traders say, each with the chart that translates it.
import pathlib
OUT = pathlib.Path(__file__).parent
base = (OUT / 'edge-notes-01-v2-light.html').read_text()
css = base[base.index('<style>') + 7:base.index('</style>')]
N = 8
TAG = 'EDGE NOTES · 08'
extra = '''
.page { display: none; }
h1.hook { font-size: 112px; line-height: .96; letter-spacing: -.045em; margin: 54px 0 18px; color: var(--strong); }
.one { font-size: 38px; font-weight: 600; color: var(--muted); margin: 0; letter-spacing: -.01em; }
.kick { display: flex; align-items: center; gap: 14px; margin-top: auto; font: 600 22px Mono, monospace; letter-spacing: .14em; color: #5232d4; }
.kick .n { width: 58px; height: 58px; font-size: 28px; border-radius: 16px; }
.say { font-family: InstrSerif, serif; font-style: italic; font-size: 76px; line-height: 1.06; color: var(--strong); margin: 22px 0 22px; letter-spacing: -.01em; }
.say::before { content: "“"; color: #8a5ddc; } .say::after { content: "”"; color: #8a5ddc; }
.means { display: flex; align-items: center; gap: 20px; padding: 22px 28px; border-radius: 22px; background: #fdecec; border: 1.5px solid #f7c6c6; }
.means span { flex: none; font: 700 19px Mono, monospace; letter-spacing: .14em; color: #fff; background: var(--down); border-radius: 10px; padding: 8px 12px; }
.means b { font-size: 44px; line-height: 1.12; letter-spacing: -.025em; color: var(--strong); }
.viz { margin-top: 24px; padding: 22px 22px 10px; }
.cap { display: flex; justify-content: space-between; font: 500 19px Mono, monospace; color: var(--faint); padding: 4px 6px 6px; }
/* cover translator */
.gt { margin-top: 34px; padding: 0; overflow: hidden; }
.gt-h { display: flex; justify-content: space-between; align-items: center; padding: 18px 30px; border-bottom: 1.5px solid var(--line); font: 600 21px Mono, monospace; letter-spacing: .14em; color: var(--muted); }
.gt-h i { font-style: normal; font-size: 30px; color: #8a5ddc; }
.gt-in, .gt-out { padding: 22px 30px; font-size: 44px; line-height: 1.12; letter-spacing: -.025em; font-weight: 700; }
.gt-in { color: var(--strong); }
.gt-out { background: #f6f3fe; color: var(--down); border-top: 1.5px solid var(--line); }
.gt svg { display: block; margin: 6px 14px 0; }
/* timeframe ladder */
.ladder { display: grid; grid-template-columns: repeat(5, 1fr); gap: 14px; align-items: start; padding: 8px 6px 4px; }
.ladder div { text-align: center; }
.ladder em { display: block; font: 700 30px Mono, monospace; font-style: normal; color: var(--strong); background: #f3f1fb; border: 1.5px solid var(--line); border-radius: 16px; padding: 14px 0; }
.ladder .x em { background: var(--down); border-color: var(--down); color: #fff; }
.ladder i { display: block; margin: 14px auto 10px; width: 64%; border-radius: 6px 6px 14px 14px; background: linear-gradient(180deg, #f1a3a3, #dc2626); }
.ladder b { font: 700 27px Mono, monospace; color: var(--down); }
/* balance bars */
.bal { display: grid; grid-template-columns: repeat(5, 1fr); gap: 18px; align-items: end; height: 560px; padding: 10px 10px 0; }
.bal span { display: flex; flex-direction: column; justify-content: flex-end; height: 100%; gap: 10px; text-align: center; font: 700 26px Mono, monospace; color: var(--strong); }
.bal i { display: block; border-radius: 16px 16px 6px 6px; background: linear-gradient(180deg, #8a5ddc, #6541e9); }
.bal small { font: 600 22px Jakarta, sans-serif; color: var(--muted); }
.bal .z i { background: none; border-bottom: 5px dashed var(--down); border-radius: 0; height: 6px !important; }
.bal .z b { font-size: 54px; }
/* memory vs journal */
.vs { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
.vs div { border-radius: 26px; padding: 110px 20px; text-align: center; background: #fff; border: 1.5px solid var(--line); box-shadow: 0 16px 40px rgb(60 40 140 / .08); }
.vs em { display: block; font: 600 22px Mono, monospace; font-style: normal; letter-spacing: .14em; color: var(--muted); }
.vs b { display: block; font: 700 100px Mono, monospace; line-height: 1.1; margin: 20px 0 12px; letter-spacing: -.03em; }
.vs small { display: block; font-size: 28px; color: var(--muted); }
.vs .g b { color: var(--up); } .vs .r { background: #fdecec; border-color: #f7c6c6; } .vs .r b { color: var(--down); }
/* final checklist */
h2.end { font-size: 100px; line-height: .98; letter-spacing: -.045em; margin: 50px 0 30px; color: var(--strong); }
.check { list-style: none; margin: 0; padding: 0; display: grid; gap: 14px; }
.check li { display: flex; gap: 20px; align-items: center; background: #fff; border: 1.5px solid var(--line); border-radius: 20px; padding: 20px 24px; font-size: 34px; box-shadow: 0 8px 24px rgb(60 40 140 / .05); color: var(--strong); font-weight: 600; letter-spacing: -.015em; }
.check .n { width: 50px; height: 50px; font-size: 24px; border-radius: 13px; }
.foot { margin-top: auto; padding-top: 26px; }
'''
def dots(i): return '<div class="dots">' + ''.join(f'<i{" class=on" if j == i else ""}></i>' for j in range(1, N + 1)) + '</div>'
def top(): return f'<div class="top"><div class="word">journzey</div><div class="tag">{TAG}</div></div>'
def foot(i, left): return f'<div class="foot"><div class="swipe">{left}</div>{dots(i)}</div>'
def entry(k, said, means): return f'<div class="kick"><span class="n">{k}</span>ENTRY {k} OF 6</div><div class="say">{said}</div><div class="means"><span>TRANSLATION</span><b>{means}</b></div>'
CTA = '<div class="cta">journzey is coming soon <span>Follow for launch day 🔔</span></div>'
NEXT = 'Next →'

pages = [
 # 1 · cover
 f'''{top()}<h1 class="hook">Trader → English.<br><span class="serif grad" style="font-weight:400">A dictionary.</span></h1>
 <p class="one">#4 is personal.</p>
 <div class="card gt"><div class="gt-h"><span>TRADER</span><i>⇄</i><span>ENGLISH</span></div>
  <div class="gt-in">“It's just a pullback.”</div>
  <div class="gt-out">I'm down 4R and in denial.</div>
  <svg id="c0" width="910" height="400"></svg></div>
 {foot(1, 'Swipe to translate <span class="grad" style="font-size:40px">→</span>')}''',
 # 2 · moved stop
 f'''{top()}{entry(1, "I'll give it a bit more room.", 'I no longer have a stop.')}
 <div class="card viz"><svg id="c1" width="890" height="600"></svg></div>{foot(2, NEXT)}''',
 # 3 · retest
 f'''{top()}{entry(2, "It's a retest.", 'It broke. Bye.')}
 <div class="card viz"><svg id="c2" width="890" height="600"></svg></div>{foot(3, NEXT)}''',
 # 4 · swing trader
 f'''{top()}{entry(3, "I'm a swing trader now.", 'My 5-minute scalp went red.')}
 <div class="card viz"><div class="ladder">
  <div><em>1m</em><i style="height:34px"></i><b>−$40</b></div>
  <div><em>15m</em><i style="height:80px"></i><b>−$95</b></div>
  <div><em>4H</em><i style="height:170px"></i><b>−$210</b></div>
  <div><em>1D</em><i style="height:290px"></i><b>−$480</b></div>
  <div class="x"><em>1W 💀</em><i style="height:410px"></i><b>−$1,150</b></div>
 </div><div class="cap"><span>same trade · new timeframe every time it got worse</span><span>example</span></div></div>{foot(4, NEXT)}''',
 # 5 · stop when green
 f'''{top()}{entry(4, "I'll stop when I'm green.", 'See you at 3 a.m.')}
 <div class="card viz"><svg id="c4" width="890" height="600"></svg></div>{foot(5, NEXT)}''',
 # 6 · risk management
 f'''{top()}{entry(5, 'Risk management is boring.', 'So is a $0 balance.')}
 <div class="card viz"><div class="bal">
  <span><b>$10k</b><i style="height:400px"></i><small>Mon</small></span>
  <span><b>$8.4k</b><i style="height:336px"></i><small>Tue</small></span>
  <span><b>$5.9k</b><i style="height:236px"></i><small>Wed</small></span>
  <span><b>$2.3k</b><i style="height:92px"></i><small>Thu</small></span>
  <span class="z"><b>💀</b><i></i><small>Fri</small></span>
 </div><div class="cap"><span>account balance · 10% risk per trade</span><span>example</span></div></div>{foot(6, NEXT)}''',
 # 7 · journal
 f'''{top()}{entry(6, "I don't need a journal.", 'I only remember the wins.')}
 <div class="vs" style="margin-top:28px"><div class="g"><em>YOUR MEMORY</em><b>+$1,200</b><small>“great month 😎”</small></div>
 <div class="r"><em>YOUR JOURNAL</em><b>−$340</b><small>same month, 23 trades</small></div></div>
 <div class="cap" style="margin-top:14px"><span>same trader · same month</span><span>example</span></div>{foot(7, 'The cure →')}''',
 # 8 · cure + CTA
 f'''{top()}<h2 class="end">The cure is<br><span class="serif grad" style="font-weight:400">boring.</span></h2>
 <ol class="check">
  <li><span class="n">1</span>Stops only move toward profit</li>
  <li><span class="n">2</span>Wait for the retest to hold</li>
  <li><span class="n">3</span>Pick the timeframe before entry</li>
  <li><span class="n">4</span>2 losses = done for the day</li>
  <li><span class="n">5</span>Risk 1% or less per trade</li>
  <li><span class="n">6</span>Journal every single trade</li>
 </ol>
 {CTA}<div class="foot" style="padding-top:18px"><div class="swipe" style="font-size:28px">Send this to whoever said #3 😭</div>{dots(8)}</div>''',
]

JS = r'''
function rng(seed){ return () => (seed = (seed * 16807) % 2147483647) / 2147483647; }
function candles(dr, start, seed, vol){ const r=rng(seed); let p=start; return dr.map(d=>{ const o=p, c=o+d+(r()-0.5)*vol; const h=Math.max(o,c)+r()*vol*.7, l=Math.min(o,c)-r()*vol*.7; p=c; return {o,c,h,l}; }); }
function frame(svg, c, padR, extra){ const W=+svg.getAttribute('width'), H=+svg.getAttribute('height'), top=16, bot=H-36, left=6;
  const ex=extra||[]; const hi=Math.max(...c.map(k=>k.h),...ex)+2, lo=Math.min(...c.map(k=>k.l),...ex)-2;
  const y=v=>top+(hi-v)/(hi-lo)*(bot-top), n=c.length, step=(W-padR-left)/n, bw=step*.62, x=i=>left+i*step+step/2; return {W,H,top,bot,y,x,bw,hi,lo}; }
function grid(F){ let s=''; for(let t=0;t<=4;t++){ const yy=F.top+(F.bot-F.top)*t/4; s+=`<line x1="6" x2="${F.W-6}" y1="${yy}" y2="${yy}" stroke="#eeebf8" stroke-width="1.5"/>`; } return s; }
function drawC(F, c){ let s=''; c.forEach((k,i)=>{ const col=k.c>=k.o?'#059669':'#dc2626', yo=F.y(k.o), yc=F.y(k.c); s+=`<line x1="${F.x(i)}" x2="${F.x(i)}" y1="${F.y(k.h)}" y2="${F.y(k.l)}" stroke="${col}" stroke-width="2.2"/><rect x="${F.x(i)-F.bw/2}" y="${Math.min(yo,yc)}" width="${F.bw}" height="${Math.max(3,Math.abs(yc-yo))}" rx="2.5" fill="${col}"/>`; }); return s; }
function pill(x, y, t, col, anchor){ const w=t.length*12.5+30; const xx=anchor==='end'?x-w:anchor==='mid'?x-w/2:x; return `<rect x="${xx}" y="${y-19}" width="${w}" height="38" rx="11" fill="${col}"/><text x="${xx+w/2}" y="${y+7}" font-size="20" fill="#fff" text-anchor="middle" style="font-family:Jakarta;font-weight:700">${t}</text>`; }
function hline(F, p, col, label, side, dash){ const yy=F.y(p); return `<line x1="6" x2="${F.W-6}" y1="${yy}" y2="${yy}" stroke="${col}" stroke-width="2.5" stroke-dasharray="${dash||'9 7'}"/>`+(label?pill(side==='left'?12:F.W-10, yy, label, col, side==='left'?'start':'end'):''); }
function sym(F, t){ return `<text x="14" y="${F.H-8}" font-size="18" fill="#8f8ca8" style="font-family:Mono">${t}</text><text x="${F.W-10}" y="${F.H-8}" font-size="18" fill="#8f8ca8" text-anchor="end">illustration</text>`; }
// cover · a downtrend where every bounce is "just a pullback"
(function(){ const svg=document.getElementById('c0');
  const dr=[-1,-2.2,-1.4,1.2,1.6,.6,-2.4,-2.8,-1.6,1.4,1.8,-.4,-2.6,-3,-2,1.2,1.4,.2,-2.6,-3.2,-2.4,-1.2,-1.8,-2.6];
  const c=candles(dr,4180,7,2.2); const F=frame(svg,c,14); let s=grid(F)+drawC(F,c);
  [5,10,17].forEach((i,k)=>{ s+=pill(F.x(i), F.y(c[i].h)-30, k<2?'“pullback”':'“pullback” 🙂', '#6541e9', 'mid'); });
  svg.innerHTML=s+sym(F,'XAUUSD 15m'); })();
// 1 · the stop gets moved three times, price goes through all of them
(function(){ const svg=document.getElementById('c1');
  const dr=[.8,1.2,.4,-.6,-1.4,-.8,-1.6,-.4,.6,-1.8,-2,-.6,.4,-1.6,-2.2,-1,.3,-1.8,-2.4,-1.2,-.6,-1.6,-2];
  const c=candles(dr,4120,3,2.0); const e=c[2].c;
  const F=frame(svg,c,14,[e+2]); let s=grid(F);
  s+=hline(F,e,'#6541e9','entry');
  const st=[e-4,e-9,e-15]; st.forEach((p,k)=>{ s+=hline(F,p,k===2?'#dc2626':'#f1a3a3',`SL ${k+1}${k?' “a bit more room”':''}`,'left','6 6'); });
  s+=drawC(F,c); const last=c[c.length-1];
  s+=pill(F.W-10, F.y(last.c)-40, `now: ${((last.c-e)/4).toFixed(1)}R`, '#991b1b', 'end');
  svg.innerHTML=s+sym(F,'XAUUSD 5m'); })();
// 2 · support breaks; the hoped-for retest never comes
(function(){ const svg=document.getElementById('c2');
  const dr=[.4,-.6,.8,-.4,.6,-.8,.5,-.3,.4,-.6,-2.6,-2.2,-1.4,.6,-1.8,-2.4,-1.6,-.4,-2,-2.6,-1.2,-1.8];
  const c=candles(dr,4100,17,1.6); const sup=Math.min(...c.slice(0,10).map(k=>k.l));
  const F=frame(svg,c,14,[sup+3]); let s=grid(F)+hline(F,sup,'#636080','support','left','6 6')+drawC(F,c);
  const b=c.findIndex((k,i)=>i>9&&k.c<sup), x0=F.x(b+1), y0=F.y(c[b+1].c);
  s+=`<path d="M${x0},${y0} Q${x0+70},${F.y(sup)-10} ${x0+150},${F.y(sup)+4} T${x0+300},${F.y(sup-10)+30}" fill="none" stroke="#8a5ddc" stroke-width="4" stroke-dasharray="10 8"/>`;
  s+=pill(x0+150, F.y(sup)-36, 'the retest in your head', '#8a5ddc', 'mid');
  const last=c[c.length-1]; s+=pill(F.W-10, F.y(last.c)-40, 'the actual chart', '#dc2626', 'end');
  svg.innerHTML=s+sym(F,'NAS100 15m'); })();
// 4 · intraday P&L: green once at 10:15, kept trading until 3 a.m.
(function(){ const svg=document.getElementById('c4'); const W=890,H=600,L=80,R=20,T=40,B=540;
  const pts=[[9,0],[9.5,-60],[10,-20],[10.25,20],[10.5,-40],[11,-120],[12,-80],[13,-190],[14,-150],[15,-260],[16,-210],[17,-320],[18,-280],[19,-360],[20,-300],[21,-420],[22,-380],[23,-470],[24,-430],[25,-540],[26,-500],[27,-640]];
  const lo=-700, hi=100, x=h=>L+(h-9)/(27-9)*(W-L-R), y=v=>T+(hi-v)/(hi-lo)*(B-T);
  let s=''; [100,0,-200,-400,-600].forEach(v=>{ s+=`<line x1="${L}" x2="${W-R}" y1="${y(v)}" y2="${y(v)}" stroke="${v?'#eeebf8':'#c9c4e4'}" stroke-width="${v?1.5:2.5}" ${v?'':'stroke-dasharray="8 6"'}/><text x="${L-12}" y="${y(v)+7}" font-size="18" fill="#8f8ca8" text-anchor="end" style="font-family:Mono">${v>0?'+':''}${v}</text>`; });
  const d=pts.map((p,i)=>(i?'L':'M')+x(p[0])+','+y(p[1])).join(' ');
  s+=`<path d="${d} L${x(27)},${y(lo)} L${x(9)},${y(lo)} Z" fill="#dc2626" opacity=".06"/><path d="${d}" fill="none" stroke="#dc2626" stroke-width="5" stroke-linejoin="round"/>`;
  s+=`<circle cx="${x(10.25)}" cy="${y(20)}" r="11" fill="#059669"/>`+pill(x(10.25)+20, y(20)-34, '+$20 · “one more”', '#059669');
  s+=`<circle cx="${x(27)}" cy="${y(-640)}" r="11" fill="#dc2626"/>`+pill(x(27)-6, y(-640)-36, '3:00 a.m. · −$640 🌙', '#991b1b', 'end');
  [[9,'09:00'],[15,'15:00'],[21,'21:00'],[27,'03:00']].forEach(([h,t])=>{ s+=`<text x="${x(h)}" y="${H-16}" font-size="18" fill="#8f8ca8" text-anchor="${h===27?'end':h===9?'start':'middle'}" style="font-family:Mono">${t}</text>`; });
  s+=`<text x="${W-R}" y="24" font-size="18" fill="#8f8ca8" text-anchor="end">day P&amp;L · example</text>`;
  svg.innerHTML=s; })();
'''
html = f'''<!doctype html><html><head><meta charset="utf-8"><title>{TAG}</title><style>{css}{extra}</style></head><body><div class="glow"></div>
{''.join(f'<div class="page" id="p{i + 1}">{p}</div>' for i, p in enumerate(pages))}<script>{JS}</script></body></html>'''
(OUT / 'edge-notes-08-light.html').write_text(html)
print('ok')
