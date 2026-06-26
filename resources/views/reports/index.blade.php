@php
    $inputClass = 'min-h-11 rounded-lg border-slate-300 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500';
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-orange-600">{{ __('Reports') }}</p>
                <h2 class="mt-1 text-2xl font-semibold text-slate-950">{{ __($report['name']) }}</h2>
                <p class="mt-2 max-w-3xl text-sm text-slate-600">{{ __($report['description']) }}</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:grid-cols-[18rem_1fr] lg:px-8">
            <aside class="space-y-2">
                @foreach ($reports as $key => $item)
                    <a href="{{ route('reports.show', $key) }}"
                        class="block rounded-lg border px-4 py-3 text-sm transition {{ $currentReport === $key ? 'border-orange-300 bg-orange-50 text-slate-950' : 'border-slate-200 bg-white text-slate-700 hover:border-orange-200' }}">
                        <span class="font-semibold">{{ __($item['name']) }}</span>
                    </a>
                @endforeach
            </aside>

            <main class="space-y-6">
                <form method="GET" action="{{ route('reports.show', $currentReport) }}" class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="grid gap-3 md:grid-cols-4">
                        <select name="course_id" class="{{ $inputClass }}">
                            <option value="">{{ __('All assigned courses') }}</option>
                            @foreach ($courses as $course)
                                <option value="{{ $course->id }}" @selected((int) $filters['courseId'] === (int) $course->id)>{{ $course->code }} - {{ $course->name }}</option>
                            @endforeach
                        </select>
                        <select name="exam_id" class="{{ $inputClass }}">
                            <option value="">{{ __('All assigned exams') }}</option>
                            @foreach ($exams as $exam)
                                <option value="{{ $exam->id }}" @selected((int) $filters['examId'] === (int) $exam->id)>{{ $exam->title }}</option>
                            @endforeach
                        </select>
                        <input name="q" value="{{ $filters['search'] }}" placeholder="{{ __('Search report') }}" class="{{ $inputClass }}">
                        <button class="inline-flex min-h-11 items-center justify-center rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">
                            {{ __('Filter') }}
                        </button>
                    </div>
                </form>

                <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    @foreach ($cards as $card)
                        <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                            <p class="text-sm font-medium text-slate-500">{{ __($card['label']) }}</p>
                            <p class="mt-2 text-3xl font-semibold text-slate-950">{{ $card['value'] }}</p>
                        </article>
                    @endforeach
                </section>

                <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
                    <div class="flex flex-col gap-3 border-b border-slate-100 p-5 sm:flex-row sm:items-center sm:justify-between">
                        <h3 class="text-lg font-semibold text-slate-950">{{ __('Report Details') }}</h3>
                        <input data-table-filter="#reports-table" type="search" placeholder="{{ __('Search visible rows') }}" class="{{ $inputClass }} sm:w-72">
                    </div>
                    <div class="overflow-x-auto p-4">
                        <table id="reports-table" class="min-w-full text-left text-sm">
                            <thead class="bg-slate-100 text-xs uppercase text-slate-600">
                                <tr>
                                    @foreach ($headers as $header)
                                        <th class="px-4 py-3">{{ __($header) }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($rows as $row)
                                    <tr data-filter-row>
                                        @foreach ($row as $cell)
                                            <td class="px-4 py-3 text-slate-700">{{ $cell }}</td>
                                        @endforeach
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ count($headers) }}" class="px-4 py-8 text-center text-sm text-slate-500">
                                            {{ __('No assigned records are available for this report.') }}
                                        </td>
                                    </tr>
                                @endforelse
                                <tr data-filter-empty hidden>
                                    <td colspan="{{ count($headers) }}" class="px-4 py-8 text-center text-sm text-slate-500">
                                        {{ __('No rows match your search.') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </main>
        </div>
    </div>
</x-app-layout>
