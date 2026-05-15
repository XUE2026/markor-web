package net.gsantner.markorweb.controller;

import net.gsantner.markorweb.config.AppConfig;
import net.gsantner.markorweb.service.FileService;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.http.ResponseEntity;
import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.web.bind.annotation.*;

import java.util.Map;
import java.util.Optional;

@Controller
public class EditorController {

    @Autowired
    private AppConfig appConfig;

    @Autowired
    private FileService fileService;

    @GetMapping("/")
    public String index(Model model) {
        model.addAttribute("mode", appConfig.getMode());
        model.addAttribute("targetFile", appConfig.getTargetFile());
        model.addAttribute("title", appConfig.getTitle());
        return "editor";
    }

    @GetMapping("/api/files")
    @ResponseBody
    public ResponseEntity<?> listFiles() {
        return ResponseEntity.ok(fileService.listFiles());
    }

    @GetMapping("/api/file/{filename}")
    @ResponseBody
    public ResponseEntity<?> getFile(@PathVariable String filename) {
        Optional<Map<String, Object>> file = fileService.getFile(filename);
        
        if (file.isPresent()) {
            return ResponseEntity.ok(file.get());
        } else {
            return ResponseEntity.notFound().build();
        }
    }

    @PostMapping("/api/file/{filename}")
    @ResponseBody
    public ResponseEntity<?> saveFile(@PathVariable String filename, @RequestBody Map<String, String> request) {
        String content = request.get("content");
        if (content == null) {
            return ResponseEntity.badRequest().body(Map.of("error", "Content is required"));
        }
        
        if (fileService.saveFile(filename, content)) {
            return ResponseEntity.ok(Map.of("success", true));
        } else {
            return ResponseEntity.status(403).body(Map.of("error", "Write access denied"));
        }
    }

    @DeleteMapping("/api/file/{filename}")
    @ResponseBody
    public ResponseEntity<?> deleteFile(@PathVariable String filename) {
        if (fileService.deleteFile(filename)) {
            return ResponseEntity.ok(Map.of("success", true));
        } else {
            return ResponseEntity.status(403).body(Map.of("error", "Delete access denied"));
        }
    }
}
