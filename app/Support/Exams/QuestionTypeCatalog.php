<?php

namespace App\Support\Exams;

use Illuminate\Support\Collection;

class QuestionTypeCatalog
{
    public static function categories(): array
    {
        return [
            [
                'key' => 'objective',
                'label' => 'Objective Questions',
                'description' => 'Structured questions with clear answer choices or pairings.',
                'accent' => 'orange',
                'types' => [
                    [
                        'key' => 'mcq',
                        'label' => 'Multiple Choice',
                        'short_label' => 'MCQ',
                        'description' => 'One prompt with selectable answer options.',
                        'builder_phase' => 'Phase 3',
                    ],
                    [
                        'key' => 'true_false',
                        'label' => 'True / False',
                        'short_label' => 'T/F',
                        'description' => 'A statement marked as true or false.',
                        'builder_phase' => 'Phase 4',
                    ],
                    [
                        'key' => 'true_false_correct',
                        'label' => 'True / False + Correction',
                        'short_label' => 'T/F+',
                        'description' => 'False answers require a corrected statement.',
                        'builder_phase' => 'Phase 4',
                    ],
                    [
                        'key' => 'matching',
                        'label' => 'Matching',
                        'short_label' => 'Match',
                        'description' => 'Pair terms, definitions, commands, or concepts.',
                        'builder_phase' => 'Phase 5',
                    ],
                ],
            ],
            [
                'key' => 'text',
                'label' => 'Text-Based Questions',
                'description' => 'Written answers for definitions, explanations, and short responses.',
                'accent' => 'slate',
                'types' => [
                    [
                        'key' => 'fill_blank',
                        'label' => 'Fill in the Blank',
                        'short_label' => 'Blank',
                        'description' => 'A sentence or paragraph with missing terms.',
                        'builder_phase' => 'Phase 6',
                    ],
                    [
                        'key' => 'essay',
                        'label' => 'Direct Question / Essay / Short Answer',
                        'short_label' => 'Essay',
                        'description' => 'Open response with instructor-defined guidance.',
                        'builder_phase' => 'Phase 7',
                    ],
                ],
            ],
            [
                'key' => 'coding',
                'label' => 'Coding & Technical Questions',
                'description' => 'Programming and database tasks prepared for future auto-grading.',
                'accent' => 'navy',
                'types' => [
                    [
                        'key' => 'sql',
                        'label' => 'SQL Query',
                        'short_label' => 'SQL',
                        'description' => 'Query writing with schema, expected output, and constraints.',
                        'language' => 'SQL',
                        'builder_phase' => 'Phase 8',
                    ],
                    [
                        'key' => 'plsql',
                        'label' => 'PL/SQL',
                        'short_label' => 'PL/SQL',
                        'description' => 'Procedures, functions, triggers, and database programming.',
                        'language' => 'PL/SQL',
                        'builder_phase' => 'Phase 8',
                    ],
                    [
                        'key' => 'cpp',
                        'label' => 'C++ Coding',
                        'short_label' => 'C++',
                        'description' => 'Algorithm or programming tasks with starter code.',
                        'language' => 'C++',
                        'builder_phase' => 'Phase 8',
                    ],
                    [
                        'key' => 'java',
                        'label' => 'Java Coding',
                        'short_label' => 'Java',
                        'description' => 'Class, method, or console programming tasks.',
                        'language' => 'Java',
                        'builder_phase' => 'Phase 8',
                    ],
                    [
                        'key' => 'java_mobile',
                        'label' => 'Java Mobile Application',
                        'short_label' => 'Mobile',
                        'description' => 'Android-style Java tasks with app requirements.',
                        'language' => 'Java',
                        'builder_phase' => 'Phase 8',
                    ],
                    [
                        'key' => 'web_html',
                        'label' => 'Web Coding: HTML',
                        'short_label' => 'HTML',
                        'description' => 'Markup structure tasks with required page elements.',
                        'language' => 'HTML',
                        'builder_phase' => 'Phase 8',
                    ],
                    [
                        'key' => 'web_css',
                        'label' => 'Web Coding: CSS',
                        'short_label' => 'CSS',
                        'description' => 'Styling tasks with layout and visual constraints.',
                        'language' => 'CSS',
                        'builder_phase' => 'Phase 8',
                    ],
                    [
                        'key' => 'web_js',
                        'label' => 'Web Coding: JavaScript',
                        'short_label' => 'JS',
                        'description' => 'Browser logic, DOM, and interaction tasks.',
                        'language' => 'JavaScript',
                        'builder_phase' => 'Phase 8',
                    ],
                    [
                        'key' => 'web_php',
                        'label' => 'Web Coding: PHP',
                        'short_label' => 'PHP',
                        'description' => 'Server-side scripting tasks and Laravel-ready logic.',
                        'language' => 'PHP',
                        'builder_phase' => 'Phase 8',
                    ],
                ],
            ],
            [
                'key' => 'networking',
                'label' => 'Networking / Packet Tracer Questions',
                'description' => 'Network scenarios with files, topology images, and configuration tasks.',
                'accent' => 'emerald',
                'types' => [
                    [
                        'key' => 'packet_tracer',
                        'label' => 'Packet Tracer Scenario',
                        'short_label' => 'PKT',
                        'description' => 'Upload topology resources and define network tasks.',
                        'builder_phase' => 'Phase 9',
                    ],
                ],
            ],
        ];
    }

    public static function find(string $key): ?array
    {
        return self::types()->firstWhere('key', $key);
    }

    public static function keys(): array
    {
        return self::types()->pluck('key')->all();
    }

    public static function types(): Collection
    {
        return collect(self::categories())
            ->flatMap(fn (array $category) => collect($category['types'])->map(fn (array $type) => [
                ...$type,
                'category' => $category['key'],
                'category_label' => $category['label'],
                'accent' => $category['accent'],
            ]))
            ->values();
    }
}
