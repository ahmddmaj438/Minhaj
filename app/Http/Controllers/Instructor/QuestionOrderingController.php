<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\UpdateQuestionOrderRequest;
use App\Models\Exam\InstructorExam;
use App\Models\Exam\InstructorExamQuestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class QuestionOrderingController extends Controller
{
    public function index(InstructorExam $exam): View
    {
        $this->authorizeExam($exam);

        $questions = $exam->questions()->get();

        return view('instructor.exams.questions.order', [
            'exam' => $exam->load('course.majors'),
            'questions' => $questions,
            'totalQuestionMarks' => $questions->sum(fn (InstructorExamQuestion $question) => (float) $question->marks),
            'editRoutes' => $this->editRoutes($questions),
        ]);
    }

    public function update(UpdateQuestionOrderRequest $request, InstructorExam $exam): RedirectResponse
    {
        abort_unless($request->user()?->can('button.instructor.exams.questions.order.save'), 403);
        abort_unless($request->user()?->can('db.instructor_exam_questions.update'), 403);

        $this->authorizeExam($exam);

        $payload = collect($request->validated()['questions'])
            ->map(fn (array $question) => [
                'id' => (int) $question['id'],
                'position' => (int) $question['position'],
                'marks' => (float) $question['marks'],
            ])
            ->sortBy('position')
            ->values();

        $ownedIds = $exam->questions()->pluck('id')->all();
        abort_if($payload->pluck('id')->diff($ownedIds)->isNotEmpty(), 403);

        $payload->each(function (array $question, int $index): void {
            InstructorExamQuestion::whereKey($question['id'])->update([
                'position' => $index + 1,
                'marks' => $question['marks'],
            ]);
        });

        return redirect()
            ->route('instructor.exams.questions.order.index', $exam)
            ->with('status', 'Question order and marks updated.');
    }

    public function duplicate(InstructorExam $exam, InstructorExamQuestion $question): RedirectResponse
    {
        abort_unless(auth()->user()?->can('button.instructor.exams.questions.order.duplicate'), 403);
        abort_unless(auth()->user()?->can('db.instructor_exam_questions.insert'), 403);

        $this->authorizeExam($exam);
        abort_unless($question->instructor_exam_id === $exam->id, 404);

        $copy = $question->replicate([
            'tcexam_question_id',
        ]);
        $copy->title = 'Copy of '.$question->title;
        $copy->position = $exam->questions()->max('position') + 1;
        $copy->save();

        return redirect()
            ->route('instructor.exams.questions.order.index', $exam)
            ->with('status', 'Question duplicated. Review the copy before publishing.');
    }

    public function destroy(InstructorExam $exam, InstructorExamQuestion $question): RedirectResponse
    {
        abort_unless(auth()->user()?->can('button.instructor.exams.questions.order.delete'), 403);
        abort_unless(auth()->user()?->can('db.instructor_exam_questions.delete'), 403);

        $this->authorizeExam($exam);
        abort_unless($question->instructor_exam_id === $exam->id, 404);

        $question->delete();
        $this->normalizePositions($exam);

        return redirect()
            ->route('instructor.exams.questions.order.index', $exam)
            ->with('status', 'Question removed and ordering updated.');
    }

    private function authorizeExam(InstructorExam $exam): void
    {
        abort_unless(auth()->id() === $exam->instructor_id || auth()->user()?->isSuperAdmin(), 403);
    }

    private function normalizePositions(InstructorExam $exam): void
    {
        $exam->questions()->get()->values()->each(function (InstructorExamQuestion $question, int $index): void {
            $question->update(['position' => $index + 1]);
        });
    }

    private function editRoutes(Collection $questions): array
    {
        return $questions->mapWithKeys(fn (InstructorExamQuestion $question) => [
            $question->id => match ($question->type) {
                'mcq' => route('instructor.exams.questions.mcq.edit', [$question->exam, $question]),
                'true_false', 'true_false_correct' => route('instructor.exams.questions.true-false.edit', [$question->exam, $question]),
                'matching' => route('instructor.exams.questions.matching.edit', [$question->exam, $question]),
                'fill_blank' => route('instructor.exams.questions.fill-blank.edit', [$question->exam, $question]),
                'essay' => route('instructor.exams.questions.essay.edit', [$question->exam, $question]),
                'packet_tracer' => route('instructor.exams.questions.packet-tracer.edit', [$question->exam, $question]),
                default => $question->category === 'coding'
                    ? route('instructor.exams.questions.coding.edit', [$question->exam, $question])
                    : route('instructor.exams.question-types.index', $question->exam),
            },
        ])->all();
    }
}
