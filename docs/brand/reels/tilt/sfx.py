# Sound design for the tilt reel (21.5 s). Leaves room for a voiceover (pad kept low).
import numpy as np, wave, sys
SR = 44100; DUR = 21.5; N = int(SR * DUR); out = np.zeros(N); rng = np.random.default_rng(3)
def add(sig, t, gain=1.0):
    i = int(t * SR); j = min(N, i + len(sig)); out[i:j] += sig[:j - i] * gain
def env(n, a=.005, d=.2): t = np.arange(n) / SR; return np.minimum(1, t / a) * np.exp(-t / d)
def tone(f, dur, d=.15, a=.005): n = int(SR * dur); t = np.arange(n) / SR; return np.sin(2 * np.pi * f * t) * env(n, a, d)
def sweep(f0, f1, dur, d=.2): n = int(SR * dur); f = np.linspace(f0, f1, n); return np.sin(2 * np.pi * np.cumsum(f) / SR) * env(n, .005, d)
def noise(dur, lp=.2, d=.1, a=.01):
    n = int(SR * dur); x = rng.standard_normal(n); y = np.zeros(n)
    for i in range(1, n): y[i] = y[i - 1] + lp * (x[i] - y[i - 1])
    return y / (np.abs(y).max() + 1e-9) * env(n, a, d)
t = np.arange(N) / SR
pad = sum(np.sin(2 * np.pi * f * t) * w for f, w in [(98, .5), (116.54, .35), (146.83, .3), (196, .15)])
out += pad * np.clip(t / 1.2, 0, 1) * np.clip((21.3 - t) / 1.2, 0, 1) * .035
CUTS = [3.5, 7, 10.5, 14, 17.5]
for c in CUTS: add(noise(.35, lp=.06, d=.15, a=.06), c - .12, .4)                       # whoosh on every cut
for tl in (0.9, 1.9): add(sweep(300, 150, .45, d=.22), tl, .35); add(tone(90, .4, d=.2), tl, .4)   # scene 1 losses
add(tone(110, .3, d=.08), 3.5 + .9, .5); add(tone(110, .3, d=.08), 3.5 + 1.5, .5)               # scene 2 desk slams
for k, tl in enumerate((7.8, 8.7, 9.5)): add(sweep(320 - k * 40, 120, .5, d=.25), tl, .4)       # scene 3 losses
for b in np.arange(7.0, 10.5, 60 / 130): add(tone(55, .18, d=.05), b, .5); add(tone(60, .14, d=.04), b + .14, .35)  # heartbeat
for s in np.arange(10.5, 14.0, .5): add(noise(.02, lp=.8, d=.004, a=.001), s, .25)              # clock ticks (cooldown)
add(tone(880, .5, d=.25), 10.5 + .9, .12); add(tone(660, .6, d=.3), 10.5 + 1.0, .12)            # tilt-alert chime
add(sweep(400, 120, .9, d=.5), 14.2, .25); add(tone(1318.5, .5, d=.2), 16.0, .15); add(tone(1760, .6, d=.25), 16.08, .15)
for i in list(range(11)) + list(range(14, 39)): add(noise(.03, lp=.7, d=.006, a=.001), 17.5 + .1 + (i + 1) / 18, .22)   # typewriter
for f, d in ((523.25, 0), (659.25, .02), (783.99, .04)): add(tone(f, 1.6, d=.8), 19.9 + d, .1)
out = out / np.abs(out).max() * .85
with wave.open(sys.argv[1], 'w') as w: w.setnchannels(1); w.setsampwidth(2); w.setframerate(SR); w.writeframes((out * 32767).astype(np.int16).tobytes())
print('ok')
