<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\StoreInstructorExamRequest;
use App\Http\Requests\Instructor\UpdateInstructorExamRequest;
use App\Models\Course;
use App\Models\Exam\InstructorExam;
use App\Models\Exam\InstructorExamQuestion;
use App\Services\Access\LearningAccess;
use App\Support\Exams\ExamDisplayFormatCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ExamSetupController extends Controller
{
    public function create(LearningAccess $access): View
    {
        $user = request()->user();

        $exams = $access->examQuery($user)
            ->with('course')
            ->withCount('questions')
            ->latest('updated_at')
            ->get();

        return view('instructor.exams.create', [
            'courses' => $access->courseQuery($user)
                ->where('is_active', true)
                ->orderBy('code')
                ->orderBy('name')
                ->get(),
            'exams' => $exams,
            'displayFormats' => ExamDisplayFormatCatalog::formats(),
        ]);
    }

    public function edit(InstructorExam $exam, LearningAccess $access): View
    {
        $this->authorizeInstructorExam($exam, $access);

        $questions = $exam->questions()->get();
        $user = request()->user();

        return view('instructor.exams.edit', [
            'exam' => $exam->load('course.majors'),
            'courses' => $access->courseQuery($user)
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

    public function store(StoreInstructorExamRequest $request, LearningAccess $access): RedirectResponse
    {
        abort_unless($request->user()?->can('button.instructor.exams.store.save_draft'), 403);
        abort_unless($request->user()?->can('db.instructor_exams.insert'), 403);

        $validated = $request->validated();
        abort_unless($access->canAccessCourse($request->user(), (int) $validated['course_id']), 403, 'This course is not assigned to you.');

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

    public function update(UpdateInstructorExamRequest $request, InstructorExam $exam, LearningAccess $access): RedirectResponse
    {
        abort_unless($request->user()?->can('db.instructor_exams.update'), 403);
        $this->authorizeInstructorExam($exam, $access);
        $validated = $request->validated();
        $keepsOwnedCourse = (int) $exam->instructor_id === (int) $request->user()->id
            && (int) $exam->course_id === (int) $validated['course_id'];
        abort_unless($keepsOwnedCourse || $access->canAccessCourse($request->user(), (int) $validated['course_id']), 403, 'This course is not assigned to you.');

        $exam->update($validated);

        return redirect()
            ->route('instructor.exams.edit', $exam)
            ->with('status', 'Exam updated.');
    }

    public function destroy(Request $request, InstructorExam $exam, LearningAccess $access): RedirectResponse
    {
        abort_unless($request->user()?->can('db.instructor_exams.delete'), 403);
        $this->authorizeInstructorExam($exam, $access);

        if ($exam->status === InstructorExam::STATUS_PUBLISHED || $exam->assignments()->exists() || $exam->sessions()->exists()) {
            throw ValidationException::withMessages([
                'exam' => __('This exam already has academic activity. Return it to draft or close its assignments instead of removing it.'),
            ]);
        }

        $exam->delete();

        return redirect()
            ->route('instructor.exams.create')
            ->with('status', 'Exam deleted.');
    }

    private function authorizeInstructorExam(InstructorExam $exam, LearningAccess $access): void
    {
        abort_unless($access->canAccessExam(auth()->user(), $exam), 403, 'You do not have permission to access this exam.');
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
