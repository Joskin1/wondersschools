<?php

namespace App\Filament\Pages;

use App\Models\FrontendContent;
use App\Models\Setting;
use App\Services\FrontendLibrary;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class Settings extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string | \UnitEnum | null $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'School Settings';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.settings';

    public ?array $data = [];

    /**
     * List of keys that belong to the settings table.
     */
    protected array $settingKeys = [
        'school_name',
        'school_short_name',
        'school_motto',
        'school_established',
        'school_phone',
        'school_email',
        'school_logo',
        'school_website',
        'school_address',
        'fee_schedule_link',
        'primary_color',
        'secondary_color',
        'accent_color',
        'layout_style',
        'student_portal_url',
        'staff_portal_url',
        'admin_portal_url',
        'footer_social_facebook',
        'footer_social_instagram',
        'footer_social_linkedin',
        'footer_social_x',
        'seo_title',
        'seo_description',
    ];

    /**
     * List of frontend content keys that are stored as JSON arrays.
     */
    protected array $jsonKeys = [
        'features_items',
        'stats_items',
        'stats_destinations',
        'academics_tracks',
        'facilities_items',
        'news_articles',
        'testimonials_items',
        'admissions_cta_steps',
        'contact_additional_phones',
        'contact_additional_emails',
        'contact_form_classes',
    ];

    protected function getHeaderActions(): array
    {
        return [
            Action::make('clear_content')
                ->label('Clear Content')
                ->color('danger')
                ->icon('heroicon-o-trash')
                ->requiresConfirmation()
                ->modalHeading('Clear Website Content')
                ->modalDescription('This will clear content fields so you can fill them from scratch. A snapshot of current content is saved first for undo capability.')
                ->modalSubmitActionLabel('Yes, Clear Content')
                ->form([
                    Toggle::make('keep_hero')
                        ->label('Keep hero section')
                        ->default(true)
                        ->helperText('Keep hero cover photo, headline, and buttons so the page maintains a complete visual presence.'),
                ])
                ->action(function (array $data): void {
                    $this->clearContent($data['keep_hero'] ?? true);
                }),

            Action::make('restore_previous')
                ->label('Restore Previous Content')
                ->color('warning')
                ->icon('heroicon-o-arrow-uturn-left')
                ->visible(fn (): bool => ! empty(Setting::where('key', 'content_snapshot')->value('value')))
                ->requiresConfirmation()
                ->modalHeading('Restore Previous Content Snapshot')
                ->modalDescription('This will overwrite current content with the exact snapshot saved immediately prior to your last reset or restore action.')
                ->modalSubmitActionLabel('Yes, Restore Previous')
                ->action(function (): void {
                    $this->restorePreviousContent();
                }),

            Action::make('restore_defaults')
                ->label('Restore Default Content')
                ->color('gray')
                ->icon('heroicon-o-arrow-path')
                ->requiresConfirmation()
                ->modalHeading('Restore Standard Prospectus Defaults')
                ->modalDescription('This will restore standard prospectus defaults for this school. Your current content will be snapshotted first.')
                ->modalSubmitActionLabel('Yes, Restore Defaults')
                ->action(function (): void {
                    $this->restoreDefaultContent();
                }),
        ];
    }

    public function mount(): void
    {
        $settings = Setting::all()->pluck('value', 'key')->toArray();
        $contents = FrontendContent::all()->pluck('value', 'key')->toArray();

        // Merge settings and frontend contents
        $data = array_merge($settings, $contents);

        // Decode JSON repeater & tags fields for Filament components
        foreach ($this->jsonKeys as $jsonKey) {
            if (isset($data[$jsonKey]) && is_string($data[$jsonKey])) {
                $decoded = json_decode($data[$jsonKey], true);
                if (is_array($decoded)) {
                    $data[$jsonKey] = $decoded;
                }
            }
        }

        // Normalize single file upload fields if stored as JSON array string or array
        $singleFileKeys = ['school_logo', 'hero_image', 'about_image'];
        foreach ($singleFileKeys as $fileKey) {
            if (isset($data[$fileKey])) {
                $val = $data[$fileKey];
                if (is_string($val) && (str_starts_with($val, '[') || str_starts_with($val, '{'))) {
                    $decoded = json_decode($val, true);
                    if (is_array($decoded)) {
                        $first = reset($decoded);
                        $data[$fileKey] = is_string($first) ? $first : null;
                    }
                } elseif (is_array($val)) {
                    $first = reset($val);
                    $data[$fileKey] = is_string($first) ? $first : null;
                }
            }
        }

        // Apply tenant primary color if tenant is initialized
        if (tenant() && tenant()->primary_color) {
            $data['primary_color'] = tenant()->primary_color;
        }

        $this->form->fill($data);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Settings')
                    ->tabs([
                        // ── Tab 1: School Identity ──────────────────────────────────────────────
                        Tab::make('School Identity')
                            ->icon('heroicon-o-building-library')
                            ->schema([
                                Section::make('Institutional Identity & Crest')
                                    ->description('Official school information, brand lockups, crest, and primary registry contact details shown across the website header and footer.')
                                    ->headerActions([
                                        $this->makeSaveAction('School Identity', [
                                            'school_name', 'school_short_name', 'school_motto', 'school_established',
                                            'school_phone', 'school_email', 'school_website', 'fee_schedule_link',
                                            'school_address', 'school_logo',
                                        ]),
                                    ])
                                    ->schema([
                                        Grid::make(2)->schema([
                                            TextInput::make('school_name')
                                                ->label('Official School Name')
                                                ->required()
                                                ->maxLength(120)
                                                ->placeholder('e.g. Apex Crown College')
                                                ->helperText('Full legal/brand name of the school.'),

                                            TextInput::make('school_short_name')
                                                ->label('Short Name / Monogram')
                                                ->maxLength(10)
                                                ->placeholder('e.g. AC')
                                                ->helperText('Abbreviation or monogram for compact badge views.'),

                                            TextInput::make('school_motto')
                                                ->label('School Motto')
                                                ->maxLength(150)
                                                ->placeholder('e.g. Excellence, Character & Leadership')
                                                ->helperText('Official school motto or guiding ethos.'),

                                            TextInput::make('school_established')
                                                ->label('Established Year')
                                                ->maxLength(4)
                                                ->placeholder('e.g. 2001')
                                                ->helperText('Founding year displayed on crest badges (e.g. 2001).'),

                                            TextInput::make('school_phone')
                                                ->label('Primary Registry Phone')
                                                ->tel()
                                                ->required()
                                                ->maxLength(30)
                                                ->placeholder('e.g. +234 800 123 4567')
                                                ->helperText('Primary telephone for prospective parents and registry.'),

                                            TextInput::make('school_email')
                                                ->label('Primary Admissions Email')
                                                ->email()
                                                ->required()
                                                ->maxLength(80)
                                                ->placeholder('e.g. admissions@apexcrown.edu.ng')
                                                ->helperText('Main admissions contact email for website inquiries.'),

                                            TextInput::make('school_website')
                                                ->label('School Website URL')
                                                ->url()
                                                ->maxLength(100)
                                                ->placeholder('https://apexcrown.edu.ng')
                                                ->helperText('Canonical web address.'),

                                            TextInput::make('fee_schedule_link')
                                                ->label('Fee Schedule Link / Document URL')
                                                ->url()
                                                ->maxLength(255)
                                                ->placeholder('https://apexcrown.edu.ng/fees.pdf')
                                                ->helperText('External link or PDF download for tuition schedule.'),
                                        ]),

                                        Textarea::make('school_address')
                                            ->label('Campus Physical Address')
                                            ->required()
                                            ->maxLength(250)
                                            ->rows(3)
                                            ->placeholder('e.g. Plot 14 - 18, Apex Boulevard, Lekki Phase 1, Lagos State, Nigeria')
                                            ->helperText('Full campus street address displayed in the footer and contact sections.'),

                                        FileUpload::make('school_logo')
                                            ->label('School Crest / Logo')
                                            ->image()
                                            ->imageEditor()
                                            ->directory('logos')
                                            ->disk(config('filesystems.upload_disk', 'public'))
                                            ->maxSize(2048)
                                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'])
                                            ->helperText('Official crest/logo image (max 2 MB). Displayed in the navbar header, mobile drawer, and footer colophon.'),
                                    ]),
                            ]),

                        // ── Tab 2: Branding ─────────────────────────────────────────────────────
                        Tab::make('Branding')
                            ->icon('heroicon-o-paint-brush')
                            ->schema([
                                View::make('filament.components.branding-preview'),

                                Section::make('Brand Color Tokens')
                                    ->description('Only three colours are configured. Contrast and hover states derive automatically.')
                                    ->headerActions([
                                        $this->makeSaveAction('Branding', [
                                            'primary_color', 'secondary_color', 'accent_color', 'layout_style',
                                        ]),
                                    ])
                                    ->schema([
                                        Grid::make(3)->schema([
                                            ColorPicker::make('primary_color')
                                                ->label('Primary Color (Ink)')
                                                ->required()
                                                ->helperText('Authoritative dark ink for headings, dark bands, and footer.'),

                                            ColorPicker::make('secondary_color')
                                                ->label('Secondary Color (Support)')
                                                ->required()
                                                ->helperText('Muted slate tone for subtitles and subtle UI accents.'),

                                            ColorPicker::make('accent_color')
                                                ->label('Accent Color (Gold Role)')
                                                ->required()
                                                ->helperText('Warm gold for eyebrows, CTA buttons, and stat numerals.'),
                                        ]),

                                        Select::make('layout_style')
                                            ->label('Layout Style')
                                            ->options([
                                                'standard' => 'Ivy League Prospectus (Standard)',
                                                'centered' => 'Centered Classic',
                                                'compact'  => 'Compact Modern',
                                            ])
                                            ->default('standard')
                                            ->helperText('Controls the overall page layout style.'),
                                    ]),
                            ]),

                        // ── Tab 3: Navigation & Portals ─────────────────────────────────────────
                        Tab::make('Navigation & Portals')
                            ->icon('heroicon-o-bars-3')
                            ->schema([
                                Section::make('Top Announcement Bar')
                                    ->headerActions([
                                        $this->makeSaveAction('Navigation & Portals', [
                                            'topbar_badge', 'topbar_notice', 'topbar_link_text', 'topbar_link_url',
                                            'nav_about_label', 'nav_features_label', 'nav_academics_label',
                                            'nav_stats_label', 'nav_facilities_label', 'nav_news_label',
                                            'nav_contact_label', 'nav_portals_label', 'portal_student_label',
                                            'portal_staff_label', 'portal_admin_label', 'portal_student_label_short',
                                            'portal_staff_label_short', 'portal_admin_label_short',
                                            'student_portal_url', 'staff_portal_url', 'admin_portal_url',
                                            'header_cta_text', 'header_cta_link',
                                        ]),
                                    ])
                                    ->schema([
                                        Grid::make(2)->schema([
                                            TextInput::make('topbar_badge')
                                                ->label('Announcement Eyebrow / Tag')
                                                ->maxLength(40)
                                                ->placeholder('e.g. ADMISSIONS 2026/2027')
                                                ->helperText('Short label before the announcement. Leave blank to hide.'),

                                            TextInput::make('topbar_notice')
                                                ->label('Announcement Notice Text')
                                                ->maxLength(120)
                                                ->placeholder('e.g. Entrance examination and transfer enrollment now open.')
                                                ->helperText('Main announcement bar text. Leave blank to hide topbar.'),
                                        ]),

                                        Grid::make(2)->schema([
                                            TextInput::make('topbar_link_text')
                                                ->label('Call-to-Action Link Text')
                                                ->maxLength(40)
                                                ->placeholder('e.g. Learn More & Apply')
                                                ->helperText('Link label at the end of the announcement bar.'),

                                            TextInput::make('topbar_link_url')
                                                ->label('Call-to-Action Target URL')
                                                ->maxLength(100)
                                                ->placeholder('e.g. #admissions')
                                                ->helperText('Target URL or page anchor (e.g. #admissions).'),
                                        ]),
                                    ]),

                                Section::make('Main Navigation Menu Labels')
                                    ->description('Desktop navbar and mobile drawer section links.')
                                    ->schema([
                                        Grid::make(4)->schema([
                                            TextInput::make('nav_about_label')->label('About Link Label')->maxLength(40)->placeholder('e.g. About'),
                                            TextInput::make('nav_features_label')->label('Distinctives Link Label')->maxLength(40)->placeholder('e.g. Distinctives'),
                                            TextInput::make('nav_academics_label')->label('Academics Link Label')->maxLength(40)->placeholder('e.g. Curriculum'),
                                            TextInput::make('nav_stats_label')->label('Outcomes Link Label')->maxLength(40)->placeholder('e.g. Outcomes'),
                                        ]),

                                        Grid::make(4)->schema([
                                            TextInput::make('nav_facilities_label')->label('Facilities Link Label')->maxLength(40)->placeholder('e.g. Campus'),
                                            TextInput::make('nav_news_label')->label('News Link Label')->maxLength(40)->placeholder('e.g. Bulletin'),
                                            TextInput::make('nav_contact_label')->label('Contact Link Label')->maxLength(40)->placeholder('e.g. Contact'),
                                            TextInput::make('nav_cta_label')->label('Navbar CTA Button Text')->maxLength(40)->placeholder('e.g. Admissions'),
                                        ]),
                                    ]),

                                Section::make('Portals Configuration')
                                    ->description('Configure desktop dropdown links and mobile drawer 3-column button grid.')
                                    ->schema([
                                        Grid::make(4)->schema([
                                            TextInput::make('nav_portals_label')->label('Portals Dropdown Label')->maxLength(40)->placeholder('e.g. Portals'),
                                            TextInput::make('portal_student_label')->label('Student Portal Label')->maxLength(40)->placeholder('e.g. Student Portal'),
                                            TextInput::make('portal_student_sublabel')->label('Student Portal Subtitle')->maxLength(60)->placeholder('e.g. Results, schedules & LMS'),
                                            TextInput::make('portal_student_mob_label')->label('Student Mobile Label')->maxLength(20)->placeholder('e.g. Student'),
                                        ]),

                                        Grid::make(4)->schema([
                                            TextInput::make('portal_faculty_label')->label('Faculty Portal Label')->maxLength(40)->placeholder('e.g. Faculty Portal'),
                                            TextInput::make('portal_faculty_sublabel')->label('Faculty Portal Subtitle')->maxLength(60)->placeholder('e.g. Gradebook & registers'),
                                            TextInput::make('portal_faculty_mob_label')->label('Faculty Mobile Label')->maxLength(20)->placeholder('e.g. Faculty'),
                                            TextInput::make('portal_admin_label')->label('Admin Portal Label')->maxLength(40)->placeholder('e.g. Admin Portal'),
                                        ]),

                                        Grid::make(4)->schema([
                                            TextInput::make('portal_admin_sublabel')->label('Admin Portal Subtitle')->maxLength(60)->placeholder('e.g. Institutional console'),
                                            TextInput::make('portal_admin_mob_label')->label('Admin Mobile Label')->maxLength(20)->placeholder('e.g. Admin'),
                                            TextInput::make('portal_student_url')->label('Student Portal URL')->maxLength(100)->placeholder('e.g. /student/login'),
                                            TextInput::make('portal_faculty_url')->label('Faculty Portal URL')->maxLength(100)->placeholder('e.g. /teacher/login'),
                                        ]),

                                        TextInput::make('portal_admin_url')->label('Admin Portal URL')->maxLength(100)->placeholder('e.g. /admin/login'),
                                    ]),

                                Section::make('Mobile Navigation Drawer')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            TextInput::make('mobile_drawer_badge')->label('Drawer Eyebrow Badge')->maxLength(50)->placeholder('e.g. Admissions Open'),
                                            TextInput::make('mobile_drawer_portals_heading')->label('Drawer Portals Header')->maxLength(40)->placeholder('e.g. Institutional Portals'),
                                        ]),

                                        Grid::make(2)->schema([
                                            TextInput::make('mobile_drawer_inquire_btn')->label('Drawer Inquire Button Text')->maxLength(40)->placeholder('e.g. Inquire'),
                                            TextInput::make('mobile_drawer_apply_btn')->label('Drawer Apply Button Text')->maxLength(40)->placeholder('e.g. Apply Now'),
                                        ]),
                                    ]),
                            ]),

                        // ── Tab 4: Hero Section ────────────────────────────────────────────────
                        Tab::make('Hero Cover')
                            ->icon('heroicon-o-sparkles')
                            ->schema([
                                Section::make('Hero Cover Section')
                                    ->description('Full-bleed photograph, primary display heading, subtitle, and action buttons.')
                                    ->headerActions([
                                        $this->makeSaveAction('Hero Section', [
                                            'hero_badge', 'hero_scroll_label', 'hero_title', 'hero_subtitle',
                                            'hero_image', 'hero_image_alt', 'hero_primary_cta_text',
                                            'hero_primary_cta_link', 'hero_secondary_cta_text', 'hero_secondary_cta_link',
                                        ]),
                                    ])
                                    ->schema([
                                        Grid::make(2)->schema([
                                            TextInput::make('hero_badge')
                                                ->label('Hero Academic Badge')
                                                ->maxLength(60)
                                                ->placeholder('e.g. 2026 / 2027 Academic Session')
                                                ->helperText('Top eyebrow tag (e.g., 2026 / 2027 Academic Session).'),

                                            TextInput::make('hero_scroll_label')
                                                ->label('Scroll Prompt Label')
                                                ->maxLength(60)
                                                ->placeholder('e.g. Scroll to explore prospectus')
                                                ->helperText('Small caption at bottom of hero section.'),
                                        ]),

                                        TextInput::make('hero_title')
                                            ->label('Main Prospectus Heading')
                                            ->maxLength(120)
                                            ->placeholder('e.g. Nurturing Intellectual Depth & Moral Leadership')
                                            ->helperText('Primary hero display heading rendered in serif.'),

                                        Textarea::make('hero_subtitle')
                                            ->label('Lead Narrative Sentence')
                                            ->maxLength(250)
                                            ->rows(3)
                                            ->placeholder('e.g. An accredited British-Nigerian secondary institution committed to scholastic rigor, scientific inquiry, and the formation of character.')
                                            ->helperText('Single sentence lead text constrained to 62ch max width for readability.'),

                                        Grid::make(2)->schema([
                                            FileUpload::make('hero_image')
                                                ->label('Hero Background Cover Image')
                                                ->image()
                                                ->imageEditor()
                                                ->directory('frontend')
                                                ->disk(config('filesystems.upload_disk', 'public'))
                                                ->maxSize(4096)
                                                ->helperText('Full-bleed cover photo with ink gradient overlay (max 4 MB).'),

                                            TextInput::make('hero_image_alt')
                                                ->label('Hero Image Alt Text')
                                                ->maxLength(100)
                                                ->placeholder('e.g. College Scholars in Lekki')
                                                ->helperText('Accessibility description for search engines and assistive tech.'),

                                            TextInput::make('hero_primary_cta_text')->label('Primary CTA Text')->maxLength(40)->placeholder('e.g. Apply for Admission'),
                                            TextInput::make('hero_primary_cta_link')->label('Primary CTA Link')->maxLength(100)->placeholder('e.g. #admissions'),
                                            TextInput::make('hero_secondary_cta_text')->label('Secondary CTA Text')->maxLength(40)->placeholder('e.g. Explore Prospectus'),
                                            TextInput::make('hero_secondary_cta_link')->label('Secondary CTA Link')->maxLength(100)->placeholder('e.g. #about'),
                                        ]),
                                    ]),
                            ]),

                        // ── Tab 5: 01 — About the College ───────────────────────────────────────
                        Tab::make('01 — About')
                            ->icon('heroicon-o-identification')
                            ->schema([
                                Section::make('01 — About the College')
                                    ->description('Institutional story, founding vision, legacy badge, and principal portrait.')
                                    ->headerActions([
                                        $this->makeSaveAction('About Section', [
                                            'about_eyebrow', 'about_heading', 'about_image', 'about_image_alt',
                                            'about_years_badge', 'about_years_label', 'about_body',
                                            'about_principal_name', 'about_principal_title',
                                        ]),
                                    ])
                                    ->schema([
                                        Grid::make(2)->schema([
                                            TextInput::make('about_eyebrow')
                                                ->label('Section Eyebrow Label')
                                                ->maxLength(50)
                                                ->placeholder('e.g. ABOUT THE COLLEGE')
                                                ->helperText('Eyebrow text beside the section number.'),

                                            TextInput::make('about_heading')
                                                ->label('About Main Heading')
                                                ->maxLength(120)
                                                ->placeholder('e.g. A Tradition of Uncompromising Academic Standard'),

                                            FileUpload::make('about_image')
                                                ->label('Portrait Photograph')
                                                ->image()
                                                ->imageEditor()
                                                ->directory('frontend')
                                                ->disk(config('filesystems.upload_disk', 'public'))
                                                ->maxSize(4096)
                                                ->helperText('Vertical portrait photograph (max 4 MB).'),

                                            TextInput::make('about_image_alt')
                                                ->label('Portrait Alt Text')
                                                ->maxLength(100)
                                                ->placeholder('e.g. Dr. Mrs. Adebisi Balogun Head of School')
                                                ->helperText('Accessibility description for the portrait.'),
                                        ]),

                                        Grid::make(2)->schema([
                                            TextInput::make('about_years_badge')
                                                ->label('Legacy Number Badge')
                                                ->maxLength(10)
                                                ->placeholder('e.g. 25')
                                                ->helperText('Numerical callout (e.g. 25).'),

                                            TextInput::make('about_years_label')
                                                ->label('Legacy Subtitle')
                                                ->maxLength(60)
                                                ->placeholder('e.g. Years of Academic Legacy in Lagos')
                                                ->helperText('Badge caption below the years number.'),
                                        ]),

                                        RichEditor::make('about_body')
                                            ->label('About Narrative Body')
                                            ->placeholder('Write your school story, founding vision, and academic philosophy...')
                                            ->toolbarButtons([
                                                'bold', 'italic', 'underline', 'strike',
                                                'link', 'orderedList', 'bulletList',
                                                'h2', 'h3', 'undo', 'redo',
                                            ])
                                            ->helperText('Full narrative rendered in serif typography.'),

                                        Grid::make(2)->schema([
                                            TextInput::make('about_principal_name')
                                                ->label('Principal / Head of School Name')
                                                ->maxLength(80)
                                                ->placeholder('e.g. Dr. (Mrs.) Adebisi Balogun'),

                                            TextInput::make('about_principal_title')
                                                ->label('Principal Qualifications & Title')
                                                ->maxLength(120)
                                                ->placeholder('e.g. B.Sc, M.Ed, Ph.D. — Principal & Head of School'),
                                        ]),
                                    ]),
                            ]),

                        // ── Tab 6: 02 — Distinctives ────────────────────────────────────────────
                        Tab::make('02 — Distinctives')
                            ->icon('heroicon-o-academic-cap')
                            ->schema([
                                Section::make('02 — Distinctives & Pillars')
                                    ->description('Core educational differentiators and curriculum pillars.')
                                    ->headerActions([
                                        $this->makeSaveAction('Distinctives', [
                                            'features_eyebrow', 'features_heading', 'features_intro',
                                            'features_cta_text', 'features_cta_link', 'features_items',
                                        ]),
                                    ])
                                    ->schema([
                                        Grid::make(2)->schema([
                                            TextInput::make('features_eyebrow')
                                                ->label('Section Eyebrow')
                                                ->maxLength(50)
                                                ->placeholder('e.g. DISTINCTIVES'),

                                            TextInput::make('features_heading')
                                                ->label('Distinctives Main Heading')
                                                ->maxLength(120)
                                                ->placeholder('e.g. The Pillars of an Apex Crown Education'),
                                        ]),

                                        Textarea::make('features_intro')
                                            ->label('Section Intro Summary')
                                            ->maxLength(250)
                                            ->rows(2)
                                            ->placeholder('e.g. A deliberate blend of academic depth, moral discipline, and technological literacy structured to cultivate leaders.'),

                                        Grid::make(2)->schema([
                                            TextInput::make('features_cta_text')->label('Link Label')->maxLength(40)->placeholder('e.g. Review Full Curriculum'),
                                            TextInput::make('features_cta_link')->label('Link Target')->maxLength(100)->placeholder('e.g. #academics'),
                                        ]),

                                        Repeater::make('features_items')
                                            ->label('Distinctive Pillars (Editorial Table of Contents)')
                                            ->maxItems(8)
                                            ->reorderable()
                                            ->collapsible()
                                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? 'Pillar')
                                            ->schema([
                                                TextInput::make('title')
                                                    ->label('Pillar Title')
                                                    ->required()
                                                    ->maxLength(100)
                                                    ->placeholder('e.g. Integrated Dual Curriculum'),

                                                Textarea::make('desc')
                                                    ->label('Pillar Narrative Description')
                                                    ->required()
                                                    ->maxLength(300)
                                                    ->rows(2)
                                                    ->placeholder('e.g. Simultaneous mastery of the Nigerian National Curriculum alongside British Cambridge Checkpoint and IGCSE.'),
                                            ]),
                                    ]),
                            ]),

                        // ── Tab 7: 03 — Outcomes & Stats ────────────────────────────────────────
                        Tab::make('03 — Outcomes & Stats')
                            ->icon('heroicon-o-chart-bar')
                            ->schema([
                                Section::make('03 — Examination Outcomes & Stats')
                                    ->description('Academic track record, examination pass rates, and representative alumni destinations.')
                                    ->headerActions([
                                        $this->makeSaveAction('Outcomes & Stats', [
                                            'stats_eyebrow', 'stats_heading', 'stats_items',
                                            'stats_destinations_label', 'stats_destinations',
                                        ]),
                                    ])
                                    ->schema([
                                        Grid::make(2)->schema([
                                            TextInput::make('stats_eyebrow')
                                                ->label('Section Eyebrow')
                                                ->maxLength(50)
                                                ->placeholder('e.g. EXAMINATION OUTCOMES'),

                                            TextInput::make('stats_heading')
                                                ->label('Outcomes Main Heading')
                                                ->maxLength(120)
                                                ->placeholder('e.g. Ten-Year Record of Scholastic Excellence'),
                                        ]),

                                        Repeater::make('stats_items')
                                            ->label('Key Examination Statistics')
                                            ->maxItems(6)
                                            ->reorderable()
                                            ->collapsible()
                                            ->itemLabel(fn (array $state): ?string => ($state['value'] ?? '') . ' — ' . ($state['label'] ?? 'Stat'))
                                            ->schema([
                                                Grid::make(3)->schema([
                                                    TextInput::make('value')
                                                        ->label('Stat Value (e.g. 100%, 94.8%)')
                                                        ->required()
                                                        ->maxLength(20)
                                                        ->placeholder('e.g. 100%'),

                                                    TextInput::make('label')
                                                        ->label('Metric Label')
                                                        ->required()
                                                        ->maxLength(50)
                                                        ->placeholder('e.g. WAEC Pass Rate'),

                                                    TextInput::make('detail')
                                                        ->label('Context Note')
                                                        ->maxLength(120)
                                                        ->placeholder('e.g. 5+ credits including English & Maths (10-year record)'),
                                                ]),
                                            ]),

                                        Grid::make(2)->schema([
                                            TextInput::make('stats_destinations_label')
                                                ->label('Matriculations Subtitle')
                                                ->maxLength(50)
                                                ->placeholder('e.g. Representative Matriculations:'),

                                            TagsInput::make('stats_destinations')
                                                ->label('Representative Destination Universities')
                                                ->placeholder('Add university destination...')
                                                ->helperText('Type university name and press Enter (e.g. University of Ibadan, Imperial College London).'),
                                        ]),
                                    ]),
                            ]),

                        // ── Tab 8: 04 — Curriculum ──────────────────────────────────────────────
                        Tab::make('04 — Curriculum')
                            ->icon('heroicon-o-book-open')
                            ->schema([
                                Section::make('04 — Curriculum & Programmes')
                                    ->description('Structured academic divisions, certifications, and core subject disciplines.')
                                    ->headerActions([
                                        $this->makeSaveAction('Curriculum', [
                                            'academics_eyebrow', 'academics_heading', 'academics_intro', 'academics_tracks',
                                        ]),
                                    ])
                                    ->schema([
                                        Grid::make(2)->schema([
                                            TextInput::make('academics_eyebrow')
                                                ->label('Section Eyebrow')
                                                ->maxLength(50)
                                                ->placeholder('e.g. CURRICULUM & PROGRAMMES'),

                                            TextInput::make('academics_heading')
                                                ->label('Curriculum Main Heading')
                                                ->maxLength(120)
                                                ->placeholder('e.g. Structured Pathways for Secondary Scholars'),
                                        ]),

                                        Textarea::make('academics_intro')
                                            ->label('Curriculum Overview Paragraph')
                                            ->maxLength(300)
                                            ->rows(2)
                                            ->placeholder('e.g. A comprehensive curriculum designed to build foundational mastery in junior years and specialization in senior years.'),

                                        Repeater::make('academics_tracks')
                                            ->label('Academic Division Cards')
                                            ->maxItems(6)
                                            ->reorderable()
                                            ->collapsible()
                                            ->itemLabel(fn (array $state): ?string => ($state['code'] ?? '') . ' — ' . ($state['name'] ?? 'Track'))
                                            ->schema([
                                                Grid::make(2)->schema([
                                                    TextInput::make('code')->label('Division Code')->required()->maxLength(40)->placeholder('e.g. JSS 1 — JSS 3'),
                                                    TextInput::make('name')->label('Division Title')->required()->maxLength(80)->placeholder('e.g. Junior Secondary School'),
                                                    TextInput::make('ages')->label('Target Age Bracket')->required()->maxLength(40)->placeholder('e.g. Ages 10 — 13 Years'),
                                                    TextInput::make('certs')->label('Certifications / Qualifications')->maxLength(80)->placeholder('e.g. BECE & Cambridge Checkpoint'),
                                                ]),

                                                Textarea::make('desc')
                                                    ->label('Division Description')
                                                    ->required()
                                                    ->maxLength(300)
                                                    ->rows(2)
                                                    ->placeholder('e.g. Focuses on foundational intellectual development: computational thinking, language mastery...'),

                                                TagsInput::make('subjects')
                                                    ->label('Core Subjects / Focus Areas')
                                                    ->placeholder('Add subject...')
                                                    ->helperText('Type subject name and press Enter.'),
                                            ]),
                                    ]),
                            ]),

                        // ── Tab 9: 05 — Facilities ──────────────────────────────────────────────
                        Tab::make('05 — Facilities')
                            ->icon('heroicon-o-building-office-2')
                            ->schema([
                                Section::make('05 — Campus Infrastructure')
                                    ->description('Learning and living environments, laboratories, sporting and creative facilities.')
                                    ->headerActions([
                                        $this->makeSaveAction('Facilities', [
                                            'facilities_eyebrow', 'facilities_heading', 'facilities_items',
                                        ]),
                                    ])
                                    ->schema([
                                        Grid::make(2)->schema([
                                            TextInput::make('facilities_eyebrow')
                                                ->label('Section Eyebrow')
                                                ->maxLength(50)
                                                ->placeholder('e.g. CAMPUS INFRASTRUCTURE'),

                                            TextInput::make('facilities_heading')
                                                ->label('Facilities Main Heading')
                                                ->maxLength(120)
                                                ->placeholder('e.g. Purpose-Built Learning & Living Environments'),
                                        ]),

                                        Repeater::make('facilities_items')
                                            ->label('Campus Facilities Gallery')
                                            ->maxItems(8)
                                            ->reorderable()
                                            ->collapsible()
                                            ->itemLabel(fn (array $state): ?string => ($state['category'] ?? '') . ' — ' . ($state['title'] ?? 'Facility'))
                                            ->schema([
                                                Grid::make(3)->schema([
                                                    TextInput::make('title')
                                                        ->label('Facility Name')
                                                        ->required()
                                                        ->maxLength(80)
                                                        ->placeholder('e.g. Advanced Science Laboratories'),

                                                    TextInput::make('category')
                                                        ->label('Category Tag')
                                                        ->required()
                                                        ->maxLength(30)
                                                        ->placeholder('e.g. ACADEMIC, TECHNOLOGY, RESEARCH, ATHLETICS'),

                                                    FileUpload::make('image')
                                                        ->label('Facility Photograph')
                                                        ->image()
                                                        ->imageEditor()
                                                        ->directory('frontend')
                                                        ->disk(config('filesystems.upload_disk', 'public'))
                                                        ->maxSize(4096)
                                                        ->helperText('Facility photo for gallery and lightbox modal (max 4 MB).'),
                                                ]),

                                                Textarea::make('desc')
                                                    ->label('Facility Description')
                                                    ->required()
                                                    ->maxLength(300)
                                                    ->rows(2)
                                                    ->placeholder('e.g. Dedicated biology, chemistry, and physics laboratories fully fitted with modern glassware...'),
                                            ]),
                                    ]),
                            ]),

                        // ── Tab 10: 06 — Bulletin & News ────────────────────────────────────────
                        Tab::make('06 — Bulletin & News')
                            ->icon('heroicon-o-newspaper')
                            ->schema([
                                Section::make('06 — Bulletin & Announcements')
                                    ->description('Public announcements, term dates, and institutional news highlights.')
                                    ->headerActions([
                                        $this->makeSaveAction('Bulletin & News', [
                                            'news_eyebrow', 'news_heading', 'news_articles',
                                        ]),
                                    ])
                                    ->schema([
                                        Grid::make(2)->schema([
                                            TextInput::make('news_eyebrow')
                                                ->label('Section Eyebrow')
                                                ->maxLength(50)
                                                ->placeholder('e.g. BULLETIN & CALENDAR'),

                                            TextInput::make('news_heading')
                                                ->label('Bulletin Main Heading')
                                                ->maxLength(120)
                                                ->placeholder('e.g. Recent Announcements & Key Dates'),
                                        ]),

                                        Repeater::make('news_articles')
                                            ->label('Recent Announcements List')
                                            ->maxItems(6)
                                            ->reorderable()
                                            ->collapsible()
                                            ->itemLabel(fn (array $state): ?string => ($state['category'] ?? '') . ' — ' . ($state['title'] ?? 'Announcement'))
                                            ->schema([
                                                Grid::make(2)->schema([
                                                    TextInput::make('title')
                                                        ->label('Article Headline')
                                                        ->required()
                                                        ->maxLength(150)
                                                        ->placeholder('e.g. First Batch Entrance Examination & Screening'),

                                                    TextInput::make('category')
                                                        ->label('Category Tag')
                                                        ->required()
                                                        ->maxLength(30)
                                                        ->placeholder('e.g. ADMISSIONS, ATHLETICS, ACADEMICS'),

                                                    TextInput::make('date')
                                                        ->label('Event / Announcement Date')
                                                        ->required()
                                                        ->maxLength(50)
                                                        ->placeholder('e.g. Saturday, 18 April 2026'),

                                                    FileUpload::make('image')
                                                        ->label('Thumbnail Image')
                                                        ->image()
                                                        ->imageEditor()
                                                        ->directory('frontend')
                                                        ->disk(config('filesystems.upload_disk', 'public'))
                                                        ->maxSize(2048)
                                                        ->helperText('Left square thumbnail (max 2 MB).'),
                                                ]),

                                                Textarea::make('summary')
                                                    ->label('Article Summary')
                                                    ->required()
                                                    ->maxLength(300)
                                                    ->rows(2)
                                                    ->placeholder('e.g. Prospective candidates for JSS 1 and transfer classes will sit for screening...'),
                                            ]),
                                    ]),
                            ]),

                        // ── Tab 11: 07 — Testimonials ───────────────────────────────────────────
                        Tab::make('07 — Testimonials')
                            ->icon('heroicon-o-chat-bubble-bottom-center-text')
                            ->schema([
                                Section::make('07 — Perspectives & Testimonials')
                                    ->description('Reflections and endorsements from parents, scholars, and alumni.')
                                    ->headerActions([
                                        $this->makeSaveAction('Testimonials', [
                                            'testimonials_eyebrow', 'testimonials_heading',
                                            'testimonials_intro', 'testimonials_items',
                                        ]),
                                    ])
                                    ->schema([
                                        Grid::make(2)->schema([
                                            TextInput::make('testimonials_eyebrow')
                                                ->label('Section Eyebrow')
                                                ->maxLength(50)
                                                ->placeholder('e.g. VOICES OF PARENTS & ALUMNI'),

                                            TextInput::make('testimonials_heading')
                                                ->label('Testimonials Main Heading')
                                                ->maxLength(120)
                                                ->placeholder('e.g. Perspectives on an Apex Crown Education'),
                                        ]),

                                        Textarea::make('testimonials_intro')
                                            ->label('Intro Summary')
                                            ->maxLength(250)
                                            ->rows(2)
                                            ->placeholder('e.g. Reflections from parents, guardians, and alumni who experienced our community.'),

                                        Repeater::make('testimonials_items')
                                            ->label('Quote Cards List')
                                            ->maxItems(6)
                                            ->reorderable()
                                            ->collapsible()
                                            ->itemLabel(fn (array $state): ?string => ($state['author'] ?? '') . ' (' . ($state['role'] ?? 'Quote') . ')')
                                            ->schema([
                                                Grid::make(2)->schema([
                                                    TextInput::make('author')
                                                        ->label('Speaker / Author Name')
                                                        ->required()
                                                        ->maxLength(80)
                                                        ->placeholder('e.g. Chief & Dr. (Mrs.) Olumide Adeleke'),

                                                    TextInput::make('role')
                                                        ->label('Author Designation / Relationship')
                                                        ->required()
                                                        ->maxLength(100)
                                                        ->placeholder('e.g. Parents of 2024 Valedictorians'),
                                                ]),

                                                Textarea::make('quote')
                                                    ->label('Testimonial Quote Text')
                                                    ->required()
                                                    ->maxLength(350)
                                                    ->rows(3)
                                                    ->placeholder('e.g. Enrolling our children at Apex Crown was the most consequential choice we made...'),
                                            ]),
                                    ]),
                            ]),

                        // ── Tab 12: 08 — Admissions ─────────────────────────────────────────────
                        Tab::make('08 — Admissions')
                            ->icon('heroicon-o-clipboard-document-check')
                            ->schema([
                                Section::make('08 — Admissions & Application Steps')
                                    ->description('Admissions banner callout, step-by-step application timeline, and primary buttons.')
                                    ->headerActions([
                                        $this->makeSaveAction('Admissions', [
                                            'admissions_cta_eyebrow', 'admissions_cta_heading', 'admissions_cta_subtitle',
                                            'admissions_cta_steps', 'admissions_cta_primary_btn', 'admissions_cta_primary_link',
                                            'admissions_cta_secondary_btn', 'admissions_cta_secondary_link',
                                        ]),
                                    ])
                                    ->schema([
                                        Grid::make(2)->schema([
                                            TextInput::make('admissions_cta_eyebrow')->label('CTA Eyebrow')->maxLength(50)->placeholder('e.g. ADMISSIONS 2026 / 2027'),
                                            TextInput::make('admissions_cta_heading')->label('CTA Main Heading')->maxLength(120)->placeholder('e.g. Enroll Your Child in a Tradition of Distinction'),
                                        ]),

                                        Textarea::make('admissions_cta_subtitle')
                                            ->label('CTA Subtitle / Summary')
                                            ->maxLength(250)
                                            ->rows(2)
                                            ->placeholder('e.g. Applications are now being received for JSS 1 and limited transfer vacancies...'),

                                        Repeater::make('admissions_cta_steps')
                                            ->label('Application Steps Timeline')
                                            ->maxItems(5)
                                            ->reorderable()
                                            ->collapsible()
                                            ->itemLabel(fn (array $state): ?string => ($state['num'] ?? '') . ' — ' . ($state['title'] ?? 'Step'))
                                            ->schema([
                                                Grid::make(2)->schema([
                                                    TextInput::make('num')->label('Step Number')->required()->maxLength(10)->placeholder('e.g. 01'),
                                                    TextInput::make('title')->label('Step Title')->required()->maxLength(60)->placeholder('e.g. Obtain Form'),
                                                ]),

                                                Textarea::make('desc')->label('Step Instructions')->required()->maxLength(200)->rows(2)->placeholder('e.g. Complete the online application or visit campus Registry.'),
                                            ]),

                                        Grid::make(2)->schema([
                                            TextInput::make('admissions_cta_primary_btn')->label('Primary Button Text')->maxLength(40)->placeholder('e.g. Begin Online Application'),
                                            TextInput::make('admissions_cta_primary_link')->label('Primary Button Link')->maxLength(100)->placeholder('e.g. #contact'),
                                            TextInput::make('admissions_cta_secondary_btn')->label('Secondary Button Text')->maxLength(40)->placeholder('e.g. Download Prospectus (PDF)'),
                                            TextInput::make('admissions_cta_secondary_link')->label('Secondary Button Link')->maxLength(100)->placeholder('e.g. #contact'),
                                        ]),
                                    ]),
                            ]),

                        // ── Tab 13: 09 — Contact & FAQ ──────────────────────────────────────────
                        Tab::make('09 — Contact & FAQ')
                            ->icon('heroicon-o-envelope')
                            ->schema([
                                Section::make('Visitation & Office Desks')
                                    ->description('Campus visitation schedule, registry desks, and additional phone lines.')
                                    ->headerActions([
                                        $this->makeSaveAction('Contact & FAQ', [
                                            'contact_eyebrow', 'contact_heading', 'contact_intro',
                                            'contact_address_label', 'contact_address', 'contact_phone_label',
                                            'contact_additional_phones', 'contact_email_label', 'contact_additional_emails',
                                            'contact_visiting_hours_label', 'contact_visiting_hours',
                                            'contact_form_title', 'contact_form_desc', 'contact_form_name_label',
                                            'contact_form_phone_label', 'contact_form_email_label', 'contact_form_grade_label',
                                            'contact_form_grade_placeholder', 'contact_form_notes_label',
                                            'contact_form_classes', 'contact_form_success_title', 'contact_form_success_desc',
                                        ]),
                                    ])
                                    ->schema([
                                        Grid::make(2)->schema([
                                            TextInput::make('contact_eyebrow')->label('Section Eyebrow')->maxLength(50)->placeholder('e.g. CAMPUS VISITATION & INQUIRY'),
                                            TextInput::make('contact_heading')->label('Contact Main Heading')->maxLength(120)->placeholder('e.g. Schedule a Guided Tour or Speak with Admissions'),
                                        ]),

                                        Textarea::make('contact_intro')->label('Contact Intro Narrative')->maxLength(250)->rows(3)->placeholder('e.g. Our Admissions Registry receives families for private consultations...'),

                                        Grid::make(2)->schema([
                                            TextInput::make('contact_address_label')->label('Address Header Label')->maxLength(40)->placeholder('e.g. Campus Address'),
                                            Textarea::make('contact_address')->label('Campus Address Block')->maxLength(250)->rows(2)->placeholder('e.g. Plot 14 - 18, Apex Boulevard, Lekki Phase 1, Lagos'),

                                            TextInput::make('contact_phone_label')->label('Phone Desk Header Label')->maxLength(40)->placeholder('e.g. Telephone'),
                                            TagsInput::make('contact_additional_phones')->label('Additional Telephone Lines')->placeholder('Add phone line...')->helperText('Lines rendered alongside primary school phone.'),

                                            TextInput::make('contact_email_label')->label('Email Desk Header Label')->maxLength(40)->placeholder('e.g. Registry Email'),
                                            TagsInput::make('contact_additional_emails')->label('Additional Registry Emails')->placeholder('Add email desk...')->helperText('Email desks rendered alongside primary school email.'),

                                            TextInput::make('contact_visiting_hours_label')->label('Visiting Hours Header Label')->maxLength(40)->placeholder('e.g. Admissions Hours'),
                                            TextInput::make('contact_visiting_hours')->label('Admissions Visiting Schedule')->maxLength(120)->placeholder('e.g. Monday – Friday: 8:00 AM – 4:00 PM | Saturday: 9:00 AM – 1:00 PM'),
                                        ]),
                                    ]),

                                Section::make('Inquiry Form Configuration')
                                    ->description('Admissions inquiry form configuration and candidate grade options.')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            TextInput::make('contact_form_title')->label('Form Title')->maxLength(80)->placeholder('e.g. Admissions Prospectus Inquiry'),
                                            Textarea::make('contact_form_desc')->label('Form Subtitle / Note')->maxLength(200)->rows(2)->placeholder('e.g. Submit your inquiry and our counsellor will respond within one business day.'),
                                            TextInput::make('contact_form_name_label')->label('Parent Name Field Label')->maxLength(60)->placeholder('e.g. Parent / Guardian Name *'),
                                            TextInput::make('contact_form_phone_label')->label('Phone Field Label')->maxLength(60)->placeholder('e.g. Telephone Number *'),
                                            TextInput::make('contact_form_email_label')->label('Email Field Label')->maxLength(60)->placeholder('e.g. Email Address *'),
                                            TextInput::make('contact_form_grade_label')->label('Grade Dropdown Label')->maxLength(60)->placeholder('e.g. Class Level of Interest *'),
                                            TextInput::make('contact_form_grade_placeholder')->label('Grade Placeholder Option')->maxLength(60)->placeholder('e.g. Select Candidate Grade'),
                                            TextInput::make('contact_form_notes_label')->label('Notes Field Label')->maxLength(80)->placeholder('e.g. Prospective Scholar Notes / Questions'),
                                        ]),

                                        Repeater::make('contact_form_classes')
                                            ->label('Candidate Grade Options')
                                            ->maxItems(10)
                                            ->reorderable()
                                            ->collapsible()
                                            ->itemLabel(fn (array $state): ?string => ($state['value'] ?? '') . ' — ' . ($state['label'] ?? 'Grade'))
                                            ->schema([
                                                Grid::make(2)->schema([
                                                    TextInput::make('value')
                                                        ->label('Submission Value Token')
                                                        ->required()
                                                        ->maxLength(40)
                                                        ->placeholder('e.g. jss1')
                                                        ->helperText('Clean token saved to inquiry database record.'),

                                                    TextInput::make('label')
                                                        ->label('Parent Display Option Label')
                                                        ->required()
                                                        ->maxLength(100)
                                                        ->placeholder('e.g. Junior Secondary 1 (Entry)')
                                                        ->helperText('Full text displayed in dropdown option.'),
                                                ]),
                                            ]),

                                        Grid::make(2)->schema([
                                            TextInput::make('contact_form_success_title')->label('Submission Success Heading')->maxLength(60)->placeholder('e.g. Inquiry Received'),
                                            Textarea::make('contact_form_success_desc')->label('Submission Success Message')->maxLength(250)->rows(2)->placeholder('e.g. Thank you for inquiring. We will get in touch shortly.'),
                                        ]),
                                    ]),
                            ]),

                        // ── Tab 14: Footer ───────────────────────────────────────────────────────
                        Tab::make('Footer')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Section::make('Colophon & Accreditations')
                                    ->description('Footer columns, edition badge, mission summary, and accreditations.')
                                    ->headerActions([
                                        $this->makeSaveAction('Footer', [
                                            'footer_edition_label', 'footer_accreditations', 'footer_description',
                                            'footer_col2_heading', 'footer_col3_heading', 'footer_col4_heading',
                                            'footer_exam_link_label', 'footer_exam_link_url', 'footer_tuition_link_label',
                                            'footer_tuition_link_url', 'footer_privacy_label', 'footer_privacy_link',
                                            'footer_terms_label', 'footer_terms_link', 'footer_directions_label',
                                            'footer_directions_link',
                                        ]),
                                    ])
                                    ->schema([
                                        Grid::make(2)->schema([
                                            TextInput::make('footer_edition_label')->label('Footer Edition Badge')->maxLength(40)->placeholder('e.g. Prospectus Edition'),
                                            TextInput::make('footer_accreditations')->label('Footer Accreditations Text')->maxLength(150)->placeholder('e.g. Accredited by WAEC, NECO & Cambridge International.'),
                                        ]),

                                        Textarea::make('footer_description')->label('Footer Mission Summary')->maxLength(250)->rows(3)->placeholder('e.g. Apex Crown College is an accredited secondary school dedicated to academic brilliance...'),
                                    ]),

                                Section::make('Column Titles & Admissions Links')
                                    ->schema([
                                        Grid::make(3)->schema([
                                            TextInput::make('footer_col2_heading')->label('Column 2 Title')->maxLength(40)->placeholder('e.g. Prospectus'),
                                            TextInput::make('footer_col3_heading')->label('Column 3 Title')->maxLength(40)->placeholder('e.g. Registry & Portals'),
                                            TextInput::make('footer_col4_heading')->label('Column 4 Title')->maxLength(40)->placeholder('e.g. Campus Registry'),
                                        ]),

                                        Grid::make(2)->schema([
                                            TextInput::make('footer_exam_link_label')->label('Exam Dates Link Label')->maxLength(50)->placeholder('e.g. Entrance Examination Dates'),
                                            TextInput::make('footer_exam_link_url')->label('Exam Dates Link Target')->maxLength(100)->placeholder('e.g. #admissions'),
                                            TextInput::make('footer_tuition_link_label')->label('Tuition Link Label')->maxLength(50)->placeholder('e.g. Tuition & Scholarships'),
                                            TextInput::make('footer_tuition_link_url')->label('Tuition Link Target')->maxLength(100)->placeholder('e.g. #admissions'),
                                        ]),
                                    ]),

                                Section::make('Legal & Directions Bar')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            TextInput::make('footer_privacy_label')->label('Privacy Policy Label')->maxLength(40)->placeholder('e.g. Privacy Policy'),
                                            TextInput::make('footer_privacy_link')->label('Privacy Policy Target')->maxLength(100)->placeholder('e.g. #about'),
                                            TextInput::make('footer_terms_label')->label('Terms Label')->maxLength(40)->placeholder('e.g. Terms of Enrollment'),
                                            TextInput::make('footer_terms_link')->label('Terms Target')->maxLength(100)->placeholder('e.g. #about'),
                                            TextInput::make('footer_directions_label')->label('Directions Label')->maxLength(40)->placeholder('e.g. Campus Directions'),
                                            TextInput::make('footer_directions_link')->label('Directions Target')->maxLength(100)->placeholder('e.g. #contact'),
                                        ]),
                                    ]),
                            ]),

                        // ── Tab 15: Social Links ─────────────────────────────────────────────────
                        Tab::make('Social Links')
                            ->icon('heroicon-o-share')
                            ->schema([
                                Section::make('Social Profiles')
                                    ->description('Social media profiles rendered in website footer icons. Leave blank to hide individual profiles.')
                                    ->headerActions([
                                        $this->makeSaveAction('Social Links', [
                                            'footer_social_facebook', 'footer_social_instagram',
                                            'footer_social_linkedin', 'footer_social_x',
                                        ]),
                                    ])
                                    ->schema([
                                        Grid::make(2)->schema([
                                            TextInput::make('footer_social_facebook')->label('Facebook Page URL')->url()->maxLength(255)->placeholder('https://facebook.com/yourschool'),
                                            TextInput::make('footer_social_instagram')->label('Instagram Profile URL')->url()->maxLength(255)->placeholder('https://instagram.com/yourschool'),
                                            TextInput::make('footer_social_linkedin')->label('LinkedIn Profile URL')->url()->maxLength(255)->placeholder('https://linkedin.com/company/yourschool'),
                                            TextInput::make('footer_social_x')->label('X (Twitter) Profile URL')->url()->maxLength(255)->placeholder('https://x.com/yourschool'),
                                        ]),
                                    ]),
                            ]),

                        // ── Tab 16: SEO ──────────────────────────────────────────────────────────
                        Tab::make('SEO')
                            ->icon('heroicon-o-magnifying-glass')
                            ->schema([
                                Section::make('Search Engine Meta')
                                    ->description('Search engine optimization metadata for public website indexing.')
                                    ->headerActions([
                                        $this->makeSaveAction('SEO', [
                                            'seo_title', 'seo_description',
                                        ]),
                                    ])
                                    ->schema([
                                        TextInput::make('seo_title')
                                            ->label('Meta Title Suffix')
                                            ->maxLength(120)
                                            ->placeholder('e.g. Apex Crown College | Admissions Prospectus 2026/2027')
                                            ->helperText('Appended to page titles in browser tabs and search engine results.'),

                                        Textarea::make('seo_description')
                                            ->label('Meta Description')
                                            ->maxLength(160)
                                            ->rows(3)
                                            ->placeholder('e.g. Official admissions prospectus of Apex Crown College. Premier secondary education in Lagos.')
                                            ->helperText('Brief description shown in search engine results snippets (recommended under 160 characters).'),
                                    ]),
                            ]),

                        // ── Tab 17: Content Management ───────────────────────────────────────────
                        Tab::make('Content Management')
                            ->icon('heroicon-o-circle-stack')
                            ->schema([
                                Section::make('Content Lifecycle & Reset Tools')
                                    ->description('Use the header action buttons above to manage your website content state.')
                                    ->schema([
                                        Grid::make(3)->schema([
                                            Section::make('1. Clear Content')
                                                ->description('Clears optional prospectus sections to empty states so you can enter your school data from scratch. You can choose to preserve the hero section. Current content is snapshotted first.')
                                                ->schema([]),

                                            Section::make('2. Restore Previous')
                                                ->description('Restores the exact pre-action snapshot taken immediately before your last clear or restore action. Provides a single level of instant undo.')
                                                ->schema([]),

                                            Section::make('3. Restore Defaults')
                                                ->description('Re-runs the standard prospectus content seeder for this school, populating all 231 default values. Current content is snapshotted first.')
                                                ->schema([]),
                                        ]),
                                    ]),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function saveSectionKeys(array $keys, string $sectionLabel = 'Section'): void
    {
        $data = $this->form->getRawState();
        $singleFileKeys = ['school_logo', 'hero_image', 'about_image'];

        // 1. Process Settings Table Keys
        foreach ($keys as $key) {
            if (in_array($key, $this->settingKeys, true) && array_key_exists($key, $data)) {
                $value = $data[$key];
                if (in_array($key, $singleFileKeys, true) && is_array($value)) {
                    $first = reset($value);
                    $value = is_string($first) ? $first : null;
                }
                Setting::updateOrCreate(['key' => $key], ['value' => $value]);

                if ($key === 'primary_color' && tenant()) {
                    tenant()->update(['primary_color' => $value]);
                }
            }
        }

        // 2. Process Frontend Content Keys
        foreach ($keys as $key) {
            if (! in_array($key, $this->settingKeys, true) && array_key_exists($key, $data)) {
                $value = $data[$key];
                if (in_array($key, $singleFileKeys, true) && is_array($value)) {
                    $first = reset($value);
                    $storageValue = is_string($first) ? $first : '';
                } else {
                    $storageValue = is_array($value) ? json_encode($value) : $value;
                }
                FrontendContent::updateOrCreate(['key' => $key], ['value' => $storageValue]);
            }
        }

        // 3. Invalidate cache
        FrontendLibrary::flush(tenant('id'));

        Notification::make()
            ->title("{$sectionLabel} saved successfully")
            ->success()
            ->send();
    }

    protected function makeSaveAction(string $sectionLabel, array $keys): Action
    {
        return Action::make('save_' . \Illuminate\Support\Str::slug($sectionLabel, '_'))
            ->label("Save {$sectionLabel}")
            ->icon('heroicon-m-check')
            ->color('primary')
            ->button()
            ->action(function () use ($sectionLabel, $keys): void {
                $this->saveSectionKeys($keys, $sectionLabel);
            });
    }

    public function submit(): void
    {
        $data = $this->form->getState();
        $singleFileKeys = ['school_logo', 'hero_image', 'about_image'];

        // 1. Process Settings Table Keys
        foreach ($this->settingKeys as $key) {
            if (array_key_exists($key, $data)) {
                $value = $data[$key];
                if (in_array($key, $singleFileKeys, true) && is_array($value)) {
                    $first = reset($value);
                    $value = is_string($first) ? $first : null;
                }
                Setting::updateOrCreate(['key' => $key], ['value' => $value]);
            }
        }

        // 2. Process Frontend Content Keys
        foreach ($data as $key => $value) {
            if (! in_array($key, $this->settingKeys, true)) {
                if (in_array($key, $singleFileKeys, true) && is_array($value)) {
                    $first = reset($value);
                    $storageValue = is_string($first) ? $first : '';
                } else {
                    $storageValue = is_array($value) ? json_encode($value) : $value;
                }
                FrontendContent::updateOrCreate(['key' => $key], ['value' => $storageValue]);
            }
        }

        // 3. Update tenant primary color physical column if applicable
        if (tenant() && array_key_exists('primary_color', $data)) {
            tenant()->update(['primary_color' => $data['primary_color']]);
        }

        // 4. Invalidate cache
        FrontendLibrary::flush(tenant('id'));

        Notification::make()
            ->title('Settings saved successfully')
            ->success()
            ->send();
    }

    public function clearContent(bool $keepHero = true): void
    {
        // 1. Snapshot current content into single 'content_snapshot' row in settings table
        $currentContents = FrontendContent::query()->pluck('value', 'key')->toArray();
        Setting::updateOrCreate(['key' => 'content_snapshot'], ['value' => json_encode($currentContents)]);

        // 2. Define hero keys to preserve if keep_hero is true
        $heroKeys = [
            'hero_badge',
            'hero_title',
            'hero_subtitle',
            'hero_image',
            'hero_image_alt',
            'hero_primary_cta_text',
            'hero_primary_cta_link',
            'hero_secondary_cta_text',
            'hero_secondary_cta_link',
            'hero_scroll_label',
        ];

        // 3. Update frontend_contents rows (clearing them to empty strings or empty arrays, not deleting rows)
        $allContents = FrontendContent::all();
        foreach ($allContents as $content) {
            if ($keepHero && in_array($content->key, $heroKeys, true)) {
                continue;
            }

            $emptyValue = in_array($content->key, $this->jsonKeys, true) ? '[]' : '';
            $content->update(['value' => $emptyValue]);
        }

        // 4. Invalidate cache
        FrontendLibrary::flush(tenant('id'));

        Notification::make()
            ->title('Website content cleared successfully')
            ->success()
            ->send();

        $this->mount();
    }

    public function restorePreviousContent(): void
    {
        $snapshotJson = Setting::where('key', 'content_snapshot')->value('value');
        if (empty($snapshotJson)) {
            Notification::make()->title('No previous snapshot found')->warning()->send();
            return;
        }

        $snapshot = json_decode($snapshotJson, true);
        if (! is_array($snapshot)) {
            Notification::make()->title('Corrupted snapshot data')->danger()->send();
            return;
        }

        foreach ($snapshot as $key => $value) {
            FrontendContent::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        FrontendLibrary::flush(tenant('id'));

        Notification::make()
            ->title('Previous content snapshot restored successfully')
            ->success()
            ->send();

        $this->mount();
    }

    public function restoreDefaultContent(): void
    {
        // 1. Snapshot current state first
        $currentContents = FrontendContent::query()->pluck('value', 'key')->toArray();
        Setting::updateOrCreate(['key' => 'content_snapshot'], ['value' => json_encode($currentContents)]);

        // 2. Re-run tenant seeder
        (new \Database\Seeders\TenantFrontendContentSeeder())->run();

        // 3. Flush cache
        FrontendLibrary::flush(tenant('id'));

        Notification::make()
            ->title('Standard default content restored successfully')
            ->success()
            ->send();

        $this->mount();
    }
}
