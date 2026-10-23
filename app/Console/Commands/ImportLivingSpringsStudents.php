<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Classroom;
use App\Models\Session;
use App\Models\Setting;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Tenant;
use App\Services\StudentAccountService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ImportLivingSpringsStudents extends Command
{
    protected $signature = 'import:living-springs-students {--tenant=}';

    protected $description = 'Import Living Springs students across JSS1-SS3, assign LRS credentials and portal access, and generate downloadable sign-off sheets';

    public function handle(StudentAccountService $accountService): int
    {
        $tenantId = $this->option('tenant');
        if (! $tenantId) {
            $tenantId = Tenant::where('id', 'livingsspring')->exists() ? 'livingsspring' : 'wonders';
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

        $schoolName = Setting::where('key', 'school_name')->value('value') ?: 'LivingSpring Royal Seed College';
        $domain = $tenant->domains->first()?->domain ?? 'livingsspring.duckdns.org';
        $scheme = str_contains($domain, 'test') ? 'http' : 'https';
        $portalUrl = "{$scheme}://{$domain}/student/login";

        $classMapping = [
            'SS 3'  => Classroom::where('name', 'SS3')->orWhere('name', 'SS 3')->first(),
            'SS 2'  => Classroom::where('name', 'SS2')->orWhere('name', 'SS 2')->first(),
            'SS 1'  => Classroom::where('name', 'SS1')->orWhere('name', 'SS 1')->first(),
            'JSS 3' => Classroom::where('name', 'JSS3')->orWhere('name', 'JSS 3')->first(),
            'JSS 2' => Classroom::where('name', 'JSS2')->orWhere('name', 'JSS 2')->first(),
            'JSS 1' => Classroom::where('name', 'JSS1')->orWhere('name', 'JSS 1')->first(),
        ];

        foreach ($classMapping as $label => $class) {
            if (! $class) {
                $this->error("Classroom for '{$label}' not found.");
                return 1;
            }
        }

        $rawStudents = $this->getRawStudentData();

        $admYear = (string) ($activeSession->start_year ?: now()->format('Y'));
        $hasUserSurname = \Illuminate\Support\Facades\Schema::hasColumn('users', 'surname');
        $hasStudentFullName = \Illuminate\Support\Facades\Schema::hasColumn('students', 'full_name');

        $processedStudents = [];
        $totalCreated = 0;
        $totalUpdated = 0;
        $globalSequence = 1;

        DB::beginTransaction();
        try {
            foreach ($rawStudents as $classLabel => $names) {
                $classroom = $classMapping[$classLabel];
                $this->info("Processing {$classLabel} (" . count($names) . " students)...");

                // Sort alphabetically per class (Surname first, A to Z)
                usort($names, function ($a, $b) {
                    $cleanA = trim(preg_replace('/\s+/', ' ', rtrim($a, '.')));
                    $cleanB = trim(preg_replace('/\s+/', ' ', rtrim($b, '.')));
                    return strcasecmp($cleanA, $cleanB);
                });

                $classSn = 1;
                foreach ($names as $rawName) {
                    $cleanName = trim(preg_replace('/\s+/', ' ', rtrim($rawName, '.')));
                    $password = $this->derivePassword($cleanName);

                    $parts = preg_split('/\s+/', $cleanName);
                    $surname = $parts[0] ?? '';
                    $firstName = $parts[1] ?? '';
                    $otherName = count($parts) > 2 ? implode(' ', array_slice($parts, 2)) : null;

                    // Check if student with exact name and enrollment in this class already exists
                    $student = Student::where(function ($q) use ($cleanName, $surname, $firstName, $hasUserSurname, $hasStudentFullName) {
                        if ($hasStudentFullName) {
                            $q->where('full_name', $cleanName);
                        }
                        $q->orWhereHas('user', function ($uq) use ($surname, $firstName, $cleanName, $hasUserSurname) {
                            if ($hasUserSurname) {
                                $uq->where('surname', $surname)->where('first_name', $firstName);
                            } else {
                                $uq->where('name', $cleanName);
                            }
                        });
                    })
                    ->whereHas('enrollments', fn ($q) => $q->where('classroom_id', $classroom->id)->where('session_id', $activeSession->id))
                    ->first();

                    if (! $student) {
                        $studentData = [
                            'status'           => 'active',
                            'is_portal_active' => true,
                            'activated_at'     => now(),
                        ];
                        if ($hasStudentFullName) {
                            $studentData['full_name'] = $cleanName;
                        }

                        $student = Student::create($studentData);

                        StudentEnrollment::create([
                            'student_id'   => $student->id,
                            'classroom_id' => $classroom->id,
                            'session_id'   => $activeSession->id,
                        ]);

                        $totalCreated++;
                    } else {
                        $totalUpdated++;
                    }

                    // Enforce "LRSC" prefix followed by admission year and 3-digit zero-padded number (e.g. LRSC2026001)
                    $admNumber = 'LRSC' . $admYear . str_pad((string) $globalSequence, 3, '0', STR_PAD_LEFT);
                    $student->forceFill([
                        'admission_number' => $admNumber,
                    ])->saveQuietly();

                    // Create or update login account
                    if (method_exists($accountService, 'createOrLinkUser')) {
                        $user = $accountService->createOrLinkUser($student, $surname, $firstName, $otherName, $password);
                    } else {
                        $user = $accountService->createOrUpdateLogin($student, $password);
                    }

                    $processedStudents[$classLabel][] = [
                        'sn'               => $classSn++,
                        'student_id'       => $student->id,
                        'name'             => $cleanName,
                        'admission_number' => $student->admission_number,
                        'email'            => $user->email,
                        'password'         => $password,
                        'classroom'        => $classLabel,
                        'portal_active'    => 'Active',
                    ];

                    $globalSequence++;
                }
            }

            DB::commit();
            $this->info("Database transactions committed successfully! Created: {$totalCreated}, Updated: {$totalUpdated}");
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error("Error importing students: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }

        // Generate Export Files
        $this->generateExports($processedStudents, $activeSession->name, $schoolName, $portalUrl);

        return 0;
    }

    private function derivePassword(string $fullName): string
    {
        $cleaned = trim(preg_replace('/\s+/', ' ', $fullName));
        if (preg_match('/^([a-zA-Z]+)/', $cleaned, $matches)) {
            $firstWord = $matches[1];
        } else {
            $parts = explode(' ', $cleaned);
            $firstWord = preg_replace('/[^a-zA-Z]/', '', $parts[0] ?? 'Student');
        }

        return ucfirst(strtolower($firstWord)) . '123';
    }

    /**
     * @param array<string, array<int, array<string, mixed>>> $studentsByClass
     */
    private function generateExports(array $studentsByClass, string $sessionName, string $schoolName, string $portalUrl): void
    {
        $downloadDir = public_path('downloads');
        if (! file_exists($downloadDir)) {
            mkdir($downloadDir, 0755, true);
        }

        // 1. Generate CSV
        $csvPath = "{$downloadDir}/living_spring_students_credentials.csv";
        $csvFile = fopen($csvPath, 'w');
        fputcsv($csvFile, ['Class', 'S/N', 'Full Name', 'Admission Number (Username)', 'Password', 'Internal Email', 'Portal Access', 'Signature']);

        foreach ($studentsByClass as $class => $students) {
            foreach ($students as $s) {
                fputcsv($csvFile, [
                    $class,
                    $s['sn'],
                    $s['name'],
                    $s['admission_number'],
                    $s['password'],
                    $s['email'],
                    $s['portal_active'],
                    '', // Empty for physical signature
                ]);
            }
        }
        fclose($csvFile);
        $this->info("CSV generated: {$csvPath}");

        // 2. Generate Excel (.xlsx) with Master + per-class sheets
        $this->generateExcel($studentsByClass, "{$downloadDir}/living_spring_students_credentials.xlsx", $sessionName, $schoolName);
        $this->info("Excel generated: {$downloadDir}/living_spring_students_credentials.xlsx");

        // 3. Generate Printable PDF Sign-Off Roster
        $this->generatePdf($studentsByClass, "{$downloadDir}/living_spring_students_credentials.pdf", $sessionName, $schoolName, $portalUrl);
        $this->info("PDF generated: {$downloadDir}/living_spring_students_credentials.pdf");
    }

    /**
     * @param array<string, array<int, array<string, mixed>>> $studentsByClass
     */
    private function generateExcel(array $studentsByClass, string $filePath, string $sessionName, string $schoolName): void
    {
        $spreadsheet = new Spreadsheet();
        
        // Master Sheet: All Students
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('All Students');
        
        $headers = ['S/N', 'Class', 'Student Full Name', 'Admission Number (Username)', 'Default Password', 'Internal Email', 'Portal Status', 'Student Signature'];
        $sheet->fromArray($headers, null, 'A1');
        
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A8A']], // Dark Navy
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];
        $sheet->getStyle('A1:H1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(28);

        $rowIdx = 2;
        $globalSn = 1;
        foreach ($studentsByClass as $class => $students) {
            foreach ($students as $s) {
                $sheet->fromArray([
                    $globalSn++,
                    $class,
                    $s['name'],
                    $s['admission_number'],
                    $s['password'],
                    $s['email'],
                    $s['portal_active'],
                    '', // Empty for physical signature
                ], null, "A{$rowIdx}");

                // Center align specific columns
                $sheet->getStyle("A{$rowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("B{$rowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("D{$rowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("E{$rowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("G{$rowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getRowDimension($rowIdx)->setRowHeight(20);

                $rowIdx++;
            }
        }

        $sheet->getStyle("A1:H" . ($rowIdx - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Add Individual Class Sheets
        foreach ($studentsByClass as $class => $students) {
            $classSheet = $spreadsheet->createSheet();
            $classSheet->setTitle($class);

            $classHeaders = ['S/N', 'Student Full Name', 'Admission Number', 'Password', 'Portal Status', 'Student Signature', 'Date Signed'];
            $classSheet->fromArray($classHeaders, null, 'A1');
            $classSheet->getStyle('A1:G1')->applyFromArray($headerStyle);
            $classSheet->getRowDimension(1)->setRowHeight(26);

            $cRow = 2;
            foreach ($students as $s) {
                $classSheet->fromArray([
                    $s['sn'],
                    $s['name'],
                    $s['admission_number'],
                    $s['password'],
                    $s['portal_active'],
                    '', // Empty for sign
                    '', // Date signed
                ], null, "A{$cRow}");

                $classSheet->getStyle("A{$cRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $classSheet->getStyle("C{$cRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $classSheet->getStyle("D{$cRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $classSheet->getStyle("E{$cRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $classSheet->getRowDimension($cRow)->setRowHeight(22);

                $cRow++;
            }

            $classSheet->getStyle("A1:G" . ($cRow - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            foreach (range('A', 'G') as $col) {
                $classSheet->getColumnDimension($col)->setAutoSize(true);
            }
        }

        $spreadsheet->setActiveSheetIndex(0);
        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);
        $spreadsheet->disconnectWorksheets();
    }

    /**
     * @param array<string, array<int, array<string, mixed>>> $studentsByClass
     */
    private function generatePdf(array $studentsByClass, string $filePath, string $sessionName, string $schoolName, string $portalUrl): void
    {
        $html = view('pdf.students-credentials-roster', [
            'schoolName'      => $schoolName,
            'studentsByClass' => $studentsByClass,
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

    /**
     * @return array<string, array<int, string>>
     */
    private function getRawStudentData(): array
    {
        return [
            'SS 3' => [
                'Ubah Favour CHIZITERE',
                'Adigwe Angel',
                'Oshowale ANJOLA Favour',
                'Onovo Mercy UGOCHUKWU',
                'Iwuegbu Prosper AMARACHI',
                'Akalonu Mmesoma DANIELLA',
                'Adu Ejiroghene CHRISTABELLE',
                'Orji Adjutant',
                'Agukwe David CHUKWUEBUKA',
                'Agukwe Daniel CHUKWUEMEKA',
                'Adeniran Olaoluwa JAMES',
                'Okubotin Simeon',
                'Idowu Esther',
                'Alozie David CHINEDU',
                'Ikechukwu Ifebuchechukwu EDNA',
                'Adebayo Inioluwa PRECIOUS',
                'Philip Empress',
                'Ibeji Destiny EHIME CHUKWUKA',
                'Ifeanyi Osuji Treasure',
                'ANIELOKA CHIDERA DORIS',
            ],
            'SS 2' => [
                'Suleman Favour',
                'Okafor Amarachi PRINCESS',
                'Echebiri Uzochi KENNEDY',
                'Oliseyenum Dominion OVIE',
                'Solomon Joshua CHIDIEBUBE',
                'Duku Daniel',
                'Alozie Nathaniel CHIGOZIE',
                'Akpata chioma VIVIAN',
                'Dozie Odunze Somachi',
                'Onovo  NMACHUKWU Precious',
                'Chinweuba Oluch ESTHERi',
                'Emmanuel Chidubem',
                'Ifeanyi Ezeh Chiamaka PRISCA',
                'Odu Emmanuel',
                'Nwachukwu Munachimso ADRIEL',
                'Adigwe ANTHONIA Chisom',
                'Adewole  Jomiloju HULDA',
                'Obi Kamsiyochukwu',
                'Alagabi Oluwadarasimi',
                'ROTIMI  TOLUWALASE ALEXANDER',
                'INNOCENT MARYANN CHINECHEREM',
                'OKEEGBE HADASSAH AYOOLUWA',
                'OLIOBI IFECHUKWU MICHAEL',
                'ADEBIYI ABDULSAMAD',
                'OLAYEMI OLUWAJUWON',
            ],
            'SS 1' => [
                'Ajadi Deborah OLUWAFIKUNAYOMI',
                'Faluyi Olamiposi MICHAEL',
                'Oyetunde Jeremiah',
                'Oshowale MODUPE LIGHT CHIOMA',
                'Adeniran Ifeoluwa MARY',
                'Lawal Faridah OPEYEMI',
                'Chimezie Delight OBICHUKWU',
                'Enyinnaya Chidubem CALEB',
                'Nwachukwu Deborah CHIAMAKA',
                'Ezemezue Ifechukwu SAMUEL',
                'ELEOGU KAMSIYOCHUKWU EMMANUEL',
                'Duku David OGHENEMEGA',
                'Onunkwo Jennifer ONYINYE',
                'Uzochi Precious CHINOMSO',
                'Orumgbe Prosper CHUKWUEMEKA',
                'Afuye  OLUWATOSIN JESUTOLAMI Silas',
                'Ezenwaka Kamsiyochukwu MAXIMUS',
                'Aluka Bianca  ESTHER Chinonso',
                'Madubuike Mercy Onyinye',
                'Shasore Faridat OYINDAMOLA',
                'Patrick Chimaobi Delight',
                'Oliobi Onyinyechukwu ESTHER',
                'ADEJUMO DARASIMI DORCAS',
                'Ajah KamSI GIDEON',
                'AJADI EMMANUEL MOBOLAJI',
            ],
            'JSS 3' => [
                'PHILEMON NOBLE GINIKACHUKWU',
                'OKAKA RHEMA ONOSETALE',
                'TAIWO OLUWATOYOSI PEARL',
                'OBIEFUNA IFECHUKWU VICTORIA',
                'Oji  AMA Nnenna MERCY',
                'Ofoma Bethel CHIMAZURU',
                'Etuk Utomobong IDORENYEN',
                'Echebiri Chibueze KOSISOCHUKWU',
                'Itoro Faith BRIGHT',
                'Amuneke Emmanuel KAMSIYOCHUKWU',
                'Nwachukwu Ezinwa SOPHIA',
                'Eze Onyeabo MARTINS',
                'Maduka Chisom MIRACLE',
                'Chukwuma Greatness',
                'Chimezie Miracle NNENNA',
                'Chika Ebube Peace',
                'Allison Victor',
                'Aramide Oluwafifebamilo ALPHONSUS',
                'Chukwuneta Chisom EMMANUELLA',
                'Eze Samuel ONYEKACHI',
                'Ganiyu Ruth GANIYAT',
                'Idowu Gift OIZA',
                'Madu KAMSIYONNA DIVINE',
                'Mufumei Ifechukwude LORAINE',
                'Okubotin Favour PREKIZIMOR',
                'Ifeanyi-Osuji Cherish CHIEMELA',
                'Isima Lolo Helen',
                'Faluyi Tioluwalase MATTHEW',
                'Aderounmu Victor Damilola',
                'Okolo James IKEM',
                'Abdulkareem Aminat Omobayonle',
                'Uzochukwu Collins Obinna',
                'Okoli Esther Mmachukwume',
                'STEPHEN MENTOR CHIBUIKE',
                'OLAYEMI JANET OLUWAPELUMI',
                'AMEKE ESTHER.',
            ],
            'JSS 2' => [
                'ELEOGU CHIKAMSOEJE EMMANUELLA',
                'AKAOLISA CHRISTABEL IHUOMA',
                'PATRICK NMASINACHI REJOICE',
                'CHIBUKO HARMONY C.A',
                'DOZIE ODUNZE MHARIDAH .C.',
                'EMECHAFOR VIVIAN KASIAMOBI',
                'ADEOYE  ADETUMILARA AKIJAH',
                'EZEMEZUE NMESOMA ESTHER',
                'AKPATA CHIEMERIE GIDEON',
                'IKECHUKWU JULIET CHINECHEREM',
                'ONUNKWO CHIKAMSO DAMIAN',
                'UGWU DANIEL KOSISOCHUKWU',
                'ODUMEGWU  CHINAZA VICTORIA',
                'ETUK OTTOABASI  IDORENKEN SOCHINWEOKWU',
                'ADESOLA OLUWADARASIMI BOLUWAJI',
                'EJEMO OBANIJESU RICHARD',
                'AKALONU DAVID CHINECHEREM',
                'ANEKE DESTINY SOROMUTOCHUKWU',
                'ABDULKADIR KHADIJAT BARMA',
                'OBI MICHELLE NAESOCHUKWU',
                'AKEREDOLU OLAYEMI DANIEL',
                'OKOYEOKU CHIMAMANDA JESSICA',
                'ORUMGBE CHIOMA EMMANUELLA',
                'ROTIMI FEHINTOLA  WINIFRED',
                'ENYINNAYA DESIRE CHINEMEREM',
                'UDOJI DANIEL CHIDERA',
                'ADEDIRAN SAMUEL TOBI',
                'ADELEYE ZUBAIDAH DAMILOLA',
                'OKORO TREASURE MUNACHI',
                'OLAMIDE DEBORAH BOLUWATIFE',
                'NWIBE CHIDIEBUBE GERALD',
                'ADEBAYO PRAISE',
                'VICTOR SAMUEL OSEBHAHIEMEN',
                'NWANERI JOSHUA CHUKWUBUIKE',
                'NWANERI ELIZABETH EZINNE',
            ],
            'JSS 1' => [
                'OPARANOZIE DANIEL KAMSIYOCHUKWU',
                'OKEEGBE EMMANUEL OLUSEGUN',
                'ONA MELVIN OSAZE',
                'ABUGU CHIDERA kelvin',
                'CHUKWUDI VIVIAN',
                'NWAJIUFOR SOMTOCHUKWU NAOMI',
                'ADEJUMO DARAMOLA DANIEL',
                'FALUYI IFEOLUWA ABIGAIL',
                'AGUARE ESELE TESTIMONY',
                'GOLOBA TOHEEB OLATUNBOSIN',
                'PATRICK CHIMDIEBUBE GODBLESS',
                'EZEOLUEBUBE CHISOM EMMANUELLA',
                'ALO STEPHANIE UDUMA',
                'UZOCHI EMMANUEL',
                'AMADI DELIGHT KOSISOCHUKWU',
                'ADEOYE ARIF ADEDAMOLA',
                'OLADOYINBO AARON',
                'AKUCHUKWU GIDEON CHIKAMSO',
                'JOHNSON PROSPER KOSISOCHUKWU',
                'VICTOR SAMUEL OSEBHAHIEMEN',
                'ADELEKE TOBILOBA OPEYEMI',
                'ANIELOKA CHIKAMSO JOEL',
                'CHIMEZIE DIVINE CHUKWUEMEKA',
                'IFEANYI CHUKWUEBUKA GREAT',
                'NDUKA LIGHT CHIKAMSO',
                'CHIKA CHIGOZIE MARTIN',
                'KINGSLEY CHIDERA JORDAN',
                'ODIKE IMMACULATA CHIDERA',
                'DADA MERCY OLUWAGBEMISOLA',
                'DADA NATHANIEL OLUWADARASIMI',
                'SOYINKA GABRIEL OLUWATOMISIN',
                'JUBRIL HABIBAT',
                'ALLI MORENIKEJI KAUSARA',
                'KENNETH KELECHUKWU EWEPUTANNA',
                'OLAYEMI EMMANUEL DARASIMI',
                'ROBERTS TIOLUWANIMI',
                'OKUNOLA ARINOLA MAHFUZAN',
                'SAMUEL BEULAH CHIMAMANDA',
                'ADEYEMI GREAT EROMSELE',
                'EBUKA HERBERT MUNACHIMSO',
            ],
        ];
    }
}
