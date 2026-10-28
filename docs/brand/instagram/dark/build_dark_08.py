# Trader Notes 08 (dark, 7 slides): "How to blow your account in 5 easy steps" — a sarcastic tutorial; the last slide flips it into the 5 rules that prevent it.
# Reuses the CSS and chart helpers of build_dark.py (read as text, so the older posts are not regenerated).
import pathlib, re
OUT = pathlib.Path(__file__).parent
src = (OUT / 'build_dark.py').read_text()
FONTS = '../../../../public/assets/fonts'
CSS = re.search(r"CSS = '''(.*?)'''", src, re.S).group(1).replace('FONTS', FONTS)
HELPERS = re.search(r"JS = r'''(.*?)// VWAP", src, re.S).group(1)
N = 7
T = 'TRADER NOTES · 08'
CSS += '''
.hook { font-size:104px; line-height:.98; letter-spacing:-.045em; font-weight:700; color:var(--strong); margin:0 0 16px; }
.sub { font-family:InstrSerif,serif; font-style:italic; font-size:48px; color:var(--muted); margin:0 0 26px; }
.step { display:flex; align-items:center; gap:16px; margin-top:auto; font:600 22px Mono, monospace; letter-spacing:.14em; color:#c4b5ff; }
.step .n { flex:none; width:58px; height:58px; border-radius:16px; display:grid; place-items:center; color:#fff; background:var(--brand); font:700 30px Mono, monospace; letter-spacing:0; }
.ttl { font-size:96px; line-height:1; letter-spacing:-.045em; font-weight:700; color:var(--strong); margin:18px 0 14px; }
.says { font-family:InstrSerif,serif; font-style:italic; font-size:54px; line-height:1.12; color:#c4b5ff; margin:0 0 28px; }
.card { padding:20px 20px 10px; }
.cap { display:flex; justify-content:space-between; font:500 19px Mono, monospace; color:var(--faint); padding:8px 8px 6px; }
/* step 1 · one winning trade */
.win { text-align:center; padding:70px 24px 56px; }
.win .sym { font:600 26px Mono, monospace; color:var(--muted); letter-spacing:.06em; }
.win .sym b { color:var(--up); }
.win .pl { font:700 190px Mono, monospace; color:var(--up); letter-spacing:-.05em; line-height:1.1; margin:10px 0 4px; text-shadow:0 0 60px rgb(52 211 153 / .35); }
.ego { margin:30px 10px 6px; text-align:left; }
.ego div { display:flex; justify-content:space-between; font:600 24px Mono, monospace; color:var(--text); margin-bottom:12px; }
.ego div b { color:#feb2b4; }
.ego i { display:block; height:34px; border-radius:17px; background:var(--brand); box-shadow:0 0 30px rgb(157 124 255 / .5); }
/* step 2 · lot ladder */
.lots { display:grid; grid-template-columns:repeat(5,1fr); gap:18px; align-items:end; height:600px; padding:10px 10px 0; }
.lots span { display:flex; flex-direction:column; justify-content:flex-end; height:100%; gap:10px; text-align:center; }
.lots i { display:block; border-radius:14px 14px 4px 4px; background:linear-gradient(180deg,#f87171,#7f1d1d); }
.lots b { font:700 30px Mono, monospace; color:var(--strong); }
.lots small { font:600 22px Mono, monospace; color:var(--down); }
/* step 4 · revenge log */
.log { font:500 28px Mono, monospace; }
.log div { display:grid; grid-template-columns:120px 1fr 150px 170px; align-items:center; padding:28px 20px; border-bottom:1.5px solid var(--line); color:var(--text); }
.log div:last-child { border-bottom:0; }
.log .h { font-size:19px; letter-spacing:.12em; color:var(--faint); padding-top:6px; padding-bottom:12px; }
.log .r { text-align:right; } .log .d { color:var(--down); font-weight:700; }
.log .tot { background:rgb(248 113 113 / .1); border-radius:16px; font-weight:700; color:var(--strong); }
.log .tot .d { font-size:34px; }
/* step 5 · $0 + excuses */
.zero { position:relative; height:640px; display:grid; place-items:center; }
.zero .bal { text-align:center; }
.zero .bal small { display:block; font:600 22px Mono, monospace; letter-spacing:.14em; color:var(--muted); }
.zero .bal b { display:block; font:700 170px Mono, monospace; letter-spacing:-.05em; color:var(--down); line-height:1.1; text-shadow:0 0 70px rgb(248 113 113 / .35); }
.zero .bal s { font:600 34px Mono, monospace; color:var(--faint); }
.ex { position:absolute; font-size:27px; font-weight:600; color:var(--text); background:var(--panel2); border:1.5px solid var(--line); border-radius:16px; padding:12px 20px; white-space:nowrap; }
/* final */
.flip { font-size:96px; line-height:1; letter-spacing:-.045em; font-weight:700; color:var(--strong); margin:46px 0 34px; }
.rules { gap:16px; } .rules li { padding:22px 28px; align-items:center; } .rules b { font-size:36px; } .rules .n { width:54px; height:54px; font-size:26px; }
.rules s { color:var(--faint); text-decoration-thickness:3px; text-decoration-color:rgb(248 113 113 / .7); font-size:24px; display:block; font-family:Mono, monospace; margin-bottom:4px; }
.ask { margin-top:26px; font-size:32px; font-weight:600; color:var(--strong); }
'''
def who(): return f'<div class="who"><div class="av">j</div><div><b>journzey</b><span>@journzey</span></div><div class="tag">{T}</div></div>'
def foot(i, left=''): return f'<div class="foot"><div class="swipe">{left}</div><div class="count">{i} / {N}</div></div>'
def head(k, name, says): return f'<div class="step"><span class="n">{k}</span>STEP {k} OF 5</div><div class="ttl">{name}</div><p class="says">{says}</p>'
CTA = '<div class="cta">journzey is coming soon <span>Follow for launch day 🔔</span></div>'

pages = [
 f'''{who()}<div class="hook">How to blow<br>your account<br><span class="grad">in 5 easy steps.</span></div>
 <p class="sub">A tutorial. Please don't follow it. 🙃</p>
 <div class="card"><svg id="cv" width="890" height="560"></svg></div>{foot(1, 'Step 1 →')}''',
 f'''{who()}{head(1, 'Win one trade.', "You're basically a hedge fund now.")}
 <div class="card win"><div class="sym">XAUUSD · <b>BUY</b> · 0.10 lot</div><div class="pl">+$84</div>
 <div class="ego"><div><span>ego level</span><b>hedge fund 🧠</b></div><i></i></div></div>{foot(2, 'Step 2 →')}''',
 f'''{who()}{head(2, 'Double the size.', 'Confidence is a risk model.')}
 <div class="card"><div class="lots">
  <span><i style="height:22px"></i><b>0.10</b><small>1%</small></span>
  <span><i style="height:46px"></i><b>0.20</b><small>2%</small></span>
  <span><i style="height:114px"></i><b>0.50</b><small>5%</small></span>
  <span><i style="height:228px"></i><b>1.00</b><small>10%</small></span>
  <span><i style="height:440px"></i><b>2.00 🚀</b><small>20%</small></span>
 </div><div class="cap"><span>lot size · risk per trade</span><span>example</span></div></div>{foot(3, 'Step 3 →')}''',
 f'''{who()}{head(3, 'Delete your stop.', 'Stops are for people who plan to lose.')}
 <div class="card"><svg id="s3" width="890" height="620"></svg></div>{foot(4, 'Step 4 →')}''',
 f'''{who()}{head(4, 'Win it back. Now.', 'The market owes you.')}
 <div class="card log">
  <div class="h"><span>TIME</span><span>TRADE</span><span class="r">LOTS</span><span class="r">P&amp;L</span></div>
  <div><span>14:02</span><span>“revenge”</span><span class="r">1.00</span><span class="r d">−$410</span></div>
  <div><span>14:07</span><span>“revenge” 2</span><span class="r">2.00</span><span class="r d">−$780</span></div>
  <div><span>14:13</span><span>“last one”</span><span class="r">3.00</span><span class="r d">−$1,240</span></div>
  <div><span>14:20</span><span>“all in” 🙏</span><span class="r">5.00</span><span class="r d">−$2,950</span></div>
  <div class="tot"><span></span><span>18 minutes</span><span></span><span class="r d">−$5,380</span></div>
 </div><div class="cap"><span>trade log</span><span>example</span></div>{foot(5, 'Step 5 →')}''',
 f'''{who()}{head(5, 'Blame the market.', 'It was definitely rigged.')}
 <div class="card zero">
  <span class="ex" style="left:34px;top:30px">🎯 “stop hunt”</span>
  <span class="ex" style="right:34px;top:62px">🤡 “manipulation”</span>
  <span class="ex" style="left:56px;bottom:84px">📰 “the news”</span>
  <span class="ex" style="right:44px;bottom:44px">🪐 “Mercury retrograde”</span>
  <div class="bal"><small>ACCOUNT BALANCE</small><b>$0.00</b><s>$10,000</s></div>
 </div>{foot(6, 'Now flip it →')}''',
 f'''{who()}<div class="flip">Now do the<br><span class="grad">exact opposite.</span></div>
 <ol class="rules">
  <li><span class="n">1</span><span><s>win one, feel genius</s><b>One win is one data point</b></span></li>
  <li><span class="n">2</span><span><s>double the size</s><b>Same size, win or lose</b></span></li>
  <li><span class="n">3</span><span><s>delete the stop</s><b>A stop on every trade</b></span></li>
  <li><span class="n">4</span><span><s>win it back now</s><b>2 losses = close the chart</b></span></li>
  <li><span class="n">5</span><span><s>blame the market</s><b>Read your journal instead</b></span></li>
 </ol>
 <div class="ask">Which step are you on? Comment 1–5 👇</div>
 {CTA}{foot(7, 'Save this 🔖')}''',
]

JS = HELPERS + r'''
function hline(F, p, col, label, dash, side){ const yy=F.y(p); return `<line x1="4" x2="${F.W-6}" y1="${yy}" y2="${yy}" stroke="${col}" stroke-width="2.5" stroke-dasharray="${dash||'9 7'}"/>`+(label?pill(side==='left'?12:F.W-10, yy, label, col, side==='left'?'start':'end'):''); }
function note(F, t){ return `<text x="12" y="${F.H-10}" font-size="17" fill="#67648a" style="font-family:Mono">${t||''}</text><text x="${F.W-8}" y="${F.H-10}" font-size="17" fill="#67648a" text-anchor="end">illustration</text>`; }
// cover · equity curve: small rise, then the 5 steps take it to zero
(function(){ const svg=document.getElementById('cv'); const W=890,H=560,L=20,R=20,T=40,B=500;
  const v=[10000,10084,10300,10120,10600,11200,10400,9100,9600,8200,6900,7600,5200,3900,4600,2100,900,0];
  const n=v.length-1, x=i=>L+i*(W-L-R)/n, y=p=>T+(11800-p)/11800*(B-T);
  let s=''; [0,4000,8000].forEach(p=>{ s+=`<line x1="${L}" x2="${W-R}" y1="${y(p)}" y2="${y(p)}" stroke="#1d1c38" stroke-width="1.5"/>`; });
  const d=v.map((p,i)=>(i?'L':'M')+x(i)+','+y(p)).join(' ');
  s+=`<defs><linearGradient id="cg" x1="0" x2="1"><stop offset="0" stop-color="#34d399"/><stop offset=".3" stop-color="#9d7cff"/><stop offset=".6" stop-color="#f87171"/><stop offset="1" stop-color="#b91c1c"/></linearGradient></defs>`;
  s+=`<path d="${d}" fill="none" stroke="url(#cg)" stroke-width="7" stroke-linejoin="round" stroke-linecap="round"/>`;
  [[1,'1'],[5,'2'],[8,'3'],[12,'4'],[17,'5']].forEach(([i,k])=>{ const cx=x(i), cy=y(v[i]); s+=`<circle cx="${cx}" cy="${cy}" r="24" fill="#060613" stroke="#feb2b4" stroke-width="3"/><text x="${cx}" y="${cy+9}" font-size="24" fill="#faf8ff" text-anchor="middle" style="font-family:Mono;font-weight:700">${k}</text>`; });
  s+=pill(x(17)-40, y(0)+30, '$0 · tutorial complete ✅', '#b91c1c', 'end');
  s+=`<text x="${L}" y="24" font-size="18" fill="#67648a" style="font-family:Mono">account equity</text><text x="${W-R}" y="24" font-size="18" fill="#67648a" text-anchor="end">illustration</text>`;
  svg.innerHTML=s; })();
// step 3 · no stop: price walks away from the entry
(function(){ const svg=document.getElementById('s3');
  const dr=[.6,1,.4,-.8,-1.4,-.6,-1.8,-.4,.8,-1.6,-2.2,-.8,.6,-1.8,-2.4,-1.2,.4,-2,-2.6,-1.4,-.6,-2,-2.4];
  const c=candles(dr,4120,5,2.2); const e=c[2].c, ghost=e-4;
  const F=frame(svg,c,24,[e+3]); let s=grid(F,24);
  s+=hline(F,e,'#7c5cff','entry')+hline(F,ghost,'#3a3766','where your stop was','5 7');
  s+=drawC(F,c); s+=pill(F.x(4)+10, F.y(e)-44, 'stop: deleted 😎', '#5b3fd6');
  const last=c[c.length-1]; s+=pill(F.W-10, F.y(last.c)-46, `${((last.c-e)/4).toFixed(1)}R · “it'll come back”`, '#b91c1c', 'end');
  s+=note(F,'XAUUSD 5m'); svg.innerHTML=s; })();
'''
html = f'<!doctype html><html><head><meta charset="utf-8"><title>{T}</title><style>{CSS}</style></head><body><div class="glow"></div><div class="grain"></div>' + ''.join(f'<div class="page" id="p{i + 1}">{p}</div>' for i, p in enumerate(pages)) + f'<script>{JS}</script></body></html>'
(OUT / 'trader-notes-08.html').write_text(html)
print('ok')
