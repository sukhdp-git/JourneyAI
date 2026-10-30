# Builds two 4-second vertical B-roll clips from real journzey terminal recordings (Demo Journal, sample data).
# Each second is one self-contained shot with its own sound effect, so the videos can be cut at 1.0 / 2.0 / 3.0 s.
# usage: python3 compose.py <raw-recordings-dir> <out-dir>   (recordings come from the Playwright screencast script)
import json, math, pathlib, subprocess, sys, wave
import numpy as np
from PIL import Image, ImageDraw, ImageFilter, ImageFont

RAW, OUT = pathlib.Path(sys.argv[1]), pathlib.Path(sys.argv[2])
W, H, FPS, SR = 1080, 1920, 30, 48000
FONT = '/usr/share/fonts/truetype/dejavu/DejaVuSansMono.ttf'

# (clip, start s, focus x 0-1, focus y 0-1, zoom, sound)
V = {
 1: [('v1a-deathclock', .35, .5, .5, 1.0, 'tick'),
     ('v1b-radar',      .10, .5, .5, 1.0, 'alarm'),
     ('v1c-boss',       .12, .5, .5, 1.0, 'hit'),
     ('v1d-roast',      None, .5, .5, 1.0, 'ping')],
 2: [('v2a-quicklog',   .20, .5, .5, 1.0, 'taps'),
     ('v2b-equity',     .04, .5, .5, 1.0, 'rise'),
     ('v2c-like',       .14, .5, .5, 1.0, 'coin'),
     ('v2d-ghost',      .08, .5, .5, 1.0, 'chime')],
}

def ease(x): x = min(1, max(0, x)); return x * x * (3 - 2 * x)

def load(clip):
    d = RAW / clip; t = json.load(open(d / 'times.json'))
    fs = sorted(d.glob('*.jpg')); return t, fs

def first_content(t, fs):  # skip the blank page before the first paint
    for ti, f in zip(t, fs):
        a = np.asarray(Image.open(f).convert('L').resize((40, 70)))
        if a.mean() < 120: return ti
    return 0

def tag():
    im = Image.new('RGBA', (W, H), (0, 0, 0, 0)); d = ImageDraw.Draw(im)
    try: f = ImageFont.truetype(FONT, 26)
    except OSError: f = ImageFont.load_default()
    txt = 'SAMPLE DATA · DEMO JOURNAL'; w = d.textlength(txt, font=f)
    x, y = (W - w) / 2, H - 78
    d.rounded_rectangle((x - 22, y - 12, x + w + 22, y + 40), 22, fill=(10, 8, 28, 170), outline=(255, 255, 255, 40))
    d.text((x, y), txt, font=f, fill=(222, 220, 236, 230)); return im
TAG = tag()

def shot(clip, s0, fx, fy, zoom):
    t, fs = load(clip)
    if s0 is None: s0 = first_content(t, fs) + .05
    cache = {}
    def src(tt):
        i = max(0, np.searchsorted(t, tt, side='right') - 1)
        if i not in cache: cache.clear(); cache[i] = Image.open(fs[i]).convert('RGB')
        return cache[i]
    frames = []
    for k in range(FPS):
        u = k / FPS; im = src(s0 + u); sw, sh = im.size
        z = zoom * (1.08 - .08 * ease(u / .14) + .035 * ease((u - .14) / .86))   # punch-in, then slow push
        cw = sw / z; ch = cw * H / W
        if ch > sh: ch = sh; cw = ch * W / H
        cx = min(max(fx * sw, cw / 2), sw - cw / 2); cy = min(max(fy * sh, ch / 2), sh - ch / 2)
        fr = im.crop((int(cx - cw / 2), int(cy - ch / 2), int(cx + cw / 2), int(cy + ch / 2))).resize((W, H), Image.LANCZOS)
        if k < 3: fr = fr.filter(ImageFilter.GaussianBlur(6 - 2 * k))                    # motion blur on the cut
        fr = fr.convert('RGBA')
        if k < 3: fr = Image.blend(fr, Image.new('RGBA', (W, H), (255, 255, 255, 255)), (.28, .14, .05)[k])
        fr.alpha_composite(TAG); frames.append(fr.convert('RGB'))
    return frames

# ---------- sound design (synthesised, no licensed audio) ----------
rng = np.random.default_rng(7)
def env(n, a=.003, d=.2): tt = np.arange(n) / SR; return np.minimum(1, tt / a) * np.exp(-tt / d)
def tone(f, dur, d=.15, a=.003): n = int(SR * dur); tt = np.arange(n) / SR; return np.sin(2 * np.pi * f * tt) * env(n, a, d)
def sweep(f0, f1, dur, d=.2, a=.003): n = int(SR * dur); f = np.geomspace(f0, f1, n); return np.sin(2 * np.pi * np.cumsum(f) / SR) * env(n, a, d)
def noise(dur, lp=.2, d=.1, a=.005):
    n = int(SR * dur); x = rng.standard_normal(n); y = np.zeros(n); k = 0.0
    for i in range(n): k += lp * (x[i] - k); y[i] = k
    return y / (np.abs(y).max() + 1e-9) * env(n, a, d)
def sfx(kind):
    out = np.zeros(SR)
    def add(sig, at, g=1.0): i = int(at * SR); j = min(SR, i + len(sig)); out[i:j] += sig[:j - i] * g
    add(noise(.18, lp=.35, d=.06, a=.002), 0, .45)          # whoosh-in on every cut
    add(sweep(160, 45, .35, d=.12), 0, .9)                   # impact
    if kind == 'tick':
        for at in (.18, .5, .82): add(noise(.03, lp=.9, d=.006), at, .7); add(tone(2400, .05, d=.01), at, .25)
        add(sweep(70, 50, .25, d=.08), .5, .6)
    elif kind == 'alarm':
        for at in (.15, .45, .75): add(sweep(700, 1100, .22, d=.2, a=.01), at, .22)
        add(noise(.8, lp=.08, d=.9, a=.6) * np.linspace(0, 1, int(SR * .8)), .15, .2)
    elif kind == 'hit':
        add(sweep(220, 40, .5, d=.25), .12, 1.0); add(noise(.25, lp=.6, d=.05), .12, .5)
        add(sweep(90, 60, .6, d=.4, a=.05) * (1 + .5 * np.sin(np.arange(int(SR * .6)) / SR * 2 * np.pi * 18)), .35, .35)
    elif kind == 'ping':
        add(tone(1318.5, .4, d=.18), .12, .35); add(tone(1760, .5, d=.25), .24, .35)
    elif kind == 'taps':
        for at in (.12, .58): add(noise(.04, lp=.95, d=.008), at, .8); add(tone(1800, .04, d=.012), at, .35); add(sweep(400, 200, .06, d=.03), at, .4)
    elif kind == 'rise':
        n = int(SR * .8); add(sweep(220, 1400, .8, d=2, a=.6) * np.linspace(.2, 1, n), .08, .18); add(noise(.8, lp=.25, d=2, a=.7) * np.linspace(0, 1, n), .08, .22)
        for f, at in ((1046.5, .82), (1568, .86)): add(tone(f, .15, d=.08), at, .15)
    elif kind == 'coin':
        for f, at in ((1567.98, .22), (2093, .28), (2637, .34)): add(tone(f, .5, d=.2), at, .22)
        add(noise(.08, lp=.9, d=.02), .2, .3)
    elif kind == 'chime':
        for f, at in ((523.25, .12), (659.25, .22), (783.99, .32), (1046.5, .42)): add(tone(f, .55, d=.3, a=.01), at, .2)
    fade = np.ones(SR); fade[-480:] = np.linspace(1, 0, 480); return out * fade   # clean edit point at each second

for v, shots in V.items():
    frames, audio = [], []
    for clip, s0, fx, fy, z, kind in shots:
        frames += shot(clip, s0, fx, fy, z); audio.append(sfx(kind))
    a = np.tanh(np.concatenate(audio) * 1.3); a = a / np.abs(a).max() * .89
    wav = OUT / f'_v{v}.wav'
    with wave.open(str(wav), 'w') as w: w.setnchannels(1); w.setsampwidth(2); w.setframerate(SR); w.writeframes((a * 32767).astype(np.int16).tobytes())
    name = {1: 'journzey-broll-1-the-problem.mp4', 2: 'journzey-broll-2-the-fix.mp4'}[v]
    p = subprocess.Popen(['ffmpeg', '-y', '-loglevel', 'error', '-f', 'rawvideo', '-pix_fmt', 'rgb24', '-s', f'{W}x{H}', '-r', str(FPS), '-i', '-',
        '-i', str(wav), '-c:v', 'libx264', '-profile:v', 'high', '-crf', '17', '-preset', 'slow', '-g', str(FPS), '-keyint_min', str(FPS), '-sc_threshold', '0',
        '-pix_fmt', 'yuv420p', '-c:a', 'aac', '-b:a', '192k', '-ac', '2', '-ar', str(SR), '-shortest', '-movflags', '+faststart', str(OUT / name)], stdin=subprocess.PIPE)
    for fr in frames: p.stdin.write(fr.tobytes())
    p.stdin.close(); p.wait(); wav.unlink()
    print('ok', name, len(frames) / FPS, 's')
