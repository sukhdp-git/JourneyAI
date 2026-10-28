# Music bed + sound design for the "Everyone posts this" reel (24.5 s, 48 kHz stereo). Original synthesis: no licensed audio.
import numpy as np, wave
R = '/tmp/claude-0/-home-user-JourneyAI/33fbe251-1222-52a4-8036-7bc0d6df5c07/scratchpad/reel'
SR = 48000; DUR = 24.5; N = int(SR * DUR); rng = np.random.default_rng(7)
mus = np.zeros((N, 2)); sfx = np.zeros((N, 2))

def rd(path):
    w = wave.open(path); x = np.frombuffer(w.readframes(w.getnframes()), np.int16).astype(float) / 32768
    if w.getnchannels() == 2: x = x.reshape(-1, 2).mean(1)
    return x
def add(buf, sig, t, g=1.0, pan=0.0):
    i = int(t * SR); j = min(N, i + len(sig))
    if j <= i: return
    l, r = np.sqrt(.5 * (1 - pan)), np.sqrt(.5 * (1 + pan))
    buf[i:j, 0] += sig[:j - i] * g * l * 1.414; buf[i:j, 1] += sig[:j - i] * g * r * 1.414
def env(n, a=.003, d=.2): t = np.arange(n) / SR; return np.minimum(1, t / max(a, 1e-4)) * np.exp(-t / d)
def tone(f, dur, d=.2, a=.003): n = int(SR * dur); t = np.arange(n) / SR; return np.sin(2 * np.pi * f * t) * env(n, a, d)
def sweep(f0, f1, dur, d=.2, a=.003): n = int(SR * dur); f = np.geomspace(f0, f1, n); return np.sin(2 * np.pi * np.cumsum(f) / SR) * env(n, a, d)
def lp_noise(dur, k=.2):
    n = int(SR * dur); x = rng.standard_normal(n); y = np.empty(n); acc = 0.0
    for i in range(n): acc += k * (x[i] - acc); y[i] = acc
    return y / (np.abs(y).max() + 1e-9)
def noise(dur, k=.2, d=.1, a=.004): return lp_noise(dur, k) * env(int(SR * dur), a, d)

# ---------- music: dark pad (A minor) + sub + soft kick grid at 96 BPM, lift at payoff
beat = 60 / 96
def pad(freqs, t0, t1, g):
    n = int((t1 - t0) * SR); t = np.arange(n) / SR; s = np.zeros(n)
    for f in freqs:
        for det in (-.003, .003): s += np.sin(2 * np.pi * f * (1 + det) * t + rng.random() * 6)
    s *= np.minimum(1, t / .4) * np.minimum(1, (t1 - t0 - t) / .4)
    add(mus, s / len(freqs) * .5, t0, g)
pad([110, 130.81, 164.81, 220], 0, 7.3, .5)          # Am
pad([87.31, 130.81, 174.61, 220], 7.3, 12.8, .5)      # F
pad([98, 146.83, 196, 246.94], 12.8, 17.65, .55)      # G (tension)
pad([130.81, 164.81, 196, 261.63, 329.63], 17.65, 20.0, .6)  # C major lift: the payoff
pad([110, 130.81, 164.81, 220], 20.0, DUR, .5)        # back to Am → loops into the start
kick = sweep(140, 40, .4, d=.14); hat = np.diff(noise(.05, .95, .012), prepend=0)
t = 0.0
while t < DUR - .05:
    k = int(round(t / beat))
    if not (6.30 <= t < 6.70):                         # drop out right before "MEMORY"
        if k % 2 == 0: add(mus, kick, t, .55)
        if t >= 3.1: add(mus, hat, t + beat / 2, .25, .3)
        add(mus, tone(55 if t < 17.65 or t >= 20 else 65.41, beat * .9, d=.25, a=.01), t, .35)
    t += beat
for t0 in (14.40, 15.15, 15.75, 16.85):                # montage: a hit on every cut
    add(mus, kick, t0, .9); add(mus, noise(.18, .5, .06), t0, .35)

# ---------- sound effects
def whoosh(t_cut, g=.35, pan=0): add(sfx, noise(.32, .07, .18, .26), t_cut - .24, g, pan)
add(sfx, noise(.05, .9, .015), 0.0, .7); add(sfx, noise(.09, .6, .03), .045, .5); add(sfx, sweep(110, 35, .6, d=.25), 0.0, 1.0)  # shutter + thump
for c, p in [(1.50, -.3), (3.10, .3), (4.72, -.3), (5.98, .3), (7.30, -.3), (8.95, .3), (10.55, -.3), (12.80, .3), (17.65, 0), (20.0, 0), (22.60, 0)]:
    whoosh(c, .32, p)
add(sfx, sweep(95, 60, .5, d=.3) + .5 * sweep(190, 120, .5, d=.25), 1.56, .7)                 # "NOBODY": low error buzz
add(sfx, (np.sign(tone(55, .35, d=.25)) * env(int(SR * .35), .002, .12)), 1.56, .18)
for t0 in (3.84, 5.18): add(sfx, noise(.22, .35, .08, .01) * np.linspace(.4, 1, int(SR * .22)), t0, .45)  # strike-through swipe
mac = rd(f'{R}/clips/mac.wav'); mac = mac[int(13 / 30 * SR):]; add(sfx, mac[:int(1.3 * SR)], 5.98, 1.2)  # real laptop close
add(sfx, sweep(120, 38, .7, d=.3), 6.46, 1.0); add(sfx, noise(.25, .3, .08), 6.46, .4)          # "MEMORY" impact
for f, dt in ((1567.98, 0), (2093, .07), (2637, .14)): add(sfx, tone(f, .6, d=.22), 8.08 + dt, .14)  # "WINS" cash chime
kb = rd(f'{R}/clips/kb.wav'); kb = kb[int(18 / 30 * SR):]; seg = kb[:int(1.6 * SR)]
seg = seg * np.minimum(1, np.arange(len(seg)) / (SR * .05)) * np.minimum(1, (len(seg) - np.arange(len(seg))) / (SR * .1))
add(sfx, seg / (np.abs(seg).max() + 1e-9), 8.95, .35)                                              # real keyboard
for i in range(4): add(sfx, tone(2400 if i % 2 == 0 else 1800, .05, d=.012) + noise(.05, .9, .006), 12.85 + i * .4, .35)  # clock tick-tock
add(sfx, noise(1.0, .15, 1.5, .9) * np.linspace(0, 1, SR), 16.65, .3); add(sfx, sweep(200, 1400, 1.0, d=2, a=.9), 16.65, .06)  # riser into payoff
for f in (523.25, 659.25, 783.99): add(sfx, tone(f, 1.0, d=.5, a=.01), 18.84, .07)               # "Fewer mistakes" shine
add(sfx, sweep(90, 30, .9, d=.4), 20.0, .7)
for f, d in ((1318.5, .9), (1975.5, .7), (2637, .5)): add(sfx, tone(f, 1.4, d=d), 21.46, .12)     # 🔔 bell
for f, dt in ((880, 0), (1108.73, .06), (1318.5, .12)): add(sfx, tone(f, .5, d=.2), 20.02 + dt, .05)

# ---------- mix with the voice (music ducked under speech)
vo = rd(f'{R}/vo_edit.wav'); vo = np.pad(vo, (0, max(0, N - len(vo))))[:N]
envv = np.abs(vo); k = int(SR * .12); envv = np.convolve(envv, np.ones(k) / k, 'same'); duck = 1 - .55 * np.clip(envv / (envv.max() * .25), 0, 1)
mix = mus * .30 * duck[:, None] + sfx * .55 + vo[:, None] * 1.0
fade = np.minimum(1, np.arange(N) / (SR * .01)); mix *= fade[:, None]          # click-free start; no fade-out (it loops)
mix = np.tanh(mix * 1.1) / np.tanh(1.1)
with wave.open(f'{R}/mix.wav', 'w') as w:
    w.setnchannels(2); w.setsampwidth(2); w.setframerate(SR); w.writeframes((np.clip(mix, -1, 1) * 32767).astype(np.int16).tobytes())
print('ok')
