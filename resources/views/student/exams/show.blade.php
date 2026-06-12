<x-app-layout>
    @php
        $displayFormat = $exam->display_format ?? \App\Models\Exam\InstructorExam::FORMAT_ONE_QUESTION_AT_TIME;
        $formatMeta = \App\Support\Exams\ExamDisplayFormatCatalog::formats()[$displayFormat]
            ?? \App\Support\Exams\ExamDisplayFormatCatalog::formats()[\App\Models\Exam\InstructorExam::FORMAT_ONE_QUESTION_AT_TIME];
        $questionTotal = max($questions->count(), 1);
        $sectionedQuestions = $questions->groupBy(fn ($question) => $question->topic ?: str($question->category)->replace('_', ' ')->title()->toString());
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-medium text-orange-600">{{ $exam->course?->code }} - {{ $exam->course?->name }}</p>
                <h2 class="text-2xl font-semibold leading-tight text-slate-950">{{ $exam->title }}</h2>
            </div>
            <div class="text-sm text-slate-500">Attempt {{ $session->attempt_number }}</div>
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

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_330px]">
                <form method="POST" action="{{ route('student.exams.sessions.answers.save', $session) }}" class="space-y-6" data-exam-session-form>
                    @csrf

                    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                            <div>
                                <p class="text-sm font-semibold uppercase tracking-wide text-orange-600">Exam format</p>
                                <h3 class="mt-1 text-lg font-semibold text-slate-950">{{ $formatMeta['title'] }}</h3>
                                <p class="mt-2 text-sm leading-6 text-slate-600">{{ $formatMeta['summary'] }}</p>
                            </div>
                            <div class="min-w-44 rounded-md bg-slate-50 p-3 text-sm">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-slate-500">Progress</span>
                                    <span class="font-semibold text-slate-950">{{ $questions->count() }} questions</span>
                                </div>
                                <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-200">
                                    <div class="h-full w-full rounded-full bg-orange-500"></div>
                                </div>
                            </div>
                        </div>
                    </section>

                    @if ($displayFormat === \App\Models\Exam\InstructorExam::FORMAT_ONE_QUESTION_AT_TIME)
                        <section class="space-y-6"
                            x-data="{
                                current: 0,
                                total: {{ $questions->count() }},
                                flagged: {},
                                go(index) {
                                    this.current = Math.max(0, Math.min(index, this.total - 1));
                                    window.scrollTo({ top: 0, behavior: 'smooth' });
                                },
                                percent() {
                                    if (this.total < 1) return 0;
                                    return Math.round(((this.current + 1) / this.total) * 100);
                                }
                            }">
                            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">
                                            Question <span x-text="current + 1"></span> of {{ $questions->count() }}
                                        </p>
                                        <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-slate-200 lg:w-80">
                                            <div class="h-full rounded-full bg-orange-500 transition-all" :style="`width: ${percent()}%`"></div>
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($questions as $index => $question)
                                            <button type="button" x-on:click="go({{ $index }})"
                                                class="relative flex h-10 w-10 items-center justify-center rounded-md border text-sm font-semibold transition"
                                                :class="current === {{ $index }} ? 'border-orange-500 bg-orange-50 text-orange-700' : 'border-slate-200 bg-white text-slate-700 hover:border-orange-300'">
                                                {{ $index + 1 }}
                                                <span x-show="flagged[{{ $question->id }}]" class="absolute -right-1 -top-1 h-2.5 w-2.5 rounded-full bg-amber-500"></span>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            @foreach ($questions as $index => $question)
                                <div x-show="current === {{ $index }}" x-transition>
                                    <div class="mb-4 flex flex-col gap-3 rounded-lg border border-amber-200 bg-amber-50 p-4 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <p class="text-sm font-semibold text-amber-900">Need to review this question?</p>
                                            <p class="mt-1 text-sm text-amber-800">Flag it and use the navigator to return before submitting.</p>
                                        </div>
                                        <button type="button" x-on:click="flagged[{{ $question->id }}] = !flagged[{{ $question->id }}]"
                                            class="inline-flex items-center justify-center rounded-md border border-amber-300 bg-white px-4 py-2 text-sm font-semibold text-amber-800 shadow-sm hover:bg-amber-100">
                                            <span x-text="flagged[{{ $question->id }}] ? 'Remove flag' : 'Flag question'"></span>
                                        </button>
                                    </div>

                                    @include('student.exams.partials.question', [
                                        'question' => $question,
                                        'answer' => $answersByQuestion->get($question->id),
                                        'timing' => $timingPlan[$question->id] ?? null,
                                    ])

                                    <div class="mt-6 flex flex-col gap-3 rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                                        <button type="button" x-on:click="go(current - 1)" x-bind:disabled="current === 0"
                                            class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 shadow-sm hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50">
                                            Previous
                                        </button>
                                        <button type="button" x-on:click="go(current + 1)" x-bind:disabled="current === total - 1"
                                            class="inline-flex items-center justify-center rounded-md bg-slate-950 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50">
                                            Next
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </section>
                    @elseif ($displayFormat === \App\Models\Exam\InstructorExam::FORMAT_GOOGLE_FORMS)
                        <section class="space-y-6">
                            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                    <div>
                                        <p class="text-sm font-semibold uppercase tracking-wide text-orange-600">Sections</p>
                                        <h3 class="mt-1 text-lg font-semibold text-slate-950">Work through each group from top to bottom</h3>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($sectionedQuestions as $sectionName => $sectionQuestions)
                                            <a href="#section-{{ \Illuminate\Support\Str::slug($sectionName) }}"
                                                class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-orange-100 hover:text-orange-700">
                                                {{ $sectionName }}
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            @foreach ($sectionedQuestions as $sectionName => $sectionQuestions)
                                <section id="section-{{ \Illuminate\Support\Str::slug($sectionName) }}" class="scroll-mt-6 rounded-lg border border-slate-200 bg-white shadow-sm">
                                    <div class="rounded-t-lg bg-orange-600 px-6 py-5 text-white">
                                        <p class="text-sm font-semibold uppercase tracking-wide text-orange-100">Section {{ $loop->iteration }}</p>
                                        <h3 class="mt-1 text-xl font-semibold">{{ $sectionName }}</h3>
                                        <p class="mt-2 text-sm text-orange-50">{{ $sectionQuestions->count() }} questions in this section</p>
                                    </div>

                                    <div class="space-y-5 p-5">
                                        @foreach ($sectionQuestions as $question)
                                            @include('student.exams.partials.question', [
                                                'question' => $question,
                                                'answer' => $answersByQuestion->get($question->id),
                                                'timing' => $timingPlan[$question->id] ?? null,
                                            ])
                                        @endforeach
                                    </div>
                                </section>
                            @endforeach
                        </section>
                    @else
                        <section class="space-y-6">
                            <div class="sticky top-0 z-10 rounded-lg border border-slate-200 bg-white/95 p-5 shadow-sm backdrop-blur">
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                    <div>
                                        <p class="text-sm font-semibold uppercase tracking-wide text-orange-600">Quick navigation</p>
                                        <h3 class="mt-1 text-lg font-semibold text-slate-950">All questions are on this page</h3>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($questions as $question)
                                            <a href="#question-{{ $question->id }}"
                                                class="flex h-9 w-9 items-center justify-center rounded-md border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:border-orange-300 hover:bg-orange-50 hover:text-orange-700">
                                                {{ $loop->iteration }}
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            @foreach ($questions as $question)
                                <div id="question-{{ $question->id }}" class="scroll-mt-28">
                                    @include('student.exams.partials.question', [
                                        'question' => $question,
                                        'answer' => $answersByQuestion->get($question->id),
                                        'timing' => $timingPlan[$question->id] ?? null,
                                    ])
                                </div>
                            @endforeach
                        </section>
                    @endif

                    <section class="flex flex-col gap-3 rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                        <a href="{{ route('student.exams.index') }}"
                            class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 shadow-sm hover:bg-slate-50">
                            Back to exams
                        </a>
                        <div class="flex flex-col gap-3 sm:flex-row">
                            <button type="submit"
                                class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 shadow-sm hover:bg-slate-50">
                                Save draft
                            </button>
                            <button type="submit" formaction="{{ route('student.exams.sessions.submit', $session) }}"
                                data-submit-exam-button
                                class="inline-flex items-center justify-center rounded-md bg-orange-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-orange-700">
                                Submit exam
                            </button>
                        </div>
                    </section>
                </form>

                <aside class="space-y-6">
                    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="text-base font-semibold text-slate-950">Session</h3>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div>
                                <dt class="text-slate-500">Started</dt>
                                <dd class="mt-1 font-semibold text-slate-900">{{ $session->started_at?->format('M j, Y H:i') }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Ends</dt>
                                <dd class="mt-1 font-semibold text-slate-900">{{ $session->expires_at?->format('M j, Y H:i') ?? 'No timed expiry' }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Questions</dt>
                                <dd class="mt-1 font-semibold text-slate-900">{{ $questions->count() }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Format</dt>
                                <dd class="mt-1 font-semibold text-slate-900">{{ $formatMeta['title'] }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Max score</dt>
                                <dd class="mt-1 font-semibold text-slate-900">{{ $session->max_score ?? $exam->total_marks }}</dd>
                            </div>
                        </dl>
                    </section>

                    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"
                        x-data="{
                            remaining: @js($remainingSeconds),
                            display() {
                                if (this.remaining === null) return 'No timer';
                                const minutes = Math.floor(this.remaining / 60);
                                const seconds = this.remaining % 60;
                                return `${minutes}:${String(seconds).padStart(2, '0')}`;
                            },
                            tick() {
                                if (this.remaining === null || this.remaining <= 0) return;
                                this.remaining -= 1;
                                if (this.remaining === 0) {
                                    window.dispatchEvent(new CustomEvent('minhaj-exam-expired'));
                                }
                            }
                        }"
                        x-init="setInterval(() => tick(), 1000)">
                        <h3 class="text-base font-semibold text-slate-950">Time remaining</h3>
                        <p class="mt-3 text-3xl font-semibold text-slate-950" x-text="display()"></p>
                    </section>
                </aside>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const examIndexUrl = @js(route('student.exams.index'));
            const submitUrl = @js(route('student.exams.sessions.submit', $session));
            const submittedKey = `minhaj:exam-session-submitted:${@js($session->id)}`;

            const redirectToExamList = () => {
                window.location.replace(examIndexUrl);
            };

            const coverExamPage = () => {
                if (document.querySelector('[data-exam-submit-cover]')) {
                    return;
                }

                const cover = document.createElement('div');
                cover.setAttribute('data-exam-submit-cover', 'true');
                cover.style.position = 'fixed';
                cover.style.inset = '0';
                cover.style.zIndex = '2147483647';
                cover.style.background = '#ffffff';
                cover.style.display = 'grid';
                cover.style.placeItems = 'center';
                cover.style.fontFamily = 'system-ui, sans-serif';
                cover.style.fontSize = '16px';
                cover.style.fontWeight = '600';
                cover.style.color = '#0f172a';
                cover.textContent = 'Submitting exam...';
                document.body.appendChild(cover);
            };

            const navigationEntries = performance.getEntriesByType
                ? performance.getEntriesByType('navigation')
                : [];
            const restoredByHistory = navigationEntries.length > 0 && navigationEntries[0].type === 'back_forward';

            if (sessionStorage.getItem(submittedKey) === '1' || restoredByHistory) {
                coverExamPage();
                redirectToExamList();
                return;
            }

            history.pushState({ minhajExamSession: @js($session->id) }, '', window.location.href);

            window.addEventListener('popstate', () => {
                redirectToExamList();
            });

            window.addEventListener('pageshow', (event) => {
                if (sessionStorage.getItem(submittedKey) === '1' || event.persisted) {
                    coverExamPage();
                    redirectToExamList();
                }
            });

            document.querySelector('[data-exam-session-form]')?.addEventListener('submit', (event) => {
                const submitter = event.submitter;
                const targetUrl = submitter?.getAttribute('formaction') || event.currentTarget.action;

                if (submitter?.hasAttribute('data-submit-exam-button') || targetUrl === submitUrl) {
                    sessionStorage.setItem(submittedKey, '1');
                    coverExamPage();
                }
            });

            window.addEventListener('minhaj-exam-expired', () => {
                const form = document.querySelector('[data-exam-session-form]');
                const submitButton = form?.querySelector('[data-submit-exam-button]');

                if (!form || !submitButton || sessionStorage.getItem(submittedKey) === '1') {
                    return;
                }

                const autoSubmit = document.createElement('input');
                autoSubmit.type = 'hidden';
                autoSubmit.name = 'auto_submit';
                autoSubmit.value = '1';
                form.appendChild(autoSubmit);
                form.requestSubmit(submitButton);
            }, { once: true });
        })();
    </script>
</x-app-layout>
