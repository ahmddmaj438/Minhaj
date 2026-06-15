@php
    $activeTab = old('_academic_tab', 'setup');
    $inputClass = 'mt-2 block min-h-11 w-full rounded-xl border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500';
    $selectClass = $inputClass;
    $labelClass = 'block text-sm font-semibold text-slate-800';
    $panelClass = 'rounded-xl border border-slate-200 bg-white p-6 shadow-sm';

    $tabs = [
        ['id' => 'setup', 'label' => __('Setup'), 'description' => __('Majors and student profiles')],
        ['id' => 'enrollment', 'label' => __('Enrollment'), 'description' => __('Courses, majors, and students')],
        ['id' => 'assignments', 'label' => __('Exam assignments'), 'description' => __('Availability and attempts')],
        ['id' => 'review', 'label' => __('Review'), 'description' => __('Search records and sessions')],
    ];

    $nextAction = match (true) {
        $majors->isEmpty() => [
            'tab' => 'setup',
            'label' => __('Create the first major'),
            'detail' => __('Majors organize students and make course planning easier.'),
        ],
        $studentUsers->isNotEmpty() && $students->isEmpty() => [
            'tab' => 'setup',
            'label' => __('Create student profiles'),
            'detail' => __('Connect existing Laravel users to student numbers and academic status.'),
        ],
        $courses->isEmpty() => [
            'tab' => 'enrollment',
            'label' => __('Prepare courses'),
            'detail' => __('Courses are needed before enrollment or exam assignment can be completed.'),
        ],
        $assignments->isEmpty() && $exams->isNotEmpty() => [
            'tab' => 'assignments',
            'label' => __('Assign a published exam'),
            'detail' => __('Choose the exam, course, optional student, and availability window.'),
        ],
        default => [
            'tab' => 'review',
            'label' => __('Review academic activity'),
            'detail' => __('Use the tables to monitor students, assignments, and exam sessions.'),
        ],
    };

    $assignmentStatusClass = [
        'assigned' => 'bg-orange-100 text-orange-800',
        'open' => 'bg-emerald-100 text-emerald-800',
        'closed' => 'bg-slate-100 text-slate-800',
        'cancelled' => 'bg-red-100 text-red-800',
    ];

    $sessionStatusClass = [
        'in_progress' => 'bg-blue-100 text-blue-800',
        'submitted' => 'bg-emerald-100 text-emerald-800',
        'expired' => 'bg-amber-100 text-amber-900',
        'cancelled' => 'bg-red-100 text-red-800',
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-orange-600">{{ __('Academic operations') }}</p>
                <h2 class="mt-1 text-2xl font-semibold leading-tight text-slate-950">{{ __('Academic workspace') }}</h2>
                <p class="mt-2 max-w-2xl text-sm text-slate-600">
                    {{ __('Set up programs, connect students to courses, assign published exams, and monitor attempts from one guided workspace.') }}
                </p>
            </div>

            <button type="button"
                class="inline-flex min-h-11 items-center justify-center rounded-xl bg-orange-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-orange-700 lg:w-auto"
                x-data
                x-on:click="$dispatch('academic-open-tab', @js($nextAction['tab']))">
                {{ $nextAction['label'] }}
            </button>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8"
            x-data="{ tab: @js($activeTab) }"
            x-on:academic-open-tab.window="tab = $event.detail; $nextTick(() => document.getElementById('academic-workspace')?.scrollIntoView({ behavior: 'smooth', block: 'start' }))">
            @if (session('status'))
                <div role="status" aria-live="polite" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div role="alert" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    <p class="font-semibold">{{ __('Please review the highlighted fields.') }}</p>
                    <ul class="mt-2 list-disc space-y-1 ps-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <article class="dashboard-card-motion rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">{{ __('Majors') }}</p>
                    <p class="mt-2 text-3xl font-semibold text-slate-950">{{ number_format($majors->count()) }}</p>
                    <p class="mt-2 text-sm text-slate-600">{{ __('Programs available for students and courses.') }}</p>
                </article>
                <article class="dashboard-card-motion rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">{{ __('Students') }}</p>
                    <p class="mt-2 text-3xl font-semibold text-slate-950">{{ number_format($students->count()) }}</p>
                    <p class="mt-2 text-sm text-slate-600">{{ __('Laravel users with academic profiles.') }}</p>
                </article>
                <article class="dashboard-card-motion rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">{{ __('Courses') }}</p>
                    <p class="mt-2 text-3xl font-semibold text-slate-950">{{ number_format($courses->count()) }}</p>
                    <p class="mt-2 text-sm text-slate-600">{{ __('Course catalog records ready for enrollment.') }}</p>
                </article>
                <article class="dashboard-card-motion rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">{{ __('Assignments') }}</p>
                    <p class="mt-2 text-3xl font-semibold text-slate-950">{{ number_format($assignments->count()) }}</p>
                    <p class="mt-2 text-sm text-slate-600">{{ __('Recent exam assignment rules.') }}</p>
                </article>
            </section>

            <section class="rounded-xl border border-orange-100 bg-white/95 p-5 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-sm font-semibold text-orange-700">{{ __('Recommended next step') }}</p>
                        <h3 class="mt-1 text-xl font-semibold text-slate-950">{{ $nextAction['label'] }}</h3>
                        <p class="mt-1 text-sm text-slate-600">{{ $nextAction['detail'] }}</p>
                    </div>

                    <div class="flex flex-wrap gap-2" role="group" aria-label="{{ __('Academic workflow shortcuts') }}">
                        @foreach ($tabs as $item)
                            <button type="button"
                                class="inline-flex min-h-11 items-center rounded-xl border px-4 py-2 text-sm font-semibold transition"
                                x-on:click="tab = @js($item['id'])"
                                x-bind:aria-pressed="tab === @js($item['id']) ? 'true' : 'false'"
                                :class="tab === @js($item['id']) ? 'border-orange-300 bg-orange-50 text-brand-ink shadow-inner-soft' : 'border-slate-200 bg-white text-slate-700 hover:border-orange-200 hover:bg-orange-50'">
                                {{ $item['label'] }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </section>

            <section id="academic-workspace" class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 p-4">
                    <div class="grid gap-2 lg:grid-cols-4" role="tablist" aria-label="{{ __('Academic workflow') }}">
                        @foreach ($tabs as $item)
                            <button type="button"
                                role="tab"
                                class="rounded-xl px-4 py-3 text-start transition focus:outline-none focus-visible:ring-4 focus-visible:ring-orange-100"
                                x-on:click="tab = @js($item['id'])"
                                x-bind:aria-selected="tab === @js($item['id']) ? 'true' : 'false'"
                                x-bind:aria-controls="'academic-panel-' + @js($item['id'])"
                                :class="tab === @js($item['id']) ? 'bg-brand-navy text-white shadow-sm' : 'bg-slate-50 text-slate-700 hover:bg-orange-50 hover:text-brand-ink'">
                                <span class="block text-sm font-semibold">{{ $item['label'] }}</span>
                                <span class="mt-1 block text-xs leading-5 opacity-80">{{ $item['description'] }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="p-4 sm:p-6">
                    <div id="academic-panel-setup" role="tabpanel" x-show="tab === 'setup'" x-transition x-bind:aria-hidden="tab === 'setup' ? 'false' : 'true'">
                        <div class="mb-5">
                            <h3 class="text-xl font-semibold text-slate-950">{{ __('Start with the academic identity') }}</h3>
                            <p class="mt-1 text-sm text-slate-600">
                                {{ __('Create the program structure first, then connect existing user accounts to student profiles.') }}
                            </p>
                        </div>

                        <div class="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
                            <section class="{{ $panelClass }}">
                                <div class="border-b border-slate-100 pb-4">
                                    <p class="text-sm font-semibold text-orange-700">{{ __('Program setup') }}</p>
                                    <h4 class="mt-1 text-lg font-semibold text-slate-950">{{ __('Create a major') }}</h4>
                                    <p class="mt-1 text-sm text-slate-600">{{ __('Use short codes that staff can recognize in tables and forms.') }}</p>
                                </div>

                                <form method="POST" action="{{ route('academics.majors.store') }}" class="mt-5 grid gap-4">
                                    @csrf
                                    <input type="hidden" name="_academic_tab" value="setup">

                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <div>
                                            <label for="major_code" class="{{ $labelClass }}">{{ __('Code') }}</label>
                                            <input id="major_code" name="code" value="{{ old('code') }}" placeholder="CS" required class="{{ $inputClass }}">
                                            <x-input-error :messages="$errors->get('code')" class="mt-2" />
                                        </div>
                                        <div>
                                            <label for="major_name" class="{{ $labelClass }}">{{ __('Name') }}</label>
                                            <input id="major_name" name="name" value="{{ old('name') }}" placeholder="Computer Science" required class="{{ $inputClass }}">
                                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                                        </div>
                                    </div>

                                    <div x-data="{ advanced: {{ old('description') ? 'true' : 'false' }} }">
                                        <button type="button" x-on:click="advanced = ! advanced" class="inline-flex min-h-11 items-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-orange-50">
                                            <span x-text="advanced ? @js(__('Hide optional details')) : @js(__('Add optional details'))"></span>
                                        </button>

                                        <div x-show="advanced" x-transition class="mt-4 grid gap-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                                            <div>
                                                <label for="major_description" class="{{ $labelClass }}">{{ __('Description') }}</label>
                                                <textarea id="major_description" name="description" rows="3" class="{{ $inputClass }}">{{ old('description') }}</textarea>
                                                <x-input-error :messages="$errors->get('description')" class="mt-2" />
                                            </div>
                                            <label class="flex min-h-11 items-center gap-3 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700">
                                                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true)) class="rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                                                {{ __('Active major') }}
                                            </label>
                                        </div>
                                    </div>

                                    <button class="inline-flex min-h-11 w-fit items-center rounded-xl bg-orange-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-orange-700">
                                        {{ __('Create major') }}
                                    </button>
                                </form>
                            </section>

                            <section class="{{ $panelClass }}">
                                <div class="border-b border-slate-100 pb-4">
                                    <p class="text-sm font-semibold text-orange-700">{{ __('Student setup') }}</p>
                                    <h4 class="mt-1 text-lg font-semibold text-slate-950">{{ __('Make a user a student') }}</h4>
                                    <p class="mt-1 text-sm text-slate-600">{{ __('Only users without a student profile appear in the account list.') }}</p>
                                </div>

                                @if ($studentUsers->isEmpty())
                                    <div class="empty-state mt-5">
                                        <strong class="block">{{ __('No available user accounts') }}</strong>
                                        <span class="mt-1 block text-sm">{{ __('Every current user already has a student profile, or no users are ready for conversion.') }}</span>
                                    </div>
                                @endif

                                <form method="POST" action="{{ route('academics.students.store') }}" class="mt-5 grid gap-4">
                                    @csrf
                                    <input type="hidden" name="_academic_tab" value="setup">

                                    <div class="grid gap-4 lg:grid-cols-[1.2fr_0.8fr]">
                                        <div>
                                            <label for="student_user_id" class="{{ $labelClass }}">{{ __('User account') }}</label>
                                            <select id="student_user_id" name="user_id" required class="{{ $selectClass }}">
                                                <option value="">{{ __('Select a user without student profile') }}</option>
                                                @foreach ($studentUsers as $user)
                                                    <option value="{{ $user->id }}" @selected((string) old('user_id') === (string) $user->id)>{{ $user->name }} - {{ $user->email }}</option>
                                                @endforeach
                                            </select>
                                            <x-input-error :messages="$errors->get('user_id')" class="mt-2" />
                                        </div>
                                        <div>
                                            <label for="student_number" class="{{ $labelClass }}">{{ __('Student number') }}</label>
                                            <input id="student_number" name="student_number" value="{{ old('student_number') }}" required class="{{ $inputClass }}">
                                            <x-input-error :messages="$errors->get('student_number')" class="mt-2" />
                                        </div>
                                    </div>

                                    <div x-data="{ advanced: {{ old('major_id') || old('admission_year') || old('academic_status') ? 'true' : 'false' }} }">
                                        <button type="button" x-on:click="advanced = ! advanced" class="inline-flex min-h-11 items-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-orange-50">
                                            <span x-text="advanced ? @js(__('Hide academic details')) : @js(__('Show academic details'))"></span>
                                        </button>

                                        <div x-show="advanced" x-transition class="mt-4 grid gap-4 rounded-xl border border-slate-200 bg-slate-50 p-4 lg:grid-cols-3">
                                            <div>
                                                <label for="student_major_id" class="{{ $labelClass }}">{{ __('Major') }}</label>
                                                <select id="student_major_id" name="major_id" class="{{ $selectClass }}">
                                                    <option value="">{{ __('No major yet') }}</option>
                                                    @foreach ($majors as $major)
                                                        <option value="{{ $major->id }}" @selected((string) old('major_id') === (string) $major->id)>{{ $major->code }} - {{ $major->name }}</option>
                                                    @endforeach
                                                </select>
                                                <x-input-error :messages="$errors->get('major_id')" class="mt-2" />
                                            </div>
                                            <div>
                                                <label for="admission_year" class="{{ $labelClass }}">{{ __('Admission year') }}</label>
                                                <input id="admission_year" type="number" name="admission_year" min="1990" max="2100" value="{{ old('admission_year') }}" class="{{ $inputClass }}">
                                                <x-input-error :messages="$errors->get('admission_year')" class="mt-2" />
                                            </div>
                                            <div>
                                                <label for="academic_status" class="{{ $labelClass }}">{{ __('Status') }}</label>
                                                <select id="academic_status" name="academic_status" required class="{{ $selectClass }}">
                                                    <option value="active" @selected(old('academic_status', 'active') === 'active')>{{ __('Active') }}</option>
                                                    <option value="inactive" @selected(old('academic_status') === 'inactive')>{{ __('Inactive') }}</option>
                                                    <option value="graduated" @selected(old('academic_status') === 'graduated')>{{ __('Graduated') }}</option>
                                                    <option value="suspended" @selected(old('academic_status') === 'suspended')>{{ __('Suspended') }}</option>
                                                </select>
                                                <x-input-error :messages="$errors->get('academic_status')" class="mt-2" />
                                            </div>
                                        </div>
                                    </div>

                                    <button @disabled($studentUsers->isEmpty()) class="inline-flex min-h-11 w-fit items-center rounded-xl bg-orange-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-orange-700 disabled:cursor-not-allowed disabled:opacity-60">
                                        {{ __('Create student profile') }}
                                    </button>
                                </form>
                            </section>
                        </div>
                    </div>

                    <div id="academic-panel-enrollment" role="tabpanel" x-show="tab === 'enrollment'" x-transition x-bind:aria-hidden="tab === 'enrollment' ? 'false' : 'true'">
                        <div class="mb-5">
                            <h3 class="text-xl font-semibold text-slate-950">{{ __('Connect courses to programs and students') }}</h3>
                            <p class="mt-1 text-sm text-slate-600">{{ __('First map courses to majors, then enroll individual students in the courses they can access.') }}</p>
                        </div>

                        <div class="grid gap-6 xl:grid-cols-2">
                            <section class="{{ $panelClass }}">
                                <div class="border-b border-slate-100 pb-4">
                                    <p class="text-sm font-semibold text-orange-700">{{ __('Curriculum map') }}</p>
                                    <h4 class="mt-1 text-lg font-semibold text-slate-950">{{ __('Assign course to major') }}</h4>
                                </div>

                                <form method="POST" action="{{ route('academics.major-courses.store') }}" class="mt-5 grid gap-4">
                                    @csrf
                                    <input type="hidden" name="_academic_tab" value="enrollment">
                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <div>
                                            <label for="course_major_major_id" class="{{ $labelClass }}">{{ __('Major') }}</label>
                                            <select id="course_major_major_id" name="major_id" required class="{{ $selectClass }}">
                                                <option value="">{{ __('Select major') }}</option>
                                                @foreach ($majors as $major)
                                                    <option value="{{ $major->id }}" @selected((string) old('major_id') === (string) $major->id)>{{ $major->code }} - {{ $major->name }}</option>
                                                @endforeach
                                            </select>
                                            <x-input-error :messages="$errors->get('major_id')" class="mt-2" />
                                        </div>
                                        <div>
                                            <label for="course_major_course_id" class="{{ $labelClass }}">{{ __('Course') }}</label>
                                            <select id="course_major_course_id" name="course_id" required class="{{ $selectClass }}">
                                                <option value="">{{ __('Select course') }}</option>
                                                @foreach ($courses as $course)
                                                    <option value="{{ $course->id }}" @selected((string) old('course_id') === (string) $course->id)>{{ $course->code }} - {{ $course->name }}</option>
                                                @endforeach
                                            </select>
                                            <x-input-error :messages="$errors->get('course_id')" class="mt-2" />
                                        </div>
                                    </div>

                                    <div x-data="{ advanced: {{ old('recommended_level') ? 'true' : 'false' }} }">
                                        <button type="button" x-on:click="advanced = ! advanced" class="inline-flex min-h-11 items-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-orange-50">
                                            <span x-text="advanced ? @js(__('Hide course planning options')) : @js(__('Show course planning options'))"></span>
                                        </button>
                                        <div x-show="advanced" x-transition class="mt-4 grid gap-4 rounded-xl border border-slate-200 bg-slate-50 p-4 sm:grid-cols-2">
                                            <label class="flex min-h-11 items-center gap-3 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700">
                                                <input type="checkbox" name="is_required" value="1" @checked(old('is_required', true)) class="rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                                                {{ __('Required course') }}
                                            </label>
                                            <div>
                                                <label for="recommended_level" class="{{ $labelClass }}">{{ __('Recommended level') }}</label>
                                                <input id="recommended_level" type="number" name="recommended_level" min="1" max="20" value="{{ old('recommended_level') }}" class="{{ $inputClass }}">
                                                <x-input-error :messages="$errors->get('recommended_level')" class="mt-2" />
                                            </div>
                                        </div>
                                    </div>

                                    <button @disabled($majors->isEmpty() || $courses->isEmpty()) class="inline-flex min-h-11 w-fit items-center rounded-xl bg-orange-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-orange-700 disabled:cursor-not-allowed disabled:opacity-60">
                                        {{ __('Assign course') }}
                                    </button>
                                </form>
                            </section>

                            <section class="{{ $panelClass }}">
                                <div class="border-b border-slate-100 pb-4">
                                    <p class="text-sm font-semibold text-orange-700">{{ __('Student enrollment') }}</p>
                                    <h4 class="mt-1 text-lg font-semibold text-slate-950">{{ __('Enroll student in course') }}</h4>
                                </div>

                                <form method="POST" action="{{ route('academics.course-students.store') }}" class="mt-5 grid gap-4">
                                    @csrf
                                    <input type="hidden" name="_academic_tab" value="enrollment">
                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <div>
                                            <label for="enroll_student_id" class="{{ $labelClass }}">{{ __('Student') }}</label>
                                            <select id="enroll_student_id" name="student_profile_id" required class="{{ $selectClass }}">
                                                <option value="">{{ __('Select student') }}</option>
                                                @foreach ($students as $student)
                                                    <option value="{{ $student->id }}" @selected((string) old('student_profile_id') === (string) $student->id)>{{ $student->user?->name }} - {{ $student->student_number }}</option>
                                                @endforeach
                                            </select>
                                            <x-input-error :messages="$errors->get('student_profile_id')" class="mt-2" />
                                        </div>
                                        <div>
                                            <label for="enroll_course_id" class="{{ $labelClass }}">{{ __('Course') }}</label>
                                            <select id="enroll_course_id" name="course_id" required class="{{ $selectClass }}">
                                                <option value="">{{ __('Select course') }}</option>
                                                @foreach ($courses as $course)
                                                    <option value="{{ $course->id }}" @selected((string) old('course_id') === (string) $course->id)>{{ $course->code }} - {{ $course->name }}</option>
                                                @endforeach
                                            </select>
                                            <x-input-error :messages="$errors->get('course_id')" class="mt-2" />
                                        </div>
                                    </div>

                                    <div x-data="{ advanced: {{ old('enrolled_at') || old('enrollment_status') ? 'true' : 'false' }} }">
                                        <button type="button" x-on:click="advanced = ! advanced" class="inline-flex min-h-11 items-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-orange-50">
                                            <span x-text="advanced ? @js(__('Hide enrollment details')) : @js(__('Show enrollment details'))"></span>
                                        </button>
                                        <div x-show="advanced" x-transition class="mt-4 grid gap-4 rounded-xl border border-slate-200 bg-slate-50 p-4 sm:grid-cols-2">
                                            <div>
                                                <label for="enrollment_status" class="{{ $labelClass }}">{{ __('Enrollment status') }}</label>
                                                <select id="enrollment_status" name="enrollment_status" required class="{{ $selectClass }}">
                                                    <option value="enrolled" @selected(old('enrollment_status', 'enrolled') === 'enrolled')>{{ __('Enrolled') }}</option>
                                                    <option value="pending" @selected(old('enrollment_status') === 'pending')>{{ __('Pending') }}</option>
                                                    <option value="completed" @selected(old('enrollment_status') === 'completed')>{{ __('Completed') }}</option>
                                                    <option value="dropped" @selected(old('enrollment_status') === 'dropped')>{{ __('Dropped') }}</option>
                                                </select>
                                                <x-input-error :messages="$errors->get('enrollment_status')" class="mt-2" />
                                            </div>
                                            <div>
                                                <label for="enrolled_at" class="{{ $labelClass }}">{{ __('Enrolled at') }}</label>
                                                <input id="enrolled_at" type="datetime-local" name="enrolled_at" value="{{ old('enrolled_at') }}" class="{{ $inputClass }}">
                                                <p class="form-hint mt-2">{{ __('Leave empty to use the current time.') }}</p>
                                                <x-input-error :messages="$errors->get('enrolled_at')" class="mt-2" />
                                            </div>
                                        </div>
                                    </div>

                                    <button @disabled($students->isEmpty() || $courses->isEmpty()) class="inline-flex min-h-11 w-fit items-center rounded-xl bg-orange-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-orange-700 disabled:cursor-not-allowed disabled:opacity-60">
                                        {{ __('Enroll student') }}
                                    </button>
                                </form>
                            </section>
                        </div>
                    </div>

                    <div id="academic-panel-assignments" role="tabpanel" x-show="tab === 'assignments'" x-transition x-bind:aria-hidden="tab === 'assignments' ? 'false' : 'true'">
                        <div class="mb-5">
                            <h3 class="text-xl font-semibold text-slate-950">{{ __('Assign exams with fewer mistakes') }}</h3>
                            <p class="mt-1 text-sm text-slate-600">
                                {{ __('Only published exams can be assigned. If you choose a specific student, that student must already be enrolled in the selected course.') }}
                            </p>
                        </div>

                        <section class="{{ $panelClass }}">
                            <form method="POST" action="{{ route('academics.exam-assignments.store') }}" class="grid gap-5">
                                @csrf
                                <input type="hidden" name="_academic_tab" value="assignments">

                                <div class="grid gap-4 lg:grid-cols-3">
                                    <div>
                                        <label for="assignment_exam_id" class="{{ $labelClass }}">{{ __('Published exam') }}</label>
                                        <select id="assignment_exam_id" name="instructor_exam_id" required class="{{ $selectClass }}">
                                            <option value="">{{ __('Select exam') }}</option>
                                            @foreach ($exams as $exam)
                                                <option value="{{ $exam->id }}" @selected((string) old('instructor_exam_id') === (string) $exam->id)>{{ $exam->title }} - {{ $exam->course?->code }}</option>
                                            @endforeach
                                        </select>
                                        <p class="form-hint mt-2">{{ __('Publish an exam first if it does not appear here.') }}</p>
                                        <x-input-error :messages="$errors->get('instructor_exam_id')" class="mt-2" />
                                    </div>
                                    <div>
                                        <label for="assignment_course_id" class="{{ $labelClass }}">{{ __('Course') }}</label>
                                        <select id="assignment_course_id" name="course_id" required class="{{ $selectClass }}">
                                            <option value="">{{ __('Select course') }}</option>
                                            @foreach ($courses as $course)
                                                <option value="{{ $course->id }}" @selected((string) old('course_id') === (string) $course->id)>{{ $course->code }} - {{ $course->name }}</option>
                                            @endforeach
                                        </select>
                                        <p class="form-hint mt-2">{{ __('The course must match the selected exam course.') }}</p>
                                        <x-input-error :messages="$errors->get('course_id')" class="mt-2" />
                                    </div>
                                    <div>
                                        <label for="assignment_student_id" class="{{ $labelClass }}">{{ __('Specific student') }}</label>
                                        <select id="assignment_student_id" name="student_profile_id" class="{{ $selectClass }}">
                                            <option value="">{{ __('Whole course') }}</option>
                                            @foreach ($students as $student)
                                                <option value="{{ $student->id }}" @selected((string) old('student_profile_id') === (string) $student->id)>{{ $student->user?->name }} - {{ $student->student_number }}</option>
                                            @endforeach
                                        </select>
                                        <p class="form-hint mt-2">{{ __('Leave empty to assign the exam to all enrolled students in the course.') }}</p>
                                        <x-input-error :messages="$errors->get('student_profile_id')" class="mt-2" />
                                    </div>
                                </div>

                                <div x-data="{ advanced: {{ old('available_at') || old('due_at') || old('max_attempts') !== null || old('status') || old('show_score_to_student') || old('show_feedback_to_student') ? 'true' : 'false' }} }">
                                    <button type="button" x-on:click="advanced = ! advanced" class="inline-flex min-h-11 items-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-orange-50">
                                        <span x-text="advanced ? @js(__('Hide schedule and visibility')) : @js(__('Show schedule and visibility'))"></span>
                                    </button>

                                    <div x-show="advanced" x-transition class="mt-4 grid gap-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                                        <div class="grid gap-4 lg:grid-cols-4">
                                            <div>
                                                <label for="available_at" class="{{ $labelClass }}">{{ __('Available at') }}</label>
                                                <input id="available_at" type="datetime-local" name="available_at" value="{{ old('available_at') }}" class="{{ $inputClass }}">
                                                <x-input-error :messages="$errors->get('available_at')" class="mt-2" />
                                            </div>
                                            <div>
                                                <label for="due_at" class="{{ $labelClass }}">{{ __('Due at') }}</label>
                                                <input id="due_at" type="datetime-local" name="due_at" value="{{ old('due_at') }}" class="{{ $inputClass }}">
                                                <p class="form-hint mt-2">{{ __('Due time must be the same as or later than available time.') }}</p>
                                                <x-input-error :messages="$errors->get('due_at')" class="mt-2" />
                                            </div>
                                            <div>
                                                <label for="max_attempts" class="{{ $labelClass }}">{{ __('Max attempts') }}</label>
                                                <input id="max_attempts" type="number" name="max_attempts" value="{{ old('max_attempts', 1) }}" min="1" max="20" required class="{{ $inputClass }}">
                                                <x-input-error :messages="$errors->get('max_attempts')" class="mt-2" />
                                            </div>
                                            <div>
                                                <label for="assignment_status" class="{{ $labelClass }}">{{ __('Status') }}</label>
                                                <select id="assignment_status" name="status" required class="{{ $selectClass }}">
                                                    <option value="assigned" @selected(old('status', 'assigned') === 'assigned')>{{ __('Assigned') }}</option>
                                                    <option value="open" @selected(old('status', 'assigned') === 'open')>{{ __('Open') }}</option>
                                                    <option value="closed" @selected(old('status', 'assigned') === 'closed')>{{ __('Closed') }}</option>
                                                    <option value="cancelled" @selected(old('status', 'assigned') === 'cancelled')>{{ __('Cancelled') }}</option>
                                                </select>
                                                <x-input-error :messages="$errors->get('status')" class="mt-2" />
                                            </div>
                                        </div>

                                        <div class="grid gap-3 sm:grid-cols-2">
                                            @foreach ($assignmentFeatures as $key => $feature)
                                                <label class="flex min-h-11 items-start gap-3 rounded-xl border border-slate-200 bg-white px-3 py-3 text-sm text-slate-700">
                                                    <input type="checkbox" name="{{ $key }}" value="1" @checked(old($key, false))
                                                        class="mt-1 rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                                                    <span>
                                                        <span class="block font-semibold text-slate-900">{{ $feature['label'] }}</span>
                                                        <span class="mt-1 block text-xs leading-5 text-slate-500">{{ $feature['description'] }}</span>
                                                    </span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <button @disabled($exams->isEmpty() || $courses->isEmpty()) class="inline-flex min-h-11 w-fit items-center rounded-xl bg-orange-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-orange-700 disabled:cursor-not-allowed disabled:opacity-60">
                                    {{ __('Create assignment') }}
                                </button>
                            </form>
                        </section>
                    </div>

                    <div id="academic-panel-review" role="tabpanel" x-show="tab === 'review'" x-transition x-bind:aria-hidden="tab === 'review' ? 'false' : 'true'">
                        <div class="mb-5">
                            <h3 class="text-xl font-semibold text-slate-950">{{ __('Review and manage academic records') }}</h3>
                            <p class="mt-1 text-sm text-slate-600">{{ __('Search each table locally to find the record you need without leaving the page.') }}</p>
                        </div>

                        <div class="space-y-5">
                            <details open class="rounded-xl border border-slate-200 bg-white shadow-sm">
                                <summary class="cursor-pointer list-none border-b border-slate-100 p-5">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <h4 class="text-lg font-semibold text-slate-950">{{ __('Students') }}</h4>
                                            <p class="mt-1 text-sm text-slate-600">{{ __('Profiles, majors, course counts, and status.') }}</p>
                                        </div>
                                        <input data-table-filter="#academic-students-table" type="search" placeholder="{{ __('Search students') }}" class="min-h-11 rounded-xl border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:w-72">
                                    </div>
                                </summary>
                                <div class="table-comfort overflow-x-auto p-4">
                                    <table id="academic-students-table" class="min-w-full text-left text-sm">
                                        <thead class="bg-slate-100 text-xs uppercase text-slate-600">
                                            <tr>
                                                <th class="px-4 py-3">{{ __('Student') }}</th>
                                                <th class="px-4 py-3">{{ __('Major') }}</th>
                                                <th class="px-4 py-3">{{ __('Courses') }}</th>
                                                <th class="px-4 py-3">{{ __('Status') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            @forelse ($students as $student)
                                                <tr data-filter-row>
                                                    <td class="px-4 py-3">
                                                        <p class="font-semibold text-slate-950">{{ $student->user?->name }}</p>
                                                        <p class="text-xs text-slate-500">{{ $student->student_number }}</p>
                                                    </td>
                                                    <td class="px-4 py-3">{{ $student->major?->code ?? __('Not assigned') }}</td>
                                                    <td class="px-4 py-3">{{ $student->courses->count() }}</td>
                                                    <td class="px-4 py-3">
                                                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold capitalize text-slate-800">{{ $student->academic_status }}</span>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="px-4 py-8">
                                                        <div class="empty-state text-center">
                                                            <strong class="block">{{ __('No students yet.') }}</strong>
                                                            <span class="mt-1 block text-sm">{{ __('Create student profiles from the setup tab.') }}</span>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforelse
                                            <tr data-filter-empty hidden>
                                                <td colspan="4" class="px-4 py-8">
                                                    <div class="empty-state text-center">{{ __('No students match your search.') }}</div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </details>

                            <details class="rounded-xl border border-slate-200 bg-white shadow-sm">
                                <summary class="cursor-pointer list-none border-b border-slate-100 p-5">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <h4 class="text-lg font-semibold text-slate-950">{{ __('Courses and majors') }}</h4>
                                            <p class="mt-1 text-sm text-slate-600">{{ __('Course catalog, linked programs, and enrollments.') }}</p>
                                        </div>
                                        <input data-table-filter="#academic-courses-table" type="search" placeholder="{{ __('Search courses') }}" class="min-h-11 rounded-xl border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:w-72">
                                    </div>
                                </summary>
                                <div class="table-comfort overflow-x-auto p-4">
                                    <table id="academic-courses-table" class="min-w-full text-left text-sm">
                                        <thead class="bg-slate-100 text-xs uppercase text-slate-600">
                                            <tr>
                                                <th class="px-4 py-3">{{ __('Course') }}</th>
                                                <th class="px-4 py-3">{{ __('Majors') }}</th>
                                                <th class="px-4 py-3">{{ __('Students') }}</th>
                                                <th class="px-4 py-3">{{ __('Status') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            @forelse ($courses as $course)
                                                <tr data-filter-row>
                                                    <td class="px-4 py-3">
                                                        <p class="font-semibold text-slate-950">{{ $course->code }}</p>
                                                        <p class="text-xs text-slate-500">{{ $course->name }}</p>
                                                    </td>
                                                    <td class="px-4 py-3">{{ $course->majors->pluck('code')->join(', ') ?: __('Not assigned') }}</td>
                                                    <td class="px-4 py-3">{{ $course->students->count() }}</td>
                                                    <td class="px-4 py-3">{{ $course->is_active ? __('Active') : __('Inactive') }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="px-4 py-8">
                                                        <div class="empty-state text-center">{{ __('No courses yet.') }}</div>
                                                    </td>
                                                </tr>
                                            @endforelse
                                            <tr data-filter-empty hidden>
                                                <td colspan="4" class="px-4 py-8">
                                                    <div class="empty-state text-center">{{ __('No courses match your search.') }}</div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </details>

                            <details class="rounded-xl border border-slate-200 bg-white shadow-sm">
                                <summary class="cursor-pointer list-none border-b border-slate-100 p-5">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <h4 class="text-lg font-semibold text-slate-950">{{ __('Exam assignments') }}</h4>
                                            <p class="mt-1 text-sm text-slate-600">{{ __('Course-wide and student-specific exam availability.') }}</p>
                                        </div>
                                        <input data-table-filter="#academic-assignments-table" type="search" placeholder="{{ __('Search assignments') }}" class="min-h-11 rounded-xl border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:w-72">
                                    </div>
                                </summary>
                                <div class="table-comfort overflow-x-auto p-4">
                                    <table id="academic-assignments-table" class="min-w-full text-left text-sm">
                                        <thead class="bg-slate-100 text-xs uppercase text-slate-600">
                                            <tr>
                                                <th class="px-4 py-3">{{ __('Exam') }}</th>
                                                <th class="px-4 py-3">{{ __('Course') }}</th>
                                                <th class="px-4 py-3">{{ __('Student') }}</th>
                                                <th class="px-4 py-3">{{ __('Window') }}</th>
                                                <th class="px-4 py-3">{{ __('Attempts') }}</th>
                                                <th class="px-4 py-3">{{ __('Status') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            @forelse ($assignments as $assignment)
                                                <tr data-filter-row>
                                                    <td class="px-4 py-3 font-semibold text-slate-950">{{ $assignment->exam?->title }}</td>
                                                    <td class="px-4 py-3">{{ $assignment->course?->code }}</td>
                                                    <td class="px-4 py-3">{{ $assignment->student?->user?->name ?? __('Whole course') }}</td>
                                                    <td class="px-4 py-3 text-xs text-slate-600">
                                                        {{ $assignment->available_at?->format('M j, Y H:i') ?? __('Any time') }}
                                                        <span class="text-slate-400">{{ __('to') }}</span>
                                                        {{ $assignment->due_at?->format('M j, Y H:i') ?? __('No due date') }}
                                                    </td>
                                                    <td class="px-4 py-3">{{ $assignment->max_attempts }}</td>
                                                    <td class="px-4 py-3">
                                                        <span class="rounded-full px-3 py-1 text-xs font-semibold capitalize {{ $assignmentStatusClass[$assignment->status] ?? 'bg-slate-100 text-slate-800' }}">{{ str_replace('_', ' ', $assignment->status) }}</span>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="px-4 py-8">
                                                        <div class="empty-state text-center">{{ __('No exam assignments yet.') }}</div>
                                                    </td>
                                                </tr>
                                            @endforelse
                                            <tr data-filter-empty hidden>
                                                <td colspan="6" class="px-4 py-8">
                                                    <div class="empty-state text-center">{{ __('No assignments match your search.') }}</div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </details>

                            <details class="rounded-xl border border-slate-200 bg-white shadow-sm">
                                <summary class="cursor-pointer list-none border-b border-slate-100 p-5">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <h4 class="text-lg font-semibold text-slate-950">{{ __('Exam sessions') }}</h4>
                                            <p class="mt-1 text-sm text-slate-600">{{ __('Manage attempts after students start exams.') }}</p>
                                        </div>
                                        <input data-table-filter="#academic-sessions-table" type="search" placeholder="{{ __('Search sessions') }}" class="min-h-11 rounded-xl border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:w-72">
                                    </div>
                                </summary>
                                <div class="table-comfort overflow-x-auto p-4">
                                    <table id="academic-sessions-table" class="min-w-full text-left text-sm">
                                        <thead class="bg-slate-100 text-xs uppercase text-slate-600">
                                            <tr>
                                                <th class="px-4 py-3">{{ __('Student') }}</th>
                                                <th class="px-4 py-3">{{ __('Exam') }}</th>
                                                <th class="px-4 py-3">{{ __('Attempt') }}</th>
                                                <th class="px-4 py-3">{{ __('Timing') }}</th>
                                                <th class="px-4 py-3">{{ __('Score') }}</th>
                                                <th class="px-4 py-3">{{ __('Status') }}</th>
                                                <th class="px-4 py-3">{{ __('Manage') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            @forelse ($sessions as $session)
                                                <tr data-filter-row>
                                                    <td class="px-4 py-3">{{ $session->student?->user?->name }}</td>
                                                    <td class="px-4 py-3">{{ $session->assignment?->exam?->title }}</td>
                                                    <td class="px-4 py-3">{{ $session->attempt_number }}</td>
                                                    <td class="px-4 py-3 text-xs text-slate-600">
                                                        {{ __('Started') }}: {{ $session->started_at?->format('M j, Y H:i') ?? __('Not started') }}<br>
                                                        {{ __('Expires') }}: {{ $session->expires_at?->format('M j, Y H:i') ?? __('No limit') }}
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        {{ $session->score ?? '-' }}
                                                        @if ($session->percentage !== null)
                                                            <span class="text-slate-500">({{ $session->percentage }}%)</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        <span class="rounded-full px-3 py-1 text-xs font-semibold capitalize {{ $sessionStatusClass[$session->status] ?? 'bg-slate-100 text-slate-800' }}">{{ str_replace('_', ' ', $session->status) }}</span>
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        <form method="POST" action="{{ route('academics.exam-sessions.update', $session) }}" class="flex min-w-64 gap-2">
                                                            @csrf
                                                            @method('PATCH')
                                                            <input type="hidden" name="_academic_tab" value="review">
                                                            <select name="status" class="min-h-11 rounded-xl border-slate-300 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                                                <option value="in_progress" @selected($session->status === 'in_progress')>{{ __('In progress') }}</option>
                                                                <option value="submitted" @selected($session->status === 'submitted')>{{ __('Submitted') }}</option>
                                                                <option value="expired" @selected($session->status === 'expired')>{{ __('Expired') }}</option>
                                                                <option value="cancelled" @selected($session->status === 'cancelled')>{{ __('Cancelled') }}</option>
                                                            </select>
                                                            <button class="inline-flex min-h-11 items-center rounded-xl bg-slate-900 px-4 py-2 text-xs font-semibold text-white hover:bg-slate-700">
                                                                {{ __('Save') }}
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="7" class="px-4 py-8">
                                                        <div class="empty-state text-center">{{ __('No sessions yet. Sessions appear after students start an assigned exam.') }}</div>
                                                    </td>
                                                </tr>
                                            @endforelse
                                            <tr data-filter-empty hidden>
                                                <td colspan="7" class="px-4 py-8">
                                                    <div class="empty-state text-center">{{ __('No sessions match your search.') }}</div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </details>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
