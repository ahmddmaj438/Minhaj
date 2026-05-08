<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ExamWizardController extends Controller
{
    public function step1(): View
    {
        return view('exams.wizard.step1');
    }

    public function storeStep1(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('button.exam.wizard.step1.next'), 403);

        $data = $request->validate([
            'exam_name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:1000'],
            'start_at' => ['nullable', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'pass_threshold' => ['required', 'numeric', 'min:0'],
        ]);

        session(['exam_wizard.step1' => $data]);

        return redirect()->route('exam.wizard.step2');
    }

    public function step2(): View
    {
        return view('exams.wizard.step2', [
            'modules' => DB::table('tce_modules')->orderBy('module_name')->get(),
        ]);
    }

    public function storeStep2(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('button.exam.wizard.step2.next'), 403);

        $data = $request->validate([
            'question_type' => ['required', 'integer', 'min:1', 'max:10'],
            'difficulty' => ['required', 'integer', 'min:1', 'max:10'],
            'questions_count' => ['required', 'integer', 'min:1', 'max:500'],
            'answers_per_question' => ['required', 'integer', 'min:0', 'max:20'],
            'module_id' => ['nullable', 'integer', 'exists:tce_modules,module_id'],
        ]);

        session(['exam_wizard.step2' => $data]);

        return redirect()->route('exam.wizard.step3');
    }

    public function step3(): View
    {
        $step2 = session('exam_wizard.step2', []);
        $subjectsQuery = DB::table('tce_subjects')->orderBy('subject_name');
        if (! empty($step2['module_id'])) {
            $subjectsQuery->where('subject_module_id', (int) $step2['module_id']);
        }

        return view('exams.wizard.step3', [
            'subjects' => $subjectsQuery->get(),
        ]);
    }

    public function finish(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('button.exam.wizard.finish.create_exam'), 403);
        abort_unless($request->user()?->can('db.tce_tests.insert'), 403);
        abort_unless($request->user()?->can('db.tce_test_subject_set.insert'), 403);
        abort_unless($request->user()?->can('db.tce_test_subjects.insert'), 403);

        $step1 = session('exam_wizard.step1');
        $step2 = session('exam_wizard.step2');
        abort_if(! $step1 || ! $step2, 422, 'Wizard session is missing. Restart exam creation.');

        $data = $request->validate([
            'subject_ids' => ['required', 'array', 'min:1'],
            'subject_ids.*' => ['integer', 'exists:tce_subjects,subject_id'],
        ]);

        DB::transaction(function () use ($step1, $step2, $data): void {
            $testId = DB::table('tce_tests')->insertGetId([
                'test_name' => $step1['exam_name'],
                'test_description' => $step1['description'],
                'test_begin_time' => $step1['start_at'] ?? null,
                'test_end_time' => $step1['end_at'] ?? null,
                'test_duration_time' => (int) $step1['duration_minutes'],
                'test_user_id' => auth()->id() ?? 1,
                'test_score_threshold' => (float) $step1['pass_threshold'],
            ], 'test_id');

            $subsetId = DB::table('tce_test_subject_set')->insertGetId([
                'tsubset_test_id' => $testId,
                'tsubset_type' => (int) $step2['question_type'],
                'tsubset_difficulty' => (int) $step2['difficulty'],
                'tsubset_quantity' => (int) $step2['questions_count'],
                'tsubset_answers' => (int) $step2['answers_per_question'],
            ], 'tsubset_id');

            foreach ($data['subject_ids'] as $subjectId) {
                DB::table('tce_test_subjects')->insert([
                    'subjset_tsubset_id' => $subsetId,
                    'subjset_subject_id' => (int) $subjectId,
                ]);
            }
        });

        session()->forget(['exam_wizard.step1', 'exam_wizard.step2']);

        return redirect()->route('data.table.index', ['table' => 'tce_tests'])->with('status', 'Exam created successfully.');
    }
}

