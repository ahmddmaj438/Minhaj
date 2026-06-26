<?php

namespace App\Services\Academics;

use App\Models\Exam\InstructorExam;
use App\Models\ExamAssignment;
use App\Models\ExamSession;
use App\Models\Major;
use App\Models\StudentProfile;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcademicWorkflowService
{
    public function createMajor(array $data, bool $isActive): Major
    {
        return DB::transaction(fn (): Major => Major::create([
            ...$data,
            'is_active' => $isActive,
        ]));
    }

    public function createStudentProfile(array $data): StudentProfile
    {
        return DB::transaction(function () use ($data): StudentProfile {
            $userId = $data['user_id'] ?? null;

            if (! $userId) {
                $user = User::create([
                    'name' => $data['student_name'],
                    'email' => $data['student_email'],
                    'password' => $data['student_password'],
                ]);

                $userId = $user->id;
            }

            return StudentProfile::create([
                'user_id' => $userId,
                'major_id' => $data['major_id'] ?? null,
                'student_number' => $data['student_number'],
                'academic_status' => $data['academic_status'],
                'admission_year' => $data['admission_year'] ?? null,
            ]);
        });
    }

    public function assignCourseToMajor(array $data, bool $isRequired): void
    {
        DB::transaction(function () use ($data, $isRequired): void {
            $major = Major::findOrFail($data['major_id']);

            $major->courses()->syncWithoutDetaching([
                $data['course_id'] => [
                    'is_required' => $isRequired,
                    'recommended_level' => $data['recommended_level'] ?? null,
                ],
            ]);
        });
    }

    public function enrollStudentInCourse(array $data): void
    {
        DB::transaction(function () use ($data): void {
            $student = StudentProfile::findOrFail($data['student_profile_id']);

            $student->courses()->syncWithoutDetaching([
                $data['course_id'] => [
                    'enrollment_status' => $data['enrollment_status'],
                    'enrolled_at' => $data['enrolled_at'] ?? now(),
                ],
            ]);
        });
    }

    public function assignTeacherToCourse(array $data, int $assignedBy): void
    {
        DB::transaction(function () use ($data, $assignedBy): void {
            $course = \App\Models\Course::findOrFail($data['course_id']);

            $course->teachers()->syncWithoutDetaching([
                $data['user_id'] => [
                    'role' => 'teacher',
                    'assigned_by' => $assignedBy,
                    'assigned_at' => now(),
                ],
            ]);
        });
    }

    public function createExamAssignment(
        array $data,
        int $assignedBy,
        bool $showScoreToStudent,
        bool $showFeedbackToStudent
    ): ExamAssignment {
        $data['available_at'] = $this->parseAssignmentDateTime($data['available_at'] ?? null);
        $data['due_at'] = $this->parseAssignmentDateTime($data['due_at'] ?? null);

        $this->ensureAssignmentDatesAreValid($data['available_at'], $data['due_at']);
        $this->ensureExamCanBeAssigned($data);
        $this->ensureStudentCanReceiveAssignment($data);
        $this->ensureAssignmentIsNotDuplicate($data);

        unset($data['show_score_to_student'], $data['show_feedback_to_student']);

        return DB::transaction(fn (): ExamAssignment => ExamAssignment::create([
            ...$data,
            'assigned_by' => $assignedBy,
            'settings' => [
                'show_score_to_student' => $showScoreToStudent,
                'show_feedback_to_student' => $showFeedbackToStudent,
            ],
        ]));
    }

    public function updateSessionStatus(ExamSession $session, string $status): ExamSession
    {
        return DB::transaction(function () use ($session, $status): ExamSession {
            $session->update([
                'status' => $status,
                'submitted_at' => $status === ExamSession::STATUS_SUBMITTED
                    ? ($session->submitted_at ?? now())
                    : $session->submitted_at,
            ]);

            return $session->refresh();
        });
    }

    private function ensureAssignmentDatesAreValid(?CarbonImmutable $availableAt, ?CarbonImmutable $dueAt): void
    {
        if ($availableAt && $dueAt && $dueAt->lt($availableAt)) {
            throw ValidationException::withMessages([
                'due_at' => 'The due time must be the same as or later than the available time.',
            ]);
        }
    }

    private function ensureExamCanBeAssigned(array $data): void
    {
        $exam = InstructorExam::findOrFail($data['instructor_exam_id']);

        if ($exam->status !== InstructorExam::STATUS_PUBLISHED) {
            throw ValidationException::withMessages([
                'instructor_exam_id' => 'Only published exams can be assigned to students.',
            ]);
        }

        if ((int) $exam->course_id !== (int) $data['course_id']) {
            throw ValidationException::withMessages([
                'course_id' => 'The selected exam belongs to another course.',
            ]);
        }
    }

    private function ensureStudentCanReceiveAssignment(array $data): void
    {
        if (empty($data['student_profile_id'])) {
            return;
        }

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

    private function ensureAssignmentIsNotDuplicate(array $data): void
    {
        $duplicate = ExamAssignment::query()
            ->where('instructor_exam_id', $data['instructor_exam_id'])
            ->where('course_id', $data['course_id'])
            ->when(
                $data['student_profile_id'] ?? null,
                fn ($query, int $studentId) => $query->where('student_profile_id', $studentId),
                fn ($query) => $query->whereNull('student_profile_id')
            )
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'student_profile_id' => 'This exam is already assigned to the selected course and student.',
            ]);
        }
    }

    private function parseAssignmentDateTime(?string $value): ?CarbonImmutable
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return CarbonImmutable::parse($value);
    }
}
