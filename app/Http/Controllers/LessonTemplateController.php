<?php

namespace App\Http\Controllers;

use App\Services\LessonDocxTemplateService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LessonTemplateController extends Controller
{
    public function __construct(
        private LessonDocxTemplateService $templateService
    ) {}

    /**
     * Download the Lesson Plan .docx template.
     */
    public function lessonPlanTemplate(Request $request): BinaryFileResponse
    {
        return $this->templateService->downloadLessonPlanTemplate();
    }

    /**
     * Download the Lesson Note .docx template.
     */
    public function lessonNoteTemplate(Request $request): BinaryFileResponse
    {
        return $this->templateService->downloadLessonNoteTemplate();
    }
}
