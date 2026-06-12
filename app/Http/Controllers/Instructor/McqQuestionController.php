<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\UpdateMcqQuestionRequest;
use App\Models\Exam\InstructorExam;
use App\Models\Exam\InstructorExamQuestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class McqQuestionController extends Controller
{
    public function edit(InstructorExam $exam, InstructorExamQuestion $question): View
    {
        $this->authorizeMcqQuestion($exam, $question);

        return view('instructor.exams.questions.mcq', [
            'exam' => $exam->load('course'),
            'question' => $question,
            'options' => $this->optionsForForm($question),
        ]);
    }

    public function update(UpdateMcqQuestionRequest $request, InstructorExam $exam, InstructorExamQuestion $question): RedirectResponse
    {
        abort_unless($request->user()?->can('button.instructor.exams.questions.mcq.save'), 403);
        abort_unless($request->user()?->can('db.instructor_exam_questions.update'), 403);

        $this->authorizeMcqQuestion($exam, $question);

        $validated = $request->validated();
        $intent = $validated['intent'];
        $correctIndexes = collect($validated['correct_options'] ?? [])->map(fn ($index) => (int) $index)->all();

        $options = collect($validated['options'])
            ->map(function (array $option, int $index) use ($correctIndexes): ?array {
                $text = trim((string) ($option['text'] ?? ''));

                if ($text === '') {
                    return null;
                }

                return [
                    'key' => 'option_' . ($index + 1),
                    'text' => $text,
                    'feedback' => trim((string) ($option['feedback'] ?? '')) ?: null,
                    'is_correct' => in_array($index, $correctIndexes, true),
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
            'display_override' => $validated['display_override'],
            'save_to_bank' => $request->boolean('save_to_bank'),
            'prompt' => [
                'status' => 'configured',
                'question_text' => $validated['question_text'],
                'instructions' => $validated['instructions'] ?? null,
            ],
            'settings' => [
                ...($question->settings ?? []),
                'builder_phase' => 'Phase 3',
                'allow_multiple_correct' => $request->boolean('allow_multiple_correct'),
                'shuffle_options' => $request->boolean('shuffle_options'),
                'options' => $options,
            ],
        ]);

        if ($intent === 'save_add_another') {
            $newQuestion = InstructorExamQuestion::create([
                'instructor_exam_id' => $exam->id,
                'type' => 'mcq',
                'category' => 'objective',
                'title' => 'Multiple Choice',
                'position' => $exam->questions()->max('position') + 1,
                'marks' => 1,
                'prompt' => [
                    'status' => 'type_selected',
                    'instructions' => null,
                ],
                'settings' => [
                    'builder_phase' => 'Phase 3',
                ],
            ]);

            return redirect()
                ->route('instructor.exams.questions.mcq.edit', [$exam, $newQuestion])
                ->with('status', 'MCQ question saved. A new Multiple Choice question is ready.');
        }

        return redirect()
            ->route('instructor.exams.questions.mcq.edit', [$exam, $question])
            ->with('status', 'MCQ question saved. You can review it here before moving to ordering and preview later.');
    }

    private function authorizeMcqQuestion(InstructorExam $exam, InstructorExamQuestion $question): void
    {
        abort_unless(auth()->id() === $exam->instructor_id || auth()->user()?->isSuperAdmin(), 403);
        abort_unless($question->instructor_exam_id === $exam->id, 404);
        abort_unless($question->type === 'mcq', 404);
    }

    private function optionsForForm(InstructorExamQuestion $question): array
    {
        $storedOptions = collect($question->settings['options'] ?? [])
            ->map(fn (array $option) => [
                'text' => $option['text'] ?? '',
                'feedback' => $option['feedback'] ?? '',
                'is_correct' => (bool) ($option['is_correct'] ?? false),
            ])
            ->values();

        if ($storedOptions->isEmpty()) {
            $storedOptions = collect([
                ['text' => '', 'feedback' => '', 'is_correct' => true],
                ['text' => '', 'feedback' => '', 'is_correct' => false],
                ['text' => '', 'feedback' => '', 'is_correct' => false],
                ['text' => '', 'feedback' => '', 'is_correct' => false],
            ]);
        }

        return $storedOptions->all();
    }
}
