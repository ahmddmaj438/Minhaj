<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-medium text-orange-600">Student portal</p>
                <h2 class="text-2xl font-semibold leading-tight text-slate-950">My Available Exams</h2>
            </div>
            <div class="text-sm text-slate-500">{{ $student->student_number }}</div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    <p class="font-semibold">Please review the exam message.</p>
                    <ul class="mt-2 list-disc space-y-1 ps-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section class="grid gap-5 lg:grid-cols-2">
                @forelse ($examCards as $card)
                    @php
                        $assignment = $card['assignment'];
                        $exam = $assignment->exam;
                        $activeSession = $card['activeSession'];
                        $submittedSession = $card['submittedSession'];
                        $questionsCount = $exam?->questions?->count() ?? 0;
                        $manualPending = (bool) (($submittedSession?->metadata ?? [])['manual_grading_pending'] ?? false);
                    @endphp

                    <article class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="text-sm font-semibold uppercase tracking-wide text-orange-600">
                                    {{ $assignment->course?->code }} - {{ $assignment->course?->name }}
                                </p>
                                <h3 class="mt-2 text-xl font-semibold text-slate-950">{{ $exam?->title }}</h3>
                                <p class="mt-2 text-sm leading-6 text-slate-600">
                                    {{ $exam?->description ?: 'No description provided.' }}
                                </p>
                            </div>
                            <span class="w-fit rounded-full px-3 py-1 text-xs font-semibold {{ $activeSession ? 'bg-emerald-100 text-emerald-700' : ($submittedSession ? 'bg-slate-100 text-slate-700' : 'bg-orange-100 text-orange-700') }}">
                                {{ $activeSession ? 'In progress' : ($submittedSession ? 'Submitted' : 'Available') }}
                            </span>
                        </div>

                        <dl class="mt-5 grid gap-3 text-sm sm:grid-cols-4">
                            <div class="rounded-md bg-slate-50 p-3">
                                <dt class="text-slate-500">Duration</dt>
                                <dd class="mt-1 font-semibold text-slate-900">{{ $exam?->duration_minutes }} min</dd>
                            </div>
                            <div class="rounded-md bg-slate-50 p-3">
                                <dt class="text-slate-500">Questions</dt>
                                <dd class="mt-1 font-semibold text-slate-900">{{ $questionsCount }}</dd>
                            </div>
                            <div class="rounded-md bg-slate-50 p-3">
                                <dt class="text-slate-500">Due</dt>
                                <dd class="mt-1 font-semibold text-slate-900">{{ $assignment->due_at?->format('M j H:i') ?? 'Open' }}</dd>
                            </div>
                            <div class="rounded-md bg-slate-50 p-3">
                                <dt class="text-slate-500">Attempts</dt>
                                <dd class="mt-1 font-semibold text-slate-900">{{ $card['attemptsUsed'] }} / {{ $assignment->max_attempts }}</dd>
                            </div>
                        </dl>

                        <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="text-sm text-slate-500">
                                @if ($submittedSession && $card['scoreVisible'])
                                    <span class="font-semibold text-slate-950">Score:</span>
                                    {{ number_format((float) $submittedSession->score, 2) }} / {{ number_format((float) $submittedSession->max_score, 2) }}
                                    @if ($submittedSession->percentage !== null)
                                        <span class="ms-1">({{ number_format((float) $submittedSession->percentage, 2) }}%)</span>
                                    @endif
                                @elseif ($submittedSession && $manualPending)
                                    Pending instructor grading
                                @elseif ($submittedSession)
                                    Submitted. Score hidden by instructor.
                                @else
                                    {{ $assignment->available_at?->format('M j, Y H:i') ?? 'Available now' }}
                                @endif
                            </div>

                            @if ($activeSession)
                                <a href="{{ route('student.exams.sessions.show', $activeSession) }}"
                                    class="inline-flex items-center justify-center rounded-md bg-slate-950 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800">
                                    Resume
                                </a>
                            @elseif ($submittedSession)
                                <span class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700">
                                    Completed
                                </span>
                            @else
                                <form method="POST" action="{{ route('student.exams.start', $assignment) }}">
                                    @csrf
                                    <button class="inline-flex items-center justify-center rounded-md bg-orange-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-orange-700">
                                        Start
                                    </button>
                                </form>
                            @endif
                        </div>
                    </article>
                @empty
                    <section class="rounded-lg border border-dashed border-slate-300 bg-white p-10 text-center">
                        <h3 class="text-lg font-semibold text-slate-950">No available exams</h3>
                        <p class="mt-2 text-sm text-slate-600">Assigned exams appear here when their availability window opens.</p>
                    </section>
                @endforelse
            </section>
        </div>
    </div>
</x-app-layout>
