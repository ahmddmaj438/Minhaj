<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-medium text-orange-600">Academic operations</p>
                <h2 class="text-2xl font-semibold leading-tight text-slate-950">Students, Courses, Assignments</h2>
            </div>
            <div class="text-sm text-slate-500">Manage the student exam flow</div>
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
                    <p class="font-semibold">Please review the form errors.</p>
                    <ul class="mt-2 list-disc space-y-1 ps-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section class="mb-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">Majors</p>
                    <p class="mt-2 text-3xl font-semibold text-slate-950">{{ number_format($majors->count()) }}</p>
                </article>
                <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">Students</p>
                    <p class="mt-2 text-3xl font-semibold text-slate-950">{{ number_format($students->count()) }}</p>
                </article>
                <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">Courses</p>
                    <p class="mt-2 text-3xl font-semibold text-slate-950">{{ number_format($courses->count()) }}</p>
                </article>
                <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">Assignments</p>
                    <p class="mt-2 text-3xl font-semibold text-slate-950">{{ number_format($assignments->count()) }}</p>
                </article>
            </section>

            <div class="grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
                <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="border-b border-slate-100 pb-5">
                        <p class="text-sm font-semibold uppercase tracking-wide text-orange-600">Step 1</p>
                        <h3 class="mt-1 text-lg font-semibold text-slate-950">Create a major</h3>
                    </div>

                    <form method="POST" action="{{ route('academics.majors.store') }}" class="mt-6 grid gap-4">
                        @csrf
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="major_code" class="block text-sm font-medium text-slate-800">Code</label>
                                <input id="major_code" name="code" value="{{ old('code') }}" placeholder="CS"
                                    class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                            </div>
                            <div>
                                <label for="major_name" class="block text-sm font-medium text-slate-800">Name</label>
                                <input id="major_name" name="name" value="{{ old('name') }}" placeholder="Computer Science"
                                    class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                            </div>
                        </div>
                        <div>
                            <label for="major_description" class="block text-sm font-medium text-slate-800">Description</label>
                            <textarea id="major_description" name="description" rows="3"
                                class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">{{ old('description') }}</textarea>
                        </div>
                        <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
                            <input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                            Active major
                        </label>
                        <div>
                            <button class="inline-flex items-center rounded-md bg-orange-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-orange-700">
                                Create major
                            </button>
                        </div>
                    </form>
                </section>

                <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="border-b border-slate-100 pb-5">
                        <p class="text-sm font-semibold uppercase tracking-wide text-orange-600">Step 2</p>
                        <h3 class="mt-1 text-lg font-semibold text-slate-950">Make a Laravel user a student</h3>
                    </div>

                    <form method="POST" action="{{ route('academics.students.store') }}" class="mt-6 grid gap-4">
                        @csrf
                        <div>
                            <label for="student_user_id" class="block text-sm font-medium text-slate-800">User account</label>
                            <select id="student_user_id" name="user_id"
                                class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                <option value="">Select a user without student profile</option>
                                @foreach ($studentUsers as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }} - {{ $user->email }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-3">
                            <div>
                                <label for="student_number" class="block text-sm font-medium text-slate-800">Student number</label>
                                <input id="student_number" name="student_number" value="{{ old('student_number') }}"
                                    class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                            </div>
                            <div>
                                <label for="student_major_id" class="block text-sm font-medium text-slate-800">Major</label>
                                <select id="student_major_id" name="major_id"
                                    class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                    <option value="">No major yet</option>
                                    @foreach ($majors as $major)
                                        <option value="{{ $major->id }}">{{ $major->code }} - {{ $major->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="admission_year" class="block text-sm font-medium text-slate-800">Admission year</label>
                                <input id="admission_year" type="number" name="admission_year" min="1990" max="2100" value="{{ old('admission_year') }}"
                                    class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                            </div>
                        </div>
                        <div>
                            <label for="academic_status" class="block text-sm font-medium text-slate-800">Status</label>
                            <select id="academic_status" name="academic_status"
                                class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="graduated">Graduated</option>
                                <option value="suspended">Suspended</option>
                            </select>
                        </div>
                        <div>
                            <button class="inline-flex items-center rounded-md bg-orange-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-orange-700">
                                Create student profile
                            </button>
                        </div>
                    </form>
                </section>
            </div>

            <div class="mt-6 grid gap-6 xl:grid-cols-2">
                <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="border-b border-slate-100 pb-5">
                        <p class="text-sm font-semibold uppercase tracking-wide text-orange-600">Step 3</p>
                        <h3 class="mt-1 text-lg font-semibold text-slate-950">Assign course to major</h3>
                    </div>
                    <form method="POST" action="{{ route('academics.major-courses.store') }}" class="mt-6 grid gap-4">
                        @csrf
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="course_major_major_id" class="block text-sm font-medium text-slate-800">Major</label>
                                <select id="course_major_major_id" name="major_id" class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                    <option value="">Select major</option>
                                    @foreach ($majors as $major)
                                        <option value="{{ $major->id }}">{{ $major->code }} - {{ $major->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="course_major_course_id" class="block text-sm font-medium text-slate-800">Course</label>
                                <select id="course_major_course_id" name="course_id" class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                    <option value="">Select course</option>
                                    @foreach ($courses as $course)
                                        <option value="{{ $course->id }}">{{ $course->code }} - {{ $course->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
                                <input type="checkbox" name="is_required" value="1" checked class="rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                                Required course
                            </label>
                            <div>
                                <label for="recommended_level" class="block text-sm font-medium text-slate-800">Recommended level</label>
                                <input id="recommended_level" type="number" name="recommended_level" min="1" max="20"
                                    class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                            </div>
                        </div>
                        <button class="inline-flex w-fit items-center rounded-md bg-orange-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-orange-700">
                            Assign course
                        </button>
                    </form>
                </section>

                <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="border-b border-slate-100 pb-5">
                        <p class="text-sm font-semibold uppercase tracking-wide text-orange-600">Step 4</p>
                        <h3 class="mt-1 text-lg font-semibold text-slate-950">Enroll student in course</h3>
                    </div>
                    <form method="POST" action="{{ route('academics.course-students.store') }}" class="mt-6 grid gap-4">
                        @csrf
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="enroll_student_id" class="block text-sm font-medium text-slate-800">Student</label>
                                <select id="enroll_student_id" name="student_profile_id" class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                    <option value="">Select student</option>
                                    @foreach ($students as $student)
                                        <option value="{{ $student->id }}">{{ $student->user?->name }} - {{ $student->student_number }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="enroll_course_id" class="block text-sm font-medium text-slate-800">Course</label>
                                <select id="enroll_course_id" name="course_id" class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                    <option value="">Select course</option>
                                    @foreach ($courses as $course)
                                        <option value="{{ $course->id }}">{{ $course->code }} - {{ $course->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="enrollment_status" class="block text-sm font-medium text-slate-800">Enrollment status</label>
                                <select id="enrollment_status" name="enrollment_status" class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                    <option value="enrolled">Enrolled</option>
                                    <option value="pending">Pending</option>
                                    <option value="completed">Completed</option>
                                    <option value="dropped">Dropped</option>
                                </select>
                            </div>
                            <div>
                                <label for="enrolled_at" class="block text-sm font-medium text-slate-800">Enrolled at</label>
                                <input id="enrolled_at" type="datetime-local" name="enrolled_at" class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                            </div>
                        </div>
                        <button class="inline-flex w-fit items-center rounded-md bg-orange-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-orange-700">
                            Enroll student
                        </button>
                    </form>
                </section>
            </div>

            <section class="mt-6 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <div class="border-b border-slate-100 pb-5">
                    <p class="text-sm font-semibold uppercase tracking-wide text-orange-600">Step 5</p>
                    <h3 class="mt-1 text-lg font-semibold text-slate-950">Assign exam to course or one student</h3>
                </div>
                <form method="POST" action="{{ route('academics.exam-assignments.store') }}" class="mt-6 grid gap-4">
                    @csrf
                    <div class="grid gap-4 lg:grid-cols-3">
                        <div>
                            <label for="assignment_exam_id" class="block text-sm font-medium text-slate-800">Exam</label>
                            <select id="assignment_exam_id" name="instructor_exam_id" class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                <option value="">Select exam</option>
                                @foreach ($exams as $exam)
                                    <option value="{{ $exam->id }}" @selected((string) old('instructor_exam_id') === (string) $exam->id)>{{ $exam->title }} - {{ $exam->course?->code }}</option>
                                @endforeach
                            </select>
                            <p class="mt-2 text-xs text-slate-500">Only published exams are available for assignment.</p>
                        </div>
                        <div>
                            <label for="assignment_course_id" class="block text-sm font-medium text-slate-800">Course</label>
                            <select id="assignment_course_id" name="course_id" class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                <option value="">Select course</option>
                                @foreach ($courses as $course)
                                    <option value="{{ $course->id }}" @selected((string) old('course_id') === (string) $course->id)>{{ $course->code }} - {{ $course->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="assignment_student_id" class="block text-sm font-medium text-slate-800">Specific student</label>
                            <select id="assignment_student_id" name="student_profile_id" class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                <option value="">Whole course</option>
                                @foreach ($students as $student)
                                    <option value="{{ $student->id }}" @selected((string) old('student_profile_id') === (string) $student->id)>{{ $student->user?->name }} - {{ $student->student_number }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="grid gap-4 lg:grid-cols-4">
                        <div>
                            <label for="available_at" class="block text-sm font-medium text-slate-800">Available at</label>
                            <input id="available_at" type="datetime-local" name="available_at" value="{{ old('available_at') }}" class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                        </div>
                        <div>
                            <label for="due_at" class="block text-sm font-medium text-slate-800">Due at</label>
                            <input id="due_at" type="datetime-local" name="due_at" value="{{ old('due_at') }}" class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                        </div>
                        <div>
                            <label for="max_attempts" class="block text-sm font-medium text-slate-800">Max attempts</label>
                            <input id="max_attempts" type="number" name="max_attempts" value="{{ old('max_attempts', 1) }}" min="1" max="20" class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                        </div>
                        <div>
                            <label for="assignment_status" class="block text-sm font-medium text-slate-800">Status</label>
                            <select id="assignment_status" name="status" class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                <option value="assigned" @selected(old('status', 'assigned') === 'assigned')>Assigned</option>
                                <option value="open" @selected(old('status', 'assigned') === 'open')>Open</option>
                                <option value="closed" @selected(old('status', 'assigned') === 'closed')>Closed</option>
                                <option value="cancelled" @selected(old('status', 'assigned') === 'cancelled')>Cancelled</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid gap-3 rounded-md border border-slate-200 bg-slate-50 p-4 sm:grid-cols-2">
                        @foreach ($assignmentFeatures as $key => $feature)
                            <label class="flex items-start gap-3 text-sm text-slate-700">
                                <input type="checkbox" name="{{ $key }}" value="1" @checked(old($key, false))
                                    class="mt-1 rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                                <span>
                                    <span class="block font-semibold text-slate-900">{{ $feature['label'] }}</span>
                                    <span class="mt-1 block text-xs leading-5 text-slate-500">{{ $feature['description'] }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <button class="inline-flex w-fit items-center rounded-md bg-orange-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-orange-700">
                        Create assignment
                    </button>
                </form>
            </section>

            <div class="mt-6 grid gap-6 xl:grid-cols-2">
                <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 p-6">
                        <h3 class="text-lg font-semibold text-slate-950">Students</h3>
                        <p class="mt-1 text-sm text-slate-500">Laravel users with student profiles</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-left text-sm">
                            <thead class="bg-slate-100 text-xs uppercase text-slate-600">
                                <tr>
                                    <th class="px-4 py-3">Student</th>
                                    <th class="px-4 py-3">Major</th>
                                    <th class="px-4 py-3">Courses</th>
                                    <th class="px-4 py-3">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($students as $student)
                                    <tr>
                                        <td class="px-4 py-3">
                                            <p class="font-semibold text-slate-950">{{ $student->user?->name }}</p>
                                            <p class="text-xs text-slate-500">{{ $student->student_number }}</p>
                                        </td>
                                        <td class="px-4 py-3">{{ $student->major?->code ?? 'Not assigned' }}</td>
                                        <td class="px-4 py-3">{{ $student->courses->count() }}</td>
                                        <td class="px-4 py-3 capitalize">{{ $student->academic_status }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-6 text-center text-slate-500">No students yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 p-6">
                        <h3 class="text-lg font-semibold text-slate-950">Majors</h3>
                        <p class="mt-1 text-sm text-slate-500">Programs with student and course counts</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-left text-sm">
                            <thead class="bg-slate-100 text-xs uppercase text-slate-600">
                                <tr>
                                    <th class="px-4 py-3">Major</th>
                                    <th class="px-4 py-3">Courses</th>
                                    <th class="px-4 py-3">Students</th>
                                    <th class="px-4 py-3">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($majors as $major)
                                    <tr>
                                        <td class="px-4 py-3">
                                            <p class="font-semibold text-slate-950">{{ $major->name }}</p>
                                            <p class="text-xs text-slate-500">{{ $major->code }}</p>
                                        </td>
                                        <td class="px-4 py-3">{{ $major->courses_count }}</td>
                                        <td class="px-4 py-3">{{ $major->students_count }}</td>
                                        <td class="px-4 py-3">{{ $major->is_active ? 'Active' : 'Inactive' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-6 text-center text-slate-500">No majors yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <section class="mt-6 rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 p-6">
                    <h3 class="text-lg font-semibold text-slate-950">Exam assignments</h3>
                    <p class="mt-1 text-sm text-slate-500">Course-wide and student-specific exam availability</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-slate-100 text-xs uppercase text-slate-600">
                            <tr>
                                <th class="px-4 py-3">Exam</th>
                                <th class="px-4 py-3">Course</th>
                                <th class="px-4 py-3">Student</th>
                                <th class="px-4 py-3">Window</th>
                                <th class="px-4 py-3">Attempts</th>
                                <th class="px-4 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($assignments as $assignment)
                                <tr>
                                    <td class="px-4 py-3 font-semibold text-slate-950">{{ $assignment->exam?->title }}</td>
                                    <td class="px-4 py-3">{{ $assignment->course?->code }}</td>
                                    <td class="px-4 py-3">{{ $assignment->student?->user?->name ?? 'Whole course' }}</td>
                                    <td class="px-4 py-3 text-xs text-slate-600">
                                        {{ $assignment->available_at?->format('M j, Y H:i') ?? 'Any time' }}
                                        <span class="text-slate-400">to</span>
                                        {{ $assignment->due_at?->format('M j, Y H:i') ?? 'No due date' }}
                                    </td>
                                    <td class="px-4 py-3">{{ $assignment->max_attempts }}</td>
                                    <td class="px-4 py-3 capitalize">{{ $assignment->status }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-6 text-center text-slate-500">No exam assignments yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="mt-6 rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 p-6">
                    <h3 class="text-lg font-semibold text-slate-950">Exam sessions</h3>
                    <p class="mt-1 text-sm text-slate-500">Manage attempts after students start exams</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-slate-100 text-xs uppercase text-slate-600">
                            <tr>
                                <th class="px-4 py-3">Student</th>
                                <th class="px-4 py-3">Exam</th>
                                <th class="px-4 py-3">Attempt</th>
                                <th class="px-4 py-3">Timing</th>
                                <th class="px-4 py-3">Score</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Manage</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($sessions as $session)
                                <tr>
                                    <td class="px-4 py-3">{{ $session->student?->user?->name }}</td>
                                    <td class="px-4 py-3">{{ $session->assignment?->exam?->title }}</td>
                                    <td class="px-4 py-3">{{ $session->attempt_number }}</td>
                                    <td class="px-4 py-3 text-xs text-slate-600">
                                        Started: {{ $session->started_at?->format('M j, Y H:i') ?? 'Not started' }}<br>
                                        Expires: {{ $session->expires_at?->format('M j, Y H:i') ?? 'No limit' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ $session->score ?? '-' }}
                                        @if ($session->percentage !== null)
                                            <span class="text-slate-500">({{ $session->percentage }}%)</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 capitalize">{{ str_replace('_', ' ', $session->status) }}</td>
                                    <td class="px-4 py-3">
                                        <form method="POST" action="{{ route('academics.exam-sessions.update', $session) }}" class="flex gap-2">
                                            @csrf
                                            @method('PATCH')
                                            <select name="status" class="rounded-md border-slate-300 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                                <option value="in_progress" @selected($session->status === 'in_progress')>In progress</option>
                                                <option value="submitted" @selected($session->status === 'submitted')>Submitted</option>
                                                <option value="expired" @selected($session->status === 'expired')>Expired</option>
                                                <option value="cancelled" @selected($session->status === 'cancelled')>Cancelled</option>
                                            </select>
                                            <button class="rounded-md bg-slate-900 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-700">
                                                Save
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-6 text-center text-slate-500">No sessions yet. Sessions appear after students start an assigned exam.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
