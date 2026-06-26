<?php

namespace App\Support;

use Illuminate\Support\Str;

class FriendlyName
{
    public static function screen(string $routeName): array
    {
        $map = [
            'dashboard' => ['Dashboard', 'View the main system overview and recent activity.'],
            'profile.edit' => ['My Profile', 'Manage personal account information.'],
            'users.index' => ['User Accounts', 'View and manage system users.'],
            'users.create' => ['Add User Account', 'Create a new user account.'],
            'academics.index' => ['Academic Workspace', 'Configure programs, courses, students, exam assignments, and academic uploads.'],
            'academics.upload.index' => ['Academic Excel Upload', 'Upload academic setup data as a secondary bulk option.'],
            'academics.upload.template' => ['Download Academic Template', 'Download the spreadsheet used to prepare academic data.'],
            'academics.upload.preview' => ['Preview Academic Upload', 'Review uploaded academic data before saving it.'],
            'admin.access.index' => ['Access Management', 'Control role access to pages, actions, and system data.'],
            'admin.super-users.index' => ['System Administrators', 'Manage the people with full system access.'],
            'admin.settings.ai-configuration.edit' => ['AI Assistance Settings', 'Configure optional AI support used by the system.'],
            'groups.index' => ['User Roles', 'Manage role-based access.'],
            'data.tables.index' => ['System Data', 'Browse friendly data sections used by the system.'],
            'data.table.index' => ['Manage System Data', 'View information in a selected data section.'],
            'data.table.create' => ['Add New System Data', 'Add information to a selected data section.'],
            'data.table.edit' => ['Edit System Data', 'Save changes in a selected data section.'],
            'exam.wizard.step1' => ['Exam Setup Wizard', 'Start preparing exam information.'],
            'exam.wizard.step2' => ['Exam Questions Wizard', 'Add questions while preparing an exam.'],
            'exam.wizard.step3' => ['Exam Review Wizard', 'Review exam settings before saving.'],
            'instructor.exams.create' => ['Prepare Exam', 'Create exam details and settings.'],
            'instructor.exams.edit' => ['Manage Exam Settings', 'Edit exam details, timing, marks, and course selection.'],
            'instructor.exams.question-types.index' => ['Add Exam Questions', 'Choose question types and build exam content.'],
            'instructor.exams.preview.show' => ['Preview Exam', 'Review how the exam will appear to students.'],
            'instructor.exams.publish.show' => ['Publish Exam', 'Check readiness and make the exam available.'],
            'instructor.exams.questions.order.index' => ['Arrange Exam Questions', 'Set the order of questions and remove unused items.'],
            'instructor.exams.questions.mcq.edit' => ['Edit Multiple Choice Question', 'Modify multiple choice question text, choices, and marks.'],
            'instructor.exams.questions.true-false.edit' => ['Edit True or False Question', 'Modify true or false question details.'],
            'instructor.exams.questions.matching.edit' => ['Edit Matching Question', 'Modify matching pairs and marks.'],
            'instructor.exams.questions.fill-blank.edit' => ['Edit Fill in the Blank Question', 'Modify blank answers and grading options.'],
            'instructor.exams.questions.essay.edit' => ['Edit Essay Question', 'Modify essay prompt and grading guidance.'],
            'instructor.exams.questions.coding.edit' => ['Edit Coding Question', 'Modify programming task details and marks.'],
            'instructor.exams.questions.packet-tracer.edit' => ['Edit Practical Network Question', 'Modify practical network task details.'],
            'instructor.grading.index' => ['Review Student Answers', 'Open submitted attempts that need review.'],
            'instructor.grading.sessions.show' => ['Grade Student Attempt', 'Review answers and save grading decisions.'],
            'student.exams.index' => ['My Exams', 'View assigned exams available to the student.'],
            'student.exams.sessions.show' => ['Take Exam', 'Answer questions in an active exam attempt.'],
        ];

        [$label, $description] = $map[$routeName] ?? [self::headline($routeName), 'Manage this part of the system.'];

        return compact('label', 'description');
    }

    public static function button(string $key): string
    {
        $action = Str::afterLast($key, '.');

        $map = [
            'create_group' => 'Add user role',
            'edit_group' => 'Edit user role',
            'delete_group' => 'Remove user role',
            'assign_user_to_group' => 'Assign people to role',
            'save_screens' => 'Save page access',
            'save_buttons' => 'Save action access',
            'save_db_access' => 'Save data access',
            'save_group_users' => 'Save role members',
            'update_profile' => 'Save profile changes',
            'update_password' => 'Change password',
            'delete_account' => 'Remove account',
            'create_record' => 'Add new information',
            'update_record' => 'Save changes',
            'delete_record' => 'Remove from system',
            'create_user' => 'Add user account',
            'change_status' => 'Enable or disable user account',
            'save_draft' => 'Save exam draft',
            'duplicate' => 'Duplicate question',
            'publish' => 'Publish exam',
            'unpublish' => 'Return exam to draft',
            'select_type' => 'Choose question type',
            'save' => 'Save changes',
            'ai_assist' => 'Use AI assistance',
            'create_exam' => 'Create exam',
            'next' => 'Continue',
            'test' => 'Test settings',
        ];

        return $map[$action] ?? self::headline($action);
    }

    public static function dataSection(string $table): array
    {
        $map = [
            'courses' => ['Course Data', 'Course Information', 'Manage courses used in the academic system.'],
            'majors' => ['Program Data', 'Program Information', 'Manage academic programs and departments.'],
            'course_major' => ['Academic Data', 'Program Course Planning', 'Connect courses to programs and recommended study levels.'],
            'course_student' => ['Student Data', 'Course Enrollment', 'Manage student enrollment in courses.'],
            'student_profiles' => ['Student Data', 'Student Academic Profiles', 'Manage student numbers, programs, and study status.'],
            'instructor_exams' => ['Exam Data', 'Exam Management', 'Manage exam settings, timing, marks, and course links.'],
            'instructor_exam_questions' => ['Exam Data', 'Exam Questions', 'Manage questions used in prepared exams.'],
            'exam_assignments' => ['Exam Data', 'Exam Availability', 'Manage which students or courses can take each exam.'],
            'exam_sessions' => ['Exam Data', 'Student Exam Attempts', 'Monitor exam attempts and completion status.'],
            'exam_session_answers' => ['Exam Data', 'Student Answers', 'Review answers saved during exam attempts.'],
            'exam_activity_logs' => ['Exam Data', 'Exam Activity History', 'Review important activity recorded during exam delivery.'],
            'users' => ['User and Access Data', 'User Accounts', 'Manage people who can use the system.'],
            'groups' => ['User and Access Data', 'User Roles', 'Manage role-based access.'],
            'roles' => ['User and Access Data', 'Access Roles', 'Manage access roles behind each user role.'],
            'permissions' => ['User and Access Data', 'Access Permissions', 'Manage permission entries used by the system.'],
            'group_user' => ['User and Access Data', 'Role Members', 'Connect people to user roles.'],
            'group_role' => ['User and Access Data', 'Role Access Links', 'Connect user roles to access rules.'],
            'permission_role' => ['User and Access Data', 'Role Permission Rules', 'Connect access roles to allowed work.'],
            'ai_configurations' => ['System Settings', 'AI Assistance Settings', 'Manage optional AI support configuration.'],
            'tce_modules' => ['Academic Data', 'Learning Modules', 'Manage learning modules used by imported exam data.'],
            'tce_subjects' => ['Course Data', 'Subject Information', 'Manage subjects and topics used by imported exams.'],
            'tce_questions' => ['Exam Data', 'Question Bank', 'Manage imported exam questions.'],
            'tce_answers' => ['Exam Data', 'Answer Choices', 'Manage possible answers for imported questions.'],
            'tce_tests' => ['Exam Data', 'Imported Exam Settings', 'Manage imported exam settings and delivery rules.'],
            'tce_test_subjects' => ['Exam Data', 'Exam Subject Links', 'Connect imported exams to subject areas.'],
            'tce_test_subject_set' => ['Exam Data', 'Exam Question Selection', 'Manage how imported exams select questions.'],
            'tce_testgroups' => ['Exam Data', 'Exam Audience Access', 'Manage who can access imported exams.'],
            'tce_tests_users' => ['Exam Data', 'Exam Student Access', 'Manage which students can access imported exams.'],
            'tce_tests_logs' => ['Exam Data', 'Imported Exam Attempts', 'Review imported exam attempt records.'],
            'tce_tests_logs_answers' => ['Exam Data', 'Imported Student Answers', 'Review imported answer records.'],
            'tce_testuser_stat' => ['Exam Data', 'Imported Result Summary', 'Review imported exam results.'],
            'tce_users' => ['Student Data', 'Imported Student Accounts', 'Manage imported student account records.'],
            'tce_user_groups' => ['Student Data', 'Imported Student Role Links', 'Manage imported student role links.'],
            'tce_usrgroups' => ['Student Data', 'Imported Student Roles', 'Manage imported student roles.'],
            'tce_sessions' => ['System Settings', 'Imported Session Settings', 'Manage imported session records.'],
            'tce_sslcerts' => ['System Settings', 'Security Certificates', 'Manage certificate information used by imported exams.'],
            'tce_testsslcerts' => ['System Settings', 'Exam Security Certificates', 'Connect certificates to imported exams.'],
            'tcexam_test_links' => ['Exam Data', 'Exam Sync Links', 'Manage connections between prepared exams and imported exams.'],
            'tcexam_result_snapshots' => ['Exam Data', 'Exam Result Snapshots', 'Review saved exam result summaries.'],
        ];

        [$group, $label, $description] = $map[$table] ?? ['System Data', self::headline($table), 'Manage information used by the system.'];

        return compact('group', 'label', 'description');
    }

    public static function dataAction(string $action): string
    {
        return match ($action) {
            'select' => 'View information',
            'insert' => 'Add new information',
            'update' => 'Modify existing information',
            'delete' => 'Remove information',
            'export' => 'Export data',
            'upload' => 'Upload data',
            default => self::headline($action),
        };
    }

    public static function column(string $column): string
    {
        $map = [
            'id' => 'Reference number',
            'created_at' => 'Created date',
            'updated_at' => 'Last updated',
            'deleted_at' => 'Removed date',
            'module_name' => 'Learning module',
            'module_enabled' => 'Module active',
            'module_user_id' => 'Module owner',
            'subject_name' => 'Subject',
            'subject_description' => 'Subject description',
            'question_description' => 'Question text',
            'question_explanation' => 'Question explanation',
            'answer_description' => 'Answer text',
            'answer_isright' => 'Correct answer',
            'test_name' => 'Exam name',
            'test_description' => 'Exam description',
            'test_begin_time' => 'Start date',
            'test_end_time' => 'End date',
            'test_duration_time' => 'Duration in minutes',
            'test_score_threshold' => 'Pass threshold',
            'user_name' => 'Username',
            'user_email' => 'Email address',
            'group_name' => 'Role name',
        ];

        if (isset($map[$column])) {
            return $map[$column];
        }

        $name = preg_replace('/(^tce_|^tcexam_)/', '', $column) ?: $column;
        $name = str_replace(['_id', '_ids'], [' reference', ' references'], $name);

        return self::headline($name);
    }

    private static function headline(string $value): string
    {
        $value = preg_replace('/(^tce_|^tcexam_)/', '', $value) ?: $value;
        $value = str_replace(['.', '_', '-'], ' ', $value);
        $value = preg_replace('/\b(db|crud|dml)\b/i', 'data', $value) ?: $value;

        return Str::of($value)->squish()->title()->toString();
    }
}
