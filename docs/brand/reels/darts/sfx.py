import numpy as np, wave, sys
SR = 44100; DUR = 16.0; N = int(SR * DUR); out = np.zeros(N)
rng = np.random.default_rng(7)
def add(sig, t, gain=1.0):
    i = int(t * SR); j = min(N, i + len(sig)); out[i:j] += sig[:j - i] * gain
def env(n, a=0.005, d=0.2):
    t = np.arange(n) / SR; return np.minimum(1, t / a) * np.exp(-t / d)
def tone(f, dur, d=0.15, a=0.005):
    n = int(SR * dur); t = np.arange(n) / SR; return np.sin(2 * np.pi * f * t) * env(n, a, d)
def sweep(f0, f1, dur, d=0.2):
    n = int(SR * dur); t = np.arange(n) / SR; f = np.linspace(f0, f1, n); ph = 2 * np.pi * np.cumsum(f) / SR; return np.sin(ph) * env(n, 0.005, d)
def noise(dur, lp=0.2, d=0.1, a=0.01):
    n = int(SR * dur); x = rng.standard_normal(n); y = np.zeros(n)
    for i in range(1, n): y[i] = y[i - 1] + lp * (x[i] - y[i - 1])
    return y / (np.abs(y).max() + 1e-9) * env(n, a, d)
# ambient pad: soft minor chord with slow swell, fades before the ending
t = np.arange(N) / SR
pad = sum(np.sin(2 * np.pi * f * t) * w for f, w in [(110, .5), (130.81, .35), (164.81, .3), (220, .15)])
padenv = np.clip(t / 1.5, 0, 1) * np.clip((15.6 - t) / 1.0, 0, 1) * (0.55 + 0.45 * np.clip((11.6 - t) / 0.8, 0, 1))
out += pad * padenv * 0.05
T0, GAP = 0.8, 2.1
gains_bottom = [-1, -1, 1, 1, 1]
for k in range(5):
    T = T0 + k * GAP; rel = T + .35; hit = rel + .3
    add(noise(.32, lp=.08, d=.12, a=.08), rel, .35)                 # whoosh
    add(tone(140, .25, d=.06), hit, .6); add(noise(.05, lp=.6, d=.01), hit, .3)
    add(sweep(260, 150, .35, d=.18), hit + .05, .22)                  # top: loss bloop
    if gains_bottom[k] > 0:
        add(tone(1318.5, .4, d=.12), hit + .05, .18); add(tone(1760, .5, d=.18), hit + .13, .18)   # coin chime
    else:
        add(sweep(240, 140, .35, d=.18), hit + .1, .15)
    if k < 3:                                                         # scribble while journaling
        for s in range(5): add(noise(.07, lp=.5, d=.03, a=.005), hit + .55 + s * .11, .12)
# final: dim swoosh + typewriter
add(noise(.8, lp=.03, d=.4, a=.2), 11.6, .25)
for i in list(range(16)) + list(range(20, 43)):
    add(noise(.03, lp=.7, d=.006, a=.001), 12.2 + (i + 1) / 16, .25)
add(tone(523.25, 1.2, d=.6), 14.6, .12); add(tone(659.25, 1.2, d=.6), 14.62, .1); add(tone(783.99, 1.4, d=.7), 14.64, .1)
out = out / np.abs(out).max() * 0.85
pcm = (out * 32767).astype(np.int16)
with wave.open(sys.argv[1], 'w') as w: w.setnchannels(1); w.setsampwidth(2); w.setframerate(SR); w.writeframes(pcm.tobytes())
print('ok')
