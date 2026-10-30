# journzey site B-roll (2 × 4 s)

Real recordings of the journzey terminal (Demo Journal, sample data), phone size, 1080×1920, 30 fps, H.264 + AAC.
Every second is its own shot with its own sound effect. Cut at **1.0 / 2.0 / 3.0 s** to get eight 1-second clips (keyframe every second).

| File | 0–1 s | 1–2 s | 2–3 s | 3–4 s |
|---|---|---|---|---|
| `journzey-broll-1-the-problem.mp4` | Death Clock ticking (tick sound) | Blow-up probability radar 42.3% (alarm) | Boss fight: The Revenge Demon (hit) | Mind roast card appears (notification ping) |
| `journzey-broll-2-the-fix.mp4` | Quick Log: instrument → Buy / Sell (taps) | Equity chart draws in (riser) | Confessions like → +$15 (coin) | The Ghost: you vs. rules-followed you (chime) |

Where each shot fits the v3 scripts (`../scripts-v3-viral.md`):
- Death Clock → reel 4 (POV chat ending)
- Radar / Boss fight → reels 1, 5, 7 (the danger, the revenge pattern)
- Roast card → reels 1 and 3
- Quick Log → reel 8 ("Log it. Break it.")
- Equity chart / Ghost → reels 2, 5, 7 (the payoff)
- Confessions +$15 → reel 6

The bottom tab bar is hidden for clean framing; every screen keeps a small "SAMPLE DATA · DEMO JOURNAL" tag. Sound effects are synthesised in `compose.py` (no licensed audio).

Rebuild: run `record.mjs` with Playwright against a local copy (logged-in demo member state file), then `python3 compose.py <raw-dir> .`.
