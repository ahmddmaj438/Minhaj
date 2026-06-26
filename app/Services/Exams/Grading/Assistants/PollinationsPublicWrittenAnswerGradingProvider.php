<?php

namespace App\Services\Exams\Grading\Assistants;

use App\Models\Exam\InstructorExamQuestion;
use App\Models\ExamSessionAnswer;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PollinationsPublicWrittenAnswerGradingProvider implements WrittenAnswerGradingProvider
{
    public function __construct(
        private readonly WrittenAnswerEvaluationPayload $evaluationPayload,
    ) {}

    public function available(): bool
    {
        return (bool) config('services.ai_grading.pollinations.enabled', true);
    }

    public function suggest(ExamSessionAnswer $answer): WrittenAnswerGradingSuggestion
    {
        if (! $this->available()) {
            throw new RuntimeException('Pollinations public provider is disabled.');
        }

        $answer->loadMissing('question.sessionAnswers', 'session.assignment.exam.course', 'session.student.user');
        $question = $answer->question;

        if (! $question instanceof InstructorExamQuestion) {
            throw new RuntimeException('The answer does not have a question to grade.');
        }

        $maxScore = (float) $question->marks;
        [$response, $usedInsecureTransport, $model, $attempts] = $this->requestWithFallbacks(
            $this->prompt($this->evaluationPayload->make($answer))
        );

        if ($response->failed()) {
            throw new RuntimeException($this->failureMessage($response, $attempts));
        }

        $data = $this->decodeJson($response->body());

        if (! is_array($data)) {
            throw new RuntimeException('The AI service returned a response that could not be read.');
        }

        if (! array_key_exists('suggested_score', $data) || ! is_numeric($data['suggested_score'])) {
            throw new RuntimeException('The AI service returned a suggestion without a usable score.');
        }

        return new WrittenAnswerGradingSuggestion(
            suggestedScore: min(max((float) ($data['suggested_score'] ?? 0), 0), $maxScore),
            maxScore: $maxScore,
            confidence: min(max((float) ($data['confidence'] ?? 0.45), 0), 1),
            feedback: trim((string) ($data['feedback'] ?? 'Review suggested score before saving.')),
            strengths: $this->stringList($data['strengths'] ?? []),
            improvements: $this->stringList($data['improvements'] ?? []),
            provider: 'pollinations_public:'.$model,
            rationale: trim((string) ($data['rationale'] ?? 'Suggested from the question guidance and student answer evidence.')),
            providerNote: $this->providerNote($usedInsecureTransport, $attempts),
            rubricAssessment: $this->rubricAssessmentList($data['rubric_assessment'] ?? []),
        );
    }

    private function prompt(array $evaluation): string
    {
        return json_encode([
            'instruction' => 'Return only minified valid JSON. Evaluate this exam answer from the JSON request. Score by content correctness, rubric, expected answer, question difficulty, and submitted evidence. Do not grade from answer length.',
            'hard_rules' => [
                'Use the question max_score scale.',
                'Never exceed max_score.',
                'Blank, unrelated, or unsupported answers should receive 0 or a very low score.',
                'The instructor makes the final decision.',
            ],
            'required_response_shape' => [
                'suggested_score' => 'number between 0 and question.max_score',
                'confidence' => 'number between 0 and 1',
                'feedback' => 'short instructor-facing feedback',
                'rationale' => 'why this score fits the answer based on rubric evidence',
                'strengths' => ['content evidence that supports the answer'],
                'improvements' => ['missing or weak requirements'],
                'rubric_assessment' => [
                    [
                        'criterion' => 'rubric item or inferred requirement',
                        'score' => 'number',
                        'max_score' => 'number',
                        'evidence' => 'student answer evidence',
                        'notes' => 'brief grading note',
                    ],
                ],
            ],
            'evaluation_request' => $this->compactEvaluation($evaluation),
        ], JSON_UNESCAPED_SLASHES);
    }

    private function compactEvaluation(array $evaluation): array
    {
        $question = data_get($evaluation, 'question', []);
        $rubric = data_get($evaluation, 'rubric_and_expected_answer', []);
        $studentSubmission = data_get($evaluation, 'student_submission', []);

        return [
            'schema_version' => 'minhaj.written_answer_evaluation.compact.v1',
            'scoring_policy' => [
                'max_score' => (float) data_get($evaluation, 'scoring_policy.max_score', data_get($question, 'max_score', 0)),
                'score_scale' => 'question_marks',
                'grade_on_content_correctness' => true,
                'grade_against_rubric_and_expected_answer' => true,
                'do_not_use_length_as_primary_score' => true,
            ],
            'question' => $this->filled([
                'type' => data_get($question, 'type'),
                'category' => data_get($question, 'category'),
                'answer_format' => data_get($question, 'answer_format'),
                'title' => $this->shortText(data_get($question, 'title'), 160),
                'text' => $this->shortText(data_get($question, 'text'), 700),
                'instructions' => $this->shortText(data_get($question, 'instructions'), 500),
                'difficulty' => data_get($question, 'difficulty'),
                'topic' => $this->shortText(data_get($question, 'topic'), 160),
                'programming_language' => data_get($question, 'programming_language'),
                'max_score' => (float) data_get($question, 'max_score', 0),
            ]),
            'rubric_and_expected_answer' => $this->filled([
                'rubric' => $this->shortText(data_get($rubric, 'rubric'), 900),
                'criteria' => $this->shortText(data_get($rubric, 'criteria'), 900),
                'expected_answer' => $this->shortText(data_get($rubric, 'expected_answer'), 900),
                'expected_tasks' => $this->shortText(data_get($rubric, 'expected_tasks'), 700),
                'accepted_blanks' => $this->shortText(data_get($rubric, 'accepted_blanks'), 700),
                'matching_pairs' => $this->shortText(data_get($rubric, 'matching_pairs'), 700),
                'correct_true_false_answer' => data_get($rubric, 'correct_true_false_answer'),
            ]),
            'student_submission' => $this->filled([
                'answer' => $this->shortText(data_get($studentSubmission, 'normalized_text'), 1200),
                'current_score' => data_get($studentSubmission, 'current_score'),
                'current_feedback' => $this->shortText(data_get($studentSubmission, 'current_feedback'), 500),
            ]),
        ];
    }

    /**
     * @return array{0: Response, 1: bool, 2: string, 3: list<string>}
     */
    private function requestWithFallbacks(string $prompt): array
    {
        $startedAt = microtime(true);
        $attempts = [];
        $models = $this->models();
        $lastResponse = null;
        $usedInsecureTransport = false;

        foreach ($models as $modelIndex => $model) {
            for ($attempt = 1; $attempt <= $this->maxAttempts(); $attempt++) {
                if (! $this->hasEnoughTimeForAnotherAttempt($startedAt)) {
                    $attempts[] = 'Stopped before another Pollinations attempt to avoid PHP maximum execution time.';
                    break 2;
                }

                [$response, $usedInsecure] = $this->request($prompt, $model);
                $usedInsecureTransport = $usedInsecureTransport || $usedInsecure;
                $lastResponse = $response;
                $attempts[] = $model.' attempt '.$attempt.' returned HTTP '.$response->status();

                if (! $response->tooManyRequests()) {
                    return [$response, $usedInsecureTransport, $model, $attempts];
                }

                if ($attempt < $this->maxAttempts()) {
                    $delay = $this->retryDelaySeconds($attempt, $startedAt);

                    if ($delay > 0) {
                        sleep($delay);
                    }
                }
            }

            if ($modelIndex < count($models) - 1 && $this->hasEnoughTimeForAnotherAttempt($startedAt)) {
                sleep(1);
            }
        }

        if (! $lastResponse instanceof Response) {
            throw new RuntimeException('The AI service could not be reached.');
        }

        return [$lastResponse, $usedInsecureTransport, end($models) ?: $this->model(), $attempts];
    }

    /**
     * @return array{0: Response, 1: bool}
     */
    private function request(string $prompt, string $model): array
    {
        $url = $this->endpoint().'/'.rawurlencode($prompt);
        $timeout = $this->requestTimeoutSeconds();
        $canDisableVerification = (bool) config('services.ai_grading.pollinations.ssl_retry_without_verification', true);
        $request = Http::timeout($timeout);
        $query = [
            'model' => $model,
            'json' => 'true',
            'temperature' => '0.2',
        ];

        if ($canDisableVerification) {
            return [
                $request->withoutVerifying()->get($url, $query),
                true,
            ];
        }

        return [
            $request->get($url, $query),
            false,
        ];
    }

    private function failureMessage(Response $response, array $attempts): string
    {
        $message = 'The AI service is currently unavailable. Please try again later.';

        if ($response->tooManyRequests()) {
            $message = 'The AI service is busy right now. Please wait a minute and try again.';
        }

        return $message;
    }

    private function providerNote(bool $usedInsecureTransport, array $attempts): string
    {
        $note = 'Generated by the available AI fallback. Please review the suggestion before saving the final mark.';

        if ($usedInsecureTransport) {
            $note .= ' Your system used a backup connection method for this request.';
        }

        if (count($attempts) > 1) {
            $note .= ' The request needed more than one attempt before it succeeded.';
        }

        return $note;
    }

    private function endpoint(): string
    {
        return rtrim((string) config('services.ai_grading.pollinations.endpoint', 'https://text.pollinations.ai'), '/');
    }

    private function model(): string
    {
        return (string) config('services.ai_grading.pollinations.model', 'openai');
    }

    /**
     * @return list<string>
     */
    private function models(): array
    {
        $configured = config('services.ai_grading.pollinations.models');
        $models = is_array($configured)
            ? $configured
            : explode(',', (string) $configured);

        $models = collect([$this->model(), ...$models])
            ->map(fn ($model): string => trim((string) $model))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $models !== [] ? $models : ['openai'];
    }

    private function maxAttempts(): int
    {
        return max(1, (int) config('services.ai_grading.pollinations.max_attempts', 2));
    }

    private function retryDelaySeconds(int $attempt, float $startedAt): int
    {
        $delay = (int) config('services.ai_grading.pollinations.retry_delay_seconds', 1);
        $remaining = $this->runtimeBudgetSeconds() - (int) ceil(microtime(true) - $startedAt);

        return min(max(0, $delay * $attempt), max(0, $remaining - $this->minimumAttemptWindowSeconds()));
    }

    private function requestTimeoutSeconds(): int
    {
        $configured = (int) config('services.ai_grading.pollinations.timeout', 18);

        return min(max(1, $configured), $this->runtimeBudgetSeconds() - 4);
    }

    private function runtimeBudgetSeconds(): int
    {
        $configured = (int) config('services.ai_grading.pollinations.runtime_budget_seconds', 26);
        $phpLimit = (int) ini_get('max_execution_time');

        if ($phpLimit > 0) {
            return min($configured, max(5, $phpLimit - 4));
        }

        return max(5, $configured);
    }

    private function minimumAttemptWindowSeconds(): int
    {
        return min(8, max(3, $this->requestTimeoutSeconds()));
    }

    private function hasEnoughTimeForAnotherAttempt(float $startedAt): bool
    {
        return (microtime(true) - $startedAt) < ($this->runtimeBudgetSeconds() - $this->minimumAttemptWindowSeconds());
    }

    private function decodeJson(string $body): ?array
    {
        $data = json_decode($body, true);

        if (is_array($data)) {
            return $data;
        }

        if (preg_match('/```json\s*(.*?)\s*```/is', $body, $matches) === 1) {
            $data = json_decode($matches[1], true);

            return is_array($data) ? $data : null;
        }

        if (preg_match('/\{.*\}/s', $body, $matches) === 1) {
            $data = json_decode($matches[0], true);

            return is_array($data) ? $data : null;
        }

        return null;
    }

    private function filled(array $items): array
    {
        return collect($items)
            ->reject(fn ($value): bool => $value === null || $value === '' || $value === [])
            ->all();
    }

    private function shortText(mixed $value, int $limit): ?string
    {
        if (is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_SLASHES);
        }

        $text = trim((string) ($value ?? ''));

        if ($text === '') {
            return null;
        }

        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;

        if (mb_strlen($text) <= $limit) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $limit - 3)).'...';
    }

    private function stringList(mixed $value): array
    {
        return collect(is_array($value) ? $value : [])
            ->map(fn ($item): string => trim((string) $item))
            ->filter()
            ->values()
            ->all();
    }

    private function rubricAssessmentList(mixed $value): array
    {
        return collect(is_array($value) ? $value : [])
            ->filter(fn ($item): bool => is_array($item))
            ->map(fn (array $item): array => [
                'criterion' => trim((string) ($item['criterion'] ?? 'Criterion')),
                'score' => (float) ($item['score'] ?? 0),
                'max_score' => (float) ($item['max_score'] ?? 0),
                'evidence' => trim((string) ($item['evidence'] ?? '')),
                'notes' => trim((string) ($item['notes'] ?? '')),
            ])
            ->values()
            ->all();
    }
}
