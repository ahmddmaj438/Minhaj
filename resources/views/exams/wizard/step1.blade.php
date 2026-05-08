<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-900 leading-tight">Create Exam - Step 1 of 3</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white/95 shadow-sm rounded-xl border border-orange-100 p-6">
                <h3 class="text-lg font-semibold text-slate-900 mb-4">Exam Information</h3>
                <form method="POST" action="{{ route('exam.wizard.step1.store') }}" class="grid gap-5 md:grid-cols-2">
                    @csrf
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Exam Name</label>
                        <input name="exam_name" value="{{ old('exam_name') }}" class="block w-full border-slate-300 rounded-md shadow-sm focus:border-orange-500 focus:ring-orange-400" required>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
                        <textarea name="description" rows="4" class="block w-full border-slate-300 rounded-md shadow-sm focus:border-orange-500 focus:ring-orange-400" required>{{ old('description') }}</textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Duration (minutes)</label>
                        <input type="number" name="duration_minutes" value="{{ old('duration_minutes', 60) }}" class="block w-full border-slate-300 rounded-md shadow-sm focus:border-orange-500 focus:ring-orange-400" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Pass Threshold</label>
                        <input type="number" step="0.01" name="pass_threshold" value="{{ old('pass_threshold', 50) }}" class="block w-full border-slate-300 rounded-md shadow-sm focus:border-orange-500 focus:ring-orange-400" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Start Date & Time</label>
                        <input type="datetime-local" name="start_at" value="{{ old('start_at') }}" class="block w-full border-slate-300 rounded-md shadow-sm focus:border-orange-500 focus:ring-orange-400">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">End Date & Time</label>
                        <input type="datetime-local" name="end_at" value="{{ old('end_at') }}" class="block w-full border-slate-300 rounded-md shadow-sm focus:border-orange-500 focus:ring-orange-400">
                    </div>
                    <div class="md:col-span-2">
                        <button class="px-4 py-2 rounded bg-orange-500 text-white hover:bg-orange-600">Next Step</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

