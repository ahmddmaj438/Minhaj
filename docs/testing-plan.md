# Minhaj Testing Plan

This plan describes the automated and manual testing approach for the Minhaj Laravel system.

## Automated Test Areas

### Authentication

Covered by feature tests for login, logout, registration, password reset, password confirmation, password update, and email verification.

### Authorization and Permissions

Covered by tests for screen access, action permissions, role assignment, role removal, direct URL blocking, student portal separation, teacher module access, and admin access management.

### Academic Setup

Covered by tests for program and student creation, course enrollment rules, teacher assignment rules, exam assignment rules, duplicate assignment prevention, same-day availability windows, and academic Excel upload preview and confirmation.

### Course and Exam Access Control

Covered by tests that verify students cannot access another student's assignment or session, teachers cannot edit another teacher's exam, question routes reject questions from another exam, and reports block unassigned course filters.

### Exam Builder

Covered by tests for creating/publishing ready exams, blocking incomplete publication, returning published exams to draft, duplicating questions, selecting question types, switching display formats, preview rendering, and supported question templates.

### Student Exam Taking

Covered by tests for available exam listing, starting sessions, draft answer creation, saving supported answer types, resume behavior, timer display, expired sessions, timeout submission, no reopening after submission, and activity logs.

### Grading

Covered by tests for objective scoring, manual grading, mixed auto/manual grading display, score recalculation, pending manual review, and final teacher score updates.

### AI Assistance

Covered by tests for AI configuration, connection testing, missing key behavior, rejected key behavior, timeout behavior, invalid format behavior, empty response behavior, provider fallback, browser AI suggestion storage, and human review before final grade.

### Reports

Covered by tests that render all report screens, verify friendly labels, search student results, verify assigned-course visibility, and block tampered report filters.

### UI and UX

Covered by feature rendering tests for key pages and templates. Manual browser review is still recommended for final graduation presentation polish.

### Error Handling and Security

Covered by tests for security headers, login rate limits, tampered permission values, invalid answer payloads, private upload access, friendly missing pages, unsafe upload rejection, duplicate database constraints, and unauthorized access.

## Browser Testing

Laravel Dusk is not installed in this project. Full browser automation was not added to avoid introducing a new dependency and changing the project setup. Recommended manual browser checks:

- Login as admin, teacher, and student.
- Create and publish an exam.
- Assign the exam to a student.
- Take and submit the exam as the student.
- Review and grade the submission as the teacher.
- Open each report and test filters.

## Commands

Run all tests:

```bash
php artisan test
```

Run only feature tests:

```bash
php artisan test tests/Feature
```

Run only unit tests:

```bash
php artisan test tests/Unit
```

Run the newly added report and AI review tests:

```bash
php artisan test tests/Feature/ReportsWorkflowTest.php tests/Feature/AiGradingHumanReviewTest.php
```

## Coverage Summary by Module

- Authentication: automated feature coverage exists.
- Authorization: automated feature coverage exists.
- Academic setup: automated feature and unit coverage exists.
- Excel upload: automated feature coverage exists.
- Exam builder: automated feature coverage exists.
- Student exam taking: automated feature coverage exists.
- Grading: automated feature coverage exists.
- AI assistance: automated feature coverage exists.
- Reports: automated feature coverage exists.
- Security and error handling: automated feature coverage exists.

## Remaining Manual Review

Manual review is recommended for final visual layout, translated text quality, real AI-provider behavior with production keys, and any export workflow if one is added later.
