<?php

namespace App\Services\Access;

use App\Models\Course;
use App\Models\Exam\InstructorExam;
use App\Models\ExamSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class LearningAccess
{
    public function courseQuery(User $user): Builder
    {
        $query = Course::query();

        if ($this->canManageEverything($user)) {
            return $query;
        }

        return $query->whereHas('teachers', fn (Builder $teacherQuery) => $teacherQuery->whereKey($user->id));
    }

    public function examQuery(User $user): Builder
    {
        $query = InstructorExam::query();

        if ($this->canManageEverything($user)) {
            return $query;
        }

        return $query->where(function (Builder $examQuery) use ($user): void {
            $examQuery
                ->where('instructor_id', $user->id)
                ->orWhereHas('course.teachers', fn (Builder $teacherQuery) => $teacherQuery->whereKey($user->id));
        });
    }

    public function canAccessCourse(User $user, Course|int|null $course): bool
    {
        if ($this->canManageEverything($user)) {
            return true;
        }

        $courseId = $course instanceof Course ? $course->id : $course;

        if (! $courseId) {
            return false;
        }

        return $user->assignedCourses()
            ->whereKey($courseId)
            ->wherePivot('role', 'teacher')
            ->exists();
    }

    public function canAccessExam(User $user, InstructorExam $exam): bool
    {
        if ($this->canManageEverything($user)) {
            return true;
        }

        return (int) $exam->instructor_id === (int) $user->id
            || $this->canAccessCourse($user, $exam->course_id);
    }

    public function canGradeSession(User $user, ExamSession $session): bool
    {
        if ($this->canManageEverything($user)) {
            return true;
        }

        $session->loadMissing('assignment.exam.course');
        $exam = $session->assignment?->exam;

        return $exam instanceof InstructorExam && $this->canAccessExam($user, $exam);
    }

    private function canManageEverything(User $user): bool
    {
        return $user->isSuperAdmin()
            || $user->hasPermission('screen.admin.access.index.view');
    }
}
