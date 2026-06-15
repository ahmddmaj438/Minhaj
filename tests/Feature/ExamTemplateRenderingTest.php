<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Exam\InstructorExam;
use App\Models\Exam\InstructorExamQuestion;
use App\Models\ExamAssignment;
use App\Models\ExamSession;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamTemplateRenderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_instructor_preview_renders_each_exam_template_with_printable_exam_details(): void
    {
        [$instructor, $course] = $this->instructorCourseContext();

        $cases = [
            InstructorExam::FORMAT_ONE_QUESTION_AT_TIME => [
                'One Question at a Time template',
                'Focused question flow',
                'Student question navigator',
            ],
            InstructorExam::FORMAT_ALL_QUESTIONS => [
                'All Questions on One Page template',
                'All questions are visible on one page',
                'Quick links',
            ],
            InstructorExam::FORMAT_GOOGLE_FORMS => [
                'Google Forms Style template',
                'Section-based exam flow',
                'Section 1',
            ],
        ];

        foreach ($cases as $format => $expectedTexts) {
            $exam = $this->exam($course, $instructor, [
                'title' => 'Template Preview '.$format,
                'description' => 'Read all instructions before answering.',
                'display_format' => $format,
            ]);
            $this->questions($exam);

            $response = $this->actingAs($instructor)
                ->get(route('instructor.exams.preview.show', $exam));

            $response
                ->assertOk()
                ->assertSee('Template Preview '.$format)
                ->assertSee('DBS301')
                ->assertSee('Student name')
                ->assertSee('Student number')
                ->assertSee('Instructions')
                ->assertSee('Print / save as PDF')
                ->assertSee('window.print()', false)
                ->assertSee('data-preview-format="'.$format.'"', false)
                ->assertSee('10.00 marks')
                ->assertSee('Normalization means')
                ->assertSee('Explain third normal form');

            foreach ($expectedTexts as $text) {
                $response->assertSee($text);
            }
        }
    }

    public function test_instructor_can_switch_between_templates_and_preview_uses_saved_choice(): void
    {
        [$instructor, $course] = $this->instructorCourseContext();
        $exam = $this->exam($course, $instructor);
        $this->questions($exam);

        foreach ([
            InstructorExam::FORMAT_ALL_QUESTIONS,
            InstructorExam::FORMAT_GOOGLE_FORMS,
            InstructorExam::FORMAT_ONE_QUESTION_AT_TIME,
        ] as $format) {
            $this->actingAs($instructor)
                ->put(route('instructor.exams.update', $exam), [
                    'title' => $exam->title,
                    'description' => $exam->description,
                    'course_id' => $course->id,
                    'duration_minutes' => $exam->duration_minutes,
                    'starts_at' => null,
                    'ends_at' => null,
                    'total_marks' => $exam->total_marks,
                    'display_format' => $format,
                ])
                ->assertRedirect(route('instructor.exams.edit', $exam, absolute: false));

            $exam->refresh();
            $this->assertSame($format, $exam->display_format);

            $this->actingAs($instructor)
                ->get(route('instructor.exams.preview.show', $exam))
                ->assertOk()
                ->assertSee('data-preview-format="'.$format.'"', false);
        }
    }

    public function test_legacy_invalid_template_values_fall_back_to_default_everywhere(): void
    {
        [$instructor, $course] = $this->instructorCourseContext();
        [$studentUser, $student] = $this->student($course);

        $exam = $this->exam($course, $instructor, [
            'status' => InstructorExam::STATUS_PUBLISHED,
        ]);
        $this->questions($exam);
        $exam->forceFill(['display_format' => 'legacy_template'])->save();

        $this->actingAs($instructor)
            ->get(route('instructor.exams.preview.show', $exam))
            ->assertOk()
            ->assertSee('data-preview-format="'.InstructorExam::FORMAT_ONE_QUESTION_AT_TIME.'"', false)
            ->assertSee('One Question at a Time template')
            ->assertDontSee('All Questions on One Page template')
            ->assertDontSee('Google Forms Style template');

        $assignment = ExamAssignment::create([
            'instructor_exam_id' => $exam->id,
            'course_id' => $course->id,
            'student_profile_id' => $student->id,
            'assigned_by' => $instructor->id,
            'available_at' => now()->subHour(),
            'due_at' => now()->addHours(2),
            'max_attempts' => 1,
            'status' => ExamAssignment::STATUS_ASSIGNED,
        ]);

        $this->actingAs($studentUser)->post(route('student.exams.start', $assignment));
        $session = ExamSession::latest('id')->firstOrFail();

        $this->actingAs($studentUser)
            ->get(route('student.exams.sessions.show', $session))
            ->assertOk()
            ->assertSee('data-exam-template="'.InstructorExam::FORMAT_ONE_QUESTION_AT_TIME.'"', false)
            ->assertSee('One Question at a Time')
            ->assertSee('Flag question')
            ->assertSee('Next')
            ->assertDontSee('All questions are on this page')
            ->assertDontSee('Work through each group');
    }

    private function instructorCourseContext(): array
    {
        $instructor = User::factory()->create();
        $course = Course::create([
            'code' => 'DBS301',
            'name' => 'Database Systems',
            'is_active' => true,
        ]);

        return [$instructor, $course];
    }

    private function student(Course $course): array
    {
        $user = User::factory()->create();
        $student = StudentProfile::create([
            'user_id' => $user->id,
            'student_number' => 'S1001',
            'academic_status' => StudentProfile::STATUS_ACTIVE,
        ]);
        $student->courses()->attach($course->id, [
            'enrollment_status' => 'enrolled',
            'enrolled_at' => now(),
        ]);

        return [$user, $student];
    }

    private function exam(Course $course, User $instructor, array $overrides = []): InstructorExam
    {
        return InstructorExam::create(array_merge([
            'course_id' => $course->id,
            'instructor_id' => $instructor->id,
            'title' => 'Template Exam',
            'description' => 'Use clear work and show all steps.',
            'duration_minutes' => 60,
            'total_marks' => 30,
            'display_format' => InstructorExam::FORMAT_ONE_QUESTION_AT_TIME,
            'status' => InstructorExam::STATUS_DRAFT,
        ], $overrides));
    }

    private function questions(InstructorExam $exam): void
    {
        InstructorExamQuestion::create([
            'instructor_exam_id' => $exam->id,
            'type' => 'mcq',
            'category' => 'objective',
            'title' => 'Normalization basics',
            'position' => 1,
            'marks' => 10,
            'topic' => 'Database Design',
            'prompt' => [
                'question_text' => 'Normalization means organizing relational data.',
                'instructions' => 'Choose the best answer.',
                'status' => 'configured',
            ],
            'settings' => [
                'options' => [
                    ['key' => 'option_1', 'text' => 'Reducing redundancy', 'is_correct' => true],
                    ['key' => 'option_2', 'text' => 'Duplicating every table', 'is_correct' => false],
                ],
            ],
        ]);

        InstructorExamQuestion::create([
            'instructor_exam_id' => $exam->id,
            'type' => 'essay',
            'category' => 'text',
            'title' => 'Third normal form',
            'position' => 2,
            'marks' => 20,
            'topic' => 'Database Design',
            'display_override' => 'expanded_essay',
            'prompt' => [
                'question_text' => 'Explain third normal form.',
                'instructions' => 'Use a short example.',
                'status' => 'configured',
            ],
            'settings' => ['rubric' => 'Accuracy, example, and clarity.'],
        ]);
    }
}
