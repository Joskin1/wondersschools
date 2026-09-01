<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use App\Models\Classroom;
use App\Models\Session;
use App\Models\Student;
use App\Models\StudentEnrollment;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as SpreadsheetDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImportStudents extends Page
{
    use WithFileUploads;

    protected static string $resource = StudentResource::class;

    protected string $view = 'filament.resources.student-resource.pages.import-students';

    protected static ?string $title = 'Bulk Import Students';

    public ?TemporaryUploadedFile $upload = null;

    /** @var array<int, array<string, mixed>> */
    public array $previewRows = [];

    public int $validCount = 0;

    public int $invalidCount = 0;

    /** @var array<int, string> */
    private const TEMPLATE_COLUMNS = [
        'full_name',
        'classroom',
        'session',
        'date_of_birth',
        'gender',
        'address',
        'previous_school',
        'parent_name',
        'parent_phone',
        'parent_email',
    ];

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download_template')
                ->label('Download Template')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn () => self::downloadTemplate()),

            Action::make('back_to_students')
                ->label('Back to Students')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(StudentResource::getUrl('index')),
        ];
    }

    public static function downloadTemplate(): BinaryFileResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Students');
        $sheet->fromArray(self::TEMPLATE_COLUMNS, null, 'A1');
        $sheet->fromArray([
            'Ada Johnson',
            Classroom::query()->orderBy('class_order')->orderBy('name')->value('name') ?? 'Primary 1',
            Session::active()->value('name') ?? '',
            '2017-05-21',
            'Female',
            '12 Example Street',
            'Example Nursery School',
            'Mrs Johnson',
            '08012345678',
            'parent@example.com',
        ], null, 'A2');

        foreach (range('A', 'J') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $classroomsSheet = $spreadsheet->createSheet();
        $classroomsSheet->setTitle('Classrooms');
        $classroomsSheet->fromArray(['id', 'name'], null, 'A1');
        Classroom::query()
            ->orderBy('class_order')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->each(fn (Classroom $classroom, int $index) => $classroomsSheet->fromArray([
                $classroom->id,
                $classroom->name,
            ], null, 'A'.($index + 2)));

        $sessionsSheet = $spreadsheet->createSheet();
        $sessionsSheet->setTitle('Sessions');
        $sessionsSheet->fromArray(['id', 'name', 'active'], null, 'A1');
        Session::query()
            ->orderByDesc('is_active')
            ->orderByDesc('start_year')
            ->get(['id', 'name', 'is_active'])
            ->each(fn (Session $session, int $index) => $sessionsSheet->fromArray([
                $session->id,
                $session->name,
                $session->is_active ? 'yes' : 'no',
            ], null, 'A'.($index + 2)));

        $path = tempnam(sys_get_temp_dir(), 'student-import-template-').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        return response()->download($path, 'student-import-template.xlsx')->deleteFileAfterSend();
    }

    public function updatedUpload(): void
    {
        $this->previewRows = [];
        $this->validCount = 0;
        $this->invalidCount = 0;
    }

    public function preview(): void
    {
        $this->validate([
            'upload' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ]);

        try {
            $this->previewRows = $this->parseUpload();
            $this->validCount = collect($this->previewRows)->where('valid', true)->count();
            $this->invalidCount = count($this->previewRows) - $this->validCount;

            Notification::make()
                ->title('File preview ready')
                ->body("{$this->validCount} valid rows, {$this->invalidCount} rows need attention.")
                ->success()
                ->send();
        } catch (\Throwable $e) {
            $this->previewRows = [];
            $this->validCount = 0;
            $this->invalidCount = 0;

            Notification::make()
                ->title('Could not read spreadsheet')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function saveImport(): void
    {
        if (empty($this->previewRows)) {
            Notification::make()->title('Preview the spreadsheet before saving.')->warning()->send();

            return;
        }

        if ($this->invalidCount > 0) {
            Notification::make()
                ->title('Fix invalid rows before saving')
                ->body('Only a clean preview can be imported.')
                ->danger()
                ->send();

            return;
        }

        DB::transaction(function () {
            foreach ($this->previewRows as $row) {
                $student = Student::create([
                    'full_name' => $row['data']['full_name'],
                    'date_of_birth' => $row['data']['date_of_birth'],
                    'gender' => $row['data']['gender'],
                    'address' => $row['data']['address'],
                    'previous_school' => $row['data']['previous_school'],
                    'parent_name' => $row['data']['parent_name'],
                    'parent_phone' => $row['data']['parent_phone'],
                    'parent_email' => $row['data']['parent_email'],
                    'status' => 'pending',
                ]);

                StudentEnrollment::create([
                    'student_id' => $student->id,
                    'classroom_id' => $row['classroom_id'],
                    'session_id' => $row['session_id'],
                ]);
            }
        });

        $created = $this->validCount;
        $this->reset(['upload', 'previewRows', 'validCount', 'invalidCount']);

        Notification::make()
            ->title('Students imported')
            ->body("{$created} students were created.")
            ->success()
            ->send();

        $this->redirect(StudentResource::getUrl('index'));
    }

    public function clearPreview(): void
    {
        $this->reset(['upload', 'previewRows', 'validCount', 'invalidCount']);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parseUpload(): array
    {
        $spreadsheet = IOFactory::load($this->upload->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);
        $spreadsheet->disconnectWorksheets();

        if (count($rows) < 2) {
            throw new \RuntimeException('The spreadsheet must include a header row and at least one student row.');
        }

        $headers = $this->headersFromRow(array_shift($rows));
        $missing = array_diff(['full_name', 'classroom'], array_values($headers));

        if ($missing) {
            throw new \RuntimeException('Missing required columns: '.implode(', ', $missing));
        }

        $classrooms = Classroom::query()->get(['id', 'name']);
        $sessions = Session::query()->get(['id', 'name', 'is_active']);
        $activeSession = $sessions->firstWhere('is_active', true);

        $parsed = [];

        foreach (array_values($rows) as $index => $row) {
            if ($this->isBlankRow($row)) {
                continue;
            }

            $rowNumber = $index + 2;
            $data = $this->dataFromRow($row, $headers);
            $errors = [];

            if ($data['full_name'] === '') {
                $errors[] = 'Full name is required.';
            }

            $classroom = $this->matchByIdOrName($classrooms, $data['classroom']);
            if (! $classroom) {
                $errors[] = "Classroom \"{$data['classroom']}\" was not found.";
            }

            $session = $data['session'] !== ''
                ? $this->matchByIdOrName($sessions, $data['session'])
                : $activeSession;

            if (! $session) {
                $errors[] = $data['session'] !== ''
                    ? "Session \"{$data['session']}\" was not found."
                    : 'No active academic session is available.';
            }

            $dateOfBirth = $this->normalizeDate($data['date_of_birth']);
            if ($data['date_of_birth'] !== '' && $dateOfBirth === null) {
                $errors[] = 'Date of birth must be YYYY-MM-DD or a valid Excel date.';
            }

            if ($data['parent_email'] !== '' && ! filter_var($data['parent_email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Parent email is invalid.';
            }

            $data['date_of_birth'] = $dateOfBirth;

            $parsed[] = [
                'row' => $rowNumber,
                'valid' => empty($errors),
                'errors' => $errors,
                'data' => $data,
                'classroom_id' => $classroom?->id,
                'classroom_name' => $classroom?->name,
                'session_id' => $session?->id,
                'session_name' => $session?->name,
            ];
        }

        if (empty($parsed)) {
            throw new \RuntimeException('No student rows were found in the spreadsheet.');
        }

        return $parsed;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, string>
     */
    private function headersFromRow(array $row): array
    {
        $headers = [];

        foreach ($row as $column => $value) {
            $normalized = Str::of((string) $value)
                ->trim()
                ->lower()
                ->replace([' ', '-', '.'], '_')
                ->replaceMatches('/[^a-z0-9_]/', '')
                ->toString();

            if ($normalized !== '') {
                $headers[$column] = $normalized;
            }
        }

        return $headers;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, string>  $headers
     * @return array<string, mixed>
     */
    private function dataFromRow(array $row, array $headers): array
    {
        $data = array_fill_keys(self::TEMPLATE_COLUMNS, '');

        foreach ($headers as $column => $field) {
            if (array_key_exists($field, $data)) {
                $data[$field] = is_string($row[$column] ?? null)
                    ? trim($row[$column])
                    : ($row[$column] ?? '');
            }
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function isBlankRow(array $row): bool
    {
        return collect($row)
            ->filter(fn ($value) => trim((string) $value) !== '')
            ->isEmpty();
    }

    private function matchByIdOrName($records, mixed $value): mixed
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (ctype_digit($value)) {
            $matched = $records->firstWhere('id', (int) $value);
            if ($matched) {
                return $matched;
            }
        }

        $normalized = Str::of($value)->trim()->lower()->squish()->toString();

        return $records->first(
            fn ($record) => Str::of($record->name)->trim()->lower()->squish()->toString() === $normalized
        );
    }

    private function normalizeDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return SpreadsheetDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }

        $timestamp = strtotime((string) $value);

        return $timestamp ? date('Y-m-d', $timestamp) : null;
    }
}
