# Minhaj Project Report

Generated for the Laravel project at `C:\xampp\htdocs\Minhaj`.

## 1. Project Summary

Minhaj is a Laravel 12 application for managing users, permissions, TCExam data, and an instructor exam-building workflow. The project combines:

- Laravel Breeze authentication.
- A custom role/group/permission system.
- Admin screens for access management and super-user management.
- A TCExam database integration for tests, modules, subjects, questions, answers, test users, logs, and result snapshots.
- A local instructor exam builder with multiple question types.
- A dashboard showing analytics about users, exams, questions, answers, and results.
- Tailwind CSS, Alpine.js, and Vite for frontend styling and behavior.

The application currently uses SQLite through `database/database.sqlite`.

## 2. Main Technology Stack

- `composer.json`: Laravel 12 project with PHP `^8.2`, Laravel Tinker, Breeze, Pint, Sail, PHPUnit, Faker, Mockery, and Collision.
- `package.json`: Vite, Tailwind CSS, Alpine.js, Axios, Laravel Vite plugin, and Tailwind forms plugin.
- `vite.config.js`: Builds `resources/css/app.css` and `resources/js/app.js`.
- `tailwind.config.js`: Scans Blade templates and Laravel pagination views for Tailwind classes.
- `phpunit.xml`: Test configuration for Laravel/PHPUnit.

## 3. Application Entry Flow

The request starts at:

- `public/index.php`: Laravel public entry point. It loads the app and handles HTTP requests.
- `bootstrap/app.php`: Configures the Laravel application, routes, middleware aliases, and exception handling.
- `routes/web.php`: Main web routes for dashboard, profile, admin, TCExam data, exam wizard, instructor exams, and groups.
- `routes/auth.php`: Authentication routes created by Laravel Breeze.
- `routes/console.php`: Console route file, currently standard Laravel structure.

Important middleware aliases are registered in `bootstrap/app.php`:

- `screen` => `App\Http\Middleware\CheckScreenPermission`
- `rule` => `App\Http\Middleware\CheckRuleAccess`

## 4. Routing

### `routes/web.php`

This is the main route file.

Important routes:

- `/`: Shows `welcome.blade.php`.
- `/dashboard`: Authenticated, verified, screen-protected dashboard. It gathers counts and chart data for users, exams, questions, answers, result snapshots, courses, groups, TCExam subjects, and modules.
- `/profile`: Edit, update, and delete profile.
- `/users`: User listing and user creation.
- `/admin/access`: Group, role, screen, button, DB permission, and user assignment management.
- `/admin/super-users`: Grant or revoke super admin role.
- `/admin/data/tables`: Generic TCExam CRUD over allowed `tce_` and `tcexam_` tables.
- `/admin/exams/wizard`: Multi-step TCExam test creation wizard.
- `/instructor/exams`: Local instructor exam creation, editing, question selection, question editing, ordering, previewing, and deletion.
- `/groups`: Alias to the access management page.

The dashboard route currently contains data preparation directly in the route closure. It could later be moved into a `DashboardController` for cleaner separation.

### `routes/auth.php`

Standard Breeze auth routes:

- Register.
- Login.
- Forgot password.
- Reset password.
- Email verification.
- Confirm password.
- Password update.
- Logout.

## 5. Middleware

### `app/Http/Middleware/CheckScreenPermission.php`

This middleware protects page access.

How it works:

- Reads the current route name.
- Applies only to `GET` and `HEAD` requests.
- Skips core auth routes like logout, verification, and password update.
- Checks permission using:

```php
$user->can('screen.' . $routeName . '.view')
```

Example:

- Route name: `dashboard`
- Permission needed: `screen.dashboard.view`

If the user does not have the permission, it aborts with `403`.

### `app/Http/Middleware/CheckRuleAccess.php`

This middleware checks a resource/action permission.

It expects route parameters like:

```php
middleware('rule:users,create')
```

Then checks:

```php
$user->hasPermission('users_create')
```

In the current project, most permission checks are done directly inside controllers using `abort_unless(...)`, while this middleware is available for future route-level checks.

## 6. Authorization and Permission System

### `app/Providers/AppServiceProvider.php`

Registers a global `Gate::before` rule.

Permission priority:

1. In testing, all abilities return `true`.
2. Root super admin always returns `true`.
3. Ability `grant_super_admin` returns `false` unless root super admin already matched.
4. Super admins return `true`.
5. Normal users are checked through `User::hasPermission()`.
6. Otherwise Laravel continues normal Gate handling.

### `app/Models/User.php`

The user model extends Laravel's `Authenticatable`.

Important behavior:

- Hidden fields: password and remember token.
- Password is hashed through Laravel casts.
- Users belong to many groups.
- Users belong to many roles.
- `hasPermission($permissionName)` checks:
  - Direct role permissions.
  - Group role permissions.
- `isRootSuperAdmin()` checks if email equals `super_admin_!@minhaj.com`.
- `isSuperAdmin()` checks root super admin or role slug `super_admin`.

### Permission Model Structure

- `Group.php`: Groups have users, rules, and roles.
- `Role.php`: Roles have users, groups, and permissions.
- `Permission.php`: Permission names are stored as strings.
- `Rule.php`: Older/simple resource-action-effect model. Groups can have rules.

The main active permission system uses `roles` and `permissions`. Permission names follow patterns:

- Screen permissions: `screen.dashboard.view`
- Button permissions: `button.dashboard.group_management`
- DB permissions: `db.users.insert`, `db.instructor_exams.update`

## 7. Controllers

### `app/Http/Controllers/AdminAccessController.php`

Controls the admin access management UI.

Main methods:

- `index()`: Loads groups, users, screens, buttons, DB tables, and selected group permissions.
- `storeGroup()`: Creates a new group and creates/attaches a matching role.
- `updateGroupScreens()`: Stores selected screen permissions on the group's role.
- `updateGroupButtons()`: Stores selected button permissions.
- `updateGroupDbAccess()`: Stores selected database permissions.
- `updateGroupUsers()`: Assigns users to a group.
- `availableScreens()`: Builds a screen list from named GET routes.
- `availableButtons()`: Builds a button permission map.
- `syncPermissionPrefix()`: Replaces permissions for one prefix, such as `screen.` or `button.`.

This controller is central to the custom authorization system.

### `app/Http/Controllers/SuperUserController.php`

Manages super admin role assignment.

Main methods:

- `index()`: Lists users and root super admin email.
- `grant()`: Adds the `super_admin` role to a user.
- `revoke()`: Removes the `super_admin` role, but never from the root super admin.

Only root super admin can grant/revoke super admins because of `grant_super_admin`.

### `app/Http/Controllers/UserManagementController.php`

Basic admin user management.

Main methods:

- `index()`: Lists users.
- `create()`: Shows create user form.
- `store()`: Validates and creates user.

It checks both button permission and DB insert permission before creating users.

### `app/Http/Controllers/ProfileController.php`

Standard Breeze profile controller with extra DB permission checks.

Main methods:

- `edit()`: Shows profile edit page.
- `update()`: Updates profile, requires `db.users.update`.
- `destroy()`: Deletes current user, requires `db.users.delete`.

### Auth Controllers

Located in `app/Http/Controllers/Auth`.

These are Laravel Breeze controllers:

- `AuthenticatedSessionController.php`: Login/logout flow. Redirects users to dashboard if they can view dashboard.
- `RegisteredUserController.php`: Registration. Redirects users to dashboard only if allowed.
- `PasswordResetLinkController.php`: Sends reset link.
- `NewPasswordController.php`: Stores new password after reset.
- `PasswordController.php`: Updates logged-in user password.
- `ConfirmablePasswordController.php`: Confirm password flow.
- `EmailVerificationPromptController.php`: Shows verification prompt.
- `EmailVerificationNotificationController.php`: Sends verification email.
- `VerifyEmailController.php`: Handles signed verification link.

### `app/Http/Controllers/TCExamCrudController.php`

Generic admin CRUD over TCExam-related tables.

Important rules:

- Only tables starting with `tce_` or `tcexam_` are allowed.
- Uses SQLite PRAGMA to read column metadata.
- Builds forms dynamically from database columns.
- Detects input type from column name/type:
  - Boolean fields become checkboxes.
  - Text fields become textareas.
  - Date/time fields become date or datetime inputs.
  - Numeric fields become number inputs.
  - Email/password names get matching input types.
- Looks up foreign key options automatically.
- Requires both button permission and DB table permission for create/update/delete.

Main methods:

- `tables()`: Shows available TCExam tables.
- `index($table)`: Shows up to 100 rows.
- `create($table)`: Shows dynamic create form.
- `store($table)`: Inserts row.
- `edit($table, $id)`: Shows edit form.
- `update($table, $id)`: Updates row.
- `destroy($table, $id)`: Deletes row.

### `app/Http/Controllers/ExamWizardController.php`

Creates TCExam tests through a 3-step wizard.

Flow:

1. Step 1 collects test name, description, duration, date range, pass threshold.
2. Step 2 collects question type, difficulty, quantity, answers per question, and optional module.
3. Step 3 selects subjects.
4. Finish inserts into:
   - `tce_tests`
   - `tce_test_subject_set`
   - `tce_test_subjects`

Uses session storage between steps.

### Instructor Exam Controllers

Located in `app/Http/Controllers/Instructor`.

These controllers power the local instructor exam builder.

#### `ExamSetupController.php`

Creates, edits, updates, and deletes local instructor exams.

Important methods:

- `create()`: Shows exam creation form and existing exams.
- `store()`: Creates a draft exam.
- `edit()`: Shows exam metadata and question list.
- `update()`: Updates exam metadata.
- `destroy()`: Deletes an exam.
- `questionEditRoutes()`: Maps each question to its correct edit route.

Only the exam instructor or a super admin can edit/delete an exam.

#### `QuestionTypeController.php`

Lets the instructor select a question type.

It uses `QuestionTypeCatalog` to create an `InstructorExamQuestion` with:

- `type`
- `category`
- `title`
- `position`
- `marks`
- `programming_language`
- `prompt`
- `settings`

Then redirects to the dedicated builder for that question type.

#### `McqQuestionController.php`

Edits multiple-choice questions.

Stores:

- Question text.
- Instructions.
- Marks.
- Difficulty/topic.
- Option list.
- Correct options.
- Shuffle settings.
- Whether multiple correct answers are allowed.

#### `TrueFalseQuestionController.php`

Edits true/false questions.

Supports:

- Normal true/false.
- True/false with correction.
- Wrong terms and corrections.
- Explanation.

#### `MatchingQuestionController.php`

Edits matching questions.

Stores pairs of left/right values, optional notes, and shuffle settings.

#### `FillBlankQuestionController.php`

Edits fill-in-the-blank questions.

Stores blanks with:

- Label.
- Accepted answers.
- Hint.
- Case sensitivity.
- Whitespace trimming.

#### `EssayQuestionController.php`

Edits essay or short-answer questions.

Stores:

- Question prompt.
- Instructions.
- Expected/model answer.
- Rubric.
- Min/max word count.

#### `CodingQuestionController.php`

Edits coding and technical questions.

Used for SQL, PL/SQL, C++, Java, mobile Java, HTML, CSS, JavaScript, PHP, etc.

Stores:

- Problem statement.
- Instructions.
- Starter code.
- Expected output.
- Constraints.
- Sample input/output.
- Test case notes.

#### `PacketTracerQuestionController.php`

Edits Packet Tracer/networking questions.

Stores:

- Scenario.
- Instructions.
- Expected tasks.
- Configuration notes.
- Optional `.pkt` file.
- Optional topology screenshot.

Uploaded files are saved under:

```text
storage/app/private/exam-resources/exams/{exam}/questions/{question}
```

#### `QuestionOrderingController.php`

Manages question order, marks, and deletion.

Main methods:

- `index()`: Shows all questions in order.
- `update()`: Saves new order and marks.
- `destroy()`: Deletes a question and normalizes positions.

#### `ExamPreviewController.php`

Shows a preview of an instructor exam and total question marks.

## 8. Form Requests and Validation

Form requests live in `app/Http/Requests`.

### Auth and Profile Requests

- `Auth/LoginRequest.php`: Validates login and rate-limits failed attempts.
- `ProfileUpdateRequest.php`: Validates profile name/email.

### Instructor Requests

- `StoreInstructorExamRequest.php`: Validates title, course, duration, date range, marks, and intent.
- `UpdateInstructorExamRequest.php`: Same as store but without intent.
- `StoreQuestionTypeSelectionRequest.php`: Ensures selected type exists in `QuestionTypeCatalog`.
- `UpdateMcqQuestionRequest.php`: Validates MCQ prompt, options, correct options, multiple-correct rules.
- `UpdateTrueFalseQuestionRequest.php`: Validates statement, answer, corrections when required.
- `UpdateMatchingQuestionRequest.php`: Requires at least two complete matching pairs.
- `UpdateFillBlankQuestionRequest.php`: Requires at least one blank with accepted answer.
- `UpdateEssayQuestionRequest.php`: Validates essay prompt, rubric, word limits, marks.
- `UpdateCodingQuestionRequest.php`: Validates coding prompt, starter code, expected output, samples, marks.
- `UpdatePacketTracerQuestionRequest.php`: Validates Packet Tracer scenario and checks `.pkt` extension.
- `UpdateQuestionOrderRequest.php`: Validates question id, position, and marks.

These request classes keep validation out of controllers.

## 9. Models

### Core Models

- `User.php`: Auth user, group/role relationships, permission checks.
- `Group.php`: Group name/slug, users, rules, roles.
- `Role.php`: Role name/slug, users, groups, permissions.
- `Permission.php`: Permission string, roles relationship.
- `Rule.php`: Resource/action/effect model, groups relationship.

### Course and Instructor Exam Models

- `Course.php`: Course code, name, description, active flag, instructor exams relationship.
- `Exam/InstructorExam.php`: Local instructor exam metadata, course/instructor/questions relationships.
- `Exam/InstructorExamQuestion.php`: Local question record, JSON prompt/settings, belongs to exam.

### TCExam Integration Models

- `TCExamTest.php`: Maps to `tce_tests`, primary key `test_id`, no timestamps.
- `TCExamTestUser.php`: Maps to `tce_tests_users`, primary key `testuser_id`, no timestamps.
- `TCExamTestLink.php`: App-side wrapper linking a TCExam test to a context.
- `TCExamResultSnapshot.php`: Stores imported/synced TCExam result data.

Important fix:

`TCExamResultSnapshot.php` defines:

```php
protected $table = 'tcexam_result_snapshots';
```

Without that, Laravel guesses `t_c_exam_result_snapshots`, which is wrong.

## 10. Question Type Catalog

### `app/Support/Exams/QuestionTypeCatalog.php`

Defines all supported instructor question types.

Categories:

- Objective:
  - MCQ
  - True/False
  - True/False + Correction
  - Matching
- Text:
  - Fill in the Blank
  - Essay / Short Answer
- Coding:
  - SQL
  - PL/SQL
  - C++
  - Java
  - Java Mobile
  - HTML
  - CSS
  - JavaScript
  - PHP
- Networking:
  - Packet Tracer

Provides helper methods:

- `categories()`
- `types()`
- `keys()`
- `find($key)`

## 11. Database and Migrations

### Laravel Default Tables

- `users`
- `password_reset_tokens`
- `sessions`
- `cache`
- `cache_locks`
- `jobs`
- `job_batches`
- `failed_jobs`

### Custom Access Tables

- `groups`
- `rules`
- `group_user`
- `group_rule`
- `roles`
- `permissions`
- `group_role`
- `role_user`
- `permission_role`

These tables support the group/role/permission system.

### TCExam Tables

Migration `2026_05_06_000150_create_tcexam_core_tables.php` creates TCExam-style tables if they do not already exist:

- `tce_sessions`
- `tce_users`
- `tce_modules`
- `tce_subjects`
- `tce_questions`
- `tce_answers`
- `tce_tests`
- `tce_test_subject_set`
- `tce_test_subjects`
- `tce_tests_users`
- `tce_tests_logs`
- `tce_tests_logs_answers`
- `tce_user_groups`
- `tce_usrgroups`
- `tce_testgroups`
- `tce_sslcerts`
- `tce_testsslcerts`
- `tce_testuser_stat`

### App TCExam Wrapper Tables

- `tcexam_test_links`: Links a TCExam test to an app context.
- `tcexam_result_snapshots`: Stores synced result data, scores, percentages, pass/fail, start/end times, and raw payload.

### Instructor Exam Tables

- `courses`: Course metadata.
- `instructor_exams`: Local app exam metadata.
- `instructor_exam_questions`: Local app question definitions with JSON prompt/settings.

## 12. Seeders and Database Files

- `database/seeders/DatabaseSeeder.php`: Main seeder. It appears to seed app data, permissions, users, groups, TCExam data, courses, or exam builder defaults.
- `database/seeders/d.php`: Additional seeder/helper file with a short non-standard name. It should be reviewed later and renamed if it is important.
- `database/database.sqlite`: Current SQLite database file.
- `database/minhaj.sql`: SQL dump/import file.
- `database/schema/system_schema_dump.sql`: Schema dump file.

## 13. Views

Views live in `resources/views`.

### Layouts

- `layouts/app.blade.php`: Main authenticated layout. Loads Vite assets and navigation.
- `layouts/guest.blade.php`: Guest/auth layout.
- `layouts/navigation.blade.php`: Top navigation with permission-aware links.

### Dashboard

- `dashboard.blade.php`: Modern analytics dashboard.

It displays:

- Total users.
- Exams.
- Questions.
- Answers.
- Results.
- Pass rate.
- Exam mix donut.
- Weekly completion bars.
- Question type bars.
- Recent instructor exams.
- Course/group/subject/module counts.
- Group management shortcut when permitted.

It uses scroll-reveal hooks:

```html
data-reveal
dashboard-reveal
dashboard-card-motion
dashboard-bar-fill
dashboard-progress-fill
dashboard-donut
```

### Admin Views

- `admin/access/index.blade.php`: Manage groups, users, screens, buttons, DB permissions.
- `admin/super-users/index.blade.php`: Grant/revoke super admin role.

### Auth Views

Standard Breeze auth UI:

- `auth/login.blade.php`
- `auth/register.blade.php`
- `auth/forgot-password.blade.php`
- `auth/reset-password.blade.php`
- `auth/confirm-password.blade.php`
- `auth/verify-email.blade.php`

### Profile Views

- `profile/edit.blade.php`
- `profile/partials/update-profile-information-form.blade.php`
- `profile/partials/update-password-form.blade.php`
- `profile/partials/delete-user-form.blade.php`

### User Views

- `users/index.blade.php`: List users.
- `users/create.blade.php`: Create user form.

### TCExam Data Views

- `data/tables.blade.php`: Table selector/list.
- `data/index.blade.php`: Generic table row list.
- `data/form.blade.php`: Dynamic create/edit form.

### TCExam Wizard Views

- `exams/wizard/step1.blade.php`
- `exams/wizard/step2.blade.php`
- `exams/wizard/step3.blade.php`

These correspond to `ExamWizardController`.

### Instructor Exam Views

- `instructor/exams/create.blade.php`: Create/list instructor exams.
- `instructor/exams/edit.blade.php`: Edit exam metadata and manage questions.
- `instructor/exams/question-types.blade.php`: Select question type.
- `instructor/exams/preview.blade.php`: Preview exam and questions.
- `instructor/exams/partials/workspace-nav.blade.php`: Shared navigation inside the exam builder.
- `instructor/exams/questions/mcq.blade.php`
- `instructor/exams/questions/true-false.blade.php`
- `instructor/exams/questions/matching.blade.php`
- `instructor/exams/questions/fill-blank.blade.php`
- `instructor/exams/questions/essay.blade.php`
- `instructor/exams/questions/coding.blade.php`
- `instructor/exams/questions/packet-tracer.blade.php`
- `instructor/exams/questions/order.blade.php`

### Components

Reusable Breeze/Tailwind components:

- `application-logo`
- `auth-session-status`
- `danger-button`
- `dropdown`
- `dropdown-link`
- `input-error`
- `input-label`
- `modal`
- `nav-link`
- `primary-button`
- `responsive-nav-link`
- `secondary-button`
- `text-input`

## 14. Frontend Assets

### `resources/css/app.css`

Contains:

- Tailwind imports.
- Brand CSS variables.
- Global page background.
- Dashboard motion classes.

Dashboard motion is intentionally light:

- `dashboard-reveal`: Subtle fade/translate on scroll.
- `dashboard-card-motion`: Small hover lift.
- `dashboard-bar-fill`: Animated vertical chart bars.
- `dashboard-progress-fill`: Animated horizontal bars.
- `dashboard-donut`: Gentle donut scale-in.
- `prefers-reduced-motion`: Disables animation for users who prefer reduced motion.

### `resources/js/app.js`

Contains:

- Bootstrap import.
- Alpine.js setup.
- `revealDashboard()` IntersectionObserver.

The scroll animation logic:

- Finds elements with `data-reveal`.
- Adds `is-visible` when they enter the viewport.
- Unobserves after reveal.
- Falls back to immediately visible if `IntersectionObserver` is unavailable.

### `resources/js/bootstrap.js`

Standard Laravel/Axios bootstrap file.

## 15. Tests

Tests are under `tests`.

### Feature Tests

- Auth tests:
  - Login screen.
  - Login success/failure.
  - Logout.
  - Email verification.
  - Password confirmation.
  - Password reset.
  - Password update.
  - Registration.
- `ProfileTest.php`: Profile display/update/delete tests.
- `ExampleTest.php`: Basic application response test.

### Unit Tests

- `Unit/ExampleTest.php`: Basic true assertion.

Current verified status:

```text
25 tests passed, 61 assertions
```

## 16. Important Workflows

### User Login and Authorization

1. User visits `/login`.
2. `AuthenticatedSessionController` authenticates using `LoginRequest`.
3. User is redirected to dashboard only if they can view dashboard.
4. Protected routes use `auth`, `verified`, and/or `screen`.
5. Screen middleware checks `screen.{route-name}.view`.
6. Controller actions also check button and DB permissions.

### Admin Creates a Group

1. Admin opens `/admin/access`.
2. Creates group.
3. Controller creates group and matching role.
4. Admin assigns screens, buttons, DB permissions, and users.
5. Permissions are stored in `permissions` and linked to role through `permission_role`.
6. Group is linked to role through `group_role`.
7. Users inherit role permissions through group membership.

### TCExam CRUD

1. Admin opens `/admin/data/tables`.
2. Controller lists allowed `tce_` and `tcexam_` tables.
3. User selects a table.
4. Controller reads schema dynamically.
5. Forms are generated dynamically from columns.
6. Create/update/delete requires button permission plus DB permission.

### TCExam Wizard

1. Step 1 saves exam metadata to session.
2. Step 2 saves test/question configuration to session.
3. Step 3 selects subjects.
4. Finish writes to TCExam tables.

### Instructor Exam Builder

1. Instructor creates local exam.
2. Instructor selects question type.
3. App creates a question placeholder.
4. Dedicated question controller edits details.
5. Details are stored mostly in JSON columns:
   - `prompt`
   - `settings`
6. Instructor can order questions.
7. Instructor can preview exam.

### Dashboard

1. `/dashboard` route queries app and TCExam tables.
2. Data is passed to `dashboard.blade.php`.
3. Blade renders cards and charts.
4. `resources/js/app.js` reveals sections on scroll.

## 17. Current Changes Recently Added

Recent implemented changes include:

- Dashboard analytics UI.
- Dashboard motion and scroll reveal.
- Fix for `TCExamResultSnapshot` table name.
- Instructor exam edit/update/delete route additions.
- Instructor exam edit view and partials are currently present in the working tree.

## 18. File Appendix

### Root Files

- `.env`: Local environment configuration.
- `.env.example`: Example environment file.
- `.editorconfig`: Editor formatting rules.
- `.gitattributes`: Git attributes.
- `.gitignore`: Ignored files.
- `.phpunit.result.cache`: PHPUnit cache.
- `.styleci.yml`: StyleCI config.
- `artisan`: Laravel CLI entry.
- `CHANGELOG.md`: Laravel/framework changelog.
- `composer.json`: PHP dependencies and scripts.
- `composer.lock`: Locked PHP dependencies.
- `package.json`: JS dependencies and scripts.
- `package-lock.json`: Locked JS dependencies.
- `phpunit.xml`: Test config.
- `postcss.config.js`: PostCSS config.
- `README.md`: Default/readme documentation.
- `tailwind.config.js`: Tailwind config.
- `vite.config.js`: Vite config.
- `good.html`, `test.html`: Standalone HTML files, likely design/reference experiments.
- `PROJECT_REPORT.md`: This report.

### Config Files

- `config/app.php`: App name, environment, providers, timezone, locale, key.
- `config/auth.php`: Auth guards, providers, password reset settings.
- `config/cache.php`: Cache stores.
- `config/database.php`: Database connections.
- `config/filesystems.php`: Filesystem disks.
- `config/logging.php`: Logging channels.
- `config/mail.php`: Mail config.
- `config/queue.php`: Queue connections.
- `config/services.php`: Third-party services.
- `config/session.php`: Session storage config.

### Bootstrap

- `bootstrap/app.php`: Laravel application configuration.
- `bootstrap/providers.php`: Provider loading.
- `bootstrap/cache/.gitignore`: Keeps cache directory in Git.

### Public

- `public/index.php`: Web entry point.
- `public/favicon.ico`: Favicon.
- `public/robots.txt`: Search crawler rules.
- `public/build/*`: Built Vite assets.

### Storage

Storage directories contain runtime files and `.gitignore` placeholders:

- `storage/app`
- `storage/app/private`
- `storage/app/public`
- `storage/framework/cache`
- `storage/framework/sessions`
- `storage/framework/testing`
- `storage/framework/views`
- `storage/logs`

## 19. Recommendations

1. Move dashboard data logic from `routes/web.php` into a `DashboardController`.
2. Add feature tests for dashboard, permissions, instructor exam builder, and TCExam CRUD.
3. Rename `database/seeders/d.php` to a meaningful name or remove it if unused.
4. Consider extracting shared question update logic to services to reduce repetition.
5. Add policies for `InstructorExam` and `InstructorExamQuestion` instead of repeating ownership checks in controllers.
6. Add audit logging for permission changes and DB CRUD actions.
7. Add stronger validation around generic TCExam CRUD for risky tables.
8. Add indexes or query optimization if dashboard data grows large.
9. Add documentation for permission naming conventions.
10. Consider turning result snapshot syncing into a queue job or command.

