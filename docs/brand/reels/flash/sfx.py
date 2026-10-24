# Beat + sound design for the flash reels: one beat per 0.5 s cut (120 BPM). usage: sfx.py out.wav v
import numpy as np, wave, sys
SR = 44100; V = int(sys.argv[2]); CUTS = 20 if V == 3 else 10; DUR = CUTS * .5 + .4; N = int(SR * DUR); out = np.zeros(N); rng = np.random.default_rng(V)
PROFIT = {1: [0, 1, 2, 4, 6], 2: [4, 5, 7], 3: [5, 6, 11, 13, 14, 16]}[V]; LOSS = {1: [], 2: [1], 3: []}[V]
def add(sig, t, gain=1.0):
    i = int(t * SR); j = min(N, i + len(sig)); out[i:j] += sig[:j - i] * gain
def env(n, a=.003, d=.2): t = np.arange(n) / SR; return np.minimum(1, t / a) * np.exp(-t / d)
def tone(f, dur, d=.15, a=.003): n = int(SR * dur); t = np.arange(n) / SR; return np.sin(2 * np.pi * f * t) * env(n, a, d)
def sweep(f0, f1, dur, d=.2, a=.003): n = int(SR * dur); f = np.geomspace(f0, f1, n); return np.sin(2 * np.pi * np.cumsum(f) / SR) * env(n, a, d)
def noise(dur, lp=.2, d=.1, a=.005):
    n = int(SR * dur); x = rng.standard_normal(n); y = np.zeros(n); k = 0.0
    for i in range(n): k += lp * (x[i] - k); y[i] = k
    return y / (np.abs(y).max() + 1e-9) * env(n, a, d)
hat = noise(.05, lp=.95, d=.012); hat = np.diff(hat, prepend=0)
for c in range(CUTS):
    t0 = c * .5; last = c == CUTS - 1
    add(sweep(150, 42, .45, d=.16), t0, 1.0)                     # kick on every cut
    add(noise(.12, lp=.5, d=.04), t0, .25)                        # click/transient
    if not last: add(hat, t0 + .25, .35); add(hat, t0 + .375, .18)  # hats
    if not last: add(noise(.22, lp=.08, d=.2, a=.18), t0 + .3, .22)  # whoosh into next cut
    bass = [55, 55, 65.41, 49][c % 4]; add(tone(bass, .48, d=.3, a=.01) + .3 * tone(bass * 2, .48, d=.2, a=.01), t0, .45)
    if c in PROFIT:                                               # cash register-ish chime
        for f, dt in ((1567.98, .06), (2093, .11), (2637, .16)): add(tone(f, .5, d=.18), t0 + dt, .16)
        add(noise(.08, lp=.9, d=.02), t0 + .05, .2)
    if c in LOSS: add(sweep(260, 90, .45, d=.25), t0 + .05, .35)
# riser into the logo + impact
add(noise(1.0, lp=.15, d=1.5, a=.95) * np.linspace(0, 1, SR), (CUTS - 1) * .5 - 1.0, .35)
add(sweep(200, 1200, 1.0, d=2, a=.9), (CUTS - 1) * .5 - 1.0, .08)
lt = (CUTS - 1) * .5; add(sweep(90, 30, 1.2, d=.6), lt, 1.1); add(noise(.9, lp=.1, d=.4), lt, .4)
for f in (523.25, 659.25, 783.99, 1046.5): add(tone(f, 1.2, d=.6, a=.01), lt + .02, .08)
out = np.tanh(out * 1.4); out = out / np.abs(out).max() * .9
with wave.open(sys.argv[1], 'w') as w: w.setnchannels(1); w.setsampwidth(2); w.setframerate(SR); w.writeframes((out * 32767).astype(np.int16).tobytes())
print('ok', V, DUR)
