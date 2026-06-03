<?php

namespace App\Services\Exams;

use App\Models\ExamAssignment;
use App\Models\ExamSession;
use App\Models\StudentProfile;
use Illuminate\Support\Collection;

class ExamAvailabilityService
{
    public function availableForStudent(StudentProfile $student): Collection
    {
        $courseIds = $student->courses()
            ->wherePivot('enrollment_status', 'enrolled')
            ->pluck('courses.id');

        if ($courseIds->isEmpty()) {
            return collect();
        }

        return ExamAssignment::query()
            ->with([
                'course',
                'exam.course',
                'exam.questions',
                'sessions' => fn ($query) => $query
                    ->where('student_profile_id', $student->id)
                    ->latest(),
            ])
            ->whereIn('course_id', $courseIds)
            ->whereIn('status', [ExamAssignment::STATUS_ASSIGNED, ExamAssignment::STATUS_OPEN])
            ->where(function ($query) use ($student): void {
                $query
                    ->whereNull('student_profile_id')
                    ->orWhere('student_profile_id', $student->id);
            })
            ->latest()
            ->get()
            ->filter(function (ExamAssignment $assignment) use ($student): bool {
                $submitted = $this->latestSubmittedSession($assignment, $student);
                if ($submitted) {
                    return true;
                }

                return $this->isInsideAvailabilityWindow($assignment)
                    && (
                        $this->hasAttemptCapacity($assignment, $student)
                        || $this->activeSession($assignment, $student) !== null
                    );
            })
            ->values();
    }

    public function activeSession(ExamAssignment $assignment, StudentProfile $student): ?ExamSession
    {
        $sessions = $assignment->relationLoaded('sessions')
            ? $assignment->sessions
            : $assignment->sessions()
                ->where('student_profile_id', $student->id)
                ->latest()
                ->get();

        return $sessions->first(fn (ExamSession $session): bool => $session->status === ExamSession::STATUS_IN_PROGRESS);
    }

    public function attemptsUsed(ExamAssignment $assignment, StudentProfile $student): int
    {
        if ($assignment->relationLoaded('sessions')) {
            return $assignment->sessions
                ->where('student_profile_id', $student->id)
                ->count();
        }

        return $assignment->sessions()
            ->where('student_profile_id', $student->id)
            ->count();
    }

    public function latestSubmittedSession(ExamAssignment $assignment, StudentProfile $student): ?ExamSession
    {
        $sessions = $assignment->relationLoaded('sessions')
            ? $assignment->sessions
            : $assignment->sessions()
                ->where('student_profile_id', $student->id)
                ->latest()
                ->get();

        return $sessions->first(fn (ExamSession $session): bool => $session->status === ExamSession::STATUS_SUBMITTED);
    }

    public function attemptsRemaining(ExamAssignment $assignment, StudentProfile $student): int
    {
        return max((int) $assignment->max_attempts - $this->attemptsUsed($assignment, $student), 0);
    }

    private function hasAttemptCapacity(ExamAssignment $assignment, StudentProfile $student): bool
    {
        return $this->attemptsUsed($assignment, $student) < (int) $assignment->max_attempts;
    }

    private function isInsideAvailabilityWindow(ExamAssignment $assignment): bool
    {
        if ($assignment->available_at && now()->lt($assignment->available_at)) {
            return false;
        }

        if ($assignment->due_at && now()->gt($assignment->due_at)) {
            return false;
        }

        return true;
    }
}
