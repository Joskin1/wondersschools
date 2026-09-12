<?php

namespace App\Services;

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Style\Font;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LessonDocxTemplateService
{
    /**
     * Generate and download a Lesson Plan .docx template.
     */
    public function downloadLessonPlanTemplate(): BinaryFileResponse
    {
        $phpWord = new PhpWord();
        $this->applyDefaultStyles($phpWord);

        $section = $phpWord->addSection();

        // Document title
        $section->addText(
            'LESSON PLAN TEMPLATE',
            ['bold' => true, 'size' => 18, 'color' => '1a365d'],
            ['alignment' => 'center', 'spaceAfter' => 200]
        );
        $section->addText(
            'Fill in each section below. Do not remove or rename the section headers.',
            ['italic' => true, 'size' => 10, 'color' => '6b7280'],
            ['alignment' => 'center', 'spaceAfter' => 400]
        );

        // Section 1: Title
        $this->addSectionHeader($section, 'TITLE');
        $this->addPlaceholder($section, 'Enter the lesson title here (e.g. Introduction to Photosynthesis)');
        $section->addTextBreak();

        // Section 2: Time / Duration
        $this->addSectionHeader($section, 'TIME / DURATION');
        $this->addPlaceholder($section, 'e.g. 40 minutes');
        $section->addTextBreak();

        // Section 3: Section / Period
        $this->addSectionHeader($section, 'SECTION / PERIOD');
        $this->addPlaceholder($section, 'e.g. Morning, Period 3');
        $section->addTextBreak();

        // Section 4: Learning Objectives
        $this->addSectionHeader($section, 'LEARNING OBJECTIVES');
        $this->addPlaceholder($section, 'List each objective on a new line. At the end of the lesson, students should be able to:');
        $this->addPlaceholder($section, '1. First learning objective');
        $this->addPlaceholder($section, '2. Second learning objective');
        $this->addPlaceholder($section, '3. Third learning objective');
        $section->addTextBreak();

        // Section 5: Key Vocabulary Words
        $this->addSectionHeader($section, 'KEY VOCABULARY WORDS');
        $this->addPlaceholder($section, 'Enter words separated by commas (e.g. photosynthesis, chlorophyll, glucose)');
        $section->addTextBreak();

        // Section 6: Reference Materials
        $this->addSectionHeader($section, 'REFERENCE MATERIALS');
        $this->addPlaceholder($section, 'Enter materials separated by commas (e.g. Mathematics Textbook for JSS 2, National Curriculum Guide)');
        $section->addTextBreak();

        // Section 7: Instructional Materials
        $this->addSectionHeader($section, 'INSTRUCTIONAL MATERIALS');
        $this->addPlaceholder($section, 'Enter materials separated by commas (e.g. Whiteboard, Projector, Charts, Markers)');
        $section->addTextBreak();

        // Section 8: Prior Knowledge / Background
        $this->addSectionHeader($section, 'PRIOR KNOWLEDGE / BACKGROUND');
        $this->addPlaceholder($section, 'Explain how this lesson connects to what students have previously learned...');
        $section->addTextBreak();

        // Section 9: Content
        $this->addSectionHeader($section, 'CONTENT');
        $this->addPlaceholder($section, 'Enter the full lesson content here. You can use multiple paragraphs...');
        $section->addTextBreak();

        // Section 10: Teaching Methods
        $this->addSectionHeader($section, 'TEACHING METHODS');
        $this->addPlaceholder($section, 'Enter methods separated by commas (e.g. Discussion Method, Group Work, Demonstration)');
        $section->addTextBreak();

        // Section 11: Presentation Steps
        $this->addSectionHeader($section, 'PRESENTATION STEPS');
        $this->addPlaceholder($section, 'List each step on a new line:');
        $this->addPlaceholder($section, '1. First step of your presentation');
        $this->addPlaceholder($section, '2. Second step of your presentation');
        $this->addPlaceholder($section, '3. Third step of your presentation');
        $section->addTextBreak();

        // Section 12: Strategies and Activities
        $this->addSectionHeader($section, 'STRATEGIES AND ACTIVITIES');
        $this->addPlaceholder($section, 'Describe the teaching strategies and student activities for this lesson...');
        $section->addTextBreak();

        // Section 13: Evaluation Questions
        $this->addSectionHeader($section, 'EVALUATION QUESTIONS');
        $this->addPlaceholder($section, 'List each question on a new line:');
        $this->addPlaceholder($section, '1. First evaluation question');
        $this->addPlaceholder($section, '2. Second evaluation question');
        $this->addPlaceholder($section, '3. Third evaluation question');
        $section->addTextBreak();

        // Section 14: Conclusion
        $this->addSectionHeader($section, 'CONCLUSION');
        $this->addPlaceholder($section, 'Explain how the lesson is brought to a close...');
        $section->addTextBreak();

        // Section 15: Assignment / Homework
        $this->addSectionHeader($section, 'ASSIGNMENT / HOMEWORK');
        $this->addPlaceholder($section, 'Enter the assignment or homework given to students after this lesson...');

        return $this->saveAndDownload($phpWord, 'Lesson_Plan_Template.docx');
    }

    /**
     * Generate and download a Lesson Note .docx template.
     */
    public function downloadLessonNoteTemplate(): BinaryFileResponse
    {
        $phpWord = new PhpWord();
        $this->applyDefaultStyles($phpWord);

        $section = $phpWord->addSection();

        // Document title
        $section->addText(
            'LESSON NOTE TEMPLATE',
            ['bold' => true, 'size' => 18, 'color' => '1a365d'],
            ['alignment' => 'center', 'spaceAfter' => 200]
        );
        $section->addText(
            'Fill in each section below. Do not remove or rename the section headers.',
            ['italic' => true, 'size' => 10, 'color' => '6b7280'],
            ['alignment' => 'center', 'spaceAfter' => 400]
        );

        // Section 1: Title
        $this->addSectionHeader($section, 'TITLE');
        $this->addPlaceholder($section, 'Enter the lesson topic / title here (e.g. Introduction to Photosynthesis & Plant Nutrition)');
        $section->addTextBreak();

        // Section 2: Learning Objectives
        $this->addSectionHeader($section, 'LEARNING OBJECTIVES');
        $this->addPlaceholder($section, 'List each objective on a new line. At the end of the lesson, students should be able to:');
        $this->addPlaceholder($section, '1. First learning objective');
        $this->addPlaceholder($section, '2. Second learning objective');
        $this->addPlaceholder($section, '3. Third learning objective');
        $section->addTextBreak();

        // Section 3: Lesson Note Content
        $this->addSectionHeader($section, 'LESSON NOTE CONTENT');
        $this->addPlaceholder($section, 'Type or paste your full lesson note content here.');
        $this->addPlaceholder($section, 'You can use multiple paragraphs, include key concepts, definitions, examples, and explanations.');
        $this->addPlaceholder($section, 'The content you write here will be saved as the lesson note body.');

        return $this->saveAndDownload($phpWord, 'Lesson_Note_Template.docx');
    }

    /**
     * Apply default document styles.
     */
    private function applyDefaultStyles(PhpWord $phpWord): void
    {
        $phpWord->setDefaultFontName('Calibri');
        $phpWord->setDefaultFontSize(11);
    }

    /**
     * Add a bold section header.
     */
    private function addSectionHeader($section, string $title): void
    {
        $section->addText(
            $title,
            ['bold' => true, 'size' => 13, 'color' => '111827'],
            ['spaceBefore' => 200, 'spaceAfter' => 100, 'borderBottomSize' => 6, 'borderBottomColor' => 'd1d5db']
        );
    }

    /**
     * Add placeholder / instruction text.
     */
    private function addPlaceholder($section, string $text): void
    {
        $section->addText(
            $text,
            ['italic' => true, 'size' => 11, 'color' => '9ca3af']
        );
    }

    /**
     * Save the PhpWord document to a temp file and return as download.
     */
    private function saveAndDownload(PhpWord $phpWord, string $filename): BinaryFileResponse
    {
        $path = tempnam(sys_get_temp_dir(), 'lesson-template-') . '.docx';
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($path);

        return response()->download($path, $filename)->deleteFileAfterSend();
    }
}
