@php
    $sectionedQuestions = $questions->groupBy(
        fn ($question) => $question->topic ?: str($question->category)->replace('_', ' ')->title()->toString()
    );
@endphp

@if ($displayFormat === \App\Models\Exam\InstructorExam::FORMAT_ONE_QUESTION_AT_TIME)
    <section class="space-y-5" data-exam-template="{{ $displayFormat }}">
        <div class="exam-print-section rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-orange-600">{{ __('One Question at a Time template') }}</p>
                    <h3 class="mt-1 text-xl font-semibold text-slate-950">{{ __('Focused question flow') }}</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        {{ __('Students answer one question per screen with previous/next controls and a question navigator. The printable preview includes all questions.') }}
                    </p>
                </div>

                <div class="rounded-xl bg-slate-50 p-4 text-sm">
                    <div class="flex items-center justify-between gap-6">
                        <span class="font-semibold text-slate-900">{{ __('Question 1 of :count', ['count' => max($questions->count(), 1)]) }}</span>
                        <span class="text-slate-500">{{ __('Student question navigator') }}</span>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @forelse ($questions as $question)
                            <span class="flex h-9 w-9 items-center justify-center rounded-lg border {{ $loop->first ? 'border-orange-500 bg-orange-50 text-orange-700' : 'border-slate-200 bg-white text-slate-700' }} text-sm font-semibold">
                                {{ $loop->iteration }}
                            </span>
                        @empty
                            <span class="rounded-lg border border-dashed border-slate-300 px-3 py-2 text-sm text-slate-600">{{ __('No questions') }}</span>
                        @endforelse
                    </div>
                    <div class="mt-4 flex justify-between gap-3">
                        <span class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700">{{ __('Previous') }}</span>
                        <span class="rounded-lg bg-slate-950 px-4 py-2 text-sm font-semibold text-white">{{ __('Next') }}</span>
                    </div>
                </div>
            </div>
        </div>

        @forelse ($questions as $question)
            @include('instructor.exams.partials.preview-question', ['question' => $question])
        @empty
            <div class="empty-state text-center">
                <strong>{{ __('No questions to preview') }}</strong>
                <span class="mt-1 block">{{ __('Add questions before reviewing the exam template.') }}</span>
            </div>
        @endforelse
    </section>
@elseif ($displayFormat === \App\Models\Exam\InstructorExam::FORMAT_GOOGLE_FORMS)
    <section class="space-y-6" data-exam-template="{{ $displayFormat }}">
        <div class="exam-print-section rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-orange-600">{{ __('Google Forms Style template') }}</p>
                    <h3 class="mt-1 text-xl font-semibold text-slate-950">{{ __('Section-based exam flow') }}</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        {{ __('Questions are grouped by topic or category with clear section headers and vertical answer flow.') }}
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    @forelse ($sectionedQuestions as $sectionName => $sectionQuestions)
                        <a href="#preview-section-{{ $loop->iteration }}"
                            class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-orange-100 hover:text-orange-700">
                            {{ $sectionName }}
                        </a>
                    @empty
                        <span class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700">{{ __('No sections') }}</span>
                    @endforelse
                </div>
            </div>
        </div>

        @forelse ($sectionedQuestions as $sectionName => $sectionQuestions)
            <section id="preview-section-{{ $loop->iteration }}" class="exam-print-section scroll-mt-24 rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="rounded-t-xl bg-orange-600 px-6 py-5 text-white">
                    <p class="text-sm font-semibold uppercase tracking-wide text-orange-100">{{ __('Section :number', ['number' => $loop->iteration]) }}</p>
                    <h3 class="mt-1 text-xl font-semibold text-white">{{ $sectionName }}</h3>
                    <p class="mt-2 text-sm text-orange-50">{{ __(':count questions in this section', ['count' => $sectionQuestions->count()]) }}</p>
                </div>

                <div class="space-y-5 p-5">
                    @foreach ($sectionQuestions as $question)
                        @include('instructor.exams.partials.preview-question', ['question' => $question])
                    @endforeach
                </div>
            </section>
        @empty
            <div class="empty-state text-center">
                <strong>{{ __('No questions to preview') }}</strong>
                <span class="mt-1 block">{{ __('Add questions before reviewing the exam template.') }}</span>
            </div>
        @endforelse
    </section>
@else
    <section class="space-y-5" data-exam-template="{{ $displayFormat }}">
        <div class="exam-print-section rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-orange-600">{{ __('All Questions on One Page template') }}</p>
                    <h3 class="mt-1 text-xl font-semibold text-slate-950">{{ __('All questions are visible on one page') }}</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        {{ __('Students scroll through the entire exam and can use quick links to jump between questions before submitting.') }}
                    </p>
                </div>

                <div>
                    <p class="text-sm font-semibold text-slate-900">{{ __('Quick links') }}</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @forelse ($questions as $question)
                            <a href="#preview-question-{{ $question->id }}"
                                class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:border-orange-300 hover:bg-orange-50 hover:text-orange-700">
                                {{ $loop->iteration }}
                            </a>
                        @empty
                            <span class="rounded-lg border border-dashed border-slate-300 px-3 py-2 text-sm text-slate-600">{{ __('No questions') }}</span>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        @forelse ($questions as $question)
            <div id="preview-question-{{ $question->id }}" class="scroll-mt-24">
                @include('instructor.exams.partials.preview-question', ['question' => $question])
            </div>
        @empty
            <div class="empty-state text-center">
                <strong>{{ __('No questions to preview') }}</strong>
                <span class="mt-1 block">{{ __('Add questions before reviewing the exam template.') }}</span>
            </div>
        @endforelse
    </section>
@endif
