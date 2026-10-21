# Builds "journzey — 10 more reel scripts (emotion-first, 30s)" (HTML + PDF source) in the Clean Light theme.
import html, pathlib, importlib.util
OUT = pathlib.Path(__file__).parent
spec = importlib.util.spec_from_file_location('b1', OUT / 'build_scripts.py'); b1 = importlib.util.module_from_spec(spec); spec.loader.exec_module(b1)
css, esc = b1.css, b1.esc

P = ['Hook', 'Body', 'Re-hook', 'Body 2', 'Solution', 'Close']
T = ['0–3s', '3–11s', '11–14s', '14–21s', '21–27s', '27–30s']

S = [
 dict(n=11, title='"Nobody claps for the trade you didn\'t take"', kind='Motivation', emotion='Feeling unseen → pride',
  rows=[
   ('"Nobody will ever clap for the trade you didn\'t take."', 'Nobody claps for this 👏', 'FACE: very close, slow push-in. One second of silence before you speak.'),
   ('"No screenshot. No green number. Just you, closing the chart, when every part of you wanted to click BUY. That\'s the hardest trade there is, and nobody sees it."', 'The invisible trade', 'DESK: hand over the mouse… pulls back. Chart closes.'),
   ('"But here\'s what that invisible trade actually saved you…"', 'What it saved you 👇', 'TEXT CARD, music drops out.'),
   ('"One skipped revenge trade can be the difference between a red day and a blown week. The best traders don\'t win the most. They skip the most bad trades."', 'Pros skip more trades', 'DESK: calm wide shot, you leaning back.'),
   ('"So start counting them. Every night write three numbers: trades taken, trades skipped, rules followed. Watch the second number grow. That\'s real progress."', 'Taken · Skipped · Rules', 'TEXT: three counters ticking up. Or your notebook.'),
   ('"If you skipped a bad trade today, comment \'skipped\'. I see you."', 'Comment "skipped" 🤝', 'FACE: small nod.'),
  ], caption='Nobody claps for the trade you didn\'t take. 👏\n\nNo screenshot. No green number. Just discipline nobody sees.\n\nStart counting it: trades taken · trades skipped · rules followed.\n\nSkipped a bad trade today? Comment "skipped" 🤝',
  tags='#tradingpsychology #tradingdiscipline #daytrading #forextrader #tradingmotivation', pin='Skipped two today. Hardest part of my day 😅'),

 dict(n=12, title='"You don\'t need a new strategy"', kind='Why journaling matters', emotion='Frustration → hope',
  rows=[
   ('"You don\'t need a new strategy. You need a better memory."', 'You don\'t need a new strategy', 'FACE: direct, slightly slower than normal.'),
   ('"You\'ve switched strategies how many times this year? Every loss, you blame the setup. But be honest: do you remember WHY you lost last Tuesday?"', 'Why did you lose last Tuesday?', 'SCREEN: fast scroll through 5 different strategy videos or course pages. Then black.'),
   ('"Here\'s the uncomfortable truth…"', 'The uncomfortable truth', 'TEXT CARD.'),
   ('"Most traders don\'t lose because their strategy is bad. They repeat the same two or three mistakes, and never see them, because they never wrote them down."', 'Same 3 mistakes. On repeat.', 'DESK: same losing moment shown 3 times (loop edit).'),
   ('"Before you buy another course, journal your next 30 trades: entry, exit, reason, emotion. Then read them back. Your edge is hiding in there."', '30 trades. Then read them.', 'DESK: notebook / journal on screen, pen writing.'),
   ('"How many strategies have you tried this year? Be honest."', 'How many? 👇', 'FACE: eyebrow raise.'),
  ], caption='You don\'t need a new strategy. You need a better memory. 🧠\n\nMost losses come from the same 2–3 mistakes, repeated, and invisible until you write them down.\n\nBefore the next course: journal 30 trades. Entry, exit, reason, emotion. Read them back.\n\nHow many strategies have you tried this year? 👇',
  tags='#tradingjournal #tradingstrategy #forex #daytrading #tradingpsychology', pin='I tried 6 in one year. Problem was never the strategy 🙃'),

 dict(n=13, title='"It\'s 2am and you open the chart"', kind='Emotional / discipline', emotion='Loneliness, regret → self-care',
  rows=[
   ('"It\'s 2am. You can\'t sleep. And you open the chart. Just to check."', '2:07 AM', 'DARK ROOM: only the phone screen lighting your face. Real night shot.'),
   ('"Ten minutes later you\'re in a trade you\'d never take at 10am. No plan. No stop. Just the need to feel better after a red day."', 'No plan. No stop.', 'PHONE close-up: thumb tapping Buy. Quick cut.'),
   ('"And you already know how this ends."', 'You know how this ends.', 'TEXT CARD, a heartbeat sound.'),
   ('"A tired brain makes decisions a rested one never would. It\'s not weakness. It\'s just the wrong time to be the one making decisions."', 'Wrong time. Not wrong you.', 'FACE: soft light, calm voice.'),
   ('"Set trading hours like a job. Write them on a sticky note. Outside those hours, the app stays closed. Tonight, your best trade is sleep."', 'Your best trade tonight: sleep 😴', 'DESK: sticky note "Trading hours 9–12" on the monitor. Lamp switches off.'),
   ('"Save this for the next 2am. Send it to the friend who needs it."', 'Send it to them 📤', 'FACE + black end.'),
  ], caption='2am. Can\'t sleep. "Just checking the chart." 📱\n\nA tired brain takes trades a rested one never would.\n\nSet trading hours like a job. Outside them, the app stays closed.\n\nTonight, your best trade is sleep 😴\nSend this to the friend who needs it.',
  tags='#tradingpsychology #traderlife #forextrader #daytrading #mindset', pin='What are your trading hours? Mine are 9–12.'),

 dict(n=14, title='"Lose 50%, need 100%"', kind='Knowledge (risk)', emotion='Shock → control',
  rows=[
   ('"Lose 50% of your account, and you need to make 100% just to get back to where you started."', '−50% needs +100%', 'TEXT: big numbers, a red bar halving.'),
   ('"Lose 10%? You need 11% back. 20%? 25%. 30%? 43%. 50%? A hundred percent."', '−10→11% · −20→25% · −30→43%', 'TEXT: the table building line by line, getting redder.'),
   ('"That\'s why the best traders are obsessed with one thing…"', 'They\'re obsessed with ONE thing', 'TEXT CARD.'),
   ('"Not winning big. Losing small. Every deep drawdown makes the climb back harder and harder."', 'Lose small.', 'FACE: calm, serious.'),
   ('"Cap your risk at around 1% per trade, and set a daily loss limit. When you hit it, you\'re done for the day. Protect the account first. Profits come second."', '1% per trade · daily limit', 'DESK: closing the platform, standing up.'),
   ('"What\'s your daily loss limit? If you don\'t have one, that\'s your answer."', 'Your daily limit? 👇', 'FACE.'),
  ], caption='−10% needs +11%\n−20% needs +25%\n−30% needs +43%\n−50% needs +100% 😳\n\nThe best traders aren\'t obsessed with winning big. They\'re obsessed with losing small.\n\nWhat\'s your daily loss limit? 👇',
  tags='#riskmanagement #forextrader #propfirm #daytrading #tradingtips', pin='Mine: 2% a day and I\'m out.'),

 dict(n=15, title='"The most expensive sentence in trading"', kind='Knowledge + emotion', emotion='Hope → hard truth',
  rows=[
   ('"The most expensive sentence in trading: \'It\'ll come back.\'"', '"It\'ll come back." 💸', 'FACE: whisper the sentence. Hard cut.'),
   ('"You\'re down 20 pips. Then 40. You remove the stop. Just this once. And hope quietly becomes your strategy."', 'Hope = strategy?', 'SCREEN: dragging the stop off the chart, P&L going deeper red.'),
   ('"And sometimes… it DOES come back."', 'Sometimes it does…', 'TEXT CARD, a short pause.'),
   ('"That\'s the trap. The one time it comes back teaches you to do it again. And the time it doesn\'t wipes out a month."', 'That\'s the trap.', 'TEXT: a month of green, one huge red bar.'),
   ('"Decide where you\'re wrong BEFORE you enter. Write it down: if price closes below this level, I\'m out. A stop isn\'t a loss. It\'s the price of being wrong small."', 'Wrong small > wrong big', 'DESK: writing the invalidation level on a sticky note.'),
   ('"Has \'it\'ll come back\' ever cost you? Comment the damage."', 'Comment the damage 😅', 'FACE: wince-smile.'),
  ], caption='The most expensive sentence in trading: "It\'ll come back." 💸\n\nSometimes it does. That\'s the trap. The one time it works teaches you to do it again.\n\nDecide where you\'re wrong before you enter. A stop isn\'t a loss. It\'s the price of being wrong small.\n\nComment the damage 😅',
  tags='#stoploss #tradingpsychology #forex #daytrading #riskmanagement', pin='Mine cost me a whole month once.'),

 dict(n=16, title='"Would you show your family your trades?"', kind='Emotional / why journal', emotion='Shame → honesty',
  rows=[
   ('"Would you show your full trading history to your family?"', 'Would you show them? 👀', 'FACE: very close, quiet voice.'),
   ('"Not the wins. All of it. The revenge trades. The 3am entries. The account you quietly reset and never mentioned."', 'All of it.', 'DESK: phone face down on the table. Closed laptop.'),
   ('"If your answer is no… that\'s okay. But here\'s why it matters."', 'If the answer is no…', 'TEXT CARD.'),
   ('"What we hide, we repeat. Shame keeps losses secret, and secret losses never get fixed."', 'What we hide, we repeat.', 'FACE: steady eye contact.'),
   ('"Start one honest journal. Just for you. Every trade, every feeling. You don\'t have to show anyone. You just have to stop hiding it from yourself."', 'Stop hiding it from yourself', 'DESK: opening a notebook or journal, writing the first line.'),
   ('"Follow if you\'re ready to trade honestly. I\'m on the same journey."', 'Same journey 🤝', 'FACE: small smile.'),
  ], caption='Would you show your full trading history to your family? 👀\n\nWhat we hide, we repeat. Secret losses never get fixed.\n\nStart one honest journal. Just for you. You don\'t have to show anyone. You just have to stop hiding it from yourself.\n\nFollow if you\'re on the same journey 🤝',
  tags='#tradingjournal #tradingpsychology #honesttrading #forex #mindset', pin='Took me a long time to be honest with myself here.'),

 dict(n=17, title='"Profitable on demo, broke on live"', kind='Knowledge (psychology)', emotion='Confusion → understanding',
  rows=[
   ('"You\'re profitable on demo. Then you go live… and everything falls apart."', 'Demo: 🟢 Live: 🔴', 'SPLIT SCREEN: demo account green vs live account red.'),
   ('"Same strategy. Same charts. Same you. So what changed?"', 'So what changed?', 'FACE: confused shrug.'),
   ('"Fear changed."', 'FEAR.', 'TEXT CARD, a heartbeat sound.'),
   ('"On demo, losing is a number. Live, it\'s your rent. So you cut winners early, move stops, hesitate on entries. The strategy didn\'t break. Your emotions joined the trade."', 'Your emotions joined the trade', 'DESK: hand hesitating over the mouse, sweating-palms close-up.'),
   ('"Go live with the smallest size possible, so the money feels small. Before every trade, write one word for how you feel. After 20 trades, you\'ll see which emotion is costing you."', 'Tiny size + one feeling word', 'DESK: writing "anxious" next to a trade.'),
   ('"Demo hero or live hero? Comment which one you are."', 'Demo hero or live hero? 👇', 'FACE.'),
  ], caption='Profitable on demo. Broke on live. 😩\n\nSame strategy. What changed? Fear.\n\nFix: go live with the smallest size, and write one word for how you feel before each trade. After 20 trades, you\'ll see which emotion costs you.\n\nDemo hero or live hero? 👇',
  tags='#forextrader #tradingpsychology #demoaccount #daytrading #tradingtips', pin='Demo hero for 6 months here 🙋‍♂️'),

 dict(n=18, title='"5 losses in a row doesn\'t mean you\'re bad"', kind='Knowledge + motivation', emotion='Self-doubt → relief',
  rows=[
   ('"You lost five trades in a row, and now you think you\'re a terrible trader."', '5 losses in a row 😞', 'FACE: tired, head in hands, then look up at the camera.'),
   ('"Here\'s the maths nobody shows you. With a 50% win rate, over 100 trades, there\'s about an 80% chance you hit at least 5 losses in a row. Even at 60%, it\'s close to a coin flip."', '50% win rate → ~80% chance', 'TEXT: coin flips / a row of red dots appearing.'),
   ('"So the streak isn\'t the problem."', 'The streak isn\'t the problem.', 'TEXT CARD.'),
   ('"What you do DURING the streak is. Bigger size. Faster entries. Switching strategy on loss number four."', 'What you do during it is.', 'DESK: fast cuts, frantic clicking.'),
   ('"Plan for the streak before it comes: same risk on every trade, and a daily stop. Then judge your strategy over 100 trades, not five."', 'Judge 100 trades, not 5', 'TEXT: a 100-trade equity curve with a small dip that recovers (illustration).'),
   ('"Save this for your next losing streak. You\'re not broken. It\'s just maths."', 'You\'re not broken 💜', 'FACE: reassuring smile.'),
  ], caption='Lost 5 in a row? You\'re not a bad trader. 💜\n\nWith a 50% win rate, there\'s about an 80% chance of at least 5 losses in a row somewhere in 100 trades. At 60%, it\'s close to a coin flip.\n\nThe streak isn\'t the problem. What you do during it is.\n\nSave this for your next losing streak.',
  tags='#tradingpsychology #losingstreak #forex #daytrading #tradingmotivation', pin='Longest streak I\'ve had: [your number]. Still here.'),

 dict(n=19, title='"Your phone is full of green screenshots"', kind='Why journal + knowledge', emotion='Ego → learning',
  rows=[
   ('"Your phone is full of green screenshots, and they\'re teaching you nothing."', 'All green. All useless.', 'PHONE: scrolling a gallery of green P&L screenshots.'),
   ('"Wins feel good, so we save them. Losses hurt, so we close the app and forget. But the forgetting is the expensive part."', 'Forgetting is expensive', 'DESK: closing the app angrily, phone face down.'),
   ('"Every loss comes with a receipt."', 'Every loss has a receipt 🧾', 'TEXT CARD: a receipt sliding in.'),
   ('"What setup. What time. What you felt. What rule you broke. That receipt is the cheapest lesson you\'ll ever pay for, but only if you read it."', 'Setup · time · feeling · rule', 'TEXT: receipt lines printing one by one.'),
   ('"Tonight, screenshot your worst trade of the week. Write three lines under it: what happened, what you felt, what you\'ll do differently. Every week."', '3 lines under your worst trade', 'DESK: screenshot + notes app, typing.'),
   ('"Losses are tuition. Read the receipt. Follow for more."', 'Read the receipt.', 'FACE.'),
  ], caption='Your phone is full of green screenshots, and they\'re teaching you nothing 📱\n\nEvery loss comes with a receipt: setup, time, feeling, rule broken.\n\nTonight: screenshot your worst trade of the week and write 3 lines under it. Every week.\n\nLosses are tuition. Read the receipt 🧾',
  tags='#tradingjournal #tradinglosses #forex #daytrading #tradingpsychology', pin='My worst trade this week taught me more than my best one.'),

 dict(n=20, title='"Day 1 vs Day 100 of journaling"', kind='Motivation + soft journzey', emotion='Doubt → belief',
  rows=[
   ('"Day 1 of journaling: I felt stupid. Day 100: I finally understood my own trading."', 'Day 1 → Day 100', 'SPLIT: an old messy notebook page vs a clean dashboard.'),
   ('"Day 1, writing a trade down felt pointless. Day [x]: I noticed [your real pattern]. Day [x]: [your second real pattern]."', '[your real patterns]', 'DESK: flipping notebook pages, then the dashboard.'),
   ('"And then, on day [x]…"', 'And then, day [x]…', 'TEXT CARD.'),
   ('"[Your real breakthrough, e.g. your first week with zero revenge trades, or your first green month. Only say it if it happened.]"', '[your breakthrough]', 'SCREEN: calendar month view, or your real flex card.'),
   ('"Journaling doesn\'t make you profitable overnight. It makes you aware. And awareness compounds. That\'s why I\'m building journzey, to make that first step easy."', 'Awareness compounds', 'FACE: honest, calm. A one-second shot of journzey, no hard sell.'),
   ('"Start your Day 1 today. Comment \'Day 1\' and I\'ll check on you in 30 days."', 'Comment "Day 1" 📓', 'FACE: point at camera.'),
  ], caption='Day 1 of journaling: I felt stupid.\nDay 100: I finally understood my own trading. 📓\n\nJournaling doesn\'t make you profitable overnight. It makes you aware, and awareness compounds.\n\nComment "Day 1" and I\'ll check on you in 30 days 👇',
  tags='#tradingjournal #tradingjourney #buildinpublic #forex #tradingmotivation', pin='Day 1 crew, let\'s go 📓'),
]

def part_rows(rows):
    out = []
    for i, (vo, ost, shot) in enumerate(rows):
        cls = ' class="hl"' if P[i] in ('Hook', 'Re-hook', 'Solution') else ''
        out.append(f'<tr{cls}><td class="part"><b>{P[i]}</b><span>{T[i]}</span></td><td class="vo">{esc(vo)}</td><td class="ost">{esc(ost)}</td><td class="shot">{esc(shot)}</td></tr>')
    return ''.join(out)

body = [f'''
<section class="cover">
  <div class="word grad">journzey</div>
  <h1>10 more reel scripts<br><span class="serif grad">that make traders stop and listen.</span></h1>
  <p class="lead">Emotion first, about 30 seconds each. A mix of trading knowledge, motivation and why journaling matters, written to feel like a conversation, not an advert.</p>
  <div class="chips">{''.join(f'<span class="chip">{p}</span>' for p in P)}</div>
</section>

<section>
  <h2>Why the first reel lost people in 2–3 seconds</h2>
  <div class="card warn"><ul>
    <li><b>It started with you ("how I built it"), not with them.</b> A stranger doesn't care about your app yet. They care about their own pain. Every script here opens on the viewer's feeling: "You lost five in a row…", "It's 2am…".</li>
    <li><b>Nothing happened in the first second.</b> A calm desk shot with a title looks like an intro, and intros get skipped. Start mid-sentence, on a face or on movement, with big text.</li>
    <li><b>The hook gave no tension.</b> The best hooks open a loop the viewer needs closed: a shocking number, a confession, or a question about them.</li>
    <li><b>Format:</b> landscape footage with black bars and quiet audio. Film vertical and keep the voice loud and close (see the first script file for the full filming guide).</li>
  </ul></div>

  <h2 style="margin-top:14pt">How to deliver the voiceover so people keep listening</h2>
  <div class="card soft"><ul>
    <li><b>Talk to ONE person.</b> Say "you", never "guys" or "everyone". Imagine one friend who just blew their account.</li>
    <li><b>The hook is slower than the rest.</b> Say the first line slowly and clearly, with a tiny pause before the key word ("…and you open the chart.").</li>
    <li><b>Pause before every re-hook</b> (half a second of silence). Silence makes people look up.</li>
    <li><b>Lean on the words in CAPITALS</b> in the scripts. Change your pitch on them.</li>
    <li><b>Emotional lines: quieter and lower.</b> Knowledge lines: confident and a bit faster.</li>
    <li><b>Short sentences only.</b> If a sentence needs a breath in the middle, cut it in two.</li>
    <li><b>Record the hook 3–5 times</b> and keep the most natural one. Don't read: look at the line, look away, say it.</li>
    <li><b>Mix:</b> post these between the feature reels (first file) so the account is about traders first and the app second.</li>
  </ul></div>

  <h3>The 10 scripts</h3>
  <table><thead><tr><th style="width:8mm">#</th><th style="width:70mm">Reel</th><th style="width:38mm">Type</th><th>Emotion</th></tr></thead><tbody>
  {''.join(f'<tr><td class="mono">{s["n"]}</td><td><b>{esc(s["title"])}</b></td><td>{esc(s["kind"])}</td><td>{esc(s["emotion"])}</td></tr>' for s in S)}
  </tbody></table>
  <div class="card warn"><b>Honesty:</b> lines in [brackets] must be your real story or numbers. The losing-streak figures in reel 18 are calculated (probability of at least 5 losses in a row in 100 independent trades: ≈81% at a 50% win rate, ≈46% at 60%). The drawdown numbers in reel 14 are exact (recovery needed = 1 ÷ (1 − loss) − 1).</div>
</section>
''']
for s in S:
    body.append(f'''
<section class="script">
  <div class="shead"><div><h2>{esc(s["title"])}</h2><span class="lbl">{esc(s["kind"])} · Emotion: {esc(s["emotion"])}</span></div><span class="num">REEL {s["n"]} · ~30s</span></div>
  <table><thead><tr><th style="width:23mm">Part</th><th style="width:62mm">Voiceover</th><th style="width:36mm">On-screen text</th><th>What to record</th></tr></thead>
  <tbody>{part_rows(s["rows"])}</tbody></table>
  <div class="meta">
    <div class="card"><span class="lbl">Caption</span><div class="cap">{esc(s["caption"])}</div><div class="mono" style="margin-top:6pt;color:#5232d4;font-size:8pt">{esc(s["tags"])} #journzey</div></div>
    <div class="card"><span class="lbl">Pinned comment</span>{esc(s["pin"])}<span class="lbl" style="margin-top:10pt">Delivery note</span>Slow, clear hook. Half a second of silence before the re-hook. Quieter on the emotional lines, confident on the solution.</div>
  </div>
</section>''')
doc = f'<!doctype html><html><head><meta charset="utf-8"><title>journzey · 10 more reel scripts</title><style>{css}</style></head><body>{"".join(body)}</body></html>'
(OUT / 'journzey-10-more-reel-scripts.html').write_text(doc)
print('ok')
