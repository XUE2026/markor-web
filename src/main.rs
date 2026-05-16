use actix_web::{web, App, HttpResponse, HttpServer, Responder};
use serde::{Deserialize, Serialize};
use std::fs;
use std::path::PathBuf;
use std::sync::Mutex;
use once_cell::sync::Lazy;
use chrono::{DateTime, Utc};

#[derive(Debug, Serialize, Deserialize, Clone)]
pub struct FileInfo {
    name: String,
    size: u64,
    modified: String,
}

#[derive(Debug, Serialize, Deserialize)]
pub struct FileContent {
    name: String,
    content: String,
    html: String,
    modified: Option<String>,
}

#[derive(Debug, Serialize, Deserialize)]
pub struct SaveRequest {
    content: String,
}

pub struct AppState {
    data_dir: Mutex<PathBuf>,
    mode: Mutex<String>,
    target_file: Mutex<String>,
}

static HTML_TEMPLATE: &str = r#"<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Markor Web - Rust Edition</title>
    <link rel="stylesheet" href="/static/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Markor Web - Rust Edition</h1>
            <nav>
                <a href="/">编辑器</a>
                <span class="mode-indicator" id="mode-indicator">模式: full</span>
            </nav>
        </header>
        <main>
            <div class="editor-container">
                <aside class="sidebar">
                    <h3>文件列表</h3>
                    <div id="file-list"></div>
                </aside>
                <section class="editor-main">
                    <div class="editor-header">
                        <input type="text" id="current-file" placeholder="文件名" value="">
                        <button onclick="saveFile()" class="btn btn-success">保存</button>
                    </div>
                    <div class="editor-content">
                        <div class="editor-pane">
                            <textarea id="editor" placeholder="在这里输入 Markdown..."></textarea>
                        </div>
                        <div class="preview-pane">
                            <div id="preview" class="markdown-body"></div>
                        </div>
                    </div>
                </section>
            </div>
        </main>
        <footer>
            <p>&copy; 2024 Markor Web - Rust Edition. 基于 Markor 项目。</p>
        </footer>
    </div>
    <script src="/static/js/app.js"></script>
</body>
</html>
"#;

static CSS: &str = r#"* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}
body {
    font-family: 'Roboto', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    background-color: #f5f5f5;
    color: #333;
    line-height: 1.6;
}
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}
header {
    background-color: #3f51b5;
    color: white;
    padding: 1rem 0;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
header h1 { font-size: 1.5rem; }
header nav { margin-top: 0.5rem; display: flex; align-items: center; gap: 1rem; }
header nav a { color: white; text-decoration: none; margin-right: 1rem; }
.mode-indicator {
    padding: 0.25rem 0.5rem;
    background-color: #ff9800;
    color: white;
    border-radius: 4px;
    font-size: 0.9rem;
}
main { padding: 2rem 0; }
footer {
    background-color: #e0e0e0;
    padding: 1rem 0;
    text-align: center;
    margin-top: 2rem;
}
.editor-container {
    display: flex;
    gap: 20px;
    height: calc(100vh - 200px);
}
.sidebar {
    width: 250px;
    background: white;
    border-radius: 8px;
    padding: 1rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.sidebar h3 { margin-bottom: 1rem; color: #3f51b5; }
#file-list { max-height: 400px; overflow-y: auto; margin-bottom: 1rem; }
.file-item {
    padding: 0.5rem;
    cursor: pointer;
    border-radius: 4px;
    margin-bottom: 0.25rem;
}
.file-item:hover { background-color: #e3f2fd; }
.file-item.active { background-color: #3f51b5; color: white; }
.editor-main {
    flex: 1;
    display: flex;
    flex-direction: column;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.editor-header {
    padding: 1rem;
    border-bottom: 1px solid #e0e0e0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
#current-file {
    flex: 1;
    padding: 0.5rem;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    font-size: 1rem;
}
.editor-content {
    flex: 1;
    display: flex;
    gap: 10px;
    padding: 1rem;
    overflow: hidden;
}
.editor-pane, .preview-pane { flex: 1; display: flex; flex-direction: column; }
#editor {
    flex: 1;
    padding: 1rem;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    font-family: 'Courier New', monospace;
    font-size: 0.95rem;
    resize: none;
}
#preview {
    flex: 1;
    padding: 1rem;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    overflow-y: auto;
    background-color: #fafafa;
}
.btn {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 1rem;
    transition: background-color 0.2s;
}
.btn-success { background-color: #4caf50; color: white; }
.btn-success:hover { background-color: #388e3c; }
.markdown-body h1, .markdown-body h2, .markdown-body h3 {
    margin-top: 1.5rem;
    margin-bottom: 1rem;
    color: #3f51b5;
}
.markdown-body code {
    background-color: #f5f5f5;
    padding: 0.125rem 0.25rem;
    border-radius: 3px;
}
.markdown-body pre {
    background-color: #2d2d2d;
    color: #f8f8f2;
    padding: 1rem;
    border-radius: 4px;
    overflow-x: auto;
}
"#;

static JS: &str = r#"let currentFile = '';
let files = [];

document.addEventListener('DOMContentLoaded', () => {
    loadFiles();
    document.getElementById('editor')?.addEventListener('input', updatePreview);
});

async function loadFiles() {
    try {
        const response = await fetch('/api/files');
        files = await response.json();
        renderFileList();
    } catch (error) {
        console.error('Error loading files:', error);
    }
}

function renderFileList() {
    const fileListEl = document.getElementById('file-list');
    if (!fileListEl) return;
    if (files.length === 0) {
        fileListEl.innerHTML = '<p>没有文件</p>';
        return;
    }
    fileListEl.innerHTML = files.map(file => `
        <div class="file-item ${file.name === currentFile ? 'active' : ''}"
             onclick="loadFile('${file.name}')">
            ${file.name}
        </div>
    `).join('');
}

async function loadFile(filename) {
    try {
        const response = await fetch(`/api/file/${encodeURIComponent(filename)}`);
        const data = await response.json();
        if (data.error) {
            alert(data.error);
            return;
        }
        currentFile = filename;
        document.getElementById('current-file').value = filename;
        document.getElementById('editor').value = data.content;
        document.getElementById('preview').innerHTML = data.html;
        renderFileList();
    } catch (error) {
        console.error('Error loading file:', error);
        alert('加载文件失败');
    }
}

function saveFile() {
    if (!currentFile) {
        alert('请先选择或创建一个文件');
        return;
    }
    const content = document.getElementById('editor').value;
    fetch(`/api/file/${encodeURIComponent(currentFile)}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ content })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('保存成功！');
            updatePreview();
        } else {
            alert('保存失败: ' + (data.error || '未知错误'));
        }
    })
    .catch(error => {
        console.error('Error saving file:', error);
        alert('保存失败');
    });
}

function updatePreview() {
    const content = document.getElementById('editor').value;
    fetch(`/api/file/${encodeURIComponent(currentFile || 'temp.md')}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ content })
    })
    .then(response => response.json())
    .then(data => {
        if (!data.error && currentFile) {
            fetch(`/api/file/${encodeURIComponent(currentFile)}`)
                .then(response => response.json())
                .then(data => {
                    if (!data.error) {
                        document.getElementById('preview').innerHTML = data.html;
                    }
                });
        }
    })
    .catch(error => console.error('Error updating preview:', error));
}
"#;

fn get_data_dir(data: &web::Data<AppState>) -> PathBuf {
    data.data_dir.lock().unwrap().clone()
}

fn get_mode(data: &web::Data<AppState>) -> String {
    data.mode.lock().unwrap().clone()
}

async fn index(data: web::Data<AppState>) -> impl Responder {
    let mode = get_mode(&data);
    let html = HTML_TEMPLATE.replace("id=\"mode-indicator\">模式: full", &format!("id=\"mode-indicator\">模式: {}", mode));
    HttpResponse::Ok()
        .content_type("text/html; charset=utf-8")
        .body(html)
}

async fn list_files(data: web::Data<AppState>) -> impl Responder {
    let data_dir = get_data_dir(&data);
    let mut files: Vec<FileInfo> = Vec::new();
    
    if let Ok(entries) = fs::read_dir(&data_dir) {
        for entry in entries.flatten() {
            if let Ok(metadata) = entry.metadata() {
                if metadata.is_file() {
                    let name = entry.file_name().to_string_lossy().to_string();
                    let modified: DateTime<Utc> = metadata.modified()
                        .map(|t| t.into())
                        .unwrap_or_else(|_| Utc::now());
                    
                    files.push(FileInfo {
                        name,
                        size: metadata.len(),
                        modified: modified.to_rfc3339(),
                    });
                }
            }
        }
    }
    
    HttpResponse::Ok()
        .json(files)
}

async fn get_file(
    data: web::Data<AppState>,
    path: web::Path<String>,
) -> impl Responder {
    let filename = path.into_inner();
    let data_dir = get_data_dir(&data);
    let file_path = data_dir.join(&filename);
    
    if !file_path.exists() {
        return HttpResponse::NotFound().json(serde_json::json!({"error": "File not found"}));
    }
    
    match fs::read_to_string(&file_path) {
        Ok(content) => {
            let html = markdown::to_html(&content);
            let modified: Option<String> = fs::metadata(&file_path)
                .ok()
                .and_then(|m| m.modified().ok())
                .map(|t| {
                    let datetime: DateTime<Utc> = t.into();
                    datetime.to_rfc3339()
                });
            
            HttpResponse::Ok().json(FileContent {
                name: filename,
                content,
                html,
                modified,
            })
        }
        Err(_) => HttpResponse::InternalServerError().json(serde_json::json!({"error": "Failed to read file"})),
    }
}

async fn save_file(
    data: web::Data<AppState>,
    path: web::Path<String>,
    body: web::Json<SaveRequest>,
) -> impl Responder {
    let mode = get_mode(&data);
    if mode != "full" && mode != "write" {
        return HttpResponse::Forbidden().json(serde_json::json!({"error": "Write access denied"}));
    }
    
    let filename = path.into_inner();
    let data_dir = get_data_dir(&data);
    let file_path = data_dir.join(&filename);
    
    match fs::write(&file_path, &body.content) {
        Ok(_) => HttpResponse::Ok().json(serde_json::json!({"success": true})),
        Err(_) => HttpResponse::InternalServerError().json(serde_json::json!({"error": "Failed to save file"})),
    }
}

async fn static_css() -> impl Responder {
    HttpResponse::Ok()
        .content_type("text/css; charset=utf-8")
        .body(CSS)
}

async fn static_js() -> impl Responder {
    HttpResponse::Ok()
        .content_type("application/javascript; charset=utf-8")
        .body(JS)
}

#[actix_web::main]
async fn main() -> std::io::Result<()> {
    env_logger::init();
    
    let data_dir = std::env::var("DATA_DIR")
        .map(PathBuf::from)
        .unwrap_or_else(|_| {
            let mut dir = dirs::data_local_dir().unwrap_or_else(|| PathBuf::from("."));
            dir.push("markor-web");
            dir.push("data");
            dir
        });
    
    let mode = std::env::var("MODE").unwrap_or_else(|_| "full".to_string());
    let target_file = std::env::var("TARGET_FILE").unwrap_or_else(|_| "".to_string());
    
    fs::create_dir_all(&data_dir).expect("Failed to create data directory");
    
    let app_state = web::Data::new(AppState {
        data_dir: Mutex::new(data_dir),
        mode: Mutex::new(mode),
        target_file: Mutex::new(target_file),
    });
    
    log::info!("Starting Markor Web server on http://0.0.0.0:8080");
    
    HttpServer::new(move || {
        App::new()
            .app_data(app_state.clone())
            .route("/", web::get().to(index))
            .route("/api/files", web::get().to(list_files))
            .route("/api/file/{filename}", web::get().to(get_file))
            .route("/api/file/{filename}", web::post().to(save_file))
            .route("/static/css/style.css", web::get().to(static_css))
            .route("/static/js/app.js", web::get().to(static_js))
    })
    .bind("0.0.0.0:8080")?
    .run()
    .await
}
