<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\InstructionalMaterial;
use App\Models\ReferenceMaterial;
use App\Models\TeachingMethod;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;

class ManageLessonPlanOptions extends Page
{
    use WithPagination;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-tag';

    protected static string | \UnitEnum | null $navigationGroup = 'Lessons';

    protected static ?string $title = 'Lesson Plan Options';

    protected static ?string $navigationLabel = 'Lesson Plan Options';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'lesson-plan-options';

    protected string $view = 'filament.pages.manage-lesson-plan-options';

    public string $activeTab = 'reference_materials';

    public string $search = '';

    /** @var array<int, int> */
    public array $selectedIds = [];

    public bool $selectAll = false;

    // Create modal state
    public bool $showCreateModal = false;
    public string $newOptionName = '';

    // Edit modal state
    public bool $showEditModal = false;
    public ?int $editingOptionId = null;
    public string $editingOptionName = '';

    // Delete single modal state
    public bool $showDeleteModal = false;
    public ?int $deletingOptionId = null;
    public ?string $deletingOptionName = null;
    public int $deletingUsageCount = 0;

    // Bulk delete modal state
    public bool $showBulkDeleteModal = false;

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user && ($user->isSudo() || $user->canAccessAdminPage('lesson_notes'));
    }

    public function mount(): void
    {
        $tab = request()->query('tab');
        if (in_array($tab, ['reference_materials', 'instructional_materials', 'teaching_methods'], true)) {
            $this->activeTab = $tab;
        }
    }

    public function setTab(string $tab): void
    {
        if (in_array($tab, ['reference_materials', 'instructional_materials', 'teaching_methods'], true)) {
            $this->activeTab = $tab;
            $this->search = '';
            $this->selectedIds = [];
            $this->selectAll = false;
            $this->resetPage();
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
        $this->selectedIds = [];
        $this->selectAll = false;
    }

    public function getModelClass(): string
    {
        return match ($this->activeTab) {
            'instructional_materials' => InstructionalMaterial::class,
            'teaching_methods'        => TeachingMethod::class,
            default                   => ReferenceMaterial::class,
        };
    }

    public function getTabLabel(): string
    {
        return match ($this->activeTab) {
            'instructional_materials' => 'Instructional Materials',
            'teaching_methods'        => 'Teaching Methods',
            default                   => 'Reference Materials',
        };
    }

    public function getTabSingular(): string
    {
        return match ($this->activeTab) {
            'instructional_materials' => 'Instructional Material',
            'teaching_methods'        => 'Teaching Method',
            default                   => 'Reference Material',
        };
    }

    /**
     * @return array{reference_materials: int, instructional_materials: int, teaching_methods: int}
     */
    public function getCountsProperty(): array
    {
        return [
            'reference_materials'     => ReferenceMaterial::count(),
            'instructional_materials' => InstructionalMaterial::count(),
            'teaching_methods'        => TeachingMethod::count(),
        ];
    }

    public function getOptionsProperty(): LengthAwarePaginator
    {
        $model = $this->getModelClass();
        $query = $model::query()
            ->withCount('lessonPlans')
            ->orderBy('name', 'asc');

        $search = trim($this->search);
        if ($search !== '') {
            $query->where('name', 'like', "%{$search}%");
        }

        return $query->paginate(20);
    }

    public function updatedSelectAll(bool $value): void
    {
        if ($value) {
            $this->selectedIds = $this->options->pluck('id')->map(fn ($id) => (int) $id)->toArray();
        } else {
            $this->selectedIds = [];
        }
    }

    // ── Create Option ────────────────────────────────────────────────────────

    public function openCreateModal(): void
    {
        $this->newOptionName = '';
        $this->showCreateModal = true;
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
        $this->newOptionName = '';
    }

    public function createOption(): void
    {
        $name = trim($this->newOptionName);
        if ($name === '') {
            Notification::make()
                ->title('Name Required')
                ->body('Please provide a name for this option.')
                ->danger()
                ->send();
            return;
        }

        $model = $this->getModelClass();
        $existing = $model::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();
        if ($existing) {
            Notification::make()
                ->title('Already Exists')
                ->body("An entry named \"{$existing->name}\" already exists in {$this->getTabLabel()}.")
                ->warning()
                ->send();
            return;
        }

        $model::create(['name' => $name]);

        $this->newOptionName = '';
        $this->showCreateModal = false;

        Notification::make()
            ->title('Option Created')
            ->body("\"{$name}\" was added to {$this->getTabLabel()} and is ready for use.")
            ->success()
            ->send();
    }

    // ── Edit Option ──────────────────────────────────────────────────────────

    public function openEditModal(int $id): void
    {
        $model = $this->getModelClass();
        $item = $model::find($id);
        if (!$item) {
            return;
        }

        $this->editingOptionId = $id;
        $this->editingOptionName = $item->name;
        $this->showEditModal = true;
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->editingOptionId = null;
        $this->editingOptionName = '';
    }

    public function updateOption(): void
    {
        $name = trim($this->editingOptionName);
        if ($name === '' || !$this->editingOptionId) {
            Notification::make()
                ->title('Valid Name Required')
                ->danger()
                ->send();
            return;
        }

        $model = $this->getModelClass();
        $item = $model::find($this->editingOptionId);
        if ($item) {
            $duplicate = $model::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                ->where('id', '!=', $this->editingOptionId)
                ->first();

            if ($duplicate) {
                Notification::make()
                    ->title('Duplicate Name')
                    ->body("Another entry with name \"{$duplicate->name}\" already exists.")
                    ->warning()
                    ->send();
                return;
            }

            $oldName = $item->name;
            $item->update(['name' => $name]);

            Notification::make()
                ->title('Option Renamed')
                ->body("Updated \"{$oldName}\" to \"{$name}\". All associated lesson plans reflect this change.")
                ->success()
                ->send();
        }

        $this->showEditModal = false;
        $this->editingOptionId = null;
        $this->editingOptionName = '';
    }

    // ── Delete Single Option ─────────────────────────────────────────────────

    public function confirmDelete(int $id): void
    {
        $model = $this->getModelClass();
        $item = $model::withCount('lessonPlans')->find($id);
        if (!$item) {
            return;
        }

        $this->deletingOptionId = $id;
        $this->deletingOptionName = $item->name;
        $this->deletingUsageCount = $item->lesson_plans_count ?? 0;
        $this->showDeleteModal = true;
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deletingOptionId = null;
        $this->deletingOptionName = null;
        $this->deletingUsageCount = 0;
    }

    public function deleteOption(): void
    {
        if (!$this->deletingOptionId) {
            return;
        }

        $model = $this->getModelClass();
        $item = $model::find($this->deletingOptionId);
        if ($item) {
            $name = $item->name;
            $item->delete();

            $this->selectedIds = array_values(array_diff($this->selectedIds, [$this->deletingOptionId]));

            Notification::make()
                ->title('Option Deleted')
                ->body("\"{$name}\" has been permanently removed from {$this->getTabLabel()}.")
                ->success()
                ->send();
        }

        $this->closeDeleteModal();
    }

    // ── Bulk Delete ──────────────────────────────────────────────────────────

    public function confirmBulkDelete(): void
    {
        if (empty($this->selectedIds)) {
            Notification::make()
                ->title('No Items Selected')
                ->body('Please check at least one option to delete.')
                ->warning()
                ->send();
            return;
        }

        $this->showBulkDeleteModal = true;
    }

    public function closeBulkDeleteModal(): void
    {
        $this->showBulkDeleteModal = false;
    }

    public function deleteSelected(): void
    {
        if (empty($this->selectedIds)) {
            return;
        }

        $model = $this->getModelClass();
        $count = count($this->selectedIds);
        $model::whereIn('id', $this->selectedIds)->delete();

        $this->selectedIds = [];
        $this->selectAll = false;
        $this->showBulkDeleteModal = false;

        Notification::make()
            ->title('Options Deleted')
            ->body("{$count} options permanently removed from {$this->getTabLabel()}.")
            ->success()
            ->send();
    }
}
