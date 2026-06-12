<?php

namespace App\Services\TCExam;

use App\Models\Course;
use App\Models\Exam\InstructorExam;
use App\Models\Exam\InstructorExamQuestion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class TCExamBuilderSync
{
    public function syncExam(InstructorExam $exam): void
    {
        if (! $this->builderLinkColumnsReady() || ! $this->hasTables(['tce_tests'])) {
            return;
        }

        $payload = [
            'test_name' => $this->uniqueTestName($exam),
            'test_description' => $this->examDescription($exam),
            'test_begin_time' => $exam->starts_at?->format('Y-m-d H:i:s'),
            'test_end_time' => $exam->ends_at?->format('Y-m-d H:i:s'),
            'test_duration_time' => (int) $exam->duration_minutes,
            'test_max_score' => (float) $exam->total_marks,
            'test_user_id' => (int) $exam->instructor_id,
            'test_random_questions_select' => false,
            'test_random_questions_order' => false,
            'test_random_answers_select' => false,
            'test_random_answers_order' => false,
        ];

        if ($exam->tcexam_test_id && DB::table('tce_tests')->where('test_id', $exam->tcexam_test_id)->exists()) {
            DB::table('tce_tests')->where('test_id', $exam->tcexam_test_id)->update($payload);
            $this->rebuildTestSubjectSets($exam->fresh('questions'));

            return;
        }

        $testId = DB::table('tce_tests')->insertGetId($payload);
        $exam->forceFill(['tcexam_test_id' => $testId])->saveQuietly();
        $this->rebuildTestSubjectSets($exam->fresh('questions'));
    }

    public function deleteExam(InstructorExam $exam): void
    {
        if (! $exam->tcexam_test_id || ! $this->hasTables(['tce_tests'])) {
            return;
        }

        $this->deleteTestSubjectSets((int) $exam->tcexam_test_id);
        DB::table('tce_tests')->where('test_id', $exam->tcexam_test_id)->delete();
    }

    public function syncQuestion(InstructorExamQuestion $question): void
    {
        if (! $this->builderLinkColumnsReady() || ! $this->hasTables(['tce_modules', 'tce_subjects', 'tce_questions', 'tce_answers'])) {
            return;
        }

        $question->loadMissing('exam.course');
        $this->syncExam($question->exam);

        $subjectId = $this->ensureSubject($question);
        $payload = [
            'question_subject_id' => $subjectId,
            'question_description' => $this->questionText($question),
            'question_explanation' => $this->questionExplanation($question),
            'question_type' => $this->questionType($question),
            'question_difficulty' => $this->difficulty($question),
            'question_enabled' => ($question->prompt['status'] ?? null) === 'configured',
            'question_position' => (int) $question->position,
            'question_timer' => null,
            'question_fullscreen' => $question->category === 'coding',
            'question_inline_answers' => in_array($question->type, ['mcq', 'true_false', 'true_false_correct'], true),
            'question_auto_next' => false,
        ];

        if ($question->tcexam_question_id && DB::table('tce_questions')->where('question_id', $question->tcexam_question_id)->exists()) {
            DB::table('tce_questions')->where('question_id', $question->tcexam_question_id)->update($payload);
            $questionId = (int) $question->tcexam_question_id;
        } else {
            $questionId = DB::table('tce_questions')->insertGetId($payload);
            $question->forceFill([
                'tcexam_question_id' => $questionId,
                'tcexam_subject_id' => $subjectId,
            ])->saveQuietly();
        }

        if ((int) $question->tcexam_subject_id !== $subjectId) {
            $question->forceFill(['tcexam_subject_id' => $subjectId])->saveQuietly();
        }

        $this->syncAnswers($question, $questionId);
        $this->rebuildTestSubjectSets($question->exam->fresh('questions'));
    }

    public function deleteQuestion(InstructorExamQuestion $question): void
    {
        if (! $question->tcexam_question_id || ! $this->hasTables(['tce_questions', 'tce_answers'])) {
            return;
        }

        DB::table('tce_answers')->where('answer_question_id', $question->tcexam_question_id)->delete();
        DB::table('tce_questions')->where('question_id', $question->tcexam_question_id)->delete();

        if ($question->exam) {
            $this->rebuildTestSubjectSets($question->exam->fresh('questions'));
        }
    }

    private function ensureSubject(InstructorExamQuestion $question): int
    {
        $course = $question->exam?->course;
        $moduleId = $this->ensureModule($course);
        $topic = trim((string) $question->topic);
        $subjectName = $topic !== '' ? $topic : 'General';

        $existing = DB::table('tce_subjects')
            ->where('subject_module_id', $moduleId)
            ->where('subject_name', $subjectName)
            ->value('subject_id');

        if ($existing) {
            return (int) $existing;
        }

        return DB::table('tce_subjects')->insertGetId([
            'subject_module_id' => $moduleId,
            'subject_name' => $subjectName,
            'subject_description' => $course ? "MINHAJ course: {$course->code} - {$course->name}" : 'MINHAJ Exam Builder subject.',
            'subject_enabled' => true,
            'subject_user_id' => (int) ($question->exam?->instructor_id ?? 1),
        ]);
    }

    private function ensureModule(?Course $course): int
    {
        $moduleName = $course
            ? trim($course->code.' - '.$course->name)
            : 'MINHAJ Exam Builder';

        $existing = DB::table('tce_modules')->where('module_name', $moduleName)->value('module_id');

        if ($existing) {
            return (int) $existing;
        }

        return DB::table('tce_modules')->insertGetId([
            'module_name' => $moduleName,
            'module_enabled' => true,
            'module_user_id' => 1,
        ]);
    }

    private function syncAnswers(InstructorExamQuestion $question, int $questionId): void
    {
        DB::table('tce_answers')->where('answer_question_id', $questionId)->delete();

        $answers = $this->answers($question);
        foreach ($answers as $index => $answer) {
            DB::table('tce_answers')->insert([
                'answer_question_id' => $questionId,
                'answer_description' => $answer['description'],
                'answer_explanation' => $answer['explanation'] ?? null,
                'answer_isright' => (bool) ($answer['is_right'] ?? false),
                'answer_enabled' => true,
                'answer_position' => $index + 1,
                'answer_keyboard_key' => null,
            ]);
        }
    }

    private function answers(InstructorExamQuestion $question): array
    {
        if ($question->type === 'mcq') {
            return collect($question->settings['options'] ?? [])
                ->map(fn (array $option) => [
                    'description' => $option['text'] ?? '',
                    'explanation' => $option['feedback'] ?? null,
                    'is_right' => (bool) ($option['is_correct'] ?? false),
                ])
                ->filter(fn (array $answer) => trim($answer['description']) !== '')
                ->values()
                ->all();
        }

        if (in_array($question->type, ['true_false', 'true_false_correct'], true)) {
            $correct = (bool) ($question->settings['correct_answer'] ?? true);

            return [
                ['description' => 'True', 'is_right' => $correct],
                ['description' => 'False', 'is_right' => ! $correct],
            ];
        }

        if ($question->type === 'matching') {
            return collect($question->settings['pairs'] ?? [])
                ->map(fn (array $pair) => [
                    'description' => ($pair['left'] ?? '').' => '.($pair['right'] ?? ''),
                    'explanation' => $pair['note'] ?? null,
                    'is_right' => true,
                ])
                ->filter(fn (array $answer) => trim($answer['description']) !== '=>')
                ->values()
                ->all();
        }

        if ($question->type === 'fill_blank') {
            return collect($question->settings['blanks'] ?? [])
                ->flatMap(fn (array $blank) => collect($blank['accepted_answers'] ?? [])->map(fn (string $answer) => [
                    'description' => $answer,
                    'explanation' => $blank['hint'] ?? null,
                    'is_right' => true,
                ]))
                ->filter(fn (array $answer) => trim($answer['description']) !== '')
                ->values()
                ->all();
        }

        return [];
    }

    private function rebuildTestSubjectSets(?InstructorExam $exam): void
    {
        if (! $exam || ! $exam->tcexam_test_id || ! $this->hasTables(['tce_test_subject_set', 'tce_test_subjects'])) {
            return;
        }

        $this->deleteTestSubjectSets((int) $exam->tcexam_test_id);

        $exam->questions()
            ->whereNotNull('tcexam_subject_id')
            ->get()
            ->groupBy(fn (InstructorExamQuestion $question) => implode('|', [
                $question->tcexam_subject_id,
                $this->questionType($question),
                $this->difficulty($question),
            ]))
            ->each(function (Collection $questions) use ($exam): void {
                $first = $questions->first();
                $subsetId = DB::table('tce_test_subject_set')->insertGetId([
                    'tsubset_test_id' => $exam->tcexam_test_id,
                    'tsubset_type' => $this->questionType($first),
                    'tsubset_difficulty' => $this->difficulty($first),
                    'tsubset_quantity' => $questions->count(),
                    'tsubset_answers' => 0,
                ]);

                DB::table('tce_test_subjects')->insert([
                    'subjset_tsubset_id' => $subsetId,
                    'subjset_subject_id' => $first->tcexam_subject_id,
                ]);
            });
    }

    private function deleteTestSubjectSets(int $testId): void
    {
        if (! $this->hasTables(['tce_test_subject_set', 'tce_test_subjects'])) {
            return;
        }

        $subsetIds = DB::table('tce_test_subject_set')
            ->where('tsubset_test_id', $testId)
            ->pluck('tsubset_id');

        if ($subsetIds->isNotEmpty()) {
            DB::table('tce_test_subjects')->whereIn('subjset_tsubset_id', $subsetIds)->delete();
        }

        DB::table('tce_test_subject_set')->where('tsubset_test_id', $testId)->delete();
    }

    private function uniqueTestName(InstructorExam $exam): string
    {
        $base = trim($exam->title);
        $currentId = $exam->tcexam_test_id;
        $exists = DB::table('tce_tests')
            ->where('test_name', $base)
            ->when($currentId, fn ($query) => $query->where('test_id', '!=', $currentId))
            ->exists();

        return $exists ? Str::limit($base, 220, '')." (MINHAJ {$exam->id})" : $base;
    }

    private function questionText(InstructorExamQuestion $question): string
    {
        $prompt = $question->prompt ?? [];

        return trim((string) (
            $prompt['question_text']
            ?? $prompt['statement']
            ?? $question->title
            ?? $question->type
        ));
    }

    private function questionExplanation(InstructorExamQuestion $question): string
    {
        return json_encode([
            'source' => 'minhaj_exam_builder',
            'builder_question_id' => $question->id,
            'type' => $question->type,
            'category' => $question->category,
            'marks' => (float) $question->marks,
            'difficulty' => $question->difficulty,
            'programming_language' => $question->programming_language,
            'display_override' => $question->display_override ?? 'standard',
            'prompt' => $question->prompt,
            'settings' => $question->settings,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function examDescription(InstructorExam $exam): string
    {
        $description = $exam->description ?: 'Created from MINHAJ Exam Builder.';
        $format = $exam->display_format ?? InstructorExam::FORMAT_ONE_QUESTION_AT_TIME;

        return $description."\n\nMINHAJ display format: ".$format;
    }

    private function questionType(InstructorExamQuestion $question): int
    {
        return match ($question->type) {
            'mcq', 'true_false', 'true_false_correct', 'matching' => 1,
            'fill_blank' => 2,
            default => 3,
        };
    }

    private function difficulty(InstructorExamQuestion $question): int
    {
        return match ($question->difficulty) {
            'easy' => 1,
            'hard' => 10,
            default => 5,
        };
    }

    private function hasTables(array $tables): bool
    {
        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }

    private function builderLinkColumnsReady(): bool
    {
        return Schema::hasColumn('instructor_exams', 'tcexam_test_id')
            && Schema::hasColumn('instructor_exam_questions', 'tcexam_question_id')
            && Schema::hasColumn('instructor_exam_questions', 'tcexam_subject_id');
    }
}
