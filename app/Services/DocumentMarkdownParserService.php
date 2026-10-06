<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\Element\TextBreak;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Element\Row;
use PhpOffice\PhpWord\Element\Cell;
use Smalot\PdfParser\Parser as PdfParser;

class DocumentMarkdownParserService
{
    /**
     * Parse any uploaded document (.docx, .doc, .pdf, .txt, .md) into standardized
     * Lesson Note / Lesson Plan structured data (Title, Objectives, Markdown & HTML content).
     *
     * @param string $filePath Absolute path to the uploaded file
     * @param string|null $originalFilename
     * @return array{title: string, learning_objectives: array, content: string, markdown: string, source: string}
     */
    public function parse(string $filePath, ?string $originalFilename = null): array
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("File does not exist at path: {$filePath}");
        }

        $filename = $originalFilename ?: basename($filePath);
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        // 1. Extract raw text and structural content from the file
        $rawText = $this->extractRawText($filePath, $extension);

        if (empty(trim($rawText))) {
            throw new \RuntimeException("Unable to extract text from the document. The file may be empty or corrupted.");
        }

        // 2. Attempt AI-powered smart parsing if Gemini API key is configured
        if ($this->isAiAvailable()) {
            try {
                $aiResult = $this->parseWithGemini($rawText, $filename);
                if ($aiResult !== null) {
                    return $aiResult;
                }
            } catch (\Throwable $e) {
                Log::warning("Gemini AI document parsing failed, falling back to local parser: " . $e->getMessage());
            }
        }

        // 3. Fallback to smart local PHP parser
        return $this->parseWithLocalFallback($rawText, $filename);
    }

    /**
     * Extract raw text based on file type.
     */
    protected function extractRawText(string $filePath, string $extension): string
    {
        return match ($extension) {
            'docx' => $this->extractFromDocx($filePath),
            'pdf'  => $this->extractFromPdf($filePath),
            'txt', 'md', 'rtf' => file_get_contents($filePath),
            'doc'  => $this->extractFromBinaryDoc($filePath),
            default => file_get_contents($filePath),
        };
    }

    /**
     * Extract structured text & tables from .docx Word document.
     */
    protected function extractFromDocx(string $filePath): string
    {
        try {
            $phpWord = IOFactory::load($filePath);
            $output = [];

            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    $text = $this->extractElementText($element);
                    if ($text !== null && trim($text) !== '') {
                        $output[] = $text;
                    }
                }
            }

            return implode("\n\n", $output);
        } catch (\Throwable $e) {
            // Fallback: unzip and extract XML document content
            return $this->extractFromDocxXml($filePath);
        }
    }

    /**
     * Fallback .docx XML text extractor.
     */
    protected function extractFromDocxXml(string $filePath): string
    {
        $zip = new \ZipArchive();
        if ($zip->open($filePath) === true) {
            $content = $zip->getFromName('word/document.xml');
            $zip->close();

            if ($content) {
                // Strip XML tags but preserve paragraph breaks
                $content = str_replace('</w:p>', "\n\n", $content);
                $content = str_replace('</w:tr>', "\n", $content);
                $content = str_replace('</w:tc>', "\t", $content);
                return trim(strip_tags($content));
            }
        }

        return '';
    }

    /**
     * Extract text from PhpWord element recursively.
     */
    protected function extractElementText($element): ?string
    {
        if ($element instanceof Text) {
            return $element->getText();
        }

        if ($element instanceof TextRun) {
            $runText = '';
            foreach ($element->getElements() as $child) {
                if ($child instanceof Text) {
                    $t = $child->getText();
                    $font = $child->getFontStyle();
                    if ($font && $font->isBold()) {
                        $t = "**{$t}**";
                    } elseif ($font && $font->isItalic()) {
                        $t = "*{$t}*";
                    }
                    $runText .= $t;
                }
            }
            return $runText;
        }

        if ($element instanceof Table) {
            $tableLines = [];
            foreach ($element->getRows() as $row) {
                $rowCells = [];
                foreach ($row->getCells() as $cell) {
                    $cellTexts = [];
                    foreach ($cell->getElements() as $cellElem) {
                        $ct = $this->extractElementText($cellElem);
                        if ($ct) {
                            $cellTexts[] = trim($ct);
                        }
                    }
                    $rowCells[] = implode(' ', $cellTexts);
                }
                $tableLines[] = '| ' . implode(' | ', $rowCells) . ' |';
            }
            return implode("\n", $tableLines);
        }

        if ($element instanceof \PhpOffice\PhpWord\Element\ListItem || $element instanceof \PhpOffice\PhpWord\Element\ListItemRun) {
            $itemText = '';
            if (method_exists($element, 'getElements')) {
                foreach ($element->getElements() as $child) {
                    $itemText .= $this->extractElementText($child);
                }
            } elseif (method_exists($element, 'getTextObject') && $element->getTextObject()) {
                $itemText = $this->extractElementText($element->getTextObject());
            } elseif (method_exists($element, 'getText')) {
                $itemText = $element->getText();
            }
            return "- " . trim($itemText);
        }

        if (method_exists($element, 'getText')) {
            return $element->getText();
        }

        return null;
    }

    /**
     * Extract text from PDF document using Smalot\PdfParser.
     */
    protected function extractFromPdf(string $filePath): string
    {
        try {
            $parser = new PdfParser();
            $pdf = $parser->parseFile($filePath);
            return $pdf->getText();
        } catch (\Throwable $e) {
            Log::warning("PdfParser failed on {$filePath}: " . $e->getMessage());
            return '';
        }
    }

    /**
     * Extract plain text from legacy binary .doc file.
     */
    protected function extractFromBinaryDoc(string $filePath): string
    {
        $content = file_get_contents($filePath);
        // Clean out binary control characters
        $text = preg_replace('/[^\x20-\x7E\t\r\n]/', ' ', $content);
        return preg_replace('/\s+/', ' ', $text);
    }

    /**
     * Check if Gemini AI is configured.
     */
    public function isAiAvailable(): bool
    {
        $apiKey = config('services.gemini.api_key');
        return !empty($apiKey);
    }

    /**
     * Parse document using Google Gemini Free API.
     */
    protected function parseWithGemini(string $rawText, string $filename): ?array
    {
        $apiKey = config('services.gemini.api_key');
        $model = config('services.gemini.model', 'gemini-1.5-flash');

        $prompt = <<<PROMPT
You are an expert curriculum coordinator and educational assistant.
A teacher has uploaded a lesson note document named "{$filename}".
Your task is to extract and organize this document into a structured educational lesson note.

Analyze the document and extract:
1. "title": The main lesson topic / title in ALL CAPS (e.g., "PHOTOSYNTHESIS & PLANT NUTRITION"). If not explicitly stated, infer the most accurate, concise topic.
2. "learning_objectives": An array of specific, actionable learning objectives ("By the end of the lesson, students should be able to..."). Provide between 2 to 5 clear objectives.
3. "markdown_content": The complete lesson note body formatted in clean, professional Markdown. Include:
   - Topic & sub-topics (##, ###)
   - Key concepts & definitions
   - Instructional materials & resources (if any)
   - Step-by-step presentation & lesson flow
   - Summary & student activities
   - Evaluation questions / exercises / homework
4. "html_content": Clean HTML representation of the lesson note body suitable for a rich text editor (with <h2>, <h3>, <p>, <ul>, <ol>, <li>, <strong>, <em>, <table>).

Document Content:
---
{$rawText}
---

Respond strictly with a valid JSON object with keys: "title", "learning_objectives", "markdown_content", "html_content". Do NOT wrap in backticks or markdown code blocks.
PROMPT;

        $response = Http::timeout(30)->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'response_mime_type' => 'application/json',
                'temperature' => 0.2,
            ]
        ]);

        if (!$response->successful()) {
            Log::warning("Gemini API returned error: " . $response->status() . " - " . $response->body());
            return null;
        }

        $data = $response->json();
        $textResult = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if (!$textResult) {
            return null;
        }

        $decoded = json_decode($textResult, true);
        if (!is_array($decoded) || empty($decoded['title'])) {
            return null;
        }

        $title = mb_strtoupper(trim((string) $decoded['title']));
        $objectives = is_array($decoded['learning_objectives'] ?? null) ? $decoded['learning_objectives'] : [];
        $markdown = (string) ($decoded['markdown_content'] ?? '');
        $html = (string) ($decoded['html_content'] ?? '');

        if (empty($html) && !empty($markdown)) {
            $html = $this->markdownToHtml($markdown);
        }

        return [
            'title' => $title,
            'learning_objectives' => $objectives,
            'content' => $html,
            'markdown' => $markdown,
            'source' => 'ai_gemini',
        ];
    }

    /**
     * Smart local fallback parser when AI is unavailable or offline.
     */
    protected function parseWithLocalFallback(string $rawText, string $filename): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $rawText);
        $lines = array_map('trim', $lines);

        $title = null;
        $objectives = [];
        $contentLines = [];
        $inObjectivesSection = false;

        foreach ($lines as $line) {
            if ($line === '') {
                if (!empty($objectives)) {
                    $inObjectivesSection = false;
                }
                $contentLines[] = '';
                continue;
            }

            // Detect Topic / Title
            if ($title === null && preg_match('/^(?:[\*#\s]*)(?:topic|title|subject|theme|lesson\s*topic)\s*[:=-]\s*(.+?)(?:[\*#\s]*)$/i', $line, $m)) {
                $title = trim($m[1], " \t\n\r\0\x0B*#_");
                continue;
            }

            // Detect Objectives header
            if (preg_match('/^(?:[\*#\s]*)(?:learning\s*objectives?|objectives?|behavioural\s*objectives?|specific\s*objectives?)\s*[:=-]?(?:[\*#\s]*)$/i', $line)) {
                $inObjectivesSection = true;
                $contentLines[] = "### Learning Objectives";
                continue;
            }

            // Check if a new section heading starts (which terminates the objectives section)
            if ($inObjectivesSection && preg_match('/^(?:#{1,4}|lesson\s*content|content|procedure|presentation|evaluation|topic|materials|instructional\s*materials|teaching\s*methods|step\s*\d+|conclusion|summary|activities|homework|assignment)\b/i', $line)) {
                $inObjectivesSection = false;
            }

            // Collect objectives if in objectives section
            if ($inObjectivesSection) {
                $isBulletOrNum = preg_match('/^(?:[\*#\s]*)(?:[-*•]|\d+[.)])\s*(.+?)(?:[\*#\s]*)$/', $line, $om);
                if ($isBulletOrNum) {
                    $cleanedObj = trim($om[1], " \t\n\r\0\x0B*#_");
                } elseif (count($objectives) < 6 && strlen($line) < 250 && !str_contains($line, ':')) {
                    $cleanedObj = trim($line, " \t\n\r\0\x0B*#_");
                } else {
                    $cleanedObj = null;
                    $inObjectivesSection = false;
                }

                if ($cleanedObj !== null && $cleanedObj !== '') {
                    $objectives[] = $cleanedObj;
                    $contentLines[] = "- " . $cleanedObj;
                    continue;
                }
            }

            // Regular content line
            $contentLines[] = $line;
        }

        // Fallback for title if not explicitly found in text
        if ($title === null) {
            // Check first non-empty line
            foreach ($lines as $line) {
                if ($line !== '' && strlen($line) < 100 && !str_starts_with($line, '#')) {
                    $title = trim($line, " \t\n\r\0\x0B*#_");
                    break;
                }
            }
            if ($title === null) {
                $cleanFilename = pathinfo($filename, PATHINFO_FILENAME);
                $title = str_replace(['_', '-'], ' ', $cleanFilename);
            }
        }

        $title = mb_strtoupper(trim($title, " \t\n\r\0\x0B*#_"));
        $markdown = implode("\n", $contentLines);
        $html = $this->markdownToHtml($markdown);

        return [
            'title' => $title,
            'learning_objectives' => $objectives,
            'content' => $html,
            'markdown' => $markdown,
            'source' => 'local_parser',
        ];
    }

    /**
     * Convert Markdown formatting into clean HTML for rich text editors.
     */
    public function markdownToHtml(string $markdown): string
    {
        if (trim($markdown) === '') {
            return '';
        }

        // Use Laravel's built-in CommonMark parser
        $html = Str::markdown($markdown);

        // Sanitize the HTML output
        return clean(trim($html));
    }
}
