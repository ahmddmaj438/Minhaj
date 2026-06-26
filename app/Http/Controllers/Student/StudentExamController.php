<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ExamActivityLog;
use App\Models\Exam\InstructorExam;
use App\Models\ExamAssignment;
use App\Models\ExamSession;
use App\Models\StudentProfile;
use App\Services\ExamSessionManager;
use App\Services\Exams\ExamActivityLogger;
use App\Services\Exams\ExamAvailabilityService;
use App\Services\Exams\ExamFeatureRegistry;
use App\Services\Exams\ExamTimingService;
use App\Services\Exams\QuestionResponseManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StudentExamController extends Controller
{
    public function index(
        Request $request,
        ExamAvailabilityService $availabilityService,
        ExamFeatureRegistry $featureRegistry
    ): View
    {
        $student = $this->studentProfile($request);
        $assignments = $availabilityService->availableForStudent($student);

        return view('student.exams.index', [
            'student' => $student->load('user'),
            'examCards' => $assignments->map(fn (ExamAssignment $assignment): array => [
                'assignment' => $assignment,
                'activeSession' => $availabilityService->activeSession($assignment, $student),
                'submittedSession' => $availabilityService->latestSubmittedSession($assignment, $student),
                'attemptsUsed' => $availabilityService->attemptsUsed($assignment, $student),
                'attemptsRemaining' => $availabilityService->attemptsRemaining($assignment, $student),
                'scoreVisible' => $featureRegistry->showScoreToStudent(
                    $assignment,
                    $availabilityService->latestSubmittedSession($assignment, $student)
                ),
                'feedbackVisible' => $featureRegistry->showFeedbackToStudent(
                    $assignment,
                    $availabilityService->latestSubmittedSession($assignment, $student)
                ),
            ]),
        ]);
    }

    public function start(Request $request, ExamAssignment $assignment, ExamSessionManager $sessionManager): RedirectResponse
    {
        $student = $this->studentProfile($request);
        $this->authorizeAssignment($assignment, $student);
        $session = $sessionManager->start($assignment, $student);

        return redirect()
            ->route('student.exams.sessions.show', $session)
            ->with('status', 'Exam session started.');
    }

    public function show(
        Request $request,
        ExamSession $session,
        ExamTimingService $timingService,
        ExamSessionManager $sessionManager
    ): Response|RedirectResponse
    {
        $student = $this->studentProfile($request);
        $this->authorizeSession($session, $student);
        $this->authorizePublishedExam($session);

        if ($sessionManager->expireIfNeeded($session)) {
            return $this->noStore(
                redirect()
                    ->route('student.exams.index')
                    ->withErrors(['exam' => 'This exam session has expired.'])
            );
        }

        if ($session->status === ExamSession::STATUS_SUBMITTED) {
            return $this->noStore(
                redirect()
                    ->route('student.exams.index')
                    ->with('status', 'This exam has already been submitted.')
            );
        }

        if ($session->status !== ExamSession::STATUS_IN_PROGRESS) {
            return $this->noStore(
                redirect()
                    ->route('student.exams.index')
                    ->withErrors(['exam' => 'This exam session is no longer open.'])
            );
        }

        $session->load([
            'assignment.exam.course',
            'assignment.exam.questions',
            'answers.question',
        ]);

        $exam = $session->assignment->exam;
        $timingPlan = $session->metadata['question_timing_plan'] ?? $timingService->questionTimingPlan($exam);
        $answersByQuestion = $session->answers->keyBy('instructor_exam_question_id');

        return $this->noStore(response()->view('student.exams.show', [
            'session' => $session,
            'assignment' => $session->assignment,
            'exam' => $exam,
            'questions' => $exam->questions,
            'answersByQuestion' => $answersByQuestion,
            'timingPlan' => $timingPlan,
            'remainingSeconds' => $session->expires_at
                ? max(now()->diffInSeconds($session->expires_at, false), 0)
                : null,
        ]));
    }

    public function save(
        Request $request,
        ExamSession $session,
        QuestionResponseManager $responseManager,
        ExamSessionManager $sessionManager,
        ExamActivityLogger $activityLogger
    ): RedirectResponse
    {
        $student = $this->studentProfile($request);
        $this->authorizeSession($session, $student);
        $this->authorizePublishedExam($session);
        $this->authorizeOpenSession($session, $student, $sessionManager, false);

        $answers = $this->validatedAnswers($request);
        $responseManager->saveDrafts($session->load('assignment.exam.questions'), $answers);
        $activityLogger->record($session, ExamActivityLog::EVENT_ANSWERS_SAVED, [
            'answer_count' => count($answers),
        ]);

        return back()->with('status', 'Answers saved.');
    }

    public function submit(
        Request $request,
        ExamSession $session,
        QuestionResponseManager $responseManager,
        ExamSessionManager $sessionManager
    ): RedirectResponse {
        $student = $this->studentProfile($request);
        $this->authorizeSession($session, $student);
        $this->authorizePublishedExam($session);

        if ($session->status !== ExamSession::STATUS_IN_PROGRESS) {
            throw ValidationException::withMessages([
                'exam' => 'Only an in-progress exam session can be submitted.',
            ]);
        }

        $responseManager->saveDrafts($session->load('assignment.exam.questions'), $this->validatedAnswers($request));
        $timedOut = $session->expires_at && now()->gt($session->expires_at);
        $submitted = $sessionManager->submit($session, timedOut: $timedOut);
        $message = (bool) (($submitted->metadata ?? [])['manual_grading_pending'] ?? false)
            ? 'Your exam was submitted successfully. Some answers need teacher review before final result is available.'
            : 'Exam submitted.';

        return $this->noStore(
            redirect()
                ->route('student.exams.index')
                ->with('status', $message)
        );
    }

    private function studentProfile(Request $request): StudentProfile
    {
        $profile = $request->user()?->studentProfile()->first();

        abort_unless($profile instanceof StudentProfile, 403, 'Student portal is available only to student users.');

        return $profile;
    }

    private function validatedAnswers(Request $request): array
    {
        $data = $request->validate([
            'answers' => ['nullable', 'array', 'max:500'],
            'answers.*' => ['nullable', 'array'],
            'answers.*.selected_options' => ['nullable', 'array', 'max:50'],
            'answers.*.selected_options.*' => ['nullable', 'string', 'max:255'],
            'answers.*.answer' => ['nullable', 'string', 'in:true,false'],
            'answers.*.correction' => ['nullable', 'string', 'max:5000'],
            'answers.*.matches' => ['nullable', 'array', 'max:200'],
            'answers.*.matches.*' => ['nullable', 'string', 'max:5000'],
            'answers.*.blanks' => ['nullable', 'array', 'max:200'],
            'answers.*.blanks.*' => ['nullable', 'string', 'max:5000'],
            'answers.*.response' => ['nullable', 'string', 'max:20000'],
        ]);

        return $data['answers'] ?? [];
    }

    private function authorizeSession(ExamSession $session, StudentProfile $student): void
    {
        abort_unless((int) $session->student_profile_id === (int) $student->id, 403);
    }

    private function authorizeAssignment(ExamAssignment $assignment, StudentProfile $student): void
    {
        $assignment->loadMissing('exam');

        $allowed = $assignment->student_profile_id === null
            || (int) $assignment->student_profile_id === (int) $student->id;

        $enrolled = $student->courses()
            ->whereKey($assignment->course_id)
            ->wherePivot('enrollment_status', 'enrolled')
            ->exists();

        if ($assignment->exam?->status !== InstructorExam::STATUS_PUBLISHED || ! $allowed || ! $enrolled) {
            throw ValidationException::withMessages([
                'exam' => 'You do not have permission to access this exam.',
            ]);
        }
    }

    private function authorizePublishedExam(ExamSession $session): void
    {
        $session->loadMissing('assignment.exam');

        abort_unless(
            $session->assignment?->exam?->status === InstructorExam::STATUS_PUBLISHED,
            404
        );
    }

    private function authorizeOpenSession(
        ExamSession $session,
        StudentProfile $student,
        ExamSessionManager $sessionManager,
        bool $authorizeOwner = true
    ): void
    {
        if ($authorizeOwner) {
            $this->authorizeSession($session, $student);
        }

        if ($sessionManager->expireIfNeeded($session)) {
            throw ValidationException::withMessages([
                'exam' => 'This exam session has expired.',
            ]);
        }

        if ($session->status !== ExamSession::STATUS_IN_PROGRESS) {
            throw ValidationException::withMessages([
                'exam' => 'Only an in-progress exam session can be changed.',
            ]);
        }
    }

    private function noStore(Response|RedirectResponse $response): Response|RedirectResponse
    {
        return $response
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
}
