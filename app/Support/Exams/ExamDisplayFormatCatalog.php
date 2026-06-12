<?php

namespace App\Support\Exams;

class ExamDisplayFormatCatalog
{
    public const ONE_QUESTION_AT_TIME = 'one_question_at_time';
    public const ALL_QUESTIONS = 'all_questions';
    public const GOOGLE_FORMS = 'google_forms';

    public static function keys(): array
    {
        return array_keys(self::formats());
    }

    public static function formats(): array
    {
        return [
            self::ONE_QUESTION_AT_TIME => [
                'title' => 'One Question at a Time',
                'summary' => 'Students focus on one question, move next or previous, and can flag items to review.',
                'best_for' => 'Timed exams and focused problem solving',
                'features' => ['Next and previous buttons', 'Question navigator', 'Timer and progress'],
                'preview' => 'single',
            ],
            self::ALL_QUESTIONS => [
                'title' => 'All Questions on One Page',
                'summary' => 'Students see the whole exam in one scrollable page and review answers before submitting.',
                'best_for' => 'Short exams and mixed question sets',
                'features' => ['Scrollable exam page', 'Quick question links', 'Review before submission'],
                'preview' => 'all',
            ],
            self::GOOGLE_FORMS => [
                'title' => 'Google Forms Style',
                'summary' => 'Students answer questions in clean vertical sections with clear section progress.',
                'best_for' => 'Academic quizzes with topic sections',
                'features' => ['Grouped sections', 'Clean vertical layout', 'Section progress'],
                'preview' => 'forms',
            ],
        ];
    }
}
