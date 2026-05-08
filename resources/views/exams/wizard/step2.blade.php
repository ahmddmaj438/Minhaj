<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-900 leading-tight">Create Exam - Step 2 of 3</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white/95 shadow-sm rounded-xl border border-orange-100 p-6">
                <h3 class="text-lg font-semibold text-slate-900 mb-4">Question Rules</h3>
                <form method="POST" action="{{ route('exam.wizard.step2.store') }}" class="grid gap-5 md:grid-cols-2">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Question Type</label>
                        <input type="number" name="question_type" value="{{ old('question_type', 1) }}" class="block w-full border-slate-300 rounded-md shadow-sm focus:border-orange-500 focus:ring-orange-400" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Difficulty Level</label>
                        <input type="number" name="difficulty" value="{{ old('difficulty', 1) }}" class="block w-full border-slate-300 rounded-md shadow-sm focus:border-orange-500 focus:ring-orange-400" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Number of Questions</label>
                        <input type="number" name="questions_count" value="{{ old('questions_count', 10) }}" class="block w-full border-slate-300 rounded-md shadow-sm focus:border-orange-500 focus:ring-orange-400" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Answers Per Question</label>
                        <input type="number" name="answers_per_question" value="{{ old('answers_per_question', 4) }}" class="block w-full border-slate-300 rounded-md shadow-sm focus:border-orange-500 focus:ring-orange-400" required>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Module (Optional)</label>
                        <select name="module_id" class="block w-full border-slate-300 rounded-md shadow-sm focus:border-orange-500 focus:ring-orange-400">
                            <option value="">All Modules</option>
                            @foreach ($modules as $module)
                                <option value="{{ $module->module_id }}" @selected(old('module_id') == $module->module_id)>{{ $module->module_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2 flex gap-3">
                        <a href="{{ route('exam.wizard.step1') }}" class="px-4 py-2 rounded border border-orange-200 text-slate-700 hover:bg-orange-50">Back</a>
                        <button class="px-4 py-2 rounded bg-orange-500 text-white hover:bg-orange-600">Next Step</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

