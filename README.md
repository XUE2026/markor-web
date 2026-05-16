# Markor Web - Python Edition

这是 Markor 的 Python Web 实现版本。

## 功能特性

- 完整的 Markor Web 编辑器
- 支持多种运行模式（完整、只读、只写、预览）
- Markdown 实时预览
- 文件管理功能
- 与原版 Markor 一致的样式

## 安装与运行

### 1. 安装依赖

```bash
pip install -r requirements.txt
```

### 2. 运行应用

#### 标准模式（完整功能）

```bash
python app.py
```

#### 指定端口

```bash
python app.py --port 8080
```

#### 其他运行模式

```bash
# 只读模式
python app.py --mode read

# 只写模式
python app.py --mode write

# 仅预览模式
python app.py --mode preview

# 指定目标文件和数据目录
python app.py --mode read --file notes.md --data-dir /path/to/your/data
```

### 3. 访问应用

打开浏览器访问：`http://localhost:5000`

## 命令行选项

| 选项 | 说明 | 默认值 |
|-----|------|-------|
| `--port` | 监听端口 | 5000 |
| `--host` | 绑定主机 | 0.0.0.0 |
| `--mode` | 运行模式 | full |
| `--file` | 目标文件（受限模式） | '' |
| `--data-dir` | 数据目录 | ./data |

## 运行模式

- `full`: 完整功能模式
- `read`: 只读模式
- `write`: 只写模式
- `preview`: 仅预览模式
