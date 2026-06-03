<?php

namespace App\Services;

use App\Models\ExamAssignment;
use App\Models\ExamSession;
use App\Models\StudentProfile;
use App\Services\Exams\ExamScoringManager;
use App\Services\Exams\Grading\SessionGradeCalculator;
use App\Services\Exams\ExamTimingService;
use App\Services\Exams\QuestionResponseManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExamSessionManager
{
    public function __construct(
        private readonly ExamTimingService $timingService,
        private readonly QuestionResponseManager $responseManager,
        private readonly ExamScoringManager $scoringManager,
        private readonly SessionGradeCalculator $gradeCalculator,
    ) {}

    public function start(ExamAssignment $assignment, StudentProfile $student): ExamSession
    {
        $this->ensureStudentCanUseAssignment($assignment, $student);

        return DB::transaction(function () use ($assignment, $student): ExamSession {
            $activeSession = $assignment->sessions()
                ->where('student_profile_id', $student->id)
                ->where('status', ExamSession::STATUS_IN_PROGRESS)
                ->latest()
                ->first();

            if ($activeSession && (! $activeSession->expires_at || now()->lte($activeSession->expires_at))) {
                $activeSession->loadMissing('assignment.exam.questions');
                $this->responseManager->createDraftAnswers($activeSession);

                return $activeSession;
            }

            if ($activeSession) {
                $activeSession->update(['status' => ExamSession::STATUS_EXPIRED]);
            }

            $exam = $assignment->exam()
                ->with('questions')
                ->firstOrFail();
            $assignment->setRelation('exam', $exam);

            $attemptNumber = $assignment->sessions()
                ->where('student_profile_id', $student->id)
                ->max('attempt_number') + 1;

            if ($attemptNumber > $assignment->max_attempts) {
                throw ValidationException::withMessages([
                    'exam' => 'The maximum number of attempts has been reached.',
                ]);
            }

            $startedAt = now();
            $possibleMarks = $this->gradeCalculator->possibleMarks($exam->questions);

            $session = ExamSession::create([
                'exam_assignment_id' => $assignment->id,
                'student_profile_id' => $student->id,
                'attempt_number' => $attemptNumber,
                'started_at' => $startedAt,
                'expires_at' => $this->timingService->sessionExpiresAt($assignment, $startedAt),
                'status' => ExamSession::STATUS_IN_PROGRESS,
                'max_score' => $possibleMarks,
                'metadata' => [
                    'duration_minutes' => $exam->duration_minutes,
                    'assignment_available_at' => $assignment->available_at?->toISOString(),
                    'assignment_due_at' => $assignment->due_at?->toISOString(),
                    'question_timing_plan' => $this->timingService->questionTimingPlan($exam),
                    'grading' => [
                        'source' => 'question_marks',
                        'possible_question_marks' => $possibleMarks,
                    ],
                ],
            ]);

            $session->setRelation('assignment', $assignment);
            $this->responseManager->createDraftAnswers($session);

            return $session;
        });
    }

    public function submit(ExamSession $session, ?float $score = null): ExamSession
    {
        if ($session->status !== ExamSession::STATUS_IN_PROGRESS) {
            throw ValidationException::withMessages([
                'session' => 'Only an in-progress exam session can be submitted.',
            ]);
        }

        $session->update([
            'submitted_at' => now(),
            'status' => ExamSession::STATUS_SUBMITTED,
        ]);

        if ($score !== null) {
            $maxScore = $session->max_score !== null ? (float) $session->max_score : null;
            $percentage = ($maxScore && $maxScore > 0)
                ? round(($score / $maxScore) * 100, 2)
                : null;

            $session->update([
                'score' => $score,
                'percentage' => $percentage,
                'passed' => $percentage !== null ? $percentage >= 50 : null,
            ]);

            return $session->refresh();
        }

        return $this->scoringManager->scoreSubmittedSession($session->refresh());
    }

    public function expire(ExamSession $session): ExamSession
    {
        if ($session->status !== ExamSession::STATUS_IN_PROGRESS) {
            return $session;
        }

        $session->update([
            'status' => ExamSession::STATUS_EXPIRED,
        ]);

        return $session->refresh();
    }

    public function cancel(ExamSession $session): ExamSession
    {
        if ($session->status === ExamSession::STATUS_SUBMITTED) {
            throw ValidationException::withMessages([
                'session' => 'A submitted exam session cannot be cancelled.',
            ]);
        }

        $session->update([
            'status' => ExamSession::STATUS_CANCELLED,
        ]);

        return $session->refresh();
    }

    private function ensureStudentCanUseAssignment(ExamAssignment $assignment, StudentProfile $student): void
    {
        if (! in_array($assignment->status, [ExamAssignment::STATUS_ASSIGNED, ExamAssignment::STATUS_OPEN], true)) {
            throw ValidationException::withMessages([
                'exam' => 'This exam assignment is not open.',
            ]);
        }

        if ($assignment->available_at && now()->lt($assignment->available_at)) {
            throw ValidationException::withMessages([
                'exam' => 'This exam is not available yet.',
            ]);
        }

        if ($assignment->due_at && now()->gt($assignment->due_at)) {
            throw ValidationException::withMessages([
                'exam' => 'This exam assignment is closed.',
            ]);
        }

        if ($assignment->student_profile_id !== null && $assignment->student_profile_id !== $student->id) {
            throw ValidationException::withMessages([
                'exam' => 'This exam is assigned to another student.',
            ]);
        }

        $isEnrolled = $student->courses()
            ->whereKey($assignment->course_id)
            ->wherePivot('enrollment_status', 'enrolled')
            ->exists();

        if (! $isEnrolled) {
            throw ValidationException::withMessages([
                'exam' => 'The student is not enrolled in the assigned course.',
            ]);
        }
    }
}
