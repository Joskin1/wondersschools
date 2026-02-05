Results System Step-by-Step Refactor Guide
Overview
This guide provides a 12-week implementation roadmap for refactoring the Results System from the current 8% complete state to a fully functional, secure, and scalable system per PRD requirements.

Target: Senior Laravel Developer
Estimated Effort: 8-12 weeks (320-480 hours)
Prerequisites: Laravel 12, Filament 5, MySQL/PostgreSQL, Redis

Phase 1: Database & Models (Weeks 1-2)
Week 1: Core Models & Migrations
Day 1-2: Create New Models
Tasks:

Create ResultOption model and migration
Create ResultComment model and migration
Create ScoreHeader model and migration
Commands:

php artisan make:model ResultOption -m
php artisan make:model ResultComment -m
php artisan make:model ScoreHeader -m
Implementation:

Copy model code from 
refactored_architecture.md
Copy migration code from architecture document
Add relationships
Add validation methods
Verification:

php artisan migrate
php artisan tinker
>>> ResultOption::create(['name' => 'test', 'value' => 'bool::true', 'settings_name' => 'Default', 'javascript_type' => 'boolean'])
>>> ResultOption::first()->getTypedValue() // Should return true
Day 3-4: Create Grading & Authority Models
Tasks:

Create Grading model and migration
Create SchoolAuthority model and migration
Commands:

php artisan make:model Grading -m
php artisan make:model SchoolAuthority -m
Implementation:

Copy model code from architecture document
Add getGradeForScore() static method to Grading
Add relationships to SchoolAuthority
Verification:

php artisan migrate
php artisan tinker
>>> Grading::create(['letter' => 'A', 'lower_bound' => 70, 'upper_bound' => 100, 'remark' => 'Excellent'])
>>> Grading::getGradeForScore(85) // Should return Grading instance with letter 'A'
Day 5: Refactor Score Model
Tasks:

Create migration to refactor 
scores
 table
Update 
Score
 model with new fields
Add scoreHeader relationship
Commands:

php artisan make:migration refactor_scores_table
Implementation:

// Migration
public function up(): void
{
    Schema::table('scores', function (Blueprint $table) {
        $table->dropColumn(['ca_score', 'exam_score', 'academic_session_id']);
        $table->foreignId('score_header_id')->after('subject_id')->constrained()->cascadeOnDelete();
        $table->string('session')->after('score_header_id');
        $table->decimal('value', 5, 2)->after('term');
        $table->unique(['student_id', 'subject_id', 'score_header_id', 'session', 'term'], 'unique_score');
        $table->index(['session', 'term']);
    });
}
⚠️ WARNING: This is a destructive migration. Backup existing scores first!

Verification:

# Backup first!
php artisan db:backup
# Run migration
php artisan migrate
# Verify structure
php artisan tinker
>>> Schema::hasColumn('scores', 'score_header_id') // Should return true
>>> Schema::hasColumn('scores', 'ca_score') // Should return false
Week 2: Result Model Refactor & Seeders
Day 1-2: Refactor Result Model
Tasks:

Create migration to refactor results table
Update 
Result
 model with new fields
Add result_data JSON field
Commands:

php artisan make:migration refactor_results_table
Implementation:

// Migration
public function up(): void
{
    Schema::table('results', function (Blueprint $table) {
        $table->dropColumn(['academic_session_id', 'term_id', 'grade', 'teacher_remark', 'principal_remark']);
        $table->string('cache_key')->after('id');
        $table->string('session')->after('student_id');
        $table->integer('term')->after('session');
        $table->string('settings_name')->after('classroom_id');
        $table->json('result_data')->after('settings_name');
        $table->integer('position_in_class')->nullable()->after('position');
        $table->decimal('overall_average', 5, 2)->nullable()->after('average_score');
        $table->timestamp('generated_at')->nullable()->after('overall_average');
        $table->index('cache_key');
        $table->index(['session', 'term', 'classroom_id']);
    });
}
Verification:

php artisan migrate
php artisan tinker
>>> Schema::hasColumn('results', 'cache_key') // Should return true
>>> Schema::hasColumn('results', 'result_data') // Should return true
Day 3-5: Create Seeders & Factories
Tasks:

Create factories for all new models
Create seeders with realistic data
Seed development database
Commands:

php artisan make:factory ResultOptionFactory
php artisan make:factory ResultCommentFactory
php artisan make:factory ScoreHeaderFactory
php artisan make:factory GradingFactory
php artisan make:factory SchoolAuthorityFactory
php artisan make:seeder ResultsSystemSeeder
Implementation:

Create factories with realistic data
Seed 90+ result options (from PRD)
Seed grading schemes (A-F)
Seed school authorities (Principal, VP)
Seed score headers for test classes
Verification:

php artisan db:seed --class=ResultsSystemSeeder
php artisan tinker
>>> ResultOption::count() // Should be >= 90
>>> Grading::count() // Should be >= 5
>>> SchoolAuthority::count() // Should be >= 2
Phase 2: Services Layer (Weeks 3-5)
Week 3: ResultComputationService
Day 1-3: Core Computation Logic
Tasks:

Create ResultComputationService
Implement computeResults() method
Implement computeStudentResult() method
Implement computeCellValue() method
Commands:

php artisan make:service ResultComputationService
Implementation:

Implement cell value computation for all column types:
scoreHeader
termTotalScoreObtained
averageOfDisplayedScores
currentTermPercentageScore
averagePercentageScoreForAllTerms
grade
subjectPosition
Verification:

php artisan tinker
>>> $service = app(\App\Services\ResultComputationService::class);
>>> $results = $service->computeResults('2023/2024', 1, 1, 'Default');
>>> count($results) // Should match number of students in class
>>> $results[0]['cells'] // Should contain computed cells
Day 4-5: Position Calculation
Tasks:

Implement injectPositions() method
Implement tie-handling logic
Implement class arm, class order, class group positions
Implement subject-specific positions
Implementation:

Sort students by overall average (descending)
Assign positions with tie handling
Calculate subject-specific positions
Verification:

php artisan tinker
>>> $service = app(\App\Services\ResultComputationService::class);
>>> $results = $service->computeResults('2023/2024', 1, 1, 'Default');
>>> $results[0]['position_in_class'] // Should be 1 for top student
>>> $results[1]['position_in_class'] // Should be 2 or same as [0] if tied
Week 4: ResultCacheService & ResultSettingsService
Day 1-2: ResultCacheService
Tasks:

Create ResultCacheService
Implement cacheResults() method
Implement getResult() method with navigation
Implement clearCache() method
Commands:

php artisan make:service ResultCacheService
Implementation:

Use Redis for caching
Implement FIRST, PREVIOUS, NEXT, LAST navigation
Set TTL to 24 hours
Verification:

php artisan tinker
>>> $service = app(\App\Services\ResultCacheService::class);
>>> $cacheKey = $service->generateCacheKey('2023/2024', 1, 1);
>>> $service->cacheResults($cacheKey, $results);
>>> $result = $service->getResult($cacheKey, 0, 'FIRST');
>>> $result['index'] // Should be 0
>>> $result = $service->getResult($cacheKey, 0, 'NEXT');
>>> $result['index'] // Should be 1
Day 3-5: ResultSettingsService
Tasks:

Create ResultSettingsService
Implement getSettings() method
Implement saveSettings() method
Implement getTypedValue() method
Commands:

php artisan make:service ResultSettingsService
Implementation:

Load settings by settings_name
Convert values to typed values (boolean, number, string, array)
Validate settings structure
Verification:

php artisan tinker
>>> $service = app(\App\Services\ResultSettingsService::class);
>>> $settings = $service->getSettings('Default');
>>> $settings->count() // Should be >= 90
>>> $service->getTypedValue('show_cumulative_score_column', 'Default') // Should return boolean
Week 5: ScoreImportService & Excel Classes
Day 1-3: ScoreImportService
Tasks:

Create ScoreImportService
Implement generateTemplate() method
Implement importScores() method
Implement exportScores() method
Commands:

php artisan make:service ScoreImportService
composer require maatwebsite/excel
Implementation:

Generate Excel template with students and score headers
Import scores from Excel with validation
Export existing scores to Excel
Verification:

php artisan tinker
>>> $service = app(\App\Services\ScoreImportService::class);
>>> $path = $service->generateTemplate('2023/2024', 1, 1);
>>> file_exists($path) // Should return true
Day 4-5: Excel Export/Import Classes
Tasks:

Create ScoreTemplateExport class
Create ScoresImport class
Create ScoresExport class
Commands:

php artisan make:export ScoreTemplateExport
php artisan make:import ScoresImport
php artisan make:export ScoresExport
Implementation:

Implement FromCollection and WithHeadings for exports
Implement ToModel and WithValidation for imports
Add error handling and validation
Verification:

# Manual test: Download template, fill scores, import
# Verify scores are imported correctly
Phase 3: Controllers, Jobs & Policies (Weeks 6-7)
Week 6: Controllers & Form Requests
Day 1-2: ResultGenerationController
Tasks:

Create ResultGenerationController
Create GenerateResultRequest
Implement generate() method
Implement show() method
Commands:

php artisan make:controller ResultGenerationController
php artisan make:request GenerateResultRequest
Implementation:

Dispatch GenerateResultJob on generate
Return cache key to frontend
Implement navigation endpoint
Verification:

# Use Postman or curl
curl -X POST http://localhost/results/generate \
  -H "Content-Type: application/json" \
  -d '{"session":"2023/2024","term":1,"classroom_id":1,"settings_name":"Default"}'
# Should return cache_key
Day 3-5: Other Controllers
Tasks:

Create ResultSettingsController
Create ScoreImportController
Create ScoreManagementController
Create all Form Requests
Commands:

php artisan make:controller ResultSettingsController
php artisan make:controller ScoreImportController
php artisan make:controller ScoreManagementController
php artisan make:request ImportScoresRequest
php artisan make:request SaveScoresRequest
php artisan make:request SaveResultSettingsRequest
Verification:

Test each endpoint with Postman
Verify validation works
Verify authorization works
Week 7: Jobs & Policies
Day 1-2: Jobs
Tasks:

Create GenerateResultJob
Create ImportScoresJob
Configure queue workers
Commands:

php artisan make:job GenerateResultJob
php artisan make:job ImportScoresJob
Implementation:

Implement job logic
Set queue name: results and imports
Set timeout: 300 seconds
Verification:

# Start queue worker
php artisan queue:work --queue=results,imports,default
# Dispatch job
php artisan tinker
>>> GenerateResultJob::dispatch('2023/2024', 1, 1, 'Default');
# Check job status
php artisan queue:failed
Day 3-5: Policies
Tasks:

Create ResultPolicy
Create ScorePolicy
Create ResultSettingsPolicy
Register policies in AuthServiceProvider
Commands:

php artisan make:policy ResultPolicy --model=Result
php artisan make:policy ScorePolicy --model=Score
php artisan make:policy ResultSettingsPolicy
Implementation:

Implement authorization methods
Test with different user roles (admin, teacher, student)
Verification:

php artisan tinker
>>> $user = User::find(1); // Teacher
>>> $result = Result::find(1);
>>> $user->can('view', $result) // Should return true/false based on policy
Phase 4: UI Layer (Weeks 8-10)
Week 8: Livewire Components
Day 1-3: ResultViewer Component
Tasks:

Create ResultViewer Livewire component
Implement filters (session, term, class, settings)
Implement generate button
Implement navigation (FIRST, PREVIOUS, NEXT, LAST)
Commands:

php artisan make:livewire ResultViewer
Implementation:

Add filter dropdowns
Add generate button with loading state
Add navigation buttons
Add print button
Verification:

Visit /results in browser
Select filters and click generate
Navigate between students
Verify print works
Day 4-5: ScoreManager Component
Tasks:

Create ScoreManager Livewire component
Implement spreadsheet-like grid
Implement inline editing
Implement bulk save
Commands:

php artisan make:livewire ScoreManager
Implementation:

Create grid with students × score headers
Add inline editing with validation
Add bulk save button
Add Excel import/export buttons
Verification:

Visit /scores/manage in browser
Edit scores inline
Click save and verify scores are saved
Download Excel template and import
Week 9: Filament Resources
Day 1-3: ResultViewerResource
Tasks:

Create ResultViewerResource
Create ViewResults page
Integrate ResultViewer Livewire component
Commands:

php artisan make:filament-resource ResultViewer --generate --view
Implementation:

Create resource with view-only page
Integrate Livewire component
Add to navigation
Verification:

Visit Filament admin panel
Navigate to Results
Verify component works
Day 4-5: Other Filament Resources
Tasks:

Create ResultSettingsResource
Create ScoreManagementResource
Remove old 
ResultResource
 (CRUD)
Commands:

php artisan make:filament-resource ResultSettings --generate
php artisan make:filament-resource ScoreManagement --generate
Verification:

Verify all resources work in Filament
Verify old ResultResource is removed
Week 10: Blade Views & Print Functionality
Day 1-3: Printable Result Component
Tasks:

Create result-header.blade.php
Create result-body.blade.php
Create result-footer.blade.php
Create print.blade.php
Implementation:

Design printable layout
Add CSS for print media
Add authority signatures and comments
Add student passport photo
Verification:

Print a result
Verify layout matches PRD
Verify all data is displayed correctly
Day 4-5: Polish & Styling
Tasks:

Add Tailwind CSS styling
Add loading states
Add error handling
Add success messages
Verification:

Test all UI flows
Verify responsive design
Verify accessibility (WCAG 2.1)
Phase 5: Testing & Optimization (Weeks 11-12)
Week 11: Unit & Feature Tests
Day 1-3: Unit Tests
Tasks:

Write tests for ResultComputationService
Write tests for ResultCacheService
Write tests for ResultSettingsService
Write tests for ScoreImportService
Commands:

php artisan make:test Services/ResultComputationServiceTest --unit
php artisan make:test Services/ResultCacheServiceTest --unit
php artisan make:test Services/ResultSettingsServiceTest --unit
php artisan make:test Services/ScoreImportServiceTest --unit
Target: 80%+ code coverage

Verification:

php artisan test --coverage
Day 4-5: Feature Tests
Tasks:

Write tests for result generation workflow
Write tests for score import/export
Write tests for authorization policies
Commands:

php artisan make:test ResultGenerationTest
php artisan make:test ScoreImportTest
php artisan make:test ResultAuthorizationTest
Verification:

php artisan test --filter=ResultGenerationTest
php artisan test --filter=ScoreImportTest
php artisan test --filter=ResultAuthorizationTest
Week 12: Performance Optimization & Deployment
Day 1-2: Performance Optimization
Tasks:

Add database indexes
Optimize queries with eager loading
Implement caching
Configure queue workers
Verification:

# Run performance tests
php artisan test --filter=PerformanceTest
# Measure result generation time
php artisan tinker
>>> $start = microtime(true);
>>> GenerateResultJob::dispatchSync('2023/2024', 1, 1, 'Default');
>>> $end = microtime(true);
>>> echo ($end - $start) . " seconds"; // Should be < 60s for 50 students
Day 3-4: Security Audit
Tasks:

Review all authorization policies
Review all input validation
Review all SQL queries for injection
Review all file uploads for security
Verification:

Use checklist from 
security_scalability_checklist.md
Day 5: Deployment
Tasks:

Deploy to staging
Run smoke tests
Deploy to production
Monitor for errors
Commands:

# Staging
git push staging main
ssh staging "cd /var/www/wonders && php artisan migrate --force"
ssh staging "cd /var/www/wonders && php artisan queue:restart"
# Production
git push production main
ssh production "cd /var/www/wonders && php artisan migrate --force"
ssh production "cd /var/www/wonders && php artisan queue:restart"
Verification:

Verify all features work on staging
Verify all features work on production
Monitor error logs for 24 hours
Risk Mitigation
High-Risk Areas
Data Migration (Score & Result tables)

Risk: Data loss during migration
Mitigation: Backup database before migration, test on staging first
Rollback: Restore from backup
Performance (Result generation for 50+ students)

Risk: Timeout or memory issues
Mitigation: Use background jobs, optimize queries, add caching
Rollback: Reduce class size, increase timeout
Authorization (Teachers accessing wrong classes)

Risk: Data breach
Mitigation: Comprehensive policy tests, manual testing
Rollback: Disable feature, fix policies
Rollback Plan
If Migration Fails
# Restore from backup
mysql -u root -p wonders < backup_YYYYMMDD.sql
# Rollback migrations
php artisan migrate:rollback --step=7
If Deployment Fails
# Revert to previous release
cd /var/www/wonders
ln -sfn releases/PREVIOUS_RELEASE current
php artisan queue:restart
Success Criteria
 All 50 files created per code structure proposal
 All tests passing (80%+ coverage)
 Result generation < 60s for 50 students
 No N+1 queries (verified with Telescope)
 All security checklist items completed
 All scalability checklist items completed
 User acceptance testing passed
 Production deployment successful
Conclusion
This guide provides a detailed, week-by-week roadmap for refactoring the Results System. Follow each phase sequentially, verify each step, and use the checklists to ensure quality.

Estimated Timeline: 12 weeks
Estimated Effort: 320-480 hours
Recommended Team: 1 senior Laravel developer

Next Steps: Begin with Phase 1, Week 1, Day 1 - Create ResultOption model and migration.