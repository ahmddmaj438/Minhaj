<?php

namespace App\Http\Controllers;

use App\Http\Requests\Academic\StoreCourseMajorRequest;
use App\Http\Requests\Academic\StoreCourseStudentRequest;
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
use App\Services\Academics\AcademicWorkflowService;
use App\Services\Exams\ExamFeatureRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AcademicManagementController extends Controller
{
    public function index(ExamFeatureRegistry $featureRegistry): View
    {
        return view('academics.index', [
            'majors' => Major::withCount(['students', 'courses'])->orderBy('name')->get(),
            'students' => StudentProfile::with(['user', 'major', 'courses'])->latest()->get(),
            'studentUsers' => User::whereDoesntHave('studentProfile')->orderBy('name')->get(),
            'courses' => Course::with(['majors', 'students.user'])->orderBy('code')->orderBy('name')->get(),
            'exams' => InstructorExam::with('course')
                ->where('status', InstructorExam::STATUS_PUBLISHED)
                ->latest()
                ->get(),
            'assignments' => ExamAssignment::with(['exam', 'course', 'student.user', 'assignedBy'])
                ->latest()
                ->limit(50)
                ->get(),
            'sessions' => ExamSession::with(['assignment.exam', 'assignment.course', 'student.user'])
                ->latest()
                ->limit(50)
                ->get(),
            'assignmentFeatures' => $featureRegistry->assignmentSettings(),
        ]);
    }

    public function storeMajor(StoreMajorRequest $request, AcademicWorkflowService $academics): RedirectResponse
    {
        $data = $request->validated();

        $academics->createMajor($data, $request->boolean('is_active', true));

        return back()->with('status', 'Major created.');
    }

    public function storeStudent(StoreStudentProfileRequest $request, AcademicWorkflowService $academics): RedirectResponse
    {
        $data = $request->validated();

        $academics->createStudentProfile($data);

        return back()->with('status', 'Student profile created.');
    }

    public function assignCourseToMajor(StoreCourseMajorRequest $request, AcademicWorkflowService $academics): RedirectResponse
    {
        $data = $request->validated();

        $academics->assignCourseToMajor($data, $request->boolean('is_required', true));

        return back()->with('status', 'Course assigned to major.');
    }

    public function enrollStudent(StoreCourseStudentRequest $request, AcademicWorkflowService $academics): RedirectResponse
    {
        $data = $request->validated();

        $academics->enrollStudentInCourse($data);

        return back()->with('status', 'Student enrolled in course.');
    }

    public function storeExamAssignment(StoreExamAssignmentRequest $request, AcademicWorkflowService $academics): RedirectResponse
    {
        $data = $request->validated();

        $academics->createExamAssignment(
            $data,
            $request->user()->id,
            $request->boolean('show_score_to_student'),
            $request->boolean('show_feedback_to_student')
        );

        return back()->with('status', 'Exam assignment created.');
    }

    public function updateSessionStatus(
        UpdateExamSessionStatusRequest $request,
        ExamSession $session,
        AcademicWorkflowService $academics
    ): RedirectResponse
    {
        $data = $request->validated();

        $academics->updateSessionStatus($session, $data['status']);

        return back()->with('status', 'Exam session status updated.');
    }
}
