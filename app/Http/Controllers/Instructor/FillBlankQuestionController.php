<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\UpdateFillBlankQuestionRequest;
use App\Models\Exam\InstructorExam;
use App\Models\Exam\InstructorExamQuestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FillBlankQuestionController extends Controller
{
    public function edit(InstructorExam $exam, InstructorExamQuestion $question): View
    {
        $this->authorizeFillBlankQuestion($exam, $question);

        return view('instructor.exams.questions.fill-blank', [
            'exam' => $exam->load('course'),
            'question' => $question,
            'blanks' => $this->blanksForForm($question),
        ]);
    }

    public function update(UpdateFillBlankQuestionRequest $request, InstructorExam $exam, InstructorExamQuestion $question): RedirectResponse
    {
        abort_unless($request->user()?->can('button.instructor.exams.questions.fill_blank.save'), 403);
        abort_unless($request->user()?->can('db.instructor_exam_questions.update'), 403);

        $this->authorizeFillBlankQuestion($exam, $question);

        $validated = $request->validated();
        $intent = $validated['intent'];
        $blanks = collect($validated['blanks'])
            ->map(function (array $blank, int $index): ?array {
                $answers = trim((string) ($blank['answers'] ?? ''));

                if ($answers === '') {
                    return null;
                }

                return [
                    'key' => 'blank_' . ($index + 1),
                    'label' => trim((string) ($blank['label'] ?? '')) ?: 'Blank ' . ($index + 1),
                    'accepted_answers' => collect(explode('|', $answers))
                        ->map(fn (string $answer) => trim($answer))
                        ->filter()
                        ->values()
                        ->all(),
                    'hint' => trim((string) ($blank['hint'] ?? '')) ?: null,
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
                'builder_phase' => 'Phase 6',
                'case_sensitive' => $request->boolean('case_sensitive'),
                'trim_whitespace' => $request->boolean('trim_whitespace'),
                'blanks' => $blanks,
            ],
        ]);

        if ($intent === 'save_add_another') {
            $newQuestion = InstructorExamQuestion::create([
                'instructor_exam_id' => $exam->id,
                'type' => 'fill_blank',
                'category' => 'text',
                'title' => 'Fill in the Blank',
                'position' => $exam->questions()->max('position') + 1,
                'marks' => 1,
                'prompt' => [
                    'status' => 'type_selected',
                    'instructions' => null,
                ],
                'settings' => [
                    'builder_phase' => 'Phase 6',
                ],
            ]);

            return redirect()
                ->route('instructor.exams.questions.fill-blank.edit', [$exam, $newQuestion])
                ->with('status', 'Fill-in-the-blank question saved. A new one is ready.');
        }

        return redirect()
            ->route('instructor.exams.questions.fill-blank.edit', [$exam, $question])
            ->with('status', 'Fill-in-the-blank question saved. You can review it here before moving to the next builder.');
    }

    private function authorizeFillBlankQuestion(InstructorExam $exam, InstructorExamQuestion $question): void
    {
        abort_unless(auth()->id() === $exam->instructor_id || auth()->user()?->isSuperAdmin(), 403);
        abort_unless($question->instructor_exam_id === $exam->id, 404);
        abort_unless($question->type === 'fill_blank', 404);
    }

    private function blanksForForm(InstructorExamQuestion $question): array
    {
        $storedBlanks = collect($question->settings['blanks'] ?? [])
            ->map(fn (array $blank) => [
                'label' => $blank['label'] ?? '',
                'answers' => implode(' | ', $blank['accepted_answers'] ?? []),
                'hint' => $blank['hint'] ?? '',
            ])
            ->values();

        if ($storedBlanks->isEmpty()) {
            $storedBlanks = collect([
                ['label' => 'Blank 1', 'answers' => '', 'hint' => ''],
            ]);
        }

        return $storedBlanks->all();
    }
}
