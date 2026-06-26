<?php

use App\Http\Controllers\AdminAccessController;
use App\Http\Controllers\Admin\AiConfigurationController;
use App\Http\Controllers\AcademicManagementController;
use App\Http\Controllers\ExamWizardController;
use App\Http\Controllers\Instructor\CodingQuestionController;
use App\Http\Controllers\Instructor\ExamPreviewController;
use App\Http\Controllers\Instructor\ExamPublishingController;
use App\Http\Controllers\Instructor\ExamSetupController;
use App\Http\Controllers\Instructor\EssayQuestionController;
use App\Http\Controllers\Instructor\FillBlankQuestionController;
use App\Http\Controllers\Instructor\MatchingQuestionController;
use App\Http\Controllers\Instructor\ManualGradingController;
use App\Http\Controllers\Instructor\McqQuestionController;
use App\Http\Controllers\Instructor\PacketTracerQuestionController;
use App\Http\Controllers\Instructor\QuestionOrderingController;
use App\Http\Controllers\Instructor\QuestionTypeController;
use App\Http\Controllers\Instructor\TrueFalseQuestionController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\Student\StudentExamController;
use App\Http\Controllers\SuperUserController;
use App\Http\Controllers\TCExamCrudController;
use App\Http\Controllers\UserManagementController;
use App\Models\Course;
use App\Models\Exam\InstructorExam;
use App\Models\Exam\InstructorExamQuestion;
use App\Models\ExamSession;
use App\Models\Group;
use App\Models\TCExamResultSnapshot;
use App\Models\TCExamTest;
use App\Models\User;
use App\Services\Access\LearningAccess;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/language', [LanguageController::class, 'update'])->name('language.switch');
Route::get('/lang/{locale}', [LanguageController::class, 'switch'])->name('language.switch.get');

Route::get('/dashboard', function () {
    $user = request()->user();
    $access = app(LearningAccess::class);
    $canManageAllLearning = $user?->isSuperAdmin() || $user?->hasPermission('screen.admin.access.index.view');
    $visibleExamIds = Schema::hasTable('instructor_exams') ? $access->examQuery($user)->pluck('id') : collect();
    $visibleCourseIds = Schema::hasTable('courses') ? $access->courseQuery($user)->pluck('id') : collect();
    $now = CarbonImmutable::now();
    $lastSevenDays = collect(range(6, 0))->map(fn (int $daysAgo) => $now->subDays($daysAgo));

    $countTable = fn (string $table): int => Schema::hasTable($table) ? DB::table($table)->count() : 0;

    $localExamCount = $visibleExamIds->count();
    $tcexamTestCount = $canManageAllLearning && Schema::hasTable('tce_tests') ? TCExamTest::count() : 0;
    $questionCount = (Schema::hasTable('instructor_exam_questions')
        ? InstructorExamQuestion::whereIn('instructor_exam_id', $visibleExamIds)->count()
        : 0)
        + ($canManageAllLearning ? $countTable('tce_questions') : 0);
    $answerCount = $canManageAllLearning ? $countTable('tce_answers') : 0;
    $resultCount = Schema::hasTable('exam_sessions')
        ? ExamSession::whereHas('assignment', fn ($query) => $query->whereIn('instructor_exam_id', $visibleExamIds))->whereNotNull('submitted_at')->count()
        : ($canManageAllLearning && Schema::hasTable('tcexam_result_snapshots') ? TCExamResultSnapshot::count() : 0);
    $passedCount = Schema::hasTable('exam_sessions')
        ? ExamSession::whereHas('assignment', fn ($query) => $query->whereIn('instructor_exam_id', $visibleExamIds))->where('passed', true)->count()
        : 0;
    $passRate = $resultCount > 0 ? round(($passedCount / $resultCount) * 100) : 0;
    $publishedExamCount = Schema::hasTable('instructor_exams')
        ? InstructorExam::whereIn('id', $visibleExamIds)->where('status', InstructorExam::STATUS_PUBLISHED)->count()
        : 0;

    $examStatus = collect([
        'Published' => $publishedExamCount,
        'Draft' => max($localExamCount - $publishedExamCount, 0),
        'Imported exams' => $tcexamTestCount,
    ]);

    $questionTypes = Schema::hasTable('instructor_exam_questions')
        ? InstructorExamQuestion::query()
            ->select('type', DB::raw('count(*) as total'))
            ->whereIn('instructor_exam_id', $visibleExamIds)
            ->groupBy('type')
            ->orderByDesc('total')
            ->limit(5)
            ->pluck('total', 'type')
        : collect();

    if ($canManageAllLearning && $questionTypes->isEmpty() && Schema::hasTable('tce_questions')) {
        $questionTypes = DB::table('tce_questions')
            ->select('question_type', DB::raw('count(*) as total'))
            ->groupBy('question_type')
            ->orderByDesc('total')
            ->limit(5)
            ->pluck('total', 'question_type')
            ->mapWithKeys(fn ($total, $type) => ['TCExam type '.$type => $total]);
    }

    $completionTrend = $lastSevenDays->map(function (CarbonImmutable $day) use ($canManageAllLearning, $visibleExamIds) {
        $completed = Schema::hasTable('tcexam_result_snapshots')
            ? ($canManageAllLearning
                ? TCExamResultSnapshot::whereDate('completed_at', $day->toDateString())->count()
                : ExamSession::whereHas('assignment', fn ($query) => $query->whereIn('instructor_exam_id', $visibleExamIds))
                    ->whereDate('submitted_at', $day->toDateString())
                    ->count())
            : 0;

        return [
            'label' => $day->translatedFormat('D'),
            'date' => $day->translatedFormat('M j'),
            'completed' => $completed,
        ];
    });

    $maxTrend = max($completionTrend->max('completed'), 1);

    $recentExams = Schema::hasTable('instructor_exams')
        ? $access->examQuery($user)->with('course')
            ->withCount('questions')
            ->latest()
            ->limit(5)
            ->get(['id', 'course_id', 'instructor_id', 'title', 'status', 'total_marks', 'created_at', 'updated_at'])
        : collect();

    $nextDraftExam = Schema::hasTable('instructor_exams')
        ? $access->examQuery($user)->with('course')
            ->withCount('questions')
            ->where('status', InstructorExam::STATUS_DRAFT)
            ->latest('updated_at')
            ->first(['id', 'course_id', 'instructor_id', 'title', 'status', 'duration_minutes', 'total_marks', 'updated_at'])
        : null;

    return view('dashboard', [
        'stats' => [
            [
                'label' => __('Total Users'),
            'value' => $canManageAllLearning ? User::count() : $visibleCourseIds->count(),
                'detail' => $canManageAllLearning ? __('Registered platform accounts') : __('Assigned courses available to you'),
                'tone' => 'blue',
            ],
            [
                'label' => __('Exams'),
                'value' => $localExamCount + $tcexamTestCount,
                'detail' => __(':published published, :imported imported exams', ['published' => $publishedExamCount, 'imported' => $tcexamTestCount]),
                'tone' => 'orange',
            ],
            [
                'label' => __('Questions'),
                'value' => $questionCount,
                'detail' => __(':answers answer choices stored', ['answers' => $answerCount]),
                'tone' => 'emerald',
            ],
            [
                'label' => __('Results'),
                'value' => $resultCount,
                'detail' => __(':rate% pass rate', ['rate' => $passRate]),
                'tone' => 'violet',
            ],
        ],
        'operations' => [
            'courses' => $visibleCourseIds->count(),
            'groups' => $canManageAllLearning && Schema::hasTable('groups') ? Group::count() : 0,
            'subjects' => $canManageAllLearning ? $countTable('tce_subjects') : 0,
            'modules' => $canManageAllLearning ? $countTable('tce_modules') : 0,
        ],
        'examStatus' => $examStatus,
        'questionTypes' => $questionTypes,
        'completionTrend' => $completionTrend,
        'maxTrend' => $maxTrend,
        'recentExams' => $recentExams,
        'nextDraftExam' => $nextDraftExam,
        'passRate' => $passRate,
    ]);
})->middleware(['auth', 'verified', 'screen'])->name('dashboard');

Route::middleware(['auth', 'screen'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UserManagementController::class, 'create'])->name('users.create');
    Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
    Route::patch('/users/{user}/status', [UserManagementController::class, 'updateStatus'])->name('users.status.update');
    Route::get('/academics', [AcademicManagementController::class, 'index'])->name('academics.index');
    Route::get('/academics/upload', [AcademicManagementController::class, 'upload'])->name('academics.upload.index');
    Route::get('/academics/upload/template', [AcademicManagementController::class, 'downloadTemplate'])->name('academics.upload.template');
    Route::post('/academics/upload/preview', [AcademicManagementController::class, 'previewUpload'])->name('academics.upload.preview');
    Route::post('/academics/upload/confirm', [AcademicManagementController::class, 'confirmUpload'])->name('academics.upload.confirm');
    Route::post('/academics/majors', [AcademicManagementController::class, 'storeMajor'])->name('academics.majors.store');
    Route::post('/academics/students', [AcademicManagementController::class, 'storeStudent'])->name('academics.students.store');
    Route::post('/academics/major-courses', [AcademicManagementController::class, 'assignCourseToMajor'])->name('academics.major-courses.store');
    Route::post('/academics/course-students', [AcademicManagementController::class, 'enrollStudent'])->name('academics.course-students.store');
    Route::post('/academics/course-teachers', [AcademicManagementController::class, 'assignTeacher'])->name('academics.course-teachers.store');
    Route::post('/academics/exam-assignments', [AcademicManagementController::class, 'storeExamAssignment'])->name('academics.exam-assignments.store');
    Route::patch('/academics/exam-sessions/{session}', [AcademicManagementController::class, 'updateSessionStatus'])->name('academics.exam-sessions.update');
    Route::get('/reports', [ReportsController::class, 'index'])->name('reports.index');
    Route::get('/reports/{report}', [ReportsController::class, 'show'])->name('reports.show');
});

Route::prefix('student/exams')->name('student.exams.')->middleware(['auth'])->group(function () {
    Route::get('/', [StudentExamController::class, 'index'])->name('index');
    Route::post('/assignments/{assignment}/start', [StudentExamController::class, 'start'])->name('start');
    Route::get('/sessions/{session}', [StudentExamController::class, 'show'])->name('sessions.show');
    Route::post('/sessions/{session}/answers', [StudentExamController::class, 'save'])->name('sessions.answers.save');
    Route::post('/sessions/{session}/submit', [StudentExamController::class, 'submit'])->name('sessions.submit');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'screen'])->group(function () {
    Route::get('/access', [AdminAccessController::class, 'index'])->name('access.index');
    Route::post('/groups', [AdminAccessController::class, 'storeGroup'])->name('groups.store');
    Route::delete('/groups/{group}', [AdminAccessController::class, 'destroyGroup'])->name('groups.destroy');
    Route::put('/groups/{group}/screens', [AdminAccessController::class, 'updateGroupScreens'])->name('groups.screens.update');
    Route::put('/groups/{group}/buttons', [AdminAccessController::class, 'updateGroupButtons'])->name('groups.buttons.update');
    Route::put('/groups/{group}/db-access', [AdminAccessController::class, 'updateGroupDbAccess'])->name('groups.db.update');
    Route::put('/groups/{group}/users', [AdminAccessController::class, 'updateGroupUsers'])->name('groups.users.update');
    Route::get('/super-users', [SuperUserController::class, 'index'])->name('super-users.index');
    Route::post('/super-users/grant', [SuperUserController::class, 'grant'])->name('super-users.grant');
    Route::delete('/super-users/{user}', [SuperUserController::class, 'revoke'])->name('super-users.revoke');
    Route::get('/settings/ai-configuration', [AiConfigurationController::class, 'edit'])->name('settings.ai-configuration.edit');
    Route::post('/settings/ai-configuration', [AiConfigurationController::class, 'update'])->name('settings.ai-configuration.update');
    Route::post('/settings/ai-configuration/test', [AiConfigurationController::class, 'test'])->name('settings.ai-configuration.test');
});

Route::prefix('admin/data')->middleware(['auth', 'screen'])->group(function () {
    Route::get('/tables', [TCExamCrudController::class, 'tables'])->name('data.tables.index');
    Route::get('/tables/{table}', [TCExamCrudController::class, 'index'])->name('data.table.index');
    Route::get('/tables/{table}/create', [TCExamCrudController::class, 'create'])->name('data.table.create');
    Route::post('/tables/{table}', [TCExamCrudController::class, 'store'])->name('data.table.store');
    Route::get('/tables/{table}/{id}/edit', [TCExamCrudController::class, 'edit'])->name('data.table.edit');
    Route::put('/tables/{table}/{id}', [TCExamCrudController::class, 'update'])->name('data.table.update');
    Route::delete('/tables/{table}/{id}', [TCExamCrudController::class, 'destroy'])->name('data.table.destroy');
});

Route::prefix('admin/exams/wizard')->middleware(['auth', 'screen'])->group(function () {
    Route::get('/step-1', [ExamWizardController::class, 'step1'])->name('exam.wizard.step1');
    Route::post('/step-1', [ExamWizardController::class, 'storeStep1'])->name('exam.wizard.step1.store');
    Route::get('/step-2', [ExamWizardController::class, 'step2'])->name('exam.wizard.step2');
    Route::post('/step-2', [ExamWizardController::class, 'storeStep2'])->name('exam.wizard.step2.store');
    Route::get('/step-3', [ExamWizardController::class, 'step3'])->name('exam.wizard.step3');
    Route::post('/finish', [ExamWizardController::class, 'finish'])->name('exam.wizard.finish');
});

Route::prefix('instructor/exams')->name('instructor.exams.')->middleware(['auth', 'screen'])->group(function () {
    Route::get('/create', [ExamSetupController::class, 'create'])->name('create');
    Route::post('/', [ExamSetupController::class, 'store'])->name('store');
    Route::get('/{exam}/edit', [ExamSetupController::class, 'edit'])->name('edit');
    Route::put('/{exam}', [ExamSetupController::class, 'update'])->name('update');
    Route::delete('/{exam}', [ExamSetupController::class, 'destroy'])->name('destroy');
    Route::get('/{exam}/question-types', [QuestionTypeController::class, 'index'])->name('question-types.index');
    Route::post('/{exam}/question-types', [QuestionTypeController::class, 'store'])->name('question-types.store');
    Route::post('/{exam}/questions/bank', [QuestionTypeController::class, 'storeFromBank'])->name('questions.bank.store');
    Route::get('/{exam}/preview', [ExamPreviewController::class, 'show'])->name('preview.show');
    Route::get('/{exam}/publish', [ExamPublishingController::class, 'show'])->name('publish.show');
    Route::post('/{exam}/publish', [ExamPublishingController::class, 'publish'])->name('publish.store');
    Route::patch('/{exam}/publish/draft', [ExamPublishingController::class, 'returnToDraft'])->name('publish.draft');
    Route::get('/{exam}/questions/order', [QuestionOrderingController::class, 'index'])->name('questions.order.index');
    Route::patch('/{exam}/questions/order', [QuestionOrderingController::class, 'update'])->name('questions.order.update');
    Route::post('/{exam}/questions/{question}/duplicate', [QuestionOrderingController::class, 'duplicate'])->name('questions.duplicate');
    Route::get('/{exam}/questions/{question}/mcq', [McqQuestionController::class, 'edit'])->name('questions.mcq.edit');
    Route::put('/{exam}/questions/{question}/mcq', [McqQuestionController::class, 'update'])->name('questions.mcq.update');
    Route::get('/{exam}/questions/{question}/true-false', [TrueFalseQuestionController::class, 'edit'])->name('questions.true-false.edit');
    Route::put('/{exam}/questions/{question}/true-false', [TrueFalseQuestionController::class, 'update'])->name('questions.true-false.update');
    Route::get('/{exam}/questions/{question}/matching', [MatchingQuestionController::class, 'edit'])->name('questions.matching.edit');
    Route::put('/{exam}/questions/{question}/matching', [MatchingQuestionController::class, 'update'])->name('questions.matching.update');
    Route::get('/{exam}/questions/{question}/fill-blank', [FillBlankQuestionController::class, 'edit'])->name('questions.fill-blank.edit');
    Route::put('/{exam}/questions/{question}/fill-blank', [FillBlankQuestionController::class, 'update'])->name('questions.fill-blank.update');
    Route::get('/{exam}/questions/{question}/essay', [EssayQuestionController::class, 'edit'])->name('questions.essay.edit');
    Route::post('/{exam}/questions/{question}/essay/guidance', [EssayQuestionController::class, 'generateGuidance'])->name('questions.essay.guidance');
    Route::put('/{exam}/questions/{question}/essay', [EssayQuestionController::class, 'update'])->name('questions.essay.update');
    Route::get('/{exam}/questions/{question}/coding', [CodingQuestionController::class, 'edit'])->name('questions.coding.edit');
    Route::put('/{exam}/questions/{question}/coding', [CodingQuestionController::class, 'update'])->name('questions.coding.update');
    Route::get('/{exam}/questions/{question}/packet-tracer', [PacketTracerQuestionController::class, 'edit'])->name('questions.packet-tracer.edit');
    Route::put('/{exam}/questions/{question}/packet-tracer', [PacketTracerQuestionController::class, 'update'])->name('questions.packet-tracer.update');
    Route::delete('/{exam}/questions/{question}', [QuestionOrderingController::class, 'destroy'])->name('questions.destroy');
});

Route::prefix('instructor/grading')->name('instructor.grading.')->middleware(['auth', 'screen'])->group(function () {
    Route::get('/', [ManualGradingController::class, 'index'])->name('index');
    Route::get('/sessions/{session}', [ManualGradingController::class, 'show'])->name('sessions.show');
    Route::post('/sessions/{session}/answers/{answer}/assist-answer', [ManualGradingController::class, 'assistAnswer'])->name('answers.assist-answer');
    Route::post('/api/sessions/{session}/answers/{answer}/assist-answer', [ManualGradingController::class, 'assistAnswer'])->name('api.answers.assist-answer');
    Route::post('/api/sessions/{session}/answers/{answer}/assist-browser', [ManualGradingController::class, 'storeClientAssistSuggestion'])->name('api.answers.assist-browser');
    Route::post('/sessions/{session}/answers/{answer}/assist-essay', [ManualGradingController::class, 'assistEssay'])->name('answers.assist-essay');
    Route::post('/api/sessions/{session}/answers/{answer}/assist-essay', [ManualGradingController::class, 'assistEssay'])->name('api.answers.assist-essay');
    Route::put('/sessions/{session}/answers/{answer}', [ManualGradingController::class, 'updateAnswer'])->name('answers.update');
});

Route::get('/groups', [AdminAccessController::class, 'index'])
    ->middleware(['auth', 'screen'])
    ->name('groups.index');

require __DIR__.'/auth.php';
