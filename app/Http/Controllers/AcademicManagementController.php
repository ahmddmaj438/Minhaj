<?php

namespace App\Http\Controllers;

use App\Http\Requests\Academic\StoreCourseMajorRequest;
use App\Http\Requests\Academic\StoreCourseStudentRequest;
use App\Http\Requests\Academic\StoreCourseTeacherRequest;
use App\Http\Requests\Academic\StoreExamAssignmentRequest;
use App\Http\Requests\Academic\StoreMajorRequest;
use App\Http\Requests\Academic\StoreStudentProfileRequest;
use App\Http\Requests\Academic\UpdateExamSessionStatusRequest;
use App\Models\Course;
use App\Models\Exam\InstructorExam;
use App\Models\ExamAssignment;
use App\Models\ExamSession;
use App\Models\Major;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\Access\LearningAccess;
use App\Services\Academics\AcademicSetupImportService;
use App\Services\Academics\AcademicWorkflowService;
use App\Services\Exams\ExamFeatureRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AcademicManagementController extends Controller
{
    public function index(ExamFeatureRegistry $featureRegistry, LearningAccess $access): View
    {
        $user = request()->user();
        $courseIds = $access->courseQuery($user)->pluck('id');
        $examIds = $access->examQuery($user)->pluck('id');
        $canManageAllLearning = $user?->isSuperAdmin() || $user?->hasPermission('screen.admin.access.index.view');

        return view('academics.index', [
            'majors' => Major::withCount(['students', 'courses'])->orderBy('name')->get(),
            'students' => StudentProfile::with(['user', 'major', 'courses'])
                ->when(! $canManageAllLearning, fn ($query) => $query->whereHas('courses', fn ($courseQuery) => $courseQuery->whereIn('courses.id', $courseIds)))
                ->latest()
                ->get(),
            'courses' => Course::with(['majors', 'students.user', 'teachers'])
                ->whereIn('id', $courseIds)
                ->orderBy('code')
                ->orderBy('name')
                ->get(),
            'teachers' => User::query()
                ->whereDoesntHave('studentProfile')
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
            'exams' => InstructorExam::with('course')
                ->whereIn('id', $examIds)
                ->where('status', InstructorExam::STATUS_PUBLISHED)
                ->latest()
                ->get(),
            'assignments' => ExamAssignment::with(['exam', 'course', 'student.user', 'assignedBy'])
                ->whereIn('course_id', $courseIds)
                ->whereIn('instructor_exam_id', $examIds)
                ->latest()
                ->limit(50)
                ->get(),
            'sessions' => ExamSession::with(['assignment.exam', 'assignment.course', 'student.user'])
                ->whereHas('assignment', fn ($query) => $query
                    ->whereIn('course_id', $courseIds)
                    ->whereIn('instructor_exam_id', $examIds))
                ->latest()
                ->limit(50)
                ->get(),
            'assignmentFeatures' => $featureRegistry->assignmentSettings(),
        ]);
    }

    public function upload(): View
    {
        return view('academics.upload', [
            'importPreview' => session('academic_import_preview'),
            'importResult' => session('academic_import_result'),
        ]);
    }

    public function downloadTemplate(AcademicSetupImportService $imports): StreamedResponse
    {
        return $imports->templateResponse();
    }

    public function previewUpload(Request $request, AcademicSetupImportService $imports): RedirectResponse
    {
        $data = $request->validate([
            'academic_file' => ['required', 'file', 'extensions:xlsx,csv', 'max:20480'],
        ], [], [
            'academic_file' => 'Academic setup file',
        ]);

        $preview = $imports->preview($data['academic_file']);
        $request->session()->put('academic_import_preview', $preview);

        return redirect()
            ->route('academics.upload.index')
            ->with('status', 'Academic data upload was checked. Review the preview before saving.');
    }

    public function confirmUpload(Request $request, AcademicSetupImportService $imports): RedirectResponse
    {
        $preview = $request->session()->get('academic_import_preview');
        abort_if(! is_array($preview), 422, 'Upload preview expired. Please upload the file again.');

        $result = $imports->import($preview, $request->user()->id);
        $request->session()->forget('academic_import_preview');
        $request->session()->flash('academic_import_result', $result);

        return redirect()
            ->route('academics.upload.index')
            ->with('status', 'Academic data upload was saved.');
    }

    public function storeMajor(StoreMajorRequest $request, AcademicWorkflowService $academics): RedirectResponse
    {
        $data = $request->validated();

        $academics->createMajor($data, $request->boolean('is_active', true));

        return back()->with('status', 'Program information was added.');
    }

    public function storeStudent(StoreStudentProfileRequest $request, AcademicWorkflowService $academics): RedirectResponse
    {
        $data = $request->validated();

        $academics->createStudentProfile($data);

        return back()->with('status', 'Student account and academic profile were added.');
    }

    public function assignCourseToMajor(StoreCourseMajorRequest $request, AcademicWorkflowService $academics): RedirectResponse
    {
        $data = $request->validated();

        $academics->assignCourseToMajor($data, $request->boolean('is_required', true));

        return back()->with('status', 'Course was connected to the program.');
    }

    public function enrollStudent(StoreCourseStudentRequest $request, AcademicWorkflowService $academics): RedirectResponse
    {
        $data = $request->validated();

        $academics->enrollStudentInCourse($data);

        return back()->with('status', 'Student was enrolled in the course.');
    }

    public function assignTeacher(StoreCourseTeacherRequest $request, AcademicWorkflowService $academics): RedirectResponse
    {
        $data = $request->validated();

        $academics->assignTeacherToCourse($data, $request->user()->id);

        return back()->with('status', 'Teacher was assigned to the course.');
    }

    public function storeExamAssignment(
        StoreExamAssignmentRequest $request,
        AcademicWorkflowService $academics,
        LearningAccess $access
    ): RedirectResponse
    {
        $data = $request->validated();
        $exam = InstructorExam::findOrFail($data['instructor_exam_id']);
        $assigningOwnedExamCourse = (int) $exam->instructor_id === (int) $request->user()->id
            && (int) $exam->course_id === (int) $data['course_id'];
        abort_unless($assigningOwnedExamCourse || $access->canAccessCourse($request->user(), (int) $data['course_id']), 403, 'This course is not assigned to you.');
        abort_unless($access->canAccessExam($request->user(), $exam), 403, 'You do not have permission to access this exam.');

        $academics->createExamAssignment(
            $data,
            $request->user()->id,
            $request->boolean('show_score_to_student'),
            $request->boolean('show_feedback_to_student')
        );

        return back()->with('status', 'Exam availability was added.');
    }

    public function updateSessionStatus(
        UpdateExamSessionStatusRequest $request,
        ExamSession $session,
        AcademicWorkflowService $academics,
        LearningAccess $access
    ): RedirectResponse
    {
        $session->loadMissing('assignment.exam');
        abort_unless($access->canGradeSession($request->user(), $session), 403, 'You do not have permission to access this exam.');

        $data = $request->validated();

        $academics->updateSessionStatus($session, $data['status']);

        return back()->with('status', 'Exam attempt status was saved.');
    }
}
