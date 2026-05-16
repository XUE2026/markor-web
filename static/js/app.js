let currentFile = '';
let files = [];
let previewMode = false;

document.addEventListener('DOMContentLoaded', () => {
    loadFiles();
    setupEventListeners();
});

function setupEventListeners() {
    document.getElementById('new-file-btn').addEventListener('click', createNewFile);
    document.getElementById('save-btn').addEventListener('click', saveCurrentFile);
    document.getElementById('preview-toggle').addEventListener('click', togglePreview);
    document.getElementById('editor').addEventListener('input', updatePreview);
}

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
        const response = await fetch(`/api/file/${filename}`);
        const data = await response.json();
        currentFile = filename;
        document.getElementById('current-file').value = filename;
        document.getElementById('editor').value = data.content;
        document.getElementById('preview').innerHTML = data.html;
        renderFileList();
    } catch (error) {
        console.error('Error loading file:', error);
    }
}

function createNewFile() {
    const filename = prompt('请输入文件名：');
    if (filename) {
        currentFile = filename;
        document.getElementById('current-file').value = filename;
        document.getElementById('editor').value = '';
        document.getElementById('preview').innerHTML = '';
        saveCurrentFile();
    }
}

async function saveCurrentFile() {
    if (!currentFile) {
        alert('请先选择或创建一个文件');
        return;
    }
    
    try {
        const content = document.getElementById('editor').value;
        const response = await fetch(`/api/file/${currentFile}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ content })
        });
        
        if (response.ok) {
            alert('保存成功！');
            loadFiles();
            updatePreview();
        } else {
            alert('保存失败');
        }
    } catch (error) {
        console.error('Error saving file:', error);
        alert('保存失败');
    }
}

function togglePreview() {
    previewMode = !previewMode;
    const editorPane = document.querySelector('.editor-pane');
    const previewPane = document.querySelector('.preview-pane');
    
    if (previewMode) {
        editorPane.style.display = 'none';
        previewPane.style.flex = '2';
    } else {
        editorPane.style.display = 'flex';
        previewPane.style.flex = '1';
    }
}

function updatePreview() {
    const content = document.getElementById('editor').value;
    // 这里可以添加简单的客户端 Markdown 渲染
    document.getElementById('preview').innerHTML = simpleMarkdown(content);
}

function simpleMarkdown(text) {
    return text
        .replace(/^### (.*$)/gim, '<h3>$1</h3>')
        .replace(/^## (.*$)/gim, '<h2>$1</h2>')
        .replace(/^# (.*$)/gim, '<h1>$1</h1>')
        .replace(/\*\*(.*)\*\*/gim, '<strong>$1</strong>')
        .replace(/\*(.*)\*/gim, '<em>$1</em>')
        .replace(/\n/gim, '<br>');
}
