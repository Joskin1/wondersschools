<?php

namespace App\Services;

use App\Models\Classroom;
use App\Models\Session;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Term;
use App\Models\TermResult;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ClassTraitsSpreadsheetService
{
    /**
     * Get active Affective Domain traits.
     *
     * @return array<string, string>
     */
    public static function getAffectiveTraits(): array
    {
        return ReportCardSettingsService::getAffectiveTraits();
    }

    /**
     * Get active Psychomotor Skills.
     *
     * @return array<string, string>
     */
    public static function getPsychomotorSkills(): array
    {
        return ReportCardSettingsService::getPsychomotorSkills();
    }

    /**
     * Generate an Excel spreadsheet template for class traits ratings.
     */
    public function generateTemplate(int $classroomId, int $sessionId, int $termId): Spreadsheet
    {
        $classroom = Classroom::findOrFail($classroomId);
        $session   = Session::findOrFail($sessionId);
        $term      = Term::findOrFail($termId);

        $enrollments = StudentEnrollment::where('classroom_id', $classroomId)
            ->where('session_id', $sessionId)
            ->with(['student.user', 'department', 'classArm'])
            ->get()
            ->sortBy(fn ($e) => $e->student?->name ?? '');

        $termResults = TermResult::where('classroom_id', $classroomId)
            ->where('session_id', $sessionId)
            ->where('term_id', $termId)
            ->get()
            ->keyBy('student_id');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr(preg_replace('/[^A-Za-z0-9_\-]/', '_', $classroom->name), 0, 30));

        $affTraits = static::getAffectiveTraits();
        $psySkills = static::getPsychomotorSkills();

        $colIdx = 5;
        $affCols = [];
        foreach ($affTraits as $k => $label) {
            $letter = Coordinate::stringFromColumnIndex($colIdx);
            $affCols[$letter] = ['key' => $k, 'title' => $label];
            $colIdx++;
        }
        $affStartCol = ! empty($affCols) ? array_key_first($affCols) : 'E';
        $affEndCol   = ! empty($affCols) ? array_key_last($affCols) : 'E';

        $psyCols = [];
        foreach ($psySkills as $k => $label) {
            $letter = Coordinate::stringFromColumnIndex($colIdx);
            $psyCols[$letter] = ['key' => $k, 'title' => $label];
            $colIdx++;
        }
        $psyStartCol = ! empty($psyCols) ? array_key_first($psyCols) : Coordinate::stringFromColumnIndex(count($affCols) + 5);
        $psyEndCol   = ! empty($psyCols) ? array_key_last($psyCols) : $psyStartCol;

        $lastDataCol = ! empty($psyCols) ? $psyEndCol : $affEndCol;

        // Hidden ID column
        $idCol = Coordinate::stringFromColumnIndex($colIdx);

        // ── Title Banner (Row 1) ─────────────────────────────────────────────
        $sheet->mergeCells("A1:{$lastDataCol}1");
        $sheet->setCellValue('A1', "BEHAVIORAL TRAITS & SKILLS RATING — {$classroom->name} ({$session->name} / {$term->name})");
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F172A']], // Slate 900
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(32);

        // ── Subtitle / Instructions (Row 2) ──────────────────────────────────
        $sheet->mergeCells("A2:{$lastDataCol}2");
        $sheet->setCellValue('A2', 'Instructions: Enter ratings from 1 to 5 for each trait (5 = Excellent, 4 = Very Good, 3 = Good, 2 = Fair, 1 = Poor). Do not modify Admission No or Student ID.');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '475569']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(20);

        // ── Group Headers (Row 3) ────────────────────────────────────────────
        $sheet->mergeCells('A3:D3');
        $sheet->setCellValue('A3', 'STUDENT INFORMATION');
        $sheet->getStyle('A3:D3')->applyFromArray([
            'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '334155']], // Slate 700
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        if (! empty($affCols)) {
            $sheet->mergeCells("{$affStartCol}3:{$affEndCol}3");
            $sheet->setCellValue("{$affStartCol}3", 'AFFECTIVE DOMAIN (CHARACTER & CONDUCT) [1 - 5]');
            $sheet->getStyle("{$affStartCol}3:{$affEndCol}3")->applyFromArray([
                'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A8A']], // Navy Blue
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
        }

        if (! empty($psyCols)) {
            $sheet->mergeCells("{$psyStartCol}3:{$psyEndCol}3");
            $sheet->setCellValue("{$psyStartCol}3", 'PSYCHOMOTOR SKILLS & ACTIVITIES [1 - 5]');
            $sheet->getStyle("{$psyStartCol}3:{$psyEndCol}3")->applyFromArray([
                'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '047857']], // Emerald 700
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
        }
        $sheet->getRowDimension(3)->setRowHeight(22);

        // ── Table Column Headers (Row 4) ─────────────────────────────────────
        $headers = [
            'A' => ['title' => 'S/N', 'width' => 6],
            'B' => ['title' => 'Admission No', 'width' => 16],
            'C' => ['title' => 'Student Full Name', 'width' => 28],
            'D' => ['title' => 'Division / Arm', 'width' => 16],
        ];

        foreach ($affCols as $col => $info) {
            $headers[$col] = ['title' => "{$info['title']}\n(1-5)", 'width' => 14];
        }

        foreach ($psyCols as $col => $info) {
            $headers[$col] = ['title' => "{$info['title']}\n(1-5)", 'width' => 14];
        }

        $headers[$idCol] = ['title' => 'Student ID', 'width' => 12];

        foreach ($headers as $col => $cfg) {
            $sheet->setCellValue("{$col}4", $cfg['title']);
            $sheet->getColumnDimension($col)->setWidth($cfg['width']);
        }

        $sheet->getStyle('A4:D4')->applyFromArray([
            'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '475569']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);

        if (! empty($affCols)) {
            $sheet->getStyle("{$affStartCol}4:{$affEndCol}4")->applyFromArray([
                'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']], // Blue 600
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            ]);
        }

        if (! empty($psyCols)) {
            $sheet->getStyle("{$psyStartCol}4:{$psyEndCol}4")->applyFromArray([
                'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '059669']], // Emerald 600
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            ]);
        }

        $sheet->getStyle("{$idCol}4")->applyFromArray([
            'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => '94A3B8']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
        ]);
        $sheet->getColumnDimension($idCol)->setVisible(false);

        $sheet->getRowDimension(4)->setRowHeight(30);

        // Validation rule for 1-5
        $validation = new DataValidation();
        $validation->setType(DataValidation::TYPE_WHOLE);
        $validation->setErrorStyle(DataValidation::STYLE_STOP);
        $validation->setAllowBlank(true);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setErrorTitle('Invalid Rating');
        $validation->setError('Rating must be a whole number between 1 and 5 (1=Poor, 5=Excellent).');
        $validation->setPromptTitle('Rating (1 - 5)');
        $validation->setPrompt('5 = Excellent, 4 = Very Good, 3 = Good, 2 = Fair, 1 = Poor');
        $validation->setOperator(DataValidation::OPERATOR_BETWEEN);
        $validation->setFormula1('1');
        $validation->setFormula2('5');

        // ── Data Rows (Row 5+) ───────────────────────────────────────────────
        $rowIndex = 5;
        $sn = 1;

        foreach ($enrollments as $enrollment) {
            $student = $enrollment->student;
            if (! $student) {
                continue;
            }

            $termResult = $termResults->get($student->id);
            $division = $enrollment->department?->name ?? ($enrollment->classArm?->name ?? '—');
            $aff = is_array($termResult?->affective_domain) ? $termResult->affective_domain : [];
            $psy = is_array($termResult?->psychomotor_skills) ? $termResult->psychomotor_skills : [];

            $sheet->setCellValue("A{$rowIndex}", $sn);
            $sheet->setCellValueExplicit("B{$rowIndex}", $student->admission_number ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue("C{$rowIndex}", $student->name);
            $sheet->setCellValue("D{$rowIndex}", $division);

            // Affective traits
            foreach ($affCols as $col => $info) {
                $sheet->setCellValue("{$col}{$rowIndex}", $aff[$info['key']] ?? '');
            }

            // Psychomotor skills
            foreach ($psyCols as $col => $info) {
                $sheet->setCellValue("{$col}{$rowIndex}", $psy[$info['key']] ?? '');
            }

            // Hidden ID
            $sheet->setCellValue("{$idCol}{$rowIndex}", $student->id);

            // Apply data validation to trait columns
            foreach (array_merge(array_keys($affCols), array_keys($psyCols)) as $colLetter) {
                $sheet->getCell("{$colLetter}{$rowIndex}")->setDataValidation(clone $validation);
            }

            // Cell styling
            $isEven = ($rowIndex % 2 === 0);
            $bgColor = $isEven ? 'F8FAFC' : 'FFFFFF';

            $sheet->getStyle("A{$rowIndex}:D{$rowIndex}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bgColor]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getStyle("A{$rowIndex}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$rowIndex}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$rowIndex}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Soft highlight for editable cells
            if (! empty($affCols)) {
                $sheet->getStyle("{$affStartCol}{$rowIndex}:{$affEndCol}{$rowIndex}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $isEven ? 'EFF6FF' : 'F8FAFC']], // Light blue
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
            }

            if (! empty($psyCols)) {
                $sheet->getStyle("{$psyStartCol}{$rowIndex}:{$psyEndCol}{$rowIndex}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $isEven ? 'ECFDF5' : 'F8FAFC']], // Light green
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
            }

            $sheet->getRowDimension($rowIndex)->setRowHeight(22);
            $rowIndex++;
            $sn++;
        }

        $lastRow = $rowIndex - 1;
        if ($lastRow >= 5) {
            $sheet->getStyle("A3:{$lastDataCol}{$lastRow}")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CBD5E1'],
                    ],
                ],
            ]);
        }

        return $spreadsheet;
    }

    /**
     * Export template to temporary file on disk.
     */
    public function exportToTempFile(int $classroomId, int $sessionId, int $termId): string
    {
        $spreadsheet = $this->generateTemplate($classroomId, $sessionId, $termId);
        $tempPath = tempnam(sys_get_temp_dir(), 'class_traits_') . '.xlsx';

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return $tempPath;
    }

    /**
     * Import and apply traits ratings from uploaded spreadsheet.
     *
     * @return array{updated: int, errors: array<string>}
     */
    public function importSpreadsheet(string $filePath, int $classroomId, int $sessionId, int $termId): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();

        $enrolledStudents = StudentEnrollment::where('classroom_id', $classroomId)
            ->where('session_id', $sessionId)
            ->pluck('student_id')
            ->toArray();

        $enrolledByAdmNo = Student::whereIn('id', $enrolledStudents)
            ->pluck('id', 'admission_number')
            ->toArray();

        $affTraits = static::getAffectiveTraits();
        $psySkills = static::getPsychomotorSkills();

        $colIdx = 5;
        $affCols = [];
        foreach ($affTraits as $k => $label) {
            $letter = Coordinate::stringFromColumnIndex($colIdx);
            $affCols[$letter] = $k;
            $colIdx++;
        }

        $psyCols = [];
        foreach ($psySkills as $k => $label) {
            $letter = Coordinate::stringFromColumnIndex($colIdx);
            $psyCols[$letter] = $k;
            $colIdx++;
        }

        $idColLetter = Coordinate::stringFromColumnIndex($colIdx);

        $updatedCount = 0;
        $errors = [];

        DB::transaction(function () use (
            $sheet,
            $highestRow,
            $enrolledStudents,
            $enrolledByAdmNo,
            $classroomId,
            $sessionId,
            $termId,
            $affCols,
            $psyCols,
            $idColLetter,
            &$updatedCount,
            &$errors
        ) {
            for ($row = 5; $row <= $highestRow; $row++) {
                $rawColId = trim((string) $sheet->getCell("{$idColLetter}{$row}")->getValue());
                if ($rawColId === '') {
                    $rawColId = trim((string) $sheet->getCell("Q{$row}")->getValue());
                }
                $rawAdmNo = trim((string) $sheet->getCell("B{$row}")->getValue());
                $name     = trim((string) $sheet->getCell("C{$row}")->getValue());

                if ($rawColId === '' && $rawAdmNo === '' && $name === '') {
                    continue;
                }

                $studentId = null;
                if ($rawColId !== '' && is_numeric($rawColId)) {
                    $candidateId = (int) $rawColId;
                    if (in_array($candidateId, $enrolledStudents)) {
                        $studentId = $candidateId;
                    }
                }

                if (! $studentId && $rawAdmNo !== '' && isset($enrolledByAdmNo[$rawAdmNo])) {
                    $studentId = $enrolledByAdmNo[$rawAdmNo];
                }

                if (! $studentId) {
                    $errors[] = "Row {$row}: Student ({$name} / {$rawAdmNo}) is not enrolled in this classroom.";
                    continue;
                }

                // Parse Affective traits
                $affectiveData = [];
                $rowHasError = false;

                foreach ($affCols as $col => $key) {
                    $val = trim((string) $sheet->getCell("{$col}{$row}")->getValue());
                    if ($val === '') {
                        continue;
                    }
                    if (! is_numeric($val) || (int) $val < 1 || (int) $val > 5) {
                        $errors[] = "Row {$row} ({$name}): '{$key}' must be between 1 and 5 (got '{$val}').";
                        $rowHasError = true;
                        break;
                    }
                    $affectiveData[$key] = (int) $val;
                }

                if ($rowHasError) {
                    continue;
                }

                // Parse Psychomotor skills
                $psychomotorData = [];
                foreach ($psyCols as $col => $key) {
                    $val = trim((string) $sheet->getCell("{$col}{$row}")->getValue());
                    if ($val === '') {
                        continue;
                    }
                    if (! is_numeric($val) || (int) $val < 1 || (int) $val > 5) {
                        $errors[] = "Row {$row} ({$name}): '{$key}' must be between 1 and 5 (got '{$val}').";
                        $rowHasError = true;
                        break;
                    }
                    $psychomotorData[$key] = (int) $val;
                }

                if ($rowHasError) {
                    continue;
                }

                // Update or create TermResult
                $termResult = TermResult::where('student_id', $studentId)
                    ->where('session_id', $sessionId)
                    ->where('term_id', $termId)
                    ->first();

                if (! $termResult) {
                    $termResult = new TermResult([
                        'student_id'   => $studentId,
                        'classroom_id' => $classroomId,
                        'session_id'   => $sessionId,
                        'term_id'      => $termId,
                        'is_finalized' => false,
                    ]);
                }

                if (! empty($affectiveData)) {
                    $termResult->affective_domain = array_merge(
                        is_array($termResult->affective_domain) ? $termResult->affective_domain : [],
                        $affectiveData
                    );
                }

                if (! empty($psychomotorData)) {
                    $termResult->psychomotor_skills = array_merge(
                        is_array($termResult->psychomotor_skills) ? $termResult->psychomotor_skills : [],
                        $psychomotorData
                    );
                }

                $termResult->save();
                $updatedCount++;
            }
        });

        return [
            'updated' => $updatedCount,
            'errors'  => $errors,
        ];
    }
}
