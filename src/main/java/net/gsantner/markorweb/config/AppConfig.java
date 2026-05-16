package net.gsantner.markorweb.config;

import org.springframework.beans.factory.annotation.Value;
import org.springframework.context.annotation.Configuration;
import jakarta.annotation.PostConstruct;
import java.io.File;
import java.nio.file.Path;
import java.nio.file.Paths;

@Configuration
public class AppConfig {

    @Value("${app.mode:full}")
    private String mode;

    @Value("${app.data-dir:}")
    private String dataDir;

    @Value("${app.target-file:}")
    private String targetFile;

    @Value("${app.title:Markor Web - Java Edition}")
    private String title;

    private Path resolvedDataDir;

    @PostConstruct
    public void init() {
        if (dataDir == null || dataDir.isEmpty()) {
            resolvedDataDir = Paths.get(System.getProperty("user.home"), ".markor-web", "data");
        } else {
            resolvedDataDir = Paths.get(dataDir);
        }
        
        File dir = resolvedDataDir.toFile();
        if (!dir.exists()) {
            dir.mkdirs();
        }
    }

    public String getMode() {
        return mode;
    }

    public Path getDataDir() {
        return resolvedDataDir;
    }

    public String getTargetFile() {
        return targetFile;
    }

    public String getTitle() {
        return title;
    }
}
