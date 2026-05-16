let currentFile = '';
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
        
        if (!response.ok) {
            alert('文件未找到');
            return;
        }
        
        const data = await response.json();
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
    if (!currentFile) return;
    
    const content = document.getElementById('editor').value;
    
    fetch(`/api/file/${encodeURIComponent(currentFile)}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ content })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            fetch(`/api/file/${encodeURIComponent(currentFile)}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('preview').innerHTML = data.html;
                });
        }
    })
    .catch(error => {
        console.error('Error updating preview:', error);
    });
}
