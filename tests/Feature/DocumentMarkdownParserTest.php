<?php

namespace Tests\Feature;

use App\Filament\Teacher\Resources\TeacherLessonNoteResource\Pages\CreateTeacherLessonNote;
use App\Models\Classroom;
use App\Models\LessonNote;
use App\Models\LessonNoteVersion;
use App\Models\Session;
use App\Models\Subject;
use App\Models\SubmissionWindow;
use App\Models\TeacherSubjectAssignment;
use App\Models\Term;
use App\Models\User;
use App\Services\DocumentMarkdownParserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use Tests\TestCase;

class DocumentMarkdownParserTest extends TestCase {
    use RefreshDatabase;

    protected User $teacher;
    protected Subject $subject;
    protected Classroom $classroom;
    protected Session $session;
    protected Term $term;

    protected function setUp(): void {
        parent::setUp();

        Storage::fake( 'public' );
        Storage::fake( 'local' );

        $this->session = Session::factory()->create( [ 'is_active' => true ] );
        $this->term = Term::factory()->create( [ 'session_id' => $this->session->id, 'is_active' => true ] );

        $this->teacher = User::factory()->create( [ 'role' => 'teacher', 'is_active' => true ] );
        $this->subject = Subject::factory()->create();
        $this->classroom = Classroom::factory()->create();

        TeacherSubjectAssignment::create( [
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'classroom_id' => $this->classroom->id,
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
        ] );

        SubmissionWindow::create( [
            'session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'week_number' => 1,
        ] );

        \Filament\Facades\Filament::setCurrentPanel( \Filament\Facades\Filament::getPanel( 'teacher' ) );
    }

    public function test_local_parser_extracts_structured_data_from_plain_text(): void {
        $text = <<<TEXT
        Topic: Respiration in Plants and Animals

        Learning Objectives:
        1. Define aerobic and anaerobic respiration.
        2. Differentiate between respiration and breathing.
        3. Identify organs involved in respiration.

        Lesson Content:
        Respiration is the biochemical process in which the cells of an organism obtain energy by combining oxygen and glucose.

        ### Key Concepts
        - Energy is released in the form of ATP.
        - Occurs inside the mitochondria.

        ### Evaluation Questions
        1. What is the chemical equation for cellular respiration?
        2. Mention two differences between aerobic and anaerobic respiration.
        TEXT;

        $tmpFile = tempnam( sys_get_temp_dir(), 'test_doc_' ) . '.txt';
        file_put_contents( $tmpFile, $text );

        $service = new DocumentMarkdownParserService();
        $result = $service->parse( $tmpFile, 'respiration_notes.txt' );

        @unlink( $tmpFile );

        $this->assertEquals( 'RESPIRATION IN PLANTS AND ANIMALS', $result[ 'title' ] );
        $this->assertCount( 3, $result[ 'learning_objectives' ] );
        $this->assertStringContainsString( 'Define aerobic and anaerobic respiration', $result[ 'learning_objectives' ][ 0 ] );
        $this->assertStringContainsString( '<h3>Key Concepts</h3>', $result[ 'content' ] );
        $this->assertStringContainsString( 'local_parser', $result[ 'source' ] );
    }

    public function test_docx_document_parsing_with_phpword(): void {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();
        $section->addText( 'Topic: Photosynthesis and Energy Transformation', [ 'bold' => true, 'size' => 16 ] );
        $section->addText( 'Learning Objectives:' );
        $section->addListItem( 'Explain the light reaction of photosynthesis.', 0 );
        $section->addListItem( 'Describe the dark reaction / Calvin cycle.', 0 );
        $section->addText( 'Photosynthesis is the process used by plants to convert light energy into chemical energy.' );

        $tmpDocx = tempnam( sys_get_temp_dir(), 'test_docx_' ) . '.docx';
        $objWriter = IOFactory::createWriter( $phpWord, 'Word2007' );
        $objWriter->save( $tmpDocx );

        $service = new DocumentMarkdownParserService();
        $result = $service->parse( $tmpDocx, 'photosynthesis.docx' );

        @unlink( $tmpDocx );

        $this->assertEquals( 'PHOTOSYNTHESIS AND ENERGY TRANSFORMATION', $result[ 'title' ] );
        $this->assertNotEmpty( $result[ 'learning_objectives' ] );
        $this->assertStringContainsString( 'Photosynthesis is the process used by plants', $result[ 'content' ] );
    }

    public function test_gemini_ai_parser_integration(): void {
        config( [ 'services.gemini.api_key' => 'fake-gemini-test-key' ] );

        Http::fake( [
            'https://generativelanguage.googleapis.com/*' => Http::response( [
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode( [
                                        'title' => 'PHOTOSYNTHESIS & PLANT NUTRITION',
                                        'learning_objectives' => [
                                            'Explain the importance of chlorophyll in photosynthesis',
                                            'Outline the chemical equation for photosynthesis',
                                            'Identify the factors affecting the rate of photosynthesis'
                                        ],
                                        'markdown_content' => '## Introduction\n\nPhotosynthesis produces glucose.\n\n### Key Stages\n\n1. Light-dependent\n2. Light-independent',
                                        'html_content' => '<h2>Introduction</h2><p>Photosynthesis produces glucose.</p><h3>Key Stages</h3><ol><li>Light-dependent</li><li>Light-independent</li></ol>',
                                    ] )
                                ]
                            ]
                        ]
                    ]
                ]
            ], 200 ),
        ] );

        $tmpFile = tempnam( sys_get_temp_dir(), 'ai_test_' ) . '.txt';
        file_put_contents( $tmpFile, 'Some raw teacher notes about plant biology.' );

        $service = new DocumentMarkdownParserService();
        $result = $service->parse( $tmpFile, 'biology.txt' );

        @unlink( $tmpFile );

        $this->assertEquals( 'PHOTOSYNTHESIS & PLANT NUTRITION', $result[ 'title' ] );
        $this->assertCount( 3, $result[ 'learning_objectives' ] );
        $this->assertStringContainsString( '<h2>Introduction</h2>', $result[ 'content' ] );
        $this->assertEquals( 'ai_gemini', $result[ 'source' ] );
    }

    public function test_gemini_fallback_to_local_parser_on_api_failure(): void {
        config( [ 'services.gemini.api_key' => 'fake-gemini-test-key' ] );

        // Simulate Gemini API outage / 500 error
        Http::fake( [
            'https://generativelanguage.googleapis.com/*' => Http::response( [ 'error' => 'Internal server error' ], 500 ),
        ] );

        $text = <<<TEXT
Topic: Thermal Expansion of Metals

Learning Objectives:
- Define linear expansivity
- State applications of bimetallic strips

Metals expand when heated.
TEXT;

        $tmpFile = tempnam( sys_get_temp_dir(), 'fallback_test_' ) . '.txt';
        file_put_contents( $tmpFile, $text );

        $service = new DocumentMarkdownParserService();
        $result = $service->parse( $tmpFile, 'physics.txt' );

        @unlink( $tmpFile );

        $this->assertEquals( 'THERMAL EXPANSION OF METALS', $result[ 'title' ] );
        $this->assertCount( 2, $result[ 'learning_objectives' ] );
        $this->assertEquals( 'local_parser', $result[ 'source' ] );
    }

    public function test_teacher_can_upload_document_file_in_lesson_note_form(): void {
        $this->actingAs( $this->teacher );

        $documentContent = <<<TEXT
        Topic: Introduction to Binary Numbers

        Learning Objectives:
        - Convert decimal numbers to binary.
        - Perform binary addition and subtraction.

        Lesson Content:
        Binary is a base-2 numeral system that uses only two symbols: 0 and 1.
        Computers use binary logic to process instructions and store data.
        TEXT;

        $file = UploadedFile::fake()->createWithContent( 'binary_arithmetic.txt', $documentContent );

        Livewire::test( CreateTeacherLessonNote::class )
        ->set( 'data.subject_id', $this->subject->id )
        ->set( 'data.classroom_id', $this->classroom->id )
        ->set( 'data.week_number', 1 )
        ->set( 'data.submission_type', 'template' )
        ->set( 'data.template_file', $file )
        ->call( 'create' )
        ->assertHasNoFormErrors();

        $lessonNote = LessonNote::where( 'teacher_id', $this->teacher->id )
        ->where( 'subject_id', $this->subject->id )
        ->where( 'classroom_id', $this->classroom->id )
        ->where( 'week_number', 1 )
        ->first();

        $this->assertNotNull( $lessonNote );
        $this->assertEquals( 'draft', $lessonNote->status );
        $this->assertNotEmpty( $lessonNote->learning_objectives );

        $version = $lessonNote->latestVersion;
        $this->assertNotNull( $version );
        $this->assertTrue( $version->isWritten() );
        $this->assertEquals( 'INTRODUCTION TO BINARY NUMBERS', $version->title );
        $this->assertStringContainsString( 'Binary is a base-2 numeral system', $version->content );

        // Ensure file was NEVER placed on the public disk
        $this->assertEmpty( Storage::disk( 'public' )->allFiles( 'lesson-note-uploads/temp' ) );

        // Ensure temp file was cleaned up from the local private disk
        $this->assertEmpty( Storage::disk( 'local' )->allFiles( 'lesson-note-uploads/temp' ) );
    }

    public function test_markdown_to_html_converts_mixed_lists_and_headings_accurately(): void {
        $service = new DocumentMarkdownParserService();

        $markdown = <<<MD
# Chapter 1

Here is an introductory paragraph.

* Bullet 1
* Bullet 2

## Intermediate Heading

1. First step
2. Second step

Final paragraph with **bold** text.
MD;

        $html = $service->markdownToHtml($markdown);

        // Headings must exist outside of any list
        $this->assertStringContainsString('<h1>Chapter 1</h1>', $html);
        $this->assertStringContainsString('<h2>Intermediate Heading</h2>', $html);

        // Bullet lists must be in <ul>
        $this->assertStringContainsString('<ul>', $html);
        $this->assertStringContainsString('<li>Bullet 1</li>', $html);
        $this->assertStringContainsString('<li>Bullet 2</li>', $html);
        $this->assertStringContainsString('</ul>', $html);

        // Numbered lists must be in <ol>
        $this->assertStringContainsString('<ol>', $html);
        $this->assertStringContainsString('<li>First step</li>', $html);
        $this->assertStringContainsString('<li>Second step</li>', $html);
        $this->assertStringContainsString('</ol>', $html);

        // Heading must NOT be wrapped inside <ul>
        $this->assertDoesNotMatchRegularExpression('/<ul>[\s\S]*<h2>Intermediate Heading<\/h2>[\s\S]*<\/ul>/', $html);

        // Bold formatting
        $this->assertStringContainsString('<strong>bold</strong>', $html);
    }

    public function test_markdown_to_html_strips_malicious_scripts(): void {
        $service = new DocumentMarkdownParserService();

        $malicious = <<<MD
# Safe Title

<script>alert('xss')</script>
<img src="x" onerror="evil()" />

Normal content here.
MD;

        $html = $service->markdownToHtml($malicious);

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('onerror', $html);
        $this->assertStringContainsString('<h1>Safe Title</h1>', $html);
        $this->assertStringContainsString('Normal content here.', $html);
    }
}
