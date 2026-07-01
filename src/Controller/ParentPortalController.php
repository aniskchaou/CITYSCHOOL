<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

#[Route('/parent')]
#[IsGranted('ROLE_PARENT')]
class ParentPortalController extends AbstractController
{
    // ── Shared mock data ────────────────────────────────────────────────────

    private function getParent(): array
    {
        return [
            'name'      => 'Mrs. Sarah Jones',
            'username'  => 'mrsjones',
            'email'     => 'sarah.jones@email.com',
            'phone'     => '+1 (555) 234-5678',
            'avatar'    => 'SJ',
            'joined'    => 'September 2023',
            'lastLogin' => '1 hour ago',
        ];
    }

    private function getChildren(): array
    {
        return [
            [
                'id'       => 1,
                'name'     => 'Emma Jones',
                'class'    => 'Grade 10-A',
                'teacher'  => 'Mr. Smith',
                'avatar'   => 'EJ',
                'avg'      => 84,
                'attendance' => 92,
                'status'   => 'active',
            ],
            [
                'id'       => 2,
                'name'     => 'Liam Jones',
                'class'    => 'Grade 8-B',
                'teacher'  => 'Ms. Williams',
                'avatar'   => 'LJ',
                'avg'      => 71,
                'attendance' => 87,
                'status'   => 'active',
            ],
        ];
    }

    private function getGrades(): array
    {
        return [
            ['subject' => 'Mathematics',      'code' => 'MATH10', 'teacher' => 'Mr. Smith',    'score' => 88, 'grade' => 'B+', 'color' => '#7c3aed', 'trend' => 'up',   'child' => 1],
            ['subject' => 'Science',           'code' => 'SCI10',  'teacher' => 'Dr. Patel',   'score' => 92, 'grade' => 'A',  'color' => '#0891b2', 'trend' => 'up',   'child' => 1],
            ['subject' => 'English',           'code' => 'ENG10',  'teacher' => 'Ms. Brown',   'score' => 79, 'grade' => 'C+', 'color' => '#f59e0b', 'trend' => 'down', 'child' => 1],
            ['subject' => 'History',           'code' => 'HIS10',  'teacher' => 'Mr. Taylor',  'score' => 85, 'grade' => 'B',  'color' => '#22c55e', 'trend' => 'up',   'child' => 1],
            ['subject' => 'Physical Education','code' => 'PE10',   'teacher' => 'Coach Davis', 'score' => 95, 'grade' => 'A',  'color' => '#ef4444', 'trend' => 'up',   'child' => 1],
            ['subject' => 'Art',               'code' => 'ART10',  'teacher' => 'Ms. Lee',     'score' => 76, 'grade' => 'C+', 'color' => '#ec4899', 'trend' => 'up',   'child' => 1],
            ['subject' => 'Mathematics',       'code' => 'MATH8',  'teacher' => 'Ms. Williams','score' => 68, 'grade' => 'D+', 'color' => '#7c3aed', 'trend' => 'down', 'child' => 2],
            ['subject' => 'Science',           'code' => 'SCI8',   'teacher' => 'Dr. Patel',   'score' => 74, 'grade' => 'C',  'color' => '#0891b2', 'trend' => 'up',   'child' => 2],
            ['subject' => 'English',           'code' => 'ENG8',   'teacher' => 'Ms. Brown',   'score' => 82, 'grade' => 'B',  'color' => '#f59e0b', 'trend' => 'up',   'child' => 2],
        ];
    }

    private function getAttendance(): array
    {
        return [
            ['date' => 'Apr 28, 2026', 'day' => 'Tuesday',   'status' => 'present', 'child' => 1, 'subject' => 'All classes'],
            ['date' => 'Apr 27, 2026', 'day' => 'Monday',    'status' => 'present', 'child' => 1, 'subject' => 'All classes'],
            ['date' => 'Apr 25, 2026', 'day' => 'Saturday',  'status' => 'weekend', 'child' => 1, 'subject' => '—'],
            ['date' => 'Apr 24, 2026', 'day' => 'Friday',    'status' => 'absent',  'child' => 1, 'subject' => 'Mathematics'],
            ['date' => 'Apr 23, 2026', 'day' => 'Thursday',  'status' => 'late',    'child' => 1, 'subject' => 'Science (10 min late)'],
            ['date' => 'Apr 22, 2026', 'day' => 'Wednesday', 'status' => 'present', 'child' => 1, 'subject' => 'All classes'],
            ['date' => 'Apr 28, 2026', 'day' => 'Tuesday',   'status' => 'present', 'child' => 2, 'subject' => 'All classes'],
            ['date' => 'Apr 27, 2026', 'day' => 'Monday',    'status' => 'absent',  'child' => 2, 'subject' => 'English'],
            ['date' => 'Apr 24, 2026', 'day' => 'Friday',    'status' => 'present', 'child' => 2, 'subject' => 'All classes'],
            ['date' => 'Apr 23, 2026', 'day' => 'Thursday',  'status' => 'present', 'child' => 2, 'subject' => 'All classes'],
        ];
    }

    private function getNotifications(): array
    {
        return [
            ['id' => 1, 'type' => 'alert',   'icon' => 'exclamation-triangle', 'title' => 'Missing Assignment',      'body' => 'Emma has a missing Math assignment due Apr 25.',          'time' => '2 hours ago',   'read' => false, 'child' => 'Emma'],
            ['id' => 2, 'type' => 'grade',   'icon' => 'star',                 'title' => 'New Grade Posted',        'body' => 'Emma received A in Science — Term Test 2.',               'time' => '5 hours ago',   'read' => false, 'child' => 'Emma'],
            ['id' => 3, 'type' => 'absent',  'icon' => 'calendar-times-o',     'title' => 'Absence Recorded',        'body' => 'Liam was marked absent on April 27.',                     'time' => 'Yesterday',     'read' => false, 'child' => 'Liam'],
            ['id' => 4, 'type' => 'payment', 'icon' => 'credit-card',          'title' => 'Fee Due Reminder',        'body' => 'Spring 2026 tuition fee ($1,500) is due May 10.',         'time' => '2 days ago',    'read' => true,  'child' => 'Both'],
            ['id' => 5, 'type' => 'report',  'icon' => 'file-text',            'title' => 'Monthly Report Ready',    'body' => 'April progress report is available for Emma Jones.',       'time' => 'Apr 30, 2026',  'read' => true,  'child' => 'Emma'],
            ['id' => 6, 'type' => 'grade',   'icon' => 'star',                 'title' => 'Grade Improved',          'body' => 'Liam improved his English score from 65 → 82.',           'time' => 'Apr 28, 2026',  'read' => true,  'child' => 'Liam'],
            ['id' => 7, 'type' => 'behavior','icon' => 'info-circle',          'title' => 'Behavior Note',           'body' => 'Emma received a participation commendation in History.',  'time' => 'Apr 27, 2026',  'read' => true,  'child' => 'Emma'],
        ];
    }

    private function getMessages(): array
    {
        return [
            ['id' => 1, 'teacher' => 'Mr. Smith',    'subject' => 'Emma\'s Math Progress',       'preview' => 'Emma is doing well but needs more practice on quadratic equations…', 'time' => '10:30 AM', 'read' => false, 'avatar' => 'MS'],
            ['id' => 2, 'teacher' => 'Ms. Brown',    'subject' => 'English Assignment Feedback',  'preview' => 'Great essay submitted! A few grammatical improvements suggested…',    'time' => 'Yesterday', 'read' => true,  'avatar' => 'MB'],
            ['id' => 3, 'teacher' => 'Dr. Patel',    'subject' => 'Science Lab Reminder',         'preview' => 'Please ensure Emma brings safety goggles for Friday\'s lab…',         'time' => 'Apr 27',   'read' => true,  'avatar' => 'DP'],
            ['id' => 4, 'teacher' => 'Ms. Williams', 'subject' => 'Liam\'s Math Concern',         'preview' => 'I wanted to discuss Liam\'s recent test performance with you…',       'time' => 'Apr 25',   'read' => false, 'avatar' => 'MW'],
        ];
    }

    private function getAnnouncements(): array
    {
        return [
            ['id' => 1, 'title' => 'End of Term Examinations',      'body' => 'Final exams scheduled for May 20–30, 2026. Timetables posted on the portal.',             'target' => 'all',      'pinned' => true,  'sent_by' => 'Admin', 'created_at' => 'Apr 28, 2026', 'views' => 145],
            ['id' => 2, 'title' => 'School Sports Day — May 15',    'body' => 'Annual sports day event. Students should wear sports uniforms. Parents welcome to attend.', 'target' => 'parents', 'pinned' => false, 'sent_by' => 'Admin', 'created_at' => 'Apr 25, 2026', 'views' => 98],
            ['id' => 3, 'title' => 'Parent-Teacher Meeting',        'body' => 'PTM scheduled for May 8, 2026 from 2–6 PM. Please register your slot online.',             'target' => 'parents', 'pinned' => true,  'sent_by' => 'Admin', 'created_at' => 'Apr 22, 2026', 'views' => 201],
            ['id' => 4, 'title' => 'Library Books Return Deadline', 'body' => 'All borrowed books must be returned by May 5, 2026 to avoid late fees.',                   'target' => 'all',      'pinned' => false, 'sent_by' => 'Admin', 'created_at' => 'Apr 20, 2026', 'views' => 72],
        ];
    }

    private function getPayments(): array
    {
        return [
            ['id' => 1, 'description' => 'Spring 2026 Tuition — Emma',  'amount' => 1500, 'status' => 'pending',  'due' => 'May 10, 2026',  'child' => 'Emma Jones',  'method' => '—'],
            ['id' => 2, 'description' => 'Spring 2026 Tuition — Liam',  'amount' => 1500, 'status' => 'pending',  'due' => 'May 10, 2026',  'child' => 'Liam Jones',  'method' => '—'],
            ['id' => 3, 'description' => 'Fall 2025 Tuition — Emma',    'amount' => 1500, 'status' => 'paid',     'due' => 'Sep 10, 2025',  'child' => 'Emma Jones',  'method' => 'Credit Card'],
            ['id' => 4, 'description' => 'Fall 2025 Tuition — Liam',    'amount' => 1500, 'status' => 'paid',     'due' => 'Sep 10, 2025',  'child' => 'Liam Jones',  'method' => 'Bank Transfer'],
            ['id' => 5, 'description' => 'Lab Fee — Emma',               'amount' => 150,  'status' => 'paid',     'due' => 'Sep 5, 2025',   'child' => 'Emma Jones',  'method' => 'Online'],
            ['id' => 6, 'description' => 'Library Fee — Liam',           'amount' => 50,   'status' => 'overdue',  'due' => 'Apr 15, 2026',  'child' => 'Liam Jones',  'method' => '—'],
        ];
    }

    private function getParentNotificationPreferences(): array
    {
        return [
            'email' => true,
            'sms' => true,
            'push' => false,
            'missingAssignments' => true,
            'lowAttendance' => true,
            'newGrades' => true,
            'announcements' => true,
            'billing' => true,
            'quietHours' => '10:00 PM - 6:00 AM',
            'frequency' => 'Instant for critical alerts, daily digest for others',
        ];
    }

    private function getParentRoleSharing(): array
    {
        return [
            ['name' => 'Mr. David Jones', 'email' => 'david.jones@email.com', 'access' => 'Full guardian access', 'status' => 'active', 'lastLogin' => 'Today, 8:10 AM'],
            ['name' => 'Mrs. Sarah Jones', 'email' => 'sarah.jones@email.com', 'access' => 'Primary account owner', 'status' => 'active', 'lastLogin' => 'Today, 7:24 AM'],
        ];
    }

    private function getChildDashboardSnapshots(): array
    {
        return [
            ['childId' => 1, 'focus' => 'Math practice recommended', 'homeworkDueToday' => 2, 'riskLevel' => 'medium', 'attendanceTrend' => 'stable'],
            ['childId' => 2, 'focus' => 'Attendance needs improvement', 'homeworkDueToday' => 1, 'riskLevel' => 'high', 'attendanceTrend' => 'down'],
        ];
    }

    private function getUpcomingClasses(): array
    {
        return [
            ['time' => '08:00 AM', 'subject' => 'Mathematics', 'teacher' => 'Mr. Smith', 'room' => 'Room 204', 'child' => 'Emma Jones'],
            ['time' => '09:40 AM', 'subject' => 'English', 'teacher' => 'Ms. Brown', 'room' => 'Room 106', 'child' => 'Liam Jones'],
            ['time' => '11:00 AM', 'subject' => 'Science Lab', 'teacher' => 'Dr. Patel', 'room' => 'Lab 2', 'child' => 'Emma Jones'],
            ['time' => '01:30 PM', 'subject' => 'History', 'teacher' => 'Mr. Taylor', 'room' => 'Room 310', 'child' => 'Liam Jones'],
        ];
    }

    private function getHomeworkDue(): array
    {
        return [
            ['title' => 'Quadratic Equations Worksheet', 'subject' => 'Mathematics', 'due' => 'Today, 6:00 PM', 'child' => 'Emma Jones', 'status' => 'pending', 'priority' => 'high'],
            ['title' => 'Read Chapter 5 and summary notes', 'subject' => 'English', 'due' => 'Tomorrow, 8:00 AM', 'child' => 'Liam Jones', 'status' => 'pending', 'priority' => 'medium'],
            ['title' => 'Science lab report submission', 'subject' => 'Science', 'due' => 'Tomorrow, 2:00 PM', 'child' => 'Emma Jones', 'status' => 'draft', 'priority' => 'medium'],
        ];
    }

    private function getParentAlerts(): array
    {
        return [
            ['title' => 'Missing assignment detected', 'detail' => 'Emma has not submitted Math worksheet #12.', 'type' => 'assignment', 'severity' => 'high', 'child' => 'Emma Jones'],
            ['title' => 'Attendance dropped below target', 'detail' => 'Liam attendance is now 87% (target 90%).', 'type' => 'attendance', 'severity' => 'high', 'child' => 'Liam Jones'],
            ['title' => 'Fee reminder', 'detail' => 'Spring tuition payment due in 8 days.', 'type' => 'billing', 'severity' => 'medium', 'child' => 'Both'],
        ];
    }

    private function getTodayInsights(): array
    {
        return [
            'summary' => 'Emma is performing strongly overall but has one urgent math submission today. Liam is showing grade recovery in English, but attendance needs immediate attention this week.',
            'highlights' => [
                'Emma is +4% above her monthly grade baseline.',
                'Liam has improved 17 points in English since last month.',
                'One high-impact action today: submit pending assignments before 6 PM.',
            ],
        ];
    }

    private function getPriorityAlerts(): array
    {
        return [
            ['label' => 'Action needed by 6:00 PM', 'message' => 'Submit Emma\'s Math worksheet to avoid grade penalty.', 'impact' => 'High', 'child' => 'Emma Jones'],
            ['label' => 'Attendance intervention', 'message' => 'Schedule check-in with Liam\'s class advisor this week.', 'impact' => 'High', 'child' => 'Liam Jones'],
        ];
    }

    private function getExamTermGradebook(): array
    {
        return [
            ['child' => 1, 'subject' => 'Mathematics', 'exam' => 'Term Test 1', 'term' => 'Term 1', 'score' => 83, 'classAvg' => 76, 'teacherComment' => 'Strong concepts, continue timed practice.'],
            ['child' => 1, 'subject' => 'Mathematics', 'exam' => 'Term Test 2', 'term' => 'Term 2', 'score' => 88, 'classAvg' => 79, 'teacherComment' => 'Great improvement in algebra and problem solving.'],
            ['child' => 1, 'subject' => 'Science', 'exam' => 'Lab Assessment', 'term' => 'Term 2', 'score' => 92, 'classAvg' => 81, 'teacherComment' => 'Excellent practical and report quality.'],
            ['child' => 1, 'subject' => 'English', 'exam' => 'Essay Evaluation', 'term' => 'Term 2', 'score' => 79, 'classAvg' => 77, 'teacherComment' => 'Ideas are clear, grammar polish needed.'],
            ['child' => 2, 'subject' => 'Mathematics', 'exam' => 'Term Test 2', 'term' => 'Term 2', 'score' => 68, 'classAvg' => 72, 'teacherComment' => 'Needs support with fractions and equations.'],
            ['child' => 2, 'subject' => 'Science', 'exam' => 'Quiz 4', 'term' => 'Term 2', 'score' => 74, 'classAvg' => 75, 'teacherComment' => 'Steady progress, revise diagrams weekly.'],
            ['child' => 2, 'subject' => 'English', 'exam' => 'Reading Test', 'term' => 'Term 2', 'score' => 82, 'classAvg' => 78, 'teacherComment' => 'Excellent comprehension improvement.' ],
        ];
    }

    private function getGradeProgressSeries(): array
    {
        return [
            ['child' => 1, 'period' => 'Jan', 'avg' => 80],
            ['child' => 1, 'period' => 'Feb', 'avg' => 82],
            ['child' => 1, 'period' => 'Mar', 'avg' => 84],
            ['child' => 1, 'period' => 'Apr', 'avg' => 86],
            ['child' => 2, 'period' => 'Jan', 'avg' => 66],
            ['child' => 2, 'period' => 'Feb', 'avg' => 69],
            ['child' => 2, 'period' => 'Mar', 'avg' => 71],
            ['child' => 2, 'period' => 'Apr', 'avg' => 73],
        ];
    }

    private function getPerformanceInsights(): array
    {
        return [
            1 => [
                'strengths' => ['Science practical work', 'Physical Education consistency', 'Math growth trend'],
                'weaknesses' => ['English grammar accuracy', 'Assignment timeliness in math'],
                'suggestions' => ['Schedule 20 minutes daily reading revision.', 'Use a homework checklist before 6 PM each day.'],
            ],
            2 => [
                'strengths' => ['English reading improvement', 'Stable class participation'],
                'weaknesses' => ['Mathematics test confidence', 'Attendance consistency'],
                'suggestions' => ['Weekly math tutoring slot with teacher support.', 'Set attendance reminder and morning checklist.'],
            ],
        ];
    }

    private function getAttendanceMonthlyTrend(): array
    {
        return [
            ['child' => 1, 'month' => 'Jan', 'rate' => 94],
            ['child' => 1, 'month' => 'Feb', 'rate' => 92],
            ['child' => 1, 'month' => 'Mar', 'rate' => 91],
            ['child' => 1, 'month' => 'Apr', 'rate' => 92],
            ['child' => 2, 'month' => 'Jan', 'rate' => 90],
            ['child' => 2, 'month' => 'Feb', 'rate' => 89],
            ['child' => 2, 'month' => 'Mar', 'rate' => 88],
            ['child' => 2, 'month' => 'Apr', 'rate' => 87],
        ];
    }

    private function getAbsenceHistory(): array
    {
        return [
            ['child' => 1, 'date' => 'Apr 24, 2026', 'reason' => 'Flu symptoms', 'status' => 'excused', 'submittedBy' => 'Parent'],
            ['child' => 2, 'date' => 'Apr 27, 2026', 'reason' => 'Medical appointment', 'status' => 'pending-review', 'submittedBy' => 'Parent'],
            ['child' => 2, 'date' => 'Mar 18, 2026', 'reason' => 'Family emergency', 'status' => 'excused', 'submittedBy' => 'Parent'],
        ];
    }

    private function getRealtimeAbsenceAlerts(): array
    {
        return [
            ['child' => 1, 'time' => '08:20 AM', 'message' => 'Emma marked absent in first period Mathematics.', 'priority' => 'high'],
            ['child' => 2, 'time' => '09:05 AM', 'message' => 'Liam checked in late to English class.', 'priority' => 'medium'],
        ];
    }

    private function getAttendanceWarningAlerts(): array
    {
        return [
            ['child' => 'Liam Jones', 'risk' => 'high', 'message' => 'Attendance is 87%, below the 90% target threshold.', 'nextAction' => 'Meet class advisor this week.'],
            ['child' => 'Emma Jones', 'risk' => 'low', 'message' => 'Attendance stable at 92%, maintain current routine.', 'nextAction' => 'No intervention required.'],
        ];
    }

    // ── Routes ──────────────────────────────────────────────────────────────

    #[Route('/dashboard', name: 'parent_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('parent/dashboard.html.twig', [
            'parent'        => $this->getParent(),
            'children'      => $this->getChildren(),
            'notifications' => $this->getNotifications(),
            'announcements' => $this->getAnnouncements(),
            'payments'      => $this->getPayments(),
        ]);
    }

    #[Route('/account-children', name: 'parent_account_children', methods: ['GET', 'POST'])]
    public function accountChildren(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $actionMessages = [
                'save_profile' => 'Parent profile updated successfully!',
                'link_child' => 'Child account linked to your guardian profile.',
                'switch_child' => 'Child dashboard context switched.',
                'save_contacts' => 'Contact details saved.',
                'save_notifications' => 'Notification preferences updated.',
                'enable_2fa' => 'Two-factor authentication has been enabled.',
                'view_unified_dashboard' => 'Unified multi-child dashboard is ready.',
                'manage_role_sharing' => 'Role sharing access updated for co-guardian.',
            ];

            $action = (string) $request->request->get('action');
            $this->addFlash('success', $actionMessages[$action] ?? 'Parent account settings updated.');

            return $this->redirectToRoute('parent_account_children');
        }

        return $this->render('parent/account_children/index.html.twig', [
            'parent' => $this->getParent(),
            'children' => $this->getChildren(),
            'notificationPreferences' => $this->getParentNotificationPreferences(),
            'roleSharing' => $this->getParentRoleSharing(),
            'childSnapshots' => $this->getChildDashboardSnapshots(),
        ]);
    }

    #[Route('/daily-overview', name: 'parent_daily_overview', methods: ['GET', 'POST'])]
    public function dailyOverview(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $actionMessages = [
                'open_classes' => 'Upcoming classes opened.',
                'review_homework' => 'Homework checklist loaded.',
                'view_announcements' => 'Latest announcements opened.',
                'resolve_alerts' => 'Priority alerts queue opened.',
                'open_quick_access' => 'Quick access section updated.',
                'refresh_insights' => 'Today\'s insights refreshed.',
                'focus_priority_alerts' => 'Priority-only alert mode enabled.',
            ];

            $action = (string) $request->request->get('action');
            $this->addFlash('success', $actionMessages[$action] ?? 'Daily overview updated.');

            return $this->redirectToRoute('parent_daily_overview');
        }

        return $this->render('parent/daily_overview/index.html.twig', [
            'parent' => $this->getParent(),
            'children' => $this->getChildren(),
            'upcomingClasses' => $this->getUpcomingClasses(),
            'homeworkDue' => $this->getHomeworkDue(),
            'announcements' => $this->getAnnouncements(),
            'alerts' => $this->getParentAlerts(),
            'todayInsights' => $this->getTodayInsights(),
            'priorityAlerts' => $this->getPriorityAlerts(),
        ]);
    }

    #[Route('/grades', name: 'parent_grades', methods: ['GET', 'POST'])]
    public function grades(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $actionMessages = [
                'view_gradebook' => 'Gradebook view refreshed by subject, exam and term.',
                'load_feedback' => 'Teacher feedback comments loaded.',
                'track_progress' => 'Progress timeline updated.',
                'compare_subjects' => 'Subject performance comparison updated.',
                'refresh_visual_analytics' => 'Visual analytics refreshed.',
                'generate_performance_insights' => 'Performance insights generated.',
            ];

            $action = (string) $request->request->get('action');
            $this->addFlash('success', $actionMessages[$action] ?? 'Academic performance tracking updated.');

            return $this->redirectToRoute('parent_grades', ['child' => $request->query->get('child', 1)]);
        }

        return $this->render('parent/grades/index.html.twig', [
            'parent'   => $this->getParent(),
            'children' => $this->getChildren(),
            'grades'   => $this->getGrades(),
            'examGradebook' => $this->getExamTermGradebook(),
            'gradeProgress' => $this->getGradeProgressSeries(),
            'performanceInsights' => $this->getPerformanceInsights(),
        ]);
    }

    #[Route('/attendance', name: 'parent_attendance', methods: ['GET', 'POST'])]
    public function attendance(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $actionMessages = [
                'view_daily_attendance' => 'Daily attendance view refreshed.',
                'view_absence_history' => 'Absence history loaded.',
                'submit_absence_explanation' => 'Absence explanation submitted to school.',
                'enable_absence_notifications' => 'Absence notifications enabled.',
                'enable_realtime_absence_alerts' => 'Real-time absence alerts enabled.',
                'view_attendance_trends' => 'Attendance trend view refreshed.',
                'view_warning_alerts' => 'Low-attendance risk warnings updated.',
            ];

            $action = (string) $request->request->get('action');
            $this->addFlash('success', $actionMessages[$action] ?? 'Attendance monitoring updated.');

            return $this->redirectToRoute('parent_attendance', ['child' => $request->query->get('child', 1)]);
        }

        return $this->render('parent/attendance/index.html.twig', [
            'parent'     => $this->getParent(),
            'children'   => $this->getChildren(),
            'attendance' => $this->getAttendance(),
            'absenceHistory' => $this->getAbsenceHistory(),
            'attendanceTrend' => $this->getAttendanceMonthlyTrend(),
            'realtimeAlerts' => $this->getRealtimeAbsenceAlerts(),
            'warningAlerts' => $this->getAttendanceWarningAlerts(),
        ]);
    }

    #[Route('/notifications', name: 'parent_notifications')]
    public function notifications(): Response
    {
        return $this->render('parent/notifications/index.html.twig', [
            'parent'        => $this->getParent(),
            'notifications' => $this->getNotifications(),
        ]);
    }

    #[Route('/messages', name: 'parent_messages', methods: ['GET', 'POST'])]
    public function messages(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $this->addFlash('success', 'Message sent to the teacher!');
            return $this->redirectToRoute('parent_messages');
        }
        return $this->render('parent/messages/index.html.twig', [
            'parent'   => $this->getParent(),
            'messages' => $this->getMessages(),
        ]);
    }

    #[Route('/announcements', name: 'parent_announcements')]
    public function announcements(): Response
    {
        return $this->render('parent/announcements/index.html.twig', [
            'parent'        => $this->getParent(),
            'announcements' => $this->getAnnouncements(),
        ]);
    }

    #[Route('/payments', name: 'parent_payments', methods: ['GET', 'POST'])]
    public function payments(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $this->addFlash('success', 'Payment processed successfully!');
            return $this->redirectToRoute('parent_payments');
        }
        return $this->render('parent/payments/index.html.twig', [
            'parent'   => $this->getParent(),
            'payments' => $this->getPayments(),
        ]);
    }

    #[Route('/reports', name: 'parent_reports')]
    public function reports(): Response
    {
        return $this->render('parent/reports/index.html.twig', [
            'parent'   => $this->getParent(),
            'children' => $this->getChildren(),
            'grades'   => $this->getGrades(),
            'attendance' => $this->getAttendance(),
        ]);
    }

    #[Route('/profile', name: 'parent_profile', methods: ['GET', 'POST'])]
    public function profile(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $this->addFlash('success', 'Profile updated successfully!');
            return $this->redirectToRoute('parent_profile');
        }
        return $this->render('parent/profile.html.twig', [
            'parent'   => $this->getParent(),
            'children' => $this->getChildren(),
        ]);
    }
}
