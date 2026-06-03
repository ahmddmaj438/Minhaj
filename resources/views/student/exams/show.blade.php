<x-app-layout>
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

                    @foreach ($questions as $question)
                        @include('student.exams.partials.question', [
                            'question' => $question,
                            'answer' => $answersByQuestion->get($question->id),
                            'timing' => $timingPlan[$question->id] ?? null,
                        ])
                    @endforeach

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
        })();
    </script>
</x-app-layout>
