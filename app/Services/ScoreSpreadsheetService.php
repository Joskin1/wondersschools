<?php

namespace App\Services;

use App\Models\ClassScoreStructure;
use App\Models\Classroom;
use App\Models\Score;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use App\Models\Session;
use App\Models\Term;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ScoreSpreadsheetService
{
    /**
     * Generate an Excel spreadsheet for a class containing the specified subjects.
     */
    public function generateTemplate(
        int $classroomId,
        array $subjectIds,
        int $sessionId,
        int $termId,
        ?int $teacherId = null
    ): Spreadsheet {
        $classroom = Classroom::findOrFail($classroomId);
        $session   = Session::findOrFail($sessionId);
        $term      = Term::findOrFail($termId);

        $subjects = Subject::whereIn('id', $subjectIds)->active()->orderBy('name')->get();
        if ($subjects->isEmpty()) {
            throw new \InvalidArgumentException('No valid active subjects provided for export.');
        }

        // Get class score structure
        $structure = ClassScoreStructure::with(['items.scoreHead'])
            ->where('class_id', $classroomId)
            ->where('session_id', $sessionId)
            ->where('term_id', $termId)
            ->first();

        if (! $structure || $structure->items->isEmpty()) {
            throw new \RuntimeException("No score structure configured for {$classroom->name} in {$session->name} ({$term->name}).");
        }

        $scoreHeads = $structure->items->map(function ($item) {
            return [
                'id'            => $item->score_head_id,
                'name'          => $item->scoreHead->name,
                'effective_max' => $item->max_score_override ?? $item->scoreHead->max_score,
            ];
        })->toArray();

        // Get enrolled students
        $enrolledIds = StudentEnrollment::where('classroom_id', $classroomId)
            ->where('session_id', $sessionId)
            ->pluck('student_id');

        $students = Student::whereIn('id', $enrolledIds)
            ->active()
            ->orderBy('full_name')
            ->get();

        // Fetch existing scores for pre-filling
        $existingScores = Score::where('classroom_id', $classroomId)
            ->whereIn('subject_id', $subjectIds)
            ->where('session_id', $sessionId)
            ->where('term_id', $termId)
            ->whereIn('student_id', $enrolledIds)
            ->get()
            ->groupBy(fn ($s) => "{$s->student_id}_{$s->subject_id}_{$s->score_head_id}");

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr(preg_replace('/[^A-Za-z0-9_\-]/', '_', $classroom->name), 0, 30));

        // ── Calculate total columns ──────────────────────────────────────────
        // Col 1: S/N, Col 2: Admission No, Col 3: Student Name
        $fixedColumns = 3;
        $scoreColumnsCount = count($subjects) * count($scoreHeads);
        $totalColumns = $fixedColumns + $scoreColumnsCount + 1; // +1 for hidden student_id
        $lastColLetter = Coordinate::stringFromColumnIndex($totalColumns);

        // ── Row 1: Title Banner ──────────────────────────────────────────────
        $sheet->mergeCells("A1:{$lastColLetter}1");
        $sheet->setCellValue('A1', "OFFICIAL SCORE ENTRY SHEET — {$classroom->name} ({$session->name} / {$term->name})");
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E293B']], // Slate 800
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(32);

        // ── Row 2: Instructions ──────────────────────────────────────────────
        $sheet->mergeCells("A2:{$lastColLetter}2");
        $sheet->setCellValue('A2', 'Instructions: Enter raw marks for each assessment component. Do NOT add total columns. Save and upload back to the portal.');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '475569']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F5F9']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(20);

        // ── Row 3: Metadata Marker (Machine-Readable) ────────────────────────
        $metaJson = json_encode([
            'v'            => 1,
            'classroom_id' => $classroomId,
            'session_id'   => $sessionId,
            'term_id'      => $termId,
            'subjects'     => $subjects->pluck('id')->toArray(),
        ]);
        $sheet->setCellValue('A3', "#META:{$metaJson}");
        $sheet->getStyle('A3')->applyFromArray([
            'font' => ['size' => 7, 'color' => ['rgb' => '94A3B8']],
        ]);
        $sheet->getRowDimension(3)->setRowHeight(12);

        // ── Row 4: Subject Group Headers ─────────────────────────────────────
        $sheet->mergeCells('A4:C4');
        $sheet->setCellValue('A4', 'STUDENT INFORMATION');
        $sheet->getStyle('A4:C4')->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '0F172A']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $colIdx = 4;
        foreach ($subjects as $subject) {
            $startCol = Coordinate::stringFromColumnIndex($colIdx);
            $endCol   = Coordinate::stringFromColumnIndex($colIdx + count($scoreHeads) - 1);
            
            if ($startCol !== $endCol) {
                $sheet->mergeCells("{$startCol}4:{$endCol}4");
            }
            $sheet->setCellValue("{$startCol}4", strtoupper($subject->name));
            
            // Alternating pleasant colors for subject groups
            $sheet->getStyle("{$startCol}4:{$endCol}4")->applyFromArray([
                'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '1E1B4B']], // Indigo 950
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EEF2FF']], // Indigo 50
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);

            $colIdx += count($scoreHeads);
        }

        // Hidden / System ID Col Header in Row 4
        $sysColLetter = Coordinate::stringFromColumnIndex($colIdx);
        $sheet->setCellValue("{$sysColLetter}4", 'SYSTEM');
        $sheet->getStyle("{$sysColLetter}4")->applyFromArray([
            'font' => ['size' => 8, 'color' => ['rgb' => '94A3B8']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->getRowDimension(4)->setRowHeight(24);

        // ── Row 5: Column Headers ────────────────────────────────────────────
        $sheet->setCellValue('A5', 'S/N');
        $sheet->setCellValue('B5', 'Admission No');
        $sheet->setCellValue('C5', 'Student Full Name');

        $sheet->getStyle('A5:C5')->applyFromArray([
            'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => '334155']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'CBD5E1']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $colIdx = 4;
        foreach ($subjects as $subject) {
            foreach ($scoreHeads as $sh) {
                $colLetter = Coordinate::stringFromColumnIndex($colIdx);
                $headerText = "[{$subject->name}] {$sh['name']} (Max: {$sh['effective_max']})";
                
                // Set comment with machine tag
                $sheet->setCellValue("{$colLetter}5", "{$sh['name']}\n(Max: {$sh['effective_max']})");
                $sheet->getComment("{$colLetter}5")->getText()->createTextRun("SUBJ_ID:{$subject->id}|HEAD_ID:{$sh['id']}|MAX:{$sh['effective_max']}");

                $sheet->getStyle("{$colLetter}5")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 8.5, 'color' => ['rgb' => '312E81']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0E7FF']],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                        'wrapText'   => true,
                    ],
                ]);
                $colIdx++;
            }
        }

        // System ID header
        $sheet->setCellValue("{$sysColLetter}5", 'Student ID');
        $sheet->getStyle("{$sysColLetter}5")->applyFromArray([
            'font' => ['size' => 8, 'color' => ['rgb' => '94A3B8']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->getRowDimension(5)->setRowHeight(32);

        // ── Row 6+: Student Data Rows ────────────────────────────────────────
        $rowNum = 6;
        $sn = 1;

        foreach ($students as $student) {
            $cleanAdm  = $this->sanitizeFormulaValue($student->admission_number ?? '—');
            $cleanName = $this->sanitizeFormulaValue($student->full_name);

            $sheet->setCellValueExplicit("A{$rowNum}", $sn, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit("B{$rowNum}", $cleanAdm, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("C{$rowNum}", $cleanName, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);

            // Style student info (read-only gray feel)
            $sheet->getStyle("A{$rowNum}:C{$rowNum}")->applyFromArray([
                'font' => ['size' => 9, 'color' => ['rgb' => '1E293B']],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getStyle("A{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Populate scores
            $colIdx = 4;
            foreach ($subjects as $subject) {
                foreach ($scoreHeads as $sh) {
                    $colLetter = Coordinate::stringFromColumnIndex($colIdx);
                    $key = "{$student->id}_{$subject->id}_{$sh['id']}";
                    
                    if ($existingScores->has($key)) {
                        $rawVal = $existingScores[$key]->first()->score;
                        if ($rawVal !== null) {
                            $sheet->setCellValueExplicit("{$colLetter}{$rowNum}", (float) $rawVal, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                        }
                    }

                    // Score cell styling
                    $sheet->getStyle("{$colLetter}{$rowNum}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 9.5, 'color' => ['rgb' => '0F172A']],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical'   => Alignment::VERTICAL_CENTER,
                        ],
                    ]);

                    $colIdx++;
                }
            }

            // Hidden Student ID
            $sheet->setCellValueExplicit("{$sysColLetter}{$rowNum}", $student->id, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
            $sheet->getStyle("{$sysColLetter}{$rowNum}")->applyFromArray([
                'font' => ['size' => 7, 'color' => ['rgb' => 'CBD5E1']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);

            $sheet->getRowDimension($rowNum)->setRowHeight(22);
            $rowNum++;
            $sn++;
        }

        $lastDataRow = $rowNum - 1;

        // ── Apply Borders & Formatting ───────────────────────────────────────
        $sheet->getStyle("A4:{$lastColLetter}{$lastDataRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['rgb' => 'CBD5E1'],
                ],
            ],
        ]);

        // Auto-size columns
        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(16);
        $sheet->getColumnDimension('C')->setWidth(26);

        for ($c = 4; $c < $totalColumns; $c++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($c))->setWidth(16);
        }
        $sheet->getColumnDimension($sysColLetter)->setWidth(12);

        return $spreadsheet;
    }

    /**
     * Export spreadsheet to a temporary file path.
     */
    public function exportToTempFile(
        int $classroomId,
        array $subjectIds,
        int $sessionId,
        int $termId,
        ?int $teacherId = null
    ): string {
        $spreadsheet = $this->generateTemplate($classroomId, $subjectIds, $sessionId, $termId, $teacherId);
        $writer = new Xlsx($spreadsheet);

        $tempPath = tempnam(sys_get_temp_dir(), 'score_export_') . '.xlsx';
        $writer->save($tempPath);

        return $tempPath;
    }

    /**
     * Parse and validate an uploaded score spreadsheet.
     *
     * Returns a comprehensive staging matrix with rows, cells, values, and errors.
     */
    public function parseAndValidate(
        string $filePath,
        int $classroomId,
        array $authorizedSubjectIds,
        int $sessionId,
        int $termId
    ): array {
        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(false); // We want cell comments too
        $spreadsheet = $reader->load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        // ── Check Metadata ───────────────────────────────────────────────────
        $metaRowValue = (string) $sheet->getCell('A3')->getValue();
        if (str_starts_with($metaRowValue, '#META:')) {
            $metaJson = substr($metaRowValue, 6);
            $meta = json_decode($metaJson, true);
            if (is_array($meta)) {
                if (isset($meta['classroom_id']) && (int) $meta['classroom_id'] !== $classroomId) {
                    throw new \RuntimeException('The uploaded spreadsheet belongs to a different classroom.');
                }
                if (isset($meta['session_id']) && (int) $meta['session_id'] !== $sessionId) {
                    throw new \RuntimeException('The uploaded spreadsheet belongs to a different academic session.');
                }
                if (isset($meta['term_id']) && (int) $meta['term_id'] !== $termId) {
                    throw new \RuntimeException('The uploaded spreadsheet belongs to a different term.');
                }
            }
        }

        // ── Load current score structure for validation ──────────────────────
        $structure = ClassScoreStructure::with(['items.scoreHead'])
            ->where('class_id', $classroomId)
            ->where('session_id', $sessionId)
            ->where('term_id', $termId)
            ->first();

        if (! $structure || $structure->items->isEmpty()) {
            throw new \RuntimeException('No score structure is currently configured for this class.');
        }

        $scoreHeadMap = [];
        foreach ($structure->items as $item) {
            $scoreHeadMap[$item->score_head_id] = [
                'id'            => $item->score_head_id,
                'name'          => $item->scoreHead->name,
                'effective_max' => $item->max_score_override ?? $item->scoreHead->max_score,
            ];
        }

        // ── Load authorized subjects ─────────────────────────────────────────
        $authorizedSubjects = Subject::whereIn('id', $authorizedSubjectIds)->pluck('name', 'id')->toArray();

        // ── Identify Columns in Row 4 & 5 ────────────────────────────────────
        $highestColumn = $sheet->getHighestColumn();
        $highestColIndex = Coordinate::columnIndexFromString($highestColumn);
        $highestRow = $sheet->getHighestRow();

        $columns = [];
        $studentIdColIndex = null;
        $admissionNoColIndex = 2;
        $nameColIndex = 3;

        // Extract column definitions
        $currentSubjectId = null;
        for ($col = 4; $col <= $highestColIndex; $col++) {
            $colLetter = Coordinate::stringFromColumnIndex($col);
            $r4Val = trim((string) $sheet->getCell("{$colLetter}4")->getValue());
            $r5Val = trim((string) $sheet->getCell("{$colLetter}5")->getValue());

            // Check if this is the system student id column
            if (str_contains(strtoupper($r5Val), 'STUDENT ID') || str_contains(strtoupper($r4Val), 'SYSTEM')) {
                $studentIdColIndex = $col;
                continue;
            }

            // Check comment for machine tags
            $comment = $sheet->getComment("{$colLetter}5")->getText()->getPlainText();
            $subjId = null;
            $headId = null;
            $maxScore = null;

            if ($comment && preg_match('/SUBJ_ID:(\d+)\|HEAD_ID:(\d+)\|MAX:(\d+)/', $comment, $matches)) {
                $subjId   = (int) $matches[1];
                $headId   = (int) $matches[2];
                $maxScore = (int) $matches[3];
            } else {
                // Parse fallback from header text
                foreach ($authorizedSubjects as $sId => $sName) {
                    if (str_contains(strtoupper($r4Val), strtoupper($sName)) || str_contains(strtoupper($r5Val), strtoupper($sName))) {
                        $subjId = $sId;
                        break;
                    }
                }
                foreach ($scoreHeadMap as $hId => $hData) {
                    if (str_contains(strtoupper($r5Val), strtoupper($hData['name']))) {
                        $headId = $hId;
                        $maxScore = $hData['effective_max'];
                        break;
                    }
                }
            }

            if ($subjId && $headId) {
                // Ensure effective max is up to date from database structure
                $effectiveMax = $scoreHeadMap[$headId]['effective_max'] ?? $maxScore ?? 100;

                // Validate teacher has authorization for this subject
                $isAuthorized = in_array($subjId, $authorizedSubjectIds);

                $columns[$col] = [
                    'col_letter'    => $colLetter,
                    'subject_id'    => $subjId,
                    'subject_name'  => $authorizedSubjects[$subjId] ?? "Subject #{$subjId}",
                    'score_head_id' => $headId,
                    'score_head'    => $scoreHeadMap[$headId]['name'] ?? "Head #{$headId}",
                    'effective_max' => $effectiveMax,
                    'is_authorized' => $isAuthorized,
                    'key'           => "{$subjId}_{$headId}",
                ];
            }
        }

        if (empty($columns)) {
            throw new \RuntimeException('No recognizable score columns found in the uploaded file. Please use the official exported template.');
        }

        // ── Load Enrolled Students ───────────────────────────────────────────
        $enrolledIds = StudentEnrollment::where('classroom_id', $classroomId)
            ->where('session_id', $sessionId)
            ->pluck('student_id');

        $students = Student::whereIn('id', $enrolledIds)
            ->active()
            ->get()
            ->keyBy('id');

        $studentsByAdm = $students->keyBy(fn ($s) => strtoupper(trim((string) $s->admission_number)));

        // ── Parse Rows ───────────────────────────────────────────────────────
        $stagedStudents = [];
        $totalErrors = 0;
        $totalScoresCount = 0;

        for ($row = 6; $row <= $highestRow; $row++) {
            $admVal  = trim((string) $sheet->getCell("B{$row}")->getValue());
            $nameVal = trim((string) $sheet->getCell("C{$row}")->getValue());
            
            $rawStudentId = null;
            if ($studentIdColIndex) {
                $rawIdColLetter = Coordinate::stringFromColumnIndex($studentIdColIndex);
                $rawStudentId = (int) $sheet->getCell("{$rawIdColLetter}{$row}")->getValue();
            }

            // Match student
            $student = null;
            if ($rawStudentId && isset($students[$rawStudentId])) {
                $student = $students[$rawStudentId];
            } elseif ($admVal && isset($studentsByAdm[strtoupper($admVal)])) {
                $student = $studentsByAdm[strtoupper($admVal)];
            }

            // Skip empty rows
            if (! $student && empty($admVal) && empty($nameVal)) {
                continue;
            }

            $studentId = $student ? $student->id : null;
            $studentName = $student ? $student->full_name : ($nameVal ?: "Unknown ({$admVal})");
            $studentAdm  = $student ? ($student->admission_number ?? '—') : ($admVal ?: '—');

            $rowScores = [];
            $rowHasErrors = false;

            if (! $student) {
                $rowHasErrors = true;
                $totalErrors++;
            }

            foreach ($columns as $col => $colDef) {
                $cellVal = $sheet->getCell("{$colDef['col_letter']}{$row}")->getValue();
                $strVal  = trim((string) $cellVal);

                $key = $colDef['key'];
                $error = null;
                $parsedVal = '';

                if (! $colDef['is_authorized']) {
                    $error = 'You are not assigned to grade this subject.';
                    $rowHasErrors = true;
                    $totalErrors++;
                } elseif ($strVal !== '' && $cellVal !== null) {
                    if (! is_numeric($strVal)) {
                        $error = 'Must be a number.';
                        $rowHasErrors = true;
                        $totalErrors++;
                        $parsedVal = $strVal;
                    } else {
                        $numeric = (float) $strVal;
                        if ($numeric < 0) {
                            $error = 'Cannot be negative.';
                            $rowHasErrors = true;
                            $totalErrors++;
                            $parsedVal = (string) $numeric;
                        } elseif ($numeric > $colDef['effective_max']) {
                            $error = "Exceeds max of {$colDef['effective_max']}.";
                            $rowHasErrors = true;
                            $totalErrors++;
                            $parsedVal = (string) $numeric;
                        } else {
                            $parsedVal = (string) $numeric;
                            $totalScoresCount++;
                        }
                    }
                }

                $rowScores[$key] = [
                    'subject_id'    => $colDef['subject_id'],
                    'score_head_id' => $colDef['score_head_id'],
                    'effective_max' => $colDef['effective_max'],
                    'value'         => $parsedVal,
                    'error'         => $error,
                ];
            }

            $stagedStudents[] = [
                'student_id'       => $studentId,
                'admission_number' => $studentAdm,
                'full_name'        => $studentName,
                'unmatched'        => ! $student,
                'scores'           => $rowScores,
                'has_error'        => $rowHasErrors,
            ];
        }

        // Group columns by subject for the preview UI
        $groupedSubjects = [];
        foreach ($columns as $colDef) {
            $sId = $colDef['subject_id'];
            if (! isset($groupedSubjects[$sId])) {
                $groupedSubjects[$sId] = [
                    'id'          => $sId,
                    'name'        => $colDef['subject_name'],
                    'score_heads' => [],
                ];
            }
            $groupedSubjects[$sId]['score_heads'][$colDef['score_head_id']] = [
                'id'            => $colDef['score_head_id'],
                'name'          => $colDef['score_head'],
                'effective_max' => $colDef['effective_max'],
                'key'           => $colDef['key'],
            ];
        }

        return [
            'classroom_id'       => $classroomId,
            'session_id'         => $sessionId,
            'term_id'            => $termId,
            'subjects'           => array_values($groupedSubjects),
            'students'           => $stagedStudents,
            'total_students'     => count($stagedStudents),
            'total_scores_count' => $totalScoresCount,
            'total_errors'       => $totalErrors,
        ];
    }

    /**
     * Prevent CSV/Excel Formula Injection (CWE-1236)
     */
    private function sanitizeFormulaValue(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        // If string starts with formula triggers (=, +, -, @, \t, \r), prepend a single quote
        if (preg_match('/^[=\+\-@\t\r]/', $value)) {
            return "'" . $value;
        }

        return $value;
    }
}

