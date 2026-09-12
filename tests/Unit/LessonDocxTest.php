<?php

namespace Tests\Unit;

use App\Services\LessonDocxParserService;
use App\Services\LessonDocxTemplateService;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use Tests\TestCase;

class LessonDocxTest extends TestCase
{
    public function test_can_generate_lesson_plan_template(): void
    {
        $service = new LessonDocxTemplateService();
        $response = $service->downloadLessonPlanTemplate();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('Lesson_Plan_Template.docx', (string) $response->headers->get('content-disposition'));
    }

    public function test_can_generate_lesson_note_template(): void
    {
        $service = new LessonDocxTemplateService();
        $response = $service->downloadLessonNoteTemplate();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('Lesson_Note_Template.docx', (string) $response->headers->get('content-disposition'));
    }

    public function test_can_parse_filled_lesson_plan_docx(): void
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();

        $section->addText('TITLE', ['bold' => true]);
        $section->addText('Introduction to Photosynthesis');

        $section->addText('TIME / DURATION', ['bold' => true]);
        $section->addText('45 minutes');

        $section->addText('SECTION / PERIOD', ['bold' => true]);
        $section->addText('Period 2');

        $section->addText('LEARNING OBJECTIVES', ['bold' => true]);
        $section->addText('1. Understand photosynthesis');
        $section->addText('2. Identify raw materials');

        $section->addText('KEY VOCABULARY WORDS', ['bold' => true]);
        $section->addText('chlorophyll, glucose, sunlight');

        $section->addText('REFERENCE MATERIALS', ['bold' => true]);
        $section->addText('Biology Textbook, Science Handbook');

        $section->addText('INSTRUCTIONAL MATERIALS', ['bold' => true]);
        $section->addText('Whiteboard, Potted plant');

        $section->addText('PRIOR KNOWLEDGE / BACKGROUND', ['bold' => true]);
        $section->addText('Students previously learned about plant parts.');

        $section->addText('CONTENT', ['bold' => true]);
        $section->addText('Photosynthesis is the process by which green plants make food.');
        $section->addText('It requires sunlight, water, and carbon dioxide.');

        $section->addText('TEACHING METHODS', ['bold' => true]);
        $section->addText('Discussion, Demonstration');

        $section->addText('PRESENTATION STEPS', ['bold' => true]);
        $section->addText('1. Review plant anatomy');
        $section->addText('2. Explain chemical equation');

        $section->addText('STRATEGIES AND ACTIVITIES', ['bold' => true]);
        $section->addText('Students will examine a leaf under the microscope.');

        $section->addText('EVALUATION QUESTIONS', ['bold' => true]);
        $section->addText('1. What is chlorophyll?');
        $section->addText('2. Write the formula for photosynthesis.');

        $section->addText('CONCLUSION', ['bold' => true]);
        $section->addText('Summarize the main points of the lesson.');

        $section->addText('ASSIGNMENT / HOMEWORK', ['bold' => true]);
        $section->addText('Complete exercises 1 to 5 on page 42.');

        $tempPath = tempnam(sys_get_temp_dir(), 'test_lp_') . '.docx';
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempPath);

        $parser = new LessonDocxParserService();
        $parsed = $parser->parseLessonPlan($tempPath);

        @unlink($tempPath);

        $this->assertEquals('Introduction to Photosynthesis', $parsed['title']);
        $this->assertEquals('45 minutes', $parsed['time']);
        $this->assertEquals('Period 2', $parsed['section']);
        $this->assertEquals(['Understand photosynthesis', 'Identify raw materials'], $parsed['learning_objectives']);
        $this->assertEquals('chlorophyll, glucose, sunlight', $parsed['key_vocabulary']);
        $this->assertEquals(['Biology Textbook', 'Science Handbook'], $parsed['reference_materials']);
        $this->assertEquals(['Whiteboard', 'Potted plant'], $parsed['instructional_materials']);
        $this->assertStringContainsString('Students previously learned', $parsed['prior_knowledge']);
        $this->assertStringContainsString('process by which green plants make food', $parsed['content']);
        $this->assertEquals(['Discussion', 'Demonstration'], $parsed['teaching_methods']);
        $this->assertEquals(['Review plant anatomy', 'Explain chemical equation'], $parsed['presentation_steps']);
        $this->assertStringContainsString('examine a leaf', $parsed['strategies_activities']);
        $this->assertEquals(['What is chlorophyll?', 'Write the formula for photosynthesis.'], $parsed['evaluation_questions']);
        $this->assertStringContainsString('Summarize the main points', $parsed['conclusion']);
        $this->assertStringContainsString('Complete exercises 1 to 5', $parsed['assignment']);
    }

    public function test_can_parse_filled_lesson_note_docx(): void
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();

        $section->addText('TITLE', ['bold' => true]);
        $section->addText('Plant Cells and Structure');

        $section->addText('LEARNING OBJECTIVES', ['bold' => true]);
        $section->addText('1. Identify cell wall');
        $section->addText('2. Differentiate plant and animal cells');

        $section->addText('LESSON NOTE CONTENT', ['bold' => true]);
        $section->addText('Plant cells have a rigid outer cell wall.');
        $section->addText('They also contain chloroplasts for photosynthesis.');

        $tempPath = tempnam(sys_get_temp_dir(), 'test_ln_') . '.docx';
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempPath);

        $parser = new LessonDocxParserService();
        $parsed = $parser->parseLessonNote($tempPath);

        @unlink($tempPath);

        $this->assertEquals('Plant Cells and Structure', $parsed['title']);
        $this->assertEquals(['Identify cell wall', 'Differentiate plant and animal cells'], $parsed['learning_objectives']);
        $this->assertStringContainsString('rigid outer cell wall', $parsed['content']);
        $this->assertStringContainsString('contain chloroplasts', $parsed['content']);
    }
}
