<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FrontendContentResource\Pages\CreateFrontendContent;
use App\Filament\Resources\FrontendContentResource\Pages\EditFrontendContent;
use App\Filament\Resources\FrontendContentResource\Pages\ListFrontendContents;
use App\Models\FrontendContent;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FrontendContentResource extends Resource
{
    protected static ?string $model = FrontendContent::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    public static function canAccess(): bool
    {
        return auth()->user()?->role === 'sudo';
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->role === 'sudo';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->role === 'sudo';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'System';
    }

    public static function getNavigationLabel(): string
    {
        return 'Advanced Content Keys';
    }

    public static function getNavigationSort(): ?int
    {
        return 99;
    }

    public static function form(Schema $schema): Schema
    {
        $isImage = function (callable $get) {
            $key = $get('key');
            if (! $key) return false;
            return str_ends_with($key, '_image') || 
                   str_ends_with($key, '_logo') || 
                   $key === 'hero_image' || 
                   $key === 'about_image' || 
                   $key === 'site_logo';
        };

        $isTextarea = function (callable $get) use ($isImage) {
            if ($isImage($get)) return false;
            $key = $get('key');
            if (! $key) return false;
            return str_contains($key, 'description') || 
                   str_contains($key, 'text') || 
                   str_contains($key, 'intro') || 
                   str_contains($key, 'subtitle') || 
                   str_contains($key, 'body') || 
                   str_contains($key, 'desc') || 
                   str_contains($key, 'detail') || 
                   str_contains($key, 'summary') || 
                   str_contains($key, 'quote') || 
                   str_contains($key, 'items') || 
                   str_contains($key, 'tracks') || 
                   str_contains($key, 'articles') || 
                   str_contains($key, 'steps') || 
                   str_contains($key, 'classes') || 
                   str_contains($key, 'destinations') || 
                   str_contains($key, 'phones') || 
                   str_contains($key, 'emails') || 
                   str_contains($key, 'hours');
        };

        $isTextInput = function (callable $get) use ($isImage, $isTextarea) {
            return ! $isImage($get) && ! $isTextarea($get);
        };

        return $schema
            ->components([
                Select::make('key')
                    ->required()
                    ->searchable()
                    ->options([
                        // Topbar & Nav
                        'topbar_badge' => 'Top Bar Session Badge',
                        'topbar_text' => 'Top Bar Announcement Text',
                        'nav_about_label' => 'Nav: About',
                        'nav_features_label' => 'Nav: Distinctives',
                        'nav_academics_label' => 'Nav: Curriculum',
                        'nav_stats_label' => 'Nav: Outcomes',
                        'nav_facilities_label' => 'Nav: Campus',
                        'nav_news_label' => 'Nav: Bulletin',
                        'nav_contact_label' => 'Nav: Contact',
                        'nav_portals_label' => 'Nav: Portals Dropdown Label',
                        'portal_student_label' => 'Portal: Student Full Label',
                        'portal_staff_label' => 'Portal: Faculty Full Label',
                        'portal_admin_label' => 'Portal: Admin Full Label',
                        'portal_student_label_short' => 'Portal: Student Short Label (Mobile)',
                        'portal_staff_label_short' => 'Portal: Faculty Short Label (Mobile)',
                        'portal_admin_label_short' => 'Portal: Admin Short Label (Mobile)',
                        'header_cta_text' => 'Header CTA Button Text',
                        'header_cta_link' => 'Header CTA Button Link',

                        // Hero Section
                        'hero_badge' => 'Hero: Academic Badge',
                        'hero_title' => 'Hero: Main Heading',
                        'hero_subtitle' => 'Hero: Subtitle',
                        'hero_image' => 'Hero: Background Photograph',
                        'hero_image_alt' => 'Hero: Background Image Alt Text',
                        'hero_primary_cta_text' => 'Hero: Primary CTA Text',
                        'hero_primary_cta_link' => 'Hero: Primary CTA Link',
                        'hero_secondary_cta_text' => 'Hero: Secondary CTA Text',
                        'hero_secondary_cta_link' => 'Hero: Secondary CTA Link',
                        'hero_scroll_label' => 'Hero: Scroll Prompt Text',

                        // 01 About Section
                        'about_eyebrow' => 'About: Eyebrow Label',
                        'about_heading' => 'About: Main Heading',
                        'about_image' => 'About: Portrait Photograph',
                        'about_image_alt' => 'About: Portrait Alt Text',
                        'about_years_badge' => 'About: Years Legacy Numeral',
                        'about_years_label' => 'About: Years Legacy Caption',
                        'about_body' => 'About: Prose Narrative (HTML RichEditor)',
                        'about_principal_name' => 'About: Principal Full Name',
                        'about_principal_title' => 'About: Principal Official Title',

                        // 02 Distinctives
                        'features_eyebrow' => 'Features: Eyebrow Label',
                        'features_heading' => 'Features: Main Heading',
                        'features_intro' => 'Features: Intro Narrative',
                        'features_cta_text' => 'Features: Curriculum CTA Text',
                        'features_cta_link' => 'Features: Curriculum CTA Link',
                        'features_items' => 'Features: Pillars Repeater (JSON Array)',

                        // 03 Outcomes
                        'stats_eyebrow' => 'Stats: Eyebrow Label',
                        'stats_heading' => 'Stats: Main Heading',
                        'stats_items' => 'Stats: Outcomes Repeater (JSON Array)',
                        'stats_destinations_label' => 'Stats: Destinations Footnote Label',
                        'stats_destinations' => 'Stats: University Destinations List (JSON Array)',

                        // 04 Academics
                        'academics_eyebrow' => 'Academics: Eyebrow Label',
                        'academics_heading' => 'Academics: Main Heading',
                        'academics_intro' => 'Academics: Intro Narrative',
                        'academics_tracks' => 'Academics: Division Tracks Repeater (JSON Array)',

                        // 05 Facilities
                        'facilities_eyebrow' => 'Facilities: Eyebrow Label',
                        'facilities_heading' => 'Facilities: Main Heading',
                        'facilities_items' => 'Facilities: Campus Items Repeater (JSON Array)',

                        // 06 News
                        'news_eyebrow' => 'News: Eyebrow Label',
                        'news_heading' => 'News: Main Heading',
                        'news_articles' => 'News: Recent Announcements Repeater (JSON Array)',

                        // 07 Testimonials
                        'testimonials_eyebrow' => 'Testimonials: Eyebrow Label',
                        'testimonials_heading' => 'Testimonials: Main Heading',
                        'testimonials_intro' => 'Testimonials: Intro Narrative',
                        'testimonials_items' => 'Testimonials: Quotes Repeater (JSON Array)',

                        // Admissions CTA
                        'admissions_cta_eyebrow' => 'Admissions: CTA Eyebrow',
                        'admissions_cta_heading' => 'Admissions: CTA Main Heading',
                        'admissions_cta_subtitle' => 'Admissions: CTA Subtitle',
                        'admissions_cta_steps' => 'Admissions: 4-Step Protocol Repeater (JSON Array)',
                        'admissions_cta_primary_btn' => 'Admissions: Primary Button Text',
                        'admissions_cta_primary_link' => 'Admissions: Primary Button Link',
                        'admissions_cta_secondary_btn' => 'Admissions: Secondary Button Text',
                        'admissions_cta_secondary_link' => 'Admissions: Secondary Button Link',

                        // Contact & Inquiry
                        'contact_eyebrow' => 'Contact: Eyebrow Label',
                        'contact_heading' => 'Contact: Main Heading',
                        'contact_intro' => 'Contact: Intro Narrative',
                        'contact_address' => 'Contact: Campus Address Block',
                        'contact_address_label' => 'Contact: Address Header Label',
                        'contact_phone_label' => 'Contact: Phone Header Label',
                        'contact_additional_phones' => 'Contact: Additional Phones (JSON Array)',
                        'contact_email_label' => 'Contact: Email Header Label',
                        'contact_additional_emails' => 'Contact: Additional Emails (JSON Array)',
                        'contact_visiting_hours_label' => 'Contact: Visiting Hours Header Label',
                        'contact_visiting_hours' => 'Contact: Admissions Visiting Hours',
                        'contact_form_title' => 'Contact: Inquiry Form Title',
                        'contact_form_desc' => 'Contact: Inquiry Form Description',
                        'contact_form_name_label' => 'Contact: Form Parent Name Label',
                        'contact_form_phone_label' => 'Contact: Form Phone Label',
                        'contact_form_email_label' => 'Contact: Form Email Label',
                        'contact_form_grade_label' => 'Contact: Form Grade Select Label',
                        'contact_form_grade_placeholder' => 'Contact: Form Grade Placeholder',
                        'contact_form_classes' => 'Contact: Grade Options (JSON Array)',
                        'contact_form_notes_label' => 'Contact: Form Notes Label',
                        'contact_form_success_title' => 'Contact: Inquiry Success Title',
                        'contact_form_success_desc' => 'Contact: Inquiry Success Message',

                        // Footer
                        'footer_edition_label' => 'Footer: Prospectus Edition Label',
                        'footer_description' => 'Footer: Mission Summary Description',
                        'footer_accreditations' => 'Footer: Accreditations Footnote',
                        'footer_col2_heading' => 'Footer: Column 2 Title',
                        'footer_col3_heading' => 'Footer: Column 3 Title',
                        'footer_col4_heading' => 'Footer: Column 4 Title',
                        'footer_exam_link_label' => 'Footer: Exam Link Label',
                        'footer_exam_link_url' => 'Footer: Exam Link URL',
                        'footer_tuition_link_label' => 'Footer: Tuition Link Label',
                        'footer_tuition_link_url' => 'Footer: Tuition Link URL',
                        'footer_privacy_label' => 'Footer: Privacy Policy Label',
                        'footer_privacy_link' => 'Footer: Privacy Policy Link',
                        'footer_terms_label' => 'Footer: Terms Label',
                        'footer_terms_link' => 'Footer: Terms Link',
                        'footer_directions_label' => 'Footer: Directions Label',
                        'footer_directions_link' => 'Footer: Directions Link',
                    ])
                    ->unique(FrontendContent::class, 'key', ignoreRecord: true)
                    ->columnSpanFull()
                    ->disabled(fn ($record) => $record !== null)
                    ->dehydrated()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (callable $set, $state) {
                        $groups = [
                            'topbar_badge'               => 'home.topbar',
                            'topbar_text'                => 'home.topbar',
                            'nav_about_label'            => 'home.nav',
                            'nav_features_label'         => 'home.nav',
                            'nav_academics_label'        => 'home.nav',
                            'nav_stats_label'            => 'home.nav',
                            'nav_facilities_label'       => 'home.nav',
                            'nav_news_label'             => 'home.nav',
                            'nav_contact_label'          => 'home.nav',
                            'nav_portals_label'          => 'home.nav',
                            'portal_student_label'       => 'home.portals',
                            'portal_staff_label'         => 'home.portals',
                            'portal_admin_label'         => 'home.portals',
                            'portal_student_label_short' => 'home.portals',
                            'portal_staff_label_short'   => 'home.portals',
                            'portal_admin_label_short'   => 'home.portals',
                            'header_cta_text'            => 'home.header',
                            'header_cta_link'            => 'home.header',
                            'hero_badge'                 => 'home.hero',
                            'hero_title'                 => 'home.hero',
                            'hero_subtitle'              => 'home.hero',
                            'hero_image'                 => 'home.hero',
                            'hero_image_alt'             => 'home.hero',
                            'hero_primary_cta_text'      => 'home.hero',
                            'hero_primary_cta_link'      => 'home.hero',
                            'hero_secondary_cta_text'    => 'home.hero',
                            'hero_secondary_cta_link'    => 'home.hero',
                            'hero_scroll_label'          => 'home.hero',
                            'about_eyebrow'              => 'home.about',
                            'about_heading'              => 'home.about',
                            'about_image'                => 'home.about',
                            'about_image_alt'            => 'home.about',
                            'about_years_badge'          => 'home.about',
                            'about_years_label'          => 'home.about',
                            'about_body'                 => 'home.about',
                            'about_principal_name'       => 'home.about',
                            'about_principal_title'      => 'home.about',
                            'features_eyebrow'           => 'home.features',
                            'features_heading'           => 'home.features',
                            'features_intro'             => 'home.features',
                            'features_cta_text'          => 'home.features',
                            'features_cta_link'          => 'home.features',
                            'features_items'             => 'home.features',
                            'stats_eyebrow'              => 'home.stats',
                            'stats_heading'              => 'home.stats',
                            'stats_items'                => 'home.stats',
                            'stats_destinations_label'   => 'home.stats',
                            'stats_destinations'         => 'home.stats',
                            'academics_eyebrow'          => 'home.academics',
                            'academics_heading'          => 'home.academics',
                            'academics_intro'            => 'home.academics',
                            'academics_tracks'           => 'home.academics',
                            'facilities_eyebrow'         => 'home.facilities',
                            'facilities_heading'         => 'home.facilities',
                            'facilities_items'           => 'home.facilities',
                            'news_eyebrow'               => 'home.news',
                            'news_heading'               => 'home.news',
                            'news_articles'              => 'home.news',
                            'testimonials_eyebrow'       => 'home.testimonials',
                            'testimonials_heading'       => 'home.testimonials',
                            'testimonials_intro'         => 'home.testimonials',
                            'testimonials_items'         => 'home.testimonials',
                            'admissions_cta_eyebrow'        => 'home.admissions',
                            'admissions_cta_heading'        => 'home.admissions',
                            'admissions_cta_subtitle'       => 'home.admissions',
                            'admissions_cta_steps'          => 'home.admissions',
                            'admissions_cta_primary_btn'    => 'home.admissions',
                            'admissions_cta_primary_link'   => 'home.admissions',
                            'admissions_cta_secondary_btn'  => 'home.admissions',
                            'admissions_cta_secondary_link' => 'home.admissions',
                            'contact_eyebrow'                => 'home.contact',
                            'contact_heading'                => 'home.contact',
                            'contact_intro'                  => 'home.contact',
                            'contact_address'                => 'home.contact',
                            'contact_address_label'          => 'home.contact',
                            'contact_phone_label'            => 'home.contact',
                            'contact_additional_phones'      => 'home.contact',
                            'contact_email_label'            => 'home.contact',
                            'contact_additional_emails'      => 'home.contact',
                            'contact_visiting_hours_label'   => 'home.contact',
                            'contact_visiting_hours'         => 'home.contact',
                            'contact_form_title'             => 'home.contact',
                            'contact_form_desc'              => 'home.contact',
                            'contact_form_name_label'        => 'home.contact',
                            'contact_form_phone_label'       => 'home.contact',
                            'contact_form_email_label'       => 'home.contact',
                            'contact_form_grade_label'       => 'home.contact',
                            'contact_form_grade_placeholder' => 'home.contact',
                            'contact_form_classes'           => 'home.contact',
                            'contact_form_notes_label'       => 'home.contact',
                            'contact_form_success_title'     => 'home.contact',
                            'contact_form_success_desc'      => 'home.contact',
                            'footer_edition_label'           => 'home.footer',
                            'footer_description'             => 'home.footer',
                            'footer_accreditations'          => 'home.footer',
                            'footer_col2_heading'            => 'home.footer',
                            'footer_col3_heading'            => 'home.footer',
                            'footer_col4_heading'            => 'home.footer',
                            'footer_exam_link_label'         => 'home.footer',
                            'footer_exam_link_url'           => 'home.footer',
                            'footer_tuition_link_label'      => 'home.footer',
                            'footer_tuition_link_url'        => 'home.footer',
                            'footer_privacy_label'           => 'home.footer',
                            'footer_privacy_link'            => 'home.footer',
                            'footer_terms_label'             => 'home.footer',
                            'footer_terms_link'              => 'home.footer',
                            'footer_directions_label'        => 'home.footer',
                            'footer_directions_link'         => 'home.footer',
                        ];
                        if (isset($groups[$state])) {
                            $set('group', $groups[$state]);
                        }
                    }),

                TextInput::make('group')
                    ->maxLength(255)
                    ->helperText('Assigned automatically based on the selected key.')
                    ->disabled()
                    ->dehydrated(),

                FileUpload::make('value_image')
                    ->label('Value (Image)')
                    ->directory('frontend')
                    ->disk(config('filesystems.upload_disk', 'public'))
                    ->columnSpanFull()
                    ->visible($isImage)
                    ->dehydrated(fn ($state, $component) => $component->isVisible()),

                Textarea::make('value_textarea')
                    ->label('Value (Long Text / JSON / HTML)')
                    ->rows(6)
                    ->columnSpanFull()
                    ->helperText('Supports HTML or JSON content for repeaters/arrays.')
                    ->visible($isTextarea)
                    ->dehydrated(fn ($state, $component) => $component->isVisible()),

                TextInput::make('value_text')
                    ->label('Value (Short Text)')
                    ->columnSpanFull()
                    ->visible($isTextInput)
                    ->dehydrated(fn ($state, $component) => $component->isVisible()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('group')
                    ->badge()
                    ->color('gray')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('key')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->fontFamily('mono'),

                TextColumn::make('value')
                    ->limit(60)
                    ->searchable()
                    ->wrap(),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('group')
            ->filters([
                SelectFilter::make('group')
                    ->options(fn () => FrontendContent::query()
                        ->whereNotNull('group')
                        ->distinct()
                        ->pluck('group', 'group')
                        ->toArray()
                    )
                    ->label('Group'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListFrontendContents::route('/'),
            'create' => CreateFrontendContent::route('/create'),
            'edit'   => EditFrontendContent::route('/{record}/edit'),
        ];
    }
}
