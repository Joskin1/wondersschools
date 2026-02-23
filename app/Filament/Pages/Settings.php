<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Services\FrontendContentService;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class Settings extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected string $view = 'filament.pages.settings';

    protected static ?string $navigationLabel = 'Website Settings';

    protected static ?string $title = 'Website Settings';

    public ?array $data = [];

    // Keys whose values are JSON arrays (Repeater / FileUpload multiple).
    // These must be decoded before filling the form and encoded before saving.
    private const JSON_KEYS = [
        'hero_images',
        'trust_items',
        'bento_cards',
        'stats',
        'core_values',
        'academics_levels',
        'academics_subjects',
        'admissions_steps',
    ];

    public function mount(): void
    {
        $settings = Setting::all()->pluck('value', 'key')->toArray();

        foreach (self::JSON_KEYS as $key) {
            if (isset($settings[$key]) && is_string($settings[$key])) {
                $decoded = json_decode($settings[$key], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $settings[$key] = $decoded;
                }
            }
        }

        $this->form->fill($settings);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Tabs::make('Settings')
                    ->tabs([

                        // ─── TAB 1: BRANDING ───────────────────────────────────
                        Tab::make('Branding')
                            ->icon('heroicon-o-building-office')
                            ->schema([
                                Section::make('School Identity')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('school_name')
                                            ->label('School Name')
                                            ->required()
                                            ->columnSpan(2),
                                        TextInput::make('school_tagline')
                                            ->label('School Tagline / Motto')
                                            ->placeholder('e.g. A Foundation That Builds Futures.')
                                            ->columnSpan(2),
                                        FileUpload::make('site_logo')
                                            ->label('School Logo')
                                            ->image()
                                            ->directory('settings')
                                            ->columnSpan(2),
                                    ]),
                                Section::make('Footer')
                                    ->schema([
                                        Textarea::make('footer_description')
                                            ->label('Footer Description')
                                            ->rows(3)
                                            ->placeholder('A short sentence describing the school for the footer.'),
                                    ]),
                                Section::make('Social & Messaging')
                                    ->schema([
                                        TextInput::make('social_whatsapp')
                                            ->label('WhatsApp Number')
                                            ->placeholder('+2348000000000')
                                            ->tel(),
                                    ]),
                            ]),

                        // ─── TAB 2: HOME PAGE ──────────────────────────────────
                        Tab::make('Home Page')
                            ->icon('heroicon-o-home')
                            ->schema([
                                Section::make('Hero Section')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('hero_tagline')
                                            ->label('Hero Tagline (small text above heading)')
                                            ->placeholder('Welcome to ...')
                                            ->columnSpan(2),
                                        TextInput::make('hero_heading')
                                            ->label('Hero Heading')
                                            ->placeholder('A Foundation That Builds Futures.')
                                            ->columnSpan(2),
                                        Textarea::make('hero_description')
                                            ->label('Hero Description')
                                            ->rows(3)
                                            ->columnSpan(2),
                                        TextInput::make('hero_cta_primary_text')
                                            ->label('Primary CTA Button Text')
                                            ->placeholder('Explore Our Curriculum'),
                                        TextInput::make('hero_cta_secondary_text')
                                            ->label('Secondary CTA Button Text')
                                            ->placeholder('Book a Tour'),
                                        FileUpload::make('hero_images')
                                            ->label('Hero Carousel Images')
                                            ->image()
                                            ->multiple()
                                            ->directory('hero')
                                            ->columnSpan(2),
                                    ]),

                                Section::make('Trust Strip')
                                    ->schema([
                                        Repeater::make('trust_items')
                                            ->label('Trust Strip Items')
                                            ->schema([
                                                TextInput::make('label')
                                                    ->label('Item Label')
                                                    ->required()
                                                    ->placeholder('e.g. Verified Curriculum'),
                                            ])
                                            ->defaultItems(4)
                                            ->addActionLabel('Add Trust Item')
                                            ->collapsible(),
                                    ]),

                                Section::make('"Why Us" Section')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('why_us_heading')
                                            ->label('Section Heading')
                                            ->placeholder('Why choose us?')
                                            ->columnSpan(2),
                                        TextInput::make('why_us_subheading')
                                            ->label('Section Subheading')
                                            ->placeholder('Because Every Child is a World of Potential.')
                                            ->columnSpan(2),
                                        Repeater::make('bento_cards')
                                            ->label('Feature Cards')
                                            ->schema([
                                                TextInput::make('title')->required()->label('Card Title'),
                                                Textarea::make('description')->rows(2)->label('Card Description'),
                                            ])
                                            ->defaultItems(3)
                                            ->addActionLabel('Add Card')
                                            ->collapsible()
                                            ->columnSpan(2),
                                    ]),

                                Section::make('Statistics Strip')
                                    ->schema([
                                        Repeater::make('stats')
                                            ->label('Statistics')
                                            ->schema([
                                                TextInput::make('value')->required()->label('Value')->placeholder('15+'),
                                                TextInput::make('label')->required()->label('Label')->placeholder('Years of Excellence'),
                                            ])
                                            ->defaultItems(4)
                                            ->addActionLabel('Add Statistic')
                                            ->collapsible()
                                            ->columns(2),
                                    ]),
                            ]),

                        // ─── TAB 3: ABOUT PAGE ─────────────────────────────────
                        Tab::make('About Page')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Section::make('Hero')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('about_heading')
                                            ->label('Page Heading')
                                            ->placeholder('We Build Foundations That Last.')
                                            ->columnSpan(2),
                                        TextInput::make('about_tagline')
                                            ->label('Page Tagline')
                                            ->placeholder('Your School Name')
                                            ->columnSpan(2),
                                    ]),
                                Section::make('About Description')
                                    ->schema([
                                        Textarea::make('about_description')
                                            ->label('About the School (main paragraph)')
                                            ->rows(5),
                                    ]),
                                Section::make('Mission & Vision')
                                    ->schema([
                                        Textarea::make('mission_statement')
                                            ->label('Mission Statement')
                                            ->rows(3),
                                        Textarea::make('vision_statement')
                                            ->label('Vision Statement')
                                            ->rows(3),
                                    ]),
                                Section::make('Core Values')
                                    ->schema([
                                        Repeater::make('core_values')
                                            ->label('Core Values')
                                            ->schema([
                                                TextInput::make('title')->required()->label('Value Title'),
                                                Textarea::make('description')->rows(2)->label('Description'),
                                            ])
                                            ->defaultItems(5)
                                            ->addActionLabel('Add Core Value')
                                            ->collapsible(),
                                    ]),
                            ]),

                        // ─── TAB 4: ACADEMICS PAGE ─────────────────────────────
                        Tab::make('Academics')
                            ->icon('heroicon-o-academic-cap')
                            ->schema([
                                Section::make('Hero')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('academics_heading')
                                            ->label('Page Heading')
                                            ->columnSpan(2),
                                        TextInput::make('academics_tagline')
                                            ->label('Page Tagline')
                                            ->columnSpan(2),
                                    ]),
                                Section::make('Introduction')
                                    ->schema([
                                        RichEditor::make('academics_intro')
                                            ->label('Academics Introduction')
                                            ->toolbarButtons([
                                                'bold', 'italic', 'underline', 'link',
                                                'bulletList', 'orderedList',
                                            ]),
                                    ]),
                                Section::make('Learning Levels')
                                    ->schema([
                                        Repeater::make('academics_levels')
                                            ->label('Learning Levels (e.g. EYFS, Primary)')
                                            ->schema([
                                                TextInput::make('title')->required()->label('Level Title'),
                                                Textarea::make('focus')->rows(2)->label('Focus Description'),
                                                Textarea::make('outcome')->rows(2)->label('Key Outcome'),
                                            ])
                                            ->defaultItems(2)
                                            ->addActionLabel('Add Level')
                                            ->collapsible(),
                                    ]),
                                Section::make('Subject Highlights')
                                    ->schema([
                                        Repeater::make('academics_subjects')
                                            ->label('Subject Highlights')
                                            ->schema([
                                                TextInput::make('title')->required()->label('Subject Name'),
                                                Textarea::make('description')->rows(3)->label('Description'),
                                            ])
                                            ->defaultItems(4)
                                            ->addActionLabel('Add Subject')
                                            ->collapsible(),
                                    ]),
                            ]),

                        // ─── TAB 5: ADMISSIONS PAGE ────────────────────────────
                        Tab::make('Admissions')
                            ->icon('heroicon-o-clipboard-document-list')
                            ->schema([
                                Section::make('Hero')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('admissions_heading')
                                            ->label('Page Heading')
                                            ->placeholder('Admissions')
                                            ->columnSpan(2),
                                        TextInput::make('admissions_tagline')
                                            ->label('Page Tagline')
                                            ->placeholder('Join the family today.')
                                            ->columnSpan(2),
                                    ]),
                                Section::make('Admission Process')
                                    ->schema([
                                        Textarea::make('admissions_intro')
                                            ->label('Process Introduction')
                                            ->rows(2),
                                        Repeater::make('admissions_steps')
                                            ->label('Admission Steps')
                                            ->schema([
                                                TextInput::make('title')->required()->label('Step Title'),
                                                Textarea::make('description')->rows(2)->label('Step Description'),
                                            ])
                                            ->defaultItems(3)
                                            ->addActionLabel('Add Step')
                                            ->collapsible(),
                                    ]),
                                Section::make('School Fees')
                                    ->schema([
                                        Textarea::make('fees_intro')
                                            ->label('Fees Introduction')
                                            ->rows(2),
                                        TextInput::make('fee_schedule_link')
                                            ->label('Fee Schedule PDF URL')
                                            ->url()
                                            ->placeholder('https://...'),
                                    ]),
                            ]),

                        // ─── TAB 6: CONTACT & SEO ──────────────────────────────
                        Tab::make('Contact & SEO')
                            ->icon('heroicon-o-map-pin')
                            ->schema([
                                Section::make('Contact Information')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('school_address')
                                            ->label('School Address')
                                            ->columnSpan(2),
                                        TextInput::make('school_phone')
                                            ->label('Phone Number')
                                            ->tel(),
                                        TextInput::make('school_email')
                                            ->label('Email Address')
                                            ->email(),
                                        TextInput::make('maps_embed_url')
                                            ->label('Google Maps Embed URL')
                                            ->url()
                                            ->placeholder('https://www.google.com/maps/embed?...')
                                            ->columnSpan(2),
                                    ]),
                                Section::make('SEO & Meta Tags')
                                    ->schema([
                                        TextInput::make('seo_title')
                                            ->label('SEO Page Title')
                                            ->placeholder('School Name — Tagline'),
                                        Textarea::make('seo_description')
                                            ->label('SEO Meta Description')
                                            ->rows(3)
                                            ->maxLength(160),
                                        FileUpload::make('seo_og_image')
                                            ->label('Open Graph / Social Share Image')
                                            ->image()
                                            ->directory('settings'),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => is_array($value) ? json_encode($value) : $value]
            );
        }

        // Flush the singleton cache so the next frontend request picks up changes.
        app(FrontendContentService::class)->flush();

        Notification::make()
            ->title('Settings saved successfully')
            ->success()
            ->send();
    }
}
