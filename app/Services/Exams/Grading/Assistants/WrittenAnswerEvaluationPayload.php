<?php

namespace App\Services\Exams\Grading\Assistants;

use App\Models\Exam\InstructorExamQuestion;
use App\Models\ExamSessionAnswer;

class WrittenAnswerEvaluationPayload
{
    public function __construct(
        private readonly WrittenAnswerSupport $support,
    ) {}

    public function make(ExamSessionAnswer $answer): array
    {
        $answer->loadMissing('question', 'session.assignment.exam.course', 'session.student.user');
        $question = $answer->question;

        if (! $question instanceof InstructorExamQuestion) {
            return [];
        }

        $settings = $question->settings ?? [];
        $prompt = $question->prompt ?? [];
        $value = $answer->answer_payload['value'] ?? [];

        return [
            'schema_version' => 'minhaj.written_answer_evaluation.v1',
            'task' => 'Evaluate this student exam answer for instructor review.',
            'scoring_policy' => [
                'score_scale' => 'question_marks',
                'max_score' => (float) $question->marks,
                'score_must_be_between' => [0, (float) $question->marks],
                'grade_on_content_correctness' => true,
                'grade_against_rubric_and_expected_answer' => true,
                'do_not_use_length_as_primary_score' => true,
                'do_not_award_marks_for_words_only' => true,
                'instructor_makes_final_decision' => true,
            ],
            'exam_context' => [
                'exam_title' => $answer->session?->assignment?->exam?->title,
                'course_code' => $answer->session?->assignment?->exam?->course?->code,
                'course_name' => $answer->session?->assignment?->exam?->course?->name,
                'student_name' => $answer->session?->student?->user?->name,
            ],
            'question' => [
                'id' => $question->id,
                'type' => $question->type,
                'category' => $question->category,
                'answer_format' => $this->support->answerFormat($question),
                'title' => $question->title,
                'text' => $this->support->questionText($question),
                'instructions' => $prompt['instructions'] ?? null,
                'difficulty' => $question->difficulty,
                'topic' => $question->topic,
                'programming_language' => $question->programming_language,
                'max_score' => (float) $question->marks,
            ],
            'rubric_and_expected_answer' => [
                'rubric' => $settings['rubric'] ?? null,
                'expected_answer' => $settings['expected_answer'] ?? null,
                'criteria' => $settings['criteria'] ?? null,
                'starter_code' => $settings['starter_code'] ?? null,
                'expected_tasks' => $settings['expected_tasks'] ?? null,
                'accepted_blanks' => $settings['blanks'] ?? null,
                'matching_pairs' => $settings['pairs'] ?? null,
                'correct_true_false_answer' => array_key_exists('correct_answer', $settings)
                    ? (bool) $settings['correct_answer']
                    : null,
            ],
            'student_submission' => [
                'normalized_text' => $this->support->answerText($answer),
                'raw_answer_value' => is_array($value) ? $value : ['value' => $value],
                'current_score' => $answer->score !== null ? (float) $answer->score : null,
                'current_feedback' => $answer->feedback,
            ],
            'required_ai_response' => [
                'suggested_score' => 'number between 0 and question.max_score',
                'confidence' => 'number between 0 and 1',
                'feedback' => 'short instructor-facing feedback',
                'rationale' => 'why this score fits the answer, based on rubric evidence',
                'strengths' => 'list of answer strengths with content evidence',
                'improvements' => 'list of missing or weak requirements',
                'rubric_assessment' => [
                    'criterion' => 'rubric item or inferred requirement',
                    'score' => 'marks earned for this criterion when applicable',
                    'max_score' => 'criterion max marks when applicable',
                    'evidence' => 'student answer evidence used for the judgement',
                    'notes' => 'brief grading note',
                ],
            ],
        ];
    }
}
