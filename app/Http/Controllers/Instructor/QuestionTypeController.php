<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\StoreQuestionTypeSelectionRequest;
use App\Models\Exam\InstructorExam;
use App\Models\Exam\InstructorExamQuestion;
use App\Support\Exams\QuestionTypeCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class QuestionTypeController extends Controller
{
    public function index(InstructorExam $exam): View
    {
        $this->authorizeInstructorExam($exam);

        return view('instructor.exams.question-types', [
            'exam' => $exam->load('course'),
            'categories' => QuestionTypeCatalog::categories(),
            'questionCount' => $exam->questions()->count(),
        ]);
    }

    public function store(StoreQuestionTypeSelectionRequest $request, InstructorExam $exam): RedirectResponse
    {
        abort_unless($request->user()?->can('button.instructor.exams.questions.select_type'), 403);
        abort_unless($request->user()?->can('db.instructor_exam_questions.insert'), 403);

        $this->authorizeInstructorExam($exam);

        $type = QuestionTypeCatalog::find($request->validated()['question_type']);
        abort_if($type === null, 422, 'Unsupported question type.');

        $position = $exam->questions()->max('position') + 1;

        $question = InstructorExamQuestion::create([
            'instructor_exam_id' => $exam->id,
            'type' => $type['key'],
            'category' => $type['category'],
            'title' => $type['label'],
            'position' => $position,
            'marks' => 1,
            'programming_language' => $type['language'] ?? null,
            'prompt' => [
                'status' => 'type_selected',
                'instructions' => null,
            ],
            'settings' => [
                'builder_phase' => $type['builder_phase'],
            ],
        ]);

        if ($type['key'] === 'mcq') {
            return redirect()
                ->route('instructor.exams.questions.mcq.edit', [$exam, $question])
                ->with('status', 'Multiple Choice selected. Build the question details below.');
        }

        if (in_array($type['key'], ['true_false', 'true_false_correct'], true)) {
            return redirect()
                ->route('instructor.exams.questions.true-false.edit', [$exam, $question])
                ->with('status', $type['label'] . ' selected. Build the statement below.');
        }

        if ($type['key'] === 'matching') {
            return redirect()
                ->route('instructor.exams.questions.matching.edit', [$exam, $question])
                ->with('status', 'Matching selected. Build the pairs below.');
        }

        if ($type['key'] === 'fill_blank') {
            return redirect()
                ->route('instructor.exams.questions.fill-blank.edit', [$exam, $question])
                ->with('status', 'Fill in the Blank selected. Build the blanks below.');
        }

        if ($type['key'] === 'essay') {
            return redirect()
                ->route('instructor.exams.questions.essay.edit', [$exam, $question])
                ->with('status', 'Essay / Short Answer selected. Build the prompt below.');
        }

        if ($type['category'] === 'coding') {
            return redirect()
                ->route('instructor.exams.questions.coding.edit', [$exam, $question])
                ->with('status', $type['label'] . ' selected. Build the coding task below.');
        }

        if ($type['key'] === 'packet_tracer') {
            return redirect()
                ->route('instructor.exams.questions.packet-tracer.edit', [$exam, $question])
                ->with('status', 'Packet Tracer Scenario selected. Build the networking task below.');
        }

        return redirect()
            ->route('instructor.exams.question-types.index', $exam)
            ->with('status', $type['label'] . ' question type selected. Its dedicated builder will be added in the next phase.');
    }

    private function authorizeInstructorExam(InstructorExam $exam): void
    {
        abort_unless(auth()->id() === $exam->instructor_id || auth()->user()?->isSuperAdmin(), 403);
    }
}
