<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-medium text-orange-600">Exam Builder</p>
                <h2 class="text-2xl font-semibold leading-tight text-slate-950">Choose Question Type</h2>
            </div>
            <div class="text-sm text-slate-500">Phase 2 of 4: Question types</div>
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
                    <p class="font-semibold">Please select a valid question type.</p>
                    <ul class="mt-2 list-disc space-y-1 ps-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_330px]">
                <div class="space-y-6">
                    <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                            <div>
                                <p class="text-sm font-semibold uppercase tracking-wide text-orange-600">Current exam</p>
                                <h3 class="mt-1 text-xl font-semibold text-slate-950">{{ $exam->title }}</h3>
                                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">
                                    {{ $exam->description ?: 'No description provided yet.' }}
                                </p>
                            </div>
                            <dl class="grid min-w-56 grid-cols-2 gap-3 text-sm">
                                <div class="rounded-md bg-slate-50 p-3">
                                    <dt class="text-slate-500">Course</dt>
                                    <dd class="mt-1 font-semibold text-slate-900">{{ $exam->course->code }}</dd>
                                </div>
                                <div class="rounded-md bg-slate-50 p-3">
                                    <dt class="text-slate-500">Marks</dt>
                                    <dd class="mt-1 font-semibold text-slate-900">{{ $exam->total_marks }}</dd>
                                </div>
                            </dl>
                        </div>
                    </section>

                    @foreach ($categories as $category)
                        @php
                            $accentClasses = match ($category['accent']) {
                                'orange' => 'border-orange-200 bg-orange-50 text-orange-700',
                                'navy' => 'border-slate-300 bg-slate-950 text-white',
                                'emerald' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                                default => 'border-slate-200 bg-slate-50 text-slate-700',
                            };
                        @endphp

                        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                            <div class="flex flex-col gap-3 border-b border-slate-100 pb-5 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <span class="inline-flex rounded-full border px-3 py-1 text-xs font-semibold {{ $accentClasses }}">
                                        {{ $category['label'] }}
                                    </span>
                                    <p class="mt-3 text-sm leading-6 text-slate-600">{{ $category['description'] }}</p>
                                </div>
                                <p class="text-sm text-slate-500">{{ count($category['types']) }} types</p>
                            </div>

                            <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                                @foreach ($category['types'] as $type)
                                    <form method="POST" action="{{ route('instructor.exams.question-types.store', $exam) }}"
                                        class="flex min-h-48 flex-col justify-between rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:border-orange-300 hover:shadow-md">
                                        @csrf
                                        <input type="hidden" name="question_type" value="{{ $type['key'] }}">

                                        <div>
                                            <div class="flex items-start justify-between gap-3">
                                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-slate-100 text-xs font-bold text-slate-800">
                                                    {{ $type['short_label'] }}
                                                </div>
                                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">
                                                    {{ $type['builder_phase'] }}
                                                </span>
                                            </div>
                                            <h4 class="mt-4 text-base font-semibold text-slate-950">{{ $type['label'] }}</h4>
                                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $type['description'] }}</p>

                                            @if (isset($type['language']))
                                                <div class="mt-4 rounded-md border border-slate-800 bg-slate-950 p-3 font-mono text-xs text-slate-100">
                                                    <div class="flex items-center justify-between text-slate-400">
                                                        <span>language</span>
                                                        <span>{{ $type['language'] }}</span>
                                                    </div>
                                                    <div class="mt-3 text-orange-300">starter_code: ready for builder</div>
                                                    <div class="text-emerald-300">expected_output: defined later</div>
                                                </div>
                                            @endif

                                            @if ($type['key'] === 'packet_tracer')
                                                <div class="mt-4 grid gap-2 text-xs text-slate-600">
                                                    <div class="rounded-md border border-dashed border-slate-300 bg-slate-50 px-3 py-2">.pkt file upload</div>
                                                    <div class="rounded-md border border-dashed border-slate-300 bg-slate-50 px-3 py-2">topology screenshot</div>
                                                    <div class="rounded-md border border-dashed border-slate-300 bg-slate-50 px-3 py-2">scenario tasks</div>
                                                </div>
                                            @endif
                                        </div>

                                        <button type="submit"
                                            class="mt-5 inline-flex w-full items-center justify-center rounded-md bg-slate-950 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
                                            Select type
                                        </button>
                                    </form>
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                </div>

                <aside class="space-y-6">
                    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="text-base font-semibold text-slate-950">Creation cycle</h3>
                        <div class="mt-5 space-y-4">
                            <div class="flex gap-3 opacity-70">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-sm font-semibold text-emerald-700">1</div>
                                <div>
                                    <p class="font-medium text-slate-900">Exam settings</p>
                                    <p class="text-sm text-slate-600">Saved as draft.</p>
                                </div>
                            </div>
                            <div class="flex gap-3">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-orange-600 text-sm font-semibold text-white">2</div>
                                <div>
                                    <p class="font-medium text-slate-900">Question types</p>
                                    <p class="text-sm text-slate-600">Choose the structure before editing details.</p>
                                </div>
                            </div>
                            <div class="flex gap-3 opacity-60">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-200 text-sm font-semibold text-slate-700">3</div>
                                <div>
                                    <p class="font-medium text-slate-900">Dedicated builders</p>
                                    <p class="text-sm text-slate-600">MCQ, coding, networking, and more.</p>
                                </div>
                            </div>
                            <div class="flex gap-3 opacity-60">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-200 text-sm font-semibold text-slate-700">4</div>
                                <div>
                                    <p class="font-medium text-slate-900">Preview</p>
                                    <p class="text-sm text-slate-600">Review the student-facing structure.</p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="text-base font-semibold text-slate-950">Draft summary</h3>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div>
                                <dt class="text-slate-500">Selected questions</dt>
                                <dd class="mt-1 font-semibold text-slate-900">{{ $questionCount }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Status</dt>
                                <dd class="mt-1 font-semibold capitalize text-slate-900">{{ $exam->status }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Duration</dt>
                                <dd class="mt-1 font-semibold text-slate-900">{{ $exam->duration_minutes }} minutes</dd>
                            </div>
                        </dl>
                        <a href="{{ route('instructor.exams.questions.order.index', $exam) }}"
                            class="mt-5 inline-flex w-full items-center justify-center rounded-md bg-slate-950 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-orange-600">
                            Manage order
                        </a>
                        <a href="{{ route('instructor.exams.preview.show', $exam) }}"
                            class="mt-3 inline-flex w-full items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 shadow-sm hover:bg-slate-50">
                            Preview exam
                        </a>
                    </section>

                    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="text-base font-semibold text-slate-950">Question schema</h3>
                        <p class="mt-3 text-sm leading-6 text-slate-600">
                            Selected types are stored in <span class="font-mono text-slate-800">instructor_exam_questions</span> with JSON fields for future builders, coding templates, resources, and auto-grading settings.
                        </p>
                    </section>
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
