import os
from flask import Flask, request, jsonify, render_template, send_from_directory
from flask_cors import CORS
from models import FileModel
import werkzeug

app = Flask(__name__)
CORS(app)

BASE_DIR = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', 'MD'))
model = FileModel(BASE_DIR)
VISUALS_DIR = os.path.join(BASE_DIR, '05_VISUALS')

@app.route('/')
def index():
    return render_template('index.html')

@app.route('/api/tree')
def get_tree():
    return jsonify(model.get_tree())

@app.route('/api/file')
def get_file():
    path = request.args.get('path')
    if not path:
        return jsonify({'error': 'Path required'}), 400
    res = model.read_file(path)
    if not res:
        return jsonify({'error': 'File not found'}), 404
    return jsonify(res)

@app.route('/api/file', methods=['POST'])
def save_file():
    data = request.json
    path = data.get('path')
    meta = data.get('meta', {})
    body = data.get('body', '')

    if not path:
        return jsonify({'error': 'Path required'}), 400

    model.save_file(path, meta, body)
    return jsonify({'success': True})

@app.route('/api/tags')
def get_tags():
    return jsonify(model.get_all_tags())

@app.route('/api/files')
def get_files():
    tag = request.args.get('tag')
    query = request.args.get('query')
    return jsonify(model.search_files(tag=tag, query=query))

@app.route('/api/upload', methods=['POST'])
def upload_file():
    if 'file' not in request.files:
        return jsonify({'error': 'No file part'}), 400
    file = request.files['file']
    if file.filename == '':
        return jsonify({'error': 'No selected file'}), 400

    filename = werkzeug.utils.secure_filename(file.filename)
    # Ensure it goes to 05_VISUALS
    save_path = os.path.join(VISUALS_DIR, filename)
    file.save(save_path)

    return jsonify({'success': True, 'path': f'05_VISUALS/{filename}'})

@app.route('/images/<path:filename>')
def serve_image(filename):
    return send_from_directory(VISUALS_DIR, filename)

if __name__ == '__main__':
    app.run(debug=True, port=5000)
