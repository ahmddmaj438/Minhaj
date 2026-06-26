<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Exam\InstructorExam;
use App\Models\Exam\InstructorExamQuestion;
use App\Services\Access\LearningAccess;
use App\Support\Exams\ExamDisplayFormatCatalog;
use Illuminate\View\View;

class ExamPreviewController extends Controller
{
    public function show(InstructorExam $exam, LearningAccess $access): View
    {
        abort_unless($access->canAccessExam(auth()->user(), $exam), 403, 'You do not have permission to access this exam.');

        $questions = $exam->questions()->get();
        $displayFormat = ExamDisplayFormatCatalog::normalize($exam->display_format);

        return view('instructor.exams.preview', [
            'exam' => $exam->load(['course', 'instructor']),
            'questions' => $questions,
            'totalQuestionMarks' => $questions->sum(fn (InstructorExamQuestion $question) => (float) $question->marks),
            'displayFormat' => $displayFormat,
            'formatMeta' => ExamDisplayFormatCatalog::find($displayFormat),
            'displayFormats' => ExamDisplayFormatCatalog::formats(),
        ]);
    }
}
