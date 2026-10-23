# Edge Notes 04–06 (light theme): win rate vs R:R, lot-size cheat sheet, trading sessions clock.
import pathlib
OUT = pathlib.Path(__file__).parent
base = (OUT / 'edge-notes-01-v2-light.html').read_text()
css = base[base.index('<style>') + 7:base.index('</style>')]
extra = '''
.lead { font-size: 30px; line-height: 1.35; margin: 0 0 26px; color: var(--text); }
.tbl { margin-top: 34px; padding: 10px 0 6px; overflow: hidden; }
.tbl h3 { margin: 0; padding: 14px 28px 16px; font-size: 24px; color: var(--muted); font-weight: 600; border-bottom: 1.5px solid var(--line); display: flex; justify-content: space-between; }
.tbl h3 span { font: 500 20px Mono, monospace; color: var(--faint); }
.tr { display: grid; align-items: center; padding: 18px 28px; border-bottom: 1.5px solid #f1eef9; font: 600 30px Mono, monospace; color: var(--strong); }
.tr:last-child { border-bottom: 0; }
.tr .bar { height: 18px; border-radius: 9px; background: linear-gradient(90deg, #8a5ddc, #b17ace); }
.tr .muted { color: var(--faint); font-weight: 500; font-size: 24px; }
.tr.hl { background: #f3effe; }
.hdr { font: 600 19px Jakarta, sans-serif !important; letter-spacing: .08em; text-transform: uppercase; color: var(--faint) !important; padding-top: 14px !important; padding-bottom: 10px !important; }
.formula { margin-top: 26px; padding: 22px 28px; border-radius: 22px; background: #fff; border: 1.5px dashed rgb(101 65 233 / .4); font: 600 28px Mono, monospace; color: #5232d4; text-align: center; }
.formula small { display: block; font: 500 20px Jakarta, sans-serif; color: var(--muted); margin-top: 6px; }
.ex { margin-top: 22px; display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; }
.ex div { background: #fff; border: 1.5px solid var(--line); border-radius: 18px; padding: 18px; text-align: center; font: 700 30px Mono, monospace; color: var(--strong); }
.ex div small { display: block; font: 500 18px Jakarta, sans-serif; color: var(--muted); margin-top: 4px; }
.res { margin-top: 18px; padding: 22px 28px; border-radius: 22px; background: #ecfdf5; border: 1.5px solid #a7f3d0; font-size: 30px; font-weight: 700; color: #065f46; display: flex; justify-content: space-between; align-items: center; }
.res b { font: 700 36px Mono, monospace; color: var(--up); }
.tip { margin-top: 22px; font-size: 26px; color: var(--muted); display: flex; gap: 12px; } .tip b { color: var(--strong); }
.quote { margin-top: 22px; padding: 24px 30px; border-radius: 22px; background: #fff; border: 1.5px solid var(--line); box-shadow: 0 8px 24px rgb(60 40 140 / .06); font-family: InstrSerif, serif; font-style: italic; font-size: 40px; line-height: 1.18; color: var(--strong); }
.clock { margin-top: 30px; padding: 26px 26px 18px; }
.lane { display: grid; grid-template-columns: 170px 1fr; align-items: center; gap: 16px; margin: 14px 0; }
.lane b { font-size: 26px; color: var(--strong); } .lane b small { display: block; font: 500 17px Mono, monospace; color: var(--faint); }
.track { position: relative; height: 46px; background: #f3f1fb; border-radius: 12px; }
.seg { position: absolute; top: 0; bottom: 0; border-radius: 12px; display: flex; align-items: center; justify-content: center; font: 600 17px Mono, monospace; color: #fff; }
.ov { position: absolute; top: -8px; bottom: -8px; border: 2.5px dashed #dc2626; border-radius: 12px; background: rgb(220 38 38 / .06); }
.axis { display: grid; grid-template-columns: 170px 1fr; gap: 16px; margin-top: 6px; }
.axis div { position: relative; height: 26px; font: 500 17px Mono, monospace; color: var(--faint); }
.axis span { position: absolute; transform: translateX(-50%); }
.sess { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 4px; }
.sess div { background: #fff; border: 1.5px solid var(--line); border-radius: 20px; padding: 20px 22px; box-shadow: 0 8px 24px rgb(60 40 140 / .05); }
.sess b { display: block; font-size: 28px; color: var(--strong); } .sess i { font: 500 18px Mono, monospace; font-style: normal; color: #5232d4; }
.sess p { margin: 8px 0 0; font-size: 22px; line-height: 1.35; color: var(--muted); }
#p2 h2 { margin-top: 0; }
.page > .top + * { margin-top: auto !important; } .foot { margin-top: auto !important; }
#p2 .lead { font-size: 32px; } #p2 .formula { font-size: 30px; padding: 26px 28px; } #p2 .quote { font-size: 44px; margin-top: 26px; }
#p2 .tr { padding: 22px 28px; } #p2 .ex div { padding: 24px 18px; font-size: 34px; } #p2 .res { padding: 26px 28px; }
#p2 .sess div { padding: 24px 24px; } #p2 .sess p { font-size: 24px; } #p2 .tip { font-size: 28px; }
.clock { position: relative; }
.ovl { position: absolute; top: 12px; bottom: 44px; left: calc(26px + 186px + (100% - 52px - 186px) * 13 / 24); width: calc((100% - 52px - 186px) * 4 / 24); border: 3px dashed #dc2626; border-radius: 14px; background: rgb(220 38 38 / .07); }
.ovl span { position: absolute; top: -20px; left: 50%; transform: translateX(-50%); background: #dc2626; color: #fff; font: 700 15px Jakarta; padding: 4px 10px; border-radius: 99px; white-space: nowrap; }
'''
def wrap(tag, p1, p2):
    return f'''<!doctype html><html><head><meta charset="utf-8"><title>{tag}</title><style>{css}{extra}</style></head><body><div class="glow"></div>
<div class="page" id="p1"><div class="top"><div class="word">journzey</div><div class="tag">{tag}</div></div>{p1}</div>
<div class="page" id="p2" style="display:none"><div class="top"><div class="word">journzey</div><div class="tag">{tag}</div></div>{p2}</div></body></html>'''
CTA = '<div class="cta">journzey is coming soon <span>Follow for launch day 🔔</span></div>'
def foot1(t): return f'<div class="foot"><div class="swipe">{t} <span class="grad" style="font-size:40px">→</span></div><div class="dots"><i class="on"></i><i></i></div></div>'
def foot2(t): return f'<div class="foot" style="margin-top:16px"><div>{t}</div><div class="dots"><i></i><i class="on"></i></div></div>'

# ---- 04 · win rate vs risk:reward ----
rows = [('1 : 1', 50.0), ('1 : 1.5', 40.0), ('1 : 2', 33.3), ('1 : 3', 25.0), ('1 : 4', 20.0)]
tr = ''.join(f'<div class="tr{" hl" if r == "1 : 2" else ""}" style="grid-template-columns:170px 1fr 150px"><span>{r}</span><span><div class="bar" style="width:{w*1.6:.0f}%"></div></span><span style="text-align:right">{w:g}%</span></div>' for r, w in rows)
p1 = f'''<h1 style="font-size:90px">You don't need a<br><span class="serif grad">high win rate.</span></h1>
<p class="sub">You need the right risk:reward. Here's the win rate you need just to break even:</p>
<div class="card tbl"><h3>Risk : reward → breakeven win rate <span>before costs</span></h3>
<div class="tr hdr" style="grid-template-columns:170px 1fr 150px"><span>R:R</span><span></span><span style="text-align:right">Win rate</span></div>{tr}</div>
<div class="formula">Breakeven win rate = 1 ÷ (1 + R)<small>At 1:3 you can lose 3 out of 4 trades and still break even.</small></div>
{foot1('So what should you track?')}'''
p2 = f'''<h2>Track your <span class="serif grad">expectancy.</span></h2>
<p class="lead">Win rate alone lies. Expectancy tells you what one trade is worth, on average.</p>
<div class="formula" style="margin-top:0">E = (win% × avg win) − (loss% × avg loss)</div>
<div class="ex"><div>40%<small>win rate</small></div><div>$200<small>avg win</small></div><div>$100<small>avg loss</small></div></div>
<div class="res">0.4 × 200 − 0.6 × 100 <b>+$20 / trade</b></div>
<div class="tip">📓 <span><b>You can only calculate this if you journal</b> every win and every loss, including the ones you'd rather forget.</span></div>
<div class="quote">"A 40% win rate can make money. A 70% win rate can lose it."</div>
{CTA}{foot2('Save this before you judge your strategy 🔖')}'''
(OUT / 'edge-notes-04-light.html').write_text(wrap('EDGE NOTES · 04', p1, p2))

# ---- 05 · lot-size cheat sheet ----
def rows3(data): return ''.join(f'<div class="tr" style="grid-template-columns:1fr 1fr 1fr"><span>{a}</span><span class="muted">{b}</span><span style="text-align:right;color:#5232d4">{c}</span></div>' for a, b, c in data)
p1 = f'''<h1 style="font-size:86px">Lot size<br><span class="serif grad">cheat sheet.</span></h1>
<p class="sub">$10,000 account · risking 1% ($100) per trade. Save it for your next entry.</p>
<div class="card tbl"><h3>XAUUSD · gold <span>$1 move = $100 per lot</span></h3><div class="tr hdr" style="grid-template-columns:1fr 1fr 1fr"><span>Stop</span><span>Risk</span><span style="text-align:right">Lots</span></div>{rows3([('$5','$100','0.20'),('$10','$100','0.10'),('$20','$100','0.05')])}</div>
<div class="card tbl" style="margin-top:20px"><h3>EURUSD <span>1 pip = $10 per lot</span></h3><div class="tr hdr" style="grid-template-columns:1fr 1fr 1fr"><span>Stop</span><span>Risk</span><span style="text-align:right">Lots</span></div>{rows3([('10 pips','$100','1.00'),('20 pips','$100','0.50'),('50 pips','$100','0.20')])}</div>
<div class="note" style="margin-top:12px">Standard contract sizes. Check your broker's specs; some differ.</div>
{foot1('Why 1% matters')}'''
risk = [('0.5%', 4.9, '#059669'), ('1%', 9.6, '#8a5ddc'), ('2%', 18.3, '#dc2626')]
tr = ''.join(f'<div class="tr" style="grid-template-columns:150px 1fr 150px"><span>{a}</span><span><div class="bar" style="width:{v*4.6:.0f}%;background:{c}"></div></span><span style="text-align:right;color:{c}">−{v}%</span></div>' for a, v, c in risk)
p2 = f'''<h2>10 losses in a row.<br><span class="serif grad">It happens.</span></h2>
<p class="lead">Even good strategies hit losing streaks. Your risk per trade decides how much it hurts:</p>
<div class="card tbl" style="margin-top:0"><h3>Risk per trade → account after 10 straight losses <span>compounded</span></h3>{tr}</div>
<div class="formula" style="font-size:27px">Lots = (account × risk%) ÷ (stop × $ per point)<small>$10,000 × 1% ÷ ($10 stop × $100) = 0.10 lots of gold</small></div>
<div class="quote">"Your stop decides your lot size. Not your mood."</div>
{CTA}{foot2('Send this to a friend who sizes by feel 📤')}'''
(OUT / 'edge-notes-05-light.html').write_text(wrap('EDGE NOTES · 05', p1, p2))

# ---- 06 · trading sessions clock (UTC + IST) ----
def pos(h): return h / 24 * 100
def lane(name, utc, a, b, col):
    segs = []
    if b > a: segs.append((a, b))
    else: segs += [(a, 24), (0, b)]
    s = ''.join(f'<div class="seg" style="left:{pos(x):.2f}%;width:{pos(y)-pos(x):.2f}%;background:{col}">{"" if y-x < 4 else utc}</div>' for x, y in segs)
    return f'<div class="lane"><b>{name}<small>{utc} UTC</small></b><div class="track">{s}</div></div>'
lanes = lane('Sydney', '22–07', 22, 7, '#b17ace') + lane('Tokyo', '00–09', 0, 9, '#d996c1') + lane('London', '08–17', 8, 17, '#8a5ddc') + lane('New York', '13–22', 13, 22, '#6541e9')
axis = ''.join(f'<span style="left:{pos(h):.2f}%">{h:02d}</span>' for h in (0, 4, 8, 12, 16, 20))
p1 = f'''<h1 style="font-size:84px">When the market<br><span class="serif grad">actually moves.</span></h1>
<p class="sub">The 4 forex sessions in one picture. The overlap is where most of the volume is.</p>
<div class="card clock" style="padding-top:34px">{lanes}
<div class="ovl"><span>OVERLAP</span></div>
<div class="axis"><div></div><div>{axis}</div></div></div>
<div class="card tbl" style="margin-top:20px"><div class="tr" style="grid-template-columns:1fr auto"><span>London × New York overlap</span><span style="color:#dc2626">13:00–17:00 UTC</span></div><div class="tr" style="grid-template-columns:1fr auto"><span class="muted" style="font-size:26px">Same overlap in India</span><span style="color:#5232d4">18:30–22:30 IST</span></div></div>
<div class="note" style="margin-top:12px">Winter times. In summer, London &amp; New York open 1 hour earlier in UTC (daylight saving).</div>
{foot1('What each session is like')}'''
p2 = f'''<h2>Know the <span class="serif grad">personality</span><br>of each session.</h2>
<div class="sess">
 <div><b>Asian</b><i>Tokyo / Sydney</i><p>Quieter ranges. JPY, AUD, NZD move most. Ranges often get broken later in London.</p></div>
 <div><b>London</b><i>08:00 UTC open</i><p>Volume arrives fast. EUR, GBP, gold. Many of the day's big moves start here.</p></div>
 <div><b>New York</b><i>13:00 UTC open</i><p>USD news at the open. Gold, indices and USD pairs. Volatile first hour.</p></div>
 <div><b>Overlap</b><i>13:00–17:00 UTC</i><p>The most liquid hours of the day. Tighter spreads, bigger moves.</p></div>
</div>
<div class="tip" style="margin-top:26px">📓 <span><b>Tag every trade with its session.</b> After a month you'll see which one pays you and which one costs you. (journzey fills the session in automatically.)</span></div>
<div class="quote">"Same strategy, different session, different results."</div>
{CTA}{foot2('Save this and set your trading hours 🔖')}'''
(OUT / 'edge-notes-06-light.html').write_text(wrap('EDGE NOTES · 06', p1, p2))
print('ok')
