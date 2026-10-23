<?php

namespace App\Filament\Pages;

use App\Models\ClassMidtermConfig;
use App\Models\ClassMidtermConfigHead;
use App\Models\Classroom;
use App\Models\Score;
use App\Models\ScoreHead;
use App\Models\Session;
use App\Models\Setting;
use App\Models\SubjectResult;
use App\Models\Term;
use App\Models\TermScoringSnapshot;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ManageMidtermStructure extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static string | \UnitEnum | null $navigationGroup = 'Results';

    protected static ?string $title = 'Mid-Term Structure';

    protected static ?string $navigationLabel = 'Mid-Term Structure';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.manage-midterm-structure';

    public ?int $classroom_id = null;
    public int $midterm_weight = 40;
    public int $rawMax = 0;
    public bool $locked = false;

    /** @var array<int, array{score_head_id: int, name: string, max_score: int, max_score_override: int|null}> */
    public array $items = [];

    public ?int $copy_source_classroom_id = null;
    public array $apply_target_classroom_ids = [];

    public static function shouldRegisterNavigation(): bool
    {
        return filter_var(Setting::where('key', 'use_midterm_results')->value('value'), FILTER_VALIDATE_BOOLEAN);
    }

    public static function canAccess(): bool
    {
        return (Auth::user()?->canAccessAdminPage('class_score_structure') ?? false)
            && filter_var(Setting::where('key', 'use_midterm_results')->value('value'), FILTER_VALIDATE_BOOLEAN);
    }

    public function mount(): void
    {
        $firstClass = Classroom::active()->ordered()->first();
        if ($firstClass) {
            $this->classroom_id = $firstClass->id;
            $this->loadConfig();
        }
    }

    public function updatedClassroomId(): void
    {
        $this->loadConfig();
    }

    public function updated(string $name): void
    {
        if (str_starts_with($name, 'items.') || $name === 'midterm_weight') {
            $this->syncRawMax();
        }
    }

    public function loadConfig(): void
    {
        $this->items = [];
        $this->rawMax = 0;
        $this->locked = false;

        if (! $this->classroom_id) {
            return;
        }

        // Check if locked: if active term has a snapshot or scores entered for this classroom
        $activeSession = Session::active()->first();
        $activeTerm = $activeSession?->activeTerm;

        if ($activeSession && $activeTerm) {
            $hasSnapshot = TermScoringSnapshot::where('classroom_id', $this->classroom_id)
                ->where('session_id', $activeSession->id)
                ->where('term_id', $activeTerm->id)
                ->exists();

            $hasScores = Score::where('classroom_id', $this->classroom_id)
                ->where('session_id', $activeSession->id)
                ->where('term_id', $activeTerm->id)
                ->exists();

            $this->locked = $hasSnapshot || $hasScores;
        }

        $config = ClassMidtermConfig::with(['heads.scoreHead'])
            ->where('classroom_id', $this->classroom_id)
            ->first();

        if ($config) {
            $this->midterm_weight = (int) $config->midterm_weight;
            $this->items = $config->heads
                ->filter(fn ($h) => $h->scoreHead !== null)
                ->map(fn ($head) => [
                    'score_head_id'      => $head->score_head_id,
                    'name'               => $head->scoreHead->name,
                    'max_score'          => $head->scoreHead->max_score,
                    'max_score_override' => $head->max_score_override,
                ])
                ->values()
                ->toArray();
        } else {
            $this->midterm_weight = 40;
        }

        $this->syncRawMax();
    }

    public function toggleScoreHead(int $scoreHeadId): void
    {
        if ($this->locked && ! Auth::user()?->isSudo()) {
            Notification::make()->title('Configuration is locked because scoring has already started for this class.')->warning()->send();
            return;
        }

        $existingIndex = collect($this->items)->search(
            fn (array $item) => (int) $item['score_head_id'] === $scoreHeadId
        );

        if ($existingIndex !== false) {
            $this->removeItem((int) $existingIndex);
            return;
        }

        $scoreHead = ScoreHead::active()->midterm()->find($scoreHeadId);
        if (! $scoreHead) {
            Notification::make()->title('Midterm score head not found or inactive.')->warning()->send();
            return;
        }

        $this->items[] = [
            'score_head_id'      => $scoreHead->id,
            'name'               => $scoreHead->name,
            'max_score'          => $scoreHead->max_score,
            'max_score_override' => null,
        ];

        $this->syncRawMax();
    }

    public function removeItem(int $index): void
    {
        if ($this->locked && ! Auth::user()?->isSudo()) {
            Notification::make()->title('Configuration is locked.')->warning()->send();
            return;
        }

        unset($this->items[$index]);
        $this->items = array_values($this->items);
        $this->syncRawMax();
    }

    public function saveConfig(): void
    {
        if (! $this->classroom_id) {
            Notification::make()->title('Please select a classroom.')->warning()->send();
            return;
        }

        // Block activating midterm if class already has legacy scores, results, or legacy snapshot in active session & term
        $activeSession = Session::where('is_active', true)->first();
        $activeTerm = $activeSession ? Term::where('session_id', $activeSession->id)->where('is_active', true)->first() : null;

        if ($activeSession && $activeTerm) {
            $hasLegacyScores = Score::where('classroom_id', $this->classroom_id)
                ->where('session_id', $activeSession->id)
                ->where('term_id', $activeTerm->id)
                ->whereHas('scoreHead', fn ($q) => $q->where('type', '!=', 'midterm'))
                ->exists();

            $hasLegacyResults = SubjectResult::where('classroom_id', $this->classroom_id)
                ->where('session_id', $activeSession->id)
                ->where('term_id', $activeTerm->id)
                ->whereNull('midterm_score')
                ->exists();

            $hasLegacySnapshot = TermScoringSnapshot::where('classroom_id', $this->classroom_id)
                ->where('session_id', $activeSession->id)
                ->where('term_id', $activeTerm->id)
                ->where('mode', 'legacy')
                ->exists();

            if ($hasLegacyScores || $hasLegacyResults || $hasLegacySnapshot) {
                Notification::make()
                    ->title('Cannot Activate Mid-Term Structure')
                    ->body('Scores or results already exist in legacy mode for this class in the current term. Mid-term cannot be activated once legacy scoring has taken place.')
                    ->danger()
                    ->send();
                return;
            }
        }

        if ($this->locked && ! Auth::user()?->isSudo()) {
            Notification::make()->title('Configuration is locked because scoring has already started.')->danger()->send();
            return;
        }

        if ($this->midterm_weight < 1 || $this->midterm_weight > 99) {
            Notification::make()->title('Midterm weight must be between 1 and 99.')->danger()->send();
            return;
        }

        if (empty($this->items)) {
            Notification::make()->title('Please assign at least one midterm score head.')->warning()->send();
            return;
        }

        foreach ($this->items as $item) {
            $override = $item['max_score_override'];
            if ($override !== null && ($override < 1 || $override > $item['max_score'])) {
                Notification::make()
                    ->title("Invalid override for \"{$item['name']}\". It must be between 1 and {$item['max_score']}.")
                    ->danger()
                    ->send();
                return;
            }
        }

        DB::transaction(function () {
            $config = ClassMidtermConfig::updateOrCreate(
                ['classroom_id' => $this->classroom_id],
                [
                    'midterm_weight'     => $this->midterm_weight,
                    'calculation_method' => 'scale_to_weight',
                ]
            );

            $keptHeadIds = collect($this->items)->pluck('score_head_id')->toArray();
            $config->heads()->whereNotIn('score_head_id', $keptHeadIds)->delete();

            foreach ($this->items as $item) {
                ClassMidtermConfigHead::updateOrCreate(
                    [
                        'class_midterm_config_id' => $config->id,
                        'score_head_id'           => $item['score_head_id'],
                    ],
                    ['max_score_override' => $item['max_score_override'] ?: null]
                );
            }
        });

        Notification::make()->title('Mid-term structure saved successfully.')->success()->send();
    }

    public function copyFromClassroom(): void
    {
        if (! $this->copy_source_classroom_id) {
            Notification::make()->title('Please select a source class to copy from.')->warning()->send();
            return;
        }

        $sourceConfig = ClassMidtermConfig::with('heads.scoreHead')->where('classroom_id', $this->copy_source_classroom_id)->first();
        if (! $sourceConfig || $sourceConfig->heads->isEmpty()) {
            Notification::make()->title('Source class has no midterm configuration.')->warning()->send();
            return;
        }

        $this->midterm_weight = (int) $sourceConfig->midterm_weight;
        $this->items = $sourceConfig->heads
            ->filter(fn ($h) => $h->scoreHead !== null)
            ->map(fn ($head) => [
                'score_head_id'      => $head->score_head_id,
                'name'               => $head->scoreHead->name,
                'max_score'          => $head->scoreHead->max_score,
                'max_score_override' => $head->max_score_override,
            ])
            ->values()
            ->toArray();

        $this->syncRawMax();
        Notification::make()->title('Configuration copied. Review and click Save to apply.')->info()->send();
    }

    public function applyToMultipleClasses(): void
    {
        if (empty($this->apply_target_classroom_ids)) {
            Notification::make()->title('Please select at least one target class.')->warning()->send();
            return;
        }

        if (empty($this->items)) {
            Notification::make()->title('Current configuration has no score heads to copy.')->warning()->send();
            return;
        }

        $activeSession = Session::where('is_active', true)->first();
        $activeTerm = $activeSession ? Term::where('session_id', $activeSession->id)->where('is_active', true)->first() : null;

        DB::transaction(function () use ($activeSession, $activeTerm) {
            foreach ($this->apply_target_classroom_ids as $targetClassId) {
                if ($activeSession && $activeTerm) {
                    // Skip any class with existing legacy scores or results in active session & term
                    $hasLegacy = Score::where('classroom_id', $targetClassId)
                        ->where('session_id', $activeSession->id)
                        ->where('term_id', $activeTerm->id)
                        ->whereHas('scoreHead', fn ($q) => $q->where('type', '!=', 'midterm'))
                        ->exists()
                        || SubjectResult::where('classroom_id', $targetClassId)
                            ->where('session_id', $activeSession->id)
                            ->where('term_id', $activeTerm->id)
                            ->whereNull('midterm_score')
                            ->exists()
                        || TermScoringSnapshot::where('classroom_id', $targetClassId)
                            ->where('session_id', $activeSession->id)
                            ->where('term_id', $activeTerm->id)
                            ->where('mode', 'legacy')
                            ->exists();

                    if ($hasLegacy) {
                        continue;
                    }
                }

                $config = ClassMidtermConfig::updateOrCreate(
                    ['classroom_id' => $targetClassId],
                    [
                        'midterm_weight'     => $this->midterm_weight,
                        'calculation_method' => 'scale_to_weight',
                    ]
                );

                $keptHeadIds = collect($this->items)->pluck('score_head_id')->toArray();
                $config->heads()->whereNotIn('score_head_id', $keptHeadIds)->delete();

                foreach ($this->items as $item) {
                    ClassMidtermConfigHead::updateOrCreate(
                        [
                            'class_midterm_config_id' => $config->id,
                            'score_head_id'           => $item['score_head_id'],
                        ],
                        ['max_score_override' => $item['max_score_override'] ?: null]
                    );
                }
            }
        });

        $count = count($this->apply_target_classroom_ids);
        $this->apply_target_classroom_ids = [];
        Notification::make()->title("Configuration applied to {$count} classes successfully.")->success()->send();
    }

    public function syncRawMax(): int
    {
        $sum = 0;
        foreach ($this->items as $item) {
            $sum += (int) ($item['max_score_override'] ?: $item['max_score']);
        }
        $this->rawMax = $sum;
        return $sum;
    }

    public function getClassroomsProperty()
    {
        return Classroom::active()->ordered()->get();
    }

    public function getMidtermScoreHeadsProperty()
    {
        return ScoreHead::active()->midterm()->orderBy('name')->get();
    }

    public function getSelectedScoreHeadIdsProperty(): array
    {
        return collect($this->items)->pluck('score_head_id')->map(fn ($id) => (int) $id)->all();
    }

    public function getExamWeightProperty(): int
    {
        return max(1, 100 - $this->midterm_weight);
    }
}
