<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\UpdatePacketTracerQuestionRequest;
use App\Models\Exam\InstructorExam;
use App\Models\Exam\InstructorExamQuestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PacketTracerQuestionController extends Controller
{
    public function edit(InstructorExam $exam, InstructorExamQuestion $question): View
    {
        $this->authorizePacketTracerQuestion($exam, $question);

        return view('instructor.exams.questions.packet-tracer', [
            'exam' => $exam->load('course'),
            'question' => $question,
        ]);
    }

    public function update(UpdatePacketTracerQuestionRequest $request, InstructorExam $exam, InstructorExamQuestion $question): RedirectResponse
    {
        abort_unless($request->user()?->can('button.instructor.exams.questions.packet_tracer.save'), 403);
        abort_unless($request->user()?->can('db.instructor_exam_questions.update'), 403);

        $this->authorizePacketTracerQuestion($exam, $question);

        $validated = $request->validated();
        $intent = $validated['intent'];
        $settings = $question->settings ?? [];

        if ($request->hasFile('pkt_file')) {
            $settings['pkt_file'] = $this->storeResource($request->file('pkt_file'), $exam, $question, 'pkt');
        }

        if ($request->hasFile('topology_screenshot')) {
            $settings['topology_screenshot'] = $this->storeResource($request->file('topology_screenshot'), $exam, $question, 'topology');
        }

        $question->update([
            'title' => str($validated['scenario'])->limit(80)->toString(),
            'marks' => $validated['marks'],
            'difficulty' => $validated['difficulty'] ?? null,
            'topic' => $validated['topic'] ?? null,
            'save_to_bank' => $request->boolean('save_to_bank'),
            'prompt' => [
                'status' => 'configured',
                'question_text' => $validated['scenario'],
                'instructions' => $validated['instructions'] ?? null,
            ],
            'settings' => [
                ...$settings,
                'builder_phase' => 'Phase 9',
                'expected_tasks' => $validated['expected_tasks'],
                'configuration_notes' => $validated['configuration_notes'] ?? null,
            ],
        ]);

        if ($intent === 'save_add_another') {
            $newQuestion = InstructorExamQuestion::create([
                'instructor_exam_id' => $exam->id,
                'type' => 'packet_tracer',
                'category' => 'networking',
                'title' => 'Packet Tracer Scenario',
                'position' => $exam->questions()->max('position') + 1,
                'marks' => 1,
                'prompt' => [
                    'status' => 'type_selected',
                    'instructions' => null,
                ],
                'settings' => [
                    'builder_phase' => 'Phase 9',
                ],
            ]);

            return redirect()
                ->route('instructor.exams.questions.packet-tracer.edit', [$exam, $newQuestion])
                ->with('status', 'Packet Tracer question saved. A new networking question is ready.');
        }

        return redirect()
            ->route('instructor.exams.questions.packet-tracer.edit', [$exam, $question])
            ->with('status', 'Packet Tracer question saved. You can review it here before moving to the next builder.');
    }

    private function authorizePacketTracerQuestion(InstructorExam $exam, InstructorExamQuestion $question): void
    {
        abort_unless(auth()->id() === $exam->instructor_id || auth()->user()?->isSuperAdmin(), 403);
        abort_unless($question->instructor_exam_id === $exam->id, 404);
        abort_unless($question->type === 'packet_tracer', 404);
    }

    private function storeResource($file, InstructorExam $exam, InstructorExamQuestion $question, string $kind): array
    {
        $path = $file->store("exam-resources/exams/{$exam->id}/questions/{$question->id}", 'local');

        return [
            'kind' => $kind,
            'disk' => 'local',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'uploaded_at' => now()->toISOString(),
        ];
    }
}
