# Builds "journzey LOOP-20" — the master 20-second reel template (HTML, rendered to PDF with Playwright).
import html, pathlib
OUT = pathlib.Path(__file__).parent
FONTS = (OUT / '../../../public/assets/fonts').resolve()
def esc(s): return html.escape(s).replace('\n', '<br>')

# block: (name, nickname, time, job, visual, on-screen text, voice, audio, edit in Edits)
BLOCKS = [
 ('Thumb stop', 'The flash', '0.0–0.4s', 'Stop the scroll before they think.',
  'Brand flash: 2 frames white → 2 frames violet → 2 frames pink → your first shot, ALREADY moving (never a still or black frame).',
  'None.', 'None.', 'Camera shutter + deep "thump". Same sound in every reel = recognition.',
  'Add a white image clip (0.1s) and two coloured clips (violet #6541e9, pink #feb2b4) before the first shot. No fade.'),
 ('Hook', 'The door', '0.4–2.5s', 'Pain + curiosity + a promise, in one line.',
  'Split screen: LEFT chaos (red P&L, messy chart), RIGHT calm (journal, green). Or one huge number on your real screen.',
  'BIG, centre, max 6 words: "[PAIN NUMBER / CLAIM]". Small on top: "the fix is at the end".',
  'Your raw voice, close to the mic, slightly urgent. Say the same words that are on screen.',
  'Low drone starts under the voice.',
  'Overlay the second clip at 50% width with a thin white line in the middle. Keep the clock or timestamp visible in the shot.'),
 ('Agitate', 'The twist', '2.5–5.5s', 'Kill the wrong belief, so they lean in.',
  '3 jump cuts, 1 second each: red candles · finger clicking buy · you leaning back from the desk.',
  'Changes every second: "Not the [X]." → "Not the [Y]." → "It\'s [Z]." (Z in brand gradient)',
  '"It\'s not your [X]. Not your [Y]. It\'s [Z]."',
  'Fast keyboard taps, one mouse click per cut, short breath before Z.',
  'Each clip slowly zooms from 100% to 108%. On the word Z, a 2-frame glitch or shake.'),
 ('Re-hook 1', 'The open loop', '5.5–7.0s', 'Ask a question you only answer at the end.',
  'Freeze the last frame, blur it, countdown 3·2·1 over it.',
  '"Mine is [X]. What\'s yours?" + small "(how I found it → end)"',
  'Voice drops almost to a whisper.',
  'A clock tick on each number of the countdown.',
  'Freeze frame (or a screenshot of the last frame) + blur. Countdown as 3 text clips of 0.4s.'),
 ('Proof tease', 'Re-hook 2', '7.0–11.0s', 'Show, don\'t tell. Then re-hook.',
  'REAL screen recording: journzey (Edge Matrix, calendar, tilt alert) or your real chart. Slow push-in on the key number.',
  '"[What the screen shows], in [N] days of my trades" → at 10.5s: "but this surprised me…"',
  'Calm, explaining. Speed up on "but this surprised me".',
  'Soft screen-tap sounds. A "whoosh" up on the re-hook line.',
  'Keyframe zoom from 100% to 120% on the number. Add an arrow or circle sticker that pops on.'),
 ('Fix montage', 'The dopamine run', '11.0–16.0s', 'Fast value, no time to scroll away.',
  '4 clips × 1.2s: each shows one step of the fix or one feature.',
  'Word-by-word captions, max 2 words per line: "[Step 1]" → "[Step 2]" → "[Step 3]" → "[Step 4]"',
  'Short, punchy, one line per clip.',
  'Whoosh on every cut. Music bed comes up slightly (keep it low under the voice).',
  'Alternate zoom-in / zoom-out per clip (95%↔108%). Auto-captions, then edit to 2 words per line.'),
 ('Payoff', 'The release', '16.0–18.5s', 'Close the loop from 5.5s. Give the "aha".',
  'Your real result: red line wipes to green, or the before/after number.',
  '"[Result in one line]" + the number in brand gradient. ONLY real numbers, or a clear "example" label.',
  'Slower, confident. Pause one beat before the number.',
  'Beat drop + soft "ding" on the number.',
  'Number counts up (or appears with a scale-pop). Hold 1 second, no cuts.'),
 ('Loop + CTA', 'The trap', '18.5–20.0s', 'One clear action, and a loop back to second 0.',
  'Back to the EXACT first shot, now calm or green.',
  'ONE call to action (rotate weekly): "Comment [WORD]" OR "Save this for [moment]" OR "Send this to the trader who [habit]".',
  'Your last words lead straight into your first words (e.g. ends "…because of this one hour." → starts "This hour cost me…").',
  'Let the music tail run into the start of the reel. No fade-out.',
  'No end card, no fade to black, no logo at the end (it breaks the loop). Your journzey watermark sits small in the top-left corner the whole reel.'),
]

VARIANTS = [
 ('A · VALUE', 'Danger hour', [
  ('Flash → candlestick chart falling fast', '—', '—'),
  ('Split: red P&L chaos / calm green journal', 'ONE HOUR KILLS MY WEEK', '"One hour of the day kills my whole week."'),
  ('Red candles · click · lean back', 'Not strategy. → Not setup. → Timing.', '"It\'s not my strategy. Not my setup. It\'s the time I trade."'),
  ('Freeze + blur, 3·2·1', 'Mine is 3 PM. Yours?', '"Mine is 3 PM. What\'s yours?"'),
  ('journzey Edge Matrix → anti-window highlighted', 'Found it in 14 days → but this surprised me…', '"journzey found it from 14 days of trades… but this surprised me."'),
  ('4 clips: log trade · tag time · see hour · skip hour', 'Log it → Tag time → Find it → Skip it', '"Log every trade. Note the time. Find the hour. Skip it."'),
  ('Red line wipes to green', 'Same strategy. Better week. [your real #]', '"Same strategy. I just stopped trading that hour."'),
  ('First shot, now calm', 'Comment your worst hour 👇', '"Comment your worst hour… because one hour can kill your week."'),
 ]),
 ('B · STORY / MOTIVATION', 'Why I started journaling', [
  ('Flash → you at the desk at night, screen glow', '—', '—'),
  ('Split: blown account / clean journal page', 'I BLEW IT. TWICE.', '"I blew my account. Twice." (only if true)'),
  ('Red screen · hand on face · empty chair', 'Not bad luck. → Not the market. → Me.', '"It wasn\'t bad luck. It wasn\'t the market. It was me."'),
  ('Freeze + blur, 3·2·1', 'What changed? (end)', '"So what changed?"'),
  ('Your journal / journzey screen, first entries', 'I wrote down every trade → then I saw it…', '"I started writing down every trade… then I saw it."'),
  ('4 clips: same mistake · same time · same feeling · new rule', 'Same mistake. → Same hour. → Same feeling. → New rule.', '"Same mistake, same hour, same feeling. So I made one rule."'),
  ('Rule on screen, glowing', '2 losses = walk away', '"Two losses in a row, I walk away. That\'s it."'),
  ('First shot, desk now calm / daylight', 'Save this for your next red day 🔖', '"Save this for your next red day… because I blew it twice."'),
 ]),
 ('C · FEATURE', 'The tilt alert', [
  ('Flash → red alert popping on screen', '—', '—'),
  ('Split: revenge click / journzey alert', 'MY JOURNAL STOPPED ME', '"My journal just stopped my revenge trade."'),
  ('2 losses · heart-rate · finger on BUY', 'Not my broker. → Not willpower. → My journal.', '"Not my broker. Not willpower. My journal."'),
  ('Freeze + blur, 3·2·1', 'How? (end)', '"How? Watch."'),
  ('journzey tilt alert + cooldown timer', '2 losses → it locks me out → but here\'s the best part…', '"Two losses and it locks new trades… but here\'s the best part."'),
  ('4 clips: Quick Log · tilt alert · cooldown · notepad', 'Logs in 10s → Spots tilt → Forces a break → Asks why', '"Logs in ten seconds. Spots tilt. Forces a break. Asks why."'),
  ('Calm notepad entry, green', 'No revenge trade today. ✅', '"No revenge trade today."'),
  ('First shot, alert now green', 'Comment TILT for launch day 🔔', '"Comment TILT… before your journal has to stop you too."'),
 ]),
]

HOOKS = [
 ('Loss reveal', '"This one habit cost me [real amount] last month."'),
 ('Pattern', '"I lose every time I trade at [hour]."'),
 ('Contrarian', '"Your strategy is fine. This is the problem."'),
 ('Callout', '"If you moved your stop today, watch this."'),
 ('Confession', '"I almost quit trading. This kept me in."'),
 ('Question', '"Why do you always lose after a big win?"'),
 ('Challenge', '"Log 10 trades. I\'ll tell you your worst habit."'),
 ('Before / after', '"Same strategy. One change. Different month."'),
 ('Proof', '"My journal found this in 14 days."'),
 ('Feature', '"My journal just stopped my revenge trade."'),
]

CHECK = [
 'Export 1080×1920, 30 fps. First frame already moving; no logo intro.',
 'Captions: bold white with a black outline, max 2 words per line, in the middle of the screen. Keep them out of the bottom ~20% and the right edge, where Instagram\'s buttons and caption sit.',
 'Same signature every reel: the violet→pink flash + shutter sound at 0s, the small journzey watermark top-left, key words in the brand gradient.',
 'Your real voice. Use AI voice only for animated reels.',
 'Real numbers only. Anything made up gets a small "example" label.',
 'Exactly ONE call to action. Rotate comment / save / send across reels.',
 'Last frame = first frame. No fade to black, no end card.',
 'Original audio or trending audio at low volume under your voice.',
 'Upload a custom cover (big 3-word title) so the grid looks like a series.',
 'Reply to every comment in the first hour. Test two hooks with Trial Reels.',
]

css = f'''
@font-face {{ font-family: Jakarta; src: url(file://{FONTS}/plus-jakarta-sans.woff2); font-weight: 200 800; }}
@font-face {{ font-family: InstrSerif; src: url(file://{FONTS}/instrument-serif-italic.woff2); font-style: italic; }}
@font-face {{ font-family: Mono; src: url(file://{FONTS}/jetbrains-mono.woff2); }}
@page {{ size: A4 landscape; margin: 11mm 12mm 12mm; }}
:root {{ --bg:#060613; --panel:#0e0e22; --line:#262448; --text:#dedcec; --strong:#faf8ff; --muted:#9d9abb; --faint:#67648a; --up:#34d399; --down:#f87171;
  --brand:linear-gradient(115deg,#7c5cff 0%,#9d6ce8 32%,#c084d8 60%,#e4a0c8 82%,#feb2b4 100%); }}
* {{ box-sizing:border-box; }} html {{ background:var(--bg); }}
body {{ margin:0; font-family:Jakarta,sans-serif; color:var(--text); font-size:8.6pt; line-height:1.38; background:var(--bg); -webkit-print-color-adjust:exact; print-color-adjust:exact; }}
.grad {{ color:#d29be3; }}
.serif {{ font-family:InstrSerif,serif; font-style:italic; font-weight:400; }}
.mono {{ font-family:Mono,monospace; }}
section {{ page-break-after:always; min-height:186mm; }}
section:last-child {{ page-break-after:auto; }}
h1 {{ font-size:46pt; line-height:.95; letter-spacing:-.045em; color:var(--strong); margin:0 0 10pt; }}
h2 {{ font-size:19pt; letter-spacing:-.03em; color:var(--strong); margin:0 0 8pt; }}
.kick {{ font:600 8pt Mono,monospace; letter-spacing:.16em; color:#c4b5ff; text-transform:uppercase; margin-bottom:8pt; }}
.lead {{ font-size:11pt; color:var(--muted); max-width:170mm; }}
.stats {{ display:flex; gap:8pt; margin-top:14pt; }}
.stat {{ border:1px solid var(--line); background:var(--panel); border-radius:10pt; padding:9pt 14pt; min-width:36mm; }}
.stat b {{ display:block; font:700 18pt Mono,monospace; color:var(--strong); }} .stat span {{ font:500 7pt Mono,monospace; color:var(--muted); letter-spacing:.08em; text-transform:uppercase; }}
.bar {{ display:flex; height:16pt; border-radius:8pt; overflow:hidden; margin:16pt 0 4pt; border:1px solid var(--line); }}
.bar i {{ display:flex; align-items:center; justify-content:center; font:600 6.5pt Mono,monospace; color:#fff; font-style:normal; white-space:nowrap; overflow:hidden; }}
.ticks {{ display:flex; justify-content:space-between; font:500 6.5pt Mono,monospace; color:var(--faint); }}
.card {{ border:1px solid var(--line); background:var(--panel); border-radius:10pt; padding:9pt 12pt; }}
.two {{ display:grid; grid-template-columns:1fr 1fr; gap:10pt; margin-top:12pt; }}
.card ul {{ margin:4pt 0 0 12pt; padding:0; }} .card li {{ margin:2.5pt 0; }}
.lbl {{ display:block; font:600 7pt Mono,monospace; letter-spacing:.1em; text-transform:uppercase; color:var(--faint); margin-bottom:3pt; }}
table {{ width:100%; border-collapse:separate; border-spacing:0; table-layout:fixed; }}
th {{ text-align:left; font:600 6.8pt Mono,monospace; letter-spacing:.08em; text-transform:uppercase; color:var(--faint); padding:5pt 6pt; border-bottom:1.5px solid var(--line); }}
td {{ vertical-align:top; padding:5pt 6pt; border-bottom:1px solid #1b1a36; font-size:7.8pt; }}
tr {{ page-break-inside:avoid; }}
td.blk b {{ display:block; color:var(--strong); font-size:9pt; }} td.blk i {{ display:block; font-style:normal; color:#c4b5ff; font:600 7pt Mono,monospace; margin-top:2pt; }} td.blk span {{ color:var(--muted); font-size:7pt; }}
td.txt {{ color:var(--strong); font-weight:600; }}
tr.hl td {{ background:rgba(124,92,255,.07); }}
table.master td {{ font-size:7.1pt; padding:3.6pt 5pt; line-height:1.3; }} table.master td.blk b {{ font-size:8.3pt; }}
table.var td {{ font-size:9.4pt; padding:7.5pt 7pt; }} table.var td.blk b {{ font-size:10pt; }}
.pill {{ display:inline-block; font:600 6.5pt Mono,monospace; padding:1.5pt 6pt; border-radius:99px; background:rgba(124,92,255,.18); color:#c4b5ff; margin-left:4pt; vertical-align:1pt; }}
.blank td {{ height:13mm; }}
.fix {{ border-left:3px solid var(--up); }} .warn {{ border-left:3px solid var(--down); }}
.hooks {{ display:grid; grid-template-columns:1fr 1fr; gap:6pt; margin-top:8pt; }}
.hooks div {{ border:1px solid var(--line); background:var(--panel); border-radius:9pt; padding:7pt 10pt; font-size:9pt; color:var(--strong); }}
.hooks span {{ display:block; font:600 6.5pt Mono,monospace; color:#c4b5ff; letter-spacing:.08em; text-transform:uppercase; margin-bottom:2pt; }}
ol.chk {{ margin:6pt 0 0 14pt; padding:0; }} ol.chk li {{ margin:4pt 0; font-size:9pt; }}
'''

COLS = ['#ffffff', '#6541e9', '#8a5ddc', '#5b3fd6', '#b17ace', '#c084d8', '#34d399', '#feb2b4']
widths = [0.4, 2.1, 3.0, 1.5, 4.0, 5.0, 2.5, 1.5]
bar = ''.join(f'<i style="width:{w / 20 * 100:.2f}%;background:{c};color:{"#060613" if c in ("#ffffff", "#feb2b4", "#34d399") else "#fff"}">{b[0] if w > 1 else ""}</i>' for w, c, b in zip(widths, COLS, BLOCKS))

rows = ''.join(f'''<tr class="{'hl' if i in (1, 3, 4, 7) else ''}"><td class="blk"><b>{i + 1}. {esc(b[0])}</b><i>{esc(b[2])}</i><span>{esc(b[1])} · {esc(b[3])}</span></td>
<td>{esc(b[4])}</td><td class="txt">{esc(b[5])}</td><td>{esc(b[6])}</td><td>{esc(b[7])}</td><td>{esc(b[8])}</td></tr>''' for i, b in enumerate(BLOCKS))

pages = [f'''
<section style="display:flex;flex-direction:column;justify-content:center">
 <div class="kick">journzey · reel system · v1</div>
 <h1>LOOP-20<br><span class="serif grad">the 20-second reel they can't leave.</span></h1>
 <p class="lead">One master format for every journzey reel: lessons, motivation and features. Same opening, same captions, same loop, so people recognise you in one second. Only the middle changes.</p>
 <div class="stats"><div class="stat"><b>20s</b><span>total length</span></div><div class="stat"><b>8</b><span>blocks</span></div><div class="stat"><b>2</b><span>re-hooks</span></div><div class="stat"><b>1</b><span>open loop</span></div><div class="stat"><b>1</b><span>call to action</span></div></div>
 <div class="bar">{bar}</div>
 <div class="ticks"><span>0s</span><span>2.5</span><span>5.5</span><span>7</span><span>11</span><span>16</span><span>18.5</span><span>20s</span></div>
 <div class="two">
  <div class="card fix"><span class="lbl">Kept from the Meta AI template (it works)</span><ul>
   <li>Pattern interrupt in the first frames, stacked hook, "not X, not Y, it's Z" agitation</li>
   <li>An open loop answered only at the end; a proof tease; a fast montage; payoff; loop ending</li>
   <li>2-word captions; last frame = first frame</li></ul></div>
  <div class="card warn"><span class="lbl">Fixed in this version</span><ul>
   <li><b>32s → 20s.</b> The 10-second montage is now 5 seconds.</li>
   <li><b>No invented numbers.</b> "$847", "99% of traders", "95% completion" were made up. Use your real numbers or an "example" label.</li>
   <li><b>One call to action, not three.</b> Three asks = none get done. Rotate them across reels.</li>
   <li><b>Captions in the safe zone</b> (middle), not the bottom, where Instagram's buttons cover them.</li>
   <li><b>Brand signature</b>: violet→pink flash instead of a generic red/green glitch.</li></ul></div>
 </div>
</section>''',
f'''<section><div class="kick">The master template</div><h2>8 blocks · 20 seconds <span class="serif grad">· fill in the brackets</span></h2>
<table class="master"><thead><tr><th style="width:38mm">Block</th><th style="width:48mm">Visual</th><th style="width:42mm">On-screen text</th><th style="width:36mm">Voice</th><th style="width:32mm">Audio</th><th>How to do it in Edits</th></tr></thead><tbody>{rows}</tbody></table>
<p style="color:var(--faint);font-size:7pt;margin-top:6pt">Highlighted rows are the retention points: hook, open loop, re-hook, loop ending. Timings can stretch ±1s; keep the total under 22s.</p></section>''']

for name, topic, vr in VARIANTS:
    vrows = ''.join(f'<tr class="{"hl" if i in (1, 3, 4, 7) else ""}"><td class="blk"><b>{i + 1}. {esc(BLOCKS[i][0])}</b><i>{esc(BLOCKS[i][2])}</i></td><td>{esc(a)}</td><td class="txt">{esc(b)}</td><td>{esc(c)}</td></tr>' for i, (a, b, c) in enumerate(vr))
    pages.append(f'''<section><div class="kick">Variant {esc(name)}</div><h2>"{esc(topic)}" <span class="serif grad">· filled-in example</span></h2>
<table class="var"><thead><tr><th style="width:38mm">Block</th><th style="width:70mm">What to film / show</th><th style="width:70mm">On-screen text</th><th>Voice</th></tr></thead><tbody>{vrows}</tbody></table>
<p style="color:var(--faint);font-size:7pt;margin-top:6pt">Only say lines that are true for you. Swap any number for your real one.</p></section>''')

blank = ''.join(f'<tr class="blank"><td class="blk"><b>{i + 1}. {esc(b[0])}</b><i>{esc(b[2])}</i><span>{esc(b[3])}</span></td><td></td><td></td><td></td></tr>' for i, b in enumerate(BLOCKS))
pages.append(f'''<section><div class="kick">Blank sheet · print or copy for every new reel</div><h2>Topic: ____________________ <span class="serif grad">· CTA: comment / save / send</span></h2>
<table><thead><tr><th style="width:52mm">Block</th><th>What to film / show</th><th>On-screen text</th><th>Voice</th></tr></thead><tbody>{blank}</tbody></table></section>''')

pages.append(f'''<section><div class="kick">Hook swipe file + upload checklist</div>
<div class="two" style="margin-top:0"><div><h2>10 hooks <span class="serif grad">to rotate</span></h2><div class="hooks">{''.join(f'<div><span>{esc(a)}</span>{esc(b)}</div>' for a, b in HOOKS)}</div>
<p style="color:var(--faint);font-size:7.5pt;margin-top:8pt">Fill the [brackets] with your own real numbers. Test two hooks on the same reel with Trial Reels and keep the winner.</p></div>
<div><h2>Before you post <span class="serif grad">(every time)</span></h2><div class="card"><ol class="chk">{''.join(f'<li>{esc(c)}</li>' for c in CHECK)}</ol></div></div></div></section>''')

doc = f'<!doctype html><html><head><meta charset="utf-8"><title>journzey LOOP-20 reel template</title><style>{css}</style></head><body>{"".join(pages)}</body></html>'
(OUT / 'journzey-loop-20-template.html').write_text(doc)
print('ok')
