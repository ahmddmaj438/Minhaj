<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-900 leading-tight">Create Exam - Step 3 of 3</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white/95 shadow-sm rounded-xl border border-orange-100 p-6">
                <h3 class="text-lg font-semibold text-slate-900 mb-4">Select Subjects</h3>
                <form method="POST" action="{{ route('exam.wizard.finish') }}">
                    @csrf
                    <div class="grid gap-3 md:grid-cols-2">
                        @foreach ($subjects as $subject)
                            <label class="flex items-center gap-2 p-3 border border-orange-100 rounded-lg hover:bg-orange-50">
                                <input type="checkbox" name="subject_ids[]" value="{{ $subject->subject_id }}" class="rounded border-slate-300 text-orange-500 focus:ring-orange-400">
                                <span class="text-sm text-slate-800">{{ $subject->subject_name }}</span>
                            </label>
                        @endforeach
                    </div>
                    <div class="mt-5 flex gap-3">
                        <a href="{{ route('exam.wizard.step2') }}" class="px-4 py-2 rounded border border-orange-200 text-slate-700 hover:bg-orange-50">Back</a>
                        <button class="px-4 py-2 rounded bg-orange-500 text-white hover:bg-orange-600">Create Exam</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

