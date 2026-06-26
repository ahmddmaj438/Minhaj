<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Exam\InstructorExam;
use App\Models\Major;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\Academics\AcademicSetupImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AcademicSetupImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_academic_template_downloads_as_excel_file(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->get(route('academics.upload.template'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_academic_excel_upload_can_be_previewed_and_confirmed(): void
    {
        $user = User::factory()->create();

        $previewResponse = $this
            ->actingAs($user)
            ->post(route('academics.upload.preview'), [
                'academic_file' => $this->templateUpload(),
            ])
            ->assertRedirect(route('academics.upload.index', absolute: false))
            ->assertSessionHas('academic_import_preview');

        $preview = $previewResponse->baseResponse->getSession()->get('academic_import_preview');
        $this->assertSame(10, $preview['summary']['successful']);
        $this->assertSame(0, $preview['summary']['failed']);

        $this
            ->actingAs($user)
            ->post(route('academics.upload.confirm'))
            ->assertRedirect(route('academics.upload.index', absolute: false))
            ->assertSessionHas('academic_import_result');

        $this->assertDatabaseHas('majors', ['code' => 'CS', 'name' => 'Computer Science']);
        $this->assertDatabaseHas('courses', ['code' => 'DBS301', 'name' => 'Database Systems']);
        $this->assertDatabaseHas('users', ['email' => 'ali.ahmed@student.example']);
        $this->assertDatabaseHas('student_profiles', ['student_number' => 'S1001']);
        $this->assertDatabaseHas('instructor_exams', ['title' => 'Database Midterm']);
        $this->assertSame(2, Major::count());
        $this->assertSame(2, Course::count());
        $this->assertSame(2, StudentProfile::count());
        $this->assertSame(2, InstructorExam::count());
    }

    public function test_academic_upload_reports_existing_duplicates_as_skipped(): void
    {
        $user = User::factory()->create();
        Major::create(['code' => 'CS', 'name' => 'Computer Science', 'is_active' => true]);
        Course::create(['code' => 'DBS301', 'name' => 'Database Systems', 'is_active' => true]);

        $previewResponse = $this
            ->actingAs($user)
            ->post(route('academics.upload.preview'), [
                'academic_file' => $this->templateUpload(),
            ])
            ->assertRedirect(route('academics.upload.index', absolute: false));

        $preview = $previewResponse->baseResponse->getSession()->get('academic_import_preview');

        $this->assertGreaterThanOrEqual(2, $preview['summary']['skipped']);
        $this->assertTrue(collect($preview['rows'])->contains(
            fn (array $row): bool => $row['status'] === 'skipped'
                && str_contains($row['message'], 'already exists')
        ));
    }

    public function test_academic_upload_reports_missing_required_columns_with_friendly_message(): void
    {
        $user = User::factory()->create();

        $previewResponse = $this
            ->actingAs($user)
            ->post(route('academics.upload.preview'), [
                'academic_file' => $this->csvUpload("Program Code\nCS\n"),
            ])
            ->assertRedirect(route('academics.upload.index', absolute: false));

        $preview = $previewResponse->baseResponse->getSession()->get('academic_import_preview');

        $this->assertGreaterThanOrEqual(1, $preview['summary']['failed']);
        $this->assertTrue(collect($preview['rows'])->contains(
            fn (array $row): bool => $row['status'] === 'failed'
                && str_contains($row['message'], 'Missing required columns')
        ));
    }

    public function test_academic_form_creates_student_account_and_profile_together(): void
    {
        $user = User::factory()->create();
        $program = Major::create(['code' => 'SE', 'name' => 'Software Engineering', 'is_active' => true]);

        $this
            ->actingAs($user)
            ->post(route('academics.students.store'), [
                'student_name' => 'Mona Saleh',
                'student_email' => 'mona.saleh@student.example',
                'student_password' => 'student12345',
                'student_number' => 'SE1001',
                'major_id' => $program->id,
                'academic_status' => 'active',
                'admission_year' => 2026,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['email' => 'mona.saleh@student.example']);
        $this->assertDatabaseHas('student_profiles', [
            'student_number' => 'SE1001',
            'major_id' => $program->id,
        ]);
    }

    private function templateUpload(): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'academic-template-').'.xlsx';
        $response = app(AcademicSetupImportService::class)->templateResponse();

        ob_start();
        $response->sendContent();
        file_put_contents($path, ob_get_clean());

        return new UploadedFile(
            $path,
            'academic-setup-template.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );
    }

    private function csvUpload(string $contents): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'academic-upload-').'.csv';
        file_put_contents($path, $contents);

        return new UploadedFile($path, 'academic-upload.csv', 'text/csv', null, true);
    }
}
