Results System Step-by-Step Refactor Guide (Filament-Only Version)
Overview

This guide provides a 12-week implementation roadmap for refactoring the Results System from the current 8% complete state to a fully functional, secure, scalable, and PRD-compliant system using:

• Laravel 12
• Filament 5 (Multi-Panel: Admin / Teacher / Student)
• MySQL
• Redis (Cache + Queues)

Target: Senior Laravel Developer
Estimated Effort: 8–12 weeks (320–480 hours)

Rules:
• No traditional Laravel Controllers
• No API routes
• No public Blade views except printable result
• All UI actions via Filament Pages / Resources
• All logic in Services + Jobs

Phase 1: Database & Models (Weeks 1–2)
Week 1: Core Models & Migrations

Day 1–2: Create New Models

Tasks:
• Create ResultOption model + migration
• Create ResultComment model + migration
• Create ScoreHeader model + migration

Commands:

php artisan make:model ResultOption -m
php artisan make:model ResultComment -m
php artisan make:model ScoreHeader -m


Implementation:
• Copy model code from refactored_architecture.md
• Add relationships
• Add getTypedValue() method to ResultOption

Verification:

php artisan migrate
php artisan tinker
>>> ResultOption::create([...])->getTypedValue();


Day 3–4: Create Grading & Authority Models

Tasks:
• Create Grading model + migration
• Create SchoolAuthority model + migration

Commands:

php artisan make:model Grading -m
php artisan make:model SchoolAuthority -m


Implementation:
• Add Grading::getGradeForScore()
• Add relationships

Verification:

>>> Grading::getGradeForScore(85)->letter;


Day 5: Refactor Score Model

Tasks:
• Create migration to refactor scores table
• Update Score model fields
• Add scoreHeader() relationship

Implementation:

Schema::table('scores', function (Blueprint $table) {
    $table->dropColumn(['ca_score', 'exam_score', 'academic_session_id']);
    $table->foreignId('score_header_id')->constrained()->cascadeOnDelete();
    $table->string('session');
    $table->decimal('value', 5, 2);
    $table->unique(['student_id','subject_id','score_header_id','session','term']);
    $table->index(['session','term']);
});


⚠ Backup first.

Week 2: Result Model Refactor & Seeders

Day 1–2: Refactor Result Model

Tasks:
• Create migration for results table
• Add:

cache_key

session

term

settings_name

result_data (JSON)

position_in_class

overall_average

generated_at

Day 3–5: Seeders & Factories

Commands:

php artisan make:factory ResultOptionFactory
php artisan make:factory ResultCommentFactory
php artisan make:factory ScoreHeaderFactory
php artisan make:factory GradingFactory
php artisan make:factory SchoolAuthorityFactory
php artisan make:seeder ResultsSystemSeeder


Tasks:
• Seed 90+ result options
• Seed grading schemes
• Seed authorities
• Seed score headers

Phase 2: Services Layer (Weeks 3–5)
Week 3: ResultComputationService
php artisan make:service ResultComputationService


Implement:
• computeResults()
• computeStudentResult()
• computeCellValue()
• Column types:

scoreHeader

termTotalScoreObtained

averageOfDisplayedScores

currentTermPercentageScore

averagePercentageScoreForAllTerms

grade

subjectPosition

• injectPositions() with tie logic

Week 4: ResultCacheService & ResultSettingsService
php artisan make:service ResultCacheService
php artisan make:service ResultSettingsService


• Redis cache
• Navigation: FIRST / PREV / NEXT / LAST
• TTL: 24 hours

Week 5: ScoreImportService & Excel
composer require maatwebsite/excel
php artisan make:service ScoreImportService
php artisan make:export ScoreTemplateExport
php artisan make:import ScoresImport
php artisan make:export ScoresExport


• Generate Excel template
• Import scores
• Export scores

Phase 3: Jobs & Policies (Weeks 6–7)
Week 6: Jobs
php artisan make:job GenerateResultJob
php artisan make:job ImportScoresJob


• Queue: results, imports
• Timeout: 300s

Week 7: Policies
php artisan make:policy ResultPolicy --model=Result
php artisan make:policy ScorePolicy --model=Score
php artisan make:policy ResultSettingsPolicy


• Admin: all
• Teacher: assigned classes
• Student: own data

Phase 4: Filament Panels & Pages (Weeks 8–10)
Week 8: Admin Panel
php artisan make:filament-page GenerateResultsPage --panel=admin
php artisan make:filament-page ResultSettingsPage --panel=admin
php artisan make:filament-page GradingSchemePage --panel=admin
php artisan make:filament-page AuthoritiesPage --panel=admin

Week 9: Teacher Panel
php artisan make:filament-page ScoreEntryPage --panel=teacher
php artisan make:filament-page ScoreImportPage --panel=teacher
php artisan make:filament-page ClassResultsPreviewPage --panel=teacher

Week 10: Student Panel
php artisan make:filament-page MyResultsPage --panel=student
php artisan make:filament-page ResultPrintPage --panel=student

Phase 5: Testing & Optimization (Weeks 11–12)
Week 11: Tests
php artisan make:test ResultComputationServiceTest --unit
php artisan make:test ResultCacheServiceTest --unit
php artisan make:test FilamentResultsFlowTest

Week 12: Performance & Deployment

• Add DB indexes
• Enable Redis
• Run queues
• Optimize queries
• Deploy

Final Success Criteria

✔ All logic in Services / Jobs
✔ All UI in Filament Panels
✔ No Controllers
✔ MySQL optimized
✔ Redis cache
✔ Policies enforced
✔ Scales to 200+ users
✔ Secure & maintainable