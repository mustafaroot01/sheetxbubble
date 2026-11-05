import cv2
import numpy as np
import base64
import math

# ─── Sheet template constants (must match sheet_generator.py) ────────
# ReportLab points: 1 pt = 1/72 inch, 1 cm = 28.3465 pt, 1 mm = 2.83465 pt
PT = 1.0
MM = 2.83465
CM = 28.3465

A4_W = 595.0
A4_H = 842.0
MX = 0.8 * CM
MT = 0.6 * CM
MB = 0.6 * CM
UW = A4_W - 2 * MX

ROW_H = 7.8 * MM
BUBBLE_R = 3.5 * MM
BUBBLE_GAP = 11.0 * MM
NUM_W = 10 * MM
COL_GAP = 5 * MM
COL_HDR_H = 6.5 * MM
INST_H = 6 * MM
FOOTER_H = 4 * MM
CONT_HDR_H = 18 * MM
INFO_ROW_H = 6.5 * MM
QR_SIZE = 2.6 * CM
TITLE_H = 1.0 * CM
MARKER_SIZE = 4 * MM
MARKER_OFFSET = 1.5 * MM


def _header_h_default():
    """Header height for default design (all 6 info fields shown)."""
    rows = math.ceil(6 / 3)
    h = TITLE_H + rows * INFO_ROW_H + 1 * MM
    return max(h, QR_SIZE + 6 * MM)


def _grid_rows_first():
    """Max question rows per column on first page (default design)."""
    top = A4_H - MT - _header_h_default() - 1 * MM
    top -= INST_H + 0.5 * MM
    top -= COL_HDR_H
    bot = MB + FOOTER_H + 1 * MM
    return max(1, int((top - bot) / ROW_H))


def _compute_bubble_positions(img_w, img_h, question_count, choices_count):
    """Compute pixel coordinates of every bubble center based on template layout.

    Returns list of (question_index, choice_index, px_x, px_y) for each bubble.
    Coordinates are in pixel space of the aligned image.
    """
    scale_x = img_w / A4_W
    scale_y = img_h / A4_H

    rpp_first = _grid_rows_first()
    ncols = 1 if question_count < 2 else (3 if question_count > rpp_first * 2 else 2)
    col_w = (UW - (ncols - 1) * COL_GAP) / ncols

    gap = BUBBLE_GAP
    if choices_count > 1:
        gap = min(BUBBLE_GAP, (col_w - NUM_W - 2 * BUBBLE_R - 3 * MM) / (choices_count - 1))
    bubbles_total_w = (choices_count - 1) * gap

    # Grid top in PDF coords (origin bottom-left)
    grid_top_pdf = A4_H - MT - _header_h_default() - 1 * MM - (INST_H + 0.5 * MM)

    positions = []
    qs_per_col = math.ceil(question_count / ncols)

    for ci in range(ncols):
        cx_pdf = MX + ci * (col_w + COL_GAP)
        bubbles_start_pdf = cx_pdf + NUM_W + (col_w - NUM_W - bubbles_total_w) / 2

        for row in range(qs_per_col):
            q_idx = ci * qs_per_col + row
            if q_idx >= question_count:
                break

            # PDF y: top of row, then center
            ry_top_pdf = grid_top_pdf - COL_HDR_H - row * ROW_H
            cy_pdf = ry_top_pdf - ROW_H / 2

            # Convert PDF coords (bottom-left origin) to pixel coords (top-left origin)
            cy_px = int((A4_H - cy_pdf) * scale_y)

            for bi in range(choices_count):
                bx_pdf = bubbles_start_pdf + bi * gap
                bx_px = int(bx_pdf * scale_x)
                positions.append((q_idx, bi, bx_px, cy_px))

    return positions


# ─── Alignment ───────────────────────────────────────────────────────

def _order_points(pts):
    rect = np.zeros((4, 2), dtype='float32')
    s = pts.sum(axis=1)
    rect[0] = pts[np.argmin(s)]
    rect[2] = pts[np.argmax(s)]
    d = np.diff(pts, axis=1)
    rect[1] = pts[np.argmin(d)]
    rect[3] = pts[np.argmax(d)]
    return rect


def _markers_from_binary(binary, w, h, min_area, max_area):
    contours, _ = cv2.findContours(binary, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)

    candidates = []
    for cnt in contours:
        area = cv2.contourArea(cnt)
        if area < min_area or area > max_area:
            continue
        x, y, bw, bh = cv2.boundingRect(cnt)
        aspect = bw / float(bh) if bh > 0 else 0
        if 0.55 <= aspect <= 1.8:
            solidity = area / (bw * bh) if bw * bh > 0 else 0
            if solidity > 0.65:
                cx, cy = x + bw / 2.0, y + bh / 2.0
                candidates.append((cx, cy, area))

    if len(candidates) < 4:
        return None

    img_corners = [(0, 0), (w, 0), (w, h), (0, h)]
    markers = []
    used = set()
    for corner in img_corners:
        best_idx = -1
        best_dist = float('inf')
        for i, (cx, cy, _) in enumerate(candidates):
            if i in used:
                continue
            d = (cx - corner[0]) ** 2 + (cy - corner[1]) ** 2
            if d < best_dist:
                best_dist = d
                best_idx = i
        if best_idx >= 0:
            markers.append(candidates[best_idx][:2])
            used.add(best_idx)

    if len(markers) != 4:
        return None
    return _order_points(np.array(markers, dtype='float32'))


def _find_corner_markers(gray):
    blurred = cv2.GaussianBlur(gray, (5, 5), 0)
    h, w = gray.shape
    min_area = (w * h) * 0.00003
    max_area = (w * h) * 0.01

    # Try several thresholds (Otsu + fixed levels) so varying phone-photo
    # lighting still yields the four corner markers.
    otsu_t, _ = cv2.threshold(blurred, 0, 255, cv2.THRESH_BINARY + cv2.THRESH_OTSU)
    for t in [int(otsu_t), 60, 90, 120, 150]:
        _, binary = cv2.threshold(blurred, t, 255, cv2.THRESH_BINARY_INV)
        markers = _markers_from_binary(binary, w, h, min_area, max_area)
        if markers is not None:
            return markers

    return None


def _align_sheet(image):
    """Align image using corner markers. Warps to A4 proportional output."""
    gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY) if len(image.shape) == 3 else image.copy()
    markers = _find_corner_markers(gray)
    if markers is None:
        return image, False

    # Marker centers in PDF space
    mk_cx = MX + MARKER_OFFSET + MARKER_SIZE / 2
    mk_cy_top = A4_H - MT - MARKER_OFFSET - MARKER_SIZE / 2
    mk_cy_bot = MB + MARKER_OFFSET + MARKER_SIZE / 2
    mk_rx = A4_W - MX - MARKER_OFFSET - MARKER_SIZE / 2

    # Target size: scale A4 to reasonable pixel dimensions (900px wide = faster processing)
    target_w = 900
    target_h = int(target_w * A4_H / A4_W)
    sx = target_w / A4_W
    sy = target_h / A4_H

    # Destination points: where markers SHOULD be in output
    dst = np.array([
        [mk_cx * sx, (A4_H - mk_cy_top) * sy],  # TL
        [mk_rx * sx, (A4_H - mk_cy_top) * sy],   # TR
        [mk_rx * sx, (A4_H - mk_cy_bot) * sy],   # BR
        [mk_cx * sx, (A4_H - mk_cy_bot) * sy],   # BL
    ], dtype='float32')

    M = cv2.getPerspectiveTransform(markers, dst)
    aligned = cv2.warpPerspective(image, M, (target_w, target_h), flags=cv2.INTER_LINEAR)
    return aligned, True


# ─── Template-based bubble reading ──────────────────────────────────

def _read_at_positions(thresh, positions, choices_count, bubble_r_px):
    """Read bubble fill levels at computed positions. Returns dict: q_idx -> best choice."""
    choice_labels = list('ABCDEF')[:choices_count]
    q_fills = {}

    # Sample radius: 110% of bubble radius captures lightly-filled bubbles
    # while still avoiding adjacent bubbles (gap = ~54px, r ≈ 16px → safe).
    r = max(5, int(bubble_r_px * 1.10))
    # Local search radius: snap each position to the darkest nearby spot
    # within ±snap_r pixels. Handles small scale/alignment errors.
    snap_r = max(3, int(bubble_r_px * 0.5))

    for q_idx, bi, bx, by in positions:
        # Find the sub-region centre with the highest fill in a small window
        best_fill = -1.0
        best_cx, best_cy = bx, by
        for dy in range(-snap_r, snap_r + 1, 3):
            for dx in range(-snap_r, snap_r + 1, 3):
                cx2, cy2 = bx + dx, by + dy
                x1 = max(0, cx2 - r)
                y1 = max(0, cy2 - r)
                x2 = min(thresh.shape[1], cx2 + r)
                y2 = min(thresh.shape[0], cy2 + r)
                if x2 <= x1 or y2 <= y1:
                    continue
                roi = thresh[y1:y2, x1:x2]
                total = roi.shape[0] * roi.shape[1]
                f = cv2.countNonZero(roi) / total if total > 0 else 0
                if f > best_fill:
                    best_fill = f
                    best_cx, best_cy = cx2, cy2

        fill_ratio = best_fill
        if q_idx not in q_fills:
            q_fills[q_idx] = []
        q_fills[q_idx].append((bi, fill_ratio, fill_ratio, best_cx, best_cy))

    answers = {}
    fill_debug = {}

    for q_idx in sorted(q_fills.keys()):
        ranked = sorted(q_fills[q_idx], key=lambda x: x[1], reverse=True)
        top = ranked[0]
        second_ratio = ranked[1][1] if len(ranked) > 1 else 0.0
        gap = top[1] - second_ratio

        # Always pick the highest-fill bubble (best-effort).
        # We only abstain if the top bubble has essentially zero fill
        # (the question was left blank — all choices look the same).
        bi = top[0]
        if top[1] >= 0.02:
            best_choice = choice_labels[bi] if bi < len(choice_labels) else None
            best_pos = (top[3], top[4])
        else:
            best_choice = None
            best_pos = None

        answers[q_idx] = (best_choice, best_pos)
        fill_debug[q_idx] = {
            'top_fill': round(top[1], 3),
            'gap': round(gap, 3),
            'choice': best_choice,
        }

    return answers, fill_debug


# ─── Fallback: contour-based reading (for sheets without markers) ───

def _read_contour_based(thresh, question_count, choices_count):
    """Original contour-based bubble detection as fallback."""
    contours, _ = cv2.findContours(thresh, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)

    bubbles = []
    for contour in contours:
        (x, y, w, h) = cv2.boundingRect(contour)
        aspect_ratio = w / float(h) if h > 0 else 0
        if 0.7 <= aspect_ratio <= 1.3 and 15 <= w <= 60 and 15 <= h <= 60:
            area = cv2.contourArea(contour)
            if area > 200:
                bubbles.append((x, y, w, h, contour))

    if len(bubbles) < question_count:
        return None

    bubbles.sort(key=lambda b: (b[1], b[0]))
    choice_labels = list('ABCDEF')[:choices_count]
    rows = _group_into_rows(bubbles, question_count)
    answers = {}

    for q_idx, row in enumerate(rows):
        row.sort(key=lambda b: b[0])
        best_choice = None
        best_fill = 0
        best_pos = None

        for idx, bubble in enumerate(row[:choices_count]):
            x, y, w, h = bubble[0], bubble[1], bubble[2], bubble[3]
            roi = thresh[y:y+h, x:x+w]
            fill = cv2.countNonZero(roi)
            total = w * h
            fill_ratio = fill / total if total > 0 else 0

            if fill_ratio > 0.4 and fill > best_fill:
                best_fill = fill
                best_choice = choice_labels[idx] if idx < len(choice_labels) else None
                best_pos = (x + w // 2, y + h // 2)

        answers[q_idx] = (best_choice, best_pos)

    return answers


def _group_into_rows(bubbles, question_count):
    if not bubbles:
        return []
    bubbles.sort(key=lambda b: b[1])
    rows = []
    current_row = [bubbles[0]]
    for bubble in bubbles[1:]:
        if abs(bubble[1] - current_row[0][1]) < 20:
            current_row.append(bubble)
        else:
            rows.append(current_row)
            current_row = [bubble]
    rows.append(current_row)
    return rows[:question_count]


# ─── Public API ──────────────────────────────────────────────────────

def _best_thresh(gray):
    """Return the binary threshold that best separates filled bubbles.

    Tries adaptive threshold (good for uneven lighting) and Otsu
    (good for uniform backgrounds) and returns both so callers can
    pick or combine them.
    """
    clahe = cv2.createCLAHE(clipLimit=2.0, tileGridSize=(8, 8))
    enhanced = clahe.apply(gray)
    blurred = cv2.GaussianBlur(enhanced, (5, 5), 0)
    adaptive = cv2.adaptiveThreshold(
        blurred, 255, cv2.ADAPTIVE_THRESH_GAUSSIAN_C,
        cv2.THRESH_BINARY_INV, 15, 4
    )
    _, otsu = cv2.threshold(blurred, 0, 255, cv2.THRESH_BINARY_INV + cv2.THRESH_OTSU)
    # Combine: a pixel is "filled" if either method says so — maximises recall
    combined = cv2.bitwise_or(adaptive, otsu)
    return combined


def read_bubbles(image_path, question_count, choices_count=4):
    image = cv2.imread(image_path)
    if image is None:
        return None

    aligned, has_markers = _align_sheet(image)
    gray = cv2.cvtColor(aligned, cv2.COLOR_BGR2GRAY)
    thresh = _best_thresh(gray)

    answers_dict = None
    if has_markers:
        img_h, img_w = aligned.shape[:2]
        positions = _compute_bubble_positions(img_w, img_h, question_count, choices_count)
        bubble_r_px = int(BUBBLE_R * (img_w / A4_W))
        answers_dict, _ = _read_at_positions(thresh, positions, choices_count, bubble_r_px)

    if not answers_dict:
        answers_dict = _read_contour_based(thresh, question_count, choices_count)

    if not answers_dict:
        return [None] * question_count

    return [answers_dict.get(i, (None, None))[0] for i in range(question_count)]


def read_bubbles_with_overlay(image_path, question_count, choices_count=4, model_answer=None):
    """Read bubbles and return (answers, overlay_image, debug)."""
    image = cv2.imread(image_path)
    if image is None:
        return None, None, {'has_markers': False, 'detected': 0}

    aligned, has_markers = _align_sheet(image)
    overlay = aligned.copy()
    gray = cv2.cvtColor(aligned, cv2.COLOR_BGR2GRAY)
    thresh = _best_thresh(gray)

    answers_dict = None
    bubble_positions = None
    fill_debug = {}

    if has_markers:
        img_h, img_w = aligned.shape[:2]
        positions = _compute_bubble_positions(img_w, img_h, question_count, choices_count)
        bubble_r_px = int(BUBBLE_R * (img_w / A4_W))
        answers_dict, fill_debug = _read_at_positions(thresh, positions, choices_count, bubble_r_px)
        # Build lookup: (q_idx, choice_idx) -> (px_x, px_y)
        bubble_positions = {}
        for q_idx, bi, bx, by in positions:
            bubble_positions[(q_idx, bi)] = (bx, by)

    if not answers_dict:
        answers_dict = _read_contour_based(thresh, question_count, choices_count)

    if not answers_dict:
        return [None] * question_count, None, {'has_markers': has_markers, 'detected': 0}

    choice_labels = list('ABCDEF')[:choices_count]
    GREEN = (0, 200, 0)
    RED = (0, 0, 220)
    GRAY = (160, 160, 160)
    YELLOW = (0, 200, 220)
    THICKNESS = 2
    img_h, img_w = overlay.shape[:2]
    r_draw = max(int(BUBBLE_R * (img_w / A4_W)), 6)

    # Draw ALL bubble positions as gray circles first — lets you see
    # immediately if the template grid is aligned with the actual bubbles.
    if bubble_positions:
        for (q_idx, bi), (bx, by) in bubble_positions.items():
            cv2.circle(overlay, (bx, by), r_draw, GRAY, 1)

    answers = []
    for q_idx in range(question_count):
        best_choice, best_pos = answers_dict.get(q_idx, (None, None))
        answers.append(best_choice)

        if model_answer and q_idx < len(model_answer) and best_pos:
            cx, cy = best_pos
            is_correct = best_choice == model_answer[q_idx]
            color = GREEN if is_correct else RED
            cv2.circle(overlay, (cx, cy), r_draw + 4, color, THICKNESS)

            if is_correct:
                p1 = (cx - 7, cy + 2)
                p2 = (cx - 2, cy + 7)
                p3 = (cx + 8, cy - 5)
                cv2.line(overlay, p1, p2, GREEN, 2)
                cv2.line(overlay, p2, p3, GREEN, 2)
            else:
                cv2.line(overlay, (cx - 6, cy - 6), (cx + 6, cy + 6), RED, 2)
                cv2.line(overlay, (cx + 6, cy - 6), (cx - 6, cy + 6), RED, 2)

                # Highlight the correct answer position in green
                if q_idx < len(model_answer) and model_answer[q_idx] in choice_labels:
                    correct_bi = choice_labels.index(model_answer[q_idx])
                    cpos = None
                    if bubble_positions and (q_idx, correct_bi) in bubble_positions:
                        cpos = bubble_positions[(q_idx, correct_bi)]
                    if cpos:
                        cv2.circle(overlay, cpos, r_draw + 4, GREEN, 2)
        elif best_pos:
            # No model answer — just show detected choice in yellow
            cv2.circle(overlay, best_pos, r_draw + 3, YELLOW, 2)

    _, buffer = cv2.imencode('.jpg', overlay, [cv2.IMWRITE_JPEG_QUALITY, 85])
    overlay_b64 = base64.b64encode(buffer).decode('utf-8')

    detected = sum(1 for a in answers if a is not None)
    return answers, overlay_b64, {
        'has_markers': has_markers,
        'detected': detected,
        'fills': fill_debug,
    }
