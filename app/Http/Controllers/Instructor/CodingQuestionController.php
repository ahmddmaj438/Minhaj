<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\UpdateCodingQuestionRequest;
use App\Models\Exam\InstructorExam;
use App\Models\Exam\InstructorExamQuestion;
use App\Support\Exams\QuestionTypeCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CodingQuestionController extends Controller
{
    public function edit(InstructorExam $exam, InstructorExamQuestion $question): View
    {
        $this->authorizeCodingQuestion($exam, $question);

        return view('instructor.exams.questions.coding', [
            'exam' => $exam->load('course'),
            'question' => $question,
            'type' => QuestionTypeCatalog::find($question->type),
        ]);
    }

    public function update(UpdateCodingQuestionRequest $request, InstructorExam $exam, InstructorExamQuestion $question): RedirectResponse
    {
        abort_unless($request->user()?->can('button.instructor.exams.questions.coding.save'), 403);
        abort_unless($request->user()?->can('db.instructor_exam_questions.update'), 403);

        $this->authorizeCodingQuestion($exam, $question);

        $validated = $request->validated();
        $intent = $validated['intent'];

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
                'builder_phase' => 'Phase 8',
                'starter_code' => $validated['starter_code'] ?? null,
                'expected_output' => $validated['expected_output'] ?? null,
                'constraints' => $validated['constraints'] ?? null,
                'sample_input' => $validated['sample_input'] ?? null,
                'sample_output' => $validated['sample_output'] ?? null,
                'test_case_notes' => $validated['test_case_notes'] ?? null,
            ],
        ]);

        if ($intent === 'save_add_another') {
            $type = QuestionTypeCatalog::find($question->type);
            $newQuestion = InstructorExamQuestion::create([
                'instructor_exam_id' => $exam->id,
                'type' => $question->type,
                'category' => 'coding',
                'title' => $type['label'] ?? 'Coding Question',
                'position' => $exam->questions()->max('position') + 1,
                'marks' => 1,
                'programming_language' => $question->programming_language,
                'prompt' => [
                    'status' => 'type_selected',
                    'instructions' => null,
                ],
                'settings' => [
                    'builder_phase' => 'Phase 8',
                ],
            ]);

            return redirect()
                ->route('instructor.exams.questions.coding.edit', [$exam, $newQuestion])
                ->with('status', 'Coding question saved. A new ' . ($type['label'] ?? 'coding') . ' question is ready.');
        }

        return redirect()
            ->route('instructor.exams.questions.coding.edit', [$exam, $question])
            ->with('status', 'Coding question saved. You can review it here before moving to the next builder.');
    }

    private function authorizeCodingQuestion(InstructorExam $exam, InstructorExamQuestion $question): void
    {
        abort_unless(auth()->id() === $exam->instructor_id || auth()->user()?->isSuperAdmin(), 403);
        abort_unless($question->instructor_exam_id === $exam->id, 404);
        abort_unless($question->category === 'coding', 404);
    }
}
