<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-medium text-orange-600">Instructor grading</p>
                <h2 class="text-2xl font-semibold leading-tight text-slate-950">Submitted Exams</h2>
            </div>
            <div class="text-sm text-slate-500">Question-based scores and feedback</div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div role="status" aria-live="polite" class="mb-6 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 p-6">
                    <h3 class="text-lg font-semibold text-slate-950">Submissions</h3>
                    <p class="mt-1 text-sm text-slate-500">Open a submission to review auto-calculated questions and assign manual scores.</p>
                </div>
                <div class="table-comfort overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-slate-100 text-xs uppercase text-slate-600">
                            <tr>
                                <th class="px-4 py-3">Exam</th>
                                <th class="px-4 py-3">Student</th>
                                <th class="px-4 py-3">Submitted</th>
                                <th class="px-4 py-3">Score</th>
                                <th class="px-4 py-3">Manual pending</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($sessions as $session)
                                @php
                                    $pending = $manualPendingCount($session);
                                @endphp
                                <tr>
                                    <td class="px-4 py-3">
                                        <p class="font-semibold text-slate-950">{{ $session->assignment->exam->title }}</p>
                                        <p class="text-xs text-slate-500">{{ $session->assignment->course?->code ?? $session->assignment->exam->course?->code }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="font-semibold text-slate-950">{{ $session->student->user?->name }}</p>
                                        <p class="text-xs text-slate-500">{{ $session->student->student_number }}</p>
                                    </td>
                                    <td class="px-4 py-3">{{ $session->submitted_at?->format('M j, Y H:i') }}</td>
                                    <td class="px-4 py-3">
                                        {{ number_format((float) $session->score, 2) }} / {{ number_format((float) $session->max_score, 2) }}
                                        @if ($session->percentage !== null)
                                            <span class="text-xs text-slate-500">({{ number_format((float) $session->percentage, 2) }}%)</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $pending > 0 ? 'bg-orange-100 text-orange-700' : 'bg-emerald-100 text-emerald-700' }}">
                                            {{ $pending }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('instructor.grading.sessions.show', $session) }}"
                                            class="inline-flex items-center rounded-md bg-slate-950 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-800">
                                            Grade
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8">
                                        <div class="empty-state text-center">
                                            <strong class="block text-base">No submitted exams yet</strong>
                                            <span class="mt-1 block text-sm">Student submissions will appear here as soon as exams are completed.</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
