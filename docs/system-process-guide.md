# Minhaj System Process Guide

This guide explains how the main system processes work in simple language. It is written for project examiners, teachers, students, and reviewers, not only developers.

## 1. User Login Process

The user opens the login page and enters an email address and password.

The system checks that the account exists and that the password is correct. It also protects the login page from too many repeated failed attempts.

After a successful login, the system sends the user to the correct starting area. Students go to their exam portal. Teachers and administrators go to the dashboard or to the screens they are allowed to use.

If login fails, the user stays on the login page and sees a friendly message. The system does not show private technical details about why the login failed.

## 2. Role and Permission Process

The system decides what a user can do from assigned roles and permissions.

An administrator can manage broad system areas such as access, users, academic setup, reports, and settings. A teacher can work with assigned courses, exams, grading, and related reports. A student can open the student exam portal and work only on exams assigned to them.

Screens are protected before they load. If a user tries to open a page they are not allowed to use, the system blocks the page and shows an access message.

Actions are also protected. For example, viewing a grading page and saving a grade are separate permissions.

## 3. Academic Setup Process

Academic setup prepares the data needed before exams can be used.

An authorized user creates programs, creates courses, adds student profiles, and connects students to courses. Teachers can also be linked to the courses they teach.

After courses, teachers, and students are connected, the system can safely decide which teacher can create or grade exams for a course and which student can see an assigned exam.

The system checks required fields, duplicate records, and invalid relationships. For example, an exam cannot be assigned to a student who is not enrolled in the course.

## 4. Excel Upload Process

If academic upload is used, the user downloads or prepares a file using the expected academic setup format.

The file can include program information, course information, students, course enrollments, and exam setup rows. The system checks the uploaded file before saving it.

During preview, the system reports which rows look correct, which rows are duplicates, and which rows have missing or invalid information. Nothing is saved until the user confirms the preview.

After confirmation, valid records are saved and skipped or failed rows are reported in a friendly way.

## 5. Exam Creation Process

A teacher or administrator creates an exam by selecting the course, entering the title, duration, marks, and availability details.

Questions are added to the exam. Supported question styles include objective questions such as multiple choice, true or false, matching, and fill in the blank, plus written or practical questions such as essay, coding, and packet tracer style questions.

Each question has marks. Written questions may also include an expected answer, rubric, review guidance, or instructions for manual grading.

Before an exam is published, the system checks that the exam is ready. Incomplete exams cannot be published. Publishing makes the exam available for assignment and student access.

## 6. Student Exam Process

The student opens the exam portal and sees only exams connected to their enrolled courses and valid assignments.

When the student starts an exam, the system creates an exam session and prepares answer spaces for the exam questions. If the student refreshes the browser while the session is still open, the same active session is resumed instead of creating a duplicate attempt.

The timer is based on the exam and assignment timing. If the session expires, the system marks it expired and prevents further editing.

Students can save answers while the session is open. When they submit, the system saves the latest answers, marks the session as submitted, and prevents reopening or editing the submitted attempt.

## 7. Grading Process

Objective questions are graded automatically when the student submits the exam.

Written, essay, coding, or practical answers are marked as needing teacher review. The teacher opens the grading screen, reviews each answer, enters the final score, and can add feedback.

After manual grading, the system recalculates the total score, percentage, pass status, and whether any answers are still pending review.

Grades are not treated as final for written answers until the teacher saves the score.

## 8. AI Grading Process

AI assistance helps teachers review written answers faster. It is an assistant only, not the final grading authority.

The AI request can use the exam question, student answer, expected answer if available, rubric, maximum mark, and course or exam context if needed.

The system should not send passwords, API keys, unrelated private account information, or unnecessary student identity details to the AI service.

The AI compares the student answer with the question, expected answer, and rubric. It returns a suggested score, feedback, strengths, weaknesses, confidence when available, and a short reasoning summary.

The suggestion is stored with the answer so the teacher can review it. The teacher can accept the idea, change the score, ignore it, or grade manually.

If the AI service fails, the API key is missing, or the response is unclear, the system records a safe message and does not invent a final score. The teacher can still grade the answer manually.

Manual grading overrides the AI suggestion because the teacher remains responsible for the final grade.

## 9. Reports Process

Reports collect information from exams, assignments, attempts, answers, grades, courses, teachers, students, and AI assistance.

Report filters let users narrow results by course, exam, status, or search text. Summary cards show totals such as exams, published exams, submitted attempts, pending grading, and average scores.

Tables show the detailed records for the selected report. If there are no records, the system shows an empty state instead of an error.

Permissions affect report visibility. Administrators can see the wider system data allowed to their role. Teachers see reports for assigned courses and exams.

## 10. Result Publishing Process

Scores become visible to students only when the assignment settings allow score or feedback visibility.

Objective exam results can be available after submission when score visibility is enabled. Written-answer results may remain pending until the teacher finishes manual review.

Students can see only their own attempts and results. They cannot view another student's result by changing the address in the browser.

Information that is not meant for students, such as internal review notes or unpublished grades, remains hidden.

## 11. Error Handling Process

When users enter wrong or incomplete information, the system shows validation messages near the form and uses friendly labels.

If a requested record no longer exists, access is unauthorized, an upload fails, or an exam session expires, the system handles the problem safely and shows a simple message.

Technical error details are hidden from normal users. The application keeps sensitive details in logs or configuration where appropriate, not on public screens.

## 12. Security Process

The system protects exams by checking login status, student enrollment, assignment ownership, exam publication status, time windows, attempt limits, and session ownership.

It protects grades by allowing only authorized teachers or administrators to open grading screens and save manual scores.

It protects access by checking page permissions and action permissions separately.

It prevents unauthorized URL access by checking the signed-in user against the requested course, exam, assignment, session, or answer.

Forms use CSRF protection. Uploaded files are validated. User input is validated before saving. Sensitive keys are stored in environment configuration and must not be shown in logs or screens.

## How the AI Works in This System

### 1. Purpose of AI

AI helps teachers grade descriptive, essay, coding, or written answers faster and more consistently. It gives a recommendation, but it does not replace teacher judgement.

### 2. AI Input

The AI can use:

- Exam question
- Student answer
- Model or expected answer if available
- Rubric or teacher guidance
- Maximum mark
- Course or exam context when useful

### 3. AI Processing

The system sends a structured grading request to the selected AI service. The request asks the AI to compare the student answer against the expected answer and rubric, then return a structured evaluation.

The AI should focus on correctness, completeness, rubric evidence, and clarity. It should not score mainly by answer length.

### 4. AI Output

The expected AI response includes:

- Suggested score
- Feedback
- Strengths
- Weaknesses or improvements
- Confidence if available
- Short reasoning summary

### 5. Teacher Review

The teacher reviews the AI suggestion. The teacher may accept it, edit the final score, reject it, or grade manually.

The final saved grade belongs to the teacher's review process, not directly to the AI response.

### 6. Failure Handling

If the AI service is unavailable, the API key is missing, the response is invalid, or the AI is uncertain, the system keeps the answer pending for teacher review.

The teacher can still grade normally. A failed AI request must not block manual grading.

### 7. Data Safety

The system should send only the data needed for grading. It should avoid sending passwords, private account information, secret keys, or unrelated student data.

API keys must stay in the environment file or secure server configuration. Logs and screens should not expose secrets.

### 8. Limitations

AI may misunderstand an answer, miss context, or give an unfair suggestion. It should not be used as the only grading authority.

Human review is required for fairness, accountability, and academic confidence.
