<?php

namespace App\Filament\Student\Pages;

use App\Models\Result;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class ViewResult extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-text';

    protected string $view = 'filament.student.pages.view-result';

    public $results;

    public function mount()
    {
        $student = Auth::guard('student')->user();
        if ($student) {
            $this->results = Result::where('student_id', $student->id)
                ->with(['academicSession', 'term', 'classroom'])
                ->latest()
                ->get();
        } else {
            $this->results = collect();
        }
    }
}
