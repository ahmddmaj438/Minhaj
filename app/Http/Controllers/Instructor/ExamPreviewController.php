<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Exam\InstructorExam;
use App\Models\Exam\InstructorExamQuestion;
use Illuminate\View\View;

class ExamPreviewController extends Controller
{
    public function show(InstructorExam $exam): View
    {
        abort_unless(auth()->id() === $exam->instructor_id || auth()->user()?->isSuperAdmin(), 403);

        $questions = $exam->questions()->get();

        return view('instructor.exams.preview', [
            'exam' => $exam->load('course'),
            'questions' => $questions,
            'totalQuestionMarks' => $questions->sum(fn (InstructorExamQuestion $question) => (float) $question->marks),
        ]);
    }
}
