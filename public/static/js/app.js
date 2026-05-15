let currentFile = '';
let files = [];
let previewMode = false;

document.addEventListener('DOMContentLoaded', () => {
    loadFiles();
    setupEventListeners();
});

function setupEventListeners() {
    document.getElementById('editor')?.addEventListener('input', updatePreview);
}

async function loadFiles() {
    try {
        const response = await fetch('/api.php?path=files');
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
        const response = await fetch(`/api.php?path=file/${encodeURIComponent(filename)}`);
        const data = await response.json();
        
        if (data.error) {
            alert(data.error);
            return;
        }
        
        currentFile = filename;
        document.getElementById('current-file').value = filename;
        
        const editor = document.getElementById('editor');
        if (editor) {
            editor.value = data.content;
        }
        
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
    
    const editor = document.getElementById('editor');
    const content = editor.value;
    
    fetch(`/api.php?path=file/${encodeURIComponent(currentFile)}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
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
    const editor = document.getElementById('editor');
    if (!editor) return;
    
    const content = editor.value;
    
    fetch('/api.php?path=file/' + encodeURIComponent(currentFile || 'temp.md'), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ content })
    })
    .then(response => response.json())
    .then(data => {
        if (!data.error) {
            fetch(`/api.php?path=file/${encodeURIComponent(currentFile || 'temp.md')}`)
                .then(response => response.json())
                .then(data => {
                    if (!data.error) {
                        document.getElementById('preview').innerHTML = data.html;
                    }
                });
        }
    })
    .catch(error => {
        console.error('Error updating preview:', error);
    });
}

function createNewFile() {
    const filename = prompt('请输入文件名：');
    if (filename) {
        currentFile = filename;
        document.getElementById('current-file').value = filename;
        
        const editor = document.getElementById('editor');
        if (editor) {
            editor.value = '';
        }
        
        document.getElementById('preview').innerHTML = '';
        saveFile();
        loadFiles();
    }
}
