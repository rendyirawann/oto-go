"""Generate the OtoGo brand kit (SVG + PNG + ICO + OG image).

Usage: python -I make_brand.py <out_dir> <fonts_dir>
"""
import math
import os
import sys

from PIL import Image, ImageDraw, ImageFont

OUT = sys.argv[1]
FONTS = sys.argv[2]
os.makedirs(OUT, exist_ok=True)

INK = "#111418"
ORANGE = "#FF5A1F"
PAPER = "#F5F3EE"
WHITE = "#FFFFFF"

# ---------------------------------------------------------------- geometry
# Mark lives in a 512 box. Open ring (the "O") with a forward chevron ("go").
RING_C = (226, 256)
RING_R = 108
RING_W = 58
APEX = (392, 256)          # forward point of the "O"
_d = APEX[0] - RING_C[0]
GAP_DEG = math.degrees(math.acos(RING_R / _d))  # tangent angle -> smooth join


def ring_end(sign):
    a = math.radians(GAP_DEG)
    return (RING_C[0] + RING_R * math.cos(a), RING_C[1] + sign * RING_R * math.sin(a))


def mark_paths(fg=ORANGE):
    (x1, y1), (x2, y2) = ring_end(-1), ring_end(1)
    return (
        f'<path d="M{x1:.1f} {y1:.1f}A{RING_R} {RING_R} 0 1 0 {x2:.1f} {y2:.1f}L{APEX[0]} {APEX[1]}Z" '
        f'fill="none" stroke="{fg}" stroke-width="{RING_W}" stroke-linejoin="round"/>'
        f'<circle cx="{RING_C[0]}" cy="{RING_C[1]}" r="26" fill="{fg}"/>'
    )


def mark_svg(tile=INK, fg=ORANGE, size=512):
    return (
        f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="{size}" height="{size}" '
        f'role="img" aria-label="OtoGo">'
        f'<rect width="512" height="512" rx="120" fill="{tile}"/>{mark_paths(fg)}</svg>'
    )


# Logotype "otogo": geometric strokes, x-height 52 (y 34..86), stroke 13.
SW = 13
R = 19.5  # centre-line radius -> outer radius 26


def o(cx, color):
    return f'<circle cx="{cx}" cy="60" r="{R}" fill="none" stroke="{color}" stroke-width="{SW}"/>'


def t(x, color):
    return (
        f'<path d="M{x} 18V68Q{x} 79.5 {x + 12} 79.5H{x + 15}" fill="none" stroke="{color}" '
        f'stroke-width="{SW}" stroke-linecap="round" stroke-linejoin="round"/>'
        f'<path d="M{x - 11} 40.5H{x + 15}" stroke="{color}" stroke-width="{SW}" stroke-linecap="round"/>'
    )


def g(cx, color):
    sx = cx + R
    return (
        o(cx, color)
        + f'<path d="M{sx} 40.5V88Q{sx} 106 {cx + 1} 106Q{cx - 11} 106 {cx - 16} 99" fill="none" '
        f'stroke="{color}" stroke-width="{SW}" stroke-linecap="round" stroke-linejoin="round"/>'
    )


def logotype(color, accent):
    return o(154, color) + t(199, color) + o(254, color) + g(314, color) + o(376, accent)


def full_svg(text_color, tile=INK):
    mark = f'<g transform="translate(12 12) scale(.1875)"><rect width="512" height="512" rx="120" fill="{tile}"/>{mark_paths()}</g>'
    return (
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 414 120" width="414" height="120" '
        'role="img" aria-label="OtoGo">' + mark + logotype(text_color, ORANGE) + "</svg>"
    )


def write(name, data):
    with open(os.path.join(OUT, name), "w", encoding="utf-8") as fh:
        fh.write(data)


write("logo-mark.svg", mark_svg())
write("favicon.svg", mark_svg())
write("logo-full.svg", full_svg(INK))
write("logo-full-light.svg", full_svg("#F5F3EE", tile="#1C2027"))
write("logotype.svg",
      '<svg xmlns="http://www.w3.org/2000/svg" viewBox="128 0 286 120" width="286" height="120" '
      'role="img" aria-label="OtoGo"><g fill="none">' + logotype("currentColor", ORANGE) + "</g></svg>")


# ---------------------------------------------------------------- raster
S = 2048  # supersample canvas
K = S / 512


def hex_rgba(h, a=255):
    h = h.lstrip("#")
    return (int(h[0:2], 16), int(h[2:4], 16), int(h[4:6], 16), a)


def thick_line(d, p1, p2, w, fill):
    d.line([p1, p2], fill=fill, width=int(w))
    r = w / 2
    for (x, y) in (p1, p2):
        d.ellipse([x - r, y - r, x + r, y + r], fill=fill)


def render_mark(size, tile=INK, fg=ORANGE, full_bleed=False, pad=0.0):
    img = Image.new("RGBA", (S, S), (0, 0, 0, 0))
    d = ImageDraw.Draw(img)
    radius = 0 if full_bleed else int(120 * K)
    d.rounded_rectangle([0, 0, S - 1, S - 1], radius=radius, fill=hex_rgba(tile))

    # Optional inner padding (maskable icons need a safe zone).
    scale = K * (1 - pad)
    off = S * pad / 2

    def P(x, y):
        return (off + x * scale, off + y * scale)

    cx, cy = P(*RING_C)
    rr = RING_R * scale
    w = RING_W * scale
    color = hex_rgba(fg)
    # PIL arc angles: 0 = 3 o'clock, clockwise. Draw from +GAP to 360-GAP.
    d.arc([cx - rr - w / 2, cy - rr - w / 2, cx + rr + w / 2, cy + rr + w / 2],
          start=GAP_DEG, end=360 - GAP_DEG, fill=color, width=int(w))
    for sign in (-1, 1):
        ex, ey = ring_end(sign)
        ex, ey = P(ex, ey)
        d.ellipse([ex - w / 2, ey - w / 2, ex + w / 2, ey + w / 2], fill=color)

    apex = P(*APEX)
    for sign in (-1, 1):
        ex, ey = P(*ring_end(sign))
        thick_line(d, (ex, ey), apex, w, color)
    hub = 26 * scale
    d.ellipse([cx - hub, cy - hub, cx + hub, cy + hub], fill=color)
    return img.resize((size, size), Image.LANCZOS)


for size, name in [(16, "favicon-16.png"), (32, "favicon-32.png"), (48, "favicon-48.png"),
                   (64, "favicon-64.png"), (192, "icon-192.png"), (512, "icon-512.png")]:
    render_mark(size).save(os.path.join(OUT, name), optimize=True)

# iOS ignores transparency: full-bleed tile.
render_mark(180, full_bleed=True).save(os.path.join(OUT, "apple-touch-icon.png"), optimize=True)
# Android maskable: full bleed + 20% safe-zone padding.
render_mark(512, full_bleed=True, pad=0.2).save(os.path.join(OUT, "icon-maskable-512.png"), optimize=True)

ico_src = render_mark(256)
ico_src.save(os.path.join(OUT, "favicon.ico"), sizes=[(16, 16), (32, 32), (48, 48), (64, 64)])


# ---------------------------------------------------------------- OG image
def font(name, size, wght=None, axes=None):
    f = ImageFont.truetype(os.path.join(FONTS, name), size)
    want = {"wght": wght or 400, "wdth": 100, "opsz": 72}
    try:
        values = []
        for ax in f.get_variation_axes():
            tag = ax.get("name", b"")
            tag = tag.decode() if isinstance(tag, bytes) else str(tag)
            key = {"Weight": "wght", "Width": "wdth", "Optical size": "opsz", "Optical Size": "opsz"}.get(tag, tag)
            v = want.get(key, ax["default"])
            values.append(max(ax["minimum"], min(ax["maximum"], v)))
        f.set_variation_by_axes(values)
    except Exception as e:
        print("font axes:", name, e)
    return f


W, H = 1200, 630
og = Image.new("RGBA", (W * 2, H * 2), hex_rgba(PAPER))
d = ImageDraw.Draw(og)

# Lane markings along the bottom: dashed road line motif.
y = H * 2 - 150
for x in range(-40, W * 2, 180):
    d.rounded_rectangle([x, y, x + 110, y + 18], radius=9, fill=hex_rgba("#E2DDD2"))
# Thin rule
d.rectangle([120, 1020, W * 2 - 120, 1022], fill=hex_rgba("#DEDAD0"))

mark = render_mark(300)
og.alpha_composite(mark.resize((300, 300), Image.LANCZOS), (120, 150))

# Bricolage axes order: opsz, wdth, wght
display = font("Bricolage.ttf", 176, wght=800)
body = font("Jakarta.ttf", 52, wght=500)
mono = font("JetBrainsMono.ttf", 34, wght=500)

d.text((120, 520), "OtoGo", font=display, fill=hex_rgba(INK))
d.text((126, 730), "Bengkel, doorsmeer & sparepart", font=body, fill=hex_rgba("#2A2F36"))
d.text((126, 800), "dalam satu aplikasi.", font=body, fill=hex_rgba("#2A2F36"))

# Plate chip: Medan "BK" plate
px, py = 1560, 200
d.rounded_rectangle([px, py, px + 520, py + 150], radius=22, fill=hex_rgba(INK))
d.rounded_rectangle([px + 12, py + 12, px + 508, py + 138], radius=14, outline=hex_rgba("#F5F3EE", 200), width=4)
plate = font("JetBrainsMono.ttf", 74, wght=700)
d.text((px + 260, py + 75), "BK 1 OTO", font=plate, fill=hex_rgba("#F5F3EE"), anchor="mm")
d.text((px + 520, py + 190), "Kota Medan, Sumatera Utara", font=mono, fill=hex_rgba("#6B6F76"), anchor="ra")

og.resize((W, H), Image.LANCZOS).convert("RGB").save(os.path.join(OUT, "og-image.png"), optimize=True)

print("brand kit written to", OUT)
for f in sorted(os.listdir(OUT)):
    print(f"  {f:28s} {os.path.getsize(os.path.join(OUT, f)):>8d} bytes")
