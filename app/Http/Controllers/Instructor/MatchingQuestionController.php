<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\UpdateMatchingQuestionRequest;
use App\Models\Exam\InstructorExam;
use App\Models\Exam\InstructorExamQuestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MatchingQuestionController extends Controller
{
    public function edit(InstructorExam $exam, InstructorExamQuestion $question): View
    {
        $this->authorizeMatchingQuestion($exam, $question);

        return view('instructor.exams.questions.matching', [
            'exam' => $exam->load('course'),
            'question' => $question,
            'pairs' => $this->pairsForForm($question),
        ]);
    }

    public function update(UpdateMatchingQuestionRequest $request, InstructorExam $exam, InstructorExamQuestion $question): RedirectResponse
    {
        abort_unless($request->user()?->can('button.instructor.exams.questions.matching.save'), 403);
        abort_unless($request->user()?->can('db.instructor_exam_questions.update'), 403);

        $this->authorizeMatchingQuestion($exam, $question);

        $validated = $request->validated();
        $intent = $validated['intent'];
        $pairs = collect($validated['pairs'])
            ->map(function (array $pair, int $index): ?array {
                $left = trim((string) ($pair['left'] ?? ''));
                $right = trim((string) ($pair['right'] ?? ''));

                if ($left === '' && $right === '') {
                    return null;
                }

                return [
                    'key' => 'pair_' . ($index + 1),
                    'left' => $left,
                    'right' => $right,
                    'note' => trim((string) ($pair['note'] ?? '')) ?: null,
                ];
            })
            ->filter()
            ->values()
            ->all();

        $question->update([
            'title' => str($validated['question_text'])->limit(80)->toString(),
            'marks' => $validated['marks'],
            'difficulty' => $validated['difficulty'] ?? null,
            'topic' => $validated['topic'] ?? null,
            'save_to_bank' => $request->boolean('save_to_bank'),
            'prompt' => [
                'status' => 'configured',
                'question_text' => $validated['question_text'],
                'instructions' => $validated['instructions'] ?? null,
            ],
            'settings' => [
                ...($question->settings ?? []),
                'builder_phase' => 'Phase 5',
                'shuffle_left_items' => $request->boolean('shuffle_left_items'),
                'shuffle_right_items' => $request->boolean('shuffle_right_items'),
                'pairs' => $pairs,
            ],
        ]);

        if ($intent === 'save_add_another') {
            $newQuestion = InstructorExamQuestion::create([
                'instructor_exam_id' => $exam->id,
                'type' => 'matching',
                'category' => 'objective',
                'title' => 'Matching',
                'position' => $exam->questions()->max('position') + 1,
                'marks' => 1,
                'prompt' => [
                    'status' => 'type_selected',
                    'instructions' => null,
                ],
                'settings' => [
                    'builder_phase' => 'Phase 5',
                ],
            ]);

            return redirect()
                ->route('instructor.exams.questions.matching.edit', [$exam, $newQuestion])
                ->with('status', 'Matching question saved. A new Matching question is ready.');
        }

        return redirect()
            ->route('instructor.exams.questions.matching.edit', [$exam, $question])
            ->with('status', 'Matching question saved. You can review it here before moving to the next builder.');
    }

    private function authorizeMatchingQuestion(InstructorExam $exam, InstructorExamQuestion $question): void
    {
        abort_unless(auth()->id() === $exam->instructor_id || auth()->user()?->isSuperAdmin(), 403);
        abort_unless($question->instructor_exam_id === $exam->id, 404);
        abort_unless($question->type === 'matching', 404);
    }

    private function pairsForForm(InstructorExamQuestion $question): array
    {
        $storedPairs = collect($question->settings['pairs'] ?? [])
            ->map(fn (array $pair) => [
                'left' => $pair['left'] ?? '',
                'right' => $pair['right'] ?? '',
                'note' => $pair['note'] ?? '',
            ])
            ->values();

        if ($storedPairs->isEmpty()) {
            $storedPairs = collect([
                ['left' => '', 'right' => '', 'note' => ''],
                ['left' => '', 'right' => '', 'note' => ''],
                ['left' => '', 'right' => '', 'note' => ''],
            ]);
        }

        return $storedPairs->all();
    }
}
