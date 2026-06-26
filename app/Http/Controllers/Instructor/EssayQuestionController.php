<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\UpdateEssayQuestionRequest;
use App\Models\Exam\InstructorExam;
use App\Models\Exam\InstructorExamQuestion;
use App\Services\Exams\ReviewGuidanceGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EssayQuestionController extends Controller
{
    public function edit(InstructorExam $exam, InstructorExamQuestion $question): View
    {
        $this->authorizeEssayQuestion($exam, $question);

        return view('instructor.exams.questions.essay', [
            'exam' => $exam->load('course'),
            'question' => $question,
        ]);
    }

    public function update(UpdateEssayQuestionRequest $request, InstructorExam $exam, InstructorExamQuestion $question): RedirectResponse
    {
        abort_unless($request->user()?->can('button.instructor.exams.questions.essay.save'), 403);
        abort_unless($request->user()?->can('db.instructor_exam_questions.update'), 403);

        $this->authorizeEssayQuestion($exam, $question);

        $validated = $request->validated();
        $intent = $validated['intent'];

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
                'builder_phase' => 'Phase 7',
                'expected_answer' => $validated['expected_answer'] ?? null,
                'rubric' => $validated['rubric'] ?? null,
                'key_points' => $validated['key_points'] ?? null,
                'mark_distribution' => $validated['mark_distribution'] ?? null,
                'common_mistakes' => $validated['common_mistakes'] ?? null,
                'evaluation_instructions' => $validated['evaluation_instructions'] ?? null,
                'review_guidance_status' => 'approved',
                'review_guidance_approved_at' => now()->toISOString(),
                'review_guidance_approved_by' => $request->user()?->id,
                'min_words' => $validated['min_words'] ?? null,
                'max_words' => $validated['max_words'] ?? null,
            ],
        ]);

        if ($intent === 'save_add_another') {
            $newQuestion = InstructorExamQuestion::create([
                'instructor_exam_id' => $exam->id,
                'type' => 'essay',
                'category' => 'text',
                'title' => 'Direct Question / Essay / Short Answer',
                'position' => $exam->questions()->max('position') + 1,
                'marks' => 1,
                'prompt' => [
                    'status' => 'type_selected',
                    'instructions' => null,
                ],
                'settings' => [
                    'builder_phase' => 'Phase 7',
                ],
            ]);

            return redirect()
                ->route('instructor.exams.questions.essay.edit', [$exam, $newQuestion])
                ->with('status', 'Essay question saved. A new Essay / Short Answer question is ready.');
        }

        return redirect()
            ->route('instructor.exams.questions.essay.edit', [$exam, $question])
            ->with('status', 'Essay question saved. You can review it here before moving to the next builder.');
    }

    public function generateGuidance(
        \Illuminate\Http\Request $request,
        InstructorExam $exam,
        InstructorExamQuestion $question,
        ReviewGuidanceGenerator $generator
    ): RedirectResponse {
        abort_unless($request->user()?->can('button.instructor.exams.questions.essay.save'), 403);
        abort_unless($request->user()?->can('db.instructor_exam_questions.update'), 403);
        $this->authorizeEssayQuestion($exam, $question);

        $settings = $question->settings ?? [];
        $draft = $generator->draft($question);

        $question->update([
            'settings' => [
                ...$settings,
                ...$draft,
            ],
        ]);

        return redirect()
            ->route('instructor.exams.questions.essay.edit', [$exam, $question])
            ->with('status', 'Review guidance draft generated. Please review and save the question before using it for grading.');
    }

    private function authorizeEssayQuestion(InstructorExam $exam, InstructorExamQuestion $question): void
    {
        abort_unless(auth()->id() === $exam->instructor_id || auth()->user()?->isSuperAdmin(), 403);
        abort_unless($question->instructor_exam_id === $exam->id, 404);
        abort_unless($question->type === 'essay', 404);
    }
}
