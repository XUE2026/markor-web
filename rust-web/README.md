# Markor Web - Rust Edition

这是 Markor 的 Rust Web 实现版本。

## 功能特性

- 完整的 Markor Web 编辑器
- 高性能异步处理
- 支持多种运行模式（完整、只读、只写、预览）
- Markdown 实时预览
- 文件管理功能
- 与原版 Markor 一致的样式

## 系统要求

- Rust 1.60+
- Cargo

## 安装与运行

### 1. 构建项目

```bash
cargo build --release
```

### 2. 配置

使用环境变量配置应用：

```bash
export DATA_DIR=/path/to/data
export MODE=full  # full, read, write, preview
export TARGET_FILE=
```

### 3. 运行应用

```bash
cargo run --release
```

### 4. 访问应用

打开浏览器访问：`http://localhost:8080`

## 运行模式

- `full`: 完整功能模式
- `read`: 只读模式
- `write`: 只写模式
- `preview`: 仅预览模式

## 技术栈

- Actix-web 4 (Web 框架)
- Tokio (异步运行时)
- Markdown (Markdown 解析)
- Serde (序列化)
- Chrono (日期时间处理)

## 性能

Rust 版本提供最佳性能和最低内存占用，适合生产环境部署。
