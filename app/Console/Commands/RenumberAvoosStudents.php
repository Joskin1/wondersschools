<?php

namespace App\Console\Commands;

use App\Models\Classroom;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class RenumberAvoosStudents extends Command
{
    protected $signature = 'avoos:renumber-students 
                            {--tenant=avoos : Tenant identifier} 
                            {--password=Avoos2026# : Default password for students} 
                            {--dry-run : Preview changes without saving} 
                            {--export-dir= : Directory to save credentials}';

    protected $description = 'Renumber AVOOS students sequentially (highest class to least class) with 3-digit year format and export login credentials';

    public function handle(): int
    {
        $tenantId = (string) $this->option('tenant');
        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            $this->error("Tenant '{$tenantId}' not found.");
            return 1;
        }

        tenancy()->initialize($tenant);
        $this->info("Initialized tenant: {$tenant->id} ({$tenant->name})");

        $isDryRun = (bool) $this->option('dry-run');
        if ($isDryRun) {
            $this->warn('=== DRY-RUN MODE (No database records will be altered) ===');
        }

        $defaultPassword = (string) $this->option('password');
        $portalUrl = 'https://avoosschools.duckdns.org/student';
        $year = now()->format('Y');

        // Order classrooms from highest class to least class
        $classrooms = Classroom::orderByDesc('class_order')->get();
        if ($classrooms->isEmpty()) {
            $this->error('No classrooms found for tenant.');
            return 1;
        }

        $globalSeq = 1;
        $records = [];
        $totalProcessed = 0;

        $this->info("Found {$classrooms->count()} classrooms. Beginning sequential numbering from highest class to least class...\n");

        foreach ($classrooms as $classroom) {
            $enrollments = StudentEnrollment::where('classroom_id', $classroom->id)
                ->with(['student.user'])
                ->get();

            if ($enrollments->isEmpty()) {
                continue;
            }

            // Sort alphabetically by student name within the classroom
            $sortedEnrollments = $enrollments->sort(function ($a, $b) {
                $nameA = strtolower((string) ($a->student?->user?->name ?? $a->student?->full_name));
                $nameB = strtolower((string) ($b->student?->user?->name ?? $b->student?->full_name));
                return strcmp($nameA, $nameB);
            })->values();

            $this->line("<comment>=== {$classroom->name} (Order: {$classroom->class_order}) - {$sortedEnrollments->count()} Students ===</comment>");

            foreach ($sortedEnrollments as $enrollment) {
                $student = $enrollment->student;
                if (! $student) {
                    continue;
                }

                $studentName = trim((string) ($student->user?->name ?? $student->full_name));
                $oldAdm = (string) $student->admission_number;
                $newAdm = 'AVS' . $year . str_pad((string) $globalSeq, 3, '0', STR_PAD_LEFT);
                $loginEmail = strtolower($newAdm) . '@student.local';

                $this->line(sprintf(
                    "  #%02d | %-20s | %-32s | %-14s -> <info>%s</info>",
                    $globalSeq,
                    $classroom->name,
                    $studentName ?: 'Unnamed Student',
                    $oldAdm ?: 'None',
                    $newAdm
                ));

                if (! $isDryRun) {
                    // Update student record
                    $student->forceFill([
                        'admission_number' => $newAdm,
                        'status' => 'active',
                        'is_portal_active' => true,
                    ])->saveQuietly();

                    // Update or create linked User account
                    $user = $student->user ?: User::where('email', $loginEmail)->first() ?: new User;
                    $user->email = $loginEmail;
                    $user->password = Hash::make($defaultPassword);
                    $user->role = 'student';
                    $user->is_active = true;
                    if (! $user->email_verified_at) {
                        $user->email_verified_at = now();
                    }
                    if (! $user->registration_completed_at) {
                        $user->registration_completed_at = now();
                    }
                    $user->save();

                    if ($student->user_id !== $user->id) {
                        $student->forceFill(['user_id' => $user->id])->saveQuietly();
                    }
                }

                $records[] = [
                    'sn' => $globalSeq,
                    'class' => $classroom->name,
                    'name' => $studentName,
                    'admission_number' => $newAdm,
                    'email' => $loginEmail,
                    'password' => $defaultPassword,
                    'portal_url' => $portalUrl,
                ];

                $globalSeq++;
                $totalProcessed++;
            }
        }

        $this->newLine();
        $this->info("Completed numbering: {$totalProcessed} students processed.");

        // Export to Excel and CSV
        $exportDir = $this->option('export-dir')
            ? (string) $this->option('export-dir')
            : public_path('downloads/credentials');

        if (! file_exists($exportDir)) {
            mkdir($exportDir, 0755, true);
        }

        $xlsxFile = rtrim($exportDir, '/') . '/AVOOS_Students_Login_Credentials.xlsx';
        $csvFile = rtrim($exportDir, '/') . '/AVOOS_Students_Login_Credentials.csv';

        $this->generateSpreadsheets($records, $xlsxFile, $csvFile, $portalUrl);

        @chmod($xlsxFile, 0664);
        @chmod($csvFile, 0664);

        $this->info("Credentials Spreadsheet (.xlsx) saved to: {$xlsxFile}");
        $this->info("Credentials CSV (.csv) saved to: {$csvFile}");
        $this->newLine();
        $this->info("Public Download Links:");
        $this->info("Excel: https://avoosschools.duckdns.org/downloads/credentials/AVOOS_Students_Login_Credentials.xlsx");
        $this->info("CSV:   https://avoosschools.duckdns.org/downloads/credentials/AVOOS_Students_Login_Credentials.csv");

        return 0;
    }

    /**
     * @param array<int, array<string, mixed>> $records
     */
    protected function generateSpreadsheets(array $records, string $xlsxPath, string $csvPath, string $portalUrl): void
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Student Login Credentials');

        // Title Block
        $sheet->setCellValue('A1', "AVOOS INT'L SCHOOLS - STUDENT & PARENT LOGIN CREDENTIALS");
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('1E3A8A');
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', "Portal Login URL: {$portalUrl} | Generated: " . now()->format('d M Y, h:i A'));
        $sheet->mergeCells('A2:G2');
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(10)->getColor()->setRGB('4B5563');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Header Row
        $headers = [
            'A4' => 'S/N',
            'B4' => 'Class',
            'C4' => 'Student Full Name',
            'D4' => 'Admission Number (Username)',
            'E4' => 'Login Email',
            'F4' => 'Default Password',
            'G4' => 'Portal Login URL',
        ];

        foreach ($headers as $cell => $header) {
            $sheet->setCellValue($cell, $header);
        }

        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E3A8A'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '0F172A'],
                ],
            ],
        ];
        $sheet->getStyle('A4:G4')->applyFromArray($headerStyle);
        $sheet->getRowDimension(4)->setRowHeight(28);

        $row = 5;
        foreach ($records as $r) {
            $sheet->setCellValue("A{$row}", $r['sn']);
            $sheet->setCellValue("B{$row}", $r['class']);
            $sheet->setCellValue("C{$row}", $r['name']);
            $sheet->setCellValue("D{$row}", $r['admission_number']);
            $sheet->setCellValue("E{$row}", $r['email']);
            $sheet->setCellValue("F{$row}", $r['password']);
            $sheet->setCellValue("G{$row}", $r['portal_url']);

            $isEven = ($row % 2 === 0);
            $bgColor = $isEven ? 'F8FAFC' : 'FFFFFF';

            $rowStyle = [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $bgColor],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'E2E8F0'],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ];
            $sheet->getStyle("A{$row}:G{$row}")->applyFromArray($rowStyle);

            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("F{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->getRowDimension($row)->setRowHeight(20);
            $row++;
        }

        // Auto-fit column widths
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Save XLSX
        $writer = new Xlsx($spreadsheet);
        $writer->save($xlsxPath);

        // Save CSV
        $csvWriter = new Csv($spreadsheet);
        $csvWriter->setSheetIndex(0);
        $csvWriter->save($csvPath);
    }
}
