@php
    $inputClass = 'mt-2 block min-h-11 w-full rounded-xl border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500';
    $labelClass = 'block text-sm font-semibold text-slate-800';
    $panelClass = 'rounded-xl border border-slate-200 bg-white p-6 shadow-sm';
    $importRows = collect($importResult['rows'] ?? $importPreview['rows'] ?? []);
    $importSummary = $importResult['summary'] ?? $importPreview['summary'] ?? null;
    $importStatusClass = [
        'ready' => 'bg-blue-100 text-blue-800',
        'successful' => 'bg-emerald-100 text-emerald-800',
        'skipped' => 'bg-amber-100 text-amber-900',
        'failed' => 'bg-red-100 text-red-800',
    ];
    $importStatusLabel = [
        'ready' => __('Ready to save'),
        'successful' => __('Saved'),
        'skipped' => __('Skipped'),
        'failed' => __('Needs attention'),
    ];
    $importTypeLabel = [
        'program' => __('Program information'),
        'course' => __('Course information'),
        'program_course' => __('Program-course link'),
        'student' => __('Student account'),
        'exam' => __('Exam setup'),
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-orange-600">{{ __('Secondary setup option') }}</p>
                <h2 class="mt-1 text-2xl font-semibold leading-tight text-slate-950">{{ __('Academic Excel Upload') }}</h2>
                <p class="mt-2 max-w-2xl text-sm text-slate-600">
                    {{ __('Use this only when you already have academic setup data prepared in a spreadsheet. The normal forms remain the primary way to manage academic data.') }}
                </p>
            </div>
            <a href="{{ route('academics.index') }}"
                class="inline-flex min-h-11 items-center justify-center rounded-xl border border-orange-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-orange-50">
                {{ __('Back to academic forms') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
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

            <div class="grid gap-6 xl:grid-cols-[0.85fr_1.15fr]">
                <section class="{{ $panelClass }}">
                    <div class="border-b border-slate-100 pb-4">
                        <p class="text-sm font-semibold text-orange-700">{{ __('Template and instructions') }}</p>
                        <h3 class="mt-1 text-lg font-semibold text-slate-950">{{ __('Prepare academic data in one file') }}</h3>
                    </div>

                    <div class="mt-5 space-y-4 text-sm leading-6 text-slate-700">
                        <p>{{ __('Download the template and keep the sheet names and column names unchanged.') }}</p>
                        <ul class="list-disc space-y-2 ps-5">
                            <li>{{ __('Programs: program code, name, description, and active status.') }}</li>
                            <li>{{ __('Courses: course code, name, description, and active status.') }}</li>
                            <li>{{ __('Program Courses: connect courses to programs and recommended levels.') }}</li>
                            <li>{{ __('Students: create student login accounts and academic profiles.') }}</li>
                            <li>{{ __('Exam Setup: prepare exam title, course, duration, marks, optional dates, and publish choice.') }}</li>
                        </ul>
                        <p>{{ __('The preview checks required columns, duplicate rows, missing program or course references, email format, and number fields before anything is saved.') }}</p>
                    </div>

                    <a href="{{ route('academics.upload.template') }}"
                        class="mt-5 inline-flex min-h-11 items-center rounded-xl bg-slate-900 px-5 py-2 text-sm font-semibold text-white hover:bg-slate-700">
                        {{ __('Download Excel template') }}
                    </a>
                </section>

                <section class="{{ $panelClass }}">
                    <div class="border-b border-slate-100 pb-4">
                        <p class="text-sm font-semibold text-orange-700">{{ __('Upload and preview') }}</p>
                        <h3 class="mt-1 text-lg font-semibold text-slate-950">{{ __('Check academic setup file') }}</h3>
                        <p class="mt-1 text-sm text-slate-600">{{ __('The system will show what can be saved, skipped, or needs correction.') }}</p>
                    </div>

                    <form method="POST" action="{{ route('academics.upload.preview') }}" enctype="multipart/form-data" class="mt-5 grid gap-4">
                        @csrf

                        <div>
                            <label for="academic_file" class="{{ $labelClass }}">{{ __('Academic setup file') }}</label>
                            <input id="academic_file" type="file" name="academic_file" accept=".xlsx,.csv" required
                                class="{{ $inputClass }} file:me-4 file:rounded-lg file:border-0 file:bg-orange-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-orange-700">
                            <p class="mt-2 text-xs text-slate-500">{{ __('Accepted formats: .xlsx or .csv. Maximum size: 20 MB.') }}</p>
                            <x-input-error :messages="$errors->get('academic_file')" class="mt-2" />
                        </div>

                        <button class="inline-flex min-h-11 w-fit items-center rounded-xl bg-orange-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-orange-700">
                            {{ __('Preview uploaded data') }}
                        </button>
                    </form>
                </section>
            </div>

            @if ($importSummary)
                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <p class="text-sm font-semibold text-orange-700">{{ isset($importResult) ? __('Import result summary') : __('Preview summary') }}</p>
                            <h3 class="mt-1 text-lg font-semibold text-slate-950">{{ __('Review row results') }}</h3>
                            <p class="mt-1 text-sm text-slate-600">{{ __('Rows that need attention must be fixed in the file and uploaded again.') }}</p>
                        </div>

                        @if (! isset($importResult) && ($importSummary['successful'] ?? 0) > 0)
                            <form method="POST" action="{{ route('academics.upload.confirm') }}">
                                @csrf
                                <button class="inline-flex min-h-11 items-center rounded-xl bg-emerald-700 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-800">
                                    {{ __('Save ready academic data') }}
                                </button>
                            </form>
                        @endif
                    </div>

                    <div class="mt-5 grid gap-4 sm:grid-cols-3">
                        <div class="rounded-lg border border-emerald-100 bg-emerald-50 p-4">
                            <p class="text-sm font-medium text-emerald-800">{{ __('Successful records') }}</p>
                            <p class="mt-2 text-2xl font-semibold text-emerald-950">{{ number_format($importSummary['successful'] ?? 0) }}</p>
                        </div>
                        <div class="rounded-lg border border-amber-100 bg-amber-50 p-4">
                            <p class="text-sm font-medium text-amber-900">{{ __('Skipped records') }}</p>
                            <p class="mt-2 text-2xl font-semibold text-amber-950">{{ number_format($importSummary['skipped'] ?? 0) }}</p>
                        </div>
                        <div class="rounded-lg border border-red-100 bg-red-50 p-4">
                            <p class="text-sm font-medium text-red-800">{{ __('Failed records') }}</p>
                            <p class="mt-2 text-2xl font-semibold text-red-950">{{ number_format($importSummary['failed'] ?? 0) }}</p>
                        </div>
                    </div>

                    <div class="mt-5 overflow-x-auto">
                        <table class="min-w-full text-left text-sm">
                            <thead class="bg-slate-100 text-xs uppercase text-slate-600">
                                <tr>
                                    <th class="px-4 py-3">{{ __('Worksheet') }}</th>
                                    <th class="px-4 py-3">{{ __('Row') }}</th>
                                    <th class="px-4 py-3">{{ __('Information type') }}</th>
                                    <th class="px-4 py-3">{{ __('Status') }}</th>
                                    <th class="px-4 py-3">{{ __('Reason') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($importRows as $row)
                                    <tr>
                                        <td class="px-4 py-3 font-semibold text-slate-950">{{ $row['sheet'] }}</td>
                                        <td class="px-4 py-3">{{ $row['number'] }}</td>
                                        <td class="px-4 py-3">{{ $importTypeLabel[$row['type']] ?? __('Academic information') }}</td>
                                        <td class="px-4 py-3">
                                            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $importStatusClass[$row['status']] ?? 'bg-slate-100 text-slate-800' }}">
                                                {{ $importStatusLabel[$row['status']] ?? ucfirst($row['status']) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-slate-600">{{ $row['message'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-8">
                                            <div class="empty-state text-center">{{ __('No rows were found in the uploaded file.') }}</div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
