<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tce_sessions')) {
            Schema::create('tce_sessions', function (Blueprint $table) {
                $table->string('cpsession_id', 32)->primary();
                $table->dateTime('cpsession_expiry');
                $table->text('cpsession_data');
            });
        }

        if (! Schema::hasTable('tce_users')) {
            Schema::create('tce_users', function (Blueprint $table) {
                $table->id('user_id');
                $table->string('user_name')->unique();
                $table->string('user_password');
                $table->string('user_email')->nullable();
                $table->dateTime('user_regdate');
                $table->string('user_ip', 39);
                $table->string('user_firstname')->nullable();
                $table->string('user_lastname')->nullable();
                $table->date('user_birthdate')->nullable();
                $table->string('user_birthplace')->nullable();
                $table->string('user_regnumber')->nullable()->unique();
                $table->string('user_ssn')->nullable()->unique();
                $table->unsignedSmallInteger('user_level')->default(1);
                $table->string('user_verifycode', 32)->nullable()->unique();
                $table->string('user_otpkey')->nullable();
            });
        }

        if (! Schema::hasTable('tce_modules')) {
            Schema::create('tce_modules', function (Blueprint $table) {
                $table->id('module_id');
                $table->string('module_name')->unique();
                $table->boolean('module_enabled')->default(false);
                $table->unsignedBigInteger('module_user_id')->default(1)->index();
            });
        }

        if (! Schema::hasTable('tce_subjects')) {
            Schema::create('tce_subjects', function (Blueprint $table) {
                $table->id('subject_id');
                $table->unsignedBigInteger('subject_module_id')->default(1)->index();
                $table->string('subject_name');
                $table->text('subject_description')->nullable();
                $table->boolean('subject_enabled')->default(false);
                $table->unsignedBigInteger('subject_user_id')->default(1)->index();
                $table->unique(['subject_module_id', 'subject_name'], 'ak_subject_name');
            });
        }

        if (! Schema::hasTable('tce_questions')) {
            Schema::create('tce_questions', function (Blueprint $table) {
                $table->id('question_id');
                $table->unsignedBigInteger('question_subject_id')->index();
                $table->text('question_description');
                $table->text('question_explanation')->nullable();
                $table->unsignedSmallInteger('question_type')->default(1);
                $table->smallInteger('question_difficulty')->default(1);
                $table->boolean('question_enabled')->default(false);
                $table->unsignedBigInteger('question_position')->nullable();
                $table->unsignedSmallInteger('question_timer')->nullable();
                $table->boolean('question_fullscreen')->default(false);
                $table->boolean('question_inline_answers')->default(false);
                $table->boolean('question_auto_next')->default(false);
            });
        }

        if (! Schema::hasTable('tce_answers')) {
            Schema::create('tce_answers', function (Blueprint $table) {
                $table->id('answer_id');
                $table->unsignedBigInteger('answer_question_id')->index();
                $table->text('answer_description');
                $table->text('answer_explanation')->nullable();
                $table->boolean('answer_isright')->default(false);
                $table->boolean('answer_enabled')->default(false);
                $table->unsignedBigInteger('answer_position')->nullable();
                $table->unsignedSmallInteger('answer_keyboard_key')->nullable();
            });
        }

        if (! Schema::hasTable('tce_tests')) {
            Schema::create('tce_tests', function (Blueprint $table) {
                $table->id('test_id');
                $table->string('test_name')->unique();
                $table->text('test_description');
                $table->dateTime('test_begin_time')->nullable();
                $table->dateTime('test_end_time')->nullable();
                $table->unsignedSmallInteger('test_duration_time')->default(0);
                $table->string('test_ip_range')->default('*.*.*.*');
                $table->boolean('test_results_to_users')->default(false);
                $table->boolean('test_report_to_users')->default(false);
                $table->decimal('test_score_right', 10, 3)->default(1);
                $table->decimal('test_score_wrong', 10, 3)->default(0);
                $table->decimal('test_score_unanswered', 10, 3)->default(0);
                $table->decimal('test_max_score', 10, 3)->default(0);
                $table->unsignedBigInteger('test_user_id')->default(1)->index();
                $table->decimal('test_score_threshold', 10, 3)->default(0);
                $table->boolean('test_random_questions_select')->default(true);
                $table->boolean('test_random_questions_order')->default(true);
                $table->unsignedSmallInteger('test_questions_order_mode')->default(0);
                $table->boolean('test_random_answers_select')->default(true);
                $table->boolean('test_random_answers_order')->default(true);
                $table->unsignedSmallInteger('test_answers_order_mode')->default(0);
                $table->boolean('test_comment_enabled')->default(true);
                $table->boolean('test_menu_enabled')->default(true);
                $table->boolean('test_noanswer_enabled')->default(true);
                $table->boolean('test_mcma_radio')->default(true);
                $table->tinyInteger('test_repeatable')->default(0);
                $table->boolean('test_mcma_partial_score')->default(true);
                $table->boolean('test_logout_on_timeout')->default(false);
                $table->string('test_password')->nullable();
            });
        }

        if (! Schema::hasTable('tce_test_subject_set')) {
            Schema::create('tce_test_subject_set', function (Blueprint $table) {
                $table->id('tsubset_id');
                $table->unsignedBigInteger('tsubset_test_id')->index();
                $table->smallInteger('tsubset_type')->default(1);
                $table->smallInteger('tsubset_difficulty')->default(1);
                $table->smallInteger('tsubset_quantity')->default(1);
                $table->smallInteger('tsubset_answers')->default(0);
            });
        }

        if (! Schema::hasTable('tce_test_subjects')) {
            Schema::create('tce_test_subjects', function (Blueprint $table) {
                $table->unsignedBigInteger('subjset_tsubset_id')->index();
                $table->unsignedBigInteger('subjset_subject_id')->index();
                $table->primary(['subjset_tsubset_id', 'subjset_subject_id'], 'tce_test_subjects_primary');
            });
        }

        if (! Schema::hasTable('tce_tests_users')) {
            Schema::create('tce_tests_users', function (Blueprint $table) {
                $table->id('testuser_id');
                $table->unsignedBigInteger('testuser_test_id')->index();
                $table->unsignedBigInteger('testuser_user_id')->index();
                $table->unsignedSmallInteger('testuser_status')->default(0);
                $table->dateTime('testuser_creation_time');
                $table->text('testuser_comment')->nullable();
                $table->unique(['testuser_test_id', 'testuser_user_id', 'testuser_status'], 'ak_testuser');
            });
        }

        if (! Schema::hasTable('tce_tests_logs')) {
            Schema::create('tce_tests_logs', function (Blueprint $table) {
                $table->id('testlog_id');
                $table->unsignedBigInteger('testlog_testuser_id')->index();
                $table->string('testlog_user_ip', 39)->nullable();
                $table->unsignedBigInteger('testlog_question_id')->index();
                $table->text('testlog_answer_text')->nullable();
                $table->decimal('testlog_score', 10, 3)->nullable();
                $table->dateTime('testlog_creation_time')->nullable();
                $table->dateTime('testlog_display_time')->nullable();
                $table->dateTime('testlog_change_time')->nullable();
                $table->unsignedBigInteger('testlog_reaction_time')->default(0);
                $table->smallInteger('testlog_order')->default(1);
                $table->unsignedSmallInteger('testlog_num_answers')->default(0);
                $table->text('testlog_comment')->nullable();
                $table->unique(['testlog_testuser_id', 'testlog_question_id'], 'ak_testuser_question');
            });
        }

        if (! Schema::hasTable('tce_tests_logs_answers')) {
            Schema::create('tce_tests_logs_answers', function (Blueprint $table) {
                $table->unsignedBigInteger('logansw_testlog_id')->index();
                $table->unsignedBigInteger('logansw_answer_id')->index();
                $table->smallInteger('logansw_selected')->default(-1);
                $table->smallInteger('logansw_order')->default(1);
                $table->unsignedBigInteger('logansw_position')->nullable();
                $table->primary(['logansw_testlog_id', 'logansw_answer_id'], 'tce_tests_logs_answers_primary');
            });
        }

        if (! Schema::hasTable('tce_user_groups')) {
            Schema::create('tce_user_groups', function (Blueprint $table) {
                $table->id('group_id');
                $table->string('group_name')->unique();
            });
        }

        if (! Schema::hasTable('tce_usrgroups')) {
            Schema::create('tce_usrgroups', function (Blueprint $table) {
                $table->unsignedBigInteger('usrgrp_user_id')->index();
                $table->unsignedBigInteger('usrgrp_group_id')->index();
                $table->primary(['usrgrp_user_id', 'usrgrp_group_id'], 'tce_usrgroups_primary');
            });
        }

        if (! Schema::hasTable('tce_testgroups')) {
            Schema::create('tce_testgroups', function (Blueprint $table) {
                $table->unsignedBigInteger('tstgrp_test_id')->index();
                $table->unsignedBigInteger('tstgrp_group_id')->index();
                $table->primary(['tstgrp_test_id', 'tstgrp_group_id'], 'tce_testgroups_primary');
            });
        }

        if (! Schema::hasTable('tce_sslcerts')) {
            Schema::create('tce_sslcerts', function (Blueprint $table) {
                $table->id('ssl_id');
                $table->string('ssl_name');
                $table->string('ssl_hash', 32);
                $table->dateTime('ssl_end_date');
                $table->boolean('ssl_enabled')->default(false);
                $table->unsignedBigInteger('ssl_user_id')->default(1);
            });
        }

        if (! Schema::hasTable('tce_testsslcerts')) {
            Schema::create('tce_testsslcerts', function (Blueprint $table) {
                $table->unsignedBigInteger('tstssl_test_id')->index();
                $table->unsignedBigInteger('tstssl_ssl_id')->index();
                $table->primary(['tstssl_test_id', 'tstssl_ssl_id'], 'tce_testsslcerts_primary');
            });
        }

        if (! Schema::hasTable('tce_testuser_stat')) {
            Schema::create('tce_testuser_stat', function (Blueprint $table) {
                $table->id('tus_id');
                $table->dateTime('tus_date');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tce_testuser_stat');
        Schema::dropIfExists('tce_testsslcerts');
        Schema::dropIfExists('tce_sslcerts');
        Schema::dropIfExists('tce_testgroups');
        Schema::dropIfExists('tce_usrgroups');
        Schema::dropIfExists('tce_user_groups');
        Schema::dropIfExists('tce_tests_logs_answers');
        Schema::dropIfExists('tce_tests_logs');
        Schema::dropIfExists('tce_tests_users');
        Schema::dropIfExists('tce_test_subjects');
        Schema::dropIfExists('tce_test_subject_set');
        Schema::dropIfExists('tce_tests');
        Schema::dropIfExists('tce_answers');
        Schema::dropIfExists('tce_questions');
        Schema::dropIfExists('tce_subjects');
        Schema::dropIfExists('tce_modules');
        Schema::dropIfExists('tce_users');
        Schema::dropIfExists('tce_sessions');
    }
};
