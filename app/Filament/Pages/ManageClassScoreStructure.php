<?php

namespace App\Filament\Pages;

use App\Models\ClassScoreStructure;
use App\Models\ClassScoreStructureItem;
use App\Models\Classroom;
use App\Models\Score;
use App\Models\ScoreHead;
use App\Models\Session;
use App\Models\Term;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ManageClassScoreStructure extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-squares-plus';

    protected static string | \UnitEnum | null $navigationGroup = 'Results';

    protected static ?string $title = 'Score Structure';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.manage-class-score-structure';

    // ── Filter state ──────────────────────────────────────────────────────────

    public ?int $session_id   = null;
    public ?int $term_id      = null;
    public ?int $classroom_id = null;

    // ── Structure state ───────────────────────────────────────────────────────

    public ?int  $structureId        = null;
    public bool  $locked             = false;
    public int   $totalScore         = 0;

    /** @var array<int, array{score_head_id: int, name: string, max_score: int, max_score_override: int|null}> */
    public array $items = [];

    public ?int $selectedScoreHeadId = null;

    // ── Lifecycle ─────────────────────────────────────────────────────────────

    public function mount(): void
    {
        $activeSession = Session::active()->first();
        if ($activeSession) {
            $this->session_id = $activeSession->id;
            $activeTerm = $activeSession->activeTerm;
            if ($activeTerm) {
                $this->term_id = $activeTerm->id;
            }
        }
    }

    // ── Livewire updater hooks ────────────────────────────────────────────────

    public function updatedSessionId(): void
    {
        $this->term_id      = null;
        $this->classroom_id = null;
        $this->resetStructure();
    }

    public function updatedTermId(): void
    {
        $this->classroom_id = null;
        $this->resetStructure();
    }

    public function updatedClassroomId(): void
    {
        $this->loadStructure();
    }

    public function updated(string $name): void
    {
        // Recalculate total whenever any item override changes
        if (str_starts_with($name, 'items.')) {
            $this->syncTotal();
        }
    }

    // ── Actions ───────────────────────────────────────────────────────────────

    public function loadStructure(): void
    {
        $this->resetStructure();

        if (! $this->session_id || ! $this->term_id || ! $this->classroom_id) {
            return;
        }

        $structure = ClassScoreStructure::with(['items.scoreHead'])
            ->where('class_id',   $this->classroom_id)
            ->where('session_id', $this->session_id)
            ->where('term_id',    $this->term_id)
            ->first();

        if (! $structure) {
            return;
        }

        $this->structureId = $structure->id;
        $this->locked      = $structure->locked;
        $this->items       = $structure->items
            ->map(fn ($item) => [
                'score_head_id'      => $item->score_head_id,
                'name'               => $item->scoreHead->name,
                'max_score'          => $item->scoreHead->max_score,
                'max_score_override' => $item->max_score_override,
            ])
            ->toArray();

        $this->syncTotal();
    }

    public function addItem(): void
    {
        if ($this->locked && ! Auth::user()?->isSudo()) {
            Notification::make()->title('Structure is locked.')->warning()->send();
            return;
        }

        if (! $this->selectedScoreHeadId) {
            return;
        }

        if (collect($this->items)->pluck('score_head_id')->contains($this->selectedScoreHeadId)) {
            Notification::make()->title('That score head is already in the structure.')->warning()->send();
            return;
        }

        $scoreHead = ScoreHead::find($this->selectedScoreHeadId);
        if (! $scoreHead) {
            return;
        }

        $newTotal = $this->totalScore + $scoreHead->max_score;
        if ($newTotal > 100) {
            Notification::make()
                ->title("Adding \"{$scoreHead->name}\" would bring the total to {$newTotal} (max 100).")
                ->danger()
                ->send();
            return;
        }

        $this->items[] = [
            'score_head_id'      => $scoreHead->id,
            'name'               => $scoreHead->name,
            'max_score'          => $scoreHead->max_score,
            'max_score_override' => null,
        ];

        $this->selectedScoreHeadId = null;
        $this->syncTotal();
    }

    public function removeItem(int $index): void
    {
        if ($this->locked && ! Auth::user()?->isSudo()) {
            Notification::make()->title('Structure is locked.')->warning()->send();
            return;
        }

        $item = $this->items[$index] ?? null;

        // Block removal if scores already exist for this score head in this class/session/term
        if ($item && $this->structureId) {
            $hasScores = Score::where('score_head_id', $item['score_head_id'])
                ->where('classroom_id', $this->classroom_id)
                ->where('session_id',   $this->session_id)
                ->where('term_id',      $this->term_id)
                ->exists();

            if ($hasScores) {
                Notification::make()
                    ->title("Cannot remove \"{$item['name']}\": scores have already been entered for it.")
                    ->danger()
                    ->send();
                return;
            }
        }

        unset($this->items[$index]);
        $this->items = array_values($this->items);
        $this->syncTotal();
    }

    public function saveStructure(): void
    {
        $user = Auth::user();

        if ($this->locked && ! $user?->isSudo()) {
            Notification::make()->title('Structure is locked. Only a super-admin can modify it.')->danger()->send();
            return;
        }

        if (! $this->session_id || ! $this->term_id || ! $this->classroom_id) {
            Notification::make()->title('Please select session, term, and class first.')->warning()->send();
            return;
        }

        if (empty($this->items)) {
            Notification::make()->title('Add at least one score head before saving.')->warning()->send();
            return;
        }

        $total = $this->syncTotal();

        if ($total > 100) {
            Notification::make()
                ->title("Total score ({$total}) exceeds 100. Adjust overrides or remove a score head.")
                ->danger()
                ->send();
            return;
        }

        DB::transaction(function () use ($total) {
            $structure = ClassScoreStructure::firstOrCreate(
                [
                    'class_id'   => $this->classroom_id,
                    'session_id' => $this->session_id,
                    'term_id'    => $this->term_id,
                ],
                ['total_score' => $total, 'locked' => false]
            );

            $structure->update(['total_score' => $total]);
            $this->structureId = $structure->id;

            // Remove items no longer in list
            $keptHeadIds = collect($this->items)->pluck('score_head_id')->toArray();
            $structure->items()->whereNotIn('score_head_id', $keptHeadIds)->delete();

            // Upsert remaining
            foreach ($this->items as $item) {
                ClassScoreStructureItem::updateOrCreate(
                    [
                        'class_score_structure_id' => $structure->id,
                        'score_head_id'            => $item['score_head_id'],
                    ],
                    ['max_score_override' => $item['max_score_override'] ?: null]
                );
            }
        });

        Notification::make()->title('Score structure saved successfully.')->success()->send();
    }

    public function toggleLock(): void
    {
        if (! $this->structureId) {
            return;
        }

        $user = Auth::user();

        if ($this->locked && ! $user?->isSudo()) {
            Notification::make()->title('Only a super-admin can unlock a structure.')->danger()->send();
            return;
        }

        $structure = ClassScoreStructure::find($this->structureId);
        if ($structure) {
            $newLocked = ! $structure->locked;
            $structure->update(['locked' => $newLocked]);
            $this->locked = $newLocked;

            Notification::make()
                ->title($newLocked ? 'Structure locked.' : 'Structure unlocked.')
                ->success()
                ->send();
        }
    }

    // ── Computed properties ───────────────────────────────────────────────────

    public function getSessionsProperty()
    {
        return Session::orderBy('start_year', 'desc')->get();
    }

    public function getTermsProperty()
    {
        if (! $this->session_id) {
            return collect();
        }
        return Term::where('session_id', $this->session_id)->orderBy('order')->get();
    }

    public function getClassroomsProperty()
    {
        return Classroom::active()->ordered()->get();
    }

    public function getAvailableScoreHeadsProperty()
    {
        $usedIds = collect($this->items)->pluck('score_head_id')->toArray();

        return ScoreHead::active()
            ->when(! empty($usedIds), fn ($q) => $q->whereNotIn('id', $usedIds))
            ->orderBy('name')
            ->get();
    }

    public static function canAccess(): bool
    {
        return Auth::user()?->canManageAcademics() ?? false;
    }

    // ── Internals ─────────────────────────────────────────────────────────────

    private function syncTotal(): int
    {
        $total = 0;
        foreach ($this->items as $item) {
            $total += (int) ($item['max_score_override'] ?: $item['max_score']);
        }
        $this->totalScore = $total;
        return $total;
    }

    private function resetStructure(): void
    {
        $this->structureId        = null;
        $this->locked             = false;
        $this->totalScore         = 0;
        $this->items              = [];
        $this->selectedScoreHeadId = null;
    }
}
