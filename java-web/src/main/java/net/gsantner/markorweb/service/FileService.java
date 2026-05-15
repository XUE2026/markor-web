package net.gsantner.markorweb.service;

import net.gsantner.markorweb.config.AppConfig;
import org.commonmark.node.*;
import org.commonmark.parser.Parser;
import org.commonmark.renderer.html.HtmlRenderer;
import org.springframework.stereotype.Service;

import java.io.IOException;
import java.nio.file.*;
import java.time.Instant;
import java.util.*;
import java.util.stream.Stream;

@Service
public class FileService {

    private final AppConfig appConfig;
    private final Parser markdownParser;
    private final HtmlRenderer htmlRenderer;

    public FileService(AppConfig appConfig) {
        this.appConfig = appConfig;
        this.markdownParser = Parser.builder().build();
        this.htmlRenderer = HtmlRenderer.builder().build();
    }

    public List<Map<String, Object>> listFiles() {
        List<Map<String, Object>> files = new ArrayList<>();
        Path dataDir = appConfig.getDataDir();
        
        try (Stream<Path> paths = Files.walk(dataDir, 1)) {
            paths.filter(Files::isRegularFile)
                 .forEach(path -> {
                     try {
                         Map<String, Object> fileInfo = new HashMap<>();
                         fileInfo.put("name", path.getFileName().toString());
                         fileInfo.put("size", Files.size(path));
                         fileInfo.put("modified", Instant.ofEpochMilli(Files.getLastModifiedTime(path).toMillis()).toString());
                         files.add(fileInfo);
                     } catch (IOException e) {
                         // Skip files that can't be read
                     }
                 });
        } catch (IOException e) {
            // Return empty list if directory doesn't exist
        }
        
        files.sort(Comparator.comparing(m -> (String) m.get("name")));
        return files;
    }

    public Optional<Map<String, Object>> getFile(String filename) {
        Path filePath = appConfig.getDataDir().resolve(filename);
        
        if (!Files.exists(filePath) || Files.isDirectory(filePath)) {
            return Optional.empty();
        }
        
        try {
            String content = Files.readString(filePath);
            String html = renderMarkdown(content);
            
            Map<String, Object> result = new HashMap<>();
            result.put("name", filename);
            result.put("content", content);
            result.put("html", html);
            result.put("modified", Instant.ofEpochMilli(Files.getLastModifiedTime(filePath).toMillis()).toString());
            
            return Optional.of(result);
        } catch (IOException e) {
            return Optional.empty();
        }
    }

    public boolean saveFile(String filename, String content) {
        String mode = appConfig.getMode();
        if (!"full".equals(mode) && !"write".equals(mode)) {
            return false;
        }
        
        Path filePath = appConfig.getDataDir().resolve(filename);
        
        try {
            Files.writeString(filePath, content, StandardOpenOption.CREATE, StandardOpenOption.TRUNCATE_EXISTING);
            return true;
        } catch (IOException e) {
            return false;
        }
    }

    public boolean deleteFile(String filename) {
        String mode = appConfig.getMode();
        if (!"full".equals(mode)) {
            return false;
        }
        
        Path filePath = appConfig.getDataDir().resolve(filename);
        
        try {
            return Files.deleteIfExists(filePath);
        } catch (IOException e) {
            return false;
        }
    }

    private String renderMarkdown(String markdown) {
        Node document = markdownParser.parse(markdown);
        return htmlRenderer.render(document);
    }
}
