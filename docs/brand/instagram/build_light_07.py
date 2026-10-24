# Edge Notes 07 (light theme, 8 slides): "Your P&L is lying to you" — 6 numbers that matter more than profit.
import pathlib
OUT = pathlib.Path(__file__).parent
base = (OUT / 'edge-notes-01-v2-light.html').read_text()
css = base[base.index('<style>') + 7:base.index('</style>')]
N = 8
TAG = 'EDGE NOTES · 07'
extra = '''
.page { display: none; }
.kick { display: flex; align-items: center; gap: 14px; margin-top: 46px; font: 600 22px Mono, monospace; letter-spacing: .12em; color: #5232d4; }
.kick .n { width: 54px; height: 54px; font-size: 28px; border-radius: 15px; }
h2 { margin: 18px 0 14px; font-size: 76px; }
.lead { font-size: 32px; line-height: 1.32; margin: 0; color: var(--text); }
.lead b { color: var(--strong); }
.viz { margin-top: 30px; padding: 26px 28px; }
.viz h3 { margin: 0 0 18px; font-size: 22px; font-weight: 600; color: var(--muted); display: flex; justify-content: space-between; }
.viz h3 span { font: 500 19px Mono, monospace; color: var(--faint); }
.hb { display: grid; grid-template-columns: 220px 1fr 170px; align-items: center; gap: 18px; margin: 16px 0; font-size: 26px; color: var(--strong); font-weight: 600; }
.hb .tk { height: 46px; border-radius: 12px; background: #f3f1fb; overflow: hidden; }
.hb .tk i { display: block; height: 100%; border-radius: 12px; }
.hb .v { font: 700 30px Mono, monospace; text-align: right; }
.res { margin-top: 18px; padding: 20px 26px; border-radius: 20px; background: #f3effe; border: 1.5px solid #ddd3fb; display: flex; justify-content: space-between; align-items: center; font-size: 28px; font-weight: 700; color: var(--strong); }
.res b { font: 700 40px Mono, monospace; background: var(--brand); -webkit-background-clip: text; background-clip: text; color: transparent; }
.res.bad { background: #fdecec; border-color: #f7c6c6; } .res.bad b { background: none; color: var(--down); }
.guide { margin-top: 24px; display: grid; gap: 12px; }
.guide div { display: grid; grid-template-columns: 210px 1fr; gap: 18px; align-items: center; background: #fff; border: 1.5px solid var(--line); border-radius: 18px; padding: 16px 22px; font-size: 26px; }
.guide b { font: 700 26px Mono, monospace; color: var(--strong); }
.guide span { color: var(--muted); }
.tip { margin-top: 24px; padding: 22px 26px; border-radius: 22px; background: #fff; border: 1.5px dashed rgb(101 65 233 / .4); font-size: 27px; line-height: 1.36; color: var(--text); }
.tip b { color: var(--strong); }
.streak { display: grid; grid-template-columns: repeat(9, 1fr); gap: 12px; margin: 6px 0 4px; }
.streak i { height: 74px; border-radius: 12px; display: grid; place-items: center; font: 700 22px Mono, monospace; font-style: normal; color: #fff; }
.streak i.w { background: var(--up); } .streak i.l { background: #f1a3a3; } .streak i.l.hot { background: var(--down); box-shadow: 0 0 0 4px rgb(220 38 38 / .18); }
.kpi2 { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
.kpi2 div { border-radius: 22px; padding: 26px; background: #f7f6fd; border: 1.5px solid var(--line); text-align: center; }
.kpi2 b { display: block; font: 700 64px Mono, monospace; color: var(--strong); line-height: 1.1; }
.kpi2 small { display: block; font-size: 22px; color: var(--muted); margin-top: 6px; }
.kpi2 .bad b { color: var(--down); } .kpi2 .good b { color: var(--up); }
.hook { font-size: 104px; line-height: .96; margin: 56px 0 20px; }
.check { list-style: none; margin: 26px 0 0; padding: 0; display: grid; gap: 14px; }
.check li { display: flex; gap: 18px; align-items: center; background: #fff; border: 1.5px solid var(--line); border-radius: 18px; padding: 16px 22px; font-size: 27px; box-shadow: 0 8px 24px rgb(60 40 140 / .05); }
.check li b { color: var(--strong); } .check .n { width: 44px; height: 44px; font-size: 22px; border-radius: 12px; }
.foot { margin-top: auto; }
.legend2 { display: flex; gap: 14px; margin: 0 0 8px; }
.legend2 span { font-size: 22px; font-weight: 700; padding: 8px 16px; border-radius: 99px; }
.legend2 .a { background: #ecfdf5; color: var(--up); } .legend2 .b { background: #fdecec; color: var(--down); }
.legend2 b { font-family: Mono, monospace; }
/* fill the 4:5 frame */
h2 { font-size: 84px; } .lead { font-size: 34px; }
.viz { padding: 30px 32px; margin-top: 34px; } .viz h3 { font-size: 24px; }
.hb { font-size: 29px; margin: 22px 0; grid-template-columns: 240px 1fr 180px; } .hb .tk { height: 56px; } .hb .v { font-size: 34px; }
.res { padding: 24px 28px; font-size: 30px; } .res b { font-size: 44px; }
.guide div { padding: 20px 24px; font-size: 28px; } .guide b { font-size: 28px; }
.tip { font-size: 29px; padding: 26px 30px; }
.kpi2 b { font-size: 84px; } .kpi2 div { padding: 34px 26px; }
.check li { padding: 20px 24px; font-size: 30px; }
.one { font-size: 38px; font-weight: 600; color: var(--strong); margin: 22px 0 0; letter-spacing: -.01em; }
.scale { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-top: 26px; }
.scale span { border-radius: 22px; padding: 26px 10px; text-align: center; font: 700 38px Mono, monospace; color: #fff; }
.scale small { display: block; font: 600 22px Jakarta, sans-serif; margin-top: 6px; opacity: .95; }
.scale .r { background: var(--down); } .scale .y { background: #f59e0b; } .scale .g { background: var(--up); }
.rec { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-top: 24px; }
.rec div { background: #fff; border: 1.5px solid var(--line); border-radius: 22px; padding: 22px 8px; text-align: center; box-shadow: 0 8px 24px rgb(60 40 140 / .06); }
.rec b { display: block; font: 700 40px Mono, monospace; color: var(--down); } .rec i { display: block; font: 600 28px Mono, monospace; font-style: normal; color: var(--strong); margin-top: 6px; }
.rec .x { background: #fdecec; border-color: #f7c6c6; }
.kpi2.tall div { padding: 60px 20px; } .kpi2.tall b { font-size: 120px; } .kpi2 small { font-size: 26px; }
h2 { font-size: 96px; } .hook { font-size: 120px; }
.kick { margin-top: auto; } .foot { margin-top: auto; padding-top: 30px; }
.hb { margin: 30px 0; font-size: 34px; grid-template-columns: 220px 1fr 200px; } .hb .tk { height: 84px; border-radius: 18px; } .hb .tk i { border-radius: 18px; } .hb .v { font-size: 40px; }
.viz { padding: 36px 36px; } .res { padding: 30px 32px; font-size: 34px; } .res b { font-size: 54px; }
.scale span { padding: 40px 10px; font-size: 46px; } .kpi2 div { padding: 46px 20px; } .kpi2 b { font-size: 96px; }
.rec b { font-size: 46px; } .rec i { font-size: 32px; } .rec div { padding: 30px 8px; }
.streak i { height: 84px; font-size: 26px; } .one { font-size: 44px; margin-top: 30px; }
#p5 .hb { margin: 22px 0; } #p5 .hb .tk { height: 60px; } #p5 .streak i { height: 70px; }
.check li { padding: 24px 28px; font-size: 36px; }
#p5 .hb { margin: 12px 0; } #p5 .hb .tk { height: 46px; } #p5 .streak i { height: 60px; } #p5 .viz { padding: 24px 30px; margin-top: 24px; } #p5 .tip { margin-top: 20px; }
'''
def dots(i): return '<div class="dots">' + ''.join(f'<i{" class=on" if j == i else ""}></i>' for j in range(1, N + 1)) + '</div>'
def top(): return f'<div class="top"><div class="word">journzey</div><div class="tag">{TAG}</div></div>'
def foot(i, left): return f'<div class="foot"><div class="swipe">{left}</div>{dots(i)}</div>'
def kick(k, label): return f'<div class="kick"><span class="n">{k}</span>NUMBER {k} OF 6 · {label}</div>'
CTA = '<div class="cta">journzey is coming soon <span>Follow for launch day 🔔</span></div>'

def hb(label, pct, val, col, vcol=None):
    return f'<div class="hb"><span>{label}</span><div class="tk"><i style="width:{pct}%;background:{col}"></i></div><span class="v" style="color:{vcol or col}">{val}</span></div>'

pages = []
def line(t): return f'<p class="one">{t}</p>'
# 1 · cover
pages.append(f"""{top()}<h1 class="hook">Your P&amp;L<br><span class="serif grad">is lying.</span></h1>
{line('Same profit. Very different trader.')}
<div class="card viz" style="padding-bottom:16px"><div class="legend2"><span class="a">A · max drop <b id="mdA"></b></span><span class="b">B · max drop <b id="mdB"></b></span></div><svg id="eq2" width="880" height="540"></svg></div>
{foot(1, '6 numbers that tell the truth <span class="grad" style="font-size:40px">→</span>')}""")
# 2 · profit factor
pages.append(f"""{top()}{kick(1, 'EFFICIENCY')}<h2><span class="serif grad">Profit factor</span></h2>
<div class="card viz">{hb('Profit', 90, '$3,600', 'var(--up)')}{hb('Loss', 50, '$2,000', 'var(--down)')}<div class="res">PF = profit ÷ loss <b>1.80</b></div></div>
<div class="scale"><span class="r">&lt; 1<small>losing</small></span><span class="y">1 – 1.5<small>thin</small></span><span class="g">1.5 +<small>real edge</small></span></div>
{foot(2, 'Next →')}""")
# 3 · avg win vs avg loss
pages.append(f"""{top()}{kick(2, 'PAYOFF')}<h2>Avg win <span class="serif grad">vs avg loss</span></h2>
<div class="card viz">{hb('Avg win', 68, '+$180', 'var(--up)')}{hb('Avg loss', 90, '−$240', 'var(--down)')}</div>
<div class="kpi2" style="margin-top:24px"><div class="bad"><b>57%</b><small>wins needed just to break even</small></div><div class="good"><b>1 : 2+</b><small>the fix: bigger winners</small></div></div>
{foot(3, 'Next →')}""")
# 4 · max drawdown
pages.append(f"""{top()}{kick(3, 'PAIN')}<h2><span class="serif grad">Max drawdown</span></h2>
<div class="card viz" style="padding-bottom:12px"><svg id="dd" width="880" height="380"></svg></div>
<div class="rec"><div><b>−10%</b><i>→ +11%</i></div><div><b>−20%</b><i>→ +25%</i></div><div><b>−30%</b><i>→ +43%</i></div><div class="x"><b>−50%</b><i>→ +100%</i></div></div>
{line('What you need to get back.')}
{foot(4, 'Next →')}""")
# 5 · losing streak
seq = 'WLWWLWLLWWLWLLLLLLLLWWLWWLW'
start = seq.index('LLLLLLLL')
tiles = ''.join(f'<i class="{"w" if c == "W" else "l" + (" hot" if start <= k < start + 8 else "")}">{c}</i>' for k, c in enumerate(seq))
pages.append(f"""{top()}{kick(4, 'SURVIVAL')}<h2>Losing <span class="serif grad">streak</span></h2>
<div class="card viz"><div class="streak">{tiles}</div></div>
<div class="card viz" style="margin-top:20px"><h3>8 losses in a row = <span>example</span></h3>{hb('1% risk', 15, '−7.7%', 'var(--up)', 'var(--strong)')}{hb('2% risk', 30, '−14.9%', '#f59e0b', 'var(--strong)')}{hb('5% risk', 67, '−33.7%', 'var(--down)')}</div>
{foot(5, 'Next →')}""")
# 6 · win rate after 2 losses
pages.append(f"""{top()}{kick(5, 'EMOTION')}<h2>Win rate <span class="serif grad">after 2 losses</span></h2>
<div class="kpi2 tall"><div class="good"><b>55%</b><small>normally</small></div><div class="bad"><b>30%</b><small>after 2 losses</small></div></div>
<div class="res bad" style="margin-top:24px">That gap is <b>revenge</b></div>
{foot(6, 'Next →')}""")
# 7 · plan followed
pages.append(f"""{top()}{kick(6, 'DISCIPLINE')}<h2><span class="serif grad">Plan followed %</span></h2>
<div class="card viz">{hb('Followed', 88, '+$2,940', 'var(--up)')}{hb('Broke rules', 36, '−$1,180', 'var(--down)')}<div class="res">Plan followed <b>78%</b></div></div>
{line('Fix discipline before strategy.')}
{foot(7, 'Your checklist →')}""")
# 8 · checklist + CTA
pages.append(f"""{top()}<h2 style="margin-top:46px">Your weekly<br><span class="serif grad">6-number check.</span></h2>
<ol class="check">
<li><span class="n">1</span><span><b>Profit factor</b> &gt; 1.5</span></li>
<li><span class="n">2</span><span><b>Avg win</b> &gt; avg loss</span></li>
<li><span class="n">3</span><span><b>Drawdown</b> inside limit</span></li>
<li><span class="n">4</span><span><b>Streak</b> survivable ×2</span></li>
<li><span class="n">5</span><span><b>After-loss win rate</b> steady</span></li>
<li><span class="n">6</span><span><b>Plan followed</b> 80%+</span></li>
</ol>
{CTA}<div class="foot" style="margin-top:16px"><div>Save for Sunday 🔖</div>{dots(8)}</div>""")

JS = r'''
function rng(seed){ return () => (seed = (seed * 16807) % 2147483647) / 2147483647; }
// Slide 1: two equity curves that end at the same +$2,400
(function(){ const svg=document.getElementById('eq2'); const W=880,H=540,L=78,R=24,T=20,B=490;
  const n=22, a=[], b=[]; let r=rng(11);
  for(let i=0;i<=n;i++){ const t=i/n; a.push(10000+2400*t+Math.sin(i*1.3)*90*(i%n?1:0)); }
  const shape=[0,600,1300,900,1700,800,-400,-900,-1300,-500,300,1100,1900,800,-200,600,1400,2200,1500,2800,2100,1700,2400];
  shape.forEach(v=>b.push(10000+v));
  const all=[...a,...b], hi=Math.max(...all)+200, lo=Math.min(...all)-200, x=i=>L+i*(W-L-R)/n, y=v=>T+(hi-v)/(hi-lo)*(B-T);
  let s=''; for(let k=0;k<=4;k++){ const v=lo+(hi-lo)*k/4; s+=`<line x1="${L}" x2="${W-R+10}" y1="${y(v)}" y2="${y(v)}" stroke="#eeebf8" stroke-width="1.5"/><text x="${L-12}" y="${y(v)+7}" font-size="18" fill="#8f8ca8" text-anchor="end">${(v/1000).toFixed(1)}k</text>`; }
  s+=`<line x1="${L}" x2="${W-R+10}" y1="${y(10000)}" y2="${y(10000)}" stroke="#c9c4e4" stroke-dasharray="6 6" stroke-width="2"/>`;
  const path=arr=>arr.map((v,i)=>(i?'L':'M')+x(i)+','+y(v)).join(' ');
  s+=`<path d="${path(b)}" fill="none" stroke="#dc2626" stroke-width="5" stroke-linejoin="round"/>`;
  s+=`<path d="${path(a)}" fill="none" stroke="#059669" stroke-width="6" stroke-linejoin="round"/>`;
  const lab=(yy,t1,t2,col)=>`<g transform="translate(${W-R+24},${yy})"><text font-size="24" fill="${col}" font-weight="700" style="font-family:Jakarta">${t1}</text><text y="28" font-size="19" fill="#636080">${t2}</text></g>`;
  const mdd=arr=>{ let pk=arr[0], m=0; arr.forEach(v=>{ pk=Math.max(pk,v); m=Math.min(m,(v-pk)/pk); }); return (m*100).toFixed(1).replace('-','−')+'%'; };
  document.getElementById('mdA').textContent=mdd(a); document.getElementById('mdB').textContent=mdd(b);
  const iMin=b.indexOf(Math.min(...b)); s+=`<circle cx="${x(iMin)}" cy="${y(b[iMin])}" r="12" fill="none" stroke="#dc2626" stroke-width="4"/><g transform="translate(${x(iMin)},${y(b[iMin])+44})"><rect x="-132" y="-22" width="264" height="40" rx="20" fill="#dc2626"/><text y="5" font-size="19" fill="#fff" text-anchor="middle" style="font-family:Jakarta;font-weight:700">−$3,000 from the top</text></g>`;
  s+=`<text x="${L}" y="${H-12}" font-size="18" fill="#8f8ca8">day 1</text><text x="${W-R}" y="${H-12}" font-size="18" fill="#8f8ca8" text-anchor="end">day 22 · both +$2,400</text>`;
  svg.innerHTML=s; })();
// Slide 4: equity curve with the max drawdown shaded
(function(){ const svg=document.getElementById('dd'); const W=880,H=380,L=10,R=10,T=20,B=340;
  const v=[100,104,103,108,112,110,115,118,113,107,104,99,96,98,101,105,103,109,114,117,121]; const n=v.length-1;
  const hi=125, lo=90, x=i=>L+i*(W-L-R)/n, y=p=>T+(hi-p)/(hi-lo)*(B-T);
  const pk=v.indexOf(118), tr=v.indexOf(96); let s='';
  s+=`<rect x="${x(pk)}" y="${y(118)}" width="${x(tr)-x(pk)}" height="${y(96)-y(118)}" fill="#dc2626" opacity=".08" rx="8"/>`;
  s+=`<line x1="${x(pk)}" x2="${W-R}" y1="${y(118)}" y2="${y(118)}" stroke="#dc2626" stroke-dasharray="7 6" stroke-width="2"/><line x1="${x(tr)}" x2="${W-R}" y1="${y(96)}" y2="${y(96)}" stroke="#dc2626" stroke-dasharray="7 6" stroke-width="2"/>`;
  s+=`<path d="${v.map((p,i)=>(i?'L':'M')+x(i)+','+y(p)).join(' ')}" fill="none" stroke="#6541e9" stroke-width="6" stroke-linejoin="round"/>`;
  s+=`<circle cx="${x(pk)}" cy="${y(118)}" r="10" fill="#6541e9"/><circle cx="${x(tr)}" cy="${y(96)}" r="10" fill="#dc2626"/>`;
  s+=`<text x="${x(pk)}" y="${y(118)-18}" font-size="20" fill="#15132a" text-anchor="middle" font-weight="700">peak</text><text x="${x(tr)}" y="${y(96)+34}" font-size="20" fill="#dc2626" text-anchor="middle" font-weight="700">low</text>`;
  s+=`<g transform="translate(${x(tr)+190},${y(96)+30})"><rect x="-150" y="-24" width="300" height="46" rx="23" fill="#dc2626"/><text y="7" font-size="21" fill="#fff" text-anchor="middle" style="font-family:Jakarta;font-weight:700">max drawdown −18.6%</text></g>`;
  s+=`<text x="${W-R}" y="${H-6}" font-size="17" fill="#8f8ca8" text-anchor="end">illustration</text>`;
  svg.innerHTML=s; })();
'''
html = f'''<!doctype html><html><head><meta charset="utf-8"><title>{TAG}</title><style>{css}{extra}</style></head><body><div class="glow"></div>
{''.join(f'<div class="page" id="p{i + 1}">{p}</div>' for i, p in enumerate(pages))}<script>{JS}</script></body></html>'''
(OUT / 'edge-notes-07-light.html').write_text(html)
print('ok')
