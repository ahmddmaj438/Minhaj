<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\StoreInstructorExamRequest;
use App\Models\Course;
use App\Models\Exam\InstructorExam;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ExamSetupController extends Controller
{
    public function create(): View
    {
        return view('instructor.exams.create', [
            'courses' => Course::query()
                ->where('is_active', true)
                ->orderBy('code')
                ->orderBy('name')
                ->get(),
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
}
