# Student, Course, Exam Assignment, and Session Flow

## Core Idea

Students are still normal Laravel `users`, so they use the same Breeze login, password reset, email verification, and session functionality.

A user becomes a student only when they has a linked `student_profiles` row. This keeps students isolated from instructors/admins while still reusing Laravel authentication.

## Tables

### `users`

Laravel Breeze login account.

Used by:

- Students
- Instructors
- Admins
- Super admins

### `student_profiles`

Student-only profile data.

Important columns:

- `user_id`: Links the student to the Laravel user.
- `major_id`: Student's academic major.
- `student_number`: Unique student number.
- `academic_status`: active, inactive, graduated, suspended.
- `admission_year`
- `metadata`

### `majors`

Academic major/program.

Example:

- Software Engineering
- Cybersecurity
- Network Engineering

### `course_major`

Pivot table between courses and majors.

This supports:

- One major has many courses.
- One course can belong to more than one major.
- Courses can be required or optional.
- Courses can have a recommended level.

### `course_student`

Pivot table between courses and students.

This supports:

- Enrolling a student into a course.
- Keeping enrollment status.
- Tracking enrollment date.

### `exam_assignments`

Connects an instructor exam to a course and optionally to one student.

Two assignment styles:

1. Course-wide assignment:
   - `course_id` is set.
   - `student_profile_id` is null.
   - Every enrolled student can see/take it.

2. Student-specific assignment:
   - `course_id` is set.
   - `student_profile_id` is set.
   - Only that student can see/take it.

Important columns:

- `instructor_exam_id`
- `course_id`
- `student_profile_id`
- `assigned_by`
- `available_at`
- `due_at`
- `max_attempts`
- `status`
- `settings`

### `exam_sessions`

Tracks a student's attempt.

Important columns:

- `exam_assignment_id`
- `student_profile_id`
- `attempt_number`
- `started_at`
- `expires_at`
- `submitted_at`
- `status`
- `score`
- `max_score`
- `percentage`
- `passed`
- `metadata`

Statuses:

- `not_started`
- `in_progress`
- `submitted`
- `expired`
- `cancelled`

### `exam_session_answers`

Stores a student's answer for each question in a session.

Important columns:

- `exam_session_id`
- `instructor_exam_question_id`
- `answer_payload`
- `score`
- `feedback`
- `answered_at`

`answer_payload` is JSON so every question type can store its own answer shape.

Examples:

MCQ:

```json
{"selected_options": [0, 2]}
```

Essay:

```json
{"answer_text": "The explanation written by the student."}
```

Fill blank:

```json
{"blanks": {"blank_1": "router", "blank_2": "switch"}}
```

Coding:

```json
{"language": "PHP", "code": "<?php echo 'Hello';"}
```

## Eloquent Models

New models:

- `Major`
- `StudentProfile`
- `ExamAssignment`
- `ExamSession`
- `ExamSessionAnswer`

Updated existing models:

- `User`
- `Course`
- `InstructorExam`
- `InstructorExamQuestion`

## Relationships

### User

- `studentProfile()`
- `assignedExams()`
- `isStudent()`

### StudentProfile

- `user()`
- `major()`
- `courses()`
- `examAssignments()`
- `examSessions()`

### Major

- `students()`
- `courses()`

### Course

- `majors()`
- `students()`
- `examAssignments()`
- `instructorExams()`

### InstructorExam

- `course()`
- `instructor()`
- `questions()`
- `assignments()`

### ExamAssignment

- `exam()`
- `course()`
- `student()`
- `assignedBy()`
- `sessions()`
- `isCourseWide()`

### ExamSession

- `assignment()`
- `student()`
- `answers()`

### ExamSessionAnswer

- `session()`
- `question()`

## Session Manager

The service `App\Services\ExamSessionManager` contains session rules.

It currently supports:

- `start($assignment, $student)`
- `submit($session, $score = null)`
- `expire($session)`
- `cancel($session)`

Start rules:

- Assignment must be `assigned` or `open`.
- Current time must be after `available_at`.
- Current time must be before `due_at`.
- Student-specific assignments can only be used by that student.
- Student must be enrolled in the assigned course.
- Student cannot exceed `max_attempts`.

When a session starts:

- Status becomes `in_progress`.
- `started_at` is set.
- `expires_at` is calculated from exam duration.
- `max_score` is copied from exam total marks.

## Recommended UI Pages

### Admin / Instructor

- `/students`
- `/students/create`
- `/majors`
- `/majors/{major}/courses`
- `/courses/{course}/students`
- `/instructor/exams/{exam}/assignments`
- `/instructor/exams/{exam}/sessions`
- `/instructor/exams/{exam}/sessions/{session}`

### Student

- `/student/exams`
- `/student/exams/{assignment}`
- `/student/sessions/{session}`
- `/student/sessions/{session}/submit`

## Recommended Build Order

1. Student management screen.
2. Major management screen.
3. Assign courses to majors.
4. Enroll students in courses.
5. Assign exams to courses/students.
6. Student "My Exams" page.
7. Start exam session.
8. Save student answers.
9. Submit exam session.
10. Instructor session/results management.

