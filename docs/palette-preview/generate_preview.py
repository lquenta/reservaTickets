"""Genera mockups y documento Word/PDF con propuestas de paleta NOVA."""
from __future__ import annotations

import math
from pathlib import Path

from docx import Document
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.oxml.ns import qn
from docx.shared import Inches, Pt, RGBColor
from fpdf import FPDF
from PIL import Image, ImageDraw, ImageFilter, ImageFont

ROOT = Path(__file__).resolve().parent
BG = ROOT / "fondo-nova.png"
OUT_DOCX = ROOT / "NOVA-propuestas-paleta.docx"
OUT_PDF = ROOT / "NOVA-propuestas-paleta.pdf"

# Paletas
CURRENT = {
    "id": "actual",
    "name": "Actual (Stranger Things / Rojo Netflix)",
    "tagline": "Paleta vigente en el sitio",
    "bg": "#0a0a0a",
    "bg_soft": "#1a0505",
    "surface": "#111111",
    "primary": "#e50914",
    "primary_hover": "#b20710",
    "secondary": "#7f1d1d",
    "accent": "#fecaca",
    "border": "#7f1d1d",
    "text": "#ffffff",
    "muted": "#a3a3a3",
}

PROP_A = {
    "id": "propuesta-a",
    "name": "Propuesta A — Magenta Nebula",
    "tagline": "Magenta dominante + acentos cyan (fiel al fondo)",
    "bg": "#07040f",
    "bg_soft": "#14081f",
    "surface": "#12081c",
    "primary": "#e11d8a",  # magenta/fuchsia del fondo
    "primary_hover": "#c01674",
    "secondary": "#7c3aed",  # violet
    "accent": "#22d3ee",  # cyan
    "border": "#6b21a8",
    "text": "#ffffff",
    "muted": "#c4b5d4",
}

PROP_B = {
    "id": "propuesta-b",
    "name": "Propuesta B — Violet Cosmos",
    "tagline": "Violeta como marca + magenta CTA + cyan highlights",
    "bg": "#06040e",
    "bg_soft": "#110820",
    "surface": "#0f0a1a",
    "primary": "#8b5cf6",  # violet marca
    "primary_hover": "#7c3aed",
    "secondary": "#d946ef",  # fuchsia secundaria
    "accent": "#2dd4bf",  # teal/cyan
    "border": "#5b21b6",
    "text": "#f8fafc",
    "muted": "#b8a9d4",
}


def hex_to_rgb(h: str) -> tuple[int, int, int]:
    h = h.lstrip("#")
    return tuple(int(h[i : i + 2], 16) for i in (0, 2, 4))


def rgba(h: str, a: int) -> tuple[int, int, int, int]:
    r, g, b = hex_to_rgb(h)
    return (r, g, b, a)


def try_font(size: int, bold: bool = False) -> ImageFont.FreeTypeFont | ImageFont.ImageFont:
    candidates = [
        "C:/Windows/Fonts/arialbd.ttf" if bold else "C:/Windows/Fonts/arial.ttf",
        "C:/Windows/Fonts/segoeuib.ttf" if bold else "C:/Windows/Fonts/segoeui.ttf",
        "C:/Windows/Fonts/calibrib.ttf" if bold else "C:/Windows/Fonts/calibri.ttf",
    ]
    for path in candidates:
        try:
            return ImageFont.truetype(path, size)
        except OSError:
            continue
    return ImageFont.load_default()


def load_bg(size: tuple[int, int]) -> Image.Image:
    if BG.exists():
        img = Image.open(BG).convert("RGB")
        img = img.resize(size, Image.Resampling.LANCZOS)
        # darken slightly for UI readability
        overlay = Image.new("RGB", size, (5, 3, 12))
        return Image.blend(img, overlay, 0.45)
    return Image.new("RGB", size, (7, 4, 15))


def draw_rounded_rect(draw: ImageDraw.ImageDraw, box, fill, radius=12, outline=None, width=1):
    draw.rounded_rectangle(box, radius=radius, fill=fill, outline=outline, width=width)


def make_swatches(palette: dict, path: Path) -> Path:
    w, h = 900, 160
    img = Image.new("RGB", (w, h), hex_to_rgb(palette["bg"]))
    draw = ImageDraw.Draw(img)
    keys = [
        ("bg", "Fondo"),
        ("bg_soft", "Fondo soft"),
        ("primary", "Primario"),
        ("secondary", "Secundario"),
        ("accent", "Acento"),
        ("border", "Borde"),
        ("muted", "Muted"),
        ("text", "Texto"),
    ]
    gap = 10
    box_w = (w - gap * (len(keys) + 1)) // len(keys)
    font = try_font(12, True)
    font_s = try_font(11)
    for i, (key, label) in enumerate(keys):
        x0 = gap + i * (box_w + gap)
        y0 = 16
        color = hex_to_rgb(palette[key])
        draw_rounded_rect(draw, (x0, y0, x0 + box_w, y0 + 88), color, radius=10)
        # label bg
        draw_rounded_rect(draw, (x0, y0 + 96, x0 + box_w, h - 12), hex_to_rgb(palette["surface"]), radius=8)
        tw = draw.textlength(label, font=font)
        draw.text((x0 + (box_w - tw) / 2, y0 + 100), label, fill=hex_to_rgb(palette["text"]), font=font)
        hex_label = palette[key].upper()
        tw2 = draw.textlength(hex_label, font=font_s)
        draw.text((x0 + (box_w - tw2) / 2, y0 + 118), hex_label, fill=hex_to_rgb(palette["muted"]), font=font_s)
    img.save(path, quality=95)
    return path


def make_mockup(palette: dict, path: Path) -> Path:
    W, H = 1080, 720
    bg = load_bg((W, H))
    # vignette
    vignette = Image.new("RGBA", (W, H), (0, 0, 0, 0))
    vd = ImageDraw.Draw(vignette)
    for i in range(80):
        a = int(180 * (i / 80) ** 1.6)
        vd.rectangle((i, i, W - 1 - i, H - 1 - i), outline=(0, 0, 0, a))
    base = bg.convert("RGBA")
    base = Image.alpha_composite(base, vignette)

    # soft color wash matching primary
    wash = Image.new("RGBA", (W, H), (0, 0, 0, 0))
    wd = ImageDraw.Draw(wash)
    pr = rgba(palette["primary"], 55)
    ac = rgba(palette["accent"], 40)
    wd.ellipse((-120, 80, 420, 520), fill=pr)
    wd.ellipse((700, 350, 1200, 850), fill=ac)
    wash = wash.filter(ImageFilter.GaussianBlur(60))
    base = Image.alpha_composite(base, wash)

    draw = ImageDraw.Draw(base)
    font_brand = try_font(28, True)
    font_nav = try_font(16)
    font_hero = try_font(92, True)
    font_sub = try_font(20)
    font_btn = try_font(18, True)
    font_card = try_font(18, True)
    font_small = try_font(14)

    primary = hex_to_rgb(palette["primary"])
    accent = hex_to_rgb(palette["accent"])
    text = hex_to_rgb(palette["text"])
    muted = hex_to_rgb(palette["muted"])
    surface = rgba(palette["surface"], 210)
    border = rgba(palette["border"], 200)

    # header bar
    draw.rounded_rectangle((24, 18, W - 24, 78), radius=14, fill=(0, 0, 0, 140), outline=border, width=1)
    draw.text((48, 34), "NOVA", fill=primary, font=font_brand)
    nav_items = ["Quiénes somos", "Eventos", "Contacto"]
    x = 200
    for item in nav_items:
        draw.text((x, 40), item, fill=muted, font=font_nav)
        x += draw.textlength(item, font=font_nav) + 28
    # CTA register
    btn_x1, btn_y1, btn_x2, btn_y2 = W - 200, 30, W - 48, 66
    draw.rounded_rectangle((btn_x1, btn_y1, btn_x2, btn_y2), radius=8, fill=primary)
    label = "Registrarse"
    tw = draw.textlength(label, font=font_btn)
    draw.text((btn_x1 + (btn_x2 - btn_x1 - tw) / 2, 38), label, fill=(255, 255, 255), font=font_btn)

    # hero title
    title = "NOVA"
    tw = draw.textlength(title, font=font_hero)
    tx = (W - tw) / 2
    # glow layers
    for blur_r, alpha in ((18, 90), (10, 140), (4, 200)):
        glow = Image.new("RGBA", (W, H), (0, 0, 0, 0))
        gd = ImageDraw.Draw(glow)
        gd.text((tx, 160), title, fill=(*primary, alpha), font=font_hero)
        glow = glow.filter(ImageFilter.GaussianBlur(blur_r))
        base = Image.alpha_composite(base, glow)
    draw = ImageDraw.Draw(base)
    draw.text((tx, 160), title, fill=primary, font=font_hero)

    subtitle = "Experiencias que dejan huella"
    tw = draw.textlength(subtitle, font=font_sub)
    draw.text(((W - tw) / 2, 270), subtitle, fill=muted, font=font_sub)

    # primary CTA
    cta = "VER EVENTOS"
    cw = 220
    cx1 = (W - cw) / 2
    cy1, cy2 = 320, 368
    draw.rounded_rectangle((cx1, cy1, cx1 + cw, cy2), radius=10, fill=primary)
    # accent outline glow
    draw.rounded_rectangle((cx1 - 2, cy1 - 2, cx1 + cw + 2, cy2 + 2), radius=12, outline=accent, width=1)
    tw = draw.textlength(cta, font=font_btn)
    draw.text((cx1 + (cw - tw) / 2, 334), cta, fill=(255, 255, 255), font=font_btn)

    # event cards row
    card_w, card_h = 280, 210
    cards = [
        ("Noche Estelar", "Vie 24 Jul · 21:00"),
        ("Cosmos Live", "Sáb 25 Jul · 20:30"),
        ("Nebula Party", "Dom 26 Jul · 19:00"),
    ]
    start_x = (W - (3 * card_w + 2 * 24)) / 2
    y = 420
    for i, (name, meta) in enumerate(cards):
        x0 = start_x + i * (card_w + 24)
        draw.rounded_rectangle((x0, y, x0 + card_w, y + card_h), radius=14, fill=surface, outline=border, width=2)
        # cover strip
        cover_color = hex_to_rgb(palette["secondary"] if i % 2 else palette["primary"])
        draw.rounded_rectangle((x0 + 12, y + 12, x0 + card_w - 12, y + 110), radius=10, fill=(*cover_color, 180))
        # accent chip
        draw.rounded_rectangle((x0 + 20, y + 20, x0 + 100, y + 44), radius=6, fill=rgba(palette["accent"], 220))
        draw.text((x0 + 30, y + 24), "DESTACADO", fill=(5, 10, 20), font=font_small)
        draw.text((x0 + 20, y + 128), name, fill=text, font=font_card)
        draw.text((x0 + 20, y + 156), meta, fill=muted, font=font_small)
        # link
        link = "Reservar →"
        draw.text((x0 + 20, y + 180), link, fill=primary, font=font_small)

    # footer note
    note = palette["name"]
    tw = draw.textlength(note, font=font_small)
    draw.text(((W - tw) / 2, H - 36), note, fill=muted, font=font_small)

    base.convert("RGB").save(path, quality=92)
    return path


def make_components(palette: dict, path: Path) -> Path:
    W, H = 900, 420
    img = Image.new("RGBA", (W, H), (*hex_to_rgb(palette["bg"]), 255))
    draw = ImageDraw.Draw(img)
    font_h = try_font(22, True)
    font = try_font(16)
    font_b = try_font(16, True)
    font_s = try_font(13)

    primary = hex_to_rgb(palette["primary"])
    secondary = hex_to_rgb(palette["secondary"])
    accent = hex_to_rgb(palette["accent"])
    text = hex_to_rgb(palette["text"])
    muted = hex_to_rgb(palette["muted"])
    border = hex_to_rgb(palette["border"])
    surface = hex_to_rgb(palette["surface"])

    draw.text((28, 20), "Componentes UI", fill=text, font=font_h)

    # buttons
    y = 70
    draw.text((28, y), "Botones", fill=muted, font=font_s)
    draw.rounded_rectangle((28, y + 28, 200, y + 72), radius=10, fill=primary)
    draw.text((68, y + 40), "Primario", fill=(255, 255, 255), font=font_b)

    draw.rounded_rectangle((220, y + 28, 400, y + 72), radius=10, outline=primary, width=2)
    draw.text((258, y + 40), "Outline", fill=primary, font=font_b)

    draw.rounded_rectangle((420, y + 28, 600, y + 72), radius=10, fill=secondary)
    draw.text((458, y + 40), "Secundario", fill=(255, 255, 255), font=font_b)

    draw.rounded_rectangle((620, y + 28, 800, y + 72), radius=10, fill=accent)
    draw.text((665, y + 40), "Acento", fill=(10, 15, 25), font=font_b)

    # inputs
    y = 170
    draw.text((28, y), "Formulario", fill=muted, font=font_s)
    draw.rounded_rectangle((28, y + 28, 420, y + 76), radius=10, fill=surface, outline=border, width=2)
    draw.text((44, y + 44), "nombre@correo.com", fill=muted, font=font)
    draw.rounded_rectangle((440, y + 28, 620, y + 76), radius=10, fill=surface, outline=primary, width=2)
    draw.text((456, y + 44), "Focus activo", fill=text, font=font)

    # chips / alerts
    y = 280
    draw.text((28, y), "Estados", fill=muted, font=font_s)
    draw.rounded_rectangle((28, y + 28, 250, y + 76), radius=10, fill=rgba(palette["primary"], 40), outline=primary, width=1)
    draw.text((48, y + 44), "Éxito / confirmación", fill=primary, font=font)

    draw.rounded_rectangle((270, y + 28, 500, y + 76), radius=10, fill=rgba(palette["accent"], 35), outline=accent, width=1)
    draw.text((290, y + 44), "Info / highlight", fill=accent, font=font)

    draw.rounded_rectangle((520, y + 28, 780, y + 76), radius=10, fill=surface, outline=border, width=1)
    draw.text((540, y + 44), "Borde / superficie", fill=muted, font=font)

    # link row
    draw.text((28, 380), "Enlace: ", fill=muted, font=font)
    draw.text((90, 380), "Reservar entradas →", fill=primary, font=font_b)
    draw.text((320, 380), "  ·  Highlight: ", fill=muted, font=font)
    draw.text((450, 380), "Disponible hoy", fill=accent, font=font_b)

    img.convert("RGB").save(path, quality=95)
    return path


def set_run_color(run, hex_color: str):
    r, g, b = hex_to_rgb(hex_color)
    run.font.color.rgb = RGBColor(r, g, b)


def add_heading(doc: Document, text: str, level=1):
    p = doc.add_heading(text, level=level)
    for run in p.runs:
        run.font.color.rgb = RGBColor(30, 20, 50)
    return p


def add_palette_section(doc: Document, palette: dict, mockup: Path, swatches: Path, components: Path):
    add_heading(doc, palette["name"], level=1)
    p = doc.add_paragraph(palette["tagline"])
    p.runs[0].italic = True

    # hex table as paragraph
    tokens = [
        ("Fondo", palette["bg"]),
        ("Fondo soft", palette["bg_soft"]),
        ("Primario", palette["primary"]),
        ("Secundario", palette["secondary"]),
        ("Acento", palette["accent"]),
        ("Borde", palette["border"]),
        ("Texto", palette["text"]),
        ("Muted", palette["muted"]),
    ]
    table = doc.add_table(rows=2, cols=len(tokens))
    table.style = "Table Grid"
    for i, (label, value) in enumerate(tokens):
        table.rows[0].cells[i].text = label
        table.rows[1].cells[i].text = value.upper()

    doc.add_paragraph()
    doc.add_picture(str(swatches), width=Inches(6.5))
    doc.add_paragraph("Mockup homepage (hero + eventos)")
    doc.add_picture(str(mockup), width=Inches(6.5))
    doc.add_paragraph("Componentes (botones, inputs, estados)")
    doc.add_picture(str(components), width=Inches(6.5))
    doc.add_page_break()


def build_docx(assets: dict):
    doc = Document()
    section = doc.sections[0]
    section.top_margin = Inches(0.7)
    section.bottom_margin = Inches(0.7)
    section.left_margin = Inches(0.75)
    section.right_margin = Inches(0.75)

    title = doc.add_heading("NOVA — Propuestas de paleta de color", level=0)
    for run in title.runs:
        run.font.color.rgb = RGBColor(88, 28, 135)

    intro = doc.add_paragraph()
    intro.add_run(
        "Documento de preview para aprobación. Se generaron 2 propuestas a partir del fondo de nebulosa adjunto "
        "(magenta / violeta / cyan sobre fondo cósmico oscuro), contrastadas con la paleta actual del sitio "
        "(rojo Netflix #E50914 estilo Stranger Things)."
    )

    doc.add_paragraph("Referencia visual — fondo propuesto:")
    if BG.exists():
        doc.add_picture(str(BG), width=Inches(6.5))

    bullets = doc.add_paragraph()
    bullets.add_run("Cómo leer este documento").bold = True
    for line in [
        "1. Revisar la paleta actual (página siguiente) como punto de partida.",
        "2. Comparar Propuesta A (Magenta Nebula) vs Propuesta B (Violet Cosmos).",
        "3. Elegir una para implementar en homepage, auth, checkout y admin público.",
        "Nota: los reportes PDF admin pueden mantener violeta actual o alinearse después.",
    ]:
        doc.add_paragraph(line, style="List Bullet")

    doc.add_page_break()

    add_palette_section(doc, CURRENT, assets["actual"]["mockup"], assets["actual"]["swatches"], assets["actual"]["components"])
    add_palette_section(doc, PROP_A, assets["propuesta-a"]["mockup"], assets["propuesta-a"]["swatches"], assets["propuesta-a"]["components"])
    add_palette_section(doc, PROP_B, assets["propuesta-b"]["mockup"], assets["propuesta-b"]["swatches"], assets["propuesta-b"]["components"])

    add_heading(doc, "Recomendación", level=1)
    rec = doc.add_paragraph()
    rec.add_run("Propuesta A — Magenta Nebula").bold = True
    doc.add_paragraph(
        "Es la que mejor refleja el fondo: magenta/fuchsia como color de marca y CTA, violeta como secundario "
        "de profundidad, y cyan para highlights (chips, focus, estados info). Mantiene el contraste alto y el "
        "carácter espectáculo del sitio, pero deja atrás el rojo Netflix."
    )
    doc.add_paragraph(
        "Propuesta B es más “producto / premium” (violeta marca + magenta como acento de acción). Úsala si "
        "quieres que NOVA se sienta más app/plataforma y menos cartel de evento."
    )

    add_heading(doc, "Aprobación", level=1)
    doc.add_paragraph("Marcar una opción:")
    doc.add_paragraph("☐ Aprobar Propuesta A — Magenta Nebula", style="List Bullet")
    doc.add_paragraph("☐ Aprobar Propuesta B — Violet Cosmos", style="List Bullet")
    doc.add_paragraph("☐ Solicitar ajustes (indicar hex o dirección abajo)", style="List Bullet")
    doc.add_paragraph()
    doc.add_paragraph("Comentarios / ajustes solicitados:")
    doc.add_paragraph("_" * 70)
    doc.add_paragraph("_" * 70)
    doc.add_paragraph()
    doc.add_paragraph("Fecha: _______________    Firma / OK: _______________")

    doc.save(OUT_DOCX)


class PalettePDF(FPDF):
    def footer(self):
        self.set_y(-15)
        self.set_font("Helvetica", "I", 9)
        self.set_text_color(120, 100, 140)
        self.cell(0, 10, f"NOVA · Propuestas de paleta · p. {self.page_no()}", align="C")


def build_pdf(assets: dict):
    pdf = PalettePDF(orientation="P", unit="mm", format="A4")
    pdf.set_auto_page_break(auto=True, margin=18)

    def title_page():
        pdf.add_page()
        pdf.set_fill_color(7, 4, 15)
        pdf.rect(0, 0, 210, 297, "F")
        if BG.exists():
            pdf.image(str(BG), x=0, y=0, w=210, h=120)
            pdf.set_fill_color(7, 4, 15)
            pdf.set_alpha = getattr(pdf, "set_alpha", None)
        pdf.set_y(130)
        pdf.set_text_color(225, 29, 138)
        pdf.set_font("Helvetica", "B", 28)
        pdf.cell(0, 14, "NOVA", align="C", new_x="LMARGIN", new_y="NEXT")
        pdf.set_text_color(255, 255, 255)
        pdf.set_font("Helvetica", "B", 18)
        pdf.cell(0, 10, "Propuestas de paleta de color", align="C", new_x="LMARGIN", new_y="NEXT")
        pdf.ln(6)
        pdf.set_text_color(196, 181, 212)
        pdf.set_font("Helvetica", "", 12)
        pdf.multi_cell(
            0,
            7,
            "Preview para aprobacion. Dos propuestas basadas en el fondo de nebulosa "
            "(magenta / violeta / cyan), vs la paleta actual rojo Netflix #E50914.",
            align="C",
        )
        pdf.ln(10)
        pdf.set_text_color(34, 211, 238)
        pdf.set_font("Helvetica", "B", 11)
        pdf.cell(0, 8, "Documento de decision de diseno", align="C", new_x="LMARGIN", new_y="NEXT")

    def palette_page(palette: dict, key: str):
        pdf.add_page()
        pdf.set_fill_color(*hex_to_rgb(palette["bg"]))
        pdf.rect(0, 0, 210, 18, "F")
        pdf.set_y(5)
        pdf.set_text_color(*hex_to_rgb(palette["primary"]))
        pdf.set_font("Helvetica", "B", 14)
        # Avoid non-latin fancy dashes for core fonts
        name = palette["name"].replace("—", "-").replace("–", "-")
        pdf.cell(0, 8, name, align="C", new_x="LMARGIN", new_y="NEXT")

        pdf.set_y(24)
        pdf.set_text_color(40, 30, 60)
        pdf.set_font("Helvetica", "I", 11)
        pdf.multi_cell(0, 6, palette["tagline"].replace("—", "-"))
        pdf.ln(2)

        # color chips as colored cells
        pdf.set_font("Helvetica", "B", 9)
        labels = [
            ("Fondo", "bg"),
            ("Soft", "bg_soft"),
            ("Primario", "primary"),
            ("Secund.", "secondary"),
            ("Acento", "accent"),
            ("Borde", "border"),
        ]
        chip_w = 30
        x0 = 15
        y0 = pdf.get_y()
        for i, (lab, k) in enumerate(labels):
            x = x0 + i * (chip_w + 3)
            rgb = hex_to_rgb(palette[k])
            pdf.set_fill_color(*rgb)
            pdf.set_xy(x, y0)
            pdf.cell(chip_w, 14, "", fill=True, border=0)
            pdf.set_xy(x, y0 + 15)
            pdf.set_text_color(60, 50, 80)
            pdf.set_font("Helvetica", "", 7)
            pdf.cell(chip_w, 4, lab, align="C", new_x="LMARGIN", new_y="TOP")
            pdf.set_xy(x, y0 + 19)
            pdf.cell(chip_w, 4, palette[k].upper(), align="C")

        pdf.set_y(y0 + 28)
        pdf.set_text_color(40, 30, 60)
        pdf.set_font("Helvetica", "B", 11)
        pdf.cell(0, 7, "Mockup homepage", new_x="LMARGIN", new_y="NEXT")
        pdf.image(str(assets[key]["mockup"]), x=15, w=180)
        pdf.ln(2)
        pdf.set_font("Helvetica", "B", 11)
        pdf.cell(0, 7, "Componentes UI", new_x="LMARGIN", new_y="NEXT")
        pdf.image(str(assets[key]["components"]), x=15, w=180)

    def closing():
        pdf.add_page()
        pdf.set_font("Helvetica", "B", 18)
        pdf.set_text_color(88, 28, 135)
        pdf.cell(0, 12, "Recomendacion y aprobacion", new_x="LMARGIN", new_y="NEXT")
        pdf.ln(4)
        pdf.set_text_color(30, 20, 50)
        pdf.set_font("Helvetica", "B", 12)
        pdf.cell(0, 8, "Recomendada: Propuesta A - Magenta Nebula", new_x="LMARGIN", new_y="NEXT")
        pdf.set_font("Helvetica", "", 11)
        pdf.multi_cell(
            0,
            6,
            "Mejor alineada con el fondo: magenta como marca/CTA, violeta de profundidad y cyan "
            "para highlights. Conserva el impacto visual del sitio sin el rojo Netflix.",
        )
        pdf.ln(3)
        pdf.multi_cell(
            0,
            6,
            "Propuesta B (Violet Cosmos): mas premium/producto. Ideal si NOVA debe sentirse "
            "plataforma digital mas que cartel de evento.",
        )
        pdf.ln(8)
        pdf.set_font("Helvetica", "B", 12)
        pdf.cell(0, 8, "Marcar una opcion:", new_x="LMARGIN", new_y="NEXT")
        pdf.set_font("Helvetica", "", 11)
        for opt in [
            "[ ] Aprobar Propuesta A - Magenta Nebula",
            "[ ] Aprobar Propuesta B - Violet Cosmos",
            "[ ] Solicitar ajustes (indicar abajo)",
        ]:
            pdf.cell(0, 8, opt, new_x="LMARGIN", new_y="NEXT")
        pdf.ln(6)
        pdf.cell(0, 8, "Comentarios:", new_x="LMARGIN", new_y="NEXT")
        pdf.set_draw_color(180, 160, 200)
        for _ in range(4):
            y = pdf.get_y()
            pdf.line(15, y + 8, 195, y + 8)
            pdf.ln(10)
        pdf.ln(4)
        pdf.cell(0, 8, "Fecha: _______________     OK / Firma: _______________", new_x="LMARGIN", new_y="NEXT")

    title_page()
    palette_page(CURRENT, "actual")
    palette_page(PROP_A, "propuesta-a")
    palette_page(PROP_B, "propuesta-b")
    closing()
    pdf.output(str(OUT_PDF))


def main():
    assets = {}
    for pal in (CURRENT, PROP_A, PROP_B):
        key = pal["id"]
        assets[key] = {
            "swatches": make_swatches(pal, ROOT / f"{key}-swatches.png"),
            "mockup": make_mockup(pal, ROOT / f"{key}-mockup.png"),
            "components": make_components(pal, ROOT / f"{key}-components.png"),
        }
        print(f"OK assets {key}")

    build_docx(assets)
    print(f"DOCX -> {OUT_DOCX}")
    build_pdf(assets)
    print(f"PDF  -> {OUT_PDF}")


if __name__ == "__main__":
    main()
