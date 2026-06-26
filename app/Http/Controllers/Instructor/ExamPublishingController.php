<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Exam\InstructorExam;
use App\Services\Access\LearningAccess;
use App\Services\Exams\ExamPublishReadiness;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ExamPublishingController extends Controller
{
    public function show(InstructorExam $exam, ExamPublishReadiness $readiness, LearningAccess $access): View
    {
        $this->authorizeExam($exam, $access);

        return view('instructor.exams.publish', [
            'exam' => $exam->load('course', 'questions'),
            'readiness' => $readiness->inspect($exam),
        ]);
    }

    public function publish(
        Request $request,
        InstructorExam $exam,
        ExamPublishReadiness $readiness,
        LearningAccess $access
    ): RedirectResponse {
        abort_unless($request->user()?->can('button.instructor.exams.publish'), 403);
        abort_unless($request->user()?->can('db.instructor_exams.update'), 403);
        $this->authorizeExam($exam, $access);

        $result = $readiness->inspect($exam);

        if (! $result['ready']) {
            throw ValidationException::withMessages([
                'publish' => 'Complete every readiness check before publishing this exam.',
            ]);
        }

        DB::transaction(fn () => $exam->update([
            'status' => InstructorExam::STATUS_PUBLISHED,
            'published_at' => now(),
        ]));

        return redirect()
            ->route('instructor.exams.publish.show', $exam)
            ->with('status', 'Exam published successfully.');
    }

    public function returnToDraft(Request $request, InstructorExam $exam, LearningAccess $access): RedirectResponse
    {
        abort_unless($request->user()?->can('button.instructor.exams.unpublish'), 403);
        abort_unless($request->user()?->can('db.instructor_exams.update'), 403);
        $this->authorizeExam($exam, $access);

        if ($exam->sessions()->exists()) {
            throw ValidationException::withMessages([
                'publish' => __('This exam already has student attempts. Close assignments instead of returning it to draft.'),
            ]);
        }

        DB::transaction(fn () => $exam->update([
            'status' => InstructorExam::STATUS_DRAFT,
            'published_at' => null,
        ]));

        return redirect()
            ->route('instructor.exams.publish.show', $exam)
            ->with('status', 'Exam returned to draft.');
    }

    private function authorizeExam(InstructorExam $exam, LearningAccess $access): void
    {
        abort_unless($access->canAccessExam(auth()->user(), $exam), 403, 'You do not have permission to access this exam.');
    }
}
