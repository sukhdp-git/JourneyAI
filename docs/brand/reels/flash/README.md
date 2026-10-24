# journzey flash reels (fast cuts, 9:16, 1080×1920, 30 fps)

| File | Length | Cuts | Story |
|---|---|---|---|
| journzey-flash-1-tp-hit.mp4 | 5 s | 10 × 0.5 s | TP hit → notifications → MT5 history → watch → MT5 +12,480 → car → NAS100 TP → penthouse → "Not luck. A journal." → logo |
| journzey-flash-2-while-they-slept.mp4 | 5 s | 10 × 0.5 s | 2:14 AM → loss → journal note → checklist → TP → MT5 +8,920 → plane → gold → "Discipline compounds." → logo |
| journzey-flash-3-day-of-a-disciplined-trader.mp4 | 10 s | 20 × 0.5 s | 06:00 → plan → London open → entry → hold → TP → notification → journal → drive → NY open → short → history → beach → equity → calendar → gold → MT5 month → "Same plan. Every. Day." → logo |

- Every cut lands on a 120 BPM beat (kick per cut, chime on profit scenes, riser + impact into the logo).
- All P&L numbers are illustrations and each frame says "Illustration · not real results". MT5/TradingView screens are generic look-alikes without logos.
- Rebuild: render `flash.html?v=1|2|3` frame by frame (draw(t), 30 fps) and audio with `python3 sfx.py out.wav <v>`, then mux with ffmpeg (loudnorm −14 LUFS).
