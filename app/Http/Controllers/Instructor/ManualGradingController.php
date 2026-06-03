<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\ExamSession;
use App\Models\ExamSessionAnswer;
use App\Services\Exams\ExamScoringManager;
use App\Services\Exams\Grading\Assistants\WrittenAnswerGradingAssistant;
use App\Services\Exams\Grading\Assistants\WrittenAnswerSupport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ManualGradingController extends Controller
{
    public function index(Request $request, ExamScoringManager $scoringManager): View
    {
        $sessions = ExamSession::query()
            ->with(['assignment.exam.course', 'student.user', 'answers.question'])
            ->where('status', ExamSession::STATUS_SUBMITTED)
            ->whereHas('assignment.exam', function ($query) use ($request): void {
                if (! $request->user()?->isSuperAdmin()) {
                    $query->where('instructor_id', $request->user()?->id);
                }
            })
            ->latest('submitted_at')
            ->get();

        return view('instructor.grading.index', [
            'sessions' => $sessions,
            'manualPendingCount' => fn (ExamSession $session): int => $session->answers
                ->filter(fn (ExamSessionAnswer $answer): bool => $scoringManager->requiresManualGrading($answer->question)
                    && $answer->score === null)
                ->count(),
        ]);
    }

    public function show(
        Request $request,
        ExamSession $session,
        ExamScoringManager $scoringManager,
        WrittenAnswerSupport $writtenAnswerSupport
    ): View {
        $session->load(['assignment.exam.course', 'student.user', 'answers.question']);
        $this->authorizeSession($request, $session);

        return view('instructor.grading.show', [
            'session' => $session,
            'answers' => $session->answers
                ->sortBy(fn (ExamSessionAnswer $answer): int => (int) $answer->question?->position)
                ->values(),
            'canManuallyGrade' => fn (ExamSessionAnswer $answer): bool => $scoringManager->requiresManualGrading($answer->question),
            'canAiAssist' => fn (ExamSessionAnswer $answer): bool => $writtenAnswerSupport->supportsAnswer($answer),
        ]);
    }

    public function updateAnswer(
        Request $request,
        ExamSession $session,
        ExamSessionAnswer $answer,
        ExamScoringManager $scoringManager
    ): RedirectResponse {
        $session->load(['assignment.exam.course', 'student.user']);
        $answer->load('question');
        $this->authorizeSession($request, $session);
        abort_unless($request->user()?->can('button.instructor.grading.answers.save'), 403);
        abort_unless($request->user()?->can('db.exam_session_answers.update'), 403);
        abort_unless($request->user()?->can('db.exam_sessions.update'), 403);
        abort_unless((int) $answer->exam_session_id === (int) $session->id, 404);
        abort_unless($scoringManager->requiresManualGrading($answer->question), 422);

        $data = $request->validate([
            'score' => ['required', 'numeric', 'min:0', 'max:'.(float) $answer->question->marks],
            'feedback' => ['nullable', 'string', 'max:5000'],
        ]);

        $payload = $answer->answer_payload ?? [];
        $payload['status'] = 'manual_graded';
        $payload['graded_at'] = now()->toISOString();
        $payload['graded_by'] = $request->user()?->id;

        $answer->update([
            'score' => $data['score'],
            'feedback' => $data['feedback'] ?? null,
            'answer_payload' => $payload,
        ]);

        $scoringManager->recomputeSession($session);

        return back()->with('status', 'Manual score saved.');
    }

    public function assistAnswer(
        Request $request,
        ExamSession $session,
        ExamSessionAnswer $answer,
        WrittenAnswerGradingAssistant $assistant
    ): RedirectResponse|JsonResponse {
        $session->load(['assignment.exam.course', 'student.user']);
        $answer->load('question');
        $this->authorizeSession($request, $session);
        abort_unless($request->user()?->can('button.instructor.grading.answers.ai_assist'), 403);
        abort_unless($request->user()?->can('db.exam_session_answers.update'), 403);
        abort_unless((int) $answer->exam_session_id === (int) $session->id, 404);
        abort_unless($assistant->supports($answer), 422, 'AI assistance is available only for keyboard-written answers.');

        $payload = $answer->answer_payload ?? [];
        $suggestion = $assistant->suggest($answer)->toArray();
        $payload['ai_grading_suggestion'] = $suggestion;
        $message = $suggestion['suggested_score'] === null
            ? 'No AI score was generated. Configure a working AI provider to evaluate this answer.'
            : 'Written-answer grading suggestion generated.';

        $answer->update(['answer_payload' => $payload]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'answer_id' => $answer->id,
                'question_id' => $answer->instructor_exam_question_id,
                'suggestion' => $suggestion,
            ]);
        }

        return back()->with('status', $suggestion['suggested_score'] === null
            ? $message
            : $message.' Review it before saving the score.');
    }

    public function assistEssay(
        Request $request,
        ExamSession $session,
        ExamSessionAnswer $answer,
        WrittenAnswerGradingAssistant $assistant
    ): RedirectResponse|JsonResponse {
        return $this->assistAnswer($request, $session, $answer, $assistant);
    }

    public function storeClientAssistSuggestion(
        Request $request,
        ExamSession $session,
        ExamSessionAnswer $answer,
        WrittenAnswerGradingAssistant $assistant
    ): JsonResponse {
        $session->load(['assignment.exam.course', 'student.user']);
        $answer->load('question');
        $this->authorizeSession($request, $session);
        abort_unless($request->user()?->can('button.instructor.grading.answers.ai_assist'), 403);
        abort_unless($request->user()?->can('db.exam_session_answers.update'), 403);
        abort_unless((int) $answer->exam_session_id === (int) $session->id, 404);
        abort_unless($assistant->supports($answer), 422, 'AI assistance is available only for keyboard-written answers.');

        $data = $request->validate([
            'suggested_score' => ['required', 'numeric', 'min:0', 'max:'.(float) $answer->question->marks],
            'max_score' => ['required', 'numeric', 'min:0'],
            'confidence' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'feedback' => ['required', 'string', 'max:5000'],
            'rationale' => ['nullable', 'string', 'max:5000'],
            'strengths' => ['nullable', 'array'],
            'strengths.*' => ['string', 'max:1000'],
            'improvements' => ['nullable', 'array'],
            'improvements.*' => ['string', 'max:1000'],
            'rubric_assessment' => ['nullable', 'array'],
            'rubric_assessment.*.criterion' => ['nullable', 'string', 'max:500'],
            'rubric_assessment.*.score' => ['nullable', 'numeric', 'min:0'],
            'rubric_assessment.*.max_score' => ['nullable', 'numeric', 'min:0'],
            'rubric_assessment.*.evidence' => ['nullable', 'string', 'max:1000'],
            'rubric_assessment.*.notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $suggestion = [
            'suggested_score' => min(max((float) $data['suggested_score'], 0), (float) $answer->question->marks),
            'max_score' => (float) $answer->question->marks,
            'confidence' => min(max((float) ($data['confidence'] ?? 0.5), 0), 1),
            'feedback' => trim($data['feedback']),
            'strengths' => array_values(array_filter($data['strengths'] ?? [])),
            'improvements' => array_values(array_filter($data['improvements'] ?? [])),
            'provider' => 'puter_browser:gpt-5-nano',
            'rationale' => trim((string) ($data['rationale'] ?? 'Generated by Puter AI browser fallback after backend providers were unavailable.')),
            'provider_note' => 'Generated by Puter AI from the instructor browser because Gemini/Groq keys were not configured and the public Pollinations endpoint was unavailable.',
            'provider_error' => null,
            'rubric_assessment' => collect($data['rubric_assessment'] ?? [])
                ->filter(fn ($item): bool => is_array($item))
                ->map(fn (array $item): array => [
                    'criterion' => trim((string) ($item['criterion'] ?? 'Criterion')),
                    'score' => (float) ($item['score'] ?? 0),
                    'max_score' => (float) ($item['max_score'] ?? 0),
                    'evidence' => trim((string) ($item['evidence'] ?? '')),
                    'notes' => trim((string) ($item['notes'] ?? '')),
                ])
                ->values()
                ->all(),
            'assist_scope' => 'written_answer',
            'generated_at' => now()->toISOString(),
        ];

        $payload = $answer->answer_payload ?? [];
        $payload['ai_grading_suggestion'] = $suggestion;
        $answer->update(['answer_payload' => $payload]);

        return response()->json([
            'message' => 'Browser AI grading suggestion generated.',
            'answer_id' => $answer->id,
            'question_id' => $answer->instructor_exam_question_id,
            'suggestion' => $suggestion,
        ]);
    }

    private function authorizeSession(Request $request, ExamSession $session): void
    {
        abort_unless(
            $request->user()?->isSuperAdmin()
                || (int) $session->assignment->exam->instructor_id === (int) $request->user()?->id,
            403
        );
    }
}
