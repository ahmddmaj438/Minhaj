<?php

namespace App\Http\Controllers;

use Carbon\CarbonImmutable;
use App\Models\Course;
use App\Models\Exam\InstructorExam;
use App\Models\ExamAssignment;
use App\Models\ExamSession;
use App\Models\Major;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\Exams\ExamFeatureRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AcademicManagementController extends Controller
{
    public function index(ExamFeatureRegistry $featureRegistry): View
    {
        return view('academics.index', [
            'majors' => Major::withCount(['students', 'courses'])->orderBy('name')->get(),
            'students' => StudentProfile::with(['user', 'major', 'courses'])->latest()->get(),
            'studentUsers' => User::whereDoesntHave('studentProfile')->orderBy('name')->get(),
            'courses' => Course::with(['majors', 'students.user'])->orderBy('code')->orderBy('name')->get(),
            'exams' => InstructorExam::with('course')->latest()->get(),
            'assignments' => ExamAssignment::with(['exam', 'course', 'student.user', 'assignedBy'])
                ->latest()
                ->limit(50)
                ->get(),
            'sessions' => ExamSession::with(['assignment.exam', 'assignment.course', 'student.user'])
                ->latest()
                ->limit(50)
                ->get(),
            'assignmentFeatures' => $featureRegistry->assignmentSettings(),
        ]);
    }

    public function storeMajor(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('db.majors.insert'), 403);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:majors,code'],
            'name' => ['required', 'string', 'max:255', 'unique:majors,name'],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        Major::create([
            ...$data,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('status', 'Major created.');
    }

    public function storeStudent(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('db.student_profiles.insert'), 403);

        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id', 'unique:student_profiles,user_id'],
            'major_id' => ['nullable', 'integer', 'exists:majors,id'],
            'student_number' => ['required', 'string', 'max:100', 'unique:student_profiles,student_number'],
            'academic_status' => ['required', Rule::in([
                StudentProfile::STATUS_ACTIVE,
                StudentProfile::STATUS_INACTIVE,
                StudentProfile::STATUS_GRADUATED,
                StudentProfile::STATUS_SUSPENDED,
            ])],
            'admission_year' => ['nullable', 'integer', 'min:1990', 'max:2100'],
        ]);

        StudentProfile::create($data);

        return back()->with('status', 'Student profile created.');
    }

    public function assignCourseToMajor(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('db.course_major.insert'), 403);

        $data = $request->validate([
            'major_id' => ['required', 'integer', 'exists:majors,id'],
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'is_required' => ['nullable', 'boolean'],
            'recommended_level' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        $major = Major::findOrFail($data['major_id']);
        $major->courses()->syncWithoutDetaching([
            $data['course_id'] => [
                'is_required' => $request->boolean('is_required', true),
                'recommended_level' => $data['recommended_level'] ?? null,
            ],
        ]);

        return back()->with('status', 'Course assigned to major.');
    }

    public function enrollStudent(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('db.course_student.insert'), 403);

        $data = $request->validate([
            'student_profile_id' => ['required', 'integer', 'exists:student_profiles,id'],
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'enrollment_status' => ['required', Rule::in(['enrolled', 'completed', 'dropped', 'pending'])],
            'enrolled_at' => ['nullable', 'date'],
        ]);

        $student = StudentProfile::findOrFail($data['student_profile_id']);
        $student->courses()->syncWithoutDetaching([
            $data['course_id'] => [
                'enrollment_status' => $data['enrollment_status'],
                'enrolled_at' => $data['enrolled_at'] ?? now(),
            ],
        ]);

        return back()->with('status', 'Student enrolled in course.');
    }

    public function storeExamAssignment(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('db.exam_assignments.insert'), 403);

        $data = $request->validate([
            'instructor_exam_id' => ['required', 'integer', 'exists:instructor_exams,id'],
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'student_profile_id' => ['nullable', 'integer', 'exists:student_profiles,id'],
            'available_at' => ['nullable', 'date'],
            'due_at' => ['nullable', 'date'],
            'max_attempts' => ['required', 'integer', 'min:1', 'max:20'],
            'show_score_to_student' => ['nullable', 'boolean'],
            'show_feedback_to_student' => ['nullable', 'boolean'],
            'status' => ['required', Rule::in([
                ExamAssignment::STATUS_ASSIGNED,
                ExamAssignment::STATUS_OPEN,
                ExamAssignment::STATUS_CLOSED,
                ExamAssignment::STATUS_CANCELLED,
            ])],
        ]);

        $availableAt = $this->parseAssignmentDateTime($data['available_at'] ?? null);
        $dueAt = $this->parseAssignmentDateTime($data['due_at'] ?? null);

        if ($availableAt && $dueAt && $dueAt->lt($availableAt)) {
            throw ValidationException::withMessages([
                'due_at' => 'The due time must be the same as or later than the available time.',
            ]);
        }

        $data['available_at'] = $availableAt;
        $data['due_at'] = $dueAt;
        $settings = [
            'show_score_to_student' => $request->boolean('show_score_to_student'),
            'show_feedback_to_student' => $request->boolean('show_feedback_to_student'),
        ];
        unset($data['show_score_to_student'], $data['show_feedback_to_student']);

        $exam = InstructorExam::findOrFail($data['instructor_exam_id']);
        if ((int) $exam->course_id !== (int) $data['course_id']) {
            throw ValidationException::withMessages([
                'course_id' => 'The selected exam belongs to another course.',
            ]);
        }

        if (! empty($data['student_profile_id'])) {
            $isEnrolled = StudentProfile::findOrFail($data['student_profile_id'])
                ->courses()
                ->whereKey($data['course_id'])
                ->wherePivot('enrollment_status', 'enrolled')
                ->exists();

            if (! $isEnrolled) {
                throw ValidationException::withMessages([
                    'student_profile_id' => 'The selected student must be enrolled in the selected course. Enroll the student first, then create the assignment.',
                ]);
            }
        }

        ExamAssignment::create([
            ...$data,
            'assigned_by' => $request->user()->id,
            'settings' => $settings,
        ]);

        return back()->with('status', 'Exam assignment created.');
    }

    private function parseAssignmentDateTime(?string $value): ?CarbonImmutable
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return CarbonImmutable::parse($value);
    }

    public function updateSessionStatus(Request $request, ExamSession $session): RedirectResponse
    {
        abort_unless($request->user()?->can('db.exam_sessions.update'), 403);

        $data = $request->validate([
            'status' => ['required', Rule::in([
                ExamSession::STATUS_IN_PROGRESS,
                ExamSession::STATUS_SUBMITTED,
                ExamSession::STATUS_EXPIRED,
                ExamSession::STATUS_CANCELLED,
            ])],
        ]);

        DB::transaction(function () use ($session, $data): void {
            $session->update([
                'status' => $data['status'],
                'submitted_at' => $data['status'] === ExamSession::STATUS_SUBMITTED
                    ? ($session->submitted_at ?? now())
                    : $session->submitted_at,
            ]);
        });

        return back()->with('status', 'Exam session status updated.');
    }
}
