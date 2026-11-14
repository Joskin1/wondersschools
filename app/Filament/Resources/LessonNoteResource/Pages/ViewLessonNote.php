<?php

namespace App\Filament\Resources\LessonNoteResource\Pages;

use App\Filament\Pages\ManageLessonPlanOptions;
use App\Filament\Resources\LessonNoteResource;
use App\Jobs\LogLessonNoteAction;
use App\Models\InstructionalMaterial;
use App\Models\LessonPlan;
use App\Models\ReferenceMaterial;
use App\Models\TeachingMethod;
use App\Notifications\LessonNoteReviewed;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ViewLessonNote extends ViewRecord
{
    protected static string $resource = LessonNoteResource::class;

    /** @var array<string, mixed>|null */
    public ?array $planData = [];

    public function getTitle(): string
    {
        return 'Review Lesson Submission';
    }

    public function mount(int | string $record): void
    {
        parent::mount($record);

        $this->loadPlanData();
    }

    public function loadPlanData(): void
    {
        $plan = $this->record->getPairedLessonPlan();
        if (!$plan) {
            $this->planData = [];
            return;
        }

        $plan->load(['referenceMaterials', 'instructionalMaterials', 'teachingMethods']);
        $data = $plan->toArray();

        if (isset($data['learning_objectives']) && is_array($data['learning_objectives'])) {
            $data['learning_objectives'] = collect($data['learning_objectives'])
                ->map(fn ($obj) => is_string($obj) ? ['objective' => $obj] : $obj)
                ->toArray();
        }

        if (isset($data['presentation_steps']) && is_array($data['presentation_steps'])) {
            $data['presentation_steps'] = collect($data['presentation_steps'])
                ->map(fn ($s) => is_string($s) ? ['step' => $s] : $s)
                ->toArray();
        }

        if (isset($data['evaluation_questions']) && is_array($data['evaluation_questions'])) {
            $data['evaluation_questions'] = collect($data['evaluation_questions'])
                ->map(fn ($q) => is_string($q) ? ['question' => $q] : $q)
                ->toArray();
        }

        if (isset($data['key_vocabulary']) && is_string($data['key_vocabulary'])) {
            $data['key_vocabulary'] = array_values(array_filter(array_map('trim', explode(',', $data['key_vocabulary']))));
        }

        if (empty($data['topic']) && !empty($data['title'])) {
            $data['topic'] = $data['title'];
        }

        $data['reference_materials'] = $plan->referenceMaterials->pluck('id')->toArray();
        $data['instructional_materials'] = $plan->instructionalMaterials->pluck('id')->toArray();
        $data['teaching_methods'] = $plan->teachingMethods->pluck('id')->toArray();

        $this->planData = $data;
        $this->planForm->fill($this->planData);
    }

    public function planForm(Schema $schema): Schema
    {
        $plan = $this->record->getPairedLessonPlan();

        if (!$plan) {
            return $schema
                ->statePath('planData')
                ->components([
                    Placeholder::make('no_plan')
                        ->label('')
                        ->content('No paired lesson plan found for this lesson note.')
                        ->columnSpanFull(),
                ]);
        }

        return $schema
            ->statePath('planData')
            ->model($plan)
            ->operation('edit')
            ->components([
                // 1. Lesson Information
                Section::make('1. Lesson Information')
                    ->description('Edit the topic, sub-topic, duration, and section.')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('topic')
                                ->label('Topic')
                                ->placeholder('e.g. INTRODUCTION TO PHOTOSYNTHESIS')
                                ->required()
                                ->maxLength(255)
                                ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                ->helperText('Main topic for this lesson plan (saved in ALL CAPS).'),

                            TextInput::make('sub_topic')
                                ->label('Sub-Topic (Optional)')
                                ->placeholder('e.g. Light & dark reactions of photosynthesis')
                                ->maxLength(255)
                                ->helperText('Specific sub-topic or focus area (first letter capitalized).'),
                        ]),

                        Grid::make(2)->schema([
                            TextInput::make('time')
                                ->label('Time / Duration')
                                ->placeholder('e.g. 40 minutes')
                                ->helperText('Duration of this lesson period'),

                            TextInput::make('section')
                                ->label('Section / Period')
                                ->placeholder('e.g. Morning, Period 3')
                                ->helperText('Which session or period this lesson covers'),
                        ]),
                    ]),

                // 2. Learning Objectives
                Section::make('2. Learning Objectives')
                    ->description('At the end of the lesson, students should be able to:')
                    ->schema([
                        Repeater::make('learning_objectives')
                            ->label('')
                            ->schema([
                                TextInput::make('objective')
                                    ->label('Objective')
                                    ->placeholder('Enter a learning objective…')
                                    ->required(),
                            ])
                            ->addActionLabel('Add Objective')
                            ->reorderable()
                            ->reorderableWithButtons()
                            ->deletable()
                            ->minItems(1)
                            ->default([['objective' => '']])
                            ->columnSpanFull(),
                    ]),

                // 3. Key Vocabulary Words
                Section::make('3. Key Vocabulary Words')
                    ->description('Important vocabulary and terminology associated with this lesson.')
                    ->schema([
                        TagsInput::make('key_vocabulary')
                            ->label('Vocabulary Words')
                            ->placeholder('Type a word and press Enter')
                            ->helperText('Enter each vocabulary word and press Enter or comma to add it.')
                            ->columnSpanFull(),
                    ]),

                // 4. Reference Materials
                Section::make('4. Reference Materials')
                    ->description('Reference materials used for this lesson.')
                    ->schema([
                        Select::make('reference_materials')
                            ->label('Reference Materials')
                            ->options(fn () => ReferenceMaterial::orderBy('name')->pluck('name', 'id'))
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->label('Material Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g. Mathematics Textbook for JSS 2'),
                            ])
                            ->createOptionUsing(function (array $data) {
                                return ReferenceMaterial::firstOrCreate(['name' => trim(Str::limit($data['name'], 252))])->id;
                            })
                            ->hintActions([
                                Action::make('quick_delete_ref_material')
                                    ->label('Delete redundant item')
                                    ->icon('heroicon-m-trash')
                                    ->color('danger')
                                    ->modalHeading('Delete Saved Reference Material')
                                    ->modalDescription('Select a redundant or inaccurate entry to permanently remove from future dropdowns.')
                                    ->form([
                                        Select::make('material_id')
                                            ->label('Select Item to Delete')
                                            ->options(fn () => ReferenceMaterial::orderBy('name')->pluck('name', 'id'))
                                            ->searchable()
                                            ->required(),
                                    ])
                                    ->requiresConfirmation()
                                    ->modalSubmitActionLabel('Yes, Delete Option')
                                    ->action(function (array $data) {
                                        if (!empty($data['material_id'])) {
                                            $item = ReferenceMaterial::find($data['material_id']);
                                            if ($item) {
                                                $name = $item->name;
                                                $item->delete();
                                                Notification::make()
                                                    ->title('Option Deleted')
                                                    ->body("Deleted \"{$name}\" from reference materials.")
                                                    ->success()
                                                    ->send();
                                            }
                                        }
                                    }),
                                Action::make('manage_reference_materials')
                                    ->label('Manage list')
                                    ->icon('heroicon-m-cog-6-tooth')
                                    ->color('primary')
                                    ->tooltip('Open the full options management dashboard in a new tab')
                                    ->url(fn () => ManageLessonPlanOptions::getUrl(['tab' => 'reference_materials']))
                                    ->openUrlInNewTab(),
                            ])
                            ->helperText('Select materials, click + to add new, or click actions above to clean/manage list.')
                            ->columnSpanFull(),
                    ]),

                // 5. Instructional Materials
                Section::make('5. Instructional Materials')
                    ->description('Instructional materials and teaching resources.')
                    ->schema([
                        Select::make('instructional_materials')
                            ->label('Instructional Materials')
                            ->options(fn () => InstructionalMaterial::orderBy('name')->pluck('name', 'id'))
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->label('Material Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g. Whiteboard, Projector, Charts'),
                            ])
                            ->createOptionUsing(function (array $data) {
                                return InstructionalMaterial::firstOrCreate(['name' => trim(Str::limit($data['name'], 252))])->id;
                            })
                            ->hintActions([
                                Action::make('quick_delete_inst_material')
                                    ->label('Delete redundant item')
                                    ->icon('heroicon-m-trash')
                                    ->color('danger')
                                    ->modalHeading('Delete Saved Instructional Material')
                                    ->modalDescription('Select a redundant or inaccurate entry to permanently remove from future dropdowns.')
                                    ->form([
                                        Select::make('material_id')
                                            ->label('Select Item to Delete')
                                            ->options(fn () => InstructionalMaterial::orderBy('name')->pluck('name', 'id'))
                                            ->searchable()
                                            ->required(),
                                    ])
                                    ->requiresConfirmation()
                                    ->modalSubmitActionLabel('Yes, Delete Option')
                                    ->action(function (array $data) {
                                        if (!empty($data['material_id'])) {
                                            $item = InstructionalMaterial::find($data['material_id']);
                                            if ($item) {
                                                $name = $item->name;
                                                $item->delete();
                                                Notification::make()
                                                    ->title('Option Deleted')
                                                    ->body("Deleted \"{$name}\" from instructional materials.")
                                                    ->success()
                                                    ->send();
                                            }
                                        }
                                    }),
                                Action::make('manage_instructional_materials')
                                    ->label('Manage list')
                                    ->icon('heroicon-m-cog-6-tooth')
                                    ->color('primary')
                                    ->tooltip('Open the full options management dashboard in a new tab')
                                    ->url(fn () => ManageLessonPlanOptions::getUrl(['tab' => 'instructional_materials']))
                                    ->openUrlInNewTab(),
                            ])
                            ->helperText('Select materials, click + to add new, or click actions above to clean/manage list.')
                            ->columnSpanFull(),
                    ]),

                // 6. Building Background / Prior Knowledge
                Section::make('6. Building Background / Connection to Prior Knowledge')
                    ->description('Connection to prior learning.')
                    ->schema([
                        RichEditor::make('prior_knowledge')
                            ->label('')
                            ->placeholder('Describe how this lesson connects to prior learning…')
                            ->toolbarButtons(['bold', 'italic', 'underline', 'bulletList', 'orderedList', 'undo', 'redo'])
                            ->columnSpanFull(),
                    ]),

                // 7. Content
                Section::make('7. Content')
                    ->description('Full lesson content.')
                    ->schema([
                        RichEditor::make('content')
                            ->label('')
                            ->placeholder('Enter the lesson content here…')
                            ->toolbarButtons([
                                'bold', 'italic', 'underline', 'strike',
                                'h2', 'h3', 'bulletList', 'orderedList',
                                'blockquote', 'codeBlock', 'link', 'undo', 'redo',
                            ])
                            ->columnSpanFull(),
                    ]),

                // 8. Teaching Method
                Section::make('8. Teaching Method')
                    ->description('Teaching methods used in this lesson.')
                    ->schema([
                        Select::make('teaching_methods')
                            ->label('Teaching Methods')
                            ->options(fn () => TeachingMethod::orderBy('name')->pluck('name', 'id'))
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->label('Method Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g. Discussion Method, Group Work'),
                            ])
                            ->createOptionUsing(function (array $data) {
                                return TeachingMethod::firstOrCreate(['name' => trim(Str::limit($data['name'], 252))])->id;
                            })
                            ->hintActions([
                                Action::make('quick_delete_teach_method')
                                    ->label('Delete redundant item')
                                    ->icon('heroicon-m-trash')
                                    ->color('danger')
                                    ->modalHeading('Delete Saved Teaching Method')
                                    ->modalDescription('Select a redundant or inaccurate entry to permanently remove from future dropdowns.')
                                    ->form([
                                        Select::make('method_id')
                                            ->label('Select Item to Delete')
                                            ->options(fn () => TeachingMethod::orderBy('name')->pluck('name', 'id'))
                                            ->searchable()
                                            ->required(),
                                    ])
                                    ->requiresConfirmation()
                                    ->modalSubmitActionLabel('Yes, Delete Option')
                                    ->action(function (array $data) {
                                        if (!empty($data['method_id'])) {
                                            $item = TeachingMethod::find($data['method_id']);
                                            if ($item) {
                                                $name = $item->name;
                                                $item->delete();
                                                Notification::make()
                                                    ->title('Option Deleted')
                                                    ->body("Deleted \"{$name}\" from teaching methods.")
                                                    ->success()
                                                    ->send();
                                            }
                                        }
                                    }),
                                Action::make('manage_teaching_methods')
                                    ->label('Manage list')
                                    ->icon('heroicon-m-cog-6-tooth')
                                    ->color('primary')
                                    ->tooltip('Open the full options management dashboard in a new tab')
                                    ->url(fn () => ManageLessonPlanOptions::getUrl(['tab' => 'teaching_methods']))
                                    ->openUrlInNewTab(),
                            ])
                            ->helperText('Select methods, click + to add new, or click actions above to clean/manage list.')
                            ->columnSpanFull(),
                    ]),

                // 9. Presentation Steps
                Section::make('9. Presentation Steps')
                    ->description('Sequence of presentation steps.')
                    ->schema([
                        Repeater::make('presentation_steps')
                            ->label('')
                            ->schema([
                                Textarea::make('step')
                                    ->label('Step')
                                    ->placeholder('Describe this presentation step…')
                                    ->rows(2)
                                    ->required(),
                            ])
                            ->addActionLabel('Add Step')
                            ->reorderable()
                            ->reorderableWithButtons()
                            ->deletable()
                            ->minItems(1)
                            ->default([['step' => '']])
                            ->columnSpanFull(),
                    ]),

                // 10. Strategies and Activities
                Section::make('10. Strategies and Activities')
                    ->description('Teaching strategies and student activities.')
                    ->schema([
                        RichEditor::make('strategies_activities')
                            ->label('')
                            ->placeholder('Describe strategies and activities…')
                            ->toolbarButtons(['bold', 'italic', 'underline', 'bulletList', 'orderedList', 'undo', 'redo'])
                            ->columnSpanFull(),
                    ]),

                // 11. Assessment / Evaluation
                Section::make('11. Assessment / Evaluation')
                    ->description('Questions to evaluate student understanding.')
                    ->schema([
                        Repeater::make('evaluation_questions')
                            ->label('')
                            ->schema([
                                TextInput::make('question')
                                    ->label('Question')
                                    ->placeholder('Enter an evaluation question…')
                                    ->required(),
                            ])
                            ->addActionLabel('Add Question')
                            ->reorderable()
                            ->reorderableWithButtons()
                            ->deletable()
                            ->minItems(1)
                            ->default([['question' => '']])
                            ->columnSpanFull(),
                    ]),

                // 12. Conclusion
                Section::make('12. Conclusion')
                    ->description('How the lesson is brought to a close.')
                    ->schema([
                        RichEditor::make('conclusion')
                            ->label('')
                            ->placeholder('Describe how the lesson concludes…')
                            ->toolbarButtons(['bold', 'italic', 'underline', 'bulletList', 'orderedList', 'undo', 'redo'])
                            ->columnSpanFull(),
                    ]),

                // 13. Assignment / Homework
                Section::make('13. Assignment / Homework')
                    ->description('Assignment or homework given to students.')
                    ->schema([
                        RichEditor::make('assignment')
                            ->label('')
                            ->placeholder('Enter the assignment or homework…')
                            ->toolbarButtons(['bold', 'italic', 'underline', 'bulletList', 'orderedList', 'undo', 'redo'])
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function savePlan(bool $notify = true): void
    {
        $plan = $this->record->getPairedLessonPlan();
        if (!$plan) {
            return;
        }

        $data = $this->planForm->getState();

        if (isset($data['learning_objectives'])) {
            $data['learning_objectives'] = collect($data['learning_objectives'])
                ->pluck('objective')
                ->filter(fn ($o) => filled($o))
                ->values()
                ->toArray();
        }

        if (isset($data['presentation_steps'])) {
            $data['presentation_steps'] = collect($data['presentation_steps'])
                ->pluck('step')
                ->filter(fn ($s) => filled($s))
                ->values()
                ->toArray();
        }

        if (isset($data['evaluation_questions'])) {
            $data['evaluation_questions'] = collect($data['evaluation_questions'])
                ->pluck('question')
                ->filter(fn ($q) => filled($q))
                ->values()
                ->toArray();
        }

        if (isset($data['key_vocabulary']) && is_array($data['key_vocabulary'])) {
            $data['key_vocabulary'] = implode(', ', array_filter($data['key_vocabulary']));
        }

        if (isset($data['topic'])) {
            $data['topic'] = LessonPlan::formatTopic($data['topic']);
            $data['title'] = $data['topic'];

            // Keep lesson note version title aligned
            if ($this->record->latestVersion) {
                $this->record->latestVersion->update(['title' => $data['topic']]);
            }
        }

        if (isset($data['sub_topic'])) {
            $data['sub_topic'] = LessonPlan::formatSubTopic($data['sub_topic']);
        }

        $planAttributes = collect($data)->except([
            'reference_materials',
            'instructional_materials',
            'teaching_methods',
        ])->toArray();

        $plan->update($planAttributes);

        if (isset($data['reference_materials'])) {
            $plan->referenceMaterials()->sync($data['reference_materials']);
        }
        if (isset($data['instructional_materials'])) {
            $plan->instructionalMaterials()->sync($data['instructional_materials']);
        }
        if (isset($data['teaching_methods'])) {
            $plan->teachingMethods()->sync($data['teaching_methods']);
        }

        if ($notify) {
            Notification::make()
                ->title('Lesson Plan Saved')
                ->body('Changes to the lesson plan have been saved successfully.')
                ->success()
                ->send();
        }
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->record->load(['latestVersion', 'teacher', 'subject', 'classroom', 'session', 'term']);

        if ($this->record->latestVersion) {
            $data['latestVersion'] = [
                'admin_comment' => $this->record->latestVersion->admin_comment,
            ];
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('save_plan')
                ->label('Save Lesson Plan')
                ->icon('heroicon-o-check')
                ->color('primary')
                ->action(function () {
                    $this->savePlan(notify: true);
                })
                ->visible(fn () => $this->record->getPairedLessonPlan() !== null),

            Actions\Action::make('download_note_pdf')
                ->label('Download Note PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->url(fn () => route('admin.lesson-note.pdf', $this->record))
                ->openUrlInNewTab(),

            Actions\Action::make('download_plan_pdf')
                ->label('Download Plan PDF')
                ->icon('heroicon-o-clipboard-document-list')
                ->color('gray')
                ->url(fn () => $this->record->getPairedLessonPlan() ? route('admin.lesson-plan.pdf', $this->record->getPairedLessonPlan()) : '#')
                ->openUrlInNewTab()
                ->visible(fn () => $this->record->getPairedLessonPlan() !== null),

            Actions\Action::make('download')
                ->label('Download File')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(fn () => $this->record->latestVersion?->getDownloadUrl() ? ($this->record->latestVersion->getDownloadUrl() . '?download=1') : '#')
                ->openUrlInNewTab()
                ->color('gray')
                ->visible(fn () => $this->record->latestVersion !== null
                    && $this->record->latestVersion->isFile()
                    && !empty($this->record->latestVersion->file_path)),

            Actions\Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->form([
                    Textarea::make('comment')
                        ->label('Comment (Optional)')
                        ->rows(3)
                        ->placeholder('Great work! Approved.')
                        ->helperText('This comment will be visible to the teacher'),
                ])
                ->action(function (array $data) {
                    if ($this->record->getPairedLessonPlan()) {
                        $this->savePlan(notify: false);
                    }

                    // approve() on LessonNote cascades to paired LessonPlan in DB transaction
                    $this->record->approve($data['comment'] ?? null, auth()->id());

                    $this->record->teacher->notify(new LessonNoteReviewed(
                        $this->record->load(['subject', 'classroom']),
                        'approved',
                        $data['comment'] ?? null
                    ));

                    LogLessonNoteAction::dispatch(
                        $this->record->id,
                        'approve',
                        auth()->id(),
                        'Approved by ' . auth()->user()->full_name
                    );

                    Notification::make()
                        ->title('Submission Approved')
                        ->body('The teacher has been notified. The lesson submission is now approved with your changes saved.')
                        ->success()
                        ->send();

                    $this->redirect($this->getResource()::getUrl('index'));
                })
                ->visible(fn () => $this->record->status === 'pending'
                    && $this->record->getPairedLessonPlan()?->status === 'pending'),

            Actions\Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->form([
                    Textarea::make('comment')
                        ->label('Reason for Rejection')
                        ->required()
                        ->rows(3)
                        ->placeholder('Please revise and resubmit...')
                        ->helperText('This comment will be visible to the teacher'),
                ])
                ->action(function (array $data) {
                    // reject() on LessonNote cascades to LessonPlan
                    $this->record->reject($data['comment'], auth()->id());

                    $this->record->teacher->notify(new LessonNoteReviewed(
                        $this->record->load(['subject', 'classroom']),
                        'rejected',
                        $data['comment']
                    ));

                    LogLessonNoteAction::dispatch(
                        $this->record->id,
                        'reject',
                        auth()->id(),
                        'Rejected by ' . auth()->user()->full_name
                    );

                    Notification::make()
                        ->title('Submission Rejected')
                        ->body('The teacher has been notified.')
                        ->warning()
                        ->send();

                    $this->redirect($this->getResource()::getUrl('index'));
                })
                ->visible(fn () => $this->record->status === 'pending'
                    && $this->record->getPairedLessonPlan()?->status === 'pending'),

            Actions\ActionGroup::make([
                Actions\Action::make('delete_note')
                    ->label('Delete Lesson Note')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Delete Lesson Note?')
                    ->modalDescription('Are you sure you want to delete this Lesson Note? The teacher will be able to start again and create a new lesson note for this week. This action cannot be undone.')
                    ->modalSubmitActionLabel('Yes, delete lesson note')
                    ->action(function () {
                        $record = $this->record;
                        \Illuminate\Support\Facades\DB::transaction(function () use ($record) {
                            $pairedPlan = $record->getPairedLessonPlan();
                            if ($pairedPlan && in_array($pairedPlan->status, ['pending', 'approved', 'rejected'])) {
                                $pairedPlan->update(['status' => 'draft']);
                            }

                            foreach ($record->versions as $version) {
                                if ($version->file_path) {
                                    try {
                                        \Illuminate\Support\Facades\Storage::disk('lesson_notes')->delete($version->file_path);
                                    } catch (\Throwable $e) {}
                                }
                            }
                            $record->versions()->forceDelete();
                            $record->forceDelete();
                        });

                        Notification::make()
                            ->title('Lesson Note Deleted')
                            ->body('The lesson note has been permanently deleted. The teacher can now create a new lesson note for this week.')
                            ->success()
                            ->send();

                        $this->redirect($this->getResource()::getUrl('index'));
                    })
                    ->visible(fn () => in_array(auth()->user()?->role, ['admin', 'sudo'], true)),

                Actions\Action::make('delete_plan')
                    ->label('Delete Lesson Plan')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Delete Paired Lesson Plan?')
                    ->modalDescription('Are you sure you want to delete the paired Lesson Plan? The teacher will be able to start again and create a new lesson plan for this week. This action cannot be undone.')
                    ->modalSubmitActionLabel('Yes, delete lesson plan')
                    ->visible(fn () => $this->record->getPairedLessonPlan() !== null
                        && in_array(auth()->user()?->role, ['admin', 'sudo'], true))
                    ->action(function () {
                        $record = $this->record;
                        $plan = $record->getPairedLessonPlan();
                        if ($plan) {
                            \Illuminate\Support\Facades\DB::transaction(function () use ($plan, $record) {
                                $plan->referenceMaterials()->detach();
                                $plan->instructionalMaterials()->detach();
                                $plan->teachingMethods()->detach();
                                $plan->forceDelete();

                                $record->update(['status' => 'draft']);
                                if ($record->latestVersion) {
                                    $record->latestVersion->update(['status' => 'draft']);
                                }
                            });

                            Notification::make()
                                ->title('Lesson Plan Deleted')
                                ->body('The paired lesson plan has been deleted. The teacher can now create a new lesson plan for this week.')
                                ->success()
                                ->send();

                            $this->redirect($this->getResource()::getUrl('index'));
                        }
                    }),

                Actions\Action::make('delete_both')
                    ->label('Delete Both (Note & Plan)')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Delete Both Lesson Note and Lesson Plan?')
                    ->modalDescription('Are you sure you want to delete both this Lesson Note and its paired Lesson Plan? The teacher will be able to start both again from scratch for this week. This action cannot be undone.')
                    ->modalSubmitActionLabel('Yes, delete both')
                    ->visible(fn () => $this->record->getPairedLessonPlan() !== null
                        && in_array(auth()->user()?->role, ['admin', 'sudo'], true))
                    ->action(function () {
                        $record = $this->record;
                        \Illuminate\Support\Facades\DB::transaction(function () use ($record) {
                            $plan = $record->getPairedLessonPlan();
                            if ($plan) {
                                $plan->referenceMaterials()->detach();
                                $plan->instructionalMaterials()->detach();
                                $plan->teachingMethods()->detach();
                                $plan->forceDelete();
                            }

                            foreach ($record->versions as $version) {
                                if ($version->file_path) {
                                    try {
                                        \Illuminate\Support\Facades\Storage::disk('lesson_notes')->delete($version->file_path);
                                    } catch (\Throwable $e) {}
                                }
                            }
                            $record->versions()->forceDelete();
                            $record->forceDelete();
                        });

                        Notification::make()
                            ->title('Lesson Note and Plan Deleted')
                            ->body('Both documents have been permanently deleted. The teacher can now start both from scratch.')
                            ->success()
                            ->send();

                        $this->redirect($this->getResource()::getUrl('index'));
                    }),
            ])
                ->label('Delete Options')
                ->icon('heroicon-o-trash')
                ->color('danger'),
        ];
    }
}
