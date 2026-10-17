# Builds "journzey — 10 reel scripts" (HTML + PDF) in the brand's Clean Light theme.
import html, json, pathlib
OUT = pathlib.Path(__file__).parent
FONTS = (OUT / '../../../public/assets/fonts').resolve()

P = ['Hook', 'Super hook', 'Body', 'Re-hook', 'Body 2', 'Value', 'Solution + hook', 'CTA']
T = ['0–2s', '2–5s', '5–12s', '12–15s', '15–21s', '21–27s', '27–34s', '34–38s']

# Each row: (voiceover, on-screen text, what to record)
S = [
 dict(n=1, title='"This app stopped my revenge trade"', feature='Tilt alert + cooldown (Home Hub)',
  why='Very relatable pain, so people comment the keyword and send it to trading friends. The red alert at second 0 stops the scroll.',
  rows=[
   ('"This app just stopped my revenge trade."', 'It warned me BEFORE', 'SCREEN: tilt alert popping up on the Home Hub. Add a red flash and a warning sound.'),
   ('"And no, it\'s not a broker setting. It\'s my journal."', 'Not my broker. My journal.', 'FACE: you at the desk, looking into the camera, half-smile.'),
   ('"Two losses in a row. Heart racing. You open a bigger trade to win it back. You know this feeling."', '2 losses… 😤', 'DESK: hand hovering over the mouse, chart full of red, close-up of your eyes.'),
   ('"But here\'s what nobody tells you…"', 'Nobody tells you this 👇', 'TEXT CARD: black screen, white text, a short "boom" sound.'),
   ('"Revenge trades have nothing to do with the market. They happen when emotions are running your account."', 'Emotions ≠ strategy', 'FACE: talking, then a slow push-in.'),
   ('"So use one rule: after 2 losses in a row, stand up and walk away for 20 minutes. No exceptions."', '2 losses = 20-min break', 'DESK: you standing up, walking out of frame. Text animates in.'),
   ('"I built journzey to enforce that. After a losing streak it flags tilt risk and starts a cooldown before you trade again."', 'journzey · tilt alert', 'SCREEN: tilt alert, then zoom on the cooldown timer.'),
   ('"Comment TILT if this is you, and follow. It launches soon."', 'Comment "TILT" 👇', 'FACE: point down at the comments. End card: journzey · coming soon.'),
  ],
  caption='Two losses. Then you size up to "win it back". Sound familiar? 😤\n\nOne rule fixed it for me: 2 losses in a row = stand up and walk away for 20 minutes.\n\nI\'m building journzey to enforce it automatically. Coming soon 🔔\n\nComment TILT if revenge trading has cost you 👇',
  tags='#revengetrading #tradingpsychology #daytrading #forextrader #tradingjournal #buildinpublic',
  pin='Mine was revenge trading after a red morning 😅 What triggers yours?'),

 dict(n=2, title='"You have a danger hour"', feature='Edge Matrix: best window & anti-window',
  why='Curiosity hook ("which hour?") plus a 3-step method people save. It ties in with the danger-hour carousel, so the reel and the post support each other.',
  rows=[
   ('"There\'s one hour of the day that\'s eating your profits."', 'You have a DANGER HOUR', 'SCREEN: candlestick chart with the red "danger hour" band (from your post), zooming in.'),
   ('"And I bet you don\'t know which one."', 'Which one is yours? 👀', 'FACE: raised eyebrow, lean in.'),
   ('"I split my trades by the hour I entered. Mornings: green. But one hour [say your real hour]? Red. Almost every week."', '[your hour] = 🟥', 'SCREEN: your P&L-by-hour view, or the table from the post.'),
   ('"The worst part?"', 'The worst part…', 'TEXT CARD + a short sound stop.'),
   ('"I was trading MORE in that hour, not less. Boredom, volatility, revenge. All in one hour."', 'I traded MORE there', 'DESK: clock on the wall or phone, then hands clicking fast.'),
   ('"Find yours: 2 weeks of writing down entry times, add up P&L for each hour, then cut your size in the worst one."', '1. Log times  2. Add P&L  3. Cut size', 'TEXT: the 3 steps pop in one by one (or use page 2 of the carousel).'),
   ('"Or let journzey find it for you. Edge Matrix shows your best window and your anti-window from your own trades."', 'journzey · Edge Matrix', 'SCREEN: Edge Matrix scrolling to Best window and Anti-window.'),
   ('"Save this for your weekly review. What\'s your worst hour? Comment it."', 'Save 🔖 + comment your hour', 'FACE: point at the save button. End card.'),
  ],
  caption='Every trader has one hour that quietly eats their profits 🕐\n\nFind yours in 3 steps:\n1. Note entry time + result for 2 weeks\n2. Add up P&L per hour\n3. Cut your size in the worst hour\n\njournzey finds it for you automatically. Coming soon 🔔\n\nWhat\'s your worst hour? 👇',
  tags='#tradingtips #daytrading #tradingjournal #forex #tradingpsychology #buildinpublic',
  pin='Mine was right after lunch 🫠 Yours?'),

 dict(n=3, title='"What are the chances you blow your account?"', feature='Blow-up probability radar (Edge Matrix)',
  why='Fear plus a number hooks people. The risk maths is shareable value ("send this to your friend who risks 5%").',
  rows=[
   ('"What\'s the chance you blow your account in the next 14 days?"', 'Chance you blow up: ??%', 'SCREEN: radar animating, number hidden behind a blur.'),
   ('"My journal calculates it. Mine scared me."', 'Mine scared me 😬', 'FACE: serious face. Only say "scared me" if it really did.'),
   ('"Win rate, average R, risk per trade, drawdown. Most traders never put these four together."', 'Win rate · R · risk · drawdown', 'SCREEN: dashboard stats strip, quick zooms on each number.'),
   ('"And here\'s the thing: even a good win rate won\'t save you…"', 'Even 60% win rate won\'t save you', 'TEXT CARD.'),
   ('"…if you risk too much. At 5% per trade, 6 losses in a row cuts your account by about 26%."', '5% risk × 6 losses = −26%', 'TEXT: a balance bar shrinking from 100% to 74%.'),
   ('"At 1% risk, the same 6 losses cost you about 6%. That\'s how you survive a bad week."', '1% risk × 6 losses = −6%', 'TEXT: the same bar barely moving. Green tick.'),
   ('"journzey\'s blow-up radar reads your last 30 days and shows your risk of blowing up in the next 14."', 'journzey · blow-up radar', 'SCREEN: the radar with your real result (or the demo, labelled "sample data").'),
   ('"Comment your risk per trade. Be honest. And follow for launch."', 'Your risk per trade? 👇', 'FACE + end card.'),
  ],
  caption='Even a 60% win rate can blow your account 💥\n\n5% risk × 6 losses in a row ≈ −26%\n1% risk × 6 losses in a row ≈ −6%\n\nSame trader. Same losses. Different survival.\n\njournzey calculates your blow-up risk from your own trades. Coming soon 🔔\n\nWhat do you risk per trade? 👇',
  tags='#riskmanagement #forextrader #propfirm #daytrading #tradingjournal #tradingtips',
  pin='I keep it at 1% now. Used to be way more 😅'),

 dict(n=4, title='"This P&L screenshot is fake"', feature='Public verified P&L link',
  why='"Fake gurus" is a hot topic in trading, so people share it ("send this to someone who believes every screenshot"). Shares are one of the strongest reach signals.',
  rows=[
   ('"This P&L screenshot is fake. I made it in 10 seconds."', 'FAKE. Made in 10 sec.', 'SCREEN: your own demo page, editing a number with the browser\'s "inspect element". Never use someone else\'s account.'),
   ('"Anyone can do this, and that\'s the problem."', 'Anyone can do this', 'FACE: shaking head.'),
   ('"Right-click, inspect, change the number. Instant green day."', 'Right-click → edit → 🤑', 'SCREEN: slow, clear demo of the edit, red becoming green.'),
   ('"So how do you know if ANY screenshot is real?"', 'So what\'s real? 🤔', 'TEXT CARD.'),
   ('"A screenshot is just pixels. A real track record comes from a journal that calculates it live."', 'Pixels ≠ proof', 'FACE.'),
   ('"Before you buy anyone\'s course or signals, ask for a verified track record over months, not a screenshot."', 'Ask for a verified link', 'TEXT: a checklist with ✅ verified link · ❌ screenshot.'),
   ('"That\'s why journzey has verified P&L links. Calculated live from the journal, so they can\'t be edited."', 'journzey · verified P&L', 'SCREEN: the public P&L page (/p/…) with % return and R, demo data labelled.'),
   ('"Send this to a friend who believes every screenshot. Follow for launch."', 'Send to that friend 📤', 'FACE: point to the share icon. End card.'),
  ],
  caption='That green P&L screenshot? Could be 10 seconds of "inspect element" 👀\n\nBefore you pay anyone for signals or a course, ask for a verified track record, not a screenshot.\n\njournzey verified P&L links are calculated live from the journal, so they can\'t be edited. Coming soon 🔔\n\nSend this to a friend who believes every screenshot 📤',
  tags='#tradingtruth #forex #daytrading #tradingjournal #fakegurus #buildinpublic',
  pin='Screenshots lie. Links don\'t. 🔗'),

 dict(n=5, title='"Prop challenges fail on rules, not trades"', feature='Prop firm account with rule presets + live progress',
  why='Prop traders are a huge, very engaged audience. "Comment your prop firm" brings lots of comments, and the trailing-drawdown explainer gets saved.',
  rows=[
   ('"Most prop challenges aren\'t failed on bad trades. They\'re failed on a rule."', 'Failed on a RULE ❌', 'SCREEN: "RULE BREACHED" status on the prop dashboard (demo).'),
   ('"And the trailing drawdown one gets almost everyone."', 'Trailing drawdown 💀', 'FACE: wince.'),
   ('"Daily loss limit. Max drawdown. Minimum trading days. Consistency rule. Four ways to fail with a profitable account."', '4 ways to fail', 'TEXT: 4 cards stacking up.'),
   ('"Here\'s the one people misunderstand…"', 'The tricky one 👇', 'TEXT CARD.'),
   ('"With a trailing drawdown, your limit moves UP when your balance hits a new high. So your room shrinks while you\'re winning."', 'Limit follows your peak ⬆️', 'TEXT: a simple line chart with the limit line stepping up under the equity line.'),
   ('"Tip: write your firm\'s 4 numbers on a sticky note on your monitor: daily loss, max loss, target, minimum days."', '📝 4 numbers on your monitor', 'DESK: you sticking a note on the monitor bezel.'),
   ('"journzey has prop presets. Pick your challenge and it tracks every rule live, with progress bars and a breach warning."', 'journzey · prop tracker', 'SCREEN: Add account → Prop firm → presets → dashboard progress bars.'),
   ('"Comment your prop firm and I\'ll make sure its rules are in. Follow for launch."', 'Your prop firm? 👇', 'FACE + end card.'),
  ],
  caption='You can be profitable AND fail a prop challenge 💀\n\nDaily loss · max drawdown · minimum days · consistency\n\nThe tricky one: a trailing drawdown moves up with your peak balance, so your room shrinks while you\'re winning.\n\njournzey tracks every rule live. Coming soon 🔔\n\nWhich prop firm are you with? 👇',
  tags='#propfirm #fundedtrader #proptrading #forextrader #daytrading #tradingjournal',
  pin='Which firm should I add first? 👇'),

 dict(n=6, title='"Log a trade in 4 taps"', feature='Quick Log + price scroller + needs-details reminder',
  why='A satisfying product demo (the price wheel spinning) that people rewatch. Rewatches add watch time, and the "how do you journal now?" question brings comments.',
  rows=[
   ('"Logging a trade in 4 taps. Watch."', '4 taps ⏱️', 'SCREEN: Quick Log done start to finish in real time, sped up 1.5×.'),
   ('"Because if journaling takes 5 minutes, you\'ll stop doing it by Wednesday."', 'Stopped journaling by Wednesday?', 'FACE: knowing smile.'),
   ('"Pick the instrument. Buy or sell. Lot size. Then scroll the price like a wheel."', 'Instrument → side → lots → price', 'SCREEN: close-up of the price wheel scrolling (phone recording of the wheel with your finger).'),
   ('"But speed isn\'t the best part…"', 'The best part 👇', 'TEXT CARD.'),
   ('"Date, time and session fill in by themselves. And later it reminds me to add my setup and how I felt."', 'Auto time + session · reminder 🔔', 'SCREEN: Trade Log banner "needs details" bell, then click "Complete now".'),
   ('"Journal tip: log within 5 minutes of closing the trade. After that, your memory starts lying to you."', 'Log within 5 minutes', 'TEXT + DESK: closing a trade, then picking up the phone.'),
   ('"That\'s journzey Quick Log. Fast enough that you actually use it."', 'journzey · Quick Log', 'SCREEN: the result card after saving (risk, P&L, R).'),
   ('"How do you journal right now? Excel, notebook or nothing? Comment."', 'Excel? Notebook? Nothing? 👇', 'FACE + end card.'),
  ],
  caption='If journaling takes 5 minutes, you\'ll quit by Wednesday 😅\n\nInstrument → buy/sell → lots → scroll the price. Done. Time and session fill in automatically.\n\nTip: log within 5 minutes of closing; your memory starts lying after that.\n\njournzey Quick Log is coming soon 🔔\n\nHow do you journal now? 👇',
  tags='#tradingjournal #daytrading #forex #tradingtips #buildinpublic #trader',
  pin='I used Excel for a year. Never again 🙃'),

 dict(n=7, title='"Same lot size, 5× the risk"', feature='Risk / position-size calculator',
  why='A surprising fact plus maths people save. Beginners share it with friends. Works without showing much of the app.',
  rows=[
   ('"You\'re probably risking way more than you think."', 'You\'re risking MORE than you think', 'FACE: direct, serious.'),
   ('"Because 0.5 lots on gold is NOT the same as 0.5 lots on EURUSD."', '0.5 gold ≠ 0.5 EURUSD', 'TEXT: two lot tickets side by side.'),
   ('"Gold: 0.5 lots with a $10 stop risks $500. EURUSD: 0.5 lots with a 20-pip stop risks $100."', 'Gold: $500 · EURUSD: $100', 'TEXT: two numbers counting up.'),
   ('"Same lot size…"', 'Same lot size…', 'TEXT CARD.'),
   ('"…five times the risk. That\'s how one gold trade wipes a week of forex profits."', '5× the risk 😳', 'DESK: chart of gold moving fast.'),
   ('"Fix: lots = (account × risk %) ÷ (stop distance × value per point). $10,000 at 1% with a $10 gold stop = 0.10 lots."', '$10k × 1% ÷ ($10 × $100) = 0.10 lots', 'TEXT: the formula building step by step.'),
   ('"journzey\'s risk calculator does this for every instrument in one second, from your own account size."', 'journzey · risk calculator', 'SCREEN: calculator, typing stop distance, lot size appearing.'),
   ('"Save this before your next trade. Follow for launch."', 'Save before your next trade 🔖', 'FACE + end card.'),
  ],
  caption='0.5 lots on gold ≠ 0.5 lots on EURUSD 😳\n\nGold, $10 stop → $500 risk\nEURUSD, 20-pip stop → $100 risk\n\nLot size should come from your stop, not your mood:\nlots = (account × risk%) ÷ (stop × value per point)\n\njournzey calculates it for every instrument. Coming soon 🔔\nSave this 🔖',
  tags='#riskmanagement #positionsizing #gold #xauusd #forex #tradingtips',
  pin='Which pair surprised you the most? 👇'),

 dict(n=8, title='"My journal messages me every Monday"', feature='Weekly brief (Monday & Tuesday mornings)',
  why='Personal and a bit vulnerable ("it said something I didn\'t want to hear"), which keeps people watching. Easy to repeat every week as a series.',
  rows=[
   ('"Every Monday my trading journal sends me a message."', 'My journal texts me 📩', 'SCREEN: the weekly brief popping up on the Home Hub.'),
   ('"And it doesn\'t sugar-coat anything."', 'No sugar-coating', 'FACE: nervous laugh.'),
   ('"Last week\'s result, then three don\'ts and three do\'s, all from my own trades."', '3 don\'ts · 3 do\'s', 'SCREEN: scroll through the brief slowly.'),
   ('"This week it said something I really didn\'t want to hear…"', 'It said THIS 👇', 'TEXT CARD.'),
   ('"[Read one real line from your brief, e.g. a don\'t about an hour, instrument or habit.]"', '[your real line]', 'SCREEN: zoom on that exact line.'),
   ('"Do this even without an app: every Sunday, 10 minutes. Best trade, worst trade, one rule for next week."', 'Sunday: best · worst · 1 rule', 'DESK: notebook, pen, coffee, 10-minute timer.'),
   ('"journzey writes this review for you every Monday, so you start the week with a plan, not a hope."', 'journzey · weekly brief', 'SCREEN: the brief again, then the Home Hub.'),
   ('"Follow. I\'ll share what it tells me every week."', 'Follow for next Monday 🔔', 'FACE + end card. Turn this into a weekly series.'),
  ],
  caption='Every Monday my trading journal tells me the truth 📩\n\nLast week\'s result + 3 don\'ts + 3 do\'s, from my own trades.\n\nNo app? Do it yourself every Sunday: best trade, worst trade, one rule for next week.\n\njournzey is coming soon 🔔\nFollow for next Monday\'s brief.',
  tags='#tradingpsychology #tradingjournal #weeklyreview #daytrading #forex #buildinpublic',
  pin='What would your journal say about your last week? 😅'),

 dict(n=9, title='"Moving your stop loss has a price"', feature='Edge Matrix: last-month audit ("Keep doing" / "Cut out")',
  why='A confession question ("YES or NO") gets very easy comments. The R maths is saveable value.',
  rows=[
   ('"Moving your stop loss is costing you more than bad entries."', 'Moving your stop = 💸', 'SCREEN: dragging a stop loss further away on a chart.'),
   ('"My journal put an exact price on it."', 'It put a PRICE on it', 'SCREEN: Edge Matrix "Cut out: Moving stop loss" chip (your number, or the demo labelled "sample data").'),
   ('"You move it \'just a bit\'. Sometimes price comes back. When it doesn\'t, a 1R loss becomes 2R or 3R."', '1R → 3R 📉', 'DESK: frustrated hand off the mouse.'),
   ('"Here\'s the maths that changed my mind."', 'The maths 👇', 'TEXT CARD.'),
   ('"Win half your trades at 2R and lose 1R: you make half an R per trade. Let your losses grow to 2R and you make zero."', '+0.5R → 0R', 'TEXT: two equity lines, one rising, one flat.'),
   ('"Rule: a stop can only move toward profit, never away. Ever."', 'Stops move ONE way ➡️', 'TEXT + FACE.'),
   ('"journzey\'s monthly audit shows what to keep doing and what to cut out, with the exact amount each habit cost you."', 'journzey · monthly audit', 'SCREEN: Last month audit, Keep doing / Cut out rows.'),
   ('"Be honest: do you move your stop? Comment YES or NO."', 'YES or NO? 👇', 'FACE + end card.'),
  ],
  caption='Moving your stop "just a bit" has a price 💸\n\nWin 50% at 2R, lose 1R → +0.5R per trade\nLet losses grow to 2R → 0R\n\nRule: stops only move toward profit. Never away.\n\njournzey shows exactly what each habit costs you. Coming soon 🔔\n\nDo you move your stop? YES or NO 👇',
  tags='#stoploss #riskmanagement #tradingpsychology #forextrader #daytrading #tradingjournal',
  pin='YES. Used to, all the time 🙋‍♂️'),

 dict(n=10, title='"I\'m posting my worst trading day"', feature='Daily flex card (P&L, emotions, rules, "Verified by journzey" QR)',
  why='Honesty stands out against fake-profit content, so people trust and follow you. Others relate and share their own red days.',
  rows=[
   ('"I\'m posting my worst trading day."', 'My WORST day 🟥', 'SCREEN: your real red flex card sliding in. Only post a real day.'),
   ('"Because hiding losses is exactly why most traders never improve."', 'Hiding losses = no growth', 'FACE: calm, honest.'),
   ('"[Tell the real story in one line: what happened and what you did.]"', '[your story]', 'DESK: replay of the chart from that day, if you have it.'),
   ('"But the money isn\'t the number that hurt most…"', 'This number hurt more 👇', 'TEXT CARD.'),
   ('"Rules followed: [your real number]. I broke my own rules. That\'s the real loss."', 'Rules followed: [x/y]', 'SCREEN: zoom on the rules / emotions line of the card.'),
   ('"Judge your day by rules followed, not by P&L. A red day with every rule followed is a good day."', 'Rules > P&L', 'TEXT.'),
   ('"journzey makes this card at the end of each day: P&L, emotions, rules, and a QR code that proves it\'s real."', 'journzey · verified daily card', 'SCREEN: the card with the QR code, then the "Verified by journzey" badge.'),
   ('"Post your worst day too. Tag me. Let\'s normalise honest trading. Follow for launch."', 'Post yours. Tag me 🤝', 'FACE + end card.'),
  ],
  caption='Posting my worst trading day. On purpose. 🟥\n\nThe money hurt, but breaking my own rules hurt more.\n\nJudge your day by rules followed, not P&L.\n\njournzey makes a verified daily card at the end of each day, wins AND losses. Coming soon 🔔\n\nPost your worst day and tag me. Let\'s normalise honest trading 🤝',
  tags='#tradinglosses #tradingpsychology #honesttrading #daytrading #forex #tradingjournal',
  pin='Red days teach more than green ones. Agree? 👇'),
]

def esc(s): return html.escape(s).replace('\n', '<br>')

css = f'''
@font-face {{ font-family: Jakarta; src: url(file://{FONTS}/plus-jakarta-sans.woff2); font-weight: 200 800; }}
@font-face {{ font-family: InstrSerif; src: url(file://{FONTS}/instrument-serif-italic.woff2); font-style: italic; }}
@font-face {{ font-family: Mono; src: url(file://{FONTS}/jetbrains-mono.woff2); }}
@page {{ size: A4; margin: 14mm 13mm 16mm; }}
:root {{ --text:#2c2a40; --strong:#15132a; --muted:#636080; --faint:#8f8ca8; --line:#e5e1f5; --soft:#f6f5fc;
  --brand:linear-gradient(115deg,#6541e9 0%,#8a5ddc 32%,#b17ace 60%,#d996c1 82%,#feb2b4 100%); }}
* {{ box-sizing:border-box; }} body {{ margin:0; font-family:Jakarta,sans-serif; color:var(--text); font-size:9.2pt; line-height:1.4; -webkit-print-color-adjust:exact; print-color-adjust:exact; }}
.grad {{ background:var(--brand); -webkit-background-clip:text; background-clip:text; color:transparent; }}
.serif {{ font-family:InstrSerif,serif; font-style:italic; font-weight:400; }}
.mono {{ font-family:Mono,monospace; }}
h1 {{ font-size:34pt; line-height:1; letter-spacing:-.04em; color:var(--strong); margin:0 0 8pt; }}
h2 {{ font-size:17pt; letter-spacing:-.03em; color:var(--strong); margin:0 0 6pt; }}
h3 {{ font-size:11pt; color:var(--strong); margin:12pt 0 4pt; }}
.cover {{ height:265mm; display:flex; flex-direction:column; justify-content:center; background:radial-gradient(ellipse 70% 40% at 50% 35%,rgba(101,65,233,.10),transparent 70%),radial-gradient(ellipse 50% 30% at 85% 90%,rgba(254,178,180,.25),transparent 70%); padding:0 6mm; page-break-after:always; }}
.word {{ font-size:22pt; font-weight:600; letter-spacing:-.04em; margin-bottom:28pt; }}
.lead {{ font-size:12pt; color:var(--muted); max-width:150mm; }}
.chips {{ display:flex; flex-wrap:wrap; gap:5pt; margin-top:16pt; }}
.chip {{ font:500 8pt Mono,monospace; border:1px solid rgba(101,65,233,.3); color:#5232d4; border-radius:99px; padding:3pt 8pt; background:#fff; }}
.card {{ border:1px solid var(--line); border-radius:10pt; padding:8pt 11pt; background:#fff; margin:6pt 0; }}
.soft {{ background:var(--soft); }}
ul {{ margin:4pt 0 4pt 14pt; padding:0; }} li {{ margin:2pt 0; }}
table {{ width:100%; border-collapse:collapse; margin:6pt 0; table-layout:fixed; }}
th {{ text-align:left; font:600 7.5pt Mono,monospace; letter-spacing:.06em; text-transform:uppercase; color:var(--faint); padding:5pt 6pt; border-bottom:1.5px solid var(--line); }}
td {{ vertical-align:top; padding:4.6pt 6pt; border-bottom:1px solid #f1eef9; font-size:8.5pt; }}
tr {{ page-break-inside:avoid; }}
td.part {{ width:23mm; }} td.part b {{ display:block; color:var(--strong); font-size:8.8pt; }} td.part span {{ font:500 7.5pt Mono,monospace; color:#6541e9; }}
td.vo {{ width:62mm; color:var(--strong); }} td.ost {{ width:36mm; font-weight:600; color:#5232d4; }} td.shot {{ color:var(--muted); }}
tr.hl td {{ background:#fbf9ff; }}
.script {{ page-break-before:always; }}
.shead {{ display:flex; justify-content:space-between; align-items:flex-start; gap:10pt; border-bottom:2px solid transparent; border-image:var(--brand) 1; padding-bottom:6pt; margin-bottom:6pt; }}
.num {{ font:700 9pt Mono,monospace; color:#fff; background:var(--brand); border-radius:6pt; padding:3pt 7pt; white-space:nowrap; }}
.meta {{ display:grid; grid-template-columns:1fr 1fr; gap:6pt; }}
.lbl {{ font:600 7.5pt Mono,monospace; letter-spacing:.06em; text-transform:uppercase; color:var(--faint); display:block; margin-bottom:2pt; }}
.meta .card {{ font-size:8.6pt; line-height:1.4; }} .cap {{ white-space:normal; }}
.foot {{ color:var(--faint); font-size:8pt; margin-top:8pt; }}
.warn {{ border-left:3px solid #dc2626; }}
'''

def part_rows(rows):
    out = []
    for i, (vo, ost, shot) in enumerate(rows):
        cls = ' class="hl"' if P[i] in ('Hook', 'Re-hook', 'Solution + hook') else ''
        out.append(f'<tr{cls}><td class="part"><b>{P[i]}</b><span>{T[i]}</span></td><td class="vo">{esc(vo)}</td><td class="ost">{esc(ost)}</td><td class="shot">{esc(shot)}</td></tr>')
    return ''.join(out)

body = [f'''
<section class="cover">
  <div class="word grad">journzey</div>
  <h1>10 reel scripts<br><span class="serif grad">built to be watched to the end.</span></h1>
  <p class="lead">Pre-launch content plan. Each reel is 30–40 seconds and follows the same structure:
  hook → super hook → body → re-hook → body → value → solution with a hook → call to action.</p>
  <div class="chips">{''.join(f'<span class="chip">{p}</span>' for p in P)}</div>
  <p class="foot" style="margin-top:30pt">How to use this file: film the shots in the "What to record" column, record the voiceover line by line, add the on-screen text as big captions, and post with the caption at the bottom of each script.</p>
</section>

<section>
  <h2>How Instagram decides what to push</h2>
  <div class="card soft"><ul>
    <li><b>Watch time and completion.</b> The share of people who watch to the end (and rewatch) matters most. That's why every script has a re-hook at 12–15s and an open question that's only answered in the "solution" part.</li>
    <li><b>Sends (shares by DM).</b> Instagram has said sends are one of the strongest signals for reaching non-followers. Scripts 3, 4 and 7 are built to be sent ("send this to a friend who…").</li>
    <li><b>Saves.</b> Useful maths and step lists (scripts 2, 3, 7, 9) get saved, which tells Instagram the reel has lasting value.</li>
    <li><b>Comments, especially early ones.</b> Every CTA asks a one-word or easy question (TILT, YES/NO, your prop firm). Reply to every comment in the first hour.</li>
    <li><b>The first 2 seconds.</b> Instagram tests every reel on a small group first. If they swipe away in 2 seconds, it stops there. Every hook starts on movement plus big text, with no intro and no logo.</li>
    <li><b>Originality.</b> Film your own footage. No TikTok watermarks, no reposted clips.</li>
    <li><b>Keywords.</b> Instagram search reads captions and on-screen text, so say "trading journal", "revenge trading", "prop firm" out loud and write them in the caption.</li>
    <li><b>Trial reels:</b> Instagram can show a reel to non-followers first. Use it to test two hooks of the same script.</li>
  </ul></div>

  <h3>Which journzey features each reel shows</h3>
  <table><thead><tr><th style="width:8mm">#</th><th style="width:62mm">Reel</th><th>Feature</th></tr></thead><tbody>
  {''.join(f'<tr><td class="mono">{s["n"]:02d}</td><td><b>{esc(s["title"])}</b></td><td>{esc(s["feature"])}</td></tr>' for s in S)}
  </tbody></table>

  <h3>Posting plan</h3>
  <div class="card"><ul>
    <li><b>3–4 reels a week</b>, plus 1–2 carousels (Edge Notes) in between. Suggested order: 1, 2, 4, 6, 3, 5, 9, 7, 8, 10. Then repeat 8 ("Monday brief") every Monday as a series.</li>
    <li><b>Time:</b> 7–9 am or 7–9 pm in your audience's time zone, outside busy market hours.</li>
    <li><b>Length 30–40s:</b> long enough for value, short enough to finish. Cut anything that doesn't move the story.</li>
    <li><b>Pinned comment:</b> pin your own answer to the CTA question right after posting.</li>
    <li><b>Pre-launch call to action:</b> "follow for launch" or "DM EARLY". Don't say "free" or "link in bio" until the site is live.</li>
  </ul></div>

</section>

<section class="script">
  <h2>What to record: filming guide</h2>
  <h3>Shot types used in the scripts</h3>
  <table><tbody>
    <tr><td style="width:24mm"><b>FACE</b></td><td>You talking to the camera. Phone on a tripod at eye level, rear camera, 1080p or 4K at 30 fps, window light in front of you (not behind). Frame from chest up, eyes in the top third.</td></tr>
    <tr><td><b>DESK</b></td><td>B-roll of your setup: wide shot of the monitors, hands on keyboard and mouse, close-up of eyes, clicking Buy, standing up and walking away, coffee, notebook, sticky note on the monitor, clock, night lamp. Film each one 5–10 seconds, slowly, a few times.</td></tr>
    <tr><td><b>SCREEN</b></td><td>Real screen recording of the app, not the monitor filmed with a phone. Windows: Win + Alt + R (Xbox Game Bar) or OBS. Mac: Cmd + Shift + 5. Browser zoom 125–150% so text is readable on a phone. Close other tabs and hide your email and real balances.</td></tr>
    <tr><td><b>TEXT CARD</b></td><td>Black or brand-light screen with one bold line, 1–2 seconds, with a short sound ("boom", "whoosh", record scratch). This is the re-hook that resets attention.</td></tr>
  </tbody></table>

  <h3>Film these once and reuse them in every reel</h3>
  <div class="card soft"><ul>
    <li>Tilt alert popping up (Home Hub), several takes.</li>
    <li>Quick Log done in 4 taps on desktop, plus the price wheel scrolled with your finger on a phone.</li>
    <li>Edge Matrix scroll: best window, anti-window, last-month audit, blow-up radar.</li>
    <li>Dashboard charts drawing in, calendar month view, the daily flex card, the public verified P&L page.</li>
    <li>Prop account: Add account → Prop firm → preset → progress bars.</li>
    <li>Risk calculator: typing a stop and the lot size appearing.</li>
    <li>Weekly brief popup (Monday morning).</li>
    <li>A wide "hero" shot of your desk at night: your signature opening and ending shot.</li>
  </ul></div>

  <h3>Audio and editing checklist</h3>
  <div class="card"><ul>
    <li>Use a clip-on mic if possible; otherwise record the voiceover in a quiet, soft room with the phone 15–20 cm from your mouth. Don't read; say each line, then look away.</li>
    <li>Change the shot every 2–3 seconds. Cut on the beat of quiet background music.</li>
    <li>Word-by-word captions in the middle of the screen (CapCut → Auto captions), with the on-screen text from the scripts on top.</li>
    <li>Sound effects on the hook, the re-hook and the solution moment. Silence for half a second before the re-hook works too.</li>
    <li>Export 1080×1920, 30 fps, loudness around −14 LUFS. Finish on a frame that loops back to the first shot.</li>
  </ul></div>

  <h3>Honesty rules (protect the "verified" brand)</h3>
  <div class="card warn"><ul>
    <li>Lines in <b>[brackets]</b> must be your real numbers or story. If something didn't happen to you, change the line or skip the reel.</li>
    <li>Screenshots from the demo journal must show <b>"sample data"</b> on screen.</li>
    <li>The app <b>warns</b> before a revenge trade; it doesn't block trades at the broker. Never promise profits.</li>
  </ul></div>
</section>
''']

for s in S:
    body.append(f'''
<section class="script">
  <div class="shead"><div><h2>{esc(s["title"])}</h2><span class="lbl">Feature: {esc(s["feature"])}</span></div><span class="num">REEL {s["n"]:02d} · 30–40s</span></div>
  <div class="card soft"><span class="lbl">Why Instagram will push it</span>{esc(s["why"])}</div>
  <table><thead><tr><th style="width:23mm">Part</th><th style="width:62mm">Voiceover</th><th style="width:36mm">On-screen text</th><th>What to record</th></tr></thead>
  <tbody>{part_rows(s["rows"])}</tbody></table>
  <div class="meta">
    <div class="card"><span class="lbl">Caption</span><div class="cap">{esc(s["caption"])}</div><div class="mono" style="margin-top:6pt;color:#5232d4;font-size:8pt">{esc(s["tags"])} #journzey</div></div>
    <div class="card"><span class="lbl">Pinned comment</span>{esc(s["pin"])}<span class="lbl" style="margin-top:10pt">Re-hook reminder</span>Add a sound or a hard cut at 12s, and hold the solution shot (27–34s) with a zoom so it feels like the payoff.</div>
  </div>
</section>''')

doc = f'<!doctype html><html><head><meta charset="utf-8"><title>journzey · 10 reel scripts</title><style>{css}</style></head><body>{"".join(body)}</body></html>'
(OUT / 'journzey-10-reel-scripts.html').write_text(doc)
json.dump([{k: v for k, v in s.items()} for s in S], open(OUT / 'scripts.json', 'w'), ensure_ascii=False, indent=1)
print('ok')
