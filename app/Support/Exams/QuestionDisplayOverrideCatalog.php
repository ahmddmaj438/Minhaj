<?php

namespace App\Support\Exams;

final class QuestionDisplayOverrideCatalog
{
    public const DEFAULT = 'standard';

    public static function all(): array
    {
        return [
            self::DEFAULT => [
                'label' => 'Use exam default',
                'description' => 'Show this question using the normal exam layout.',
            ],
            'large_text_area' => [
                'label' => 'Large answer area',
                'description' => 'Give the student more room for a written response.',
            ],
            'expanded_essay' => [
                'label' => 'Expanded essay layout',
                'description' => 'Use a wide, distraction-free writing area for long answers.',
            ],
            'matching_focused' => [
                'label' => 'Matching-focused layout',
                'description' => 'Give matching rows more horizontal space and visual separation.',
            ],
            'attachment_focused' => [
                'label' => 'Attachment-focused layout',
                'description' => 'Emphasize files, resources, and task instructions.',
            ],
        ];
    }

    public static function keys(): array
    {
        return array_keys(self::all());
    }
}
