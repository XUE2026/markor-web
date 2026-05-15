<?php

namespace Markor;

class FileStorage implements StorageInterface
{
    private $dataDir;

    public function __construct(string $dataDir)
    {
        $this->dataDir = rtrim($dataDir, '/');
        if (!is_dir($this->dataDir)) {
            mkdir($this->dataDir, 0777, true);
        }
    }

    public function listFiles(): array
    {
        $files = [];
        $iterator = new \DirectoryIterator($this->dataDir);
        
        foreach ($iterator as $fileinfo) {
            if ($fileinfo->isFile() && !$fileinfo->isDot()) {
                $files[] = [
                    'name' => $fileinfo->getFilename(),
                    'size' => $fileinfo->getSize(),
                    'modified' => date('c', $fileinfo->getMTime()),
                ];
            }
        }
        
        usort($files, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
        
        return $files;
    }

    public function getFile(string $filename): ?array
    {
        $filepath = $this->dataDir . '/' . $filename;
        if (!file_exists($filepath)) {
            return null;
        }
        
        return [
            'name' => $filename,
            'content' => file_get_contents($filepath),
            'modified' => date('c', filemtime($filepath)),
        ];
    }

    public function saveFile(string $filename, string $content): bool
    {
        $filepath = $this->dataDir . '/' . $filename;
        return file_put_contents($filepath, $content) !== false;
    }

    public function deleteFile(string $filename): bool
    {
        $filepath = $this->dataDir . '/' . $filename;
        if (file_exists($filepath)) {
            return unlink($filepath);
        }
        return false;
    }
}
