<?php
namespace App;

class Renderer
{
    private ?\Parsedown $parsedown = null;

    public function __construct()
    {
        if (class_exists('\Parsedown')) {
            $this->parsedown = new \Parsedown();
            $this->parsedown->setSafeMode(true);
            $this->parsedown->setBreaksEnabled(true);
        }
    }

    public function render(string $filePath): string
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return '<div class="error">File not found or not readable.</div>';
        }

        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $filesize = filesize($filePath);

        if ($filesize > MAX_FILE_SIZE) {
            return '<div class="error">File exceeds maximum size limit.</div>';
        }

        switch ($ext) {
            case 'md':
                return $this->renderMarkdown($filePath);
            case 'txt':
                return $this->renderText($filePath);
            case 'pdf':
                return $this->renderPdf($filePath);
            case 'doc':
            case 'docx':
                return $this->renderDocx($filePath);
            case 'xls':
            case 'xlsx':
                return $this->renderExcel($filePath);
            case 'ppt':
            case 'pptx':
                return $this->renderPpt($filePath);
            default:
                if (Security::isTextFile($filePath)) {
                    return $this->renderCode($filePath);
                }
                return '<div class="error">Unsupported file type.</div>';
        }
    }

    private function renderMarkdown(string $filePath): string
    {
        $content = file_get_contents($filePath);
        if ($content === false) return '<div class="error">Failed to read file.</div>';

        if ($this->parsedown) {
            $html = $this->parsedown->text($content);
        } else {
            $html = '<pre>' . Security::escapeHtml($content) . '</pre>';
        }

        return '<div class="rendered markdown-body">' . $html . '</div>';
    }

    private function renderText(string $filePath): string
    {
        $content = file_get_contents($filePath);
        if ($content === false) return '<div class="error">Failed to read file.</div>';
        return '<div class="rendered text-content"><pre>' . Security::escapeHtml($content) . '</pre></div>';
    }

    private function renderPdf(string $filePath): string
    {
        $filename = basename($filePath);
        $html = '<div class="pdf-viewer">';
        $html .= '<object data="' . Security::escapeHtml('/download/' . urlencode($filename)) . '" type="application/pdf" width="100%" height="800px">';
        $html .= '<p>PDF cannot be displayed inline. <a href="' . Security::escapeHtml('/download/' . urlencode($filename)) . '">Download PDF</a></p>';
        $html .= '</object>';
        $html .= '</div>';
        return $html;
    }

    private function renderDocx(string $filePath): string
    {
        if (!extension_loaded('zip') || !class_exists('ZipArchive')) {
            return $this->renderDocLegacy($filePath);
        }
        try {
            $zip = new \ZipArchive();
            if ($zip->open($filePath) !== true) {
                return $this->renderDocLegacy($filePath);
            }
            $xmlContent = $zip->getFromName('word/document.xml');
            $zip->close();
            if ($xmlContent === false) {
                return $this->renderDocLegacy($filePath);
            }
            $xml = simplexml_load_string($xmlContent);
            if ($xml === false) {
                return $this->renderDocLegacy($filePath);
            }
            $ns = $xml->getNamespaces(true);
            $w = $ns['w'] ?? 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
            $xml->registerXPathNamespace('w', $w);
            $html = '<div class="rendered docx-content">';
            $paragraphs = $xml->xpath('//w:body/w:p');
            if ($paragraphs) {
                foreach ($paragraphs as $p) {
                    $texts = [];
                    foreach ($p->xpath('.//w:t') as $t) {
                        $texts[] = (string)$t;
                    }
                    $text = implode('', $texts);
                    $pStyle = $p->xpath('.//w:pStyle');
                    if ($pStyle) {
                        $styleId = (string)$pStyle[0]['w:val'];
                        if (preg_match('/heading|title/i', $styleId)) {
                            $level = preg_replace('/[^0-9]/', '', $styleId);
                            $level = min(max((int)$level, 1), 6);
                            $html .= '<h' . $level . '>' . Security::escapeHtml($text) . '</h' . $level . '>';
                        } else {
                            $html .= '<p>' . Security::escapeHtml($text) . '</p>';
                        }
                    } else {
                        $html .= '<p>' . Security::escapeHtml($text) . '</p>';
                    }
                }
            }
            $tables = $xml->xpath('//w:body/w:tbl');
            if ($tables) {
                foreach ($tables as $tbl) {
                    $html .= '<table border="1" style="border-collapse:collapse;width:100%;margin:8px 0;">';
                    foreach ($tbl->xpath('.//w:tr') as $row) {
                        $html .= '<tr>';
                        foreach ($row->xpath('.//w:tc') as $cell) {
                            $cellTexts = [];
                            foreach ($cell->xpath('.//w:t') as $t) {
                                $cellTexts[] = (string)$t;
                            }
                            $html .= '<td style="padding:4px 8px;border:1px solid #ccc;">' . Security::escapeHtml(implode('', $cellTexts)) . '</td>';
                        }
                        $html .= '</tr>';
                    }
                    $html .= '</table>';
                }
            }
            $html .= '</div>';
            if (strlen(strip_tags($html)) < 10) {
                return $this->renderDocLegacy($filePath);
            }
            return $html;
        } catch (\Exception $e) {
            return $this->renderDocLegacy($filePath);
        }
    }

    private function renderDocLegacy(string $filePath): string
    {
        $content = file_get_contents($filePath);
        if ($content === false) return '<div class="error">Failed to read file.</div>';
        $text = strip_tags($content);
        $text = preg_replace('/\s+/', ' ', $text);
        return '<div class="rendered text-content"><h3>Raw text extraction (DOC format):</h3><pre>' . Security::escapeHtml(mb_substr($text, 0, 50000)) . '</pre></div>';
    }

    private function renderExcel(string $filePath): string
    {
        if (!extension_loaded('zip') || !class_exists('ZipArchive')) {
            return $this->renderText($filePath);
        }
        try {
            $zip = new \ZipArchive();
            if ($zip->open($filePath) !== true) {
                return $this->renderText($filePath);
            }
            $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
            $sharedStrings = [];
            if ($sharedStringsXml) {
                $ssXml = simplexml_load_string($sharedStringsXml);
                if ($ssXml) {
                    $ns = $ssXml->getNamespaces(true);
                    $s = $ns['s'] ?? 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
                    $ssXml->registerXPathNamespace('s', $s);
                    foreach ($ssXml->xpath('//s:si/s:t') as $si) {
                        $sharedStrings[] = (string)$si;
                    }
                }
            }
            $wbXml = $zip->getFromName('xl/workbook.xml');
            $sheetNames = [];
            if ($wbXml) {
                $wb = simplexml_load_string($wbXml);
                if ($wb) {
                    $ns = $wb->getNamespaces(true);
                    $wbNs = $ns[''] ?? 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
                    $r = $ns['r'] ?? 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
                    $wb->registerXPathNamespace('s', $wbNs);
                    $wb->registerXPathNamespace('r', $r);
                    foreach ($wb->xpath('//s:sheets/s:sheet') as $sheet) {
                        $sheetNames[] = (string)$sheet['name'];
                    }
                }
            }
            $html = '<div class="rendered excel-content">';
            for ($i = 0; $i < max(count($sheetNames), 1); $i++) {
                $sheetXml = $zip->getFromName('xl/worksheets/sheet' . ($i + 1) . '.xml');
                if (!$sheetXml) continue;
                $sheetName = $sheetNames[$i] ?? 'Sheet ' . ($i + 1);
                $html .= '<h3>' . Security::escapeHtml($sheetName) . '</h3>';
                $html .= '<table border="1" style="border-collapse:collapse;width:100%;font-size:13px;">';
                $sheet = simplexml_load_string($sheetXml);
                if ($sheet) {
                    $ns = $sheet->getNamespaces(true);
                    $s = $ns[''] ?? 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
                    $sheet->registerXPathNamespace('s', $s);
                    $rows = $sheet->xpath('//s:sheetData/s:row');
                    if ($rows) {
                        foreach ($rows as $row) {
                            $html .= '<tr>';
                            $cells = $row->xpath('.//s:c');
                            if ($cells) {
                                foreach ($cells as $cell) {
                                    $value = '';
                                    $type = (string)$cell['t'];
                                    $v = $cell->xpath('.//s:v');
                                    if ($v) {
                                        $rawValue = (string)$v[0];
                                        if ($type === 's' && isset($sharedStrings[(int)$rawValue])) {
                                            $value = $sharedStrings[(int)$rawValue];
                                        } else {
                                            $value = $rawValue;
                                        }
                                    }
                                    $html .= '<td style="padding:4px 8px;border:1px solid #ccc;">' . Security::escapeHtml($value) . '</td>';
                                }
                            }
                            $html .= '</tr>';
                        }
                    }
                }
                $html .= '</table><br>';
            }
            $zip->close();
            $html .= '</div>';
            return $html;
        } catch (\Exception $e) {
            return $this->renderText($filePath);
        }
    }

    private function renderPpt(string $filePath): string
    {
        if (!extension_loaded('zip') || !class_exists('ZipArchive')) {
            return $this->fallbackText($filePath);
        }
        try {
            $zip = new \ZipArchive();
            if ($zip->open($filePath) !== true) {
                return $this->fallbackText($filePath);
            }
            $html = '<div class="rendered ppt-content">';
            for ($i = 1; $i <= 50; $i++) {
                $slideXml = $zip->getFromName('ppt/slides/slide' . $i . '.xml');
                if (!$slideXml) break;
                $html .= '<div class="slide" style="border:1px solid #ddd;padding:20px;margin:10px 0;border-radius:8px;background:#fff;box-shadow:0 2px 8px rgba(0,0,0,0.1);">';
                $html .= '<div class="slide-number" style="font-size:12px;color:#999;margin-bottom:10px;">Slide ' . $i . '</div>';
                $xml = simplexml_load_string($slideXml);
                if ($xml) {
                    $ns = $xml->getNamespaces(true);
                    $a = $ns['a'] ?? 'http://schemas.openxmlformats.org/drawingml/2006/main';
                    $p = $ns['p'] ?? 'http://schemas.openxmlformats.org/presentationml/2006/main';
                    $xml->registerXPathNamespace('a', $a);
                    $xml->registerXPathNamespace('p', $p);
                    $texts = $xml->xpath('//a:t');
                    if ($texts) {
                        foreach ($texts as $t) {
                            $html .= '<p>' . Security::escapeHtml((string)$t) . '</p>';
                        }
                    }
                }
                $html .= '</div>';
            }
            $zip->close();
            $html .= '</div>';
            return $html;
        } catch (\Exception $e) {
            return $this->fallbackText($filePath);
        }
    }

    private function renderCode(string $filePath): string
    {
        $content = file_get_contents($filePath);
        if ($content === false) return '<div class="error">Failed to read file.</div>';

        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $langMap = [
            'py' => 'python', 'js' => 'javascript', 'ts' => 'typescript',
            'jsx' => 'jsx', 'tsx' => 'tsx', 'html' => 'html', 'htm' => 'html',
            'css' => 'css', 'scss' => 'scss', 'less' => 'less',
            'json' => 'json', 'xml' => 'xml', 'yaml' => 'yaml', 'yml' => 'yaml',
            'php' => 'php', 'java' => 'java', 'c' => 'c', 'cpp' => 'cpp',
            'h' => 'c', 'hpp' => 'cpp', 'go' => 'go', 'rs' => 'rust',
            'rb' => 'ruby', 'pl' => 'perl', 'lua' => 'lua',
            'swift' => 'swift', 'kt' => 'kotlin', 'sh' => 'bash',
            'bat' => 'batch', 'sql' => 'sql', 'r' => 'r',
            'md' => 'markdown', 'txt' => 'text', 'csv' => 'csv',
            'svg' => 'xml', 'vue' => 'vue', 'dockerfile' => 'dockerfile',
            'makefile' => 'makefile',
        ];
        $lang = $langMap[$ext] ?? 'text';

        return '<div class="rendered code-content">
            <div class="code-header">
                <span class="file-info">' . Security::escapeHtml(pathinfo($filePath, PATHINFO_BASENAME)) . ' (' . self::formatBytes(strlen($content)) . ')</span>
                <button class="btn btn-sm" onclick="copyCode(this)">Copy</button>
            </div>
            <pre><code class="language-' . $lang . '">' . Security::escapeHtml($content) . '</code></pre>
        </div>';
    }

    private function fallbackText(string $filePath): string
    {
        $content = file_get_contents($filePath);
        if ($content === false) return '<div class="error">Failed to read file.</div>';
        return '<div class="rendered text-content"><pre>' . Security::escapeHtml(mb_substr($content, 0, 50000)) . '</pre></div>';
    }

    public static function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        return round($bytes / pow(1024, $pow), $precision) . ' ' . $units[$pow];
    }
}

function renderer(): Renderer
{
    static $instance = null;
    if ($instance === null) {
        $instance = new Renderer();
    }
    return $instance;
}