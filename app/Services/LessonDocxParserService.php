<?php

namespace App\Services;

use App\Models\InstructionalMaterial;
use App\Models\LessonPlan;
use App\Models\ReferenceMaterial;
use App\Models\TeachingMethod;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextBreak;
use Illuminate\Support\Str;

class LessonDocxParserService
{
    /**
     * Known section headers for lesson plan templates.
     */
    private const LESSON_PLAN_SECTIONS = [
        'TITLE',
        'TIME / DURATION',
        'SECTION / PERIOD',
        'LEARNING OBJECTIVES',
        'KEY VOCABULARY WORDS',
        'REFERENCE MATERIALS',
        'INSTRUCTIONAL MATERIALS',
        'PRIOR KNOWLEDGE / BACKGROUND',
        'CONTENT',
        'TEACHING METHODS',
        'PRESENTATION STEPS',
        'STRATEGIES AND ACTIVITIES',
        'EVALUATION QUESTIONS',
        'CONCLUSION',
        'ASSIGNMENT / HOMEWORK',
    ];

    /**
     * Known section headers for lesson note templates.
     */
    private const LESSON_NOTE_SECTIONS = [
        'TITLE',
        'LEARNING OBJECTIVES',
        'LESSON NOTE CONTENT',
    ];

    /**
     * Parse a Lesson Plan .docx file and return structured data.
     *
     * @param  string  $filePath  Absolute path to the .docx file
     * @return array   Extracted fields ready for LessonPlan model creation
     *
     * @throws \RuntimeException if the file cannot be parsed
     */
    public function parseLessonPlan(string $filePath): array
    {
        $sections = $this->extractSections($filePath, self::LESSON_PLAN_SECTIONS);

        return [
            'title' => $this->getSingleLine($sections, 'TITLE'),
            'time' => $this->getSingleLine($sections, 'TIME / DURATION'),
            'section' => $this->getSingleLine($sections, 'SECTION / PERIOD'),
            'learning_objectives' => $this->getNumberedList($sections, 'LEARNING OBJECTIVES'),
            'key_vocabulary' => $this->getCommaSeparatedString($sections, 'KEY VOCABULARY WORDS'),
            'reference_materials' => $this->getCommaSeparatedList($sections, 'REFERENCE MATERIALS'),
            'instructional_materials' => $this->getCommaSeparatedList($sections, 'INSTRUCTIONAL MATERIALS'),
            'prior_knowledge' => $this->getRichText($sections, 'PRIOR KNOWLEDGE / BACKGROUND'),
            'content' => $this->getRichText($sections, 'CONTENT'),
            'teaching_methods' => $this->getCommaSeparatedList($sections, 'TEACHING METHODS'),
            'presentation_steps' => $this->getNumberedList($sections, 'PRESENTATION STEPS'),
            'strategies_activities' => $this->getRichText($sections, 'STRATEGIES AND ACTIVITIES'),
            'evaluation_questions' => $this->getNumberedList($sections, 'EVALUATION QUESTIONS'),
            'conclusion' => $this->getRichText($sections, 'CONCLUSION'),
            'assignment' => $this->getRichText($sections, 'ASSIGNMENT / HOMEWORK'),
        ];
    }

    /**
     * Parse a Lesson Note .docx file and return structured data.
     *
     * @param  string  $filePath  Absolute path to the .docx file
     * @return array   Extracted fields ready for LessonNote/LessonNoteVersion creation
     *
     * @throws \RuntimeException if the file cannot be parsed
     */
    public function parseLessonNote(string $filePath): array
    {
        $sections = $this->extractSections($filePath, self::LESSON_NOTE_SECTIONS);

        return [
            'title' => $this->getSingleLine($sections, 'TITLE'),
            'learning_objectives' => $this->getNumberedList($sections, 'LEARNING OBJECTIVES'),
            'content' => $this->getRichText($sections, 'LESSON NOTE CONTENT'),
        ];
    }

    /**
     * Parse a Lesson Plan .docx and persist it to the database.
     */
    public function createLessonPlanFromDocx(
        int $teacherId,
        int $subjectId,
        int $classroomId,
        int $sessionId,
        int $termId,
        int $weekNumber,
        string $filePath
    ): LessonPlan {
        $parsed = $this->parseLessonPlan($filePath);

        $plan = LessonPlan::create([
            'teacher_id' => $teacherId,
            'subject_id' => $subjectId,
            'classroom_id' => $classroomId,
            'session_id' => $sessionId,
            'term_id' => $termId,
            'week_number' => $weekNumber,
            'status' => 'draft',
            'title' => $parsed['title'] ?? null,
            'time' => $parsed['time'] ?? null,
            'section' => $parsed['section'] ?? null,
            'learning_objectives' => !empty($parsed['learning_objectives']) ? $parsed['learning_objectives'] : null,
            'key_vocabulary' => $parsed['key_vocabulary'] ?? null,
            'prior_knowledge' => $parsed['prior_knowledge'] ?? null,
            'content' => $parsed['content'] ?? null,
            'presentation_steps' => !empty($parsed['presentation_steps']) ? $parsed['presentation_steps'] : null,
            'strategies_activities' => $parsed['strategies_activities'] ?? null,
            'evaluation_questions' => !empty($parsed['evaluation_questions']) ? $parsed['evaluation_questions'] : null,
            'conclusion' => $parsed['conclusion'] ?? null,
            'assignment' => $parsed['assignment'] ?? null,
        ]);

        if (!empty($parsed['reference_materials'])) {
            $refIds = collect($parsed['reference_materials'])
                ->filter()
                ->map(fn ($name) => ReferenceMaterial::firstOrCreate(['name' => trim($name)])->id)
                ->toArray();
            $plan->referenceMaterials()->sync($refIds);
        }

        if (!empty($parsed['instructional_materials'])) {
            $instIds = collect($parsed['instructional_materials'])
                ->filter()
                ->map(fn ($name) => InstructionalMaterial::firstOrCreate(['name' => trim($name)])->id)
                ->toArray();
            $plan->instructionalMaterials()->sync($instIds);
        }

        if (!empty($parsed['teaching_methods'])) {
            $methodIds = collect($parsed['teaching_methods'])
                ->filter()
                ->map(fn ($name) => TeachingMethod::firstOrCreate(['name' => trim($name)])->id)
                ->toArray();
            $plan->teachingMethods()->sync($methodIds);
        }

        return $plan;
    }

    /**
     * Extract sections from a .docx file based on known headers.
     *
     * Walks through every paragraph in the document. When a paragraph's
     * text matches a known section header (case-insensitive, trimmed),
     * subsequent paragraphs are collected under that header until the
     * next header is found.
     *
     * @param  string    $filePath
     * @param  string[]  $knownHeaders
     * @return array<string, string[]>  Map of header => array of paragraph texts
     */
    private function extractSections(string $filePath, array $knownHeaders): array
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException('Document file not found.');
        }

        try {
            $phpWord = IOFactory::load($filePath);
        } catch (\Exception $e) {
            throw new \RuntimeException('Could not read the uploaded document. Please ensure it is a valid .docx file.');
        }

        // Normalize known headers for matching
        $normalizedHeaders = [];
        foreach ($knownHeaders as $header) {
            $normalizedHeaders[Str::upper(Str::squish($header))] = $header;
        }

        $sections = array_fill_keys($knownHeaders, []);
        $currentHeader = null;

        foreach ($phpWord->getSections() as $docSection) {
            foreach ($docSection->getElements() as $element) {
                $text = $this->getElementText($element);

                if ($text === null || trim($text) === '') {
                    continue;
                }

                $normalizedText = Str::upper(Str::squish(trim($text)));

                // Check if this paragraph is a section header
                if (isset($normalizedHeaders[$normalizedText])) {
                    $currentHeader = $normalizedHeaders[$normalizedText];
                    continue;
                }

                // Also check if the text starts with the header (handles variations)
                $matched = false;
                foreach ($normalizedHeaders as $normalizedHeader => $originalHeader) {
                    if ($normalizedText === $normalizedHeader) {
                        $currentHeader = $originalHeader;
                        $matched = true;
                        break;
                    }
                }

                if ($matched) {
                    continue;
                }

                // Skip document title lines
                if (Str::contains($normalizedText, ['LESSON PLAN TEMPLATE', 'LESSON NOTE TEMPLATE', 'FILL IN EACH SECTION'])) {
                    continue;
                }

                // Collect content under current header
                if ($currentHeader !== null) {
                    $trimmed = trim($text);
                    // Skip placeholder/instruction text (italic gray guidance)
                    if (!$this->isPlaceholderText($trimmed)) {
                        $sections[$currentHeader][] = $trimmed;
                    }
                }
            }
        }

        return $sections;
    }

    /**
     * Extract text content from a PhpWord element.
     */
    private function getElementText($element): ?string
    {
        if ($element instanceof TextRun) {
            $parts = [];
            foreach ($element->getElements() as $child) {
                if ($child instanceof Text) {
                    $parts[] = $child->getText();
                }
            }
            return implode('', $parts);
        }

        if ($element instanceof Text) {
            return $element->getText();
        }

        if (method_exists($element, 'getText')) {
            return $element->getText();
        }

        return null;
    }

    /**
     * Check if text looks like placeholder/instruction text from the template.
     */
    private function isPlaceholderText(string $text): bool
    {
        $placeholderPatterns = [
            'Enter the lesson',
            'e.g.',
            'List each objective',
            'Enter words separated',
            'Enter materials separated',
            'Enter methods separated',
            'Explain how this lesson connects',
            'Enter the full lesson content',
            'List each step on a new line',
            'Describe the teaching strategies',
            'List each question on a new line',
            'Explain how the lesson is brought',
            'Enter the assignment or homework',
            'Type or paste your',
            'You can use multiple paragraphs',
            'The content you write here',
            'At the end of the lesson',
            'First learning objective',
            'Second learning objective',
            'Third learning objective',
            'First step of your presentation',
            'Second step of your presentation',
            'Third step of your presentation',
            'First evaluation question',
            'Second evaluation question',
            'Third evaluation question',
            'Fill in each section below',
        ];

        foreach ($placeholderPatterns as $pattern) {
            if (Str::contains($text, $pattern, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get a single line of text from a section.
     */
    private function getSingleLine(array $sections, string $key): ?string
    {
        $lines = $sections[$key] ?? [];
        $text = trim(implode(' ', $lines));
        return $text !== '' ? $text : null;
    }

    /**
     * Get a numbered list from a section.
     * Strips leading numbers/bullets (e.g. "1. ", "2) ", "- ").
     */
    private function getNumberedList(array $sections, string $key): array
    {
        $lines = $sections[$key] ?? [];
        $items = [];

        foreach ($lines as $line) {
            // Strip leading numbers, bullets, dashes
            $cleaned = preg_replace('/^\s*(?:\d+[.)\-]\s*|[\-•*]\s*)/', '', trim($line));
            $cleaned = trim($cleaned);
            if ($cleaned !== '') {
                $items[] = $cleaned;
            }
        }

        return $items;
    }

    /**
     * Get comma-separated items as an array.
     */
    private function getCommaSeparatedList(array $sections, string $key): array
    {
        $text = $this->getSingleLine($sections, $key);
        if ($text === null) {
            return [];
        }

        return array_values(
            array_filter(
                array_map('trim', explode(',', $text)),
                fn ($item) => $item !== ''
            )
        );
    }

    /**
     * Get comma-separated items as a joined string.
     */
    private function getCommaSeparatedString(array $sections, string $key): ?string
    {
        $items = $this->getCommaSeparatedList($sections, $key);
        return !empty($items) ? implode(', ', $items) : null;
    }

    /**
     * Get rich text content as HTML paragraphs.
     */
    private function getRichText(array $sections, string $key): ?string
    {
        $lines = $sections[$key] ?? [];
        if (empty($lines)) {
            return null;
        }

        // Convert each line to an HTML paragraph
        $html = collect($lines)
            ->map(fn ($line) => '<p>' . e(trim($line)) . '</p>')
            ->implode("\n");

        return $html;
    }
}
