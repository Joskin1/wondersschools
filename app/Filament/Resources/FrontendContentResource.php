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

    public static function getNavigationGroup(): ?string
    {
        return 'Website';
    }

    public static function getNavigationLabel(): string
    {
        return 'Frontend Content';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public static function form(Schema $schema): Schema
    {
        $isImage = function (callable $get) {
            $key = $get('key');
            if (! $key) return false;
            return str_ends_with($key, '_image') || 
                   str_ends_with($key, '_logo') || 
                   $key === 'hero_images' || 
                   $key === 'site_logo';
        };

        $isTextarea = function (callable $get) use ($isImage) {
            if ($isImage($get)) return false;
            $key = $get('key');
            if (! $key) return false;
            return str_contains($key, 'description') || 
                   str_contains($key, 'text') || 
                   str_contains($key, 'mission') || 
                   str_contains($key, 'vision') || 
                   str_contains($key, 'body') || 
                   str_contains($key, 'icon');
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
                        'hero_images' => 'Hero Images (JSON Slider Array)',
                        'hero_tagline' => 'Hero Tagline',
                        'hero_heading' => 'Hero Heading Part 1',
                        'hero_heading_highlight' => 'Hero Heading Part 2 (Highlight)',
                        'hero_description' => 'Hero Description',
                        'hero_cta_primary' => 'Hero Primary CTA Button',
                        'hero_cta_secondary' => 'Hero Secondary CTA Button',
                        
                        'about_intro_welcome' => 'About Welcome Badge',
                        'about_intro_heading' => 'About Intro Heading',
                        'about_intro_text' => 'About Intro Text',
                        'about_intro_mission' => 'About Intro Mission',
                        'about_intro_read_more' => 'About Read More Button',
                        
                        'pillar_1_label' => 'Pillar 1: Label',
                        'pillar_1_image' => 'Pillar 1: Image',
                        'pillar_2_label' => 'Pillar 2: Label',
                        'pillar_2_image' => 'Pillar 2: Image',
                        'pillar_3_label' => 'Pillar 3: Label',
                        'pillar_3_image' => 'Pillar 3: Image',
                        'pillar_4_label' => 'Pillar 4: Label',
                        'pillar_4_image' => 'Pillar 4: Image',
                        
                        'trust_1' => 'Trust Strip Badge 1',
                        'trust_2' => 'Trust Strip Badge 2',
                        'trust_3' => 'Trust Strip Badge 3',
                        'trust_4' => 'Trust Strip Badge 4',
                        
                        'why_us_heading' => 'Why Us Section Heading',
                        'why_us_subheading' => 'Why Us Section Subheading',
                        
                        'feature_1_title' => 'Feature 1: Title',
                        'feature_1_description' => 'Feature 1: Description',
                        'feature_1_icon' => 'Feature 1: SVG Icon Path',
                        'feature_2_title' => 'Feature 2: Title',
                        'feature_2_description' => 'Feature 2: Description',
                        'feature_2_icon' => 'Feature 2: SVG Icon Path',
                        'feature_3_title' => 'Feature 3: Title',
                        'feature_3_description' => 'Feature 3: Description',
                        'feature_3_icon' => 'Feature 3: SVG Icon Path',
                        'feature_4_title' => 'Feature 4: Title',
                        'feature_4_description' => 'Feature 4: Description',
                        'feature_4_icon' => 'Feature 4: SVG Icon Path',
                        
                        'stat_1_value' => 'Stat 1: Value',
                        'stat_1_label' => 'Stat 1: Label',
                        'stat_2_value' => 'Stat 2: Value',
                        'stat_2_label' => 'Stat 2: Label',
                        'stat_3_value' => 'Stat 3: Value',
                        'stat_3_label' => 'Stat 3: Label',
                        'stat_4_value' => 'Stat 4: Value',
                        'stat_4_label' => 'Stat 4: Label',
                        
                        'news_heading' => 'News Section Heading',
                        'news_subheading' => 'News Section Subheading',
                        'news_view_all_label' => 'News View All Button',
                        'news_badge_label' => 'News Post Badge Text',
                        'news_read_more_label' => 'News Read More Button',
                        'news_empty_text' => 'News Empty State Text',
                        'news_view_all_mobile_label' => 'News View All Mobile Button',
                        
                        'cta_heading' => 'CTA Section Heading',
                        'cta_description' => 'CTA Section Description',
                        'cta_enrol' => 'CTA Enrol Button',
                        'cta_tour' => 'CTA Book Tour Button',
                        'cta_whatsapp' => 'CTA WhatsApp Button',
                        
                        'student_portal_url' => 'Student Portal URL',
                        'staff_portal_url' => 'Staff Portal URL',
                        'common_entrance_url' => 'Common Entrance URL',
                        
                        'footer_social_facebook' => 'Footer Facebook URL',
                        'footer_social_instagram' => 'Footer Instagram URL',
                        'footer_social_linkedin' => 'Footer LinkedIn URL',
                        'footer_social_x' => 'Footer X/Twitter URL',
                        
                        'about_hero_title' => 'About Page: Hero Title',
                        'about_hero_subtitle' => 'About Page: Hero Subtitle',
                        'about_description' => 'About Page: Description (HTML allowed)',
                        'about_mission_title' => 'About Page: Mission Title',
                        'about_mission_text' => 'About Page: Mission Description',
                        'about_vision_title' => 'About Page: Vision Title',
                        'about_vision_text' => 'About Page: Vision Description',
                        'about_core_values_title' => 'About Page: Core Values Title',
                        'core_value_1' => 'Core Value 1',
                        'core_value_2' => 'Core Value 2',
                        'core_value_3' => 'Core Value 3',
                        'core_value_4' => 'Core Value 4',
                        'core_value_5' => 'Core Value 5',
                        
                        'about_leadership_title' => 'About Page: Leadership Title',
                        'about_leadership_subtitle' => 'About Page: Leadership Subtitle',
                        'about_leadership_empty' => 'About Page: Leadership Empty State',
                        
                        'advantage_hero_title' => 'Academics Page: Hero Title',
                        'advantage_hero_subtitle' => 'Academics Page: Hero Subtitle',
                        'advantage_intro' => 'Academics Page: Introduction Text (HTML allowed)',
                        'learning_levels_title' => 'Academics Page: Learning Levels Title',
                        'learning_levels_subtitle' => 'Academics Page: Learning Levels Subtitle',
                        'eyfs_title' => 'Academics Page: EYFS Title',
                        'eyfs_focus_label' => 'Academics Page: EYFS Focus Label',
                        'eyfs_focus_text' => 'Academics Page: EYFS Focus Description',
                        'eyfs_outcome_label' => 'Academics Page: EYFS Outcome Label',
                        'eyfs_outcome_text' => 'Academics Page: EYFS Outcome Description',
                        'primary_title' => 'Academics Page: Primary School Title',
                        'primary_focus_label' => 'Academics Page: Primary Focus Label',
                        'primary_focus_text' => 'Academics Page: Primary Focus Description',
                        'primary_outcome_label' => 'Academics Page: Primary Outcome Label',
                        'primary_outcome_text' => 'Academics Page: Primary Outcome Description',
                        'subjects_title' => 'Academics Page: Subjects Title',
                        'subjects_subtitle' => 'Academics Page: Subjects Subtitle',
                        'subject_literacy_title' => 'Academics Page: Subject Literacy Title',
                        'subject_literacy_text' => 'Academics Page: Subject Literacy Description',
                        'subject_numeracy_title' => 'Academics Page: Subject Numeracy Title',
                        'subject_numeracy_text' => 'Academics Page: Subject Numeracy Description',
                        'subject_stem_title' => 'Academics Page: Subject STEM Title',
                        'subject_stem_text' => 'Academics Page: Subject STEM Description',
                        'subject_character_title' => 'Academics Page: Subject Character Title',
                        'subject_character_text' => 'Academics Page: Subject Character Description',
                    ])
                    ->unique(FrontendContent::class, 'key', ignoreRecord: true)
                    ->columnSpanFull()
                    ->disabled(fn ($record) => $record !== null)
                    ->dehydrated()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (callable $set, $state) {
                        $groups = [
                            'hero_images'            => 'home.hero',
                            'hero_tagline'           => 'home.hero',
                            'hero_heading'           => 'home.hero',
                            'hero_heading_highlight' => 'home.hero',
                            'hero_description'       => 'home.hero',
                            'hero_cta_primary'       => 'home.hero',
                            'hero_cta_secondary'     => 'home.hero',
                            'about_intro_welcome'    => 'home.about',
                            'about_intro_heading'    => 'home.about',
                            'about_intro_text'       => 'home.about',
                            'about_intro_mission'    => 'home.about',
                            'about_intro_read_more'  => 'home.about',
                            'pillar_1_label'         => 'home.pillars',
                            'pillar_1_image'         => 'home.pillars',
                            'pillar_2_label'         => 'home.pillars',
                            'pillar_2_image'         => 'home.pillars',
                            'pillar_3_label'         => 'home.pillars',
                            'pillar_3_image'         => 'home.pillars',
                            'pillar_4_label'         => 'home.pillars',
                            'pillar_4_image'         => 'home.pillars',
                            'trust_1'                => 'home.trust',
                            'trust_2'                => 'home.trust',
                            'trust_3'                => 'home.trust',
                            'trust_4'                => 'home.trust',
                            'why_us_heading'         => 'home.features',
                            'why_us_subheading'      => 'home.features',
                            'feature_1_title'        => 'home.features',
                            'feature_1_description'  => 'home.features',
                            'feature_1_icon'         => 'home.features',
                            'feature_2_title'        => 'home.features',
                            'feature_2_description'  => 'home.features',
                            'feature_2_icon'         => 'home.features',
                            'feature_3_title'        => 'home.features',
                            'feature_3_description'  => 'home.features',
                            'feature_3_icon'         => 'home.features',
                            'feature_4_title'        => 'home.features',
                            'feature_4_description'  => 'home.features',
                            'feature_4_icon'         => 'home.features',
                            'bento_1_title'          => 'home.why',
                            'bento_1_description'    => 'home.why',
                            'bento_2_title'          => 'home.why',
                            'bento_2_description'    => 'home.why',
                            'bento_3_title'          => 'home.why',
                            'bento_3_description'    => 'home.why',
                            'stat_1_value'           => 'home.stats',
                            'stat_1_label'           => 'home.stats',
                            'stat_2_value'           => 'home.stats',
                            'stat_2_label'           => 'home.stats',
                            'stat_3_value'           => 'home.stats',
                            'stat_3_label'           => 'home.stats',
                            'stat_4_value'           => 'home.stats',
                            'stat_4_label'           => 'home.stats',
                            'news_heading'               => 'home.news',
                            'news_subheading'            => 'home.news',
                            'news_view_all_label'        => 'home.news',
                            'news_badge_label'           => 'home.news',
                            'news_read_more_label'       => 'home.news',
                            'news_empty_text'            => 'home.news',
                            'news_view_all_mobile_label' => 'home.news',
                            'leadership_heading'     => 'home.leadership',
                            'leadership_subheading'  => 'home.leadership',
                            'cta_heading'            => 'home.cta',
                            'cta_description'        => 'home.cta',
                            'cta_enrol'              => 'home.cta',
                            'cta_tour'               => 'home.cta',
                            'cta_whatsapp'           => 'home.cta',
                            'student_portal_url'     => 'portals',
                            'staff_portal_url'       => 'portals',
                            'common_entrance_url'    => 'portals',
                            'footer_social_facebook' => 'footer.social',
                            'footer_social_instagram'=> 'footer.social',
                            'footer_social_linkedin' => 'footer.social',
                            'footer_social_x'        => 'footer.social',
                            'about_hero_title'       => 'about',
                            'about_hero_subtitle'    => 'about',
                            'about_description'      => 'about',
                            'about_mission_title'    => 'about.mission',
                            'about_mission_text'     => 'about.mission',
                            'about_vision_title'     => 'about.vision',
                            'about_vision_text'      => 'about.vision',
                            'about_core_values_title'=> 'about.values',
                            'core_value_1'           => 'about.values',
                            'core_value_2'           => 'about.values',
                            'core_value_3'           => 'about.values',
                            'core_value_4'           => 'about.values',
                            'core_value_5'           => 'about.values',
                            'about_leadership_title'    => 'about.leadership',
                            'about_leadership_subtitle' => 'about.leadership',
                            'about_leadership_empty'    => 'about.leadership',
                            'advantage_hero_title'    => 'academics',
                            'advantage_hero_subtitle' => 'academics',
                            'advantage_intro'         => 'academics',
                            'learning_levels_title'   => 'academics.levels',
                            'learning_levels_subtitle'=> 'academics.levels',
                            'eyfs_title'              => 'academics.eyfs',
                            'eyfs_focus_label'        => 'academics.eyfs',
                            'eyfs_focus_text'         => 'academics.eyfs',
                            'eyfs_outcome_label'      => 'academics.eyfs',
                            'eyfs_outcome_text'       => 'academics.eyfs',
                            'primary_title'           => 'academics.primary',
                            'primary_focus_label'     => 'academics.primary',
                            'primary_focus_text'      => 'academics.primary',
                            'primary_outcome_label'   => 'academics.primary',
                            'primary_outcome_text'    => 'academics.primary',
                            'subjects_title'          => 'academics.subjects',
                            'subjects_subtitle'       => 'academics.subjects',
                            'subject_literacy_title'  => 'academics.subjects',
                            'subject_literacy_text'   => 'academics.subjects',
                            'subject_numeracy_title'  => 'academics.subjects',
                            'subject_numeracy_text'   => 'academics.subjects',
                            'subject_stem_title'      => 'academics.subjects',
                            'subject_stem_text'       => 'academics.subjects',
                            'subject_character_title' => 'academics.subjects',
                            'subject_character_text'  => 'academics.subjects',
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
                    ->columnSpanFull()
                    ->visible($isImage)
                    ->dehydrated(fn ($state, $component) => $component->isVisible()),

                Textarea::make('value_textarea')
                    ->label('Value (Long Text)')
                    ->rows(6)
                    ->columnSpanFull()
                    ->helperText('Supports HTML for rich text fields rendered with {!! !!}')
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
