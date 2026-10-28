# journzey reel "Everyone posts this" — frame compositor. Writes raw RGB frames to stdout (pipe into ffmpeg).
import sys, math, functools
from PIL import Image, ImageFilter, ImageDraw, ImageEnhance
import numpy as np

R = '/tmp/claude-0/-home-user-JourneyAI/33fbe251-1222-52a4-8036-7bc0d6df5c07/scratchpad/reel'
IMG = '/tmp/claude-0/-home-user-JourneyAI/33fbe251-1222-52a4-8036-7bc0d6df5c07/images'
W, H, FPS = 1080, 1920, 30
TOTAL = 24.5
PREVIEW = '--preview' in sys.argv  # only write a few stills

def ease(x): x = min(1, max(0, x)); return x * x * (3 - 2 * x)
def lerp(a, b, t): return a + (b - a) * t

@functools.lru_cache(maxsize=48)
def load(path, bright=1.0, contrast=1.0, sat=1.0):
    im = Image.open(path).convert('RGB')
    if bright != 1: im = ImageEnhance.Brightness(im).enhance(bright)
    if contrast != 1: im = ImageEnhance.Contrast(im).enhance(contrast)
    if sat != 1: im = ImageEnhance.Color(im).enhance(sat)
    return im

def crop916(im, cx, cy, w):
    """Crop a 9:16 window centred at (cx, cy) with width w (clamped inside the image), resized to 1080x1920."""
    h = w * 16 / 9
    if h > im.height: h = im.height; w = h * 9 / 16
    if w > im.width: w = im.width; h = w * 16 / 9
    x0 = min(max(cx - w / 2, 0), im.width - w); y0 = min(max(cy - h / 2, 0), im.height - h)
    return im.resize((W, H), Image.BICUBIC, box=(x0, y0, x0 + w, y0 + h))

def zoom(im, s, ox=0.5, oy=0.5):
    if abs(s - 1) < 1e-4: return im
    w, h = W / s, H / s; x0 = (W - w) * ox; y0 = (H - h) * oy
    return im.resize((W, H), Image.BICUBIC, box=(x0, y0, x0 + w, y0 + h))

def seq(folder, idx, n_avail, ext='jpg'):
    i = int(min(max(idx, 0), n_avail - 1)) + 1
    return f'{R}/{folder}/{i:04d}.{ext}'

# ---------------------------------------------------------------- scenes
# each: (start, end, render(u, d) -> Image) where u = seconds into scene, d = scene length
def photo(path, r0, r1, bright=1.0, contrast=1.0, sat=1.0):
    def f(u, d):
        t = ease(u / d); im = load(path, bright, contrast, sat)
        return crop916(im, lerp(r0[0], r1[0], t), lerp(r0[1], r1[1], t), lerp(r0[2], r1[2], t))
    return f

def clip(folder, n, first, z0=1.0, z1=1.08, rate=1.0, bright=1.0, contrast=1.0, sat=1.0, blur_box=None, blur_until=-1, oy=0.5):
    def f(u, d):
        k = first + u * FPS * rate; idx = int(k)
        im = load(seq('clips/' + folder, idx, n), bright, contrast, sat)
        im = im.resize((W, H), Image.BICUBIC) if im.size != (W, H) else im.copy()
        if blur_box and idx <= blur_until:
            reg = im.crop(blur_box).filter(ImageFilter.GaussianBlur(26)); im.paste(reg, blur_box[:2])
        return zoom(im, lerp(z0, z1, ease(u / d)), 0.5, oy)
    return f

def site(name, n, first, z0=1.0, z1=1.06, oy=0.35):
    def f(u, d):
        im = load(seq('site/' + name, first + u * FPS, n, 'png'))
        return zoom(im, lerp(z0, z1, ease(u / d)), 0.5, oy)
    return f

MT5 = f'{IMG}/4.jpg'
mac_box = (440, 1020, 760, 1180)  # name on the lock screen (in 1215x2160 crop coords → scaled below)
mac_box = tuple(int(v * W / 1215) for v in mac_box)

SCENES = [
 (0.00, 1.50, photo(MT5, (368, 640, 736), (368, 560, 600), 1.04, 1.08)),
 (1.50, 3.10, photo(f'{IMG}/5.jpg', (1520, 930, 1040), (1640, 860, 760), 1.25, 1.12, 1.1)),
 (3.10, 4.72, photo(f'{IMG}/8.jpg', (560, 460, 518), (520, 430, 420), 1.0, 1.05)),
 (4.72, 5.98, photo(f'{IMG}/6.jpg', (1000, 880, 990), (520, 470, 560), 1.3, 1.1, 1.1)),
 (5.98, 7.30, clip('mac', 62, 13, 1.0, 1.06, 1.0, 1.55, 1.1, 1.0, mac_box, 22)),
 (7.30, 8.95, photo(f'{IMG}/7.jpg', (1060, 1090, 640), (1130, 1090, 470), 1.2, 1.1, 1.1)),
 (8.95, 10.55, clip('kb', 84, 18, 1.04, 1.12, 1.0, 1.5, 1.1, 1.15)),
 (10.55, 12.80, clip('dash', 93, 20, 1.0, 1.1, 1.0, 1.15, 1.05)),
 (12.80, 14.40, site('clock', 66, 6, 1.06, 1.2, 0.36)),
 (14.40, 15.15, site('qlog', 42, 6, 1.02, 1.0, 0.3)),
 (15.15, 15.75, site('cal', 30, 8, 1.06, 1.0, 0.25)),
 (15.75, 16.85, site('roast', 42, 4, 1.0, 1.1, 0.1)),
 (16.85, 17.65, site('boss', 36, 6, 1.06, 1.0, 0.5)),
 (17.65, 20.00, site('ghost', 78, 6, 1.0, 1.07, 0.25)),
 (20.00, 22.60, clip('setup', 61, 0, 1.0, 1.1, 0.5, 1.15, 1.05, 1.05, oy=0.4)),
 (22.60, TOTAL, photo(MT5, (368, 760, 736), (368, 650, 700), 1.04, 1.08)),
]
SITE_SCENES = {(12.80), (14.40), (15.15), (15.75), (16.85), (17.65)}

# ---------------------------------------------------------------- captions (start, end, png, centre-y)
CY = 1180; LOW = 1440
CAPS = [
 (0.16, 0.70, 'a1', CY), (0.70, 1.50, 'a2', CY),
 (1.56, 2.06, 'b1', CY), (2.06, 3.10, 'b2', CY),
 (3.14, 3.84, 'c1', CY), (3.84, 4.72, 'c2', CY),
 (4.76, 5.18, 'd1', CY), (5.18, 5.98, 'd2', CY),
 (6.02, 6.46, 'e1', CY), (6.46, 7.30, 'e2', CY),
 (7.38, 8.08, 'f1', CY), (8.08, 8.95, 'f2', CY),
 (9.08, 9.68, 'g1', CY), (9.68, 10.55, 'g2', CY),
 (10.64, 11.72, 'h1', 1300), (11.72, 12.80, 'h2', 1300),
 (12.88, 13.68, 'i1', 1260), (13.68, 14.40, 'i2', 1260),
 (14.46, 15.15, 'j1', LOW), (15.20, 15.75, 'k1', LOW), (15.80, 16.85, 'l1', 1220), (16.92, 17.65, 'm1', LOW),
 (17.72, 18.84, 'n1', 1290), (18.84, 20.00, 'n2', 1290),
 (22.66, 23.12, 'p1', CY), (23.12, TOTAL, 'p2', CY),
]
NEWLINE = {'a2', 'b2', 'c2', 'd2', 'e2', 'f2', 'g2', 'h2', 'i2', 'n2', 'p2'}  # second line appended: no full re-pop

@functools.lru_cache(maxsize=64)
def cap(name): return Image.open(f'{R}/cap/{name}.png').convert('RGBA')

def paste_scaled(base, im, cx, cy, s=1.0, alpha=1.0):
    if s != 1.0: im = im.resize((max(1, int(im.width * s)), max(1, int(im.height * s))), Image.BICUBIC)
    if alpha < 1.0:
        a = im.getchannel('A').point(lambda v: int(v * alpha)); im = im.copy(); im.putalpha(a)
    base.alpha_composite(im, (int(cx - im.width / 2), int(cy - im.height / 2)))

def pop(age):  # scale curve for a caption that just appeared
    if age < 0: return 0, 0
    k = age * FPS
    if k < 1: return .78, .6
    if k < 2: return 1.08, 1
    if k < 3: return 1.03, 1
    return 1.0, 1

# ---------------------------------------------------------------- overlays
def vignette(color, strength):
    y, x = np.ogrid[:H, :W]; d = np.sqrt(((x - W / 2) / (W / 2)) ** 2 + ((y - H / 2) / (H / 2)) ** 2)
    a = np.clip((d - .55) / .6, 0, 1) * strength * 255
    v = np.zeros((H, W, 4), np.uint8); v[..., :3] = color; v[..., 3] = a.astype(np.uint8)
    return Image.fromarray(v, 'RGBA')
VIG_DARK = vignette((0, 0, 0), .55); VIG_RED = vignette((220, 30, 50), .55); VIG_GREEN = vignette((20, 200, 120), .35)
GRAD = Image.new('RGBA', (W, H), (0, 0, 0, 0))
gd = np.zeros((H, W, 4), np.uint8); ramp = np.clip((np.arange(H) - 950) / 700, 0, 1) * 200; gd[..., 3] = ramp[:, None].astype(np.uint8); GRAD = Image.fromarray(gd, 'RGBA')

def shake(im, t, t0, amp=18, dur=.25):
    a = t - t0
    if a < 0 or a > dur: return im
    k = (1 - a / dur) * amp; dx = int(math.sin(a * 90) * k); dy = int(math.cos(a * 70) * k * .6)
    out = Image.new('RGB', (W, H)); out.paste(zoom(im, 1.04), (dx, dy)); return out

@functools.lru_cache(maxsize=64)
def backdrop(bw, bh):
    b = Image.new('RGBA', (bw + 160, bh + 160), (0, 0, 0, 0)); d = ImageDraw.Draw(b)
    d.rounded_rectangle((80, 80, 80 + bw, 80 + bh), 60, fill=(6, 5, 20, 120))
    return b.filter(ImageFilter.GaussianBlur(40))

CUTS = [s for s, _, _ in SCENES][1:]
SHAKES = [1.56, 6.46, 15.80]

def frame(fi):
    t = fi / FPS
    for s, e, fn in SCENES:
        if s <= t < e or (e == TOTAL and t >= s): break
    u = t - s; im = fn(u, e - s)
    # punch-in on every cut: 3 frames
    cu = u * FPS
    if s > 0 and cu < 3: im = zoom(im, [1.09, 1.045, 1.015][int(cu)])
    for st in SHAKES: im = shake(im, t, st)
    im = im.convert('RGBA')
    im.alpha_composite(VIG_DARK)
    if 1.50 <= t < 3.10 or 12.80 <= t < 14.40: im.alpha_composite(VIG_RED)
    if 17.65 <= t < 20.0 or 7.30 <= t < 8.95: im.alpha_composite(VIG_GREEN)
    if 20.0 <= t < 22.6: im.alpha_composite(GRAD)
    # brand flash (thumb-stop): first 4 frames
    if fi < 4:
        col = [(255, 255, 255, 235), (101, 65, 233, 200), (254, 178, 180, 150), (254, 178, 180, 60)][fi]
        im.alpha_composite(Image.new('RGBA', (W, H), col))
    # captions
    for cs, ce, name, cy in CAPS:
        if cs <= t < ce:
            c = cap(name); bw, bh = int(c.width * 1.12), int(c.height * 1.12)
            im.alpha_composite(backdrop(bw, bh), (int(W / 2 - (bw + 160) / 2), int(cy - (bh + 160) / 2)))
            age = t - cs
            sc, al = pop(age) if name not in NEWLINE else (1.0 if age * FPS >= 1 else 1.02, 1)
            paste_scaled(im, cap(name), W / 2, cy, sc * 1.12, al)
    # CTA: logo + pill
    if 20.0 <= t < 22.6:
        sc, al = pop(t - 20.0); paste_scaled(im, cap('logo'), W / 2, 560, sc, al)
        if t >= 21.46:
            sc, al = pop(t - 21.46); b = 1 + .025 * math.sin((t - 21.46) * 7)
            paste_scaled(im, cap('pill'), W / 2, 1440, sc * b, al)
    # watermark + honest label on the demo-journal screens
    if not 20.0 <= t < 22.6: paste_scaled(im, cap('wm'), 150, 175, 1.0, .9)
    if 12.80 <= t < 20.0: paste_scaled(im, cap('tag'), W - 230, 175, 1.0, .95)
    return im.convert('RGB')

if __name__ == '__main__':
    N = int(round(TOTAL * FPS))
    if PREVIEW:
        for s in [int(v * FPS) for v in map(float, sys.argv[2].split(','))]:
            frame(s).save(f'{R}/prev_{s:04d}.jpg', quality=88)
        sys.exit()
    out = sys.stdout.buffer
    for fi in range(N):
        out.write(frame(fi).tobytes())
