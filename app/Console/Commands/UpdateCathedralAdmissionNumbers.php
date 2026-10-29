<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Models\Tenant;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;

class UpdateCathedralAdmissionNumbers extends Command
{
    protected $signature = 'import:cathedral-admissions {path : Path to Excel file or directory} {--tenant=cathedral} {--dry-run}';
    protected $description = 'Import and update student admission numbers from filled class Excel rosters';

    public function handle(): int
    {
        $tenantId = $this->option('tenant');
        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            $this->error("Tenant '{$tenantId}' not found.");
            return 1;
        }

        tenancy()->initialize($tenant);
        $this->info("Initialized tenant: {$tenant->id} ({$tenant->name})");

        $path = $this->argument('path');
        if (!file_exists($path)) {
            $this->error("Path '{$path}' does not exist.");
            return 1;
        }

        $files = is_dir($path) ? glob(rtrim($path, '/') . '/*.xlsx') : [$path];
        if (empty($files)) {
            $this->error("No Excel files found at '{$path}'.");
            return 1;
        }

        $isDryRun = $this->option('dry-run');
        if ($isDryRun) {
            $this->warn("=== RUNNING IN DRY-RUN MODE (No database changes will be saved) ===");
        }

        $totalUpdated = 0;
        $totalSkipped = 0;

        foreach ($files as $file) {
            $this->line("<comment>Processing file: " . basename($file) . "</comment>");
            
            try {
                $spreadsheet = IOFactory::load($file);
            } catch (\Throwable $e) {
                $this->error("Could not read file {$file}: " . $e->getMessage());
                continue;
            }

            foreach ($spreadsheet->getAllSheets() as $sheet) {
                $sheetTitle = $sheet->getTitle();
                $highestRow = $sheet->getHighestDataRow();

                for ($row = 7; $row <= $highestRow; $row++) {
                    $studentName = trim((string)$sheet->getCell("B{$row}")->getValue());
                    $newAdm = trim((string)$sheet->getCell("C{$row}")->getValue());
                    $systemId = trim((string)$sheet->getCell("E{$row}")->getValue());

                    if (empty($studentName) && empty($systemId)) {
                        continue;
                    }

                    if (empty($newAdm)) {
                        $totalSkipped++;
                        continue;
                    }

                    $student = null;
                    if (!empty($systemId) && is_numeric($systemId)) {
                        $student = Student::find((int)$systemId);
                    }

                    if (!$student && !empty($studentName)) {
                        $student = Student::where('full_name', $studentName)
                            ->orWhereRaw('LOWER(full_name) = ?', [strtolower($studentName)])
                            ->first();
                    }

                    if (!$student) {
                        $this->warn("Row {$row} in [{$sheetTitle}]: Student '{$studentName}' (ID: {$systemId}) not found.");
                        continue;
                    }

                    $oldAdm = $student->admission_number;
                    if ($oldAdm === $newAdm) {
                        continue;
                    }

                    $this->info("Updating {$student->full_name}: '{$oldAdm}' => '{$newAdm}'");

                    if (!$isDryRun) {
                        $student->update(['admission_number' => $newAdm]);
                    }

                    $totalUpdated++;
                }
            }
        }

        $this->newLine();
        $this->info("Completed! Total students updated: {$totalUpdated}. Total left blank/skipped: {$totalSkipped}.");
        return 0;
    }
}
