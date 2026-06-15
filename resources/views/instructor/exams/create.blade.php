<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-medium text-orange-600">Instructor workspace</p>
                <h2 class="text-2xl font-semibold leading-tight text-slate-950">Exam Builder</h2>
            </div>
            <div class="text-sm text-slate-500">Create and manage exams</div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div role="status" aria-live="polite" class="mb-6 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div role="alert" class="mb-6 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    <p class="font-semibold">Please review the highlighted fields.</p>
                    <ul class="mt-2 list-disc space-y-1 ps-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @include('instructor.exams.partials.workspace-nav', [
                'exam' => null,
                'active' => 'information',
            ])

            <section class="mb-6 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-3 border-b border-slate-100 pb-5 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-wide text-orange-600">Created exams</p>
                        <h3 class="mt-1 text-lg font-semibold text-slate-950">Open an exam workspace</h3>
                    </div>
                    <span class="text-sm text-slate-500">{{ $exams->count() }} exams</span>
                </div>

                @if ($exams->isEmpty())
                    <div class="empty-state mt-5">
                        <strong class="block text-sm">{{ __('No exams have been created yet.') }}</strong>
                        <span class="mt-1 block text-sm">{{ __('Use the form below to create the exam shell, then add questions from the workspace.') }}</span>
                    </div>
                @else
                    <div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($exams as $exam)
                            <article class="flex min-h-52 flex-col justify-between rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:border-orange-300 hover:shadow-md">
                                <div>
                                    <div class="flex items-start justify-between gap-3">
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium capitalize text-slate-700">
                                            {{ __(ucfirst($exam->status)) }}
                                        </span>
                                        <span class="text-xs font-medium text-slate-500">
                                            {{ __(':count questions', ['count' => $exam->questions_count]) }}
                                        </span>
                                    </div>

                                    <a href="{{ route('instructor.exams.edit', $exam) }}" class="mt-4 block text-base font-semibold text-slate-950 hover:text-orange-700">
                                        {{ $exam->title }}
                                    </a>
                                    <p class="mt-2 text-sm text-slate-600">
                                        {{ $exam->course?->code ?? __('No course') }}
                                        @if ($exam->course)
                                            <span class="text-slate-400">/</span>
                                            {{ $exam->course->name }}
                                        @endif
                                    </p>
                                </div>

                                <dl class="mt-5 grid grid-cols-2 gap-3 text-sm">
                                    <div class="rounded-md bg-slate-50 p-3">
                                        <dt class="text-slate-500">{{ __('Duration') }}</dt>
                                        <dd class="mt-1 font-semibold text-slate-900">{{ __(':count min', ['count' => $exam->duration_minutes]) }}</dd>
                                    </div>
                                    <div class="rounded-md bg-slate-50 p-3">
                                        <dt class="text-slate-500">{{ __('Marks') }}</dt>
                                        <dd class="mt-1 font-semibold text-slate-900">{{ $exam->total_marks }}</dd>
                                    </div>
                                </dl>

                                <div class="mt-4 grid grid-cols-2 gap-2">
                                    <a href="{{ route('instructor.exams.edit', $exam) }}" class="inline-flex items-center justify-center rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-center text-xs font-semibold text-slate-700 hover:border-orange-300 hover:text-orange-700">
                                        {{ __('Setup') }}
                                    </a>
                                    <a href="{{ route('instructor.exams.question-types.index', $exam) }}" class="inline-flex items-center justify-center rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-center text-xs font-semibold text-slate-700 hover:border-orange-300 hover:text-orange-700">
                                        {{ __('Questions') }}
                                    </a>
                                    <a href="{{ route('instructor.exams.preview.show', $exam) }}" class="inline-flex items-center justify-center rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-center text-xs font-semibold text-slate-700 hover:border-orange-300 hover:text-orange-700">
                                        {{ __('Preview') }}
                                    </a>
                                    <a href="{{ route('instructor.exams.publish.show', $exam) }}" class="inline-flex items-center justify-center rounded-md bg-slate-950 px-3 py-2 text-center text-xs font-semibold text-white hover:bg-orange-600">
                                        {{ __('Publish') }}
                                    </a>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
                <form method="POST" action="{{ route('instructor.exams.store') }}" class="space-y-6">
                    @csrf

                    <section id="exam-information" class="scroll-mt-24 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="border-b border-slate-100 pb-5">
                            <p class="text-sm font-semibold uppercase tracking-wide text-orange-600">New exam</p>
                            <h3 class="mt-1 text-lg font-semibold text-slate-950">Create an exam shell</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                After saving, open the exam workspace to add questions, manage ordering, and preview.
                            </p>
                        </div>

                        <div class="mt-6 grid gap-5">
                            <div>
                                <label for="title" class="block text-sm font-medium text-slate-800">Exam title</label>
                                <input id="title" name="title" value="{{ old('title') }}" required
                                    placeholder="Midterm Exam - Database Systems"
                                    class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                <x-input-error :messages="$errors->get('title')" class="mt-2" />
                            </div>

                            <div>
                                <label for="course_id" class="block text-sm font-medium text-slate-800">Course</label>
                                <select id="course_id" name="course_id" required
                                    class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                    <option value="">Select a course</option>
                                    @foreach ($courses as $course)
                                        <option value="{{ $course->id }}" @selected(old('course_id') == $course->id)>
                                            {{ $course->code }} - {{ $course->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @if ($courses->isEmpty())
                                    <p class="mt-2 text-sm text-amber-700">No active courses are available yet. Add courses before creating instructor exams.</p>
                                @endif
                                <x-input-error :messages="$errors->get('course_id')" class="mt-2" />
                            </div>

                            <div>
                                <label for="description" class="block text-sm font-medium text-slate-800">Description</label>
                                <textarea id="description" name="description" rows="5"
                                    placeholder="Example: Covers normalization, SQL joins, transactions, and ER modeling."
                                    class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">{{ old('description') }}</textarea>
                                <x-input-error :messages="$errors->get('description')" class="mt-2" />
                            </div>
                        </div>
                    </section>

                    <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="border-b border-slate-100 pb-5">
                            <p class="text-sm font-semibold uppercase tracking-wide text-orange-600">Settings</p>
                            <h3 class="mt-1 text-lg font-semibold text-slate-950">Timing and marks</h3>
                        </div>

                        <div class="mt-6 grid gap-5 md:grid-cols-2">
                            <div>
                                <label for="duration_minutes" class="block text-sm font-medium text-slate-800">Duration</label>
                                <div class="mt-2 flex rounded-md shadow-sm">
                                    <input id="duration_minutes" type="number" name="duration_minutes" value="{{ old('duration_minutes', 60) }}" min="5" max="600" required
                                        class="block w-full ltr:rounded-l-md rtl:rounded-r-md border-slate-300 focus:border-orange-500 focus:ring-orange-500">
                                    <span class="inline-flex items-center border border-slate-300 bg-slate-50 px-3 text-sm text-slate-600 ltr:rounded-r-md ltr:border-l-0 rtl:rounded-l-md rtl:border-r-0">minutes</span>
                                </div>
                                <p class="form-hint mt-2">{{ __('Use 5 to 600 minutes. Students see this as the exam timer.') }}</p>
                                <x-input-error :messages="$errors->get('duration_minutes')" class="mt-2" />
                            </div>

                            <div>
                                <label for="total_marks" class="block text-sm font-medium text-slate-800">Total marks</label>
                                <input id="total_marks" type="number" step="0.01" min="1" name="total_marks" value="{{ old('total_marks', 100) }}" required
                                    class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                <p class="form-hint mt-2">{{ __('Keep this aligned with the sum of question marks before publishing.') }}</p>
                                <x-input-error :messages="$errors->get('total_marks')" class="mt-2" />
                            </div>

                            <div>
                                <label for="starts_at" class="block text-sm font-medium text-slate-800">Start date and time</label>
                                <input id="starts_at" type="datetime-local" name="starts_at" value="{{ old('starts_at') }}"
                                    class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                <x-input-error :messages="$errors->get('starts_at')" class="mt-2" />
                            </div>

                            <div>
                                <label for="ends_at" class="block text-sm font-medium text-slate-800">End date and time</label>
                                <input id="ends_at" type="datetime-local" name="ends_at" value="{{ old('ends_at') }}"
                                    class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                <p class="form-hint mt-2">{{ __('Leave start/end empty only when the exam should be controlled later through assignments.') }}</p>
                                <x-input-error :messages="$errors->get('ends_at')" class="mt-2" />
                            </div>
                        </div>
                    </section>

                    <section id="exam-format" class="scroll-mt-24 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="border-b border-slate-100 pb-5">
                            <p class="text-sm font-semibold uppercase tracking-wide text-orange-600">Exam Format</p>
                            <h3 class="mt-1 text-lg font-semibold text-slate-950">Choose how students will see the exam</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                This controls the student exam page. You can still adjust questions and preview the exam before publishing.
                            </p>
                        </div>

                        <div class="mt-6">
                            @include('instructor.exams.partials.format-selector', [
                                'formats' => $displayFormats,
                                'selected' => old('display_format', \App\Models\Exam\InstructorExam::FORMAT_ONE_QUESTION_AT_TIME),
                            ])
                            <x-input-error :messages="$errors->get('display_format')" class="mt-3" />
                        </div>
                    </section>

                    <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm font-semibold uppercase tracking-wide text-orange-600">Save</p>
                                <h3 class="mt-1 text-lg font-semibold text-slate-950">Start the exam workflow</h3>
                            </div>
                            <div class="flex flex-col gap-3 sm:flex-row">
                                <button type="submit" name="intent" value="draft" @disabled($courses->isEmpty())
                                    class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 shadow-sm hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                                    Save draft
                                </button>
                                <button type="submit" name="intent" value="publish_later" @disabled($courses->isEmpty())
                                    class="inline-flex items-center justify-center rounded-md bg-orange-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                                    Save and add questions
                                </button>
                            </div>
                        </div>
                    </section>
                </form>

                <aside class="space-y-6">
                    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="text-base font-semibold text-slate-950">Workflow</h3>
                        <div class="mt-5 space-y-4">
                            <div class="flex gap-3">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-orange-600 text-sm font-semibold text-white">1</div>
                                <div>
                                    <p class="font-medium text-slate-900">Create shell</p>
                                    <p class="text-sm text-slate-600">Title, course, timing, marks.</p>
                                </div>
                            </div>
                            <div class="flex gap-3 opacity-70">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-200 text-sm font-semibold text-slate-700">2</div>
                                <div>
                                    <p class="font-medium text-slate-900">Choose format</p>
                                    <p class="text-sm text-slate-600">Decide how students will view the exam.</p>
                                </div>
                            </div>
                            <div class="flex gap-3 opacity-70">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-200 text-sm font-semibold text-slate-700">3</div>
                                <div>
                                    <p class="font-medium text-slate-900">Add questions</p>
                                    <p class="text-sm text-slate-600">Build, order, preview, and publish.</p>
                                </div>
                            </div>
                        </div>
                    </section>
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
