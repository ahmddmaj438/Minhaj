<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\UpdateTrueFalseQuestionRequest;
use App\Models\Exam\InstructorExam;
use App\Models\Exam\InstructorExamQuestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TrueFalseQuestionController extends Controller
{
    public function edit(InstructorExam $exam, InstructorExamQuestion $question): View
    {
        $this->authorizeTrueFalseQuestion($exam, $question);

        return view('instructor.exams.questions.true-false', [
            'exam' => $exam->load('course'),
            'question' => $question,
            'requiresCorrection' => $question->type === 'true_false_correct',
        ]);
    }

    public function update(UpdateTrueFalseQuestionRequest $request, InstructorExam $exam, InstructorExamQuestion $question): RedirectResponse
    {
        abort_unless($request->user()?->can('button.instructor.exams.questions.true_false.save'), 403);
        abort_unless($request->user()?->can('db.instructor_exam_questions.update'), 403);

        $this->authorizeTrueFalseQuestion($exam, $question);

        $validated = $request->validated();
        $intent = $validated['intent'];
        $requiresCorrection = $question->type === 'true_false_correct';
        $wrongTerms = collect($validated['wrong_terms'] ?? [])
            ->map(fn (array $term) => [
                'text' => trim((string) ($term['text'] ?? '')),
                'correction' => trim((string) ($term['correction'] ?? '')),
            ])
            ->filter(fn (array $term) => $term['text'] !== '' && $term['correction'] !== '')
            ->values()
            ->all();

        $question->update([
            'title' => str($validated['statement'])->limit(80)->toString(),
            'marks' => $validated['marks'],
            'difficulty' => $validated['difficulty'] ?? null,
            'topic' => $validated['topic'] ?? null,
            'save_to_bank' => $request->boolean('save_to_bank'),
            'prompt' => [
                'status' => 'configured',
                'statement' => $validated['statement'],
                'instructions' => $validated['instructions'] ?? null,
            ],
            'settings' => [
                ...($question->settings ?? []),
                'builder_phase' => 'Phase 4',
                'correct_answer' => $validated['correct_answer'] === 'true',
                'requires_correction' => $requiresCorrection,
                'wrong_terms' => $requiresCorrection ? $wrongTerms : [],
                'corrected_statement' => $requiresCorrection ? ($validated['corrected_statement'] ?? null) : null,
                'explanation' => $validated['explanation'] ?? null,
            ],
        ]);

        if ($intent === 'save_add_another') {
            $newQuestion = InstructorExamQuestion::create([
                'instructor_exam_id' => $exam->id,
                'type' => $question->type,
                'category' => 'objective',
                'title' => $requiresCorrection ? 'True / False + Correction' : 'True / False',
                'position' => $exam->questions()->max('position') + 1,
                'marks' => 1,
                'prompt' => [
                    'status' => 'type_selected',
                    'instructions' => null,
                ],
                'settings' => [
                    'builder_phase' => 'Phase 4',
                    'requires_correction' => $requiresCorrection,
                ],
            ]);

            return redirect()
                ->route('instructor.exams.questions.true-false.edit', [$exam, $newQuestion])
                ->with('status', 'Question saved. A new ' . ($requiresCorrection ? 'True/False + Correction' : 'True/False') . ' question is ready.');
        }

        return redirect()
            ->route('instructor.exams.questions.true-false.edit', [$exam, $question])
            ->with('status', 'True/False question saved. You can review it here before moving to the next builder.');
    }

    private function authorizeTrueFalseQuestion(InstructorExam $exam, InstructorExamQuestion $question): void
    {
        abort_unless(auth()->id() === $exam->instructor_id || auth()->user()?->isSuperAdmin(), 403);
        abort_unless($question->instructor_exam_id === $exam->id, 404);
        abort_unless(in_array($question->type, ['true_false', 'true_false_correct'], true), 404);
    }
}
