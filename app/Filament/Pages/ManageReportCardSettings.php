<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Services\ReportCardSettingsService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class ManageReportCardSettings extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-adjustments-vertical';

    protected static string | \UnitEnum | null $navigationGroup = 'Results';

    protected static ?string $title = 'Report Card Settings';

    protected static ?string $navigationLabel = 'Report Card Settings';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.manage-report-card-settings';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user && ($user->isSudo() || $user->canAccessAdminPage('report_card_settings'));
    }

    public function mount(): void
    {
        $this->form->fill(ReportCardSettingsService::loadSettings());
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('enable_all')
                ->label('Enable All Elements')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Enable All Report Card Elements')
                ->modalDescription('Turn on all modular report card components across all sections.')
                ->modalSubmitActionLabel('Enable All')
                ->action(function (): void {
                    $defaults = ReportCardSettingsService::defaults();
                    foreach ($defaults as $k => $v) {
                        if (is_bool($v)) {
                            $defaults[$k] = true;
                        }
                    }
                    $this->form->fill($defaults);
                    Notification::make()
                        ->title('All elements enabled in form')
                        ->body('Click "Save Settings" below to permanently apply these changes.')
                        ->info()
                        ->send();
                }),

            Action::make('reset_defaults')
                ->label('Reset to Defaults')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Reset Report Card Settings')
                ->modalDescription('Restore the recommended default configuration for all report card elements and safe 18mm margins.')
                ->modalSubmitActionLabel('Reset Defaults')
                ->action(function (): void {
                    $this->form->fill(ReportCardSettingsService::defaults());
                    Notification::make()
                        ->title('Settings reset to defaults')
                        ->body('Click "Save Settings" below to permanently apply these changes.')
                        ->info()
                        ->send();
                }),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('ReportCardConfiguration')
                    ->tabs([
                        // ── Tab 1: Header & School Identity ────────────────────────
                        Tab::make('Header & Identity')
                            ->icon('heroicon-o-building-library')
                            ->schema([
                                Section::make('School Header Lockup')
                                    ->description('Configure which branding, title, and contact elements display in the report card banner.')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            Toggle::make('rc_show_school_logo')
                                                ->label('School Crest / Logo')
                                                ->helperText('Displays official school crest badge in the top header.')
                                                ->default(true),

                                            Toggle::make('rc_show_school_name')
                                                ->label('Official School Name')
                                                ->helperText('Displays the school name in bold header typography.')
                                                ->default(true),

                                            Toggle::make('rc_show_school_address')
                                                ->label('Campus Physical Address')
                                                ->helperText('Displays street, city, and state below the school name.')
                                                ->default(true),

                                            Toggle::make('rc_show_school_contacts')
                                                ->label('School Contact Info (Phone / Email / Web)')
                                                ->helperText('Displays telephone, contact email, and website link.')
                                                ->default(true),

                                            Toggle::make('rc_show_school_motto')
                                                ->label('School Motto')
                                                ->helperText('Displays the guiding ethos or motto statement.')
                                                ->default(true),

                                            Toggle::make('rc_show_report_title')
                                                ->label('Report Title Badge')
                                                ->helperText('Displays the "Official Terminal Report Card" title badge.')
                                                ->default(true),
                                        ]),

                                        Toggle::make('rc_show_term_ribbon')
                                            ->label('Academic Term & Session Ribbon')
                                            ->helperText('Displays the horizontal banner declaring the academic session and term.')
                                            ->default(true),
                                    ]),
                            ]),

                        // ── Tab 2: Student Profile & Bio-Data ───────────────────────
                        Tab::make('Student Bio-Data')
                            ->icon('heroicon-o-user-circle')
                            ->schema([
                                Section::make('Student Identifiers & Class Info')
                                    ->description('Toggle student personal attributes, classroom assignments, and attendance statistics.')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            Toggle::make('rc_show_student_photo')
                                                ->label('Student Passport Photo')
                                                ->helperText('Displays student portrait image on the header right.')
                                                ->default(true),

                                            Toggle::make('rc_show_student_name')
                                                ->label('Student Full Name')
                                                ->helperText('Displays surname and first name in prominent upper-case text.')
                                                ->default(true),

                                            Toggle::make('rc_show_admission_number')
                                                ->label('Admission Number')
                                                ->helperText('Displays official student ID or admission code.')
                                                ->default(true),

                                            Toggle::make('rc_show_class')
                                                ->label('Classroom Level')
                                                ->helperText('Displays the classroom name (e.g. SS 1, JSS 2, Grade 3).')
                                                ->default(true),

                                            Toggle::make('rc_show_class_arm')
                                                ->label('Class Arm / Stream')
                                                ->helperText('Displays student arm section (e.g. Diamond, Gold, A).')
                                                ->default(true),

                                            Toggle::make('rc_show_department')
                                                ->label('Senior Department')
                                                ->helperText('Displays senior department track (e.g. Science, Arts).')
                                                ->default(true),

                                            Toggle::make('rc_show_class_size')
                                                ->label('Number in Class / Cohort')
                                                ->helperText('Displays total student count for this class level.')
                                                ->default(true),

                                            Toggle::make('rc_show_arm_dept_population')
                                                ->label('Number in Arm / Department')
                                                ->helperText('Displays student count in the specific arm or department.')
                                                ->default(true),

                                            Toggle::make('rc_show_gender')
                                                ->label('Gender')
                                                ->helperText('Displays student biological gender.')
                                                ->default(true),

                                            Toggle::make('rc_show_dob')
                                                ->label('Date of Birth')
                                                ->helperText('Displays student date of birth.')
                                                ->default(true),

                                            Toggle::make('rc_show_attendance')
                                                ->label('Attendance Record & Rate')
                                                ->helperText('Displays school days opened, present, absent, and attendance %.')
                                                ->default(true),
                                        ]),
                                    ]),
                            ]),

                        // ── Tab 3: Academic Assessment Table ────────────────────────
                        Tab::make('Subject Assessment Table')
                            ->icon('heroicon-o-table-cells')
                            ->schema([
                                Section::make('Subject Score Columns')
                                    ->description('Customize which columns and evaluation metrics are displayed in the subject grades table.')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            Toggle::make('rc_show_score_heads')
                                                ->label('Continuous Assessment Breakdown')
                                                ->helperText('Displays per-test columns (e.g. 1st CA, 2nd CA, Mid-Term, Exam).')
                                                ->default(true),

                                            Toggle::make('rc_show_cumulative_terms')
                                                ->label('Cumulative Terms (3rd Term)')
                                                ->helperText('Displays 1st Term, 2nd Term, and 3rd Raw columns during 3rd term reporting.')
                                                ->default(true),

                                            Toggle::make('rc_show_subject_total')
                                                ->label('Subject Total Score')
                                                ->helperText('Displays composite final term score for each subject.')
                                                ->default(true),

                                            Toggle::make('rc_show_students_examined')
                                                ->label('Students Examined Count')
                                                ->helperText('Displays number of students assessed in that subject.')
                                                ->default(true),

                                            Toggle::make('rc_show_subject_grade')
                                                ->label('Subject Grade (A, B, C, D, E, F)')
                                                ->helperText('Displays academic letter grade classification.')
                                                ->default(true),

                                            Toggle::make('rc_show_subject_position')
                                                ->label('Subject Position (Rank)')
                                                ->helperText('Displays student subject rank (e.g. 1st, 2nd, 3rd).')
                                                ->default(true),

                                            Toggle::make('rc_show_subject_teacher_remark')
                                                ->label('Subject Teacher Remark')
                                                ->helperText('Displays qualitative feedback from the subject tutor.')
                                                ->default(true),
                                        ]),
                                    ]),
                            ]),

                        // ── Tab 4: Terminal Performance Summary ─────────────────────
                        Tab::make('Performance Summary')
                            ->icon('heroicon-o-chart-bar')
                            ->schema([
                                Section::make('Aggregate Results & Ranking')
                                    ->description('Control aggregate terminal calculations, percentage average, and class standings.')
                                    ->schema([
                                        Toggle::make('rc_show_performance_summary')
                                            ->label('Enable Performance Summary Section')
                                            ->helperText('Master toggle for the summary box directly below the subject table.')
                                            ->default(true),

                                        Grid::make(2)->schema([
                                            Toggle::make('rc_show_total_obtained')
                                                ->label('Total Marks Obtained / Obtainable')
                                                ->helperText('Displays overall marks scored out of total obtainable.')
                                                ->default(true),

                                            Toggle::make('rc_show_term_percentage')
                                                ->label('Term Percentage Average')
                                                ->helperText('Displays computed overall average percentage (e.g. 91.4%).')
                                                ->default(true),

                                            Toggle::make('rc_show_overall_position')
                                                ->label('Overall Position in Class')
                                                ->helperText('Displays overall class cohort position (e.g. 2nd of 40).')
                                                ->default(true),

                                            Toggle::make('rc_show_arm_dept_position')
                                                ->label('Arm / Department Position')
                                                ->helperText('Displays position restricted to student arm or department.')
                                                ->default(true),

                                            Toggle::make('rc_show_final_grade')
                                                ->label('Final Academic Grade & Remark')
                                                ->helperText('Displays cumulative grade band (e.g. A — Distinction).')
                                                ->default(true),
                                        ]),
                                    ]),
                            ]),

                        // ── Tab 5: Behavioral & Practical Domains ───────────────────
                        Tab::make('Domains & Skills')
                            ->icon('heroicon-o-sparkles')
                            ->schema([
                                Section::make('Character & Psychomotor Traits')
                                    ->description('Manage qualitative evaluation grids for character conduct and practical motor abilities.')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            Toggle::make('rc_show_affective_domain')
                                                ->label('Affective Domain (Character & Conduct)')
                                                ->helperText('Displays Punctuality, Neatness, Attentiveness, Honesty, Leadership ratings.')
                                                ->default(true),

                                            Toggle::make('rc_show_psychomotor_skills')
                                                ->label('Psychomotor Skills & Practical Activities')
                                                ->helperText('Displays Handwriting, Sports, Crafts, Verbal Fluency, Lab work ratings.')
                                                ->default(true),
                                        ]),

                                        Toggle::make('rc_show_domain_scale')
                                            ->label('Rating Scale Legend')
                                            ->helperText('Displays scale footnote: 5 - Excellent, 4 - Very Good, 3 - Good, 2 - Fair, 1 - Poor.')
                                            ->default(true),
                                    ]),
                            ]),

                        // ── Tab 6: Remarks & Reopening ──────────────────────────────
                        Tab::make('Remarks & Dates')
                            ->icon('heroicon-o-chat-bubble-bottom-center-text')
                            ->schema([
                                Section::make('Qualitative Comments & Calendar')
                                    ->description('Configure personal comments and official school calendar resumption dates.')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            Toggle::make('rc_show_class_teacher_remark')
                                                ->label('Class Teacher Remark Card')
                                                ->helperText('Displays the dedicated class teacher feedback card.')
                                                ->default(true),

                                            Toggle::make('rc_show_principal_remark')
                                                ->label('Principal / Head Teacher Remark Card')
                                                ->helperText('Displays the executive principal endorsement card.')
                                                ->default(true),
                                        ]),

                                        Toggle::make('rc_show_next_term_resumption')
                                            ->label('Next Term Resumption Date Banner')
                                            ->helperText('Displays the prominent reopening date notice for parents.')
                                            ->default(true),
                                    ]),
                            ]),

                        // ── Tab 7: Signatories, Stamp & Footer ───────────────────────
                        Tab::make('Signatories & Seal')
                            ->icon('heroicon-o-pencil-square')
                            ->schema([
                                Section::make('Validation Lines & Official Stamp')
                                    ->description('Manage verification sign-off lines, official school stamp, and security notes.')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            Toggle::make('rc_show_teacher_signature')
                                                ->label('Class Teacher Signature Line')
                                                ->helperText('Displays designated signature line for the class teacher.')
                                                ->default(true),

                                            Toggle::make('rc_show_principal_signature')
                                                ->label('Principal Signature & Date Line')
                                                ->helperText('Displays signature and date sign-off line for the school head.')
                                                ->default(true),

                                            Toggle::make('rc_show_school_stamp')
                                                ->label('Official School Seal / Stamp Box')
                                                ->helperText('Displays border box for the official school stamp.')
                                                ->default(true),

                                            Toggle::make('rc_show_footer_note')
                                                ->label('Computer-Generated Verification Footer')
                                                ->helperText('Displays bottom security note with generation date and time.')
                                                ->default(true),
                                        ]),
                                    ]),
                            ]),

                        // ── Tab 8: Paper Margins & Spacing ──────────────────────────
                        Tab::make('Paper Margins & Spacing')
                            ->icon('heroicon-o-arrows-pointing-out')
                            ->schema([
                                Section::make('Print Margins & Page Geometry')
                                    ->description('Configure generous padding around all edges of the paper so report cards never crowd page borders.')
                                    ->schema([
                                        Select::make('rc_page_margin_preset')
                                            ->label('Paper Margin Preset')
                                            ->options([
                                                'standard' => '📐 Standard Generous Margin (18mm — Recommended)',
                                                'generous' => '🌟 Extra Spacious Margin (22mm — Maximum Breathing Room)',
                                                'compact'  => '📄 Compact Margin (14mm — More Content per Page)',
                                                'custom'   => '⚙️ Custom Dimensions (Specify mm for each edge)',
                                            ])
                                            ->default('standard')
                                            ->live(),

                                        Grid::make(4)
                                            ->visible(fn ($get) => $get('rc_page_margin_preset') === 'custom')
                                            ->schema([
                                                TextInput::make('rc_page_margin_top')
                                                    ->label('Top Margin (mm)')
                                                    ->numeric()
                                                    ->minValue(5)
                                                    ->maxValue(40)
                                                    ->default(18),

                                                TextInput::make('rc_page_margin_bottom')
                                                    ->label('Bottom Margin (mm)')
                                                    ->numeric()
                                                    ->minValue(5)
                                                    ->maxValue(40)
                                                    ->default(18),

                                                TextInput::make('rc_page_margin_left')
                                                    ->label('Left Margin (mm)')
                                                    ->numeric()
                                                    ->minValue(5)
                                                    ->maxValue(40)
                                                    ->default(18),

                                                TextInput::make('rc_page_margin_right')
                                                    ->label('Right Margin (mm)')
                                                    ->numeric()
                                                    ->minValue(5)
                                                    ->maxValue(40)
                                                    ->default(18),
                                            ]),
                                    ]),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $state = $this->form->getState();
        $allKeys = ReportCardSettingsService::getAllKeys();

        foreach ($state as $key => $val) {
            if (in_array($key, $allKeys, true)) {
                $storageVal = is_bool($val) ? ($val ? '1' : '0') : (string) $val;
                Setting::updateOrCreate(['key' => $key], ['value' => $storageVal]);
            }
        }

        Notification::make()
            ->title('Report card settings saved successfully')
            ->body('All terminal report cards for this school will immediately apply these layout options.')
            ->success()
            ->send();
    }
}
