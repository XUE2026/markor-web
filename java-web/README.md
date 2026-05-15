# Markor Web - Java Edition

这是 Markor 的 Java Web 实现版本。

## 功能特性

- 完整的 Markor Web 编辑器
- 基于 Spring Boot 框架
- 支持多种运行模式（完整、只读、只写、预览）
- Markdown 实时预览
- 文件管理功能
- 与原版 Markor 一致的样式

## 系统要求

- Java 17+
- Gradle 8+

## 安装与运行

### 1. 构建项目

```bash
./gradlew build
```

### 2. 配置

在 `src/main/resources` 目录下创建 `application.properties` 文件：

```properties
app.mode=full
app.data-dir=/path/to/data
app.target-file=
app.title=Markor Web - Java Edition
```

或使用环境变量：

```bash
export APP_MODE=full
export APP_DATA_DIR=/path/to/data
```

### 3. 运行应用

```bash
./gradlew bootRun
```

### 4. 访问应用

打开浏览器访问：`http://localhost:8080`

## 运行模式

- `full`: 完整功能模式
- `read`: 只读模式
- `write`: 只写模式
- `preview`: 仅预览模式

## 技术栈

- Spring Boot 3.2
- Thymeleaf (模板引擎)
- Commonmark (Markdown 解析)
- Gradle (构建工具)

## 构建可执行 JAR

```bash
./gradlew bootJar
java -jar build/libs/markor-web-1.0.0.jar
```
