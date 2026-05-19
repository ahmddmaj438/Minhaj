<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\Course;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $rootSuper = User::firstOrCreate(
            ['email' => User::ROOT_SUPER_ADMIN_EMAIL],
            [
                'name' => 'Root Super Admin',
                'password' => bcrypt('password'),
            ]
        );

        $user = User::firstOrCreate(
            ['email' => 'test@gmail.com'],
            [
                'name' => 'Test Admin',
                'password' => bcrypt('password'),
            ]
        );

        $adminGroup = Group::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);
        $memberGroup = Group::firstOrCreate(['slug' => 'member'], ['name' => 'Member']);

        $courses = [
            ['code' => 'DBS301', 'name' => 'Database Systems'],
            ['code' => 'NET302', 'name' => 'Computer Networks'],
            ['code' => 'WEB303', 'name' => 'Web Application Development'],
        ];

        foreach ($courses as $course) {
            Course::firstOrCreate(
                ['code' => $course['code']],
                ['name' => $course['name'], 'is_active' => true]
            );
        }

        $permissionNames = [
            'screen.dashboard.view',
            'screen.profile.edit.view',
            'screen.admin.access.index.view',
            'screen.groups.index.view',
            'screen.users.index.view',
            'screen.users.create.view',
            'screen.data.tables.index.view',
            'screen.data.table.index.view',
            'screen.data.table.create.view',
            'screen.data.table.edit.view',
            'screen.exam.wizard.step1.view',
            'screen.exam.wizard.step2.view',
            'screen.exam.wizard.step3.view',
            'screen.instructor.exams.create.view',
            'screen.instructor.exams.question-types.index.view',
            'screen.instructor.exams.preview.show.view',
            'screen.instructor.exams.questions.order.index.view',
            'screen.instructor.exams.questions.mcq.edit.view',
            'screen.instructor.exams.questions.true-false.edit.view',
            'screen.instructor.exams.questions.matching.edit.view',
            'screen.instructor.exams.questions.fill-blank.edit.view',
            'screen.instructor.exams.questions.essay.edit.view',
            'screen.instructor.exams.questions.coding.edit.view',
            'screen.instructor.exams.questions.packet-tracer.edit.view',
            'button.dashboard.group_management',
            'button.users.create.create_user',
            'button.data.table.create.create_record',
            'button.data.table.edit.update_record',
            'button.data.table.index.delete_record',
            'button.exam.wizard.step1.next',
            'button.exam.wizard.step2.next',
            'button.exam.wizard.finish.create_exam',
            'button.instructor.exams.store.save_draft',
            'button.instructor.exams.questions.select_type',
            'button.instructor.exams.questions.order.save',
            'button.instructor.exams.questions.order.delete',
            'button.instructor.exams.questions.mcq.save',
            'button.instructor.exams.questions.true_false.save',
            'button.instructor.exams.questions.matching.save',
            'button.instructor.exams.questions.fill_blank.save',
            'button.instructor.exams.questions.essay.save',
            'button.instructor.exams.questions.coding.save',
            'button.instructor.exams.questions.packet_tracer.save',
            'button.groups.index.create_group',
            'button.groups.index.edit_group',
            'button.groups.index.delete_group',
            'button.groups.index.assign_user_to_group',
            'db.users.update',
            'db.users.delete',
            'db.users.insert',
            'db.groups.insert',
            'db.roles.update',
            'db.group_user.update',
            'db.tce_answers.insert',
            'db.tce_answers.update',
            'db.tce_answers.delete',
            'db.tce_modules.insert',
            'db.tce_modules.update',
            'db.tce_modules.delete',
            'db.tce_questions.insert',
            'db.tce_questions.update',
            'db.tce_questions.delete',
            'db.tce_sessions.insert',
            'db.tce_sessions.update',
            'db.tce_sessions.delete',
            'db.tce_sslcerts.insert',
            'db.tce_sslcerts.update',
            'db.tce_sslcerts.delete',
            'db.tce_subjects.insert',
            'db.tce_subjects.update',
            'db.tce_subjects.delete',
            'db.tce_test_subject_set.insert',
            'db.tce_test_subject_set.update',
            'db.tce_test_subject_set.delete',
            'db.tce_test_subjects.insert',
            'db.tce_test_subjects.update',
            'db.tce_test_subjects.delete',
            'db.tce_testgroups.insert',
            'db.tce_testgroups.update',
            'db.tce_testgroups.delete',
            'db.tce_tests.insert',
            'db.tce_tests.update',
            'db.tce_tests.delete',
            'db.tce_tests_logs.insert',
            'db.tce_tests_logs.update',
            'db.tce_tests_logs.delete',
            'db.tce_tests_logs_answers.insert',
            'db.tce_tests_logs_answers.update',
            'db.tce_tests_logs_answers.delete',
            'db.tce_tests_users.insert',
            'db.tce_tests_users.update',
            'db.tce_tests_users.delete',
            'db.tce_testsslcerts.insert',
            'db.tce_testsslcerts.update',
            'db.tce_testsslcerts.delete',
            'db.tce_testuser_stat.insert',
            'db.tce_testuser_stat.update',
            'db.tce_testuser_stat.delete',
            'db.tce_user_groups.insert',
            'db.tce_user_groups.update',
            'db.tce_user_groups.delete',
            'db.tce_users.insert',
            'db.tce_users.update',
            'db.tce_users.delete',
            'db.tce_usrgroups.insert',
            'db.tce_usrgroups.update',
            'db.tce_usrgroups.delete',
            'db.tcexam_test_links.insert',
            'db.tcexam_test_links.update',
            'db.tcexam_test_links.delete',
            'db.tcexam_result_snapshots.insert',
            'db.tcexam_result_snapshots.update',
            'db.tcexam_result_snapshots.delete',
            'db.courses.insert',
            'db.courses.update',
            'db.courses.delete',
            'db.instructor_exams.insert',
            'db.instructor_exams.update',
            'db.instructor_exams.delete',
            'db.instructor_exam_questions.insert',
            'db.instructor_exam_questions.update',
            'db.instructor_exam_questions.delete',
        ];

        $permissionIds = collect($permissionNames)->map(function (string $name) {
            return Permission::firstOrCreate(['name' => $name])->id;
        })->all();

        $adminRole = Role::firstOrCreate(['slug' => 'admin_role'], ['name' => 'Admin Role']);
        $memberRole = Role::firstOrCreate(['slug' => 'member_role'], ['name' => 'Member Role']);
        $superRole = Role::firstOrCreate(['slug' => 'super_admin'], ['name' => 'Super Admin']);

        $adminRole->permissions()->sync($permissionIds);
        $memberRole->permissions()->sync(
            Permission::whereIn('name', [
                'screen.profile.edit.view',
                'db.users.update',
            ])->pluck('id')->all()
        );

        $adminGroup->roles()->syncWithoutDetaching([$adminRole->id]);
        $memberGroup->roles()->syncWithoutDetaching([$memberRole->id]);
        $user->groups()->syncWithoutDetaching([$adminGroup->id]);
        $rootSuper->roles()->syncWithoutDetaching([$superRole->id]);
    }
}
