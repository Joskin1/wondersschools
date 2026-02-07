<?php

namespace App\Filament\Student\Pages;

use App\Models\Result;
use App\Services\ResultSettingsService;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class ResultPrintPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-printer';
    protected static string $view = 'filament.student.pages.result-print';
    protected static ?string $title = 'Print Result';
    protected static bool $shouldRegisterNavigation = false;

    public ?Result $result = null;
    public array $settings = [];

    public function mount(?int $result = null): void
    {
        $student = Auth::guard('student')->user();
        
        if ($result) {
            $this->result = Result::where('id', $result)
                ->where('student_id', $student->id)
                ->firstOrFail();
        } else {
            $this->result = Result::where('student_id', $student->id)
                ->latest('generated_at')
                ->firstOrFail();
        }

        // Load settings
        $settingsService = app(ResultSettingsService::class);
        $this->settings = $settingsService->getSettings($this->result->settings_name);
    }

    public function getResultData(): array
    {
        return $this->result->result_data ?? [];
    }

    public function getStudent()
    {
        return $this->result->student;
    }

    public function getClassroom()
    {
        return $this->result->classroom;
    }

    public function getComments()
    {
        return $this->result->comments;
    }

    public function getSetting(string $key, mixed $default = null): mixed
    {
        return $this->settings[$key] ?? $default;
    }
}
