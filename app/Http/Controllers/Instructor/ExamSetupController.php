<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\StoreInstructorExamRequest;
use App\Http\Requests\Instructor\UpdateInstructorExamRequest;
use App\Models\Course;
use App\Models\Exam\InstructorExam;
use App\Models\Exam\InstructorExamQuestion;
use App\Support\Exams\ExamDisplayFormatCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExamSetupController extends Controller
{
    public function create(): View
    {
        $user = request()->user();

        $exams = InstructorExam::query()
            ->with('course')
            ->withCount('questions')
            ->when(! $user?->isSuperAdmin(), fn ($query) => $query->where('instructor_id', $user?->id))
            ->latest('updated_at')
            ->get();

        return view('instructor.exams.create', [
            'courses' => Course::query()
                ->where('is_active', true)
                ->orderBy('code')
                ->orderBy('name')
                ->get(),
            'exams' => $exams,
            'displayFormats' => ExamDisplayFormatCatalog::formats(),
        ]);
    }

    public function edit(InstructorExam $exam): View
    {
        $this->authorizeInstructorExam($exam);

        $questions = $exam->questions()->get();

        return view('instructor.exams.edit', [
            'exam' => $exam->load('course'),
            'courses' => Course::query()
                ->where('is_active', true)
                ->orderBy('code')
                ->orderBy('name')
                ->get(),
            'questions' => $questions,
            'questionEditRoutes' => $this->questionEditRoutes($questions),
            'totalQuestionMarks' => $questions->sum(fn (InstructorExamQuestion $question) => (float) $question->marks),
            'displayFormats' => ExamDisplayFormatCatalog::formats(),
        ]);
    }

    public function store(StoreInstructorExamRequest $request): RedirectResponse
    {
        abort_unless($request->user()?->can('button.instructor.exams.store.save_draft'), 403);
        abort_unless($request->user()?->can('db.instructor_exams.insert'), 403);

        $validated = $request->validated();
        $intent = $validated['intent'];
        unset($validated['intent']);

        $exam = InstructorExam::create([
            ...$validated,
            'instructor_id' => $request->user()->id,
            'status' => InstructorExam::STATUS_DRAFT,
            'published_at' => null,
        ]);

        $message = $intent === 'publish_later'
            ? 'Exam setup saved. Choose the first question type when you are ready.'
            : 'Draft exam saved.';

        $route = $intent === 'publish_later'
            ? 'instructor.exams.question-types.index'
            : 'instructor.exams.create';

        $parameters = $intent === 'publish_later' ? [$exam] : [];

        return redirect()
            ->route($route, $parameters)
            ->with('status', $message);
    }

    public function update(UpdateInstructorExamRequest $request, InstructorExam $exam): RedirectResponse
    {
        abort_unless($request->user()?->can('db.instructor_exams.update'), 403);
        $this->authorizeInstructorExam($exam);

        $exam->update($request->validated());

        return redirect()
            ->route('instructor.exams.edit', $exam)
            ->with('status', 'Exam updated.');
    }

    public function destroy(Request $request, InstructorExam $exam): RedirectResponse
    {
        abort_unless($request->user()?->can('db.instructor_exams.delete'), 403);
        $this->authorizeInstructorExam($exam);

        $exam->delete();

        return redirect()
            ->route('instructor.exams.create')
            ->with('status', 'Exam deleted.');
    }

    private function authorizeInstructorExam(InstructorExam $exam): void
    {
        abort_unless(auth()->id() === $exam->instructor_id || auth()->user()?->isSuperAdmin(), 403);
    }

    private function questionEditRoutes($questions): array
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
