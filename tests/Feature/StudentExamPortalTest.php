<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Exam\InstructorExam;
use App\Models\Exam\InstructorExamQuestion;
use App\Models\ExamAssignment;
use App\Models\ExamSession;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StudentExamPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_login_redirects_to_exam_portal(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);
        StudentProfile::create([
            'user_id' => $user->id,
            'student_number' => 'S1001',
            'academic_status' => StudentProfile::STATUS_ACTIVE,
        ]);

        $response = $this->post('/login', [
            'email' => 'student@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('student.exams.index', absolute: false));
    }

    public function test_student_portal_lists_only_available_assigned_exams(): void
    {
        $this->travelTo('2026-06-02 10:00:00');

        [$studentUser, $student, $course, $instructor] = $this->studentCourseContext();
        $availableExam = $this->examForCourse($course, $instructor, 'Available Exam');
        $futureExam = $this->examForCourse($course, $instructor, 'Future Exam');

        ExamAssignment::create([
            'instructor_exam_id' => $availableExam->id,
            'course_id' => $course->id,
            'student_profile_id' => $student->id,
            'assigned_by' => $instructor->id,
            'available_at' => '2026-06-02 09:00:00',
            'due_at' => '2026-06-02 12:00:00',
            'max_attempts' => 1,
            'status' => ExamAssignment::STATUS_ASSIGNED,
        ]);
        ExamAssignment::create([
            'instructor_exam_id' => $futureExam->id,
            'course_id' => $course->id,
            'student_profile_id' => $student->id,
            'assigned_by' => $instructor->id,
            'available_at' => '2026-06-03 09:00:00',
            'due_at' => '2026-06-03 12:00:00',
            'max_attempts' => 1,
            'status' => ExamAssignment::STATUS_ASSIGNED,
        ]);

        $response = $this->actingAs($studentUser)->get(route('student.exams.index'));

        $response
            ->assertOk()
            ->assertSee('Available Exam')
            ->assertDontSee('Future Exam');
    }

    public function test_start_creates_exam_session_and_draft_answers(): void
    {
        $this->travelTo('2026-06-02 10:00:00');

        [$studentUser, $student, $course, $instructor] = $this->studentCourseContext();
        $exam = $this->examForCourse($course, $instructor, 'Available Exam');
        $question = InstructorExamQuestion::create([
            'instructor_exam_id' => $exam->id,
            'type' => 'mcq',
            'category' => 'objective',
            'title' => 'Choose one',
            'position' => 1,
            'marks' => 10,
            'prompt' => ['question_text' => 'Choose one'],
            'settings' => [
                'options' => [
                    ['key' => 'option_1', 'text' => 'A'],
                    ['key' => 'option_2', 'text' => 'B'],
                ],
            ],
        ]);
        $assignment = ExamAssignment::create([
            'instructor_exam_id' => $exam->id,
            'course_id' => $course->id,
            'student_profile_id' => $student->id,
            'assigned_by' => $instructor->id,
            'available_at' => '2026-06-02 09:00:00',
            'due_at' => '2026-06-02 12:00:00',
            'max_attempts' => 1,
            'status' => ExamAssignment::STATUS_ASSIGNED,
        ]);

        $response = $this
            ->actingAs($studentUser)
            ->post(route('student.exams.start', $assignment));

        $session = ExamSession::first();

        $response->assertRedirect(route('student.exams.sessions.show', $session, absolute: false));
        $this->assertDatabaseHas('exam_sessions', [
            'exam_assignment_id' => $assignment->id,
            'student_profile_id' => $student->id,
            'attempt_number' => 1,
            'status' => ExamSession::STATUS_IN_PROGRESS,
        ]);
        $this->assertDatabaseHas('exam_session_answers', [
            'exam_session_id' => $session->id,
            'instructor_exam_question_id' => $question->id,
        ]);
    }

    public function test_objective_answers_are_scored_on_submit(): void
    {
        $this->travelTo('2026-06-02 10:00:00');

        [$studentUser, $student, $course, $instructor] = $this->studentCourseContext();
        $exam = InstructorExam::create([
            'course_id' => $course->id,
            'instructor_id' => $instructor->id,
            'title' => 'Objective Exam',
            'duration_minutes' => 60,
            'total_marks' => 100,
        ]);
        $question = InstructorExamQuestion::create([
            'instructor_exam_id' => $exam->id,
            'type' => 'mcq',
            'category' => 'objective',
            'title' => 'Choose one',
            'position' => 1,
            'marks' => 10,
            'prompt' => ['question_text' => 'Choose one'],
            'settings' => [
                'options' => [
                    ['key' => 'option_1', 'text' => 'A', 'is_correct' => true],
                    ['key' => 'option_2', 'text' => 'B', 'is_correct' => false],
                ],
            ],
        ]);
        $assignment = ExamAssignment::create([
            'instructor_exam_id' => $exam->id,
            'course_id' => $course->id,
            'student_profile_id' => $student->id,
            'assigned_by' => $instructor->id,
            'available_at' => '2026-06-02 09:00:00',
            'due_at' => '2026-06-02 12:00:00',
            'max_attempts' => 1,
            'status' => ExamAssignment::STATUS_ASSIGNED,
            'settings' => ['show_score_to_student' => true],
        ]);
        $session = $this->actingAs($studentUser)->post(route('student.exams.start', $assignment));
        $session = ExamSession::first();

        $this
            ->actingAs($studentUser)
            ->post(route('student.exams.sessions.submit', $session), [
                'answers' => [
                    $question->id => [
                        'selected_options' => ['option_1'],
                    ],
                ],
            ])
            ->assertRedirect(route('student.exams.index', absolute: false));

        $this->assertDatabaseHas('exam_sessions', [
            'id' => $session->id,
            'status' => ExamSession::STATUS_SUBMITTED,
            'score' => 10,
            'max_score' => 10,
            'percentage' => 100,
            'passed' => true,
        ]);
        $this->assertDatabaseHas('exam_session_answers', [
            'exam_session_id' => $session->id,
            'instructor_exam_question_id' => $question->id,
            'score' => 10,
        ]);
    }

    public function test_submitted_exam_session_cannot_be_reopened_by_student(): void
    {
        $this->travelTo('2026-06-02 10:00:00');

        [$studentUser, $student, $course, $instructor] = $this->studentCourseContext();
        $exam = $this->examForCourse($course, $instructor, 'No Reopen Exam');
        $question = InstructorExamQuestion::create([
            'instructor_exam_id' => $exam->id,
            'type' => 'mcq',
            'category' => 'objective',
            'title' => 'Choose one',
            'position' => 1,
            'marks' => 10,
            'prompt' => ['question_text' => 'Choose one'],
            'settings' => [
                'options' => [
                    ['key' => 'option_1', 'text' => 'A', 'is_correct' => true],
                    ['key' => 'option_2', 'text' => 'B', 'is_correct' => false],
                ],
            ],
        ]);
        $assignment = ExamAssignment::create([
            'instructor_exam_id' => $exam->id,
            'course_id' => $course->id,
            'student_profile_id' => $student->id,
            'assigned_by' => $instructor->id,
            'available_at' => '2026-06-02 09:00:00',
            'due_at' => '2026-06-02 12:00:00',
            'max_attempts' => 1,
            'status' => ExamAssignment::STATUS_ASSIGNED,
        ]);

        $this->actingAs($studentUser)->post(route('student.exams.start', $assignment));
        $session = ExamSession::first();

        $showResponse = $this
            ->actingAs($studentUser)
            ->get(route('student.exams.sessions.show', $session));

        $showResponse->assertOk();
        $this->assertStringContainsString('no-store', $showResponse->headers->get('Cache-Control', ''));

        $submitResponse = $this
            ->actingAs($studentUser)
            ->post(route('student.exams.sessions.submit', $session), [
                'answers' => [
                    $question->id => [
                        'selected_options' => ['option_1'],
                    ],
                ],
            ]);

        $submitResponse->assertRedirect(route('student.exams.index', absolute: false));
        $this->assertStringContainsString('no-store', $submitResponse->headers->get('Cache-Control', ''));

        $reopenResponse = $this
            ->actingAs($studentUser)
            ->get(route('student.exams.sessions.show', $session));

        $reopenResponse
            ->assertRedirect(route('student.exams.index', absolute: false))
            ->assertSessionHas('status', 'This exam has already been submitted.');
        $this->assertStringContainsString('no-store', $reopenResponse->headers->get('Cache-Control', ''));
    }

    public function test_manual_grading_updates_written_question_score(): void
    {
        $this->travelTo('2026-06-02 10:00:00');

        [$studentUser, $student, $course, $instructor] = $this->studentCourseContext();
        $exam = InstructorExam::create([
            'course_id' => $course->id,
            'instructor_id' => $instructor->id,
            'title' => 'Essay Exam',
            'duration_minutes' => 60,
            'total_marks' => 100,
        ]);
        $question = InstructorExamQuestion::create([
            'instructor_exam_id' => $exam->id,
            'type' => 'essay',
            'category' => 'text',
            'title' => 'Explain',
            'position' => 1,
            'marks' => 20,
            'prompt' => ['question_text' => 'Explain the concept.'],
            'settings' => ['rubric' => 'Clear explanation.'],
        ]);
        $assignment = ExamAssignment::create([
            'instructor_exam_id' => $exam->id,
            'course_id' => $course->id,
            'student_profile_id' => $student->id,
            'assigned_by' => $instructor->id,
            'available_at' => '2026-06-02 09:00:00',
            'due_at' => '2026-06-02 12:00:00',
            'max_attempts' => 1,
            'status' => ExamAssignment::STATUS_ASSIGNED,
            'settings' => ['show_score_to_student' => true],
        ]);
        $this->actingAs($studentUser)->post(route('student.exams.start', $assignment));
        $session = ExamSession::first();
        $this
            ->actingAs($studentUser)
            ->post(route('student.exams.sessions.submit', $session), [
                'answers' => [
                    $question->id => [
                        'response' => 'A careful answer.',
                    ],
                ],
            ]);
        $answer = $session->refresh()->answers()->first();

        $this
            ->actingAs($instructor)
            ->put(route('instructor.grading.answers.update', [$session, $answer]), [
                'score' => 18,
                'feedback' => 'Good work.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('exam_sessions', [
            'id' => $session->id,
            'score' => 18,
            'max_score' => 20,
            'percentage' => 90,
            'passed' => true,
        ]);
        $this->assertDatabaseHas('exam_session_answers', [
            'id' => $answer->id,
            'score' => 18,
            'feedback' => 'Good work.',
        ]);
    }

    public function test_instructor_grading_page_displays_auto_and_manual_questions(): void
    {
        $this->travelTo('2026-06-02 10:00:00');

        [$studentUser, $student, $course, $instructor] = $this->studentCourseContext();
        $exam = InstructorExam::create([
            'course_id' => $course->id,
            'instructor_id' => $instructor->id,
            'title' => 'Mixed Exam',
            'duration_minutes' => 60,
            'total_marks' => 100,
        ]);
        $mcq = InstructorExamQuestion::create([
            'instructor_exam_id' => $exam->id,
            'type' => 'mcq',
            'category' => 'objective',
            'title' => 'Choose one',
            'position' => 1,
            'marks' => 10,
            'prompt' => ['question_text' => 'Choose one'],
            'settings' => [
                'options' => [
                    ['key' => 'option_1', 'text' => 'A', 'is_correct' => true],
                    ['key' => 'option_2', 'text' => 'B', 'is_correct' => false],
                ],
            ],
        ]);
        $essay = InstructorExamQuestion::create([
            'instructor_exam_id' => $exam->id,
            'type' => 'essay',
            'category' => 'text',
            'title' => 'Explain',
            'position' => 2,
            'marks' => 20,
            'prompt' => ['question_text' => 'Explain the concept.'],
        ]);
        $assignment = ExamAssignment::create([
            'instructor_exam_id' => $exam->id,
            'course_id' => $course->id,
            'student_profile_id' => $student->id,
            'assigned_by' => $instructor->id,
            'available_at' => '2026-06-02 09:00:00',
            'due_at' => '2026-06-02 12:00:00',
            'max_attempts' => 1,
            'status' => ExamAssignment::STATUS_ASSIGNED,
        ]);
        $this->actingAs($studentUser)->post(route('student.exams.start', $assignment));
        $session = ExamSession::first();
        $this
            ->actingAs($studentUser)
            ->post(route('student.exams.sessions.submit', $session), [
                'answers' => [
                    $mcq->id => ['selected_options' => ['option_1']],
                    $essay->id => ['response' => 'A careful answer.'],
                ],
            ]);

        $response = $this
            ->actingAs($instructor)
            ->get(route('instructor.grading.sessions.show', $session));

        $response
            ->assertOk()
            ->assertSee('Choose one')
            ->assertSee('Explain the concept.')
            ->assertSee('Auto calculated')
            ->assertSee('Manual grading');

        $this->assertDatabaseHas('exam_sessions', [
            'id' => $session->id,
            'score' => 10,
            'max_score' => 30,
            'percentage' => null,
        ]);
    }

    public function test_written_answer_ai_assist_generates_suggestion_for_keyboard_answers_only(): void
    {
        $this->travelTo('2026-06-02 10:00:00');
        config([
            'services.ai_grading.provider' => 'auto',
            'services.ai_grading.google.api_key' => '',
            'services.ai_grading.groq.api_key' => '',
            'services.ai_grading.pollinations.enabled' => false,
        ]);

        [$studentUser, $student, $course, $instructor] = $this->studentCourseContext();
        $exam = InstructorExam::create([
            'course_id' => $course->id,
            'instructor_id' => $instructor->id,
            'title' => 'Written Assist Exam',
            'duration_minutes' => 60,
            'total_marks' => 100,
        ]);
        $essay = InstructorExamQuestion::create([
            'instructor_exam_id' => $exam->id,
            'type' => 'essay',
            'category' => 'text',
            'title' => 'Explain',
            'position' => 1,
            'marks' => 20,
            'prompt' => ['question_text' => 'Explain the concept.'],
            'settings' => [
                'rubric' => 'Assess accuracy, completeness, examples, and clarity.',
                'min_words' => 20,
            ],
        ]);
        $coding = InstructorExamQuestion::create([
            'instructor_exam_id' => $exam->id,
            'type' => 'coding',
            'category' => 'coding',
            'title' => 'Write SQL',
            'position' => 2,
            'marks' => 30,
            'programming_language' => 'sql',
            'prompt' => ['problem_statement' => 'Write a SQL query to count students by course.'],
            'settings' => [
                'rubric' => 'Assess query correctness, grouping, and readable aliases.',
                'starter_code' => 'SELECT',
            ],
        ]);
        $fillBlank = InstructorExamQuestion::create([
            'instructor_exam_id' => $exam->id,
            'type' => 'fill_blank',
            'category' => 'objective',
            'title' => 'Complete terms',
            'position' => 3,
            'marks' => 10,
            'prompt' => ['question_text' => 'Complete the database terms.'],
            'settings' => [
                'blanks' => [
                    ['label' => 'Key type', 'accepted_answers' => ['primary key']],
                    ['label' => 'Integrity type', 'accepted_answers' => ['referential integrity']],
                ],
            ],
        ]);
        $mcq = InstructorExamQuestion::create([
            'instructor_exam_id' => $exam->id,
            'type' => 'mcq',
            'category' => 'objective',
            'title' => 'Choose one',
            'position' => 4,
            'marks' => 10,
            'prompt' => ['question_text' => 'Choose one'],
            'settings' => [
                'options' => [
                    ['key' => 'option_1', 'text' => 'A', 'is_correct' => true],
                    ['key' => 'option_2', 'text' => 'B', 'is_correct' => false],
                ],
            ],
        ]);
        $assignment = ExamAssignment::create([
            'instructor_exam_id' => $exam->id,
            'course_id' => $course->id,
            'student_profile_id' => $student->id,
            'assigned_by' => $instructor->id,
            'available_at' => '2026-06-02 09:00:00',
            'due_at' => '2026-06-02 12:00:00',
            'max_attempts' => 1,
            'status' => ExamAssignment::STATUS_ASSIGNED,
        ]);
        $this->actingAs($studentUser)->post(route('student.exams.start', $assignment));
        $session = ExamSession::first();
        $this
            ->actingAs($studentUser)
            ->post(route('student.exams.sessions.submit', $session), [
                'answers' => [
                    $essay->id => [
                        'response' => 'This answer explains the concept with some supporting details and a practical example for clarity.',
                    ],
                    $coding->id => [
                        'response' => 'SELECT course_id, COUNT(*) AS total_students FROM course_student GROUP BY course_id;',
                    ],
                    $fillBlank->id => [
                        'blanks' => ['primary key', 'referential integrity'],
                    ],
                    $mcq->id => [
                        'selected_options' => ['option_1'],
                    ],
                ],
            ]);

        $essayAnswer = $session->refresh()->answers()->where('instructor_exam_question_id', $essay->id)->first();
        $codingAnswer = $session->answers()->where('instructor_exam_question_id', $coding->id)->first();
        $fillBlankAnswer = $session->answers()->where('instructor_exam_question_id', $fillBlank->id)->first();
        $mcqAnswer = $session->answers()->where('instructor_exam_question_id', $mcq->id)->first();

        $this
            ->actingAs($instructor)
            ->post(route('instructor.grading.answers.assist-answer', [$session, $essayAnswer]))
            ->assertRedirect();

        $this->assertNotNull($essayAnswer->refresh()->answer_payload['ai_grading_suggestion'] ?? null);
        $this
            ->actingAs($instructor)
            ->post(route('instructor.grading.answers.assist-answer', [$session, $codingAnswer]))
            ->assertRedirect();

        $this->assertNotNull($codingAnswer->refresh()->answer_payload['ai_grading_suggestion'] ?? null);
        $this
            ->actingAs($instructor)
            ->post(route('instructor.grading.answers.assist-answer', [$session, $fillBlankAnswer]))
            ->assertRedirect();

        $this->assertNotNull($fillBlankAnswer->refresh()->answer_payload['ai_grading_suggestion'] ?? null);
        $this
            ->actingAs($instructor)
            ->post(route('instructor.grading.answers.assist-answer', [$session, $mcqAnswer]))
            ->assertStatus(422);
    }

    public function test_written_answer_ai_assist_api_sends_question_score_and_answer_to_google(): void
    {
        $this->travelTo('2026-06-02 10:00:00');
        config([
            'services.ai_grading.provider' => 'google_gemini',
            'services.ai_grading.google.api_key' => 'test-google-key',
            'services.ai_grading.google.model' => 'gemini-2.5-flash',
        ]);
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'suggested_score' => 16,
                                        'confidence' => 0.81,
                                        'feedback' => 'The answer covers the main idea but needs more detail.',
                                        'strengths' => ['Clear main idea'],
                                        'improvements' => ['Add a stronger example'],
                                        'rationale' => 'The query has the right aggregate idea but should be checked for joins and grouping completeness.',
                                        'rubric_assessment' => [
                                            [
                                                'criterion' => 'Aggregate count',
                                                'score' => 8,
                                                'max_score' => 10,
                                                'evidence' => 'Uses COUNT(cs.student_profile_id).',
                                                'notes' => 'The aggregate is present and readable.',
                                            ],
                                            [
                                                'criterion' => 'Grouping',
                                                'score' => 6,
                                                'max_score' => 10,
                                                'evidence' => 'Groups by c.code.',
                                                'notes' => 'Grouping is present but should be checked against selected columns.',
                                            ],
                                        ],
                                    ]),
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        [$studentUser, $student, $course, $instructor] = $this->studentCourseContext();
        $exam = InstructorExam::create([
            'course_id' => $course->id,
            'instructor_id' => $instructor->id,
            'title' => 'Gemini Coding Assist Exam',
            'duration_minutes' => 60,
            'total_marks' => 100,
        ]);
        $coding = InstructorExamQuestion::create([
            'instructor_exam_id' => $exam->id,
            'type' => 'coding',
            'category' => 'coding',
            'title' => 'Write normalization query',
            'position' => 1,
            'marks' => 20,
            'difficulty' => 'hard',
            'topic' => 'Database design',
            'programming_language' => 'sql',
            'prompt' => [
                'problem_statement' => 'Write a SQL query that lists each course and the number of enrolled students.',
                'instructions' => 'Use grouping and a readable alias.',
            ],
            'settings' => [
                'rubric' => 'Correct joins, grouping, count aggregate, and readable aliases.',
                'expected_answer' => 'A grouped SELECT query that counts student enrollments per course.',
                'starter_code' => 'SELECT',
            ],
        ]);
        $assignment = ExamAssignment::create([
            'instructor_exam_id' => $exam->id,
            'course_id' => $course->id,
            'student_profile_id' => $student->id,
            'assigned_by' => $instructor->id,
            'available_at' => '2026-06-02 09:00:00',
            'due_at' => '2026-06-02 12:00:00',
            'max_attempts' => 1,
            'status' => ExamAssignment::STATUS_ASSIGNED,
        ]);
        $this->actingAs($studentUser)->post(route('student.exams.start', $assignment));
        $session = ExamSession::first();
        $this
            ->actingAs($studentUser)
            ->post(route('student.exams.sessions.submit', $session), [
                'answers' => [
                    $coding->id => [
                        'response' => 'SELECT c.code, COUNT(cs.student_profile_id) AS enrolled_students FROM courses c JOIN course_student cs ON cs.course_id = c.id GROUP BY c.code;',
                    ],
                ],
            ]);
        $answer = $session->refresh()->answers()->where('instructor_exam_question_id', $coding->id)->first();
        $answer->update([
            'score' => 12,
            'feedback' => 'Initial manual note.',
        ]);

        $response = $this
            ->actingAs($instructor)
            ->postJson(route('instructor.grading.api.answers.assist-answer', [$session, $answer]));

        $response
            ->assertOk()
            ->assertJsonPath('suggestion.suggested_score', 16)
            ->assertJsonPath('suggestion.provider', 'google_gemini:gemini-2.5-flash')
            ->assertJsonPath('suggestion.rationale', 'The query has the right aggregate idea but should be checked for joins and grouping completeness.')
            ->assertJsonPath('suggestion.provider_note', 'Generated by Google Gemini API. Instructor review is still required before saving the score.')
            ->assertJsonPath('suggestion.rubric_assessment.0.criterion', 'Aggregate count')
            ->assertJsonPath('suggestion.rubric_assessment.0.score', 8);

        $recorded = Http::recorded();
        $this->assertCount(1, $recorded);
        $request = $recorded->first()[0];
        $body = $request->body();

        $this->assertStringContainsString('/v1beta/models/gemini-2.5-flash:generateContent', $request->url());
        $this->assertStringContainsString('responseMimeType', $body);
        $this->assertStringContainsString('application\/json', $body);
        $this->assertStringContainsString('rationale', $body);
        $this->assertStringContainsString('rubric_assessment', $body);
        $this->assertStringContainsString('evaluation_request', $body);
        $this->assertStringContainsString('do_not_use_length_as_primary_score', $body);
        $this->assertStringContainsString('do_not_award_marks_for_words_only', $body);
        $this->assertStringContainsString('current_score', $body);
        $this->assertStringContainsString('12', $body);
        $this->assertStringContainsString('max_score', $body);
        $this->assertStringContainsString('20', $body);
        $this->assertStringContainsString('Write a SQL query that lists each course and the number of enrolled students.', $body);
        $this->assertStringContainsString('code_written_in_editor', $body);
        $this->assertStringContainsString('programming_language', $body);
        $this->assertStringContainsString('sql', $body);
        $this->assertStringContainsString('hard', $body);
        $this->assertStringContainsString('Correct joins, grouping, count aggregate, and readable aliases.', $body);
        $this->assertStringContainsString('SELECT c.code, COUNT(cs.student_profile_id) AS enrolled_students', $body);
    }

    public function test_written_answer_ai_assist_uses_groq_when_gemini_is_not_configured(): void
    {
        $this->travelTo('2026-06-02 10:00:00');
        config([
            'services.ai_grading.provider' => 'auto',
            'services.ai_grading.google.api_key' => '',
            'services.ai_grading.groq.api_key' => 'test-groq-key',
            'services.ai_grading.groq.model' => 'openai/gpt-oss-20b',
        ]);
        Http::fake([
            'https://api.groq.com/openai/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'suggested_score' => 18,
                                'confidence' => 0.87,
                                'feedback' => 'The SQL answer is mostly correct and uses a grouped aggregate.',
                                'strengths' => ['Uses COUNT with grouping'],
                                'improvements' => ['Check whether left joins are required for courses without students'],
                                'rationale' => 'The answer matches the expected grouped count query and includes evidence of joins, grouping, and a readable alias.',
                                'rubric_assessment' => [
                                    [
                                        'criterion' => 'Correct aggregate',
                                        'score' => 9,
                                        'max_score' => 10,
                                        'evidence' => 'COUNT(cs.student_profile_id) AS enrolled_students',
                                        'notes' => 'The aggregate count is correct.',
                                    ],
                                ],
                            ]),
                        ],
                    ],
                ],
            ], 200),
        ]);

        [$studentUser, $student, $course, $instructor] = $this->studentCourseContext();
        $exam = InstructorExam::create([
            'course_id' => $course->id,
            'instructor_id' => $instructor->id,
            'title' => 'Groq Assist Exam',
            'duration_minutes' => 60,
            'total_marks' => 100,
        ]);
        $coding = InstructorExamQuestion::create([
            'instructor_exam_id' => $exam->id,
            'type' => 'coding',
            'category' => 'coding',
            'title' => 'Count enrollments',
            'position' => 1,
            'marks' => 20,
            'difficulty' => 'medium',
            'topic' => 'SQL aggregates',
            'programming_language' => 'sql',
            'prompt' => ['problem_statement' => 'Write a query that counts enrolled students per course.'],
            'settings' => [
                'rubric' => 'Correct join, aggregate count, grouping, and readable alias.',
                'expected_answer' => 'A grouped SQL query that counts enrollments per course.',
            ],
        ]);
        $assignment = ExamAssignment::create([
            'instructor_exam_id' => $exam->id,
            'course_id' => $course->id,
            'student_profile_id' => $student->id,
            'assigned_by' => $instructor->id,
            'available_at' => '2026-06-02 09:00:00',
            'due_at' => '2026-06-02 12:00:00',
            'max_attempts' => 1,
            'status' => ExamAssignment::STATUS_ASSIGNED,
        ]);

        $this->actingAs($studentUser)->post(route('student.exams.start', $assignment));
        $session = ExamSession::first();
        $this
            ->actingAs($studentUser)
            ->post(route('student.exams.sessions.submit', $session), [
                'answers' => [
                    $coding->id => [
                        'response' => 'SELECT c.code, COUNT(cs.student_profile_id) AS enrolled_students FROM courses c JOIN course_student cs ON cs.course_id = c.id GROUP BY c.code;',
                    ],
                ],
            ]);

        $answer = $session->refresh()->answers()->where('instructor_exam_question_id', $coding->id)->first();

        $this
            ->actingAs($instructor)
            ->postJson(route('instructor.grading.api.answers.assist-answer', [$session, $answer]))
            ->assertOk()
            ->assertJsonPath('suggestion.suggested_score', 18)
            ->assertJsonPath('suggestion.provider', 'groq:openai/gpt-oss-20b')
            ->assertJsonPath('suggestion.rubric_assessment.0.criterion', 'Correct aggregate');

        $recorded = Http::recorded();
        $this->assertCount(1, $recorded);
        $request = $recorded->first()[0];
        $body = $request->body();

        $this->assertSame('https://api.groq.com/openai/v1/chat/completions', $request->url());
        $this->assertStringContainsString('Bearer test-groq-key', $request->header('Authorization')[0] ?? '');
        $this->assertStringContainsString('gpt-oss-20b', $body);
        $this->assertStringContainsString('response_format', $body);
        $this->assertStringContainsString('json_schema', $body);
        $this->assertStringContainsString('strict', $body);
        $this->assertStringContainsString('evaluation_request', $body);
        $this->assertStringContainsString('do_not_use_length_as_primary_score', $body);
    }

    public function test_written_answer_ai_assist_uses_public_pollinations_when_no_keys_are_configured(): void
    {
        $this->travelTo('2026-06-02 10:00:00');
        config([
            'services.ai_grading.provider' => 'auto',
            'services.ai_grading.google.api_key' => '',
            'services.ai_grading.groq.api_key' => '',
            'services.ai_grading.pollinations.enabled' => true,
            'services.ai_grading.pollinations.model' => 'openai',
        ]);
        Http::fake([
            'https://text.pollinations.ai/*' => Http::response(json_encode([
                'suggested_score' => 14,
                'confidence' => 0.62,
                'feedback' => 'The answer includes the main idea but misses one expected detail.',
                'strengths' => ['Mentions indexes improve lookup speed'],
                'improvements' => ['Explain maintenance cost or write overhead'],
                'rationale' => 'The response matches the core expected concept but lacks a complete tradeoff discussion.',
                'rubric_assessment' => [
                    [
                        'criterion' => 'Read performance',
                        'score' => 8,
                        'max_score' => 10,
                        'evidence' => 'Indexes help the database find matching rows faster.',
                        'notes' => 'Correct core idea.',
                    ],
                    [
                        'criterion' => 'Tradeoffs',
                        'score' => 2,
                        'max_score' => 5,
                        'evidence' => 'No mention of write overhead.',
                        'notes' => 'Incomplete discussion.',
                    ],
                ],
            ]), 200),
        ]);

        [$studentUser, $student, $course, $instructor] = $this->studentCourseContext();
        $exam = InstructorExam::create([
            'course_id' => $course->id,
            'instructor_id' => $instructor->id,
            'title' => 'Pollinations Assist Exam',
            'duration_minutes' => 60,
            'total_marks' => 100,
        ]);
        $essay = InstructorExamQuestion::create([
            'instructor_exam_id' => $exam->id,
            'type' => 'essay',
            'category' => 'text',
            'title' => 'Explain indexing',
            'position' => 1,
            'marks' => 20,
            'prompt' => ['question_text' => 'Explain why database indexes improve reads.'],
            'settings' => [
                'rubric' => 'Accuracy, completeness, tradeoffs, and clarity.',
                'expected_answer' => 'Indexes improve lookup speed but can increase write/storage cost.',
            ],
        ]);
        $assignment = ExamAssignment::create([
            'instructor_exam_id' => $exam->id,
            'course_id' => $course->id,
            'student_profile_id' => $student->id,
            'assigned_by' => $instructor->id,
            'available_at' => '2026-06-02 09:00:00',
            'due_at' => '2026-06-02 12:00:00',
            'max_attempts' => 1,
            'status' => ExamAssignment::STATUS_ASSIGNED,
        ]);

        $this->actingAs($studentUser)->post(route('student.exams.start', $assignment));
        $session = ExamSession::first();
        $this
            ->actingAs($studentUser)
            ->post(route('student.exams.sessions.submit', $session), [
                'answers' => [
                    $essay->id => [
                        'response' => 'Indexes help the database find matching rows faster because it can search an ordered structure instead of scanning every row.',
                    ],
                ],
            ]);

        $answer = $session->refresh()->answers()->where('instructor_exam_question_id', $essay->id)->first();

        $this
            ->actingAs($instructor)
            ->postJson(route('instructor.grading.api.answers.assist-answer', [$session, $answer]))
            ->assertOk()
            ->assertJsonPath('suggestion.suggested_score', 14)
            ->assertJsonPath('suggestion.provider', 'pollinations_public:openai')
            ->assertJsonPath('suggestion.rubric_assessment.0.criterion', 'Read performance');

        $recorded = Http::recorded();
        $this->assertCount(1, $recorded);
        $request = $recorded->first()[0];
        $body = urldecode($request->url());

        $this->assertStringStartsWith('https://text.pollinations.ai/', $request->url());
        $this->assertStringContainsString('json=true', $request->url());
        $this->assertStringContainsString('evaluation_request', $body);
        $this->assertStringContainsString('do_not_use_length_as_primary_score', $body);
    }

    public function test_written_answer_ai_assist_retries_pollinations_when_public_queue_is_full(): void
    {
        $this->travelTo('2026-06-02 10:00:00');
        config([
            'services.ai_grading.provider' => 'pollinations',
            'services.ai_grading.google.api_key' => '',
            'services.ai_grading.groq.api_key' => '',
            'services.ai_grading.pollinations.enabled' => true,
            'services.ai_grading.pollinations.model' => 'openai',
            'services.ai_grading.pollinations.models' => ['openai', 'mistral'],
            'services.ai_grading.pollinations.max_attempts' => 2,
            'services.ai_grading.pollinations.retry_delay_seconds' => 0,
        ]);
        Http::fakeSequence('https://text.pollinations.ai/*')
            ->push([
                'error' => 'Queue full for IP: 1 requests already queued (max: 1).',
                'status' => 429,
            ], 429)
            ->push(json_encode([
                'suggested_score' => 16,
                'confidence' => 0.7,
                'feedback' => 'The answer is mostly correct after retry.',
                'strengths' => ['Explains faster lookup'],
                'improvements' => ['Add one more tradeoff detail'],
                'rationale' => 'The response includes the main expected concept and one tradeoff.',
                'rubric_assessment' => [
                    [
                        'criterion' => 'Core concept',
                        'score' => 10,
                        'max_score' => 10,
                        'evidence' => 'Indexes help the database find matching rows faster.',
                        'notes' => 'Correct.',
                    ],
                ],
            ]), 200);

        [$studentUser, $student, $course, $instructor] = $this->studentCourseContext();
        $exam = InstructorExam::create([
            'course_id' => $course->id,
            'instructor_id' => $instructor->id,
            'title' => 'Pollinations Retry Exam',
            'duration_minutes' => 60,
            'total_marks' => 100,
        ]);
        $essay = InstructorExamQuestion::create([
            'instructor_exam_id' => $exam->id,
            'type' => 'essay',
            'category' => 'text',
            'title' => 'Explain indexing',
            'position' => 1,
            'marks' => 20,
            'prompt' => ['question_text' => 'Explain why database indexes improve reads.'],
            'settings' => [
                'rubric' => 'Accuracy, completeness, tradeoffs, and clarity.',
                'expected_answer' => 'Indexes improve lookup speed but can increase write/storage cost.',
            ],
        ]);
        $assignment = ExamAssignment::create([
            'instructor_exam_id' => $exam->id,
            'course_id' => $course->id,
            'student_profile_id' => $student->id,
            'assigned_by' => $instructor->id,
            'available_at' => '2026-06-02 09:00:00',
            'due_at' => '2026-06-02 12:00:00',
            'max_attempts' => 1,
            'status' => ExamAssignment::STATUS_ASSIGNED,
        ]);

        $this->actingAs($studentUser)->post(route('student.exams.start', $assignment));
        $session = ExamSession::first();
        $this
            ->actingAs($studentUser)
            ->post(route('student.exams.sessions.submit', $session), [
                'answers' => [
                    $essay->id => [
                        'response' => 'Indexes help the database find matching rows faster and can add write overhead.',
                    ],
                ],
            ]);

        $answer = $session->refresh()->answers()->where('instructor_exam_question_id', $essay->id)->first();

        $this
            ->actingAs($instructor)
            ->postJson(route('instructor.grading.api.answers.assist-answer', [$session, $answer]))
            ->assertOk()
            ->assertJsonPath('suggestion.suggested_score', 16)
            ->assertJsonPath('suggestion.provider', 'pollinations_public:openai')
            ->assertJsonPath('suggestion.rubric_assessment.0.criterion', 'Core concept')
            ->assertJsonPath('suggestion.provider_note', fn (string $note): bool => str_contains($note, 'Public endpoint retries used'));

        $this->assertCount(2, Http::recorded());
    }

    public function test_browser_ai_assist_suggestion_can_be_saved_for_keyboard_answer(): void
    {
        $this->travelTo('2026-06-02 10:00:00');

        [$studentUser, $student, $course, $instructor] = $this->studentCourseContext();
        $exam = InstructorExam::create([
            'course_id' => $course->id,
            'instructor_id' => $instructor->id,
            'title' => 'Browser Assist Exam',
            'duration_minutes' => 60,
            'total_marks' => 100,
        ]);
        $essay = InstructorExamQuestion::create([
            'instructor_exam_id' => $exam->id,
            'type' => 'essay',
            'category' => 'text',
            'title' => 'Explain indexing',
            'position' => 1,
            'marks' => 20,
            'prompt' => ['question_text' => 'Explain why database indexes improve reads.'],
            'settings' => [
                'rubric' => 'Accuracy, completeness, tradeoffs, and clarity.',
                'expected_answer' => 'Indexes improve lookup speed but can increase write/storage cost.',
            ],
        ]);
        $assignment = ExamAssignment::create([
            'instructor_exam_id' => $exam->id,
            'course_id' => $course->id,
            'student_profile_id' => $student->id,
            'assigned_by' => $instructor->id,
            'available_at' => '2026-06-02 09:00:00',
            'due_at' => '2026-06-02 12:00:00',
            'max_attempts' => 1,
            'status' => ExamAssignment::STATUS_ASSIGNED,
        ]);

        $this->actingAs($studentUser)->post(route('student.exams.start', $assignment));
        $session = ExamSession::first();
        $this
            ->actingAs($studentUser)
            ->post(route('student.exams.sessions.submit', $session), [
                'answers' => [
                    $essay->id => [
                        'response' => 'Indexes help the database find matching rows faster and can add write overhead.',
                    ],
                ],
            ]);

        $answer = $session->refresh()->answers()->where('instructor_exam_question_id', $essay->id)->first();

        $this
            ->actingAs($instructor)
            ->postJson(route('instructor.grading.api.answers.assist-browser', [$session, $answer]), [
                'suggested_score' => 17,
                'max_score' => 20,
                'confidence' => 0.74,
                'feedback' => 'Browser AI says the answer is mostly correct.',
                'rationale' => 'The answer explains lookup speed and a write overhead tradeoff.',
                'strengths' => ['Mentions lookup speed'],
                'improvements' => ['Could mention storage cost'],
                'rubric_assessment' => [
                    [
                        'criterion' => 'Tradeoff',
                        'score' => 4,
                        'max_score' => 5,
                        'evidence' => 'can add write overhead',
                        'notes' => 'Correct tradeoff.',
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('suggestion.suggested_score', 17)
            ->assertJsonPath('suggestion.provider', 'puter_browser:gpt-5-nano')
            ->assertJsonPath('suggestion.rubric_assessment.0.criterion', 'Tradeoff');

        $suggestion = $answer->refresh()->answer_payload['ai_grading_suggestion'] ?? [];

        $this->assertSame('puter_browser:gpt-5-nano', $suggestion['provider'] ?? null);
        $this->assertEquals(17.0, $suggestion['suggested_score'] ?? null);
    }

    public function test_written_answer_ai_assist_falls_back_when_google_fails(): void
    {
        $this->travelTo('2026-06-02 10:00:00');
        config([
            'services.ai_grading.provider' => 'google_gemini',
            'services.ai_grading.google.api_key' => 'test-google-key',
            'services.ai_grading.pollinations.enabled' => false,
        ]);
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response(['error' => 'Temporary API failure'], 500),
        ]);

        [$studentUser, $student, $course, $instructor] = $this->studentCourseContext();
        $exam = InstructorExam::create([
            'course_id' => $course->id,
            'instructor_id' => $instructor->id,
            'title' => 'Fallback Assist Exam',
            'duration_minutes' => 60,
            'total_marks' => 100,
        ]);
        $essay = InstructorExamQuestion::create([
            'instructor_exam_id' => $exam->id,
            'type' => 'essay',
            'category' => 'text',
            'title' => 'Explain indexing',
            'position' => 1,
            'marks' => 20,
            'prompt' => ['question_text' => 'Explain why database indexes improve reads.'],
            'settings' => ['rubric' => 'Accuracy, completeness, and clarity.'],
        ]);
        $assignment = ExamAssignment::create([
            'instructor_exam_id' => $exam->id,
            'course_id' => $course->id,
            'student_profile_id' => $student->id,
            'assigned_by' => $instructor->id,
            'available_at' => '2026-06-02 09:00:00',
            'due_at' => '2026-06-02 12:00:00',
            'max_attempts' => 1,
            'status' => ExamAssignment::STATUS_ASSIGNED,
        ]);

        $this->actingAs($studentUser)->post(route('student.exams.start', $assignment));
        $session = ExamSession::first();
        $this
            ->actingAs($studentUser)
            ->post(route('student.exams.sessions.submit', $session), [
                'answers' => [
                    $essay->id => [
                        'response' => 'Indexes help the database find matching rows faster because it can search an ordered structure instead of scanning every row.',
                    ],
                ],
            ]);

        $answer = $session->refresh()->answers()->where('instructor_exam_question_id', $essay->id)->first();

        $this
            ->actingAs($instructor)
            ->postJson(route('instructor.grading.api.answers.assist-answer', [$session, $answer]))
            ->assertOk()
            ->assertJsonPath('suggestion.provider', 'ai_provider_unavailable')
            ->assertJsonPath('suggestion.suggested_score', null)
            ->assertJsonPath('suggestion.confidence', 0)
            ->assertJsonPath('suggestion.provider_note', 'No AI score was produced because no AI provider completed the request. Add a Groq/Gemini key or keep POLLINATIONS_ENABLED=true for the public fallback.')
            ->assertJsonPath('suggestion.rationale', fn (?string $value): bool => str_contains((string) $value, 'No content-based grade was calculated locally'));

        $suggestion = $answer->refresh()->answer_payload['ai_grading_suggestion'] ?? [];

        $this->assertSame('ai_provider_unavailable', $suggestion['provider'] ?? null);
        $this->assertNull($suggestion['suggested_score'] ?? null);
        $this->assertNotEmpty($suggestion['provider_error'] ?? null);
    }

    private function studentCourseContext(): array
    {
        $instructor = User::factory()->create();
        $studentUser = User::factory()->create();
        $course = Course::create([
            'code' => 'DBS301',
            'name' => 'Database Systems',
            'is_active' => true,
        ]);
        $student = StudentProfile::create([
            'user_id' => $studentUser->id,
            'student_number' => 'S1001',
            'academic_status' => StudentProfile::STATUS_ACTIVE,
        ]);
        $student->courses()->attach($course->id, [
            'enrollment_status' => 'enrolled',
            'enrolled_at' => now(),
        ]);

        return [$studentUser, $student, $course, $instructor];
    }

    private function examForCourse(Course $course, User $instructor, string $title): InstructorExam
    {
        return InstructorExam::create([
            'course_id' => $course->id,
            'instructor_id' => $instructor->id,
            'title' => $title,
            'duration_minutes' => 60,
            'total_marks' => 100,
        ]);
    }
}
