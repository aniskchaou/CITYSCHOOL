<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_STUDENT')]
#[Route('/portal')]
class StudentPortalController extends AbstractController
{
    // ── Shared mock data ────────────────────────────────────────────────────

    private function getStudent(): array
    {
        return [
            'name'       => 'Alice Johnson',
            'username'   => $this->getUser()?->getUserIdentifier() ?? 'student',
            'email'      => 'alice.johnson@cityschool.edu',
            'grade'      => '10th Grade',
            'student_id' => 'CS-2024-0042',
            'avatar'     => '/dummy/student-lg-1.jpg',
            'gpa'        => 3.8,
            'attendance' => 94,
            'rank'       => 5,
            'total_students' => 32,
        ];
    }

    private function getCourses(): array
    {
        return [
            ['id' => 1, 'name' => 'Mathematics',        'teacher' => 'Mr. Roberts',   'color' => '#4361ee', 'icon' => 'calculator',  'progress' => 72, 'grade' => 'A', 'score' => 92, 'next_class' => 'Today 09:00', 'materials' => 14, 'assignments_due' => 2],
            ['id' => 2, 'name' => 'Physics',            'teacher' => 'Ms. Chen',      'color' => '#f72585', 'icon' => 'flask',        'progress' => 60, 'grade' => 'B+','score' => 87, 'next_class' => 'Today 11:00', 'materials' => 11, 'assignments_due' => 1],
            ['id' => 3, 'name' => 'Chemistry',          'teacher' => 'Dr. Williams',  'color' => '#f77f00', 'icon' => 'eyedropper',   'progress' => 55, 'grade' => 'A-','score' => 90, 'next_class' => 'Tomorrow 10:00', 'materials' => 9,  'assignments_due' => 0],
            ['id' => 4, 'name' => 'English Literature', 'teacher' => 'Mrs. Davis',    'color' => '#2dc653', 'icon' => 'book',         'progress' => 80, 'grade' => 'A', 'score' => 95, 'next_class' => 'Today 14:00', 'materials' => 18, 'assignments_due' => 1],
            ['id' => 5, 'name' => 'Computer Science',  'teacher' => 'Mr. Singh',     'color' => '#7209b7', 'icon' => 'laptop',       'progress' => 68, 'grade' => 'A+','score' => 98, 'next_class' => 'Tomorrow 13:00', 'materials' => 22, 'assignments_due' => 3],
            ['id' => 6, 'name' => 'History',            'teacher' => 'Ms. Thompson',  'color' => '#b5838d', 'icon' => 'globe',        'progress' => 45, 'grade' => 'B', 'score' => 83, 'next_class' => 'Wed 09:00',   'materials' => 10, 'assignments_due' => 0],
        ];
    }

    private function getAssignments(): array
    {
        return [
            ['id'=>1, 'title'=>'Calculus Problem Set 5',      'course'=>'Mathematics',       'due'=>'2026-05-03', 'status'=>'pending',   'points'=>100, 'score'=>null,  'feedback'=>null],
            ['id'=>2, 'title'=>'Newton\'s Laws Lab Report',   'course'=>'Physics',           'due'=>'2026-05-05', 'status'=>'pending',   'points'=>50,  'score'=>null,  'feedback'=>null],
            ['id'=>3, 'title'=>'Periodic Table Quiz Prep',    'course'=>'Chemistry',         'due'=>'2026-04-28', 'status'=>'submitted', 'points'=>30,  'score'=>27,    'feedback'=>'Great work!'],
            ['id'=>4, 'title'=>'Essay: The Great Gatsby',     'course'=>'English Literature','due'=>'2026-05-10', 'status'=>'pending',   'points'=>200, 'score'=>null,  'feedback'=>null],
            ['id'=>5, 'title'=>'Algorithm Analysis Project',  'course'=>'Computer Science',  'due'=>'2026-05-02', 'status'=>'overdue',   'points'=>150, 'score'=>null,  'feedback'=>null],
            ['id'=>6, 'title'=>'Binary Search Tree Impl.',    'course'=>'Computer Science',  'due'=>'2026-05-08', 'status'=>'pending',   'points'=>100, 'score'=>null,  'feedback'=>null],
            ['id'=>7, 'title'=>'WWI Causes Essay',            'course'=>'History',           'due'=>'2026-04-20', 'status'=>'graded',    'points'=>100, 'score'=>85,    'feedback'=>'Good analysis, needs more sources.'],
            ['id'=>8, 'title'=>'Python Data Structures',      'course'=>'Computer Science',  'due'=>'2026-04-15', 'status'=>'graded',    'points'=>100, 'score'=>98,    'feedback'=>'Excellent! Perfect implementation.'],
        ];
    }

    private function getAnnouncements(): array
    {
        return [
            ['id'=>1, 'title'=>'Mid-term Exam Schedule Released',   'body'=>'Mid-term exams will be held May 15–19. Check the full schedule on the portal.', 'date'=>'2026-04-29', 'type'=>'exam',     'author'=>'Administration'],
            ['id'=>2, 'title'=>'Science Fair Registration Open',    'body'=>'Register your project for the annual science fair by May 10. Great prizes await!',  'date'=>'2026-04-27', 'type'=>'event',    'author'=>'Mr. Roberts'],
            ['id'=>3, 'title'=>'Library Extended Hours',            'body'=>'The library will be open until 9 PM from May 1–19 to support exam preparation.',     'date'=>'2026-04-25', 'type'=>'general',  'author'=>'Library Staff'],
            ['id'=>4, 'title'=>'CS Club Meeting – This Friday',     'body'=>'Join us for our weekly CS Club meeting. Topic: Machine Learning Basics.',            'date'=>'2026-04-24', 'type'=>'club',     'author'=>'Mr. Singh'],
        ];
    }

    private function getStudentAccountAccess(): array
    {
        return [
            'emailLogin' => 'alice.johnson@cityschool.edu',
            'googleConnected' => true,
            'microsoftConnected' => false,
            'twoFactorEnabled' => false,
            'lastSecurityUpdate' => 'Apr 26, 2026',
        ];
    }

    private function getStudentNotificationPreferences(): array
    {
        return [
            'assignments' => true,
            'grades' => true,
            'announcements' => true,
            'messages' => true,
            'liveClasses' => false,
            'delivery' => 'App + Email',
        ];
    }

    private function getStudentPrivacySettings(): array
    {
        return [
            'showProfilePhoto' => true,
            'showProgressToPeers' => false,
            'allowMentorMessages' => true,
            'shareActivityStatus' => false,
        ];
    }

    private function getStudentLearningPreferences(): array
    {
        return [
            'style' => 'Visual + Hands-on examples',
            'pace' => 'Moderate with checkpoint reviews',
            'studyWindow' => '6:30 PM - 8:00 PM',
            'supportMode' => 'Short examples before full exercises',
        ];
    }

    private function getStudentGoals(): array
    {
        return [
            ['subject' => 'Mathematics', 'targetGrade' => 'A', 'currentScore' => 92, 'goal' => 'Maintain top 5 rank in class.'],
            ['subject' => 'Physics', 'targetGrade' => 'A-', 'currentScore' => 87, 'goal' => 'Improve lab accuracy and exam confidence.'],
            ['subject' => 'Computer Science', 'targetGrade' => 'A+', 'currentScore' => 98, 'goal' => 'Build a standout final project portfolio.'],
        ];
    }

    private function getStudentTodaySchedule(): array
    {
        return [
            ['course' => 'Mathematics', 'time' => '09:00-10:00', 'room' => 'Room 201', 'teacher' => 'Mr. Roberts', 'type' => 'class', 'live' => true],
            ['course' => 'Physics', 'time' => '11:00-12:00', 'room' => 'Lab 105', 'teacher' => 'Ms. Chen', 'type' => 'lab', 'live' => false],
            ['course' => 'English Literature', 'time' => '14:00-15:00', 'room' => 'Room 302', 'teacher' => 'Mrs. Davis', 'type' => 'seminar', 'live' => true],
        ];
    }

    private function getUpcomingAssessments(): array
    {
        return [
            ['title' => 'Calculus Problem Set 5', 'course' => 'Mathematics', 'due' => 'May 3', 'kind' => 'assignment'],
            ['title' => 'Newton\'s Laws Quiz', 'course' => 'Physics', 'due' => 'May 5', 'kind' => 'test'],
            ['title' => 'Algorithm Analysis Project', 'course' => 'Computer Science', 'due' => 'May 2', 'kind' => 'assignment'],
        ];
    }

    private function getTodayFocus(): array
    {
        return [
            'headline' => 'Focus on overdue Computer Science work first, then review Physics concepts ahead of the quiz.',
            'priorities' => [
                'Finish the Algorithm Analysis Project draft before 5 PM.',
                'Review Newton\'s Laws summary notes during your study window.',
                'Check Mathematics feedback before tomorrow\'s problem set deadline.',
            ],
        ];
    }

    private function getProgressSnapshot(): array
    {
        return [
            'gradeAverage' => 'A-',
            'attendanceRate' => '94%',
            'tasksOpen' => 4,
            'courseCompletion' => '63%',
        ];
    }

    private function getUrgentAlerts(): array
    {
        return [
            ['label' => 'Missing work', 'message' => 'Algorithm Analysis Project is overdue and needs submission today.', 'severity' => 'high'],
            ['label' => 'Exam soon', 'message' => 'Physics quiz opens in 3 days. Review lab concepts tonight.', 'severity' => 'medium'],
        ];
    }

    private function getCourseLessons(string $courseName): array
    {
        $lessonsByCourse = [
            'Mathematics' => [
                ['title' => 'Integration Techniques Overview', 'type' => 'document', 'completion' => 100, 'bookmarked' => true],
                ['title' => 'Substitution Method Walkthrough', 'type' => 'video', 'completion' => 70, 'bookmarked' => false],
                ['title' => 'Practice Set: Definite Integrals', 'type' => 'link', 'completion' => 45, 'bookmarked' => false],
            ],
            'Physics' => [
                ['title' => 'Newton\'s Laws Revision Sheet', 'type' => 'document', 'completion' => 85, 'bookmarked' => true],
                ['title' => 'Motion Lab Demonstration', 'type' => 'video', 'completion' => 55, 'bookmarked' => false],
                ['title' => 'Force Diagrams Practice Hub', 'type' => 'link', 'completion' => 30, 'bookmarked' => true],
            ],
            'Chemistry' => [
                ['title' => 'Periodic Trends Summary', 'type' => 'document', 'completion' => 90, 'bookmarked' => false],
                ['title' => 'Bonding Concepts Video', 'type' => 'video', 'completion' => 60, 'bookmarked' => false],
            ],
            'English Literature' => [
                ['title' => 'The Great Gatsby Context Notes', 'type' => 'document', 'completion' => 100, 'bookmarked' => true],
                ['title' => 'Character Themes Discussion', 'type' => 'video', 'completion' => 80, 'bookmarked' => false],
            ],
            'Computer Science' => [
                ['title' => 'Algorithm Complexity Cheatsheet', 'type' => 'document', 'completion' => 60, 'bookmarked' => true],
                ['title' => 'Binary Search Tree Demo', 'type' => 'video', 'completion' => 40, 'bookmarked' => false],
                ['title' => 'Coding Practice Sandbox', 'type' => 'link', 'completion' => 20, 'bookmarked' => true],
            ],
            'History' => [
                ['title' => 'WWI Causes Timeline', 'type' => 'document', 'completion' => 75, 'bookmarked' => false],
                ['title' => 'Primary Sources Workshop', 'type' => 'video', 'completion' => 50, 'bookmarked' => false],
            ],
        ];

        return $lessonsByCourse[$courseName] ?? [];
    }

    private function getLearningPaths(string $courseName): array
    {
        $paths = [
            'Mathematics' => [
                ['step' => 'Review solved examples', 'status' => 'done'],
                ['step' => 'Complete substitution drills', 'status' => 'current'],
                ['step' => 'Take mastery checkpoint', 'status' => 'next'],
            ],
            'Physics' => [
                ['step' => 'Refresh notes on Newton\'s Laws', 'status' => 'done'],
                ['step' => 'Watch motion lab recap', 'status' => 'current'],
                ['step' => 'Answer force-diagram quiz', 'status' => 'next'],
            ],
            'Computer Science' => [
                ['step' => 'Read algorithm complexity notes', 'status' => 'done'],
                ['step' => 'Finish tree traversal exercises', 'status' => 'current'],
                ['step' => 'Submit project milestone', 'status' => 'next'],
            ],
        ];

        return $paths[$courseName] ?? [
            ['step' => 'Open current lesson materials', 'status' => 'done'],
            ['step' => 'Complete assigned practice', 'status' => 'current'],
            ['step' => 'Review teacher feedback', 'status' => 'next'],
        ];
    }

    private function getResumeState(string $courseName): array
    {
        $states = [
            'Mathematics' => ['item' => 'Substitution Method Walkthrough', 'detail' => 'Resume at 08:14 where the example switches to trig substitution.'],
            'Physics' => ['item' => 'Motion Lab Demonstration', 'detail' => 'Resume at the force-and-acceleration experiment section.'],
            'Computer Science' => ['item' => 'Binary Search Tree Demo', 'detail' => 'Resume at the insertion and traversal segment.'],
        ];

        return $states[$courseName] ?? ['item' => 'Latest lesson resource', 'detail' => 'Resume from your most recent viewed module.'];
    }

    private function getSmartRecommendations(string $courseName): array
    {
        $recommendations = [
            'Mathematics' => [
                'Revisit substitution examples before attempting the graded practice set.',
                'Use the bookmarked formula sheet during tonight\'s revision block.',
            ],
            'Physics' => [
                'Prioritize force-diagram questions to strengthen quiz readiness.',
                'Replay the motion lab video at 1.25x and take summary notes.',
            ],
            'Computer Science' => [
                'Focus on tree traversal patterns before returning to the overdue project.',
                'Complete the sandbox challenge to improve weak recursion accuracy.',
            ],
        ];

        return $recommendations[$courseName] ?? [
            'Review your weakest recent topic before starting the next assignment.',
            'Reopen the teacher\'s top recommended resource and take short revision notes.',
        ];
    }

    private function getSubmissionHistory(): array
    {
        return [
            [
                'assignmentId' => 8,
                'assignment' => 'Python Data Structures',
                'course' => 'Computer Science',
                'submittedAt' => '2026-04-15 19:12',
                'method' => 'File + Notes',
                'version' => 'v2',
                'status' => 'graded',
                'receipt' => 'RCP-20260415-1032',
                'grade' => '98/100',
                'feedback' => 'Excellent implementation and clean explanation.',
                'canEdit' => false,
            ],
            [
                'assignmentId' => 3,
                'assignment' => 'Periodic Table Quiz Prep',
                'course' => 'Chemistry',
                'submittedAt' => '2026-04-28 17:40',
                'method' => 'Text Editor',
                'version' => 'v1',
                'status' => 'submitted',
                'receipt' => 'RCP-20260428-2184',
                'grade' => null,
                'feedback' => 'Awaiting teacher review.',
                'canEdit' => true,
            ],
            [
                'assignmentId' => 1,
                'assignment' => 'Calculus Problem Set 5',
                'course' => 'Mathematics',
                'submittedAt' => '2026-05-01 18:09',
                'method' => 'Link + File',
                'version' => 'draft',
                'status' => 'draft',
                'receipt' => 'RCP-20260501-3341',
                'grade' => null,
                'feedback' => 'Draft saved, final submission pending.',
                'canEdit' => true,
            ],
        ];
    }

    private function getDeadlineCountdowns(): array
    {
        return [
            ['assignment' => 'Algorithm Analysis Project', 'countdown' => '5h 10m', 'urgency' => 'high'],
            ['assignment' => 'Calculus Problem Set 5', 'countdown' => '1d 14h', 'urgency' => 'medium'],
            ['assignment' => 'Newton\'s Laws Lab Report', 'countdown' => '3d 02h', 'urgency' => 'low'],
        ];
    }

    private function getDraftStatus(): array
    {
        return [
            'autosave' => 'ON (every 30 sec)',
            'lastSavedAt' => 'Today 16:22',
            'currentDraftWords' => 342,
            'lastCheckedSimilarity' => '8%',
        ];
    }

    private function getQuizReviews(): array
    {
        return [
            [
                'quiz' => 'Periodic Elements Quiz',
                'course' => 'Chemistry',
                'correct' => 18,
                'incorrect' => 2,
                'weakTopics' => ['Electron affinity', 'Transition metals'],
            ],
            [
                'quiz' => 'Grammar & Composition',
                'course' => 'English Literature',
                'correct' => 22,
                'incorrect' => 3,
                'weakTopics' => ['Comma splices', 'Parallel structure'],
            ],
        ];
    }

    private function getMockTests(): array
    {
        return [
            ['title' => 'Math Mock Test - Integrals', 'course' => 'Mathematics', 'questions' => 20, 'mode' => 'practice'],
            ['title' => 'Physics Mock Test - Motion', 'course' => 'Physics', 'questions' => 18, 'mode' => 'practice'],
            ['title' => 'CS Mock Test - Algorithms', 'course' => 'Computer Science', 'questions' => 25, 'mode' => 'practice'],
        ];
    }

    private function getAdaptiveQuizPlan(): array
    {
        return [
            'status' => 'Ready',
            'currentLevel' => 'Intermediate',
            'nextAdjustment' => 'Difficulty increases after 3 correct answers in a row.',
        ];
    }

    private function getExamSimulationModes(): array
    {
        return [
            ['name' => 'Mid-term Simulation', 'duration' => 60, 'rules' => 'Single attempt, strict timer'],
            ['name' => 'Final Exam Simulation', 'duration' => 90, 'rules' => 'No hints, auto-submit on timeout'],
        ];
    }

    private function getPerformanceByTopic(): array
    {
        return [
            ['topic' => 'Algebra', 'accuracy' => 92, 'trend' => 'up'],
            ['topic' => 'Newtonian Mechanics', 'accuracy' => 78, 'trend' => 'steady'],
            ['topic' => 'Grammar', 'accuracy' => 88, 'trend' => 'up'],
            ['topic' => 'Algorithms', 'accuracy' => 73, 'trend' => 'down'],
        ];
    }

    private function getRevisionQuestionBank(): array
    {
        return [
            ['topic' => 'Integrals', 'questions' => 42, 'difficulty' => 'Mixed'],
            ['topic' => 'Force & Motion', 'questions' => 36, 'difficulty' => 'Intermediate'],
            ['topic' => 'Periodic Trends', 'questions' => 28, 'difficulty' => 'Beginner'],
            ['topic' => 'Data Structures', 'questions' => 55, 'difficulty' => 'Advanced'],
        ];
    }

    private function getSubjectExamGrades(): array
    {
        return [
            ['subject' => 'Mathematics', 'exam' => 'Unit Test 4', 'current' => 92, 'previous' => 87, 'teacherComment' => 'Great consistency. Keep solving mixed-difficulty sets.', 'classAverage' => 81],
            ['subject' => 'Physics', 'exam' => 'Lab Assessment', 'current' => 84, 'previous' => 79, 'teacherComment' => 'Lab reasoning improved. Work on concise conclusions.', 'classAverage' => 78],
            ['subject' => 'Chemistry', 'exam' => 'Periodic Trends Quiz', 'current' => 90, 'previous' => 85, 'teacherComment' => 'Strong conceptual understanding.', 'classAverage' => 80],
            ['subject' => 'English Literature', 'exam' => 'Essay Analysis', 'current' => 88, 'previous' => 82, 'teacherComment' => 'Argument is clear. Add deeper textual evidence.', 'classAverage' => 83],
            ['subject' => 'Computer Science', 'exam' => 'Algorithms Mid-check', 'current' => 95, 'previous' => 89, 'teacherComment' => 'Excellent logic and implementation quality.', 'classAverage' => 84],
        ];
    }

    private function getGradeTrendSeries(): array
    {
        return [
            ['label' => 'Term 1', 'score' => 82],
            ['label' => 'Term 2', 'score' => 85],
            ['label' => 'Term 3', 'score' => 88],
            ['label' => 'Term 4', 'score' => 90],
        ];
    }

    private function getStrengthWeaknessAnalysis(): array
    {
        return [
            'strengths' => ['Algorithm design', 'Mathematical problem solving', 'Essay structure'],
            'weaknesses' => ['Physics exam timing', 'History source citation depth'],
            'improvements' => [
                'Practice 15-minute Physics timed drills three times this week.',
                'Use a source-citation checklist before submitting History responses.',
                'Review teacher comments before each resubmission to close feedback loops.',
            ],
        ];
    }

    private function getClassTimetable(): array
    {
        return [
            ['day' => 'Monday', 'time' => '09:00-10:00', 'subject' => 'Mathematics', 'room' => 'Room 201'],
            ['day' => 'Monday', 'time' => '11:00-12:00', 'subject' => 'Physics', 'room' => 'Lab 105'],
            ['day' => 'Tuesday', 'time' => '10:00-11:00', 'subject' => 'Chemistry', 'room' => 'Room 210'],
            ['day' => 'Wednesday', 'time' => '13:00-14:00', 'subject' => 'Computer Science', 'room' => 'Lab 301'],
            ['day' => 'Thursday', 'time' => '14:00-15:00', 'subject' => 'English Literature', 'room' => 'Room 302'],
            ['day' => 'Friday', 'time' => '09:00-10:00', 'subject' => 'History', 'room' => 'Room 118'],
        ];
    }

    private function getExamCalendar(): array
    {
        return [
            ['subject' => 'Mathematics', 'exam' => 'Mid-term Paper', 'date' => '2026-05-15', 'time' => '09:00', 'duration' => '90 min'],
            ['subject' => 'Physics', 'exam' => 'Practical + Viva', 'date' => '2026-05-16', 'time' => '11:00', 'duration' => '75 min'],
            ['subject' => 'Computer Science', 'exam' => 'Theory Test', 'date' => '2026-05-18', 'time' => '13:00', 'duration' => '120 min'],
        ];
    }

    private function getAssignmentDeadlineCalendar(): array
    {
        return [
            ['title' => 'Algorithm Analysis Project', 'course' => 'Computer Science', 'deadline' => '2026-05-02 23:59', 'status' => 'urgent'],
            ['title' => 'Calculus Problem Set 5', 'course' => 'Mathematics', 'deadline' => '2026-05-03 22:00', 'status' => 'upcoming'],
            ['title' => 'Newton\'s Laws Lab Report', 'course' => 'Physics', 'deadline' => '2026-05-05 20:00', 'status' => 'upcoming'],
        ];
    }

    private function getCalendarSyncOptions(): array
    {
        return [
            ['name' => 'Google Calendar', 'connected' => true],
            ['name' => 'Microsoft Outlook', 'connected' => false],
            ['name' => 'Apple Calendar', 'connected' => false],
        ];
    }

    private function getSmartScheduleReminders(): array
    {
        return [
            ['label' => 'Class reminder', 'message' => 'Physics starts in 45 minutes. Review force diagrams.', 'type' => 'info'],
            ['label' => 'Deadline reminder', 'message' => 'Algorithm Analysis Project due tonight at 23:59.', 'type' => 'danger'],
            ['label' => 'Exam reminder', 'message' => 'Mathematics mid-term in 3 days. Plan 2 revision blocks.', 'type' => 'warning'],
        ];
    }

    private function getScheduleConflicts(): array
    {
        return [
            ['itemA' => 'Physics practical prep', 'itemB' => 'CS project deadline', 'impact' => 'High', 'resolution' => 'Start CS upload before 7 PM, shift Physics prep to 8 PM.'],
            ['itemA' => 'Math revision session', 'itemB' => 'History reading block', 'impact' => 'Medium', 'resolution' => 'Split into two 30-minute focused sessions.'],
        ];
    }

    private function getStudyPlanAssistant(): array
    {
        return [
            ['slot' => '18:00-18:40', 'task' => 'Finalize CS project fixes', 'goal' => 'Submit before deadline'],
            ['slot' => '18:50-19:20', 'task' => 'Physics formula review', 'goal' => 'Boost quiz speed'],
            ['slot' => '19:30-20:00', 'task' => 'Math mixed problem set', 'goal' => 'Retain high score trend'],
        ];
    }

    private function getLiveSharedMaterials(): array
    {
        return [
            ['title' => 'Integration Techniques Live Slides', 'course' => 'Mathematics', 'type' => 'pdf'],
            ['title' => 'Physics Formula Sheet', 'course' => 'Physics', 'type' => 'document'],
            ['title' => 'Sorting Algorithms Whiteboard Snapshot', 'course' => 'Computer Science', 'type' => 'image'],
        ];
    }

    private function getVideoTools(): array
    {
        return [
            ['name' => 'Zoom Classroom', 'status' => 'available'],
            ['name' => 'Microsoft Teams Class', 'status' => 'available'],
            ['name' => 'School Meet Bridge', 'status' => 'connected'],
        ];
    }

    private function getAutoRecordings(): array
    {
        return [
            ['course' => 'Mathematics', 'session' => 'Integration Techniques', 'savedTo' => 'Course Materials > Week 6'],
            ['course' => 'Physics', 'session' => 'Thermodynamics Intro', 'savedTo' => 'Course Materials > Week 5'],
        ];
    }

    private function getLivePollsAndQuizzes(): array
    {
        return [
            ['title' => 'Quick Check: Integration Rules', 'type' => 'poll', 'questions' => 3],
            ['title' => 'Physics Warm-up Quiz', 'type' => 'quiz', 'questions' => 5],
        ];
    }

    private function getMessagingPolicy(): array
    {
        return [
            'canMessageTeachers' => true,
            'canMessageClassmates' => true,
            'classmateRule' => 'Classmate messaging is enabled for enrolled course groups only.',
        ];
    }

    private function getThreadedDiscussions(): array
    {
        return [
            ['topic' => 'Algorithms Mid-check Help', 'channel' => '#computer-science', 'replies' => 12, 'last' => '15 min ago'],
            ['topic' => 'Physics Lab Q&A', 'channel' => '#physics-lab', 'replies' => 9, 'last' => '1 hour ago'],
        ];
    }

    private function getTagSuggestions(): array
    {
        return ['@teacher', '@classmate', '@lab-group', '@math-club'];
    }

    private function getLessonQuestionsBoard(): array
    {
        return [
            ['lesson' => 'Substitution Method Walkthrough', 'question' => 'When should trig substitution be preferred?', 'status' => 'answered'],
            ['lesson' => 'Motion Lab Demonstration', 'question' => 'Can we use the same setup for variable mass systems?', 'status' => 'open'],
        ];
    }

    private function getGroupProjects(): array
    {
        return [
            ['id' => 1, 'name' => 'Physics Experiment Presentation', 'course' => 'Physics', 'team' => ['Alice', 'Sarah', 'Bob'], 'status' => 'active', 'completion' => 68, 'deadline' => '2026-05-09'],
            ['id' => 2, 'name' => 'History Debate Research', 'course' => 'History', 'team' => ['Alice', 'Chris', 'Emma'], 'status' => 'planning', 'completion' => 35, 'deadline' => '2026-05-14'],
        ];
    }

    private function getGroupWorkspace(): array
    {
        return [
            ['title' => 'Shared outline document', 'type' => 'document', 'updated' => 'Today 10:15'],
            ['title' => 'Lab data notes', 'type' => 'notes', 'updated' => 'Today 09:42'],
            ['title' => 'Presentation draft slides', 'type' => 'slides', 'updated' => 'Yesterday 18:20'],
        ];
    }

    private function getGroupTaskAssignments(): array
    {
        return [
            ['task' => 'Finalize intro slides', 'assignee' => 'Alice', 'due' => '2026-05-04', 'status' => 'in progress'],
            ['task' => 'Collect experiment visuals', 'assignee' => 'Sarah', 'due' => '2026-05-05', 'status' => 'open'],
            ['task' => 'Proofread final report', 'assignee' => 'Bob', 'due' => '2026-05-07', 'status' => 'open'],
        ];
    }

    private function getGroupVersionHistory(): array
    {
        return [
            ['asset' => 'Presentation draft slides', 'version' => 'v4', 'author' => 'Alice', 'time' => 'Today 10:20'],
            ['asset' => 'Lab data notes', 'version' => 'v3', 'author' => 'Sarah', 'time' => 'Today 09:40'],
            ['asset' => 'Shared outline document', 'version' => 'v6', 'author' => 'Bob', 'time' => 'Yesterday 20:11'],
        ];
    }

    private function getNotificationAlerts(): array
    {
        return [
            ['category' => 'assignment', 'title' => 'New assignment posted', 'message' => 'Mathematics: Problem Set 6 is now available.', 'important' => true, 'time' => '10 min ago'],
            ['category' => 'grade', 'title' => 'Grade posted', 'message' => 'Physics Lab Assessment score: 84%.', 'important' => true, 'time' => '45 min ago'],
            ['category' => 'message', 'title' => 'New message', 'message' => 'Mr. Roberts replied to your question.', 'important' => false, 'time' => '1 hour ago'],
            ['category' => 'announcement', 'title' => 'School announcement', 'message' => 'Library extended hours this week.', 'important' => false, 'time' => 'Today 08:10'],
        ];
    }

    private function getDigestSettings(): array
    {
        return [
            'dailyDigest' => true,
            'weeklyDigest' => false,
            'digestTime' => '19:00',
        ];
    }

    private function getNotificationChannels(): array
    {
        return [
            'app' => true,
            'email' => true,
            'sms' => false,
        ];
    }

    private function getResourceFiles(): array
    {
        return [
            ['name' => 'Calculus Notes - Week 6.pdf', 'subject' => 'Mathematics', 'type' => 'pdf', 'size' => '1.8 MB', 'updated' => 'Today 09:40'],
            ['name' => 'Physics Lab Data.xlsx', 'subject' => 'Physics', 'type' => 'sheet', 'size' => '920 KB', 'updated' => 'Today 08:55'],
            ['name' => 'Algorithm Cheat Sheet.docx', 'subject' => 'Computer Science', 'type' => 'doc', 'size' => '540 KB', 'updated' => 'Yesterday 18:12'],
        ];
    }

    private function getNotesCollection(): array
    {
        return [
            ['title' => 'Integration formulas quick notes', 'subject' => 'Mathematics', 'tag' => 'Exam Prep'],
            ['title' => 'Lab report checklist', 'subject' => 'Physics', 'tag' => 'Assignment'],
            ['title' => 'Tree traversal patterns', 'subject' => 'Computer Science', 'tag' => 'Practice'],
        ];
    }

    private function getSharedResourcesLibrary(): array
    {
        return [
            ['title' => 'Teacher shared revision pack', 'owner' => 'Mr. Roberts', 'subject' => 'Mathematics'],
            ['title' => 'Class practical guide', 'owner' => 'Ms. Chen', 'subject' => 'Physics'],
            ['title' => 'Peer coding examples', 'owner' => 'CS Study Group', 'subject' => 'Computer Science'],
        ];
    }

    private function getCloudIntegrations(): array
    {
        return [
            ['name' => 'Google Drive', 'connected' => true],
            ['name' => 'Microsoft OneDrive', 'connected' => false],
        ];
    }

    private function getSubjectFolders(): array
    {
        return [
            ['subject' => 'Mathematics', 'files' => 14],
            ['subject' => 'Physics', 'files' => 11],
            ['subject' => 'Computer Science', 'files' => 22],
            ['subject' => 'English Literature', 'files' => 9],
        ];
    }

    private function getBadgesAndAchievements(): array
    {
        return [
            ['name' => 'Honor Roll', 'earned' => true, 'icon' => 'star'],
            ['name' => 'Study Streak 7+', 'earned' => true, 'icon' => 'fire'],
            ['name' => 'Team Collaborator', 'earned' => false, 'icon' => 'users'],
            ['name' => 'Quiz Master', 'earned' => true, 'icon' => 'trophy'],
        ];
    }

    private function getProgressMilestones(): array
    {
        return [
            ['title' => 'Complete 10 assignments', 'progress' => 80],
            ['title' => 'Maintain GPA above 3.7', 'progress' => 100],
            ['title' => 'Attend 95% of classes', 'progress' => 94],
        ];
    }

    private function getStudentChallenges(): array
    {
        return [
            ['name' => 'Math Sprint Week', 'status' => 'active', 'reward' => '150 points'],
            ['name' => 'Physics Problem Solvers', 'status' => 'upcoming', 'reward' => 'Badge + 120 points'],
            ['name' => 'CS Revision Marathon', 'status' => 'completed', 'reward' => 'Level boost'],
        ];
    }

    private function getLeaderboard(): array
    {
        return [
            ['rank' => 1, 'name' => 'Sarah K.', 'points' => 1240],
            ['rank' => 2, 'name' => 'Alice J.', 'points' => 1185],
            ['rank' => 3, 'name' => 'Bob M.', 'points' => 1102],
        ];
    }

    private function getRewardProfile(): array
    {
        return [
            'points' => 1185,
            'level' => 8,
            'nextLevelAt' => 1300,
            'motivation' => 'You are 115 points away from Level 9. Complete one challenge this week to level up.',
        ];
    }

    private function getDashboardCustomization(): array
    {
        return [
            'widgets' => [
                ['name' => 'Today Schedule', 'enabled' => true],
                ['name' => 'Upcoming Deadlines', 'enabled' => true],
                ['name' => 'Announcements', 'enabled' => true],
                ['name' => 'Study Time', 'enabled' => false],
            ],
            'layout' => 'Two-column compact',
        ];
    }

    private function getThemePreferences(): array
    {
        return [
            'selected' => 'light',
            'available' => ['light', 'dark'],
        ];
    }

    private function getAdaptiveUiSettings(): array
    {
        return [
            'enabled' => true,
            'behavior' => 'Highlights most-used widgets first and surfaces overdue tasks at top.',
        ];
    }

    private function getAccessibilityOptions(): array
    {
        return [
            'highContrast' => false,
            'largeText' => false,
            'reduceMotion' => true,
            'keyboardHints' => true,
        ];
    }

    private function getCompletedTasks(): array
    {
        return [
            ['task' => 'Submit Periodic Table Quiz Prep', 'subject' => 'Chemistry', 'completedAt' => '2026-04-28 17:40'],
            ['task' => 'Finish Python Data Structures', 'subject' => 'Computer Science', 'completedAt' => '2026-04-15 19:12'],
            ['task' => 'Attend Mathematics live class', 'subject' => 'Mathematics', 'completedAt' => '2026-05-01 09:58'],
        ];
    }

    private function getStudyTimeBySubject(): array
    {
        return [
            ['subject' => 'Mathematics', 'minutes' => 210],
            ['subject' => 'Physics', 'minutes' => 165],
            ['subject' => 'Computer Science', 'minutes' => 240],
            ['subject' => 'English Literature', 'minutes' => 120],
        ];
    }

    private function getProductivityStreaks(): array
    {
        return [
            'studyStreakDays' => 9,
            'tasksCompletedThisWeek' => 12,
            'motivationBadge' => 'Focused Learner',
        ];
    }

    private function getWeeklyPerformanceSummary(): array
    {
        return [
            'summary' => 'Strong week overall with improved Physics accuracy and consistent task completion.',
            'highlights' => [
                'Completed 4 assignments before deadline.',
                'Improved quiz average by 6%.',
                'Maintained daily study streak all week.',
            ],
        ];
    }

    private function getAiLessonQna(): array
    {
        return [
            ['lesson' => 'Substitution Method', 'question' => 'When should I use trig substitution?', 'answer' => 'Use trig substitution when expressions involve sqrt(a^2-x^2), sqrt(a^2+x^2), or sqrt(x^2-a^2).'],
            ['lesson' => 'Newton\'s Laws', 'question' => 'How do I identify net force quickly?', 'answer' => 'Draw forces first, choose axis, then sum vector components to get net force.'],
        ];
    }

    private function getMaterialSummaries(): array
    {
        return [
            ['material' => 'Integration Techniques Slides', 'summary' => 'Covers substitution, integration by parts, and common integral patterns with examples.'],
            ['material' => 'Motion Lab Notes', 'summary' => 'Explains acceleration experiments, graph interpretation, and error analysis.'],
        ];
    }

    private function getAiRecommendations(): array
    {
        return [
            'Review force-diagram drills before the Physics quiz this week.',
            'Schedule 30 minutes of algorithm complexity revision before CS project updates.',
            'Use quick recap cards after Mathematics practice to retain formulas.',
        ];
    }

    private function getAiTutorTips(): array
    {
        return [
            ['concept' => 'Integration by Parts', 'explanation' => 'Apply LIATE to select u, then use ∫u dv = uv - ∫v du.'],
            ['concept' => 'Binary Tree Traversal', 'explanation' => 'Use inorder for sorted output in BSTs and preorder for tree reconstruction.'],
        ];
    }

    private function getAutoGeneratedNotes(): array
    {
        return [
            ['lesson' => 'Physics Practical Prep', 'note' => 'Remember to log units for every measurement and include uncertainty in final result.'],
            ['lesson' => 'Essay Analysis', 'note' => 'Anchor each paragraph with one quote and one interpretation sentence.'],
        ];
    }

    private function getPersonalizedRevisionPlan(): array
    {
        return [
            ['day' => 'Monday', 'focus' => 'Physics force diagrams', 'duration' => '35 min'],
            ['day' => 'Tuesday', 'focus' => 'Mathematics substitution drills', 'duration' => '40 min'],
            ['day' => 'Wednesday', 'focus' => 'CS algorithm complexity review', 'duration' => '45 min'],
        ];
    }

    // ── Dashboard ────────────────────────────────────────────────────────────

    #[Route('/dashboard', name: 'portal_dashboard')]
    public function dashboard(Request $request): Response
    {
        $assignments = $this->getAssignments();
        $upcoming = array_filter($assignments, fn($a) => $a['status'] === 'pending' || $a['status'] === 'overdue');

        if ($request->isMethod('POST')) {
            $actionMessages = [
                'refresh_schedule' => 'Today\'s schedule refreshed.',
                'open_upcoming' => 'Upcoming assignments and tests refreshed.',
                'refresh_announcements' => 'Recent announcements refreshed.',
                'open_quick_links' => 'Quick course links refreshed.',
                'refresh_focus' => 'Today\'s focus priorities updated.',
                'refresh_snapshot' => 'Progress snapshot refreshed.',
                'refresh_urgent_alerts' => 'Urgent alerts refreshed.',
            ];
            $action = (string) $request->request->get('action', 'refresh_schedule');
            $this->addFlash('success', $actionMessages[$action] ?? 'Dashboard updated successfully.');

            return $this->redirectToRoute('portal_dashboard');
        }

        return $this->render('portal/dashboard.html.twig', [
            'student'        => $this->getStudent(),
            'courses'        => $this->getCourses(),
            'today_classes'  => $this->getStudentTodaySchedule(),
            'upcoming'       => array_values(array_slice(array_values($upcoming), 0, 5)),
            'announcements'  => array_slice($this->getAnnouncements(), 0, 3),
            'reminders'      => [
                ['text' => 'Algorithm Analysis Project is overdue!', 'type' => 'danger'],
                ['text' => 'Calculus Problem Set 5 due in 2 days',   'type' => 'warning'],
                ['text' => 'Mid-term exams start May 15',            'type' => 'info'],
            ],
            'todayFocus'     => $this->getTodayFocus(),
            'progressSnapshot' => $this->getProgressSnapshot(),
            'urgentAlerts'   => $this->getUrgentAlerts(),
            'upcomingAssessments' => $this->getUpcomingAssessments(),
        ]);
    }

    // ── Profile ───────────────────────────────────────────────────────────────

    #[Route('/profile', name: 'portal_profile')]
    public function profile(Request $request): Response
    {
        $saved = false;
        if ($request->isMethod('POST')) {
            $actionMessages = [
                'save_profile' => 'Profile updated successfully!',
                'start_signup' => 'Sign-up flow initialized. Complete verification to activate your account access.',
                'update_password' => 'Password settings updated successfully!',
                'enable_2fa' => 'Two-factor authentication setup started.',
                'save_notifications' => 'Notification preferences saved.',
                'save_privacy' => 'Privacy settings updated.',
                'connect_google' => 'Google SSO connection updated.',
                'connect_microsoft' => 'Microsoft SSO connection updated.',
                'save_learning_preferences' => 'Learning preferences saved.',
                'save_personal_goals' => 'Personal goals updated.',
            ];
            $saved = true;
            $action = (string) $request->request->get('action', 'save_profile');
            $this->addFlash('success', $actionMessages[$action] ?? 'Account settings updated successfully!');
        }

        return $this->render('portal/profile.html.twig', [
            'student' => $this->getStudent(),
            'saved'   => $saved,
            'accountAccess' => $this->getStudentAccountAccess(),
            'notificationPreferences' => $this->getStudentNotificationPreferences(),
            'privacySettings' => $this->getStudentPrivacySettings(),
            'learningPreferences' => $this->getStudentLearningPreferences(),
            'goals' => $this->getStudentGoals(),
        ]);
    }

    // ── Courses ───────────────────────────────────────────────────────────────

    #[Route('/courses', name: 'portal_courses')]
    public function courses(): Response
    {
        return $this->render('portal/courses/index.html.twig', [
            'student' => $this->getStudent(),
            'courses' => $this->getCourses(),
        ]);
    }

    #[Route('/courses/{id}', name: 'portal_course_show', requirements: ['id' => '\d+'])]
    public function courseShow(int $id, Request $request): Response
    {
        $courses = $this->getCourses();
        $course  = null;
        foreach ($courses as $c) {
            if ($c['id'] === $id) { $course = $c; break; }
        }
        if (!$course) {
            throw $this->createNotFoundException('Course not found.');
        }

        $materials = [
            ['title' => 'Chapter 1 Slides',     'type' => 'pdf',   'size' => '2.4 MB', 'date' => '2026-03-01'],
            ['title' => 'Chapter 2 Slides',     'type' => 'pdf',   'size' => '3.1 MB', 'date' => '2026-03-15'],
            ['title' => 'Introduction Video',   'type' => 'video', 'size' => '145 MB', 'date' => '2026-03-05'],
            ['title' => 'Practice Problems',    'type' => 'pdf',   'size' => '1.2 MB', 'date' => '2026-04-01'],
            ['title' => 'External Resource',    'type' => 'link',  'size' => null,     'date' => '2026-04-10'],
            ['title' => 'Chapter 3 Notes',      'type' => 'pdf',   'size' => '1.8 MB', 'date' => '2026-04-20'],
        ];

        $assignments = array_filter($this->getAssignments(), fn($a) => $a['course'] === $course['name']);

        if ($request->isMethod('POST')) {
            $actionMessages = [
                'upload_material' => 'Study material uploaded to your personal course workspace.',
                'bookmark_resource' => 'Resource bookmark updated.',
                'refresh_completion' => 'Lesson completion progress refreshed.',
                'open_learning_path' => 'Structured learning path opened.',
                'resume_learning' => 'Resuming your latest lesson.',
                'refresh_recommendations' => 'Smart recommendations refreshed.',
            ];
            $action = (string) $request->request->get('action', 'refresh_completion');
            $this->addFlash('success', $actionMessages[$action] ?? 'Course workspace updated successfully.');

            return $this->redirectToRoute('portal_course_show', ['id' => $id]);
        }

        return $this->render('portal/courses/show.html.twig', [
            'student'     => $this->getStudent(),
            'course'      => $course,
            'materials'   => $materials,
            'assignments' => array_values($assignments),
            'lessons'     => $this->getCourseLessons($course['name']),
            'learningPath' => $this->getLearningPaths($course['name']),
            'resumeState' => $this->getResumeState($course['name']),
            'recommendations' => $this->getSmartRecommendations($course['name']),
        ]);
    }

    // ── Assignments ───────────────────────────────────────────────────────────

    #[Route('/assignments', name: 'portal_assignments')]
    public function assignments(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $actionMessages = [
                'submit_work' => 'Submission received. Receipt generated in your submission history.',
                'save_draft' => 'Draft auto-saved successfully.',
                'run_plagiarism_check' => 'Plagiarism check complete. Similarity score: 8% (safe range).',
                'edit_submission' => 'Submission reopened for edits before deadline.',
                'resubmit_submission' => 'Resubmission accepted and version history updated.',
                'download_receipt' => 'Submission receipt is ready for download.',
                'refresh_countdowns' => 'Deadline countdown timers refreshed.',
            ];
            $action = (string) $request->request->get('action', 'submit_work');
            $this->addFlash('success', $actionMessages[$action] ?? 'Assignment workspace updated successfully.');

            return $this->redirectToRoute('portal_assignments');
        }

        return $this->render('portal/assignments/index.html.twig', [
            'student'     => $this->getStudent(),
            'assignments' => $this->getAssignments(),
            'submissionHistory' => $this->getSubmissionHistory(),
            'deadlineCountdowns' => $this->getDeadlineCountdowns(),
            'draftStatus' => $this->getDraftStatus(),
        ]);
    }

    #[Route('/assignments/{id}/submit', name: 'portal_assignment_submit', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function assignmentSubmit(int $id, Request $request): Response
    {
        // In a real app this would save the submission to the DB
        $this->addFlash('success', 'Assignment #'.$id.' submitted successfully!');
        return $this->redirectToRoute('portal_assignments');
    }

    // ── Quizzes ───────────────────────────────────────────────────────────────

    #[Route('/quizzes', name: 'portal_quizzes')]
    public function quizzes(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $actionMessages = [
                'start_quiz' => 'Quiz session opened. Good luck!',
                'review_answers' => 'Detailed answer review loaded.',
                'start_mock' => 'Mock test started in practice mode.',
                'start_adaptive' => 'Adaptive quiz mode activated.',
                'start_exam_simulation' => 'Timed exam simulation started.',
                'refresh_breakdown' => 'Performance breakdown refreshed.',
                'open_question_bank' => 'Question bank opened for revision.',
            ];
            $action = (string) $request->request->get('action', 'start_quiz');
            $this->addFlash('success', $actionMessages[$action] ?? 'Quiz workspace updated successfully.');

            return $this->redirectToRoute('portal_quizzes');
        }

        $quizzes = [
            ['id'=>1, 'title'=>'Mathematics Unit 4 Quiz',    'course'=>'Mathematics',       'duration'=>30, 'questions'=>15, 'opens'=>'2026-05-05 09:00', 'closes'=>'2026-05-05 17:00', 'status'=>'upcoming', 'score'=>null],
            ['id'=>2, 'title'=>'Physics Chapter 6 Test',     'course'=>'Physics',           'duration'=>60, 'questions'=>30, 'opens'=>'2026-05-08 11:00', 'closes'=>'2026-05-08 17:00', 'status'=>'upcoming', 'score'=>null],
            ['id'=>3, 'title'=>'Periodic Elements Quiz',     'course'=>'Chemistry',         'duration'=>20, 'questions'=>20, 'opens'=>'2026-04-25 10:00', 'closes'=>'2026-04-25 23:59', 'status'=>'completed','score'=>92],
            ['id'=>4, 'title'=>'Grammar & Composition',      'course'=>'English Literature','duration'=>45, 'questions'=>25, 'opens'=>'2026-04-20 14:00', 'closes'=>'2026-04-20 23:59', 'status'=>'completed','score'=>88],
            ['id'=>5, 'title'=>'Data Structures Mid-check',  'course'=>'Computer Science',  'duration'=>40, 'questions'=>20, 'opens'=>'2026-05-12 13:00', 'closes'=>'2026-05-12 17:00', 'status'=>'upcoming', 'score'=>null],
        ];

        return $this->render('portal/quizzes/index.html.twig', [
            'student' => $this->getStudent(),
            'quizzes' => $quizzes,
            'quizReviews' => $this->getQuizReviews(),
            'mockTests' => $this->getMockTests(),
            'adaptivePlan' => $this->getAdaptiveQuizPlan(),
            'examSimulations' => $this->getExamSimulationModes(),
            'performanceByTopic' => $this->getPerformanceByTopic(),
            'questionBank' => $this->getRevisionQuestionBank(),
        ]);
    }

    // ── Grades ────────────────────────────────────────────────────────────────

    #[Route('/grades', name: 'portal_grades')]
    public function grades(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $actionMessages = [
                'refresh_grades' => 'Latest grade records loaded.',
                'refresh_trends' => 'Performance trend dashboard refreshed.',
                'refresh_analysis' => 'Strength and weakness analysis updated.',
                'refresh_improvements' => 'Personal improvement suggestions refreshed.',
            ];
            $action = (string) $request->request->get('action', 'refresh_grades');
            $this->addFlash('success', $actionMessages[$action] ?? 'Grades dashboard updated successfully.');

            return $this->redirectToRoute('portal_grades');
        }

        $gradeBook = [];
        foreach ($this->getCourses() as $course) {
            $courseAssignments = array_filter($this->getAssignments(), fn($a) => $a['course'] === $course['name'] && $a['score'] !== null);
            $gradeBook[] = [
                'course'      => $course['name'],
                'teacher'     => $course['teacher'],
                'color'       => $course['color'],
                'grade'       => $course['grade'],
                'score'       => $course['score'],
                'progress'    => $course['progress'],
                'assignments' => array_values($courseAssignments),
            ];
        }

        return $this->render('portal/grades/index.html.twig', [
            'student'    => $this->getStudent(),
            'grade_book' => $gradeBook,
            'subjectExamGrades' => $this->getSubjectExamGrades(),
            'gradeTrend' => $this->getGradeTrendSeries(),
            'performanceAnalysis' => $this->getStrengthWeaknessAnalysis(),
        ]);
    }

    // ── Attendance ────────────────────────────────────────────────────────────

    #[Route('/attendance', name: 'portal_attendance')]
    public function attendance(): Response
    {
        // Build April 2026 attendance data
        $records = [];
        $statuses = ['present','present','present','present','absent','present','present','late','present','present'];
        $statusCycle = array_merge($statuses, $statuses, $statuses, $statuses);
        for ($day = 1; $day <= 30; $day++) {
            $date = sprintf('2026-04-%02d', $day);
            $dow  = date('N', strtotime($date));
            if ($dow < 6) {
                $records[$date] = $statusCycle[$day % count($statusCycle)];
            }
        }

        $summary = [
            'present' => count(array_filter($records, fn($s) => $s === 'present')),
            'absent'  => count(array_filter($records, fn($s) => $s === 'absent')),
            'late'    => count(array_filter($records, fn($s) => $s === 'late')),
            'total'   => count($records),
        ];

        return $this->render('portal/attendance/index.html.twig', [
            'student' => $this->getStudent(),
            'records' => $records,
            'summary' => $summary,
        ]);
    }

    // ── Discussions ───────────────────────────────────────────────────────────

    #[Route('/discussions', name: 'portal_discussions')]
    public function discussions(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $actionMessages = [
                'create_thread' => 'Discussion thread posted successfully.',
                'open_threaded' => 'Threaded discussion view loaded.',
                'tag_mention' => 'Tag mention inserted into your post.',
                'ask_lesson_question' => 'Your lesson question has been posted to the Q&A board.',
            ];
            $action = (string) $request->request->get('action', 'create_thread');
            $this->addFlash('success', $actionMessages[$action] ?? 'Discussion workspace updated successfully.');

            return $this->redirectToRoute('portal_discussions');
        }

        $threads = [
            ['id'=>1, 'title'=>'Help with Integration by Parts',   'course'=>'Mathematics',       'author'=>'Bob M.',    'replies'=>8,  'views'=>42, 'last_activity'=>'2 hours ago',   'solved'=>false],
            ['id'=>2, 'title'=>'Understanding Quantum Mechanics',  'course'=>'Physics',           'author'=>'Sarah K.',  'replies'=>15, 'views'=>78, 'last_activity'=>'Yesterday',      'solved'=>true],
            ['id'=>3, 'title'=>'Essay structure tips?',            'course'=>'English Literature','author'=>'Alice J.',  'replies'=>5,  'views'=>23, 'last_activity'=>'3 hours ago',    'solved'=>false],
            ['id'=>4, 'title'=>'Recursion vs Iteration – debate',  'course'=>'Computer Science',  'author'=>'David L.',  'replies'=>22, 'views'=>130,'last_activity'=>'30 minutes ago', 'solved'=>false],
            ['id'=>5, 'title'=>'Acid-Base reaction confusion',     'course'=>'Chemistry',         'author'=>'Emma R.',   'replies'=>7,  'views'=>35, 'last_activity'=>'1 day ago',      'solved'=>true],
            ['id'=>6, 'title'=>'Best study method for History?',   'course'=>'History',           'author'=>'Chris W.',  'replies'=>12, 'views'=>60, 'last_activity'=>'2 days ago',     'solved'=>false],
        ];

        return $this->render('portal/discussions/index.html.twig', [
            'student' => $this->getStudent(),
            'threads' => $threads,
            'threadedDiscussions' => $this->getThreadedDiscussions(),
            'tagSuggestions' => $this->getTagSuggestions(),
            'lessonQuestions' => $this->getLessonQuestionsBoard(),
        ]);
    }

    // ── Chat ──────────────────────────────────────────────────────────────────

    #[Route('/chat', name: 'portal_chat')]
    public function chat(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $actionMessages = [
                'start_teacher_chat' => 'Teacher chat opened.',
                'message_classmate' => 'Classmate messaging channel opened.',
                'send_message' => 'Message sent.',
            ];
            $action = (string) $request->request->get('action', 'send_message');
            $this->addFlash('success', $actionMessages[$action] ?? 'Chat updated successfully.');

            return $this->redirectToRoute('portal_chat');
        }

        $contacts = [
            ['id'=>1, 'name'=>'Mr. Roberts',  'role'=>'Mathematics Teacher', 'avatar'=>'/dummy/student-sm-1.jpg', 'online'=>true,  'last_msg'=>'See you in class!',          'time'=>'10:32 AM', 'unread'=>0],
            ['id'=>2, 'name'=>'Ms. Chen',     'role'=>'Physics Teacher',     'avatar'=>'/dummy/student-sm-2.jpg', 'online'=>false, 'last_msg'=>'Great lab report Alice!',    'time'=>'Yesterday','unread'=>1],
            ['id'=>3, 'name'=>'Bob Martinez', 'role'=>'Classmate',           'avatar'=>'/dummy/student-md-1.jpg', 'online'=>true,  'last_msg'=>'Did you finish the hw?',     'time'=>'9:15 AM',  'unread'=>2],
            ['id'=>4, 'name'=>'Sarah Kim',    'role'=>'Classmate',           'avatar'=>'/dummy/student-md-2.jpg', 'online'=>true,  'last_msg'=>'Study group tonight 7 PM?',  'time'=>'8:50 AM',  'unread'=>0],
            ['id'=>5, 'name'=>'Mr. Singh',    'role'=>'CS Teacher',          'avatar'=>'/dummy/student-sm-1.jpg', 'online'=>false, 'last_msg'=>'Your project is excellent!', 'time'=>'Mon',      'unread'=>0],
        ];

        $activeMessages = [
            ['from'=>'me',         'text'=>'Hi Mr. Roberts! I had a question about problem 7 in the homework.', 'time'=>'10:25 AM'],
            ['from'=>'Mr. Roberts','text'=>'Of course Alice! What part is confusing you?',                       'time'=>'10:27 AM'],
            ['from'=>'me',         'text'=>'I don\'t understand how to apply the chain rule here.',              'time'=>'10:28 AM'],
            ['from'=>'Mr. Roberts','text'=>'Think of it like nesting — outer function derivative × inner. Try f(g(x)) → f\'(g(x))·g\'(x)', 'time'=>'10:30 AM'],
            ['from'=>'me',         'text'=>'Oh that makes sense! Thank you so much!',                            'time'=>'10:31 AM'],
            ['from'=>'Mr. Roberts','text'=>'See you in class!',                                                  'time'=>'10:32 AM'],
        ];

        return $this->render('portal/chat/index.html.twig', [
            'student'         => $this->getStudent(),
            'contacts'        => $contacts,
            'active_contact'  => $contacts[0],
            'messages'        => $activeMessages,
            'messagingPolicy' => $this->getMessagingPolicy(),
        ]);
    }

    // ── Live Classes ─────────────────────────────────────────────────────────

    #[Route('/live', name: 'portal_live')]
    public function live(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $actionMessages = [
                'sync_calendar' => 'Calendar sync request processed.',
                'refresh_reminders' => 'Smart reminders refreshed.',
                'detect_conflicts' => 'Schedule conflict analysis refreshed.',
                'refresh_study_plan' => 'Study planning assistant updated.',
                'join_live' => 'Joining live class now.',
                'raise_hand' => 'Your hand is raised. The teacher has been notified.',
                'class_chat' => 'Class chat panel opened for this live session.',
                'open_shared_material' => 'Shared class material opened.',
                'launch_video_tool' => 'Integrated video tool launched.',
                'save_recording' => 'Auto-recording saved to the course materials.',
                'run_live_poll' => 'Live poll/quiz started in class.',
            ];
            $action = (string) $request->request->get('action', 'sync_calendar');
            $this->addFlash('success', $actionMessages[$action] ?? 'Schedule workspace updated successfully.');

            return $this->redirectToRoute('portal_live');
        }

        $sessions = [
            ['id'=>1, 'course'=>'Mathematics',        'teacher'=>'Mr. Roberts', 'topic'=>'Integration Techniques',       'time'=>'Today 09:00–10:00',      'status'=>'live',     'participants'=>18],
            ['id'=>2, 'course'=>'English Literature', 'teacher'=>'Mrs. Davis',  'topic'=>'The Great Gatsby — Chapter 5',  'time'=>'Today 14:00–15:00',      'status'=>'upcoming', 'participants'=>0],
            ['id'=>3, 'course'=>'Physics',            'teacher'=>'Ms. Chen',    'topic'=>'Newton\'s Laws Review',         'time'=>'Tomorrow 11:00–12:00',   'status'=>'upcoming', 'participants'=>0],
            ['id'=>4, 'course'=>'Computer Science',   'teacher'=>'Mr. Singh',   'topic'=>'Sorting Algorithms Deep Dive',  'time'=>'Wed 13:00–14:00',        'status'=>'upcoming', 'participants'=>0],
            ['id'=>5, 'course'=>'Physics',            'teacher'=>'Ms. Chen',    'topic'=>'Thermodynamics Intro',          'time'=>'Apr 28 11:00–12:00',     'status'=>'recorded', 'participants'=>24],
            ['id'=>6, 'course'=>'Mathematics',        'teacher'=>'Mr. Roberts', 'topic'=>'Differential Equations',        'time'=>'Apr 25 09:00–10:00',     'status'=>'recorded', 'participants'=>22],
        ];

        return $this->render('portal/live/index.html.twig', [
            'student'  => $this->getStudent(),
            'sessions' => $sessions,
            'classTimetable' => $this->getClassTimetable(),
            'examCalendar' => $this->getExamCalendar(),
            'assignmentDeadlines' => $this->getAssignmentDeadlineCalendar(),
            'calendarSyncOptions' => $this->getCalendarSyncOptions(),
            'smartReminders' => $this->getSmartScheduleReminders(),
            'scheduleConflicts' => $this->getScheduleConflicts(),
            'studyPlan' => $this->getStudyPlanAssistant(),
            'sharedMaterials' => $this->getLiveSharedMaterials(),
            'videoTools' => $this->getVideoTools(),
            'autoRecordings' => $this->getAutoRecordings(),
            'livePolls' => $this->getLivePollsAndQuizzes(),
        ]);
    }

    // ── Group Work ──────────────────────────────────────────────────────────

    #[Route('/groups', name: 'portal_groups')]
    public function groups(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $actionMessages = [
                'join_group_project' => 'Joined group project successfully.',
                'share_group_file' => 'File shared with teammates.',
                'collaborate_group_task' => 'Task collaboration update posted.',
                'track_group_progress' => 'Group progress panel refreshed.',
                'open_shared_workspace' => 'Shared workspace opened.',
                'assign_group_task' => 'Group task assignment updated.',
                'refresh_version_history' => 'Version history refreshed.',
            ];
            $action = (string) $request->request->get('action', 'join_group_project');
            $this->addFlash('success', $actionMessages[$action] ?? 'Group workspace updated successfully.');

            return $this->redirectToRoute('portal_groups');
        }

        return $this->render('portal/groups/index.html.twig', [
            'student' => $this->getStudent(),
            'groupProjects' => $this->getGroupProjects(),
            'groupWorkspace' => $this->getGroupWorkspace(),
            'groupTasks' => $this->getGroupTaskAssignments(),
            'groupVersions' => $this->getGroupVersionHistory(),
        ]);
    }

    // ── Notifications ───────────────────────────────────────────────────────

    #[Route('/notifications', name: 'portal_notifications')]
    public function notifications(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $actionMessages = [
                'refresh_alerts' => 'Notification alerts refreshed.',
                'save_notification_preferences' => 'Notification preferences updated.',
                'apply_smart_filter' => 'Smart filtering updated.',
                'save_digest_options' => 'Digest settings saved.',
                'save_channels' => 'Alert channels updated.',
            ];
            $action = (string) $request->request->get('action', 'refresh_alerts');
            $this->addFlash('success', $actionMessages[$action] ?? 'Notification settings saved successfully.');

            return $this->redirectToRoute('portal_notifications');
        }

        return $this->render('portal/notifications/index.html.twig', [
            'student' => $this->getStudent(),
            'alerts' => $this->getNotificationAlerts(),
            'notificationPreferences' => $this->getStudentNotificationPreferences(),
            'digestSettings' => $this->getDigestSettings(),
            'channels' => $this->getNotificationChannels(),
        ]);
    }

    // ── Personalization ─────────────────────────────────────────────────────

    #[Route('/personalization', name: 'portal_personalization')]
    public function personalization(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $actionMessages = [
                'save_dashboard_customization' => 'Dashboard customization saved.',
                'save_theme' => 'Theme preference updated.',
                'save_study_preferences' => 'Study preferences updated.',
                'toggle_adaptive_ui' => 'Adaptive UI settings updated.',
                'save_accessibility_options' => 'Accessibility options saved.',
            ];
            $action = (string) $request->request->get('action', 'save_dashboard_customization');
            $this->addFlash('success', $actionMessages[$action] ?? 'Personalization settings updated successfully.');

            return $this->redirectToRoute('portal_personalization');
        }

        return $this->render('portal/personalization/index.html.twig', [
            'student' => $this->getStudent(),
            'dashboardCustomization' => $this->getDashboardCustomization(),
            'themePreferences' => $this->getThemePreferences(),
            'studyPreferences' => $this->getStudentLearningPreferences(),
            'adaptiveUi' => $this->getAdaptiveUiSettings(),
            'accessibility' => $this->getAccessibilityOptions(),
        ]);
    }

    // ── Resources ───────────────────────────────────────────────────────────

    #[Route('/resources', name: 'portal_resources')]
    public function resources(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $actionMessages = [
                'upload_resource_file' => 'File uploaded successfully.',
                'download_resource_file' => 'Download started.',
                'organize_notes' => 'Notes organization updated.',
                'open_shared_resources' => 'Shared resources refreshed.',
                'connect_cloud' => 'Cloud integration settings updated.',
                'smart_search_notes' => 'Smart note search applied.',
                'refresh_subject_folders' => 'Subject folders auto-organization refreshed.',
            ];
            $action = (string) $request->request->get('action', 'upload_resource_file');
            $this->addFlash('success', $actionMessages[$action] ?? 'Resources workspace updated successfully.');

            return $this->redirectToRoute('portal_resources');
        }

        return $this->render('portal/resources/index.html.twig', [
            'student' => $this->getStudent(),
            'resourceFiles' => $this->getResourceFiles(),
            'notesCollection' => $this->getNotesCollection(),
            'sharedResources' => $this->getSharedResourcesLibrary(),
            'cloudIntegrations' => $this->getCloudIntegrations(),
            'subjectFolders' => $this->getSubjectFolders(),
        ]);
    }

    // ── Gamification ────────────────────────────────────────────────────────

    #[Route('/gamification', name: 'portal_gamification')]
    public function gamification(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $actionMessages = [
                'refresh_badges' => 'Badges and achievements refreshed.',
                'refresh_milestones' => 'Progress milestones updated.',
                'join_challenge' => 'Challenge participation updated.',
                'refresh_leaderboard' => 'Leaderboard refreshed.',
                'refresh_rewards' => 'Rewards and levels updated.',
                'refresh_motivation' => 'Motivation boost refreshed.',
            ];
            $action = (string) $request->request->get('action', 'refresh_badges');
            $this->addFlash('success', $actionMessages[$action] ?? 'Gamification dashboard updated successfully.');

            return $this->redirectToRoute('portal_gamification');
        }

        return $this->render('portal/gamification/index.html.twig', [
            'student' => $this->getStudent(),
            'badges' => $this->getBadgesAndAchievements(),
            'milestones' => $this->getProgressMilestones(),
            'challenges' => $this->getStudentChallenges(),
            'leaderboard' => $this->getLeaderboard(),
            'rewardProfile' => $this->getRewardProfile(),
        ]);
    }

    // ── Progress ─────────────────────────────────────────────────────────────

    #[Route('/progress', name: 'portal_progress')]
    public function progress(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $actionMessages = [
                'refresh_completed_tasks' => 'Completed tasks list refreshed.',
                'refresh_study_time' => 'Study-time tracking refreshed.',
                'refresh_goals' => 'Goal monitoring panel refreshed.',
                'refresh_streaks' => 'Study streaks and motivation refreshed.',
                'refresh_productivity_stats' => 'Productivity stats updated.',
                'refresh_weekly_summary' => 'Weekly performance summary refreshed.',
            ];
            $action = (string) $request->request->get('action', 'refresh_completed_tasks');
            $this->addFlash('success', $actionMessages[$action] ?? 'Progress tools updated successfully.');

            return $this->redirectToRoute('portal_progress');
        }

        $weekly = [
            ['week' => 'Week 1', 'score' => 76],
            ['week' => 'Week 2', 'score' => 80],
            ['week' => 'Week 3', 'score' => 74],
            ['week' => 'Week 4', 'score' => 85],
            ['week' => 'Week 5', 'score' => 88],
            ['week' => 'Week 6', 'score' => 83],
            ['week' => 'Week 7', 'score' => 91],
            ['week' => 'Week 8', 'score' => 89],
        ];

        $suggestions = [
            ['subject'=>'History',  'tip'=>'You\'re 15 points below your target. Focus on primary source analysis.', 'priority'=>'high'],
            ['subject'=>'Physics',  'tip'=>'Review Newton\'s Laws flashcards — exam in 2 weeks.',                     'priority'=>'medium'],
            ['subject'=>'Math',     'tip'=>'You\'re on track! Try advanced integration practice for extra credit.',   'priority'=>'low'],
        ];

        return $this->render('portal/progress/index.html.twig', [
            'student'     => $this->getStudent(),
            'courses'     => $this->getCourses(),
            'weekly'      => $weekly,
            'suggestions' => $suggestions,
            'goals' => $this->getStudentGoals(),
            'completedTasks' => $this->getCompletedTasks(),
            'studyTime' => $this->getStudyTimeBySubject(),
            'streaks' => $this->getProductivityStreaks(),
            'weeklySummary' => $this->getWeeklyPerformanceSummary(),
        ]);
    }

    // ── AI Smart Learning ───────────────────────────────────────────────────

    #[Route('/ai-learning', name: 'portal_ai_learning')]
    public function aiLearning(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $actionMessages = [
                'ask_lesson_ai' => 'AI lesson assistant answered your question.',
                'generate_material_summary' => 'Material summary generated.',
                'refresh_study_recommendations' => 'Study recommendations refreshed.',
                'open_ai_tutor' => 'AI tutor session started.',
                'generate_auto_notes' => 'Auto-generated lesson notes prepared.',
                'build_revision_plan' => 'Personalized revision plan generated.',
            ];
            $action = (string) $request->request->get('action', 'ask_lesson_ai');
            $this->addFlash('success', $actionMessages[$action] ?? 'AI learning workspace updated successfully.');

            return $this->redirectToRoute('portal_ai_learning');
        }

        return $this->render('portal/ai_learning/index.html.twig', [
            'student' => $this->getStudent(),
            'lessonQna' => $this->getAiLessonQna(),
            'materialSummaries' => $this->getMaterialSummaries(),
            'recommendations' => $this->getAiRecommendations(),
            'aiTutorTips' => $this->getAiTutorTips(),
            'autoNotes' => $this->getAutoGeneratedNotes(),
            'revisionPlan' => $this->getPersonalizedRevisionPlan(),
        ]);
    }
}
