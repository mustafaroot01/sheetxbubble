import json
import cv2


def _parse_qr_data(raw_text):
    """Parse QR data - supports both new pipe format and legacy JSON."""
    # New format: E1|SSTU001|G1|T2|P1
    if '|' in raw_text and raw_text.startswith('E'):
        result = {}
        for part in raw_text.split('|'):
            if part.startswith('E'):
                result['exam_id'] = int(part[1:]) if part[1:].isdigit() else part[1:]
            elif part.startswith('S'):
                result['student_id'] = part[1:]
            elif part.startswith('G'):
                result['stage_id'] = int(part[1:]) if part[1:].isdigit() else part[1:]
            elif part.startswith('T'):
                result['study_type_id'] = int(part[1:]) if part[1:].isdigit() else part[1:]
            elif part.startswith('P'):
                result['page'] = int(part[1:]) if part[1:].isdigit() else part[1:]
        if 'exam_id' in result and 'student_id' in result:
            return result
        return None

    # Legacy JSON format
    try:
        data = json.loads(raw_text)
        if isinstance(data, dict) and 'exam_id' in data:
            return data
    except (json.JSONDecodeError, ValueError):
        pass

    return None


def read_qr_code(image_path):
    from pyzbar.pyzbar import decode
    image = cv2.imread(image_path)
    if image is None:
        return None

    # Try original image
    for obj in decode(image):
        if obj.type == 'QRCODE':
            result = _parse_qr_data(obj.data.decode('utf-8'))
            if result:
                return result

    # Try grayscale
    gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
    for obj in decode(gray):
        if obj.type == 'QRCODE':
            result = _parse_qr_data(obj.data.decode('utf-8'))
            if result:
                return result

    # Try with threshold (helps with screen-captured QR)
    _, thresh = cv2.threshold(gray, 127, 255, cv2.THRESH_BINARY)
    for obj in decode(thresh):
        if obj.type == 'QRCODE':
            result = _parse_qr_data(obj.data.decode('utf-8'))
            if result:
                return result

    return None
