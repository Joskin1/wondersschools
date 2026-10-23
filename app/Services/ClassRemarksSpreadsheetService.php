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
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ClassRemarksSpreadsheetService
{
    /**
     * Generate an Excel spreadsheet for class remarks and attendance.
     */
    public function generateTemplate(int $classroomId, int $sessionId, int $termId): Spreadsheet
    {
        $classroom = Classroom::findOrFail($classroomId);
        $session   = Session::findOrFail($sessionId);
        $term      = Term::findOrFail($termId);

        // Fetch enrolled students
        $enrollments = StudentEnrollment::where('classroom_id', $classroomId)
            ->where('session_id', $sessionId)
            ->with(['student.user', 'department'])
            ->get()
            ->sortBy(fn ($e) => $e->student?->name ?? '');

        // Fetch existing term results
        $termResults = TermResult::where('classroom_id', $classroomId)
            ->where('session_id', $sessionId)
            ->where('term_id', $termId)
            ->get()
            ->keyBy('student_id');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr(preg_replace('/[^A-Za-z0-9_\-]/', '_', $classroom->name), 0, 30));

        // ── Title Banner (Row 1) ─────────────────────────────────────────────
        $sheet->mergeCells('A1:I1');
        $sheet->setCellValue('A1', "CLASS REMARKS & ATTENDANCE — {$classroom->name} ({$session->name} / {$term->name})");
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E293B']], // Slate 800
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(32);

        // ── Subtitle (Row 2) ─────────────────────────────────────────────────
        $sheet->mergeCells('A2:I2');
        $sheet->setCellValue('A2', 'Instructions: Enter "Times Present", "Class Teacher Comment", and "Principal Comment" for each student. Do not modify Admission No or Student ID.');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '475569']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(20);

        // ── Table Headers (Row 4) ────────────────────────────────────────────
        $headers = [
            'A' => ['title' => 'S/N', 'width' => 8, 'align' => Alignment::HORIZONTAL_CENTER],
            'B' => ['title' => 'Admission No', 'width' => 16, 'align' => Alignment::HORIZONTAL_CENTER],
            'C' => ['title' => 'Student Full Name', 'width' => 28, 'align' => Alignment::HORIZONTAL_LEFT],
            'D' => ['title' => 'Department', 'width' => 18, 'align' => Alignment::HORIZONTAL_CENTER],
            'E' => ['title' => 'Average (%)', 'width' => 14, 'align' => Alignment::HORIZONTAL_CENTER],
            'F' => ['title' => 'Position', 'width' => 12, 'align' => Alignment::HORIZONTAL_CENTER],
            'G' => ['title' => 'Times Present', 'width' => 16, 'align' => Alignment::HORIZONTAL_CENTER],
            'H' => ['title' => 'Class Teacher Comment', 'width' => 45, 'align' => Alignment::HORIZONTAL_LEFT],
            'I' => ['title' => "Principal's Comment", 'width' => 45, 'align' => Alignment::HORIZONTAL_LEFT],
            'J' => ['title' => 'Student ID', 'width' => 12, 'align' => Alignment::HORIZONTAL_CENTER], // Hidden
        ];

        foreach ($headers as $col => $cfg) {
            $sheet->setCellValue("{$col}4", $cfg['title']);
            $sheet->getColumnDimension($col)->setWidth($cfg['width']);
        }

        // Style standard header columns (A-F)
        $sheet->getStyle('A4:F4')->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '334155']], // Slate 700
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Style editable columns (G-I) with soft indigo/blue highlight
        $sheet->getStyle('G4:I4')->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']], // Indigo 600
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Hidden ID column
        $sheet->getStyle('J4')->applyFromArray([
            'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => '94A3B8']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
        ]);
        $sheet->getColumnDimension('J')->setVisible(false);

        $sheet->getRowDimension(4)->setRowHeight(26);

        // ── Data Rows (Row 5+) ───────────────────────────────────────────────
        $rowIndex = 5;
        $sn = 1;

        foreach ($enrollments as $enrollment) {
            $student = $enrollment->student;
            if (! $student) {
                continue;
            }

            $termResult = $termResults->get($student->id);
            $departmentName = $enrollment->department?->name ?? '—';
            $average = $termResult ? (float) $termResult->average : '';
            $position = $termResult?->overall_position ?? '';
            $timesPresent = $termResult?->times_present ?? '';
            $comment = $termResult?->class_teacher_comment ?? '';
            $principalComment = $termResult?->principal_comment ?? '';

            $sheet->setCellValue("A{$rowIndex}", $sn);
            $sheet->setCellValueExplicit("B{$rowIndex}", $student->admission_number ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue("C{$rowIndex}", $student->name);
            $sheet->setCellValue("D{$rowIndex}", $departmentName);
            $sheet->setCellValue("E{$rowIndex}", $average !== '' ? $average : '—');
            $sheet->setCellValue("F{$rowIndex}", $position !== '' ? $position : '—');
            $sheet->setCellValue("G{$rowIndex}", $timesPresent !== '' ? $timesPresent : '');
            $sheet->setCellValue("H{$rowIndex}", $comment);
            $sheet->setCellValue("I{$rowIndex}", $principalComment);
            $sheet->setCellValue("J{$rowIndex}", $student->id);

            // Row styling
            $sheet->getStyle("A{$rowIndex}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$rowIndex}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$rowIndex}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("E{$rowIndex}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("F{$rowIndex}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("G{$rowIndex}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Highlight editable cells lightly
            $sheet->getStyle("G{$rowIndex}:I{$rowIndex}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8FAFC']],
            ]);

            $sheet->getRowDimension($rowIndex)->setRowHeight(22);

            $sn++;
            $rowIndex++;
        }

        $lastRow = max(5, $rowIndex - 1);

        // Apply grid borders
        $sheet->getStyle("A4:I{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CBD5E1'],
                ],
            ],
        ]);

        return $spreadsheet;
    }

    /**
     * Export template to temporary file on disk.
     */
    public function exportToTempFile(int $classroomId, int $sessionId, int $termId): string
    {
        $spreadsheet = $this->generateTemplate($classroomId, $sessionId, $termId);
        $tempPath = tempnam(sys_get_temp_dir(), 'class_remarks_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return $tempPath;
    }

    /**
     * Import and apply class remarks and attendance from uploaded spreadsheet.
     *
     * @return array{updated: int, errors: array<string>}
     */
    public function importSpreadsheet(string $filePath, int $classroomId, int $sessionId, int $termId): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();

        // Enrolled student IDs for validation
        $enrolledStudents = StudentEnrollment::where('classroom_id', $classroomId)
            ->where('session_id', $sessionId)
            ->pluck('student_id')
            ->toArray();

        $enrolledByAdmNo = Student::whereIn('id', $enrolledStudents)
            ->pluck('id', 'admission_number')
            ->toArray();

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
            &$updatedCount,
            &$errors
        ) {
            for ($row = 5; $row <= $highestRow; $row++) {
                $rawColJ      = trim((string) $sheet->getCell("J{$row}")->getValue());
                $rawColI      = trim((string) $sheet->getCell("I{$row}")->getValue());
                $rawAdmNo     = trim((string) $sheet->getCell("B{$row}")->getValue());
                $name         = trim((string) $sheet->getCell("C{$row}")->getValue());
                $timesPresent = trim((string) $sheet->getCell("G{$row}")->getValue());
                $comment      = trim((string) $sheet->getCell("H{$row}")->getValue());

                // Resolve raw student ID (Col J in new format, or fallback Col I if numeric for legacy template)
                $rawStudentId = is_numeric($rawColJ) ? $rawColJ : (is_numeric($rawColI) ? $rawColI : '');
                $principalComment = ! is_numeric($rawColI) ? $rawColI : '';

                // Skip completely empty rows
                if ($rawStudentId === '' && $rawAdmNo === '' && $name === '') {
                    continue;
                }

                // Resolve student
                $studentId = null;
                if ($rawStudentId !== '' && is_numeric($rawStudentId)) {
                    $candidateId = (int) $rawStudentId;
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

                // Validate Times Present
                $parsedTimesPresent = null;
                if ($timesPresent !== '') {
                    if (! is_numeric($timesPresent) || (int) $timesPresent < 0) {
                        $errors[] = "Row {$row} ({$name}): 'Times Present' must be a non-negative number.";
                        continue;
                    }
                    $parsedTimesPresent = (int) $timesPresent;
                }

                // Find or update term result
                $termResult = TermResult::where('student_id', $studentId)
                    ->where('session_id', $sessionId)
                    ->where('term_id', $termId)
                    ->first();

                if (! $termResult) {
                    // Create placeholder if results haven't been calculated yet
                    $termResult = new TermResult([
                        'student_id'   => $studentId,
                        'classroom_id' => $classroomId,
                        'session_id'   => $sessionId,
                        'term_id'      => $termId,
                        'is_finalized' => false,
                    ]);
                }

                $termResult->times_present = $parsedTimesPresent;
                $termResult->class_teacher_comment = $comment !== '' ? $comment : null;
                if ($principalComment !== '') {
                    $termResult->principal_comment = $principalComment;
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
