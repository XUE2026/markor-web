from flask import Flask, render_template, request, jsonify, send_from_directory
import os
import markdown
from pygments import highlight
from pygments.lexers import get_lexer_by_name
from pygments.formatters import HtmlFormatter
from datetime import datetime

app = Flask(__name__)

# 配置
app.config['SECRET_KEY'] = os.environ.get('SECRET_KEY', 'dev-secret-key')
app.config['DATA_DIR'] = os.environ.get('DATA_DIR', os.path.join(os.path.dirname(__file__), 'data'))
app.config['MODE'] = os.environ.get('MODE', 'full')  # full, read, write, preview
app.config['TARGET_FILE'] = os.environ.get('TARGET_FILE', '')

# 确保数据目录存在
os.makedirs(app.config['DATA_DIR'], exist_ok=True)

def highlight_code(code, language):
    try:
        lexer = get_lexer_by_name(language, stripall=True)
        formatter = HtmlFormatter(style='monokai')
        return highlight(code, lexer, formatter)
    except:
        return f'<pre><code>{code}</code></pre>'

def render_markdown(text):
    md = markdown.Markdown(
        extensions=['extra', 'codehilite', 'tables', 'toc', 'fenced_code']
    )
    return md.convert(text)

@app.route('/')
def index():
    mode = app.config['MODE']
    target_file = app.config['TARGET_FILE']
    return render_template('index.html', mode=mode, target_file=target_file)

@app.route('/api/files', methods=['GET'])
def list_files():
    files = []
    data_dir = app.config['DATA_DIR']
    for filename in os.listdir(data_dir):
        filepath = os.path.join(data_dir, filename)
        if os.path.isfile(filepath):
            stat = os.stat(filepath)
            files.append({
                'name': filename,
                'size': stat.st_size,
                'modified': datetime.fromtimestamp(stat.st_mtime).isoformat()
            })
    return jsonify(files)

@app.route('/api/file/<filename>', methods=['GET'])
def get_file(filename):
    filepath = os.path.join(app.config['DATA_DIR'], filename)
    if not os.path.exists(filepath):
        return jsonify({'error': 'File not found'}), 404
    
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    return jsonify({
        'name': filename,
        'content': content,
        'html': render_markdown(content)
    })

@app.route('/api/file/<filename>', methods=['POST'])
def save_file(filename):
    mode = app.config['MODE']
    if mode not in ['full', 'write']:
        return jsonify({'error': 'Write access denied'}), 403
    
    filepath = os.path.join(app.config['DATA_DIR'], filename)
    content = request.json.get('content', '')
    
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)
    
    return jsonify({'success': True})

@app.route('/api/preview/<filename>', methods=['GET'])
def preview_file(filename):
    filepath = os.path.join(app.config['DATA_DIR'], filename)
    if not os.path.exists(filepath):
        return jsonify({'error': 'File not found'}), 404
    
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    return render_template('preview.html', content=render_markdown(content), filename=filename)

@app.route('/static/<path:path>')
def send_static(path):
    return send_from_directory('static', path)

if __name__ == '__main__':
    import argparse
    
    parser = argparse.ArgumentParser(description='Markor Web - Python Edition')
    parser.add_argument('--port', type=int, default=5000, help='Port to listen on')
    parser.add_argument('--host', type=str, default='0.0.0.0', help='Host to bind to')
    parser.add_argument('--mode', type=str, default='full', choices=['full', 'read', 'write', 'preview'], help='Operation mode')
    parser.add_argument('--file', type=str, default='', help='Target file for restricted modes')
    parser.add_argument('--data-dir', type=str, default='', help='Data directory')
    
    args = parser.parse_args()
    
    if args.mode:
        app.config['MODE'] = args.mode
    if args.file:
        app.config['TARGET_FILE'] = args.file
    if args.data_dir:
        app.config['DATA_DIR'] = args.data_dir
        os.makedirs(app.config['DATA_DIR'], exist_ok=True)
    
    app.run(host=args.host, port=args.port, debug=True)
