<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Exam\InstructorExam;
use App\Models\Exam\InstructorExamQuestion;
use App\Models\ExamAssignment;
use App\Models\ExamSession;
use App\Models\ExamSessionAnswer;
use App\Models\Group;
use App\Models\Permission;
use App\Models\Role;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private const REPORTS = [
        'exams-summary' => 'Exams Summary Report',
        'student-results' => 'Student Results Report',
        'course-exams' => 'Course Exams Report',
        'teacher-exams' => 'Teacher Exams Report',
        'student-attempts' => 'Student Attempts Report',
        'question-performance' => 'Question Performance Report',
        'ai-grading' => 'AI Grading Report',
        'pending-grading' => 'Pending Grading Report',
        'published-exams' => 'Published Exams Report',
        'academic-setup' => 'Academic Setup Report',
    ];

    public function test_all_report_screens_render_with_friendly_labels(): void
    {
        $context = $this->reportContext();

        foreach (self::REPORTS as $key => $title) {
            $this
                ->actingAs($context['admin'])
                ->get(route('reports.show', $key))
                ->assertOk()
                ->assertSee($title)
                ->assertSee('Report Details')
                ->assertDontSee('instructor_exams')
                ->assertDontSee('exam_sessions')
                ->assertDontSee('migration');
        }
    }

    public function test_student_results_report_filters_search_and_totals(): void
    {
        $context = $this->reportContext();
        $otherStudentUser = User::factory()->create(['name' => 'Omar Hassan']);
        $otherStudent = StudentProfile::factory()->create([
            'user_id' => $otherStudentUser->id,
            'major_id' => null,
            'student_number' => 'S2002',
        ]);
        $otherStudent->courses()->attach($context['course']->id, [
            'enrollment_status' => 'enrolled',
            'enrolled_at' => now(),
        ]);
        $otherAssignment = ExamAssignment::factory()->create([
            'instructor_exam_id' => $context['exam']->id,
            'course_id' => $context['course']->id,
            'student_profile_id' => $otherStudent->id,
            'assigned_by' => $context['teacher']->id,
        ]);
        $otherSession = ExamSession::factory()->submitted()->create([
            'exam_assignment_id' => $otherAssignment->id,
            'student_profile_id' => $otherStudent->id,
            'score' => 5,
            'max_score' => 10,
            'percentage' => 50,
            'passed' => true,
            'metadata' => ['manual_grading_pending' => false],
        ]);
        ExamSessionAnswer::factory()->create([
            'exam_session_id' => $otherSession->id,
            'instructor_exam_question_id' => $context['mcq']->id,
            'score' => 5,
        ]);

        $this
            ->actingAs($context['admin'])
            ->get(route('reports.show', ['report' => 'student-results', 'q' => 'Leen']))
            ->assertOk()
            ->assertSee('Submitted Attempts')
            ->assertSee('Leen Ali')
            ->assertSee('100.00%')
            ->assertDontSee('Omar Hassan');
    }

    public function test_teacher_reports_only_include_assigned_courses_and_block_tampered_filters(): void
    {
        config(['auth.testing_bypass_permissions' => false]);

        $teacher = $this->userWithPermissions('Teacher Reports', [
            'screen.reports.show.view',
        ]);
        $assignedCourse = Course::factory()->create(['code' => 'NET302', 'name' => 'Computer Networks']);
        $otherCourse = Course::factory()->create(['code' => 'WEB303', 'name' => 'Web Application Development']);
        $assignedCourse->teachers()->attach($teacher->id, [
            'role' => 'teacher',
            'assigned_by' => $teacher->id,
            'assigned_at' => now(),
        ]);
        InstructorExam::factory()->published()->create([
            'course_id' => $assignedCourse->id,
            'instructor_id' => $teacher->id,
            'title' => 'Assigned Course Exam',
        ]);
        InstructorExam::factory()->published()->create([
            'course_id' => $otherCourse->id,
            'instructor_id' => User::factory()->create()->id,
            'title' => 'Hidden Course Exam',
        ]);

        $this
            ->actingAs($teacher)
            ->get(route('reports.show', 'course-exams'))
            ->assertOk()
            ->assertSee('Computer Networks')
            ->assertDontSee('Web Application Development')
            ->assertDontSee('Hidden Course Exam');

        $this
            ->actingAs($teacher)
            ->get(route('reports.show', ['report' => 'course-exams', 'course_id' => $otherCourse->id]))
            ->assertForbidden()
            ->assertSee('Access not allowed');
    }

    private function reportContext(): array
    {
        $admin = User::factory()->create(['email' => User::ROOT_SUPER_ADMIN_EMAIL]);
        $teacher = User::factory()->create(['name' => 'Dr Rana']);
        $course = Course::factory()->create(['code' => 'DBS301', 'name' => 'Database Systems']);
        $course->teachers()->attach($teacher->id, [
            'role' => 'teacher',
            'assigned_by' => $admin->id,
            'assigned_at' => now(),
        ]);
        $studentUser = User::factory()->create(['name' => 'Leen Ali']);
        $student = StudentProfile::factory()->create([
            'user_id' => $studentUser->id,
            'major_id' => null,
            'student_number' => 'S1001',
        ]);
        $student->courses()->attach($course->id, [
            'enrollment_status' => 'enrolled',
            'enrolled_at' => now(),
        ]);
        $exam = InstructorExam::factory()->published()->create([
            'course_id' => $course->id,
            'instructor_id' => $teacher->id,
            'title' => 'Database Midterm',
            'total_marks' => 20,
        ]);
        $mcq = InstructorExamQuestion::factory()->create([
            'instructor_exam_id' => $exam->id,
            'title' => 'Choose the primary key',
            'position' => 1,
            'marks' => 10,
        ]);
        $essay = InstructorExamQuestion::factory()->essay()->create([
            'instructor_exam_id' => $exam->id,
            'title' => 'Explain normalization',
            'position' => 2,
            'marks' => 10,
        ]);
        $assignment = ExamAssignment::factory()->create([
            'instructor_exam_id' => $exam->id,
            'course_id' => $course->id,
            'student_profile_id' => $student->id,
            'assigned_by' => $teacher->id,
            'settings' => [
                'show_score_to_student' => true,
                'show_feedback_to_student' => true,
            ],
        ]);
        $session = ExamSession::factory()->submitted()->create([
            'exam_assignment_id' => $assignment->id,
            'student_profile_id' => $student->id,
            'score' => 20,
            'max_score' => 20,
            'percentage' => 100,
            'passed' => true,
            'metadata' => ['manual_grading_pending' => false],
        ]);
        ExamSessionAnswer::factory()->create([
            'exam_session_id' => $session->id,
            'instructor_exam_question_id' => $mcq->id,
            'score' => 10,
            'answer_payload' => ['status' => 'auto_graded', 'value' => ['selected_options' => ['option_1']]],
        ]);
        ExamSessionAnswer::factory()->create([
            'exam_session_id' => $session->id,
            'instructor_exam_question_id' => $essay->id,
            'score' => 10,
            'answer_payload' => [
                'status' => 'manual_graded',
                'value' => ['response' => 'Normalization reduces repeated data.'],
                'ai_grading_suggestion' => [
                    'suggested_score' => 9,
                    'confidence' => 0.78,
                    'feedback' => 'Good answer with a minor missing detail.',
                ],
            ],
        ]);

        return compact('admin', 'teacher', 'course', 'student', 'exam', 'mcq', 'essay', 'assignment', 'session');
    }

    private function userWithPermissions(string $groupName, array $permissions): User
    {
        $user = User::factory()->create();
        $slug = str($groupName)->slug('_')->toString();
        $group = Group::create(['name' => $groupName, 'slug' => $slug]);
        $role = Role::create(['name' => $groupName.' Role', 'slug' => $slug.'_role']);

        $role->permissions()->sync(
            collect($permissions)
                ->map(fn (string $permission): int => Permission::firstOrCreate(['name' => $permission])->id)
                ->all()
        );

        $group->roles()->sync([$role->id]);
        $user->groups()->sync([$group->id]);

        return $user;
    }
}
