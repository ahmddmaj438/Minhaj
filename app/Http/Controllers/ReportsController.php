<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Exam\InstructorExam;
use App\Models\Exam\InstructorExamQuestion;
use App\Models\ExamAssignment;
use App\Models\ExamSession;
use App\Models\ExamSessionAnswer;
use App\Models\Major;
use App\Models\StudentProfile;
use App\Services\Access\LearningAccess;
use App\Services\Exams\ExamScoringManager;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ReportsController extends Controller
{
    public function index(Request $request, LearningAccess $access): View
    {
        $reports = $this->reports();

        return view('reports.index', [
            'reports' => $reports,
            'currentReport' => array_key_first($reports),
            ...$this->payload($request, $access, array_key_first($reports)),
        ]);
    }

    public function show(Request $request, LearningAccess $access, string $report): View
    {
        abort_unless(array_key_exists($report, $this->reports()), 404);

        return view('reports.index', [
            'reports' => $this->reports(),
            'currentReport' => $report,
            ...$this->payload($request, $access, $report),
        ]);
    }

    private function payload(Request $request, LearningAccess $access, string $report): array
    {
        $user = $request->user();
        $courseIds = $access->courseQuery($user)->pluck('id');
        $examIds = $access->examQuery($user)->pluck('id');
        $courseId = $request->integer('course_id') ?: null;
        $examId = $request->integer('exam_id') ?: null;
        $status = trim((string) $request->query('status', ''));
        $search = trim((string) $request->query('q', ''));

        if ($courseId && ! $courseIds->contains($courseId)) {
            abort(403, 'This course is not assigned to you.');
        }

        if ($examId && ! $examIds->contains($examId)) {
            abort(403, 'You do not have permission to access this exam.');
        }

        $filters = compact('courseId', 'examId', 'status', 'search');
        $data = match ($report) {
            'exams-summary' => $this->examsSummary($examIds, $filters),
            'student-results' => $this->studentResults($courseIds, $examIds, $filters),
            'course-exams' => $this->courseExams($courseIds, $examIds, $filters),
            'teacher-exams' => $this->teacherExams($examIds, $filters),
            'student-attempts' => $this->studentAttempts($courseIds, $examIds, $filters),
            'question-performance' => $this->questionPerformance($examIds, $filters),
            'ai-grading' => $this->aiGrading($examIds, $filters),
            'pending-grading' => $this->pendingGrading($examIds, $filters),
            'published-exams' => $this->publishedExams($examIds, $filters),
            'academic-setup' => $this->academicSetup($courseIds, $filters),
        };

        return [
            'report' => $this->reports()[$report],
            'filters' => $filters,
            'courses' => Course::whereIn('id', $courseIds)->orderBy('code')->get(['id', 'code', 'name']),
            'exams' => InstructorExam::whereIn('id', $examIds)->orderBy('title')->get(['id', 'course_id', 'title']),
            ...$data,
        ];
    }

    private function reports(): array
    {
        return [
            'exams-summary' => ['name' => 'Exams Summary Report', 'description' => 'A high-level view of exam readiness, publication, and activity.'],
            'student-results' => ['name' => 'Student Results Report', 'description' => 'Submitted results and visible grading outcomes by student.'],
            'course-exams' => ['name' => 'Course Exams Report', 'description' => 'Exams grouped by assigned course.'],
            'teacher-exams' => ['name' => 'Teacher Exams Report', 'description' => 'Exam ownership and assigned teacher workload.'],
            'student-attempts' => ['name' => 'Student Attempts Report', 'description' => 'Attempts used, timing, and submission status.'],
            'question-performance' => ['name' => 'Question Performance Report', 'description' => 'Question marks, averages, and manual review needs.'],
            'ai-grading' => ['name' => 'AI Grading Report', 'description' => 'AI-assisted grading usage and review status.'],
            'pending-grading' => ['name' => 'Pending Grading Report', 'description' => 'Submitted answers still waiting for teacher review.'],
            'published-exams' => ['name' => 'Published Exams Report', 'description' => 'Published exams, availability, and assignment coverage.'],
            'academic-setup' => ['name' => 'Academic Setup Report', 'description' => 'Programs, courses, students, and teacher assignments.'],
        ];
    }

    private function examsSummary(Collection $examIds, array $filters): array
    {
        $exams = $this->examBase($examIds, $filters)->withCount(['questions', 'assignments'])->latest()->get();

        return $this->table(
            [
                ['label' => 'Visible Exams', 'value' => $exams->count()],
                ['label' => 'Published', 'value' => $exams->where('status', InstructorExam::STATUS_PUBLISHED)->count()],
                ['label' => 'Draft', 'value' => $exams->where('status', InstructorExam::STATUS_DRAFT)->count()],
                ['label' => 'Assigned', 'value' => $exams->sum('assignments_count')],
            ],
            ['Exam', 'Course', 'Teacher', 'Status', 'Questions', 'Assignments', 'Total Marks'],
            $exams->map(fn (InstructorExam $exam) => [
                $exam->title,
                $exam->course?->code.' - '.$exam->course?->name,
                $exam->instructor?->name ?? 'Not assigned',
                $this->statusLabel($exam->status),
                $exam->questions_count,
                $exam->assignments_count,
                number_format((float) $exam->total_marks, 2),
            ])
        );
    }

    private function studentResults(Collection $courseIds, Collection $examIds, array $filters): array
    {
        $sessions = $this->sessionBase($courseIds, $examIds, $filters)
            ->where('exam_sessions.status', ExamSession::STATUS_SUBMITTED)
            ->latest('submitted_at')
            ->get();

        return $this->table(
            [
                ['label' => 'Submitted Attempts', 'value' => $sessions->count()],
                ['label' => 'Passed', 'value' => $sessions->where('passed', true)->count()],
                ['label' => 'Pending Review', 'value' => $sessions->filter(fn ($session) => (bool) (($session->metadata ?? [])['manual_grading_pending'] ?? false))->count()],
                ['label' => 'Average Score', 'value' => $sessions->whereNotNull('percentage')->avg('percentage') ? round($sessions->whereNotNull('percentage')->avg('percentage'), 2).'%' : '-'],
            ],
            ['Student', 'Exam', 'Course', 'Submitted', 'Score', 'Result', 'Review'],
            $sessions->map(fn (ExamSession $session) => [
                $session->student?->user?->name ?? 'Student',
                $session->assignment?->exam?->title,
                $session->assignment?->course?->code,
                $session->submitted_at?->format('M j, Y H:i') ?? '-',
                $session->percentage === null ? 'Pending' : $session->percentage.'%',
                $session->passed === null ? 'Pending' : ($session->passed ? 'Passed' : 'Not passed'),
                ((bool) (($session->metadata ?? [])['manual_grading_pending'] ?? false)) ? 'Teacher review needed' : 'Complete',
            ])
        );
    }

    private function courseExams(Collection $courseIds, Collection $examIds, array $filters): array
    {
        $courses = Course::withCount(['instructorExams as exams_count' => fn ($query) => $query->whereIn('id', $examIds)])
            ->with(['teachers', 'instructorExams' => fn ($query) => $query->whereIn('id', $examIds)])
            ->whereIn('id', $courseIds)
            ->when($filters['courseId'], fn ($query, $id) => $query->whereKey($id))
            ->orderBy('code')
            ->get();

        return $this->table(
            [
                ['label' => 'Courses', 'value' => $courses->count()],
                ['label' => 'Exams', 'value' => $courses->sum('exams_count')],
                ['label' => 'Teachers', 'value' => $courses->flatMap->teachers->unique('id')->count()],
                ['label' => 'Active Courses', 'value' => $courses->where('is_active', true)->count()],
            ],
            ['Course', 'Name', 'Teachers', 'Exams', 'Students', 'Status'],
            $courses->map(fn (Course $course) => [
                $course->code,
                $course->name,
                $course->teachers->pluck('name')->join(', ') ?: 'Not assigned',
                $course->exams_count,
                $course->students()->count(),
                $course->is_active ? 'Active' : 'Inactive',
            ])
        );
    }

    private function teacherExams(Collection $examIds, array $filters): array
    {
        $exams = $this->examBase($examIds, $filters)->latest()->get();

        return $this->table(
            [
                ['label' => 'Teachers', 'value' => $exams->pluck('instructor_id')->filter()->unique()->count()],
                ['label' => 'Exams', 'value' => $exams->count()],
                ['label' => 'Published', 'value' => $exams->where('status', InstructorExam::STATUS_PUBLISHED)->count()],
                ['label' => 'Draft', 'value' => $exams->where('status', InstructorExam::STATUS_DRAFT)->count()],
            ],
            ['Teacher', 'Exam', 'Course', 'Status', 'Updated'],
            $exams->map(fn (InstructorExam $exam) => [
                $exam->instructor?->name ?? 'Not assigned',
                $exam->title,
                $exam->course?->code,
                $this->statusLabel($exam->status),
                $exam->updated_at?->format('M j, Y H:i') ?? '-',
            ])
        );
    }

    private function studentAttempts(Collection $courseIds, Collection $examIds, array $filters): array
    {
        $sessions = $this->sessionBase($courseIds, $examIds, $filters)->latest('started_at')->get();

        return $this->table(
            [
                ['label' => 'Attempts', 'value' => $sessions->count()],
                ['label' => 'In Progress', 'value' => $sessions->where('status', ExamSession::STATUS_IN_PROGRESS)->count()],
                ['label' => 'Submitted', 'value' => $sessions->where('status', ExamSession::STATUS_SUBMITTED)->count()],
                ['label' => 'Expired', 'value' => $sessions->where('status', ExamSession::STATUS_EXPIRED)->count()],
            ],
            ['Student', 'Exam', 'Attempt', 'Started', 'Submitted', 'Status'],
            $sessions->map(fn (ExamSession $session) => [
                $session->student?->user?->name ?? 'Student',
                $session->assignment?->exam?->title,
                $session->attempt_number,
                $session->started_at?->format('M j, Y H:i') ?? '-',
                $session->submitted_at?->format('M j, Y H:i') ?? '-',
                $this->statusLabel($session->status),
            ])
        );
    }

    private function questionPerformance(Collection $examIds, array $filters): array
    {
        $questions = InstructorExamQuestion::with('exam.course')
            ->whereHas('exam', fn ($query) => $query->whereIn('id', $examIds))
            ->when($filters['examId'], fn ($query, $id) => $query->where('instructor_exam_id', $id))
            ->get();

        return $this->table(
            [
                ['label' => 'Questions', 'value' => $questions->count()],
                ['label' => 'Objective', 'value' => $questions->where('category', 'objective')->count()],
                ['label' => 'Written', 'value' => $questions->whereIn('type', ['essay', 'packet_tracer'])->count()],
                ['label' => 'Total Marks', 'value' => number_format((float) $questions->sum('marks'), 2)],
            ],
            ['Exam', 'Course', 'Question', 'Type', 'Marks', 'Average Score', 'Pending Review'],
            $questions->map(function (InstructorExamQuestion $question) {
                $answers = ExamSessionAnswer::where('instructor_exam_question_id', $question->id)->get();

                return [
                    $question->exam?->title,
                    $question->exam?->course?->code,
                    $question->title,
                    $this->statusLabel($question->type),
                    number_format((float) $question->marks, 2),
                    $answers->whereNotNull('score')->avg('score') !== null ? round($answers->whereNotNull('score')->avg('score'), 2) : '-',
                    $answers->filter(fn ($answer) => ($answer->answer_payload['status'] ?? null) === 'manual_pending')->count(),
                ];
            })
        );
    }

    private function aiGrading(Collection $examIds, array $filters): array
    {
        $answers = $this->answerBase($examIds, $filters)
            ->get()
            ->filter(fn (ExamSessionAnswer $answer) => ! empty(($answer->answer_payload ?? [])['ai_grading_suggestion']));

        return $this->table(
            [
                ['label' => 'AI Suggestions', 'value' => $answers->count()],
                ['label' => 'Saved Scores', 'value' => $answers->whereNotNull('score')->count()],
                ['label' => 'Needs Review', 'value' => $answers->whereNull('score')->count()],
                ['label' => 'Exams', 'value' => $answers->pluck('session.assignment.instructor_exam_id')->unique()->count()],
            ],
            ['Student', 'Exam', 'Question', 'Suggested Score', 'Teacher Score', 'Status'],
            $answers->map(fn (ExamSessionAnswer $answer) => [
                $answer->session?->student?->user?->name ?? 'Student',
                $answer->session?->assignment?->exam?->title,
                $answer->question?->title,
                data_get($answer->answer_payload, 'ai_grading_suggestion.suggested_score', 'Pending'),
                $answer->score ?? 'Not saved',
                $answer->score === null ? 'Teacher review needed' : 'Reviewed',
            ])
        );
    }

    private function pendingGrading(Collection $examIds, array $filters): array
    {
        $answers = $this->answerBase($examIds, $filters)
            ->whereNull('score')
            ->get()
            ->filter(fn (ExamSessionAnswer $answer) => app(ExamScoringManager::class)->requiresManualGrading($answer->question));

        return $this->table(
            [
                ['label' => 'Pending Answers', 'value' => $answers->count()],
                ['label' => 'Students', 'value' => $answers->pluck('session.student_profile_id')->unique()->count()],
                ['label' => 'Exams', 'value' => $answers->pluck('session.assignment.instructor_exam_id')->unique()->count()],
                ['label' => 'Courses', 'value' => $answers->pluck('session.assignment.course_id')->unique()->count()],
            ],
            ['Student', 'Exam', 'Course', 'Question', 'Submitted'],
            $answers->map(fn (ExamSessionAnswer $answer) => [
                $answer->session?->student?->user?->name ?? 'Student',
                $answer->session?->assignment?->exam?->title,
                $answer->session?->assignment?->course?->code,
                $answer->question?->title,
                $answer->session?->submitted_at?->format('M j, Y H:i') ?? '-',
            ])
        );
    }

    private function publishedExams(Collection $examIds, array $filters): array
    {
        $exams = $this->examBase($examIds, $filters)
            ->where('status', InstructorExam::STATUS_PUBLISHED)
            ->withCount(['questions', 'assignments'])
            ->latest('published_at')
            ->get();

        return $this->table(
            [
                ['label' => 'Published Exams', 'value' => $exams->count()],
                ['label' => 'Assignments', 'value' => $exams->sum('assignments_count')],
                ['label' => 'Questions', 'value' => $exams->sum('questions_count')],
                ['label' => 'Courses', 'value' => $exams->pluck('course_id')->unique()->count()],
            ],
            ['Exam', 'Course', 'Published', 'Questions', 'Assignments', 'Availability'],
            $exams->map(fn (InstructorExam $exam) => [
                $exam->title,
                $exam->course?->code,
                $exam->published_at?->format('M j, Y H:i') ?? '-',
                $exam->questions_count,
                $exam->assignments_count,
                trim(($exam->starts_at?->format('M j, Y H:i') ?? 'Any time').' to '.($exam->ends_at?->format('M j, Y H:i') ?? 'No end')),
            ])
        );
    }

    private function academicSetup(Collection $courseIds, array $filters): array
    {
        $courses = Course::with(['majors', 'teachers'])
            ->withCount('students')
            ->whereIn('id', $courseIds)
            ->when($filters['courseId'], fn ($query, $id) => $query->whereKey($id))
            ->orderBy('code')
            ->get();

        return $this->table(
            [
                ['label' => 'Programs', 'value' => Major::count()],
                ['label' => 'Assigned Courses', 'value' => $courses->count()],
                ['label' => 'Students', 'value' => StudentProfile::whereHas('courses', fn ($query) => $query->whereIn('courses.id', $courseIds))->count()],
                ['label' => 'Teachers', 'value' => $courses->flatMap->teachers->unique('id')->count()],
            ],
            ['Course', 'Name', 'Programs', 'Teachers', 'Students', 'Status'],
            $courses->map(fn (Course $course) => [
                $course->code,
                $course->name,
                $course->majors->pluck('code')->join(', ') ?: 'Not assigned',
                $course->teachers->pluck('name')->join(', ') ?: 'Not assigned',
                $course->students_count,
                $course->is_active ? 'Active' : 'Inactive',
            ])
        );
    }

    private function examBase(Collection $examIds, array $filters)
    {
        return InstructorExam::with(['course', 'instructor'])
            ->whereIn('id', $examIds)
            ->when($filters['courseId'], fn ($query, $id) => $query->where('course_id', $id))
            ->when($filters['examId'], fn ($query, $id) => $query->whereKey($id))
            ->when($filters['status'], fn ($query, $status) => $query->where('status', $status))
            ->when($filters['search'], fn ($query, $search) => $query->where('title', 'like', '%'.$search.'%'));
    }

    private function sessionBase(Collection $courseIds, Collection $examIds, array $filters)
    {
        return ExamSession::with(['assignment.exam.course', 'student.user'])
            ->whereHas('assignment', fn ($query) => $query
                ->whereIn('course_id', $courseIds)
                ->whereIn('instructor_exam_id', $examIds)
                ->when($filters['courseId'], fn ($assignmentQuery, $id) => $assignmentQuery->where('course_id', $id))
                ->when($filters['examId'], fn ($assignmentQuery, $id) => $assignmentQuery->where('instructor_exam_id', $id)))
            ->when($filters['status'], fn ($query, $status) => $query->where('status', $status))
            ->when($filters['search'], fn ($query, $search) => $query->whereHas('student.user', fn ($userQuery) => $userQuery->where('name', 'like', '%'.$search.'%')));
    }

    private function answerBase(Collection $examIds, array $filters)
    {
        return ExamSessionAnswer::with(['session.assignment.exam.course', 'session.student.user', 'question'])
            ->whereHas('session.assignment', fn ($query) => $query
                ->whereIn('instructor_exam_id', $examIds)
                ->when($filters['examId'], fn ($assignmentQuery, $id) => $assignmentQuery->where('instructor_exam_id', $id))
                ->when($filters['courseId'], fn ($assignmentQuery, $id) => $assignmentQuery->where('course_id', $id)))
            ->when($filters['search'], fn ($query, $search) => $query->whereHas('session.student.user', fn ($userQuery) => $userQuery->where('name', 'like', '%'.$search.'%')));
    }

    private function table(array $cards, array $headers, Collection $rows): array
    {
        return compact('cards', 'headers', 'rows');
    }

    private function statusLabel(?string $value): string
    {
        return str((string) $value)->replace('_', ' ')->title()->toString();
    }
}
