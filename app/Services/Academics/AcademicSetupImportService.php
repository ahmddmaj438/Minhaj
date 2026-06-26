<?php

namespace App\Services\Academics;

use App\Models\Course;
use App\Models\Exam\InstructorExam;
use App\Models\Major;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class AcademicSetupImportService
{
    private const SHEETS = [
        'Programs' => [
            'type' => 'program',
            'required' => ['Program Code', 'Program Name'],
            'headers' => ['Program Code', 'Program Name', 'Program Description', 'Active'],
            'examples' => [
                ['CS', 'Computer Science', 'Software, networks, and data systems', 'Yes'],
                ['IT', 'Information Technology', 'Information systems and operations', 'Yes'],
            ],
        ],
        'Courses' => [
            'type' => 'course',
            'required' => ['Course Code', 'Course Name'],
            'headers' => ['Course Code', 'Course Name', 'Course Description', 'Active'],
            'examples' => [
                ['DBS301', 'Database Systems', 'Relational database design and SQL', 'Yes'],
                ['NET302', 'Computer Networks', 'Network fundamentals and protocols', 'Yes'],
            ],
        ],
        'Program Courses' => [
            'type' => 'program_course',
            'required' => ['Program Code', 'Course Code'],
            'headers' => ['Program Code', 'Course Code', 'Required', 'Recommended Level'],
            'examples' => [
                ['CS', 'DBS301', 'Yes', '3'],
                ['IT', 'NET302', 'Yes', '4'],
            ],
        ],
        'Students' => [
            'type' => 'student',
            'required' => ['Student Name', 'Student Email', 'Student Number'],
            'headers' => ['Student Name', 'Student Email', 'Student Number', 'Program Code', 'Admission Year', 'Academic Status', 'Temporary Password'],
            'examples' => [
                ['Ali Ahmed', 'ali.ahmed@student.example', 'S1001', 'CS', '2026', 'active', 'student12345'],
                ['Sara Hassan', 'sara.hassan@student.example', 'S1002', 'IT', '2026', 'active', 'student12345'],
            ],
        ],
        'Exam Setup' => [
            'type' => 'exam',
            'required' => ['Course Code', 'Exam Title', 'Duration Minutes', 'Total Marks'],
            'headers' => ['Course Code', 'Exam Title', 'Description', 'Duration Minutes', 'Total Marks', 'Start Date', 'End Date', 'Publish Now'],
            'examples' => [
                ['DBS301', 'Database Midterm', 'Covers normalization and SQL joins', '60', '100', '2026-09-01 09:00', '2026-09-01 10:00', 'No'],
                ['NET302', 'Networks Quiz', 'Introductory networking check', '30', '20', '', '', 'Yes'],
            ],
        ],
    ];

    public function templateResponse(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            echo $this->buildWorkbook(self::SHEETS);
        }, 'academic-setup-template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function preview(UploadedFile $file): array
    {
        $sheets = $this->readWorkbook($file);
        $existingPrograms = Major::pluck('id', 'code')->mapWithKeys(fn ($id, $code) => [Str::upper($code) => $id])->all();
        $existingCourses = Course::pluck('id', 'code')->mapWithKeys(fn ($id, $code) => [Str::upper($code) => $id])->all();
        $existingStudentNumbers = StudentProfile::pluck('id', 'student_number')->mapWithKeys(fn ($id, $number) => [Str::upper($number) => $id])->all();
        $existingStudentEmails = User::whereHas('studentProfile')->pluck('id', 'email')->mapWithKeys(fn ($id, $email) => [Str::lower($email) => $id])->all();
        $knownPrograms = array_fill_keys(array_keys($existingPrograms), true);
        $knownCourses = array_fill_keys(array_keys($existingCourses), true);
        $seen = [
            'program' => [],
            'course' => [],
            'program_course' => [],
            'student' => [],
            'exam' => [],
        ];

        $rows = [];
        foreach (self::SHEETS as $sheetName => $definition) {
            $sheetRows = $sheets[$sheetName] ?? [];
            $headers = $sheetRows[0] ?? [];
            $missingColumns = $this->missingColumns($headers, $definition['required']);

            if ($missingColumns !== []) {
                $rows[] = $this->row($sheetName, 1, $definition['type'], 'failed', 'Missing required columns: '.implode(', ', $missingColumns).'. Please download the latest template and keep the column names unchanged.');
                continue;
            }

            foreach (array_slice($sheetRows, 1) as $index => $values) {
                $rowNumber = $index + 2;
                $payload = $this->mapRow($headers, $values);

                if ($this->isBlankRow($payload)) {
                    continue;
                }

                $rows[] = match ($definition['type']) {
                    'program' => $this->previewProgram($sheetName, $rowNumber, $payload, $knownPrograms, $seen),
                    'course' => $this->previewCourse($sheetName, $rowNumber, $payload, $knownCourses, $seen),
                    'program_course' => $this->previewProgramCourse($sheetName, $rowNumber, $payload, $knownPrograms, $knownCourses, $seen),
                    'student' => $this->previewStudent($sheetName, $rowNumber, $payload, $knownPrograms, $existingStudentNumbers, $existingStudentEmails, $seen),
                    'exam' => $this->previewExam($sheetName, $rowNumber, $payload, $knownCourses, $seen),
                };
            }
        }

        return [
            'token' => (string) Str::uuid(),
            'rows' => $rows,
            'summary' => $this->summary($rows),
        ];
    }

    public function import(array $preview, int $instructorId): array
    {
        $rows = collect($preview['rows'] ?? [])->where('status', 'ready')->values();
        $resultRows = [];

        DB::transaction(function () use ($rows, $instructorId, &$resultRows): void {
            foreach ($rows as $row) {
                $payload = $row['payload'];

                try {
                    match ($row['type']) {
                        'program' => Major::firstOrCreate(
                            ['code' => $payload['program_code']],
                            [
                                'name' => $payload['program_name'],
                                'description' => $payload['program_description'] ?? null,
                                'is_active' => $this->bool($payload['active'] ?? 'yes'),
                            ]
                        ),
                        'course' => Course::firstOrCreate(
                            ['code' => $payload['course_code']],
                            [
                                'name' => $payload['course_name'],
                                'description' => $payload['course_description'] ?? null,
                                'is_active' => $this->bool($payload['active'] ?? 'yes'),
                            ]
                        ),
                        'program_course' => $this->saveProgramCourse($payload),
                        'student' => $this->saveStudent($payload),
                        'exam' => $this->saveExam($payload, $instructorId),
                    };

                    $resultRows[] = [...$row, 'status' => 'successful', 'message' => 'Saved successfully.'];
                } catch (\Throwable $exception) {
                    $resultRows[] = [...$row, 'status' => 'failed', 'message' => 'Could not save this row. Please review related information and try again.'];
                }
            }
        });

        $skipped = collect($preview['rows'] ?? [])->where('status', 'skipped')->values()->all();
        $failed = collect($preview['rows'] ?? [])->where('status', 'failed')->values()->all();
        $rows = array_merge($resultRows, $skipped, $failed);

        return [
            'rows' => $rows,
            'summary' => $this->summary($rows, 'successful'),
        ];
    }

    private function previewProgram(string $sheet, int $rowNumber, array $payload, array &$knownPrograms, array &$seen): array
    {
        $code = Str::upper(trim((string) ($payload['program_code'] ?? '')));
        if ($code === '' || trim((string) ($payload['program_name'] ?? '')) === '') {
            return $this->row($sheet, $rowNumber, 'program', 'failed', 'Program Code and Program Name are required.', $payload);
        }

        if (isset($seen['program'][$code])) {
            return $this->row($sheet, $rowNumber, 'program', 'skipped', 'This program appears more than once in the upload.', $payload);
        }

        $seen['program'][$code] = true;
        if (isset($knownPrograms[$code])) {
            return $this->row($sheet, $rowNumber, 'program', 'skipped', 'This program already exists in the system.', $payload);
        }

        $payload['program_code'] = $code;
        $knownPrograms[$code] = true;

        return $this->row($sheet, $rowNumber, 'program', 'ready', 'Ready to add program information.', $payload);
    }

    private function previewCourse(string $sheet, int $rowNumber, array $payload, array &$knownCourses, array &$seen): array
    {
        $code = Str::upper(trim((string) ($payload['course_code'] ?? '')));
        if ($code === '' || trim((string) ($payload['course_name'] ?? '')) === '') {
            return $this->row($sheet, $rowNumber, 'course', 'failed', 'Course Code and Course Name are required.', $payload);
        }

        if (isset($seen['course'][$code])) {
            return $this->row($sheet, $rowNumber, 'course', 'skipped', 'This course appears more than once in the upload.', $payload);
        }

        $seen['course'][$code] = true;
        if (isset($knownCourses[$code])) {
            return $this->row($sheet, $rowNumber, 'course', 'skipped', 'This course already exists in the system.', $payload);
        }

        $payload['course_code'] = $code;
        $knownCourses[$code] = true;

        return $this->row($sheet, $rowNumber, 'course', 'ready', 'Ready to add course information.', $payload);
    }

    private function previewProgramCourse(string $sheet, int $rowNumber, array $payload, array $knownPrograms, array $knownCourses, array &$seen): array
    {
        $programCode = Str::upper(trim((string) ($payload['program_code'] ?? '')));
        $courseCode = Str::upper(trim((string) ($payload['course_code'] ?? '')));
        $key = $programCode.'|'.$courseCode;

        if ($programCode === '' || $courseCode === '') {
            return $this->row($sheet, $rowNumber, 'program_course', 'failed', 'Program Code and Course Code are required.', $payload);
        }

        if (! isset($knownPrograms[$programCode])) {
            return $this->row($sheet, $rowNumber, 'program_course', 'failed', 'The program is missing. Add it in the Programs sheet or create it first.', $payload);
        }

        if (! isset($knownCourses[$courseCode])) {
            return $this->row($sheet, $rowNumber, 'program_course', 'failed', 'The course is missing. Add it in the Courses sheet or create it first.', $payload);
        }

        if (isset($seen['program_course'][$key])) {
            return $this->row($sheet, $rowNumber, 'program_course', 'skipped', 'This program-course link appears more than once in the upload.', $payload);
        }

        $seen['program_course'][$key] = true;
        $payload['program_code'] = $programCode;
        $payload['course_code'] = $courseCode;

        return $this->row($sheet, $rowNumber, 'program_course', 'ready', 'Ready to connect program and course information.', $payload);
    }

    private function previewStudent(
        string $sheet,
        int $rowNumber,
        array $payload,
        array $knownPrograms,
        array $existingStudentNumbers,
        array $existingStudentEmails,
        array &$seen
    ): array {
        $name = trim((string) ($payload['student_name'] ?? ''));
        $email = Str::lower(trim((string) ($payload['student_email'] ?? '')));
        $studentNumber = Str::upper(trim((string) ($payload['student_number'] ?? '')));
        $programCode = Str::upper(trim((string) ($payload['program_code'] ?? '')));
        $status = Str::lower(trim((string) ($payload['academic_status'] ?? 'active')));

        if ($name === '' || $email === '' || $studentNumber === '') {
            return $this->row($sheet, $rowNumber, 'student', 'failed', 'Student Name, Student Email, and Student Number are required.', $payload);
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->row($sheet, $rowNumber, 'student', 'failed', 'Student Email must be a valid email address.', $payload);
        }

        if ($programCode !== '' && ! isset($knownPrograms[$programCode])) {
            return $this->row($sheet, $rowNumber, 'student', 'failed', 'The program is missing. Add it in the Programs sheet or create it first.', $payload);
        }

        if (! in_array($status, ['active', 'inactive', 'graduated', 'suspended'], true)) {
            return $this->row($sheet, $rowNumber, 'student', 'failed', 'Academic Status must be active, inactive, graduated, or suspended.', $payload);
        }

        if (($payload['admission_year'] ?? '') !== '' && (! is_numeric($payload['admission_year']) || (int) $payload['admission_year'] < 1990 || (int) $payload['admission_year'] > 2100)) {
            return $this->row($sheet, $rowNumber, 'student', 'failed', 'Admission Year must be a valid year between 1990 and 2100.', $payload);
        }

        if (isset($existingStudentNumbers[$studentNumber]) || isset($existingStudentEmails[$email])) {
            return $this->row($sheet, $rowNumber, 'student', 'skipped', 'This student account already exists in the system.', $payload);
        }

        if (isset($seen['student'][$studentNumber]) || isset($seen['student'][$email])) {
            return $this->row($sheet, $rowNumber, 'student', 'skipped', 'This student appears more than once in the upload.', $payload);
        }

        $seen['student'][$studentNumber] = true;
        $seen['student'][$email] = true;
        $payload['student_email'] = $email;
        $payload['student_number'] = $studentNumber;
        $payload['program_code'] = $programCode;
        $payload['academic_status'] = $status;

        return $this->row($sheet, $rowNumber, 'student', 'ready', 'Ready to create student account and academic profile.', $payload);
    }

    private function previewExam(string $sheet, int $rowNumber, array $payload, array $knownCourses, array &$seen): array
    {
        $courseCode = Str::upper(trim((string) ($payload['course_code'] ?? '')));
        $title = trim((string) ($payload['exam_title'] ?? ''));
        $key = $courseCode.'|'.Str::lower($title);

        if ($courseCode === '' || $title === '') {
            return $this->row($sheet, $rowNumber, 'exam', 'failed', 'Course Code and Exam Title are required.', $payload);
        }

        if (! isset($knownCourses[$courseCode])) {
            return $this->row($sheet, $rowNumber, 'exam', 'failed', 'The exam course is missing. Add it in the Courses sheet or create it first.', $payload);
        }

        if (! is_numeric($payload['duration_minutes'] ?? null) || (int) $payload['duration_minutes'] < 1) {
            return $this->row($sheet, $rowNumber, 'exam', 'failed', 'Duration Minutes must be a number greater than zero.', $payload);
        }

        if (! is_numeric($payload['total_marks'] ?? null) || (float) $payload['total_marks'] <= 0) {
            return $this->row($sheet, $rowNumber, 'exam', 'failed', 'Total Marks must be a number greater than zero.', $payload);
        }

        if (isset($seen['exam'][$key])) {
            return $this->row($sheet, $rowNumber, 'exam', 'skipped', 'This exam appears more than once in the upload.', $payload);
        }

        $seen['exam'][$key] = true;
        $payload['course_code'] = $courseCode;

        return $this->row($sheet, $rowNumber, 'exam', 'ready', 'Ready to prepare exam settings.', $payload);
    }

    private function saveProgramCourse(array $payload): void
    {
        $major = Major::where('code', $payload['program_code'])->firstOrFail();
        $course = Course::where('code', $payload['course_code'])->firstOrFail();
        $major->courses()->syncWithoutDetaching([
            $course->id => [
                'is_required' => $this->bool($payload['required'] ?? 'yes'),
                'recommended_level' => $payload['recommended_level'] !== '' ? (int) $payload['recommended_level'] : null,
            ],
        ]);
    }

    private function saveStudent(array $payload): StudentProfile
    {
        $major = ($payload['program_code'] ?? '') !== ''
            ? Major::where('code', $payload['program_code'])->first()
            : null;

        $user = User::firstOrCreate(
            ['email' => $payload['student_email']],
            [
                'name' => $payload['student_name'],
                'password' => $payload['temporary_password'] ?: $payload['student_number'],
            ]
        );

        return StudentProfile::firstOrCreate(
            ['student_number' => $payload['student_number']],
            [
                'user_id' => $user->id,
                'major_id' => $major?->id,
                'academic_status' => $payload['academic_status'] ?: StudentProfile::STATUS_ACTIVE,
                'admission_year' => ($payload['admission_year'] ?? '') !== '' ? (int) $payload['admission_year'] : null,
            ]
        );
    }

    private function saveExam(array $payload, int $instructorId): InstructorExam
    {
        $course = Course::where('code', $payload['course_code'])->firstOrFail();

        return InstructorExam::firstOrCreate(
            [
                'course_id' => $course->id,
                'title' => $payload['exam_title'],
            ],
            [
                'instructor_id' => $instructorId,
                'description' => $payload['description'] ?? null,
                'duration_minutes' => (int) $payload['duration_minutes'],
                'starts_at' => $payload['start_date'] ?: null,
                'ends_at' => $payload['end_date'] ?: null,
                'total_marks' => (float) $payload['total_marks'],
                'status' => $this->bool($payload['publish_now'] ?? 'no') ? InstructorExam::STATUS_PUBLISHED : InstructorExam::STATUS_DRAFT,
                'published_at' => $this->bool($payload['publish_now'] ?? 'no') ? now() : null,
            ]
        );
    }

    private function readWorkbook(UploadedFile $file): array
    {
        if (Str::lower($file->getClientOriginalExtension()) === 'csv') {
            return ['Programs' => $this->readCsv($file->getRealPath())];
        }

        $zip = new ZipArchive();
        if ($zip->open($file->getRealPath()) !== true) {
            return [];
        }

        $sharedStrings = $this->sharedStrings($zip);
        $sheetNames = $this->sheetNames($zip);
        $sheets = [];

        foreach ($sheetNames as $position => $name) {
            $xml = $zip->getFromName('xl/worksheets/sheet'.($position + 1).'.xml');
            if ($xml === false) {
                continue;
            }

            $sheets[$name] = $this->readSheet($xml, $sharedStrings);
        }

        $zip->close();

        return $sheets;
    }

    private function readCsv(string $path): array
    {
        $rows = [];
        if (($handle = fopen($path, 'rb')) === false) {
            return $rows;
        }

        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = array_map(fn ($value) => trim((string) $value), $row);
        }

        fclose($handle);

        return $rows;
    }

    private function sharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }

        preg_match_all('/<si>(.*?)<\/si>/s', $xml, $items);

        return collect($items[1] ?? [])
            ->map(fn (string $item): string => html_entity_decode(strip_tags($item), ENT_QUOTES | ENT_XML1))
            ->all();
    }

    private function sheetNames(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/workbook.xml');
        preg_match_all('/<sheet[^>]*name="([^"]+)"/', (string) $xml, $matches);

        return array_map(fn (string $name): string => html_entity_decode($name, ENT_QUOTES | ENT_XML1), $matches[1] ?? []);
    }

    private function readSheet(string $xml, array $sharedStrings): array
    {
        preg_match_all('/<row[^>]*>(.*?)<\/row>/s', $xml, $rowMatches);
        $rows = [];

        foreach ($rowMatches[1] ?? [] as $rowXml) {
            preg_match_all('/<c\b([^>]*)>(.*?)<\/c>/s', $rowXml, $cells, PREG_SET_ORDER);
            $row = [];

            foreach ($cells as $cell) {
                $attributes = $cell[1] ?? '';
                if (! preg_match('/\br="([A-Z]+)\d+"/', $attributes, $reference)) {
                    continue;
                }

                preg_match('/\bt="([^"]+)"/', $attributes, $typeMatch);
                $index = $this->columnIndex($reference[1]);
                $type = $typeMatch[1] ?? '';
                $body = $cell[2] ?? '';
                $value = '';

                if ($type === 'inlineStr' && preg_match('/<t[^>]*>(.*?)<\/t>/s', $body, $match)) {
                    $value = $match[1];
                } elseif (preg_match('/<v>(.*?)<\/v>/s', $body, $match)) {
                    $value = $type === 's' ? ($sharedStrings[(int) $match[1]] ?? '') : $match[1];
                }

                $row[$index] = trim(html_entity_decode($value, ENT_QUOTES | ENT_XML1));
            }

            ksort($row);
            if ($row === []) {
                $rows[] = [];
                continue;
            }

            $filled = [];
            for ($index = 0; $index <= max(array_keys($row)); $index++) {
                $filled[] = $row[$index] ?? '';
            }

            $rows[] = $filled;
        }

        return $rows;
    }

    private function buildWorkbook(array $sheets): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'academic-template-');
        $zip = new ZipArchive();
        $zip->open($tmp, ZipArchive::OVERWRITE);

        $zip->addFromString('[Content_Types].xml', $this->contentTypesXml(count($sheets)));
        $zip->addFromString('_rels/.rels', $this->rootRelsXml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelsXml(count($sheets)));
        $zip->addFromString('xl/workbook.xml', $this->workbookXml(array_keys($sheets)));
        $zip->addFromString('xl/styles.xml', $this->stylesXml());

        foreach (array_values($sheets) as $index => $sheet) {
            $rows = array_merge([$sheet['headers']], $sheet['examples']);
            $zip->addFromString('xl/worksheets/sheet'.($index + 1).'.xml', $this->worksheetXml($rows));
        }

        $zip->close();
        $contents = file_get_contents($tmp);
        unlink($tmp);

        return (string) $contents;
    }

    private function contentTypesXml(int $sheetCount): string
    {
        $worksheets = collect(range(1, $sheetCount))
            ->map(fn (int $index): string => '<Override PartName="/xl/worksheets/sheet'.$index.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>')
            ->implode('');

        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .$worksheets
            .'</Types>';
    }

    private function rootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private function workbookRelsXml(int $sheetCount): string
    {
        $rels = collect(range(1, $sheetCount))
            ->map(fn (int $index): string => '<Relationship Id="rId'.$index.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$index.'.xml"/>')
            ->implode('');

        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .$rels
            .'<Relationship Id="rId'.($sheetCount + 1).'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>';
    }

    private function workbookXml(array $sheetNames): string
    {
        $sheets = collect($sheetNames)
            ->values()
            ->map(fn (string $name, int $index): string => '<sheet name="'.$this->xml($name).'" sheetId="'.($index + 1).'" r:id="rId'.($index + 1).'"/>')
            ->implode('');

        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets>'.$sheets.'</sheets>'
            .'</workbook>';
    }

    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="1"><font><sz val="11"/><name val="Calibri"/></font></fonts>'
            .'<fills count="1"><fill><patternFill patternType="none"/></fill></fills>'
            .'<borders count="1"><border/></borders>'
            .'<cellStyleXfs count="1"><xf/></cellStyleXfs><cellXfs count="1"><xf/></cellXfs>'
            .'</styleSheet>';
    }

    private function worksheetXml(array $rows): string
    {
        $rowXml = collect($rows)->values()->map(function (array $row, int $rowIndex): string {
            $cells = collect($row)->values()->map(function (mixed $value, int $cellIndex) use ($rowIndex): string {
                $cell = $this->columnLetters($cellIndex + 1).($rowIndex + 1);

                return '<c r="'.$cell.'" t="inlineStr"><is><t>'.$this->xml((string) $value).'</t></is></c>';
            })->implode('');

            return '<row r="'.($rowIndex + 1).'">'.$cells.'</row>';
        })->implode('');

        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetData>'.$rowXml.'</sheetData>'
            .'</worksheet>';
    }

    private function mapRow(array $headers, array $values): array
    {
        $mapped = [];
        foreach ($headers as $index => $header) {
            $mapped[$this->key($header)] = trim((string) ($values[$index] ?? ''));
        }

        return $mapped;
    }

    private function missingColumns(array $headers, array $required): array
    {
        $available = collect($headers)->map(fn ($header) => $this->key((string) $header))->all();

        return collect($required)
            ->reject(fn (string $header): bool => in_array($this->key($header), $available, true))
            ->values()
            ->all();
    }

    private function isBlankRow(array $payload): bool
    {
        return collect($payload)->every(fn ($value): bool => trim((string) $value) === '');
    }

    private function row(string $sheet, int $number, string $type, string $status, string $message, array $payload = []): array
    {
        return compact('sheet', 'number', 'type', 'status', 'message', 'payload');
    }

    private function summary(array $rows, string $successStatus = 'ready'): array
    {
        $collection = collect($rows);

        return [
            'successful' => $collection->where('status', $successStatus)->count(),
            'skipped' => $collection->where('status', 'skipped')->count(),
            'failed' => $collection->where('status', 'failed')->count(),
        ];
    }

    private function bool(mixed $value): bool
    {
        return in_array(Str::lower(trim((string) $value)), ['1', 'yes', 'y', 'true', 'active', 'published'], true);
    }

    private function key(string $value): string
    {
        return Str::of($value)->lower()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->toString();
    }

    private function columnIndex(string $letters): int
    {
        $index = 0;
        foreach (str_split($letters) as $letter) {
            $index = ($index * 26) + (ord($letter) - 64);
        }

        return $index - 1;
    }

    private function columnLetters(int $index): string
    {
        $letters = '';
        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $letters = chr(65 + $mod).$letters;
            $index = intdiv($index - $mod, 26);
        }

        return $letters;
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
