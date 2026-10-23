<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ClassArm;
use App\Models\Classroom;
use App\Models\Session;
use App\Models\Setting;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Tenant;
use App\Models\User;
use App\Services\StudentAccountService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ImportCathedralStudents extends Command
{
    protected $signature = 'import:cathedral-students {--tenant=} {--wipe}';

    protected $description = 'Import 219 Cathedral students, assign CCOSC admission numbers from SS3 to JSS1, set class arms, and export sign-off rosters';

    public function handle(StudentAccountService $accountService): int
    {
        $tenantId = $this->option('tenant');
        if (! $tenantId) {
            $tenantId = Tenant::where('id', 'cathedral')->exists() ? 'cathedral' : 'chizylite';
        }

        $tenant = Tenant::find($tenantId);
        if (! $tenant) {
            $this->error("Tenant '{$tenantId}' not found.");
            return 1;
        }

        tenancy()->initialize($tenant);
        $this->info("Initialized tenant: {$tenant->id} ({$tenant->name})");

        $activeSession = Session::where('is_active', true)->first();
        if (! $activeSession) {
            $this->error("No active academic session found.");
            return 1;
        }
        $this->info("Active session: {$activeSession->name} (ID: {$activeSession->id})");

        $schoolName = Setting::where('key', 'school_name')->value('value') ?: 'Cathedral Church of Our Saviour College';
        $domain = $tenant->domains->first()?->domain ?? 'cathedral-college.duckdns.org';
        $scheme = str_contains($domain, 'test') ? 'http' : 'https';
        $portalUrl = "{$scheme}://{$domain}/student/login";

        // Wipe existing students if requested
        if ($this->option('wipe')) {
            $this->warn("Wiping existing student records in tenant {$tenant->id}...");
            $existingStudentUserIds = Student::whereNotNull('user_id')->pluck('user_id')->toArray();
            
            StudentEnrollment::query()->delete();
            DB::table('subject_results')->delete();
            DB::table('term_results')->delete();
            if (DB::getSchemaBuilder()->hasTable('result_pins')) {
                DB::table('result_pins')->delete();
                DB::table('result_pin_batches')->delete();
            }
            Student::query()->forceDelete();
            if (! empty($existingStudentUserIds)) {
                User::whereIn('id', $existingStudentUserIds)->where('role', 'student')->delete();
            }
            $this->info("Previous student records cleared.");
        }

        // Ensure classrooms & class arms
        $classrooms = [
            'JSS1' => Classroom::firstOrCreate(['name' => 'JSS1'], ['class_order' => 1, 'is_active' => true]),
            'JSS2' => Classroom::firstOrCreate(['name' => 'JSS2'], ['class_order' => 2, 'is_active' => true]),
            'JSS3' => Classroom::firstOrCreate(['name' => 'JSS3'], ['class_order' => 3, 'is_active' => true]),
            'SS1'  => Classroom::firstOrCreate(['name' => 'SS1'], ['class_order' => 4, 'is_active' => true]),
            'SS2'  => Classroom::firstOrCreate(['name' => 'SS2'], ['class_order' => 5, 'is_active' => true]),
            'SS3'  => Classroom::firstOrCreate(['name' => 'SS3'], ['class_order' => 6, 'is_active' => true]),
        ];

        $arms = [];
        foreach (['JSS1', 'JSS2', 'JSS3', 'SS1', 'SS2'] as $cKey) {
            $arms[$cKey]['Gold'] = ClassArm::firstOrCreate([
                'classroom_id' => $classrooms[$cKey]->id,
                'name'         => 'Gold',
            ], ['is_active' => true]);

            $arms[$cKey]['Brown'] = ClassArm::firstOrCreate([
                'classroom_id' => $classrooms[$cKey]->id,
                'name'         => 'Brown',
            ], ['is_active' => true]);
        }

        $sections = $this->getStudentSections();
        $processedStudents = [];
        $globalSequence = 1;

        // Find highest existing CCOSC2026 number in the database
        $maxCcosc = Student::where('admission_number', 'like', 'CCOSC2026%')
            ->orderByDesc('admission_number')
            ->value('admission_number');
        $nextCcoscSeq = 1;
        if ($maxCcosc && preg_match('/CCOSC2026(\d+)/', $maxCcosc, $m)) {
            $nextCcoscSeq = intval($m[1]) + 1;
        }

        DB::beginTransaction();
        try {
            foreach ($sections as $sectionTitle => $sectionData) {
                $cKey = $sectionData['classroom'];
                $armName = $sectionData['arm'];
                $classroom = $classrooms[$cKey];
                $classArm = $armName ? ($arms[$cKey][$armName] ?? null) : null;

                $this->info("Processing {$sectionTitle} (" . count($sectionData['students']) . " students)...");

                foreach ($sectionData['students'] as $rawEntry) {
                    $parsed = $this->parseStudentEntry($rawEntry);

                    // Look for existing student by name and enrollment in this classroom
                    $student = Student::where('full_name', $parsed['full_name'])
                        ->orWhereRaw('LOWER(full_name) = ?', [strtolower($parsed['full_name'])])
                        ->whereHas('enrollments', fn ($q) => $q->where('classroom_id', $classroom->id)->where('session_id', $activeSession->id))
                        ->first();

                    if (! $student) {
                        if (! empty($parsed['admission_number'])) {
                            $admNumber = $parsed['admission_number'];
                        } else {
                            $admNumber = 'CCOSC2026' . str_pad((string)$nextCcoscSeq++, 5, '0', STR_PAD_LEFT);
                        }

                        $student = Student::create([
                            'full_name'        => $parsed['full_name'],
                            'admission_number' => $admNumber,
                            'date_of_birth'    => $parsed['dob'],
                            'status'           => 'active',
                            'is_portal_active' => true,
                            'activated_at'     => now(),
                        ]);

                        StudentEnrollment::create([
                            'student_id'   => $student->id,
                            'classroom_id' => $classroom->id,
                            'class_arm_id' => $classArm?->id,
                            'session_id'   => $activeSession->id,
                        ]);
                    } else {
                        $admNumber = $student->admission_number;
                    }

                    $parts = preg_split('/\s+/', $parsed['full_name']);
                    $surname = $parts[0] ?? '';
                    $firstName = $parts[1] ?? '';
                    $otherName = count($parts) > 2 ? implode(' ', array_slice($parts, 2)) : null;

                    if (method_exists($accountService, 'createOrLinkUser')) {
                        $user = $accountService->createOrLinkUser($student, $surname, $firstName, $otherName, $parsed['password']);
                    } else {
                        $user = $accountService->createOrUpdateLogin($student, $parsed['password']);
                    }

                    $processedStudents[$sectionTitle][] = [
                        'sn'               => $globalSequence,
                        'section_sn'       => count($processedStudents[$sectionTitle] ?? []) + 1,
                        'student_id'       => $student->id,
                        'name'             => $student->full_name,
                        'admission_number' => $admNumber,
                        'email'            => $user->email,
                        'password'         => $parsed['password'],
                        'classroom'        => $cKey,
                        'arm'              => $armName ?: '—',
                        'section_title'    => $sectionTitle,
                        'dob'              => $student->date_of_birth ?: ($parsed['dob'] ?? '—'),
                        'portal_active'    => 'Active',
                    ];

                    $globalSequence++;
                }
            }

            DB::commit();
            $this->info("All " . ($globalSequence - 1) . " Cathedral students imported successfully!");
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error("Failed to import students: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }

        // Generate Exports
        $this->generateExports($processedStudents, $activeSession->name, $schoolName, $portalUrl);

        return 0;
    }

    private function parseStudentEntry(string $raw): array
    {
        $dob = null;
        $lin = null;
        $clean = $raw;

        if (preg_match('/[\s—–-]+(\d{2}-\d{2}-\d{4})\s*$/', $clean, $m)) {
            $dobRaw = $m[1];
            $dob = \DateTime::createFromFormat('d-m-Y', $dobRaw)?->format('Y-m-d') ?: null;
            $clean = preg_replace('/[\s—–-]+\d{2}-\d{2}-\d{4}\s*$/', '', $clean);
        }

        if (preg_match('/(.*?)(202\d[A-Za-z]\d+|024[A-Za-z]\d+)\s*$/', $clean, $m)) {
            $clean = trim($m[1]);
            $lin = trim($m[2]);
            if (str_starts_with($lin, '024')) {
                $lin = '2' . $lin;
            }
        }

        $clean = trim(preg_replace('/^[\d\)\.\s]+/', '', $clean));
        $clean = trim(preg_replace('/[\s—–\.]+$/', '', $clean));

        if (str_starts_with($clean, 'OnadujaFiyinfoluwa')) {
            $clean = preg_replace('/^OnadujaFiyinfoluwa/', 'Onaduja Fiyinfoluwa', $clean);
        }
        if (str_starts_with($clean, 'Ade banjo')) {
            $clean = preg_replace('/^Ade banjo/i', 'Adebanjo', $clean);
        }

        $clean = trim(preg_replace('/\s+/', ' ', $clean));

        $words = explode(' ', $clean);
        $firstName = count($words) > 1 ? $words[1] : $words[0];
        $firstName = preg_replace('/[^a-zA-Z]/', '', $firstName);
        if (empty($firstName)) {
            $firstName = preg_replace('/[^a-zA-Z]/', '', $words[0]);
        }
        $password = ucfirst(strtolower($firstName)) . '123';

        return [
            'full_name'        => $clean,
            'admission_number' => $lin,
            'dob'              => $dob,
            'first_name'       => $firstName,
            'password'         => $password,
        ];
    }

    private function generateExports(array $studentsBySection, string $sessionName, string $schoolName, string $portalUrl): void
    {
        $downloadDir = public_path('downloads');
        if (! file_exists($downloadDir)) {
            mkdir($downloadDir, 0755, true);
        }

        // 1. CSV
        $csvPath = "{$downloadDir}/cathedral_students_credentials.csv";
        $csvFile = fopen($csvPath, 'w');
        fputcsv($csvFile, ['Global S/N', 'Class & Arm', 'Full Name', 'Admission Number (Username)', 'Default Password', 'Internal Email', 'Date of Birth', 'Portal Status', 'Signature']);

        foreach ($studentsBySection as $section => $students) {
            foreach ($students as $s) {
                fputcsv($csvFile, [
                    $s['sn'],
                    $section,
                    $s['name'],
                    $s['admission_number'],
                    $s['password'],
                    $s['email'],
                    $s['dob'],
                    $s['portal_active'],
                    '', // Empty for physical signature
                ]);
            }
        }
        fclose($csvFile);
        $this->info("CSV generated: {$csvPath}");

        // 2. Excel
        $this->generateExcel($studentsBySection, "{$downloadDir}/cathedral_students_credentials.xlsx", $sessionName, $schoolName);
        $this->info("Excel generated: {$downloadDir}/cathedral_students_credentials.xlsx");

        // 3. PDF
        $this->generatePdf($studentsBySection, "{$downloadDir}/cathedral_students_credentials.pdf", $sessionName, $schoolName, $portalUrl);
        $this->info("PDF generated: {$downloadDir}/cathedral_students_credentials.pdf");
    }

    private function generateExcel(array $studentsBySection, string $filePath, string $sessionName, string $schoolName): void
    {
        $spreadsheet = new Spreadsheet();

        // Master Sheet: All Students
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('All Students');

        $headers = ['S/N', 'Class & Arm', 'Student Full Name', 'Admission Number (Username)', 'Default Password', 'Internal Email', 'Date of Birth', 'Portal Status', 'Student Signature'];
        $sheet->fromArray($headers, null, 'A1');

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '3D2606']], // Cathedral Brown/Gold
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];
        $sheet->getStyle('A1:I1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(28);

        $rowIdx = 2;
        foreach ($studentsBySection as $section => $students) {
            foreach ($students as $s) {
                $sheet->fromArray([
                    $s['sn'],
                    $section,
                    $s['name'],
                    $s['admission_number'],
                    $s['password'],
                    $s['email'],
                    $s['dob'],
                    $s['portal_active'],
                    '',
                ], null, "A{$rowIdx}");

                $sheet->getStyle("A{$rowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("B{$rowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("D{$rowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("E{$rowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("G{$rowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("H{$rowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getRowDimension($rowIdx)->setRowHeight(20);

                $rowIdx++;
            }
        }

        $sheet->getStyle("A1:I" . ($rowIdx - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Individual Section Sheets
        foreach ($studentsBySection as $section => $students) {
            $sectionSheet = $spreadsheet->createSheet();
            $safeTitle = substr(str_replace([':', '\\', '/', '?', '*', '[', ']'], '', $section), 0, 31);
            $sectionSheet->setTitle($safeTitle);

            $sectionHeaders = ['S/N', 'Student Full Name', 'Admission Number', 'Default Password', 'Date of Birth', 'Portal Status', 'Student Signature', 'Date Signed'];
            $sectionSheet->fromArray($sectionHeaders, null, 'A1');
            $sectionSheet->getStyle('A1:H1')->applyFromArray($headerStyle);
            $sectionSheet->getRowDimension(1)->setRowHeight(26);

            $cRow = 2;
            foreach ($students as $s) {
                $sectionSheet->fromArray([
                    $s['section_sn'],
                    $s['name'],
                    $s['admission_number'],
                    $s['password'],
                    $s['dob'],
                    $s['portal_active'],
                    '',
                    '',
                ], null, "A{$cRow}");

                $sectionSheet->getStyle("A{$cRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sectionSheet->getStyle("C{$cRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sectionSheet->getStyle("D{$cRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sectionSheet->getStyle("E{$cRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sectionSheet->getStyle("F{$cRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sectionSheet->getRowDimension($cRow)->setRowHeight(22);

                $cRow++;
            }

            $sectionSheet->getStyle("A1:H" . ($cRow - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            foreach (range('A', 'H') as $col) {
                $sectionSheet->getColumnDimension($col)->setAutoSize(true);
            }
        }

        $spreadsheet->setActiveSheetIndex(0);
        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);
        $spreadsheet->disconnectWorksheets();
    }

    private function generatePdf(array $studentsBySection, string $filePath, string $sessionName, string $schoolName, string $portalUrl): void
    {
        $html = view('pdf.students-credentials-roster', [
            'schoolName'      => $schoolName,
            'studentsByClass' => $studentsBySection,
            'sessionName'     => $sessionName,
            'generatedDate'   => now()->format('F j, Y'),
            'portalUrl'       => $portalUrl,
        ])->render();

        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('A4', 'portrait');
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled'      => true,
            'defaultFont'          => 'sans-serif',
        ]);

        $pdf->save($filePath);
    }

    private function getStudentSections(): array
    {
        return [
            'SS 3' => [
                'classroom' => 'SS3',
                'arm'       => null,
                'students'  => [
                    "ABDULLASISI FATHIA ATINUKE",
                    "ADEBANJO OLUWATIMILEHIN MALIK",
                    "ADEBIYI TEMIDAYO SEMILORE",
                    "ADEBOTE OLUSEGUN SAMUEL",
                    "ADEGBOHUN ADERINSOLA ESTHER",
                    "ADETAYO PRAISE AYOMIDE",
                    "AJAH-IGWE PRECIOUS CHIDINDU",
                    "ALAYA KHALID OLAMIDE",
                    "AMUSA AKEEM ADEYEMI",
                    "BALOGUN PIUS IYANUOLUWA",
                    "HUSSAIN ENIOLA HUSSAINAT",
                    "HUSSAIN IBUKUN HUSSAIN",
                    "IDONGESIT ISREAL SUNDAY",
                    "IJAODOLA GIFT ABIGAEL",
                    "JESUTOFUNMI VICTORIA OLUWABUKUNMI",
                    "KUJORE MORIYANU ABAYOMI",
                    "MAKANJUOLA HALEEM OLASILE",
                    "MOGBONJUBOLA OPEYEMI DARASIMI",
                    "MUHEEB FADLULLAH OLALEKAN",
                    "ODUBELA OLUWADARASIMI VICTRESS",
                    "ODUWEKU IFEOLUWA MATTHEW",
                    "OGUNBERU REBECCA OLUWAPELUMI",
                    "OGUNLEYE OYINKANSOLA FUNKE",
                    "OLADAPO MORDECAI OLUWAGBEMIGA",
                    "OLADEPO ESTHER ABIMBOLA",
                    "OLAFIMIHAN AYOMIDE ABRAHAM",
                    "OLAGUNJU OGOOLUWATANYIMIKA JOSHUA",
                    "OLAIDE DAMILOLA OSELUMESE",
                    "OLUFOWOBI ENIOLA AMINAT",
                    "OSAGIE OROBOSA PRAISE",
                    "OSISANYA BOLUWATIFE DAVID",
                    "SOLAJA KEHINDE ADEDOLAPO",
                    "SOLAJA TAIWO JADESOLA",
                    "TIAMIYU DANIEL GBADURAMI",
                ],
            ],
            'SS 2 Gold' => [
                'classroom' => 'SS2',
                'arm'       => 'Gold',
                'students'  => [
                    "ABOLAJI AKOREDE",
                    "ADEBANJO ABDULSALAM",
                    "ADENIYI DARASIMI",
                    "ADEOYE GLORY",
                    "AMODEMAJA FUHAD",
                    "ANTHONY CHUKWUKA",
                    "AYEDUN FEYISARA",
                    "BELLO RIHANAT",
                    "CHIKERE JOSHUA",
                    "DAVID AMARACHI",
                    "EHIS ESTHER",
                    "GANIU TIMILEHIN",
                    "IFEBANJO BLESSING",
                    "LAWAL SAMUEL",
                    "OLADEPO FAVOUR",
                    "OLADIPUPO ROFIAT",
                    "OLALEYE SUCCESS",
                    "OLUWAYEMI TOBILOBA",
                    "ONAKOYA HAZZAN",
                    "OSIBOYEJO MISTURAH",
                ],
            ],
            'SS 2 Brown' => [
                'classroom' => 'SS2',
                'arm'       => 'Brown',
                'students'  => [
                    "Abdussalam Fadeela Temilola",
                    "Adeboyejo Fiyinfoluwa Olamide",
                    "Adegbohun Aderinbola Samuel",
                    "Adekoya Treasure Adejobi",
                    "Adekoya Baliqis Oluwanifemi",
                    "Ademulegun Oluwadarasimi Daniel",
                    "Adetola Opeyemi Faridat",
                    "Adewale Adekunmisi Emmanuel",
                    "Aladekugbe Oluwasemilore favour",
                    "Amusa Ayodele Rhoda",
                    "Badejo Gbemisola Moyinoluwa",
                    "Durojaiye Oluwaseun Paul",
                    "Falola Oluwakorede Joseph",
                    "Falola Testimony Omolade",
                    "Francis Treasure Onome",
                    "Hamzat Saidat Toluwalope",
                    "Hazzan Kareem Oriola",
                    "Oboh Amanda Osarumen",
                    "Odusote Emmanuel Ifeoluwa",
                    "Okoro Treasure Ugochi",
                    "Olaide Ireoluwatomiwa Ehiremen",
                    "Olaitan Caleb Oluwajomiloju",
                    "Olatunji Deborah Oluwadarasimi",
                    "Olawepo Diekololuwa Elizabeth",
                    "Oluwadiya Goodness Ezekiel",
                    "Osunkoya David Testimony",
                    "Oyebanjo Oluwashekemi Oluwanifemi",
                    "Solarin Clara Adewunmi",
                    "Sulaimon Sekinat Oluwafeyikemi",
                    "Taiwomi Oyinlola Johanna",
                    "Yusuf Abeebat",
                    "Omotere Ireoluwa",
                ],
            ],
            'SS 1 Gold' => [
                'classroom' => 'SS1',
                'arm'       => 'Gold',
                'students'  => [
                    "Adebanjo Moyin",
                    "Adekoya Aishat",
                    "Adekoya Esther",
                    "Adeleye Omorinmade",
                    "Akintunde Ifeoluwa",
                    "Alebiosu Waris",
                    "Alli Ridwan",
                    "Awosola Abdulrahmon",
                    "Banwo Oluwasemilore",
                    "Dada Gloria",
                    "Elegbede Korede",
                    "Fashikomi Jomiloju",
                    "Gafar Lateefat",
                    "Jacob Obanijesu",
                    "Jimoh Rauf",
                    "Muhammed Umar",
                    "Obieneh Justina",
                    "Ogunde Martins",
                    "Oguniji Abduljalal",
                    "Olaniyi Pemisire",
                    "Oloro Samuel",
                    "Olubido Aliyah",
                    "Salami Jariat",
                    "Salau Sofiat",
                    "Tijani Semilogo",
                ],
            ],
            'SS 1 Brown' => [
                'classroom' => 'SS1',
                'arm'       => 'Brown',
                'students'  => [
                    "Adetola Nelson",
                    "Adewale Wisdom",
                    "Archsmith khaleed",
                    "Awo Samuel",
                    "Dada Oluwabukunmi",
                    "Idongest Annointing",
                    "Jesutofunmi Emmanuel",
                    "Kajopaiye Adesola",
                    "Magbonjubola Temiloluwa",
                    "Makanjuola Aliyah",
                    "Obadein Faith",
                    "Odedeyi Feranmi",
                    "Odekunle Teniola",
                    "Ogooluwa Oluwole",
                    "Olusoga Samuel",
                    "Ramon Haliya",
                    "Salmon Rahmotallahi",
                    "Tiamiyu Temiloluwa",
                    "Uzo-Philip Sweetness",
                ],
            ],
            'JSS 3 Gold' => [
                'classroom' => 'JSS3',
                'arm'       => 'Gold',
                'students'  => [
                    "ABASS OLAMIDE FATIMOH",
                    "ABOLAJI PAUL OLUWAPAMILERIN",
                    "ADEBANJO SOFIAT OLUWADUNMININU",
                    "ADEKOYA INIOLUWA EMMANUELLA",
                    "ADELEYE OMORINBOLA BOLATITO",
                    "ADENUGBA FISAYOMI",
                    "ADESEGUN DARASIMI OJUMIRAYO",
                    "ADESOJI HAPPY SUCCEED",
                    "AINA ENOCH OLUWADARASIMI",
                    "ALAUSA RIANAT AYOMIDE",
                    "ALLI RODIAT OLUFUNKE",
                    "AZEEZ AL-AMEEN BOLUWATIFE",
                    "CHRISTIAN BLESSING CHIAMAKA",
                    "DANIEL OLUWAPAMILERIN FORTUNE",
                    "EMMANUEL PRECIOUS EJIRO-OGHENE",
                    "IJAODOLA ESTHER OREOLUWA",
                    "KASHETTU HALIYAH OYINKANSOLA",
                    "KEHINDE EMMANUEL",
                    "NWAJI PRAISE UZO",
                    "NWOSU MIRACLE",
                    "ODUSANYA OYINAADE MICHAEL",
                    "OGUNFUWA PRAISE AYOMIDE",
                    "OGUNLEYE DAMOLA MORENIKEJI",
                    "OGUNMULELE GABRIEL OLUWANISHOLA",
                    "OKORONKWO PRAISE",
                    "OLABANJI DAVID OLUWASEUN",
                    "OLALEYE BLESSING ARINOLA",
                    "OLUGBEMI ROLAND CALEB",
                    "OLUSOGA DANIEL OLUWASEGUN",
                    "OSHO BLESSING",
                    "SALAU DARASIMI HABEEB",
                    "SHOBAYO SAMUEL TESTIMONY",
                    "SIKIRU JOSHUA OMOSUNKANMI",
                    "SULAIMON OLUWAFIKAYOMI ALIYAT",
                ],
            ],
            'JSS 3 Brown' => [
                'classroom' => 'JSS3',
                'arm'       => 'Brown',
                'students'  => [
                    "ADEBAYO MICHEAL OLUWASEMILORE",
                    "ADEGBOLA SULAIMON ADEWALE",
                    "ADEKOYA FAVOUR OLUWAFISAYOMI",
                    "ADEMOLA ISRAEL OLUWAFERANMI",
                    "ADEODUN TOLUWALOGO OLAMIONIPEKUN",
                    "ADETUWO IREOLUWA ERIOLUWA",
                    "AKANBI AYOMIDE MUIZ",
                    "ALADEKUGBE TEMILADE",
                    "DAUDA HALIYAT ADESEWA",
                    "DAWODU AZEEZAT OLUWAPAMILERIN",
                    "EGBAIYELO SOLOMON IMOLEAYO",
                    "FALOLA EMMANUEL OLUWATOSIN",
                    "FALOLA OLUWAFIKAYOMI FEYINTOLA",
                    "FOLARIN SOFIAT OLAMIDE",
                    "ODUNEYE OLAKUNRE ZAINAB",
                    "OGUNJI BEST ELEBIOMAYO",
                    "OGUNPITAN JOHN",
                    "OKUSANYA ADERAYO TESTIMONY",
                    "OLADIPUPO DANIELLAADEBUNMOLA",
                    "OLADOYE JOMILOJU",
                    "OLAGUNJU DEMILADE DANIEL",
                    "OLATUNJI OLUWASEUN WITHGOD",
                    "OLOWOGBEMI TESTIMONY OMOLERE",
                    "OLUBADEWA ENOCH FIYINFOLUWA",
                    "OLUSOGA OLUWASHINAAYOMI OLUWADARASIMI",
                    "OMOTAYO OLUWAPAMILERIN AYOMIDE",
                    "OMOTERE DESIREOLUWA GLORIA",
                    "SALAMI NIMOTALAHI DARASIMI",
                    "SAMSON GIFT CHINAZA",
                    "YUSUF HALEEYAH OYINWONUOLA",
                ],
            ],
            'JSS 2 Gold' => [
                'classroom' => 'JSS2',
                'arm'       => 'Gold',
                'students'  => [
                    "1.Abe-David Olaniyi     2024K211110008",
                    "2.Adekoya Zainab        2024Q210160018",
                    "3.Adebola Daniella Moranugba  2021K210050317",
                    "4.Adegbohun Aderinmola Daniel   2024K210380016",
                    "5.Adegbuyi Michael Oluwadamilare    2021Q210520077",
                    "6.Ikotun Ganiyat        2025K240600029",
                    "7.Akeyan Akorede Al-Ameen    2024Q211010005",
                    "8.Akamo Oluwasemilore David",
                    "9.Nwannekezi    Precious  2024J210940096",
                    "10.Aluko Wuraola Rachel     2021Q210660083",
                    "11.Fagbuyi Daniel Oluwasemilore  2023Q211320022",
                    "12.Folorunsho Sophia Ayomide  2021Q210520209",
                    "13.Igwe Emmanuel Daniel",
                    "14.Ilesanmi David Mayowa",
                    "15.Ishola Al-Ameen Eniola 2024K210650005",
                    "16.Nwafor Rosemary Ezinne",
                    "17.Oladoye Iseoluwa Praise   2021K211110355",
                    "18.Oguleye Adedoyin Moriyanu  2021K211500327",
                    "19.Olatunji Favour Olasubomi  2021Q211100032",
                    "20.Olumide Lydia Precious 2024Q211510038",
                    "21.Onafowokan Raymond Ayomide  2021K211880079",
                    "22.Peter Faith Chineye     2021Q210590018",
                    "23.Shittu Fawaz Oluwafikayomi 2024Q21066001",
                ],
            ],
            'JSS 2 Brown' => [
                'classroom' => 'JSS2',
                'arm'       => 'Brown',
                'students'  => [
                    "1.Adebote Anuluwapo   Elizabeth    2021K203800129",
                    "2.Adeoye Precious Oluwafikayomi   2021K210380053",
                    "3.Adesanya Philip Pemisire   2023K211110094",
                    "4.Adetunji Adeire Khalid    2024K210380616",
                    "5.Anyibe Gabriel Chiemerie  2024K21850008",
                    "6.Aranju Wuraola Blessing 2022K210380025",
                    "7.Ayedun Testimony Darasimi 2023Q211640016",
                    "8.Bamidele Azeezat Anjolaoluwa   2021K210380077",
                    "9.Odedeyi Ifeoluwa Tijesunimi2021K211690084",
                    "10.Ogunji Winner  Oluwabamise 2024K210380617",
                    "11.Ogunleye Adesewa Olawunmi 2021Q210520040",
                    "12.Olaleye Oluwasetemipe 2021K210050098",
                    "13.Oloro Isreal Olamilekan  2023Q211320040",
                    "14.Olusola Ojumirayo Walikat  2024K210380020",
                    "15.OnadujaFiyinfoluwa David  2021K211510282",
                    "16.Oni Joshua Jomiloju",
                    "17.Otun Inioluwa  Abigail  2024K210380014",
                    "18.Salaudeen Maryam Oluwadarasimi   2021K210050214",
                    "19.Salman Abdulrahman Olasunkanmi2021Q210160123",
                    "20.Sambakiu Barakat  Olayinka 024K210380013",
                    "21.Samuel Victoria Oluwamurewa 2021Q211010043",
                    "22.Solarin Davida Adeola 2023K212250008",
                    "23Uzo-Philip Treasure Kalinwana 2023K210380024",
                    "24.Wasiu Abdulmalik Omogbolahan 2021K21110225",
                    "25Alayo Fikayomi Olamilekan 2021K210050132",
                    "26. Agi Ruth Ogheneigbulu 2023K210380004",
                    "27. Ade banjo oluwasemilore Abdulmateen  2024Q211510047",
                ],
            ],
            'JSS 1 Gold' => [
                'classroom' => 'JSS1',
                'arm'       => 'Gold',
                'students'  => [
                    "Adebote Harrison Olayikanmi — 21-05-2015",
                    "Adedeji Okiola Grace — 20-09-2013",
                    "Adegbuyi Adebayo Oluwasemilore — 01-09-2015",
                    "Adekoya Adewunmi Fawwaz — 23-03-2016",
                    "Afolorunsho Hephzibah Silver — 25-02-2017",
                    "Akintunde Toluwalase Omowunmi — 16-01-2016",
                    "Alabi Ayomide Amos — 24-01-2014",
                    "Aladekugbe Marvelous Ifeoluwa — 29-07-2015",
                    "Are Grace Oluwafirefunmi 26-05-2016",
                    "Babalola Daniel Oluwasinayomi — 21-08-2015",
                    "Daniel Emmanuel Oluwademilade — 18-12-2015",
                    "Eribake Gabriel Semilore — 09-06-2015",
                    "Famuyiwa Toluwani Jemima — 21-11-2016",
                    "Fajana Precious Olamide — 29-05-2015",
                    "Folarin Soliat Oluwatofunmi — 10-06-2016",
                    "Gbadoku Enoch Bright — 15-05-2015",
                    "Ikenna Divine Chinacherem — 01-08-2015",
                    "Kazeem Adesina Oluwasindara — 04-08-2015",
                    "Odu Marvelous Happy — 12-12-2012",
                    "Ogunde Danielah Chiamaka — 01-09-2015",
                    "Oguntunji Balikis",
                    "Olabanji Oluwadarasimi — 25-02-2016",
                    "Olaitan Israel Adeoluwa — 15-02-2016",
                    "Omelaja Benjamin Darasimi — 12-12-2015",
                    "Oyegunle Oluwashindara Wuraola — 03-05-2016",
                    "Oyewole Boluwatife Esther — 24-01-2015",
                    "Saibu Faruq — 19-06-2016",
                    "Shitta Mary Oreoluwa — 12-11-2014",
                    "Solomon Thankgod — 05-05-2011",
                    "Yusuf Fathia",
                    "Yusuf Omotoyosi Christiana — 06-11-2015",
                ],
            ],
            'JSS 1 Brown' => [
                'classroom' => 'JSS1',
                'arm'       => 'Brown',
                'students'  => [
                    "Adeniyi Oluwamayowa Emmanuel — 23-02-2015",
                    "Adenuga Fortune Adeogo — 01-12-2015",
                    "Adesina Adesewa Naomi — 05-09-2015",
                    "Adesoye Dunamis Ayomide — 01-10-2015",
                    "Alimi Rihanat Oluwanifemi — 13-08-2015",
                    "Aladekugbe Tomisin 13-10-2016",
                    "Aregbe Rahman — 02-05-2015",
                    "Bakare Ayomide Yusuf 19-11-2016",
                    "Chikere Ada Joy — 03-10-2015",
                    "Durojaiye Daniel Oluwasegun — 12-05-2015",
                    "Gbadamosi Fawaz Olamilekan — 04-07-2016",
                    "Hameed Rahat Oluwafunmike — 11-02-2016",
                    "Ibrahim Aminat Iseoluwa — 02-10-2015",
                    "Ibrahim Darasimi Daniel — 04-11-2015",
                    "Ige Heritage Adebisi — 25-03-2016",
                    "Kayode Olamiposi Joshua",
                    "Oboh Osasere Aaron — 07-03-2015",
                    "Odunsi Deborah Olamide — 04-07-2015",
                    "Olatunji Eniola Dominion — 10-07-2016",
                    "Olawepo Desola Eunice — 03-01-2016",
                    "Olusoga Ameerah Iremide — 16-05-2016",
                    "Oshinaya Oluwadarasimi Precious — 30-10-2015",
                    "Paul Hosanna Peculiar — 04-07-2015",
                    "Sanwiyu Oluwalogbon Praise — 14-08-2014",
                    "Sofuye Ayomide Damilola — 09-11-2015",
                    "Sulaiman Aishat Irewolemi — 19-01-2015",
                ],
            ],
        ];
    }
}
