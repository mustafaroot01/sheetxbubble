import json
from flask import Flask, request, jsonify, send_file
from flask_cors import CORS
from omr import read_bubbles, read_bubbles_with_overlay
from qr_reader import read_qr_code
from sheet_generator import generate_sheets_pdf

app = Flask(__name__)
CORS(app)


@app.route('/api/health', methods=['GET'])
def health():
    return jsonify({
        'success': True,
        'message': 'Python OMR service is running',
        'data': {
            'version': '1.0.0',
            'status': 'healthy'
        }
    })


@app.route('/api/read-qr', methods=['POST'])
def read_qr():
    """Fast endpoint that ONLY reads the QR code — no bubble processing."""
    if 'image' not in request.files:
        return jsonify({'success': False, 'message': 'No image file provided'}), 422

    image_file = request.files['image']
    import tempfile, os
    temp_path = os.path.join(tempfile.gettempdir(), 'qr_' + image_file.filename)
    image_file.save(temp_path)

    try:
        qr_data = read_qr_code(temp_path)
        if not qr_data:
            return jsonify({'success': False, 'message': 'QR code not detected'}), 422
        return jsonify({'success': True, 'data': {'qr_data': qr_data}})
    except Exception as e:
        return jsonify({'success': False, 'message': str(e)}), 500
    finally:
        if os.path.exists(temp_path):
            os.remove(temp_path)


@app.route('/api/correct', methods=['POST'])
def correct():
    if 'image' not in request.files:
        return jsonify({
            'success': False,
            'message': 'No image file provided',
            'error': {'code': 'IMAGE_INVALID', 'details': 'Request must include an image file'}
        }), 422

    image_file = request.files['image']
    model_answer = json.loads(request.form.get('model_answer', '[]'))
    question_count = int(request.form.get('question_count', 20))
    total_mark = float(request.form.get('total_mark', 100))

    import tempfile
    import os
    temp_path = os.path.join(tempfile.gettempdir(), 'sheet_' + image_file.filename)
    image_file.save(temp_path)

    try:
        qr_data = read_qr_code(temp_path)
        if not qr_data:
            return jsonify({
                'success': False,
                'message': 'QR code not detected in image',
                'error': {'code': 'QR_NOT_FOUND', 'details': 'Could not find a valid QR code in the uploaded image'}
            }), 422

        answers = read_bubbles(temp_path, question_count)
        if not answers:
            return jsonify({
                'success': False,
                'message': 'Could not detect bubble sheet',
                'error': {'code': 'SHEET_NOT_DETECTED', 'details': 'Could not detect bubble marks in the image'}
            }), 422

        mark_per_q = total_mark / question_count if question_count > 0 else 0
        correct_count = 0
        details = []

        for i in range(question_count):
            student_ans = answers[i] if i < len(answers) else None
            correct_ans = model_answer[i] if i < len(model_answer) else None
            is_correct = student_ans == correct_ans

            if is_correct:
                correct_count += 1

            details.append({
                'q': i + 1,
                'student_answer': student_ans,
                'correct_answer': correct_ans,
                'is_correct': is_correct,
                'mark': mark_per_q,
                'earned': mark_per_q if is_correct else 0
            })

        wrong_count = question_count - correct_count
        score = correct_count * mark_per_q
        percentage = (score / total_mark * 100) if total_mark > 0 else 0

        return jsonify({
            'success': True,
            'message': 'Sheet corrected successfully',
            'data': {
                'qr_data': qr_data,
                'answers': answers,
                'correct_count': correct_count,
                'wrong_count': wrong_count,
                'score': round(score, 2),
                'total_mark': total_mark,
                'percentage': round(percentage, 2),
                'details': details
            }
        })

    except Exception as e:
        return jsonify({
            'success': False,
            'message': str(e),
            'error': {'code': 'CORRECTION_FAILED', 'details': str(e)}
        }), 500

    finally:
        if os.path.exists(temp_path):
            os.remove(temp_path)


@app.route('/api/correct-scan', methods=['POST'])
def correct_scan():
    """Correction endpoint optimized for camera scan - returns overlay image."""
    if 'image' not in request.files:
        return jsonify({
            'success': False,
            'message': 'No image file provided',
        }), 422

    image_file = request.files['image']
    model_answer = json.loads(request.form.get('model_answer', '[]'))
    question_count = int(request.form.get('question_count', 20))
    choices_count = int(request.form.get('choices_count', 4))
    total_mark = float(request.form.get('total_mark', 100))

    import tempfile
    import os
    temp_path = os.path.join(tempfile.gettempdir(), 'scan_' + image_file.filename)
    image_file.save(temp_path)

    try:
        qr_data = read_qr_code(temp_path)

        answers, overlay_b64 = read_bubbles_with_overlay(
            temp_path, question_count, choices_count, model_answer
        )

        if not answers:
            return jsonify({
                'success': False,
                'message': 'Could not detect bubble sheet',
            }), 422

        mark_per_q = total_mark / question_count if question_count > 0 else 0
        correct_count = 0
        details = []

        for i in range(question_count):
            student_ans = answers[i] if i < len(answers) else None
            correct_ans = model_answer[i] if i < len(model_answer) else None
            is_correct = student_ans == correct_ans
            if is_correct:
                correct_count += 1
            details.append({
                'q': i + 1,
                'student_answer': student_ans,
                'correct_answer': correct_ans,
                'is_correct': is_correct,
                'mark': mark_per_q,
                'earned': mark_per_q if is_correct else 0
            })

        wrong_count = question_count - correct_count
        score = correct_count * mark_per_q
        percentage = (score / total_mark * 100) if total_mark > 0 else 0

        return jsonify({
            'success': True,
            'message': 'Sheet corrected successfully',
            'data': {
                'qr_data': qr_data,
                'answers': answers,
                'correct_count': correct_count,
                'wrong_count': wrong_count,
                'score': round(score, 2),
                'total_mark': total_mark,
                'percentage': round(percentage, 2),
                'details': details,
                'overlay_image': overlay_b64,
            }
        })

    except Exception as e:
        return jsonify({
            'success': False,
            'message': str(e),
        }), 500

    finally:
        if os.path.exists(temp_path):
            os.remove(temp_path)


@app.route('/api/generate-sheet', methods=['POST'])
def generate_sheet():
    data = request.get_json()

    if not data or 'students' not in data:
        return jsonify({
            'success': False,
            'message': 'Invalid request data',
            'error': {'code': 'GENERATION_FAILED', 'details': 'Student list is required'}
        }), 422

    try:
        pdf_path = generate_sheets_pdf(data)
        return send_file(pdf_path, mimetype='application/pdf', as_attachment=True,
                         download_name=f"bubble_sheets_{data.get('subject_name', 'exam')}.pdf")
    except Exception as e:
        return jsonify({
            'success': False,
            'message': str(e),
            'error': {'code': 'GENERATION_FAILED', 'details': str(e)}
        }), 500


if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5001, debug=True)
