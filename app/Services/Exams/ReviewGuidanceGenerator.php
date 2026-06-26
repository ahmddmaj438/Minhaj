<?php

namespace App\Services\Exams;

use App\Models\Exam\InstructorExamQuestion;
use Illuminate\Support\Str;

class ReviewGuidanceGenerator
{
    public function draft(InstructorExamQuestion $question): array
    {
        $questionText = trim((string) data_get($question->prompt, 'question_text', $question->title));
        $marks = (float) $question->marks;
        $topic = $question->topic ?: 'the topic';

        $points = collect(preg_split('/[.?!\n]+/', $questionText) ?: [])
            ->map(fn (string $part): string => trim($part))
            ->filter()
            ->take(4)
            ->values();

        if ($points->isEmpty()) {
            $points = collect([
                'Answer directly addresses the question.',
                'Uses accurate terminology for '.$topic.'.',
                'Includes a clear explanation or example.',
            ]);
        }

        $criterionMarks = max(round($marks / max($points->count(), 1), 2), 0.25);

        return [
            'expected_answer' => 'A strong answer should directly answer the question, explain the important ideas in clear language, and include a relevant example when appropriate.',
            'rubric' => $points
                ->map(fn (string $point, int $index): string => ($index + 1).'. '.$point.' - about '.$criterionMarks.' marks')
                ->implode("\n"),
            'key_points' => $points->implode("\n"),
            'mark_distribution' => 'Distribute the '.$marks.' marks across correctness, completeness, examples/evidence, and clarity. Do not award marks only for answer length.',
            'common_mistakes' => 'Common issues: vague explanation, missing required terms, unrelated examples, or statements that do not answer the question.',
            'evaluation_instructions' => 'Use this guidance as a draft. Review the student answer against the approved model answer, key points, and rubric. The teacher must confirm or adjust the final mark.',
            'review_guidance_status' => 'draft',
            'review_guidance_source' => 'local_guidance_assistant',
            'review_guidance_generated_at' => now()->toISOString(),
        ];
    }
}
