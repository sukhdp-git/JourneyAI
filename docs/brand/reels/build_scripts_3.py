# Builds "journzey — 8 viral reel scripts (v3, 9–14s)" (HTML + PDF source) in the same Clean Light theme as the earlier script files.
import pathlib, importlib.util
OUT = pathlib.Path(__file__).parent
spec = importlib.util.spec_from_file_location('b1', OUT / 'build_scripts.py'); b1 = importlib.util.module_from_spec(spec); spec.loader.exec_module(b1)
css, esc = b1.css, b1.esc

STEPS = ['Hook', 'Pattern interrupt', 'Curiosity loop', 'Visual story', 'Retention', 'Emotion', 'Payoff', 'Share moment', 'Repeatable format']
END = 'journzey is coming soon · Follow for launch day 🔔'
HL = ('Hook', 'Payoff')

# rows: (time, beat, voice / on-screen text, what to record)
S = [
 dict(n=1, title='"Don\'t send this to your trading friend."', fmt='Reverse psychology + share trigger', sec=11, emotion='Recognition → laughter',
  rows=[
   ('0–1s', 'Hook', 'TEXT + VO: "Don\'t send this to your trading friend."', 'FACE to camera, deadpan. Big text already on screen in frame 1.'),
   ('1–3s', 'Pattern interrupt', '"He\'ll know it\'s about him."', 'HARD CUT to a phone screen: 3 red trades inside 9 minutes.'),
   ('3–6s', 'Visual story', '"Down two. \'I\'ll just make it back.\' Size doubles."', 'Lot-size field 0.50 → 1.00 → 2.00, one jump on each beat.'),
   ('6–9s', 'Emotion', '"Every single time."', 'journzey Mind tab with the revenge pattern flagged. Label: Demo account.'),
   ('9–11s', 'Payoff', '"…okay. Send it."', 'FACE again, tiny smile. End card.'),
  ], why='Everyone knows that friend. Telling people "don\'t" makes them send it.',
  caption='Don\'t send this to your trading friend. 👀\n\nDown two → "I\'ll just make it back" → size doubles.\n\nEvery. Single. Time.\n\n…okay. Send it.', tags='#tradingpsychology #revengetrading #daytrading #forextrader #tradingmemes',
  pin='Tag the friend. You know the one 😅'),

 dict(n=2, title='"You were about to skip this."', fmt='Meta pattern interrupt', sec=12, emotion='Caught out → pride',
  rows=[
   ('0–1s', 'Hook', 'TEXT + VO: "You were about to skip this."', 'A thumb mid-swipe on this reel. FREEZE FRAME.'),
   ('1–3s', 'Curiosity loop', '"That itch? It\'s the same one that clicks Buy."', 'Slow zoom in on the thumb.'),
   ('3–7s', 'Retention', '(no voice, 3 sharp sounds)', 'MATCH CUTS: thumb swipes → thumb taps Buy → red number. Repeat 3×, faster each time.'),
   ('7–10s', 'Emotion', '"Impulse doesn\'t care which app you\'re in."', 'journzey beep + red banner: daily loss limit hit.'),
   ('10–12s', 'Payoff', 'TEXT: "You didn\'t skip. That\'s discipline."', 'Black screen, white text. End card.'),
  ], why='It calls out the exact behaviour happening right now, then rewards the viewer for staying.',
  caption='You were about to skip this. 👆\n\nThat itch is the same one that clicks Buy without a setup.\n\nYou didn\'t skip. That\'s discipline.', tags='#tradingdiscipline #tradingpsychology #daytrading #forex #mindset',
  pin='Made it to the end? Comment "stayed" 🧘'),

 dict(n=3, title='"Guess which trade lost the most."', fmt='Game + comments', sec=13, emotion='Curiosity → realisation',
  rows=[
   ('0–1s', 'Hook', 'TEXT + VO: "Guess which one lost the most."', '3 trade cards slam in: A, B, C.'),
   ('1–3s', 'Curiosity loop', '"Comment A, B or C. Now."', 'Cards show entry, lots, time. Emotion tag BLURRED.'),
   ('3–7s', 'Retention', '"Not A… not B…"', 'Drumroll. A flips (small loss). B flips (win).'),
   ('7–10s', 'Payoff', '"The one you took because nothing was happening."', 'C flips: biggest loss. Emotion tag unblurs: "bored".'),
   ('10–13s', 'Emotion', '"Your journal knows your most expensive feeling."', 'journzey emotion breakdown. Label: Demo account. End card.'),
  ], why='People stay to check their guess and comment before the reveal. Comments push reach.',
  caption='Guess which trade lost the most. A, B or C? 👇\n\nIt wasn\'t the scary one.\nIt was the bored one.\n\nYour journal knows your most expensive feeling.', tags='#tradingjournal #tradingpsychology #daytrading #forextrader #tradingtips',
  pin='Answer: C. Boredom trades hurt the most in this journal 😬'),

 dict(n=4, title='"POV: your trading account can text you."', fmt='iMessage chat (read in real time)', sec=13, emotion='Laughter → gut punch',
  rows=[
   ('0–1s', 'Hook', 'TEXT: "POV: your trading account can text you" · "hey"', 'Chat screen, contact "My Account 💀". Phone vibration sound on each message.'),
   ('1–3s', 'Curiosity loop', '"you up?" · "please don\'t open the chart"', 'Messages pop in one by one.'),
   ('3–5s', 'Pattern interrupt', '(you are typing… stops) · "you opened the chart"', 'Typing dots appear, then disappear.'),
   ('5–8s', 'Emotion', '"…" · "it\'s ok. I\'m not mad."', 'Slower pacing. Let it sit.'),
   ('8–10s', 'Payoff', '"I just have 23 days left."', 'The message lands. One beat of silence.'),
   ('10–13s', 'Share moment', '—', 'CUT to the journzey Death Clock ticking. Label: Demo account. End card.'),
  ], why='Funny for 8 seconds, then it hits. People send it with "💀 me".',
  caption='POV: your trading account can text you 📱💀\n\n"please don\'t open the chart"\n"you opened the chart"\n"I just have 23 days left."\n\nSend this to someone whose account is texting them.', tags='#tradingmemes #traderlife #forex #daytrading #tradingpsychology',
  pin='Mine would\'ve blocked me years ago 💀'),

 dict(n=5, title='"Every blown account has one moment."', fmt='Reverse playback', sec=12, emotion='Dread → clarity',
  rows=[
   ('0–1s', 'Hook', 'TEXT + VO: "Every blown account has one moment."', 'Balance on screen: $0.00.'),
   ('1–6s', 'Visual story', '"Let\'s rewind."', 'VHS REWIND effect: balance climbs back, trades un-close, red turns green. Speed ramps up.'),
   ('6–8s', 'Payoff', '"Here."', 'HARD STOP. Freeze on Lots 0.5 → 2.0. A red circle draws itself around it.'),
   ('8–10s', 'Emotion', '"Not the market. This click."', '1 second of silence first.'),
   ('10–12s', 'Share moment', '"Find yours before it finds you."', 'journzey trade log highlighting the oversized trade. End card.'),
  ], why='The rewind makes people need to know where it stops.',
  caption='Every blown account has one moment. ⏪\n\nNot the market.\nNot the news.\nOne click.\n\nFind yours before it finds you.', tags='#riskmanagement #tradingpsychology #forextrader #propfirm #daytrading',
  pin='Mine was doubling size after a loss. What was yours?'),

 dict(n=6, title='"Things traders will never say out loud."', fmt='Dark list → safe place', sec=14, emotion='Shame → relief',
  rows=[
   ('0–1s', 'Hook', 'TEXT: "Things traders will never say out loud."', 'Black screen. Typewriter text, soft typing sound, slow piano note.'),
   ('1–3s', 'Curiosity loop', '"I lied to my friend about my P&L."', 'Each line ~1.5s.'),
   ('3–5s', 'Retention', '"I deposited again. Third time this month."', 'Same style. Keep the rhythm.'),
   ('5–7s', 'Retention', '"I\'ve never used a stop loss."', 'Same style.'),
   ('7–9s', 'Emotion', '"I trade so I don\'t have to think about my life."', 'Hold this line a little longer. Then empty screen for a beat.'),
   ('9–12s', 'Payoff', '"Now there\'s a place to say it. Anonymously."', 'journzey Confessions feed scrolls: anonymous handles, likes as "$15 respect". Label: Demo account.'),
   ('12–14s', 'Share moment', '—', 'End card.'),
  ], why='Line 4 hits hard. Viewers save it and send it privately ("this is you").',
  caption='Things traders will never say out loud. 🤐\n\nNow there\'s a place to say them. Anonymously.', tags='#tradingpsychology #traderlife #forex #daytrading #mentalhealth',
  pin='Which one hit closest? You don\'t have to say it here 🤍'),

 dict(n=7, title='"Rate this trader, 1 to 10."', fmt='Expectation flip', sec=10, emotion='Admiration → shock',
  rows=[
   ('0–1s', 'Hook', 'TEXT + VO: "Rate this trader. 1 to 10."', 'Big green +$2,000 today.'),
   ('1–3s', 'Pattern interrupt', '—', 'Comment bubbles pop up: "10", "10 🔥", "teach me" (on-screen graphics, not real comments).'),
   ('3–6s', 'Payoff', '"He risked 80% of his account for it."', 'ZOOM OUT: risk shown = 80% of the account on one trade.'),
   ('6–8s', 'Emotion', '"He\'s a 1. He just doesn\'t know yet."', 'All the bubbles flip to "1".'),
   ('8–10s', 'Share moment', '"Your journal rates the process, not the P&L."', 'journzey risk-per-trade view. End card.'),
  ], why='The reversal starts arguments in the comments.',
  caption='Rate this trader. 1 to 10. 🤔\n\n+$2,000 today.\n80% of the account on one trade.\n\nStill a 10?', tags='#riskmanagement #forextrader #daytrading #tradingtips #propfirm',
  pin='Process > P&L. Every time.'),

 dict(n=8, title='"This video never ends." (loop)', fmt='Seamless loop', sec=9, emotion='Anxiety → control',
  rows=[
   ('0–1s', 'Hook', 'TEXT + VO: "Lose."', 'Text on screen in frame 1.'),
   ('1–2s', 'Retention', '"Feel it."', 'Red candle.'),
   ('2–3s', 'Retention', '"\'Just one more.\'"', 'Hand on mouse.'),
   ('3–5s', 'Emotion', '"Lose. Feel it. One more."', 'Bigger red candle. Faster pace.'),
   ('5–7s', 'Pattern interrupt', '"Lose. Feel it. One more. Lose—"', 'Faster again. Screen shakes.'),
   ('7–9s', 'Payoff', 'TEXT: "Log it. Break it."', 'Hand slams down a phone with the journzey Log Trade screen. Silence. End card fades straight back into "Lose."'),
  ], why='The end flows into the start, so people replay it 2–3 times without noticing. The rhythm is what revenge trading feels like.',
  caption='Lose. Feel it. Just one more. Lose. 🔁\n\nThe loop doesn\'t break by itself.\n\nLog it. Break it.', tags='#revengetrading #tradingpsychology #daytrading #forex #tradingdiscipline',
  pin='Watched it twice, didn\'t you? 🔁'),
]

FORMATS = [
 ('Don\'t send this', 'Forbidden instruction → callout → payoff → "okay, send it"', '"Don\'t show this to your wife" · "Don\'t watch this before market open"'),
 ('Guess which', '3 choices → comment → reveal → lesson', '"Guess which session lost the most" · "Guess which emotion"'),
 ('POV chat', 'An object texts you → funny → punch', 'Your stop loss texts you · your strategy texts you'),
 ('Rewind', 'End state → rewind → freeze on the moment', 'The day before a prop-firm fail · the trade before a streak broke'),
 ('Never say out loud', 'Dark list → hardest line → safe place', '"Things your trading group never posts"'),
 ('Rate this', 'Show the result → crowd reacts → hidden cost', 'Rate this win rate · rate this strategy'),
 ('Loop', 'Rhythmic repetition → break', '"Buy. Hope. Pray. Close."'),
]

def rows_html(rows):
    out = []
    for t, beat, vo, shot in rows:
        cls = ' class="hl"' if beat in HL else ''
        out.append(f'<tr{cls}><td class="part"><b>{esc(beat)}</b><span>{t}</span></td><td class="vo">{esc(vo)}</td><td class="shot">{esc(shot)}</td></tr>')
    return ''.join(out)

extra = '''
td.vo { width:72mm; } .sec { font:600 8pt Mono,monospace; color:#5232d4; }
.why { border-left:3px solid #6541e9; }
.end { text-align:center; font-weight:700; color:var(--strong); background:var(--soft); }
.steps { counter-reset:s; display:grid; grid-template-columns:1fr 1fr 1fr; gap:5pt; margin:6pt 0; }
.steps div { border:1px solid var(--line); border-radius:8pt; padding:6pt 8pt; background:#fff; font-size:8.4pt; }
.steps b { display:block; color:var(--strong); }
.steps b::before { counter-increment:s; content:counter(s) ". "; color:#6541e9; font-family:Mono,monospace; }
'''
STEPDESC = ['Stops the scroll in frame 1', 'Breaks what viewers expect', 'Gives a reason to stay', 'Shows instead of tells', 'No boring second', 'Makes them feel something', 'Rewards their attention', 'A moment worth sending', 'A structure you can repeat']

body = [f'''
<section class="cover">
  <div class="word grad">journzey</div>
  <h1>8 viral reel scripts<br><span class="serif grad">that stop the scroll in one second.</span></h1>
  <p class="lead">9 to 14 seconds each. Every script follows the same 9-step viral structure, written to feel raw, real and curious, not like an advert.</p>
  <div class="chips">{''.join(f'<span class="chip">{p}</span>' for p in STEPS)}</div>
  <p class="foot" style="margin-top:30pt">How to use this file: film the shots in the "What to record" column, record the voice lines, put the TEXT lines on screen as big captions, and post with the caption at the bottom of each script. Every reel ends on the same card: {esc(END)}</p>
</section>

<section>
  <h2>Why the last reels lost people at second 3</h2>
  <div class="card warn"><ul>
    <li><b>The hook started too late.</b> Frame 1 must already be moving and already have text on it. No logo, no "hey guys", no slow zoom.</li>
    <li><b>The hook explained instead of opening a question.</b> The first line should make them think "wait, what?", not "okay, I get it".</li>
    <li><b>The first cut came after 2+ seconds.</b> Cut, or change something on screen, every 0.8–1.5 seconds.</li>
    <li><b>The payoff was guessable.</b> If they can predict the ending, they leave. Hold the answer until the last 2 seconds.</li>
  </ul></div>

  <h2 style="margin-top:14pt">Hook rules (the first second)</h2>
  <div class="card soft"><ul>
    <li>On-screen text is <b>6 words or fewer</b>, big, in the centre of the top third.</li>
    <li><b>Something moves in frame 1:</b> a thumb, a number or a hard cut.</li>
    <li><b>Voice and text say the same thing.</b> Many people watch muted.</li>
    <li>Use one hook type: <b>contradiction, direct accusation, a question they can answer, a forbidden instruction, or a POV</b>.</li>
  </ul></div>

  <h3>The 9-step structure</h3>
  <div class="steps">{''.join(f'<div><b>{s}</b>{d}</div>' for s, d in zip(STEPS, STEPDESC))}</div>

  <h3>The 8 scripts</h3>
  <table><thead><tr><th style="width:8mm">#</th><th style="width:72mm">Reel</th><th style="width:48mm">Format</th><th>Length</th></tr></thead><tbody>
  {''.join(f'<tr><td class="mono">{s["n"]}</td><td><b>{esc(s["title"])}</b></td><td>{esc(s["fmt"])}</td><td class="mono">{s["sec"]}s</td></tr>' for s in S)}
  </tbody></table>
  <div class="card warn"><b>Honesty:</b> any trade numbers on screen must carry a small "Demo account" label. The lines in reel 6 are relatable writing, not quotes from real users, so never present them as testimonials. The comment bubbles in reel 7 are graphics, not real comments.</div>
</section>
''']
for s in S:
    body.append(f'''
<section class="script">
  <div class="shead"><div><h2>{esc(s["title"])}</h2><span class="lbl">{esc(s["fmt"])} · Emotion: {esc(s["emotion"])}</span></div><span class="num">REEL {s["n"]} · {s["sec"]}s</span></div>
  <table><thead><tr><th style="width:28mm">Beat</th><th style="width:72mm">Voice / on-screen text</th><th>What to record</th></tr></thead>
  <tbody>{rows_html(s["rows"])}</tbody></table>
  <div class="card end">{esc(END)}</div>
  <div class="card why"><span class="lbl">Why it works</span>{esc(s["why"])}</div>
  <div class="meta">
    <div class="card"><span class="lbl">Caption</span><div class="cap">{esc(s["caption"])}<br><br>{esc(END)}</div><div class="mono" style="margin-top:6pt;color:#5232d4;font-size:8pt">{esc(s["tags"])} #journzey</div></div>
    <div class="card"><span class="lbl">Pinned comment</span>{esc(s["pin"])}<span class="lbl" style="margin-top:10pt">Delivery note</span>Say the hook fast and sharp; text is on screen in frame 1. Cut every 1 second. Hold silence before the payoff line.</div>
  </div>
</section>''')

body.append(f'''
<section class="script">
  <h2>Formats to recreate again and again</h2>
  <p class="lead" style="font-size:10pt">Keep the structure, swap the topic. Post one format, check in Insights which one keeps people past second 3, then make 3 more in that format.</p>
  <table><thead><tr><th style="width:34mm">Format</th><th style="width:70mm">Structure</th><th>Next versions</th></tr></thead><tbody>
  {''.join(f'<tr><td><b>{esc(a)}</b></td><td>{esc(b)}</td><td>{esc(c)}</td></tr>' for a, b, c in FORMATS)}
  </tbody></table>
  <h3>Post these first</h3>
  <div class="card soft"><ul>
    <li><b>Reel 4 (POV chat)</b>: people read it in real time, so it holds attention longest.</li>
    <li><b>Reel 1 (Don't send this)</b>: the easiest one to share.</li>
  </ul></div>
</section>''')

doc = f'<!doctype html><html><head><meta charset="utf-8"><title>journzey · 8 viral reel scripts</title><style>{css}{extra}</style></head><body>{"".join(body)}</body></html>'
(OUT / 'journzey-8-viral-reel-scripts.html').write_text(doc)
print('ok')
