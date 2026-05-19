<?php

use App\Http\Controllers\AdminAccessController;
use App\Http\Controllers\ExamWizardController;
use App\Http\Controllers\Instructor\CodingQuestionController;
use App\Http\Controllers\Instructor\ExamPreviewController;
use App\Http\Controllers\Instructor\ExamSetupController;
use App\Http\Controllers\Instructor\EssayQuestionController;
use App\Http\Controllers\Instructor\FillBlankQuestionController;
use App\Http\Controllers\Instructor\MatchingQuestionController;
use App\Http\Controllers\Instructor\McqQuestionController;
use App\Http\Controllers\Instructor\PacketTracerQuestionController;
use App\Http\Controllers\Instructor\QuestionOrderingController;
use App\Http\Controllers\Instructor\QuestionTypeController;
use App\Http\Controllers\Instructor\TrueFalseQuestionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SuperUserController;
use App\Http\Controllers\TCExamCrudController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified', 'screen'])->name('dashboard');

Route::middleware(['auth', 'screen'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UserManagementController::class, 'create'])->name('users.create');
    Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'screen'])->group(function () {
    Route::get('/access', [AdminAccessController::class, 'index'])->name('access.index');
    Route::post('/groups', [AdminAccessController::class, 'storeGroup'])->name('groups.store');
    Route::put('/groups/{group}/screens', [AdminAccessController::class, 'updateGroupScreens'])->name('groups.screens.update');
    Route::put('/groups/{group}/buttons', [AdminAccessController::class, 'updateGroupButtons'])->name('groups.buttons.update');
    Route::put('/groups/{group}/db-access', [AdminAccessController::class, 'updateGroupDbAccess'])->name('groups.db.update');
    Route::put('/groups/{group}/users', [AdminAccessController::class, 'updateGroupUsers'])->name('groups.users.update');
    Route::get('/super-users', [SuperUserController::class, 'index'])->name('super-users.index');
    Route::post('/super-users/grant', [SuperUserController::class, 'grant'])->name('super-users.grant');
    Route::delete('/super-users/{user}', [SuperUserController::class, 'revoke'])->name('super-users.revoke');
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
    Route::get('/{exam}/question-types', [QuestionTypeController::class, 'index'])->name('question-types.index');
    Route::post('/{exam}/question-types', [QuestionTypeController::class, 'store'])->name('question-types.store');
    Route::get('/{exam}/preview', [ExamPreviewController::class, 'show'])->name('preview.show');
    Route::get('/{exam}/questions/order', [QuestionOrderingController::class, 'index'])->name('questions.order.index');
    Route::patch('/{exam}/questions/order', [QuestionOrderingController::class, 'update'])->name('questions.order.update');
    Route::get('/{exam}/questions/{question}/mcq', [McqQuestionController::class, 'edit'])->name('questions.mcq.edit');
    Route::put('/{exam}/questions/{question}/mcq', [McqQuestionController::class, 'update'])->name('questions.mcq.update');
    Route::get('/{exam}/questions/{question}/true-false', [TrueFalseQuestionController::class, 'edit'])->name('questions.true-false.edit');
    Route::put('/{exam}/questions/{question}/true-false', [TrueFalseQuestionController::class, 'update'])->name('questions.true-false.update');
    Route::get('/{exam}/questions/{question}/matching', [MatchingQuestionController::class, 'edit'])->name('questions.matching.edit');
    Route::put('/{exam}/questions/{question}/matching', [MatchingQuestionController::class, 'update'])->name('questions.matching.update');
    Route::get('/{exam}/questions/{question}/fill-blank', [FillBlankQuestionController::class, 'edit'])->name('questions.fill-blank.edit');
    Route::put('/{exam}/questions/{question}/fill-blank', [FillBlankQuestionController::class, 'update'])->name('questions.fill-blank.update');
    Route::get('/{exam}/questions/{question}/essay', [EssayQuestionController::class, 'edit'])->name('questions.essay.edit');
    Route::put('/{exam}/questions/{question}/essay', [EssayQuestionController::class, 'update'])->name('questions.essay.update');
    Route::get('/{exam}/questions/{question}/coding', [CodingQuestionController::class, 'edit'])->name('questions.coding.edit');
    Route::put('/{exam}/questions/{question}/coding', [CodingQuestionController::class, 'update'])->name('questions.coding.update');
    Route::get('/{exam}/questions/{question}/packet-tracer', [PacketTracerQuestionController::class, 'edit'])->name('questions.packet-tracer.edit');
    Route::put('/{exam}/questions/{question}/packet-tracer', [PacketTracerQuestionController::class, 'update'])->name('questions.packet-tracer.update');
    Route::delete('/{exam}/questions/{question}', [QuestionOrderingController::class, 'destroy'])->name('questions.destroy');
});

Route::get('/groups', [AdminAccessController::class, 'index'])
    ->middleware(['auth', 'screen'])
    ->name('groups.index');

require __DIR__.'/auth.php';
