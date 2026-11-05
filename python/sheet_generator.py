import json
import math
import os
import tempfile
import qrcode
import arabic_reshaper
from bidi.algorithm import get_display
from io import BytesIO
from reportlab.lib.pagesizes import A4
from reportlab.lib.units import cm, mm
from reportlab.pdfgen import canvas
from reportlab.lib import colors
from reportlab.lib.utils import ImageReader
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont

FONT_DIR = os.path.join(os.path.dirname(__file__), 'fonts')

AR = 'Helvetica'
AR_B = 'Helvetica-Bold'
EN = 'Helvetica'
EN_B = 'Helvetica-Bold'

for name, filename in [('Amiri', 'Amiri-Regular.ttf'), ('AmiriBold', 'Amiri-Bold.ttf')]:
    p = os.path.join(FONT_DIR, filename)
    if os.path.exists(p):
        try:
            pdfmetrics.registerFont(TTFont(name, p))
            if name == 'Amiri':
                AR = 'Amiri'
            else:
                AR_B = 'AmiriBold'
        except Exception:
            pass

if AR == 'Helvetica':
    try:
        pdfmetrics.registerFont(TTFont('ArialUni', '/Library/Fonts/Arial Unicode.ttf'))
        AR = AR_B = 'ArialUni'
    except Exception:
        pass

DEFAULT_DESIGN = {
    'title': 'ورقة إجابة - Answer Sheet',
    'institution_name': None,
    'logo_path': None,
    'header_bg_color': '#1a1a2e',
    'header_text_color': '#ffffff',
    'accent_color': '#16213e',
    'border_color': '#333355',
    'row_alt_color': '#f4f4fa',
    'instructions': 'ظلل الدائرة بالكامل بقلم رصاص. لا تستخدم حبر. لتصحيح إجابة، امسح بالممحاة جيداً.',
    'show_instructions': True,
    'bubble_style': 'circle',
    'auto_columns_threshold': 25,
    'show_student_name': True,
    'show_student_id': True,
    'show_stage': True,
    'show_study_type': True,
    'show_subject': True,
    'show_date': True,
    'show_footer': True,
    'footer_text': 'نظام تصحيح البابل شيت - Bubble Sheet System',
}

W, H = A4
MX = 0.8 * cm
MT = 0.6 * cm
MB = 0.6 * cm
UW = W - 2 * MX

# Bubble layout — max 60 questions per sheet (2 cols × 30 rows)
MAX_QUESTIONS = 60
ROW_H = 7.8 * mm
BUBBLE_R = 3.5 * mm
BUBBLE_GAP = 11.0 * mm  # fixed gap between bubble centers
NUM_W = 10 * mm
COL_GAP = 5 * mm
COL_HDR_H = 6.5 * mm
INST_H = 6 * mm
FOOTER_H = 4 * mm
CONT_HDR_H = 18 * mm
INFO_ROW_H = 6.5 * mm


def ar_t(text):
    try:
        reshaped = arabic_reshaper.reshape(str(text))
        return get_display(reshaped)
    except Exception:
        return str(text)


def hc(h):
    try:
        return colors.HexColor(h)
    except Exception:
        return colors.HexColor('#333333')


def _make_qr(data_dict):
    # Format: E{exam_id}|S{student_id}|G{stage_id}|T{study_type_id}[|P{page}]
    # Uses numeric IDs instead of Arabic text to keep QR small and fast to read
    parts = ['E' + str(data_dict.get('exam_id', '')), 'S' + str(data_dict.get('student_id', ''))]
    if data_dict.get('stage_id'):
        parts.append('G' + str(data_dict['stage_id']))
    if data_dict.get('study_type_id'):
        parts.append('T' + str(data_dict['study_type_id']))
    if data_dict.get('page'):
        parts.append('P' + str(data_dict['page']))
    data_str = '|'.join(parts)

    qr_obj = qrcode.QRCode(
        version=1,
        error_correction=qrcode.constants.ERROR_CORRECT_H,
        box_size=10,
        border=4,
    )
    qr_obj.add_data(data_str)
    qr_obj.make(fit=True)
    img = qr_obj.make_image(fill_color='black', back_color='white')
    buf = BytesIO()
    img.save(buf, format='PNG')
    buf.seek(0)
    return ImageReader(buf)


def generate_sheets_pdf(data):
    students = data.get('students', [])
    exam_id = data.get('exam_id')
    subject_name = data.get('subject_name', '')
    stage = data.get('stage', '')
    stage_id = data.get('stage_id', '')
    study_type = data.get('study_type', '')
    study_type_id = data.get('study_type_id', '')
    exam_date = data.get('exam_date', '')
    question_count = min(int(data.get('question_count', 20)), MAX_QUESTIONS)
    choices_count = data.get('choices_count', 4)

    design = dict(DEFAULT_DESIGN)
    if data.get('design'):
        design.update({k: v for k, v in data['design'].items() if v is not None})

    temp_file = os.path.join(tempfile.gettempdir(), f'sheets_{exam_id}.pdf')
    c = canvas.Canvas(temp_file, pagesize=A4)

    for student in students:
        _render_student(c, student, exam_id, subject_name,
                        stage, stage_id, study_type, study_type_id, exam_date, question_count, choices_count, design)
    c.save()
    return temp_file


# ─── Header ──────────────────────────────────────────────────────────
QR_SIZE = 2.6 * cm
TITLE_H = 1.0 * cm


def _field_count(design):
    return sum(1 for k in ['show_student_name', 'show_student_id', 'show_stage',
                           'show_study_type', 'show_subject', 'show_date'] if design.get(k))


def _header_h(design):
    rows = math.ceil(_field_count(design) / 3) if _field_count(design) > 0 else 0
    h = TITLE_H + rows * INFO_ROW_H + 1 * mm
    return max(h, QR_SIZE + 6 * mm)


def _grid_rows(is_first, design):
    top = H - MT
    if is_first:
        top -= _header_h(design) + 1 * mm
        if design['show_instructions'] and design.get('instructions'):
            top -= INST_H + 0.5 * mm
    else:
        top -= CONT_HDR_H + 1 * mm
    top -= COL_HDR_H
    bot = MB + (FOOTER_H + 1 * mm if design['show_footer'] else 0)
    return max(1, int((top - bot) / ROW_H))


def _render_student(c, student, exam_id, subject_name,
                    stage, stage_id, study_type, study_type_id, exam_date, qcount, ccount, design):
    labels = list('ABCDEF')[:ccount]
    rpp_first = _grid_rows(True, design)
    rpp_cont = _grid_rows(False, design)

    # Always split right/left; add a third column when two can't hold everything on one page
    ncols = 1 if qcount < 2 else (3 if qcount > rpp_first * 2 else 2)
    col_w = (UW - (ncols - 1) * COL_GAP) / ncols
    qs_first = rpp_first * ncols
    qs_cont = rpp_cont * ncols

    total_pages = 1
    if qcount > qs_first:
        total_pages += math.ceil((qcount - qs_first) / qs_cont)

    q_idx = 0
    for pg in range(total_pages):
        first = pg == 0
        if first:
            y = _draw_header(c, student, exam_id, subject_name, stage, study_type, exam_date, pg + 1, total_pages, design, stage_id, study_type_id)
            if design['show_instructions'] and design.get('instructions'):
                y = _draw_instructions(c, y, design)
            max_rows = rpp_first
        else:
            y = _draw_cont_header(c, student, exam_id, subject_name, pg + 1, total_pages, design, stage_id, study_type_id)
            max_rows = rpp_cont

        _draw_outer_border(c, design)
        if design['show_footer']:
            _draw_footer(c, exam_id, exam_date, pg + 1, total_pages, design)

        qs_this = min(qcount - q_idx, max_rows * ncols)
        qs_per_col = math.ceil(qs_this / ncols)

        for ci in range(ncols):
            cx = MX + ci * (col_w + COL_GAP)
            gt = y - 0.5 * mm
            _draw_grid(c, cx, gt, col_w, qs_per_col,
                        q_idx + ci * qs_per_col, qcount, labels, ccount, design,
                        student.get('answers'))

        q_idx += qs_this
        c.showPage()


def _draw_header(c, student, exam_id, subject_name, stage, study_type, exam_date, pnum, ptotal, design, stage_id='', study_type_id=''):
    hh = _header_h(design)
    hy = H - MT - hh
    top = hy + hh

    # QR box: right side, spans full header height
    box = hh
    bx = W - MX - box
    c.setFillColor(colors.white)
    c.setStrokeColor(hc(design['border_color']))
    c.setLineWidth(0.8)
    c.roundRect(bx, hy, box, box, 3, fill=1, stroke=1)
    qr_img = _make_qr({'student_id': student['student_id'], 'exam_id': exam_id, 'stage_id': stage_id, 'study_type_id': study_type_id})
    qs = box - 4 * mm
    c.drawImage(qr_img, bx + 2 * mm, hy + 2 * mm, width=qs, height=qs)

    # Title band: top-left, ends before QR box
    left_w = bx - MX - 2 * mm
    ty = top - TITLE_H
    c.setFillColor(hc(design['header_bg_color']))
    c.roundRect(MX, ty, left_w, TITLE_H, 3, fill=1, stroke=0)

    c.setFillColor(hc(design['header_text_color']))
    c.setFont(AR_B, 13)
    c.drawRightString(MX + left_w - 4 * mm, ty + TITLE_H / 2 - 3.5, ar_t(design['title']))

    if design.get('institution_name'):
        c.setFont(AR, 8)
        c.drawString(MX + 4 * mm, ty + TITLE_H / 2 - 2.5, ar_t(design['institution_name']))
    elif ptotal > 1:
        c.setFont(EN, 7)
        c.drawString(MX + 4 * mm, ty + TITLE_H / 2 - 2.5, f'Page {pnum}/{ptotal}')

    # Info fields - 3 columns, below title band, left of QR
    fields = []
    if design['show_student_name']:
        fields.append(('الطالب', student['full_name']))
    if design['show_student_id']:
        fields.append(('الرقم', student['student_id']))
    if design['show_stage']:
        fields.append(('المرحلة', stage))
    if design['show_study_type']:
        fields.append(('الدراسة', study_type))
    if design['show_subject']:
        fields.append(('المادة', subject_name))
    if design['show_date']:
        fields.append(('التاريخ', exam_date))

    if fields:
        ncols_f = 3
        nrows_f = math.ceil(len(fields) / ncols_f)
        gap_f = 1 * mm
        row_h = (ty - 1 * mm - hy) / nrows_f
        single_fw = (left_w - (ncols_f - 1) * gap_f) / ncols_f
        label_w = 15 * mm
        val_w = single_fw - label_w

        # RTL: first field at the right
        for i, (lbl, val) in enumerate(fields):
            col = ncols_f - 1 - (i % ncols_f)
            row = i // ncols_f
            fx = MX + col * (single_fw + gap_f)
            fy = ty - 1 * mm - (row + 1) * row_h
            lx = fx + val_w

            c.setStrokeColor(hc(design['border_color']))
            c.setLineWidth(0.4)
            c.setFillColor(hc(design['accent_color']))
            c.rect(lx, fy, label_w, row_h, fill=1, stroke=1)
            c.setFillColor(colors.white)
            c.setFont(AR_B, 8)
            c.drawCentredString(lx + label_w / 2, fy + row_h / 2 - 2.5, ar_t(lbl))

            c.setFillColor(colors.white)
            c.rect(fx, fy, val_w, row_h, fill=1, stroke=1)
            c.setFillColor(hc(design['header_bg_color']))
            font_size = 8.5
            txt = ar_t(str(val))
            while font_size > 5 and pdfmetrics.stringWidth(txt, AR, font_size) > val_w - 3 * mm:
                font_size -= 0.5
            c.setFont(AR, font_size)
            c.drawCentredString(fx + val_w / 2, fy + row_h / 2 - 2.5, txt)

    return hy - 0.5 * mm


def _draw_cont_header(c, student, exam_id, subject_name, pnum, ptotal, design, stage_id='', study_type_id=''):
    hy = H - MT - CONT_HDR_H
    c.setFillColor(hc(design['header_bg_color']))
    c.roundRect(MX, hy, UW, CONT_HDR_H, 3, fill=1, stroke=0)

    c.setFillColor(hc(design['header_text_color']))
    c.setFont(AR, 9)
    text_y = hy + CONT_HDR_H - 6 * mm
    c.drawString(MX + 3 * mm, text_y,
                 ar_t(f'{student["full_name"]}  |  {student.get("student_id", "")}'))
    c.setFont(AR, 8)
    c.drawString(MX + 3 * mm, text_y - 5 * mm,
                 ar_t(f'{subject_name}  |  صفحة {pnum} من {ptotal}'))

    qr_img = _make_qr({'student_id': student['student_id'], 'exam_id': exam_id, 'stage_id': stage_id, 'study_type_id': study_type_id, 'page': pnum})
    qs = CONT_HDR_H - 3 * mm
    qx = W - MX - qs - 2 * mm
    qy = hy + 1.5 * mm
    c.setFillColor(colors.white)
    c.roundRect(qx - 1, qy - 1, qs + 2, qs + 2, 2, fill=1, stroke=0)
    c.drawImage(qr_img, qx, qy, width=qs, height=qs)

    return hy - 0.5 * mm


def _draw_instructions(c, y_top, design):
    iy = y_top - INST_H
    c.setFillColor(hc(design['row_alt_color']))
    c.setStrokeColor(hc(design['border_color']))
    c.setLineWidth(0.3)
    c.roundRect(MX, iy, UW, INST_H, 2, fill=1, stroke=1)
    c.setFillColor(hc(design['header_bg_color']))
    c.setFont(AR, 7.5)
    c.drawRightString(MX + UW - 3 * mm, iy + INST_H / 2 - 2.5, ar_t(design['instructions']))
    return iy - 0.5 * mm


# ─── Grid - compact bubbles ──────────────────────────────────────────
def _draw_grid(c, cx, gt, cw, rows_in_col, q_start, qcount, labels, ccount, design, answers=None):
    gap = BUBBLE_GAP
    if ccount > 1:
        gap = min(BUBBLE_GAP, (cw - NUM_W - 2 * BUBBLE_R - 3 * mm) / (ccount - 1))
    bubbles_total_w = (ccount - 1) * gap
    bubbles_start = cx + NUM_W + (cw - NUM_W - bubbles_total_w) / 2

    # Column header
    c.setFillColor(hc(design['accent_color']))
    c.rect(cx, gt - COL_HDR_H, cw, COL_HDR_H, fill=1, stroke=0)

    c.setFillColor(colors.white)
    c.setFont(EN_B, 8)
    c.drawCentredString(cx + NUM_W / 2, gt - COL_HDR_H / 2 - 2.5, '#')

    # Separator in header
    c.setStrokeColor(colors.white)
    c.setStrokeAlpha(0.25)
    c.setLineWidth(0.4)
    c.line(cx + NUM_W, gt - COL_HDR_H + 1, cx + NUM_W, gt - 1)
    c.setStrokeAlpha(1)

    # Choice labels — each in a small circle outline
    for i, lbl in enumerate(labels):
        bx = bubbles_start + i * gap
        c.setStrokeColor(colors.white)
        c.setFillColor(colors.white)
        c.setStrokeAlpha(0.5)
        c.circle(bx, gt - COL_HDR_H / 2, 3.5 * mm, stroke=1, fill=0)
        c.setStrokeAlpha(1)
        c.setFont(EN_B, 8)
        c.setFillColor(colors.white)
        c.drawCentredString(bx, gt - COL_HDR_H / 2 - 2.5, lbl)

    # Rows
    drawn = 0
    for row in range(rows_in_col):
        qn = q_start + row + 1
        if qn > qcount:
            break
        drawn += 1

        ry_top = gt - COL_HDR_H - row * ROW_H
        ry_bot = ry_top - ROW_H
        cy = ry_bot + ROW_H / 2

        # Alt bg
        if row % 2 == 0:
            c.setFillColor(hc(design['row_alt_color']))
            c.rect(cx, ry_bot, cw, ROW_H, fill=1, stroke=0)

        # Bottom line
        c.setStrokeColor(colors.HexColor('#dddde5'))
        c.setLineWidth(0.2)
        c.line(cx, ry_bot, cx + cw, ry_bot)

        # Number separator
        c.setStrokeColor(colors.HexColor('#ccccdd'))
        c.setLineWidth(0.2)
        c.line(cx + NUM_W, ry_bot, cx + NUM_W, ry_top)

        # Question number — filled circle badge
        badge_r = 3.0 * mm
        c.setFillColor(hc(design['accent_color']))
        c.circle(cx + NUM_W / 2, cy, badge_r, stroke=0, fill=1)
        c.setFillColor(colors.white)
        c.setFont(EN_B, 7 if qn < 10 else 6.5)
        c.drawCentredString(cx + NUM_W / 2, cy - 2.2, str(qn))

        chosen = answers[qn - 1] if answers and qn - 1 < len(answers) else None

        for bi, lbl in enumerate(labels):
            bx = bubbles_start + bi * gap
            by = cy
            filled = chosen == lbl

            c.setStrokeColor(hc(design['border_color']))
            c.setLineWidth(0.7)
            c.setFillColor(colors.black if filled else colors.white)
            if design['bubble_style'] == 'circle':
                c.circle(bx, by, BUBBLE_R, stroke=1, fill=1)
            else:
                c.roundRect(bx - BUBBLE_R, by - BUBBLE_R,
                            BUBBLE_R * 2, BUBBLE_R * 2, 1, stroke=1, fill=1)

            if filled:
                continue
            c.setFillColor(colors.HexColor('#8888aa'))
            c.setFont(EN, 5.5)
            c.drawCentredString(bx, by - 1.8, lbl)

    # Grid border
    if drawn > 0:
        gh = COL_HDR_H + drawn * ROW_H
        c.setStrokeColor(hc(design['border_color']))
        c.setLineWidth(0.6)
        c.roundRect(cx, gt - gh, cw, gh, 2)


MARKER_SIZE = 4 * mm
MARKER_OFFSET = 1.5 * mm


def _draw_corner_markers(c):
    """Draw 4 black square markers at sheet corners for image alignment."""
    positions = [
        (MX + MARKER_OFFSET, H - MT - MARKER_OFFSET - MARKER_SIZE),  # top-left
        (W - MX - MARKER_OFFSET - MARKER_SIZE, H - MT - MARKER_OFFSET - MARKER_SIZE),  # top-right
        (MX + MARKER_OFFSET, MB + MARKER_OFFSET),  # bottom-left
        (W - MX - MARKER_OFFSET - MARKER_SIZE, MB + MARKER_OFFSET),  # bottom-right
    ]
    c.setFillColor(colors.black)
    c.setStrokeColor(colors.black)
    c.setLineWidth(0)
    for x, y in positions:
        c.rect(x, y, MARKER_SIZE, MARKER_SIZE, fill=1, stroke=0)


def _draw_outer_border(c, design):
    _draw_corner_markers(c)
    c.setStrokeColor(hc(design['border_color']))
    c.setLineWidth(0.8)
    c.roundRect(MX - 2, MB - 2, UW + 4, H - MT - MB + 4, 3)


def _draw_footer(c, exam_id, exam_date, pnum, ptotal, design):
    fy = MB + 0.5 * mm
    c.setStrokeColor(colors.HexColor('#cccccc'))
    c.setLineWidth(0.2)
    c.line(MX + 2 * mm, fy + FOOTER_H, W - MX - 2 * mm, fy + FOOTER_H)
    c.setFillColor(colors.HexColor('#999999'))
    c.setFont(AR, 4.5)
    c.drawString(MX + 3 * mm, fy, ar_t(design['footer_text']))
    c.setFont(EN, 4.5)
    c.drawRightString(W - MX - 3 * mm, fy, f'Exam #{exam_id} | {exam_date} | Page {pnum}/{ptotal}')
