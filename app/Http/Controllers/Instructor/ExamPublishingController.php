<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Exam\InstructorExam;
use App\Services\Exams\ExamPublishReadiness;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ExamPublishingController extends Controller
{
    public function show(InstructorExam $exam, ExamPublishReadiness $readiness): View
    {
        $this->authorizeExam($exam);

        return view('instructor.exams.publish', [
            'exam' => $exam->load('course'),
            'readiness' => $readiness->inspect($exam),
        ]);
    }

    public function publish(
        Request $request,
        InstructorExam $exam,
        ExamPublishReadiness $readiness
    ): RedirectResponse {
        abort_unless($request->user()?->can('button.instructor.exams.publish'), 403);
        abort_unless($request->user()?->can('db.instructor_exams.update'), 403);
        $this->authorizeExam($exam);

        $result = $readiness->inspect($exam);

        if (! $result['ready']) {
            throw ValidationException::withMessages([
                'publish' => 'Complete every readiness check before publishing this exam.',
            ]);
        }

        $exam->update([
            'status' => InstructorExam::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);

        return redirect()
            ->route('instructor.exams.publish.show', $exam)
            ->with('status', 'Exam published successfully.');
    }

    public function returnToDraft(Request $request, InstructorExam $exam): RedirectResponse
    {
        abort_unless($request->user()?->can('button.instructor.exams.unpublish'), 403);
        abort_unless($request->user()?->can('db.instructor_exams.update'), 403);
        $this->authorizeExam($exam);

        $exam->update([
            'status' => InstructorExam::STATUS_DRAFT,
            'published_at' => null,
        ]);

        return redirect()
            ->route('instructor.exams.publish.show', $exam)
            ->with('status', 'Exam returned to draft.');
    }

    private function authorizeExam(InstructorExam $exam): void
    {
        abort_unless(auth()->id() === $exam->instructor_id || auth()->user()?->isSuperAdmin(), 403);
    }
}
