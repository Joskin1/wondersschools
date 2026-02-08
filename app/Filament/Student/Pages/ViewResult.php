<?php

namespace App\Filament\Student\Pages;

use App\Models\Result;
use App\Services\ResultCacheService;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Auth;

class ViewResult extends Page
{
    protected static string $view = 'filament.student.pages.view-result';
    protected static ?string $navigationLabel = 'My Results';
    protected static ?string $title = 'My Result';
    
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';

    public ?Result $result = null;
    public ?string $session = null;
    public ?int $term = null;

    public function mount(): void
    {
        $student = Auth::guard('student')->user();
        
        // Get latest result for current student
        $this->result = Result::where('student_id', $student->id)
            ->latest('generated_at')
            ->first();
        
        if ($this->result) {
            $this->session = $this->result->session;
            $this->term = $this->result->term;
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->label('Print Result')
                ->icon('heroicon-o-printer')
                ->url(fn () => route('filament.student.pages.result-print', [
                    'result' => $this->result?->id
                ]))
                ->openUrlInNewTab()
                ->visible(fn () => $this->result !== null),
            
            Action::make('first')
                ->label('First')
                ->icon('heroicon-o-chevron-double-left')
                ->action('navigateFirst')
                ->visible(fn () => $this->result !== null),
            
            Action::make('previous')
                ->label('Previous')
                ->icon('heroicon-o-chevron-left')
                ->action('navigatePrevious')
                ->visible(fn () => $this->result !== null),
            
            Action::make('next')
                ->label('Next')
                ->icon('heroicon-o-chevron-right')
                ->action('navigateNext')
                ->visible(fn () => $this->result !== null),
            
            Action::make('last')
                ->label('Last')
                ->icon('heroicon-o-chevron-double-right')
                ->action('navigateLast')
                ->visible(fn () => $this->result !== null),
        ];
    }

    public function navigateFirst(): void
    {
        $this->navigate('FIRST');
    }

    public function navigatePrevious(): void
    {
        $this->navigate('PREV');
    }

    public function navigateNext(): void
    {
        $this->navigate('NEXT');
    }

    public function navigateLast(): void
    {
        $this->navigate('LAST');
    }

    private function navigate(string $direction): void
    {
        if (!$this->result) {
            return;
        }

        $cacheService = app(ResultCacheService::class);
        
        $newResult = $cacheService->navigateResults(
            $this->result->cache_key,
            $direction,
            $this->result->classroom_id,
            $this->result->session,
            $this->result->term
        );

        if ($newResult) {
            $this->result = $newResult;
            $this->session = $newResult->session;
            $this->term = $newResult->term;
        }
    }

    public function getResultData(): array
    {
        if (!$this->result) {
            return [];
        }

        return $this->result->result_data ?? [];
    }
}
