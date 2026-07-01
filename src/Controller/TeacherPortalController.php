<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/teacher')]
#[IsGranted('ROLE_TEACHER')]
class TeacherPortalController extends AbstractController
{
    // ── Mock data ─────────────────────────────────────────────────────────────

    private function getTeacher(): array
    {
        return [
            'name'       => 'Mr. James Smith',
            'username'   => 'teacher',
            'email'      => 'j.smith@cityschool.edu',
            'department' => 'Mathematics & Sciences',
            'phone'      => '+1 (555) 987-6543',
            'avatar'     => '/dummy/student-lg-2.jpg',
            'experience' => '8 years',
            'bio'        => 'Dedicated educator focused on building strong math and science foundations.',
            'joined'     => 'September 2018',
            'subjects'   => ['Mathematics', 'Physics', 'Chemistry', 'Advanced Math'],
        ];
    }

    private function getCourses(): array
    {
        return [
            ['id'=>1,'name'=>'Mathematics','code'=>'MATH-10','grade'=>'Grade 10','color'=>'#4361ee','students'=>28,'schedule'=>'Mon/Wed 09:00','room'=>'Room 201','avg'=>82,'materials'=>12,'assignments'=>8],
            ['id'=>2,'name'=>'Physics','code'=>'PHY-10','grade'=>'Grade 10','color'=>'#7c3aed','students'=>25,'schedule'=>'Tue/Thu 11:00','room'=>'Lab B','avg'=>76,'materials'=>9,'assignments'=>6],
            ['id'=>3,'name'=>'Chemistry','code'=>'CHEM-11','grade'=>'Grade 11','color'=>'#059669','students'=>22,'schedule'=>'Mon/Fri 14:00','room'=>'Lab A','avg'=>79,'materials'=>11,'assignments'=>7],
            ['id'=>4,'name'=>'Advanced Math','code'=>'MATH-11A','grade'=>'Grade 11','color'=>'#d97706','students'=>18,'schedule'=>'Wed/Fri 10:00','room'=>'Room 203','avg'=>88,'materials'=>15,'assignments'=>10],
        ];
    }

    private function getStudents(): array
    {
        return [
            ['id'=>1,'name'=>'Alice Johnson','grade'=>'10th','gpa'=>3.8,'attendance'=>96,'avg_score'=>88,'status'=>'excellent'],
            ['id'=>2,'name'=>'Bob Williams','grade'=>'10th','gpa'=>3.2,'attendance'=>89,'avg_score'=>74,'status'=>'good'],
            ['id'=>3,'name'=>'Carol Davis','grade'=>'10th','gpa'=>3.5,'attendance'=>93,'avg_score'=>81,'status'=>'good'],
            ['id'=>4,'name'=>'David Miller','grade'=>'10th','gpa'=>2.9,'attendance'=>78,'avg_score'=>65,'status'=>'at_risk'],
            ['id'=>5,'name'=>'Emma Wilson','grade'=>'10th','gpa'=>3.9,'attendance'=>98,'avg_score'=>95,'status'=>'excellent'],
            ['id'=>6,'name'=>'Frank Brown','grade'=>'11th','gpa'=>3.1,'attendance'=>85,'avg_score'=>71,'status'=>'good'],
            ['id'=>7,'name'=>'Grace Lee','grade'=>'11th','gpa'=>2.7,'attendance'=>72,'avg_score'=>62,'status'=>'at_risk'],
            ['id'=>8,'name'=>'Henry Clark','grade'=>'11th','gpa'=>3.6,'attendance'=>94,'avg_score'=>84,'status'=>'good'],
        ];
    }

    private function getAssignments(): array
    {
        return [
            ['id'=>1,'title'=>'Quadratic Equations Problem Set','course'=>'Mathematics','course_id'=>1,'deadline'=>'2026-05-10','submitted'=>22,'total'=>28,'graded'=>15,'status'=>'open','points'=>100,'description'=>'Solve problems 1–20 from Chapter 4.'],
            ['id'=>2,'title'=>'Newton\'s Laws Lab Report','course'=>'Physics','course_id'=>2,'deadline'=>'2026-05-08','submitted'=>25,'total'=>25,'graded'=>25,'status'=>'graded','points'=>50,'description'=>'Document your lab observations and findings.'],
            ['id'=>3,'title'=>'Chemical Bonding Worksheet','course'=>'Chemistry','course_id'=>3,'deadline'=>'2026-05-15','submitted'=>10,'total'=>22,'graded'=>0,'status'=>'open','points'=>30,'description'=>'Complete the ionic/covalent bonding exercises.'],
            ['id'=>4,'title'=>'Calculus Derivatives Quiz','course'=>'Advanced Math','course_id'=>4,'deadline'=>'2026-04-30','submitted'=>18,'total'=>18,'graded'=>18,'status'=>'graded','points'=>75,'description'=>'Differentiation and chain rule problems.'],
            ['id'=>5,'title'=>'Wave Mechanics Essay','course'=>'Physics','course_id'=>2,'deadline'=>'2026-05-20','submitted'=>5,'total'=>25,'graded'=>0,'status'=>'open','points'=>40,'description'=>'500-word essay on wave properties and behaviour.'],
        ];
    }

    private function getSubmissions(): array
    {
        return [
            ['id'=>1,'student'=>'Alice Johnson','assignment'=>'Quadratic Equations Problem Set','assignment_id'=>1,'course'=>'Mathematics','submitted_at'=>'2026-05-02 14:30','score'=>null,'max_score'=>100,'feedback'=>'','file'=>'alice_quadratic.pdf','status'=>'pending','auto_score'=>91],
            ['id'=>2,'student'=>'Bob Williams','assignment'=>'Quadratic Equations Problem Set','assignment_id'=>1,'course'=>'Mathematics','submitted_at'=>'2026-05-03 09:15','score'=>null,'max_score'=>100,'feedback'=>'','file'=>'bob_quadratic.pdf','status'=>'pending','auto_score'=>72],
            ['id'=>3,'student'=>'Carol Davis','assignment'=>'Newton\'s Laws Lab Report','assignment_id'=>2,'course'=>'Physics','submitted_at'=>'2026-04-28 16:00','score'=>46,'max_score'=>50,'feedback'=>'Excellent analysis and clear observations!','file'=>'carol_newton.pdf','status'=>'graded','auto_score'=>null],
            ['id'=>4,'student'=>'Emma Wilson','assignment'=>'Quadratic Equations Problem Set','assignment_id'=>1,'course'=>'Mathematics','submitted_at'=>'2026-05-01 10:00','score'=>null,'max_score'=>100,'feedback'=>'','file'=>'emma_quadratic.pdf','status'=>'pending','auto_score'=>95],
            ['id'=>5,'student'=>'David Miller','assignment'=>'Calculus Derivatives Quiz','assignment_id'=>4,'course'=>'Advanced Math','submitted_at'=>'2026-04-29 11:45','score'=>61,'max_score'=>75,'feedback'=>'Review chain rule applications — see page 48.','file'=>'david_calc.pdf','status'=>'graded','auto_score'=>null],
            ['id'=>6,'student'=>'Frank Brown','assignment'=>'Quadratic Equations Problem Set','assignment_id'=>1,'course'=>'Mathematics','submitted_at'=>'2026-05-02 22:00','score'=>null,'max_score'=>100,'feedback'=>'','file'=>'frank_quadratic.pdf','status'=>'pending','auto_score'=>78],
        ];
    }

    private function getQuizzes(): array
    {
        return [
            ['id'=>1,'title'=>'Algebra Fundamentals','course'=>'Mathematics','questions'=>20,'duration'=>30,'opens'=>'2026-05-05 09:00','closes'=>'2026-05-05 23:59','status'=>'active','avg'=>78,'attempts'=>24,'pass_rate'=>83],
            ['id'=>2,'title'=>'Forces & Motion','course'=>'Physics','questions'=>15,'duration'=>25,'opens'=>'2026-05-10 11:00','closes'=>'2026-05-10 23:59','status'=>'upcoming','avg'=>0,'attempts'=>0,'pass_rate'=>0],
            ['id'=>3,'title'=>'Periodic Table Elements','course'=>'Chemistry','questions'=>25,'duration'=>40,'opens'=>'2026-04-28','closes'=>'2026-04-28','status'=>'closed','avg'=>72,'attempts'=>22,'pass_rate'=>68],
            ['id'=>4,'title'=>'Integration Techniques','course'=>'Advanced Math','questions'=>10,'duration'=>20,'opens'=>'2026-05-12','closes'=>'2026-05-12','status'=>'upcoming','avg'=>0,'attempts'=>0,'pass_rate'=>0],
        ];
    }

    private function getAnnouncements(): array
    {
        return [
            ['id'=>1,'title'=>'Midterm Exam Schedule Released','body'=>'Mathematics midterm is May 15 at 09:00 AM in Room 204. Bring your ID and pencils.','course'=>'All Courses','date'=>'2026-04-30','views'=>85,'pinned'=>true],
            ['id'=>2,'title'=>'Lab Safety Reminder','body'=>'All students must wear safety goggles during the chemistry lab next Tuesday. No exceptions.','course'=>'Chemistry','date'=>'2026-04-28','views'=>22,'pinned'=>false],
            ['id'=>3,'title'=>'Extra Credit Opportunity','body'=>'Students who complete the bonus problem set by May 12 will receive up to 5% extra credit.','course'=>'Mathematics','date'=>'2026-04-25','views'=>45,'pinned'=>false],
        ];
    }

    private function getHomeworkAlerts(): array
    {
        return [
            ['assignment_id' => 1, 'title' => 'Quadratic Equations Problem Set', 'type' => 'missing', 'count' => 6, 'message' => '6 students have not submitted yet.', 'severity' => 'high'],
            ['assignment_id' => 3, 'title' => 'Chemical Bonding Worksheet', 'type' => 'late', 'count' => 12, 'message' => '12 submissions are still pending with 4 days left.', 'severity' => 'medium'],
            ['assignment_id' => 5, 'title' => 'Wave Mechanics Essay', 'type' => 'overdue', 'count' => 20, 'message' => 'Overdue risk is rising due to low submission pace.', 'severity' => 'high'],
        ];
    }

    private function getWeeklyHomeworkSummary(): array
    {
        return [
            ['week' => 'This Week', 'assigned' => 5, 'submitted' => 80, 'late' => 7, 'graded' => 43],
            ['week' => 'Last Week', 'assigned' => 4, 'submitted' => 91, 'late' => 4, 'graded' => 58],
        ];
    }

    private function getOverdueTasks(): array
    {
        return [
            ['task' => 'Chase 6 missing Math submissions', 'course' => 'Mathematics', 'deadline' => 'Today, 4:00 PM', 'priority' => 'high'],
            ['task' => 'Return feedback for 3 graded Physics reports', 'course' => 'Physics', 'deadline' => 'Tomorrow, 10:00 AM', 'priority' => 'medium'],
            ['task' => 'Remind students about Wave Mechanics essay', 'course' => 'Physics', 'deadline' => 'Today, 1:00 PM', 'priority' => 'high'],
        ];
    }

    private function getTeacherHomeworkFeedback(): array
    {
        return [
            ['assignment_id' => 1, 'student' => 'Alice Johnson', 'comment' => 'Strong work overall. Re-check question 17 for sign accuracy.', 'teacher' => 'Mr. Smith'],
            ['assignment_id' => 2, 'student' => 'Carol Davis', 'comment' => 'Excellent analysis and clear observations!', 'teacher' => 'Mr. Smith'],
            ['assignment_id' => 4, 'student' => 'David Miller', 'comment' => 'Review chain rule applications before the next quiz.', 'teacher' => 'Mr. Smith'],
        ];
    }

    private function getCommunicationAnnouncements(): array
    {
        return [
            ['source' => 'School Admin', 'title' => 'Parent-teacher week opens Monday', 'time' => 'Today, 8:00 AM', 'priority' => 'normal'],
            ['source' => 'Principal Office', 'title' => 'Urgent: submit attendance audit by 5 PM', 'time' => 'Yesterday, 4:15 PM', 'priority' => 'urgent'],
            ['source' => 'Academic Office', 'title' => 'Translation support available for family meetings', 'time' => 'Yesterday, 10:00 AM', 'priority' => 'normal'],
        ];
    }

    private function getParentTeacherMeetings(): array
    {
        return [
            ['parent' => 'Mrs. Johnson', 'student' => 'Alice Johnson', 'date' => '2026-05-06', 'time' => '3:30 PM', 'status' => 'scheduled', 'topic' => 'Homework consistency'],
            ['parent' => 'Mr. Brown', 'student' => 'Frank Brown', 'date' => '2026-05-07', 'time' => '1:00 PM', 'status' => 'pending', 'topic' => 'Late assignment recovery'],
            ['parent' => 'Mrs. Davis', 'student' => 'Carol Davis', 'date' => '2026-05-08', 'time' => '9:00 AM', 'status' => 'scheduled', 'topic' => 'Science lab performance'],
        ];
    }

    private function getPriorityThreads(): array
    {
        return [
            ['contact' => 'Mrs. Johnson (Parent)', 'flag' => 'urgent', 'reason' => 'Requesting immediate update before counseling meeting'],
            ['contact' => 'Mr. Wilson (Admin)', 'flag' => 'urgent', 'reason' => 'Needs grade export confirmation today'],
        ];
    }

    private function getTeacherNotifications(): array
    {
        return [
            ['id' => 1, 'type' => 'grade', 'title' => 'Grades posted to Mathematics', 'message' => 'Finalized scores for Quadratic Equations Problem Set are now visible to students.', 'time' => '20 minutes ago', 'priority' => 'high', 'channel' => 'app'],
            ['id' => 2, 'type' => 'attendance', 'title' => 'Attendance issue detected', 'message' => '3 students in Grade 10 Physics have dropped below 80% attendance.', 'time' => '1 hour ago', 'priority' => 'high', 'channel' => 'email'],
            ['id' => 3, 'type' => 'assignment', 'title' => 'New assignment reminder', 'message' => 'Wave Mechanics Essay opens tomorrow for Physics students.', 'time' => 'Today, 8:10 AM', 'priority' => 'normal', 'channel' => 'app'],
            ['id' => 4, 'type' => 'announcement', 'title' => 'School-wide announcement sent', 'message' => 'Parent-teacher week schedule has been published by administration.', 'time' => 'Yesterday', 'priority' => 'normal', 'channel' => 'sms'],
        ];
    }

    private function getTeacherNotificationSettings(): array
    {
        return [
            'gradesPosted' => true,
            'attendanceIssues' => true,
            'newAssignments' => true,
            'announcements' => true,
            'smartNotifications' => true,
            'digestMode' => 'Daily',
            'channels' => ['app' => true, 'email' => true, 'sms' => false],
        ];
    }

    private function getTeacherNotificationDigests(): array
    {
        return [
            ['label' => 'Daily digest', 'summary' => '4 assignment updates, 2 attendance risks, 1 school announcement.', 'delivery' => 'Every day at 5:30 PM'],
            ['label' => 'Weekly digest', 'summary' => 'Course activity recap, overdue work, and grading backlog in one report.', 'delivery' => 'Every Friday at 4:00 PM'],
        ];
    }

    private function getFeeStructure(): array
    {
        return [
            ['item' => 'Professional Development Fee', 'amount' => 120, 'frequency' => 'Annual', 'status' => 'active'],
            ['item' => 'Teaching Platform Access', 'amount' => 35, 'frequency' => 'Monthly', 'status' => 'active'],
            ['item' => 'Certification Renewal', 'amount' => 80, 'frequency' => 'Annual', 'status' => 'upcoming'],
        ];
    }

    private function getPaymentHistory(): array
    {
        return [
            ['id' => 1, 'description' => 'Teaching Platform Access - April', 'amount' => 35, 'date' => '2026-04-03', 'method' => 'Card', 'status' => 'paid', 'receipt' => 'RCP-TEA-1001'],
            ['id' => 2, 'description' => 'Professional Development Fee', 'amount' => 120, 'date' => '2026-02-15', 'method' => 'Bank Transfer', 'status' => 'paid', 'receipt' => 'RCP-TEA-0982'],
            ['id' => 3, 'description' => 'Teaching Platform Access - May', 'amount' => 35, 'date' => '2026-05-03', 'method' => 'Pending', 'status' => 'pending', 'receipt' => 'Pending'],
        ];
    }

    private function getInstallmentPlans(): array
    {
        return [
            ['name' => 'Certification Renewal Split Plan', 'installments' => 2, 'nextDue' => '2026-05-15', 'amount' => 40, 'status' => 'available'],
            ['name' => 'Annual Professional Bundle', 'installments' => 4, 'nextDue' => '2026-06-01', 'amount' => 30, 'status' => 'active'],
        ];
    }

    private function getAcademicCalendar(): array
    {
        return [
            ['date' => '2026-05-08', 'title' => 'Parent-Teacher Conference Day', 'type' => 'meeting'],
            ['date' => '2026-05-15', 'title' => 'Midterm Examinations Begin', 'type' => 'exam'],
            ['date' => '2026-05-22', 'title' => 'Science Fair Registration Deadline', 'type' => 'event'],
            ['date' => '2026-06-01', 'title' => 'Term 2 Progress Report Release', 'type' => 'report'],
        ];
    }

    private function getSchoolDocuments(): array
    {
        return [
            ['title' => 'Faculty Handbook 2026', 'category' => 'Policy', 'format' => 'PDF', 'updated' => 'Apr 28, 2026'],
            ['title' => 'Excursion Permission Form', 'category' => 'Form', 'format' => 'DOCX', 'updated' => 'Apr 30, 2026'],
            ['title' => 'Assessment Moderation Policy', 'category' => 'Policy', 'format' => 'PDF', 'updated' => 'Apr 12, 2026'],
            ['title' => 'Virtual Meeting Guidelines', 'category' => 'Guide', 'format' => 'PDF', 'updated' => 'May 1, 2026'],
        ];
    }

    private function getSchoolEvents(): array
    {
        return [
            ['name' => 'Science Fair Showcase', 'date' => '2026-05-24', 'location' => 'Main Hall', 'details' => 'Student project showcase with family access.', 'rsvp' => 'open'],
            ['name' => 'Sports Day', 'date' => '2026-05-18', 'location' => 'School Field', 'details' => 'Whole-school athletics events and class participation.', 'rsvp' => 'open'],
            ['name' => 'Math Olympiad Briefing', 'date' => '2026-05-11', 'location' => 'Room 201', 'details' => 'Briefing for selected students and supervising teachers.', 'rsvp' => 'invite-only'],
        ];
    }

    private function getSchoolNewsFeed(): array
    {
        return [
            ['headline' => 'STEM lab receives new equipment grant', 'time' => 'Today, 9:00 AM', 'tag' => 'Campus News'],
            ['headline' => 'Student debate team advances to regional finals', 'time' => 'Yesterday, 2:30 PM', 'tag' => 'Achievements'],
            ['headline' => 'Transport schedule updated for Friday events', 'time' => 'Yesterday, 8:15 AM', 'tag' => 'Operations'],
        ];
    }

    private function getPermissionSlips(): array
    {
        return [
            ['student' => 'Alice Johnson', 'trip' => 'Science Museum Visit', 'deadline' => '2026-05-09', 'status' => 'pending'],
            ['student' => 'Henry Clark', 'trip' => 'University Math Workshop', 'deadline' => '2026-05-13', 'status' => 'approved'],
            ['student' => 'Carol Davis', 'trip' => 'Chemistry Lab Field Activity', 'deadline' => '2026-05-10', 'status' => 'pending'],
        ];
    }

    private function getMeetingReminders(): array
    {
        return [
            ['title' => 'Mrs. Johnson meeting in 45 minutes', 'time' => 'Today, 2:45 PM', 'channel' => 'App'],
            ['title' => 'Grade review call tomorrow morning', 'time' => 'Tomorrow, 8:00 AM', 'channel' => 'Email'],
        ];
    }

    private function getSuggestedMeetingSlots(): array
    {
        return [
            ['slot' => 'Tue 3:30 PM', 'reason' => 'No class conflict and parent is available'],
            ['slot' => 'Wed 11:15 AM', 'reason' => 'Best overlap with advisor availability'],
            ['slot' => 'Fri 1:00 PM', 'reason' => 'Shortest turnaround before report release'],
        ];
    }

    private function getMeetingSummaries(): array
    {
        return [
            ['parent' => 'Mrs. Johnson', 'student' => 'Alice Johnson', 'date' => '2026-04-19', 'notes' => 'Agreed on a homework checklist and weekly math review.', 'followUp' => 'Review progress in 2 weeks'],
            ['parent' => 'Mr. Brown', 'student' => 'Frank Brown', 'date' => '2026-04-14', 'notes' => 'Discussed late submissions and deadline planning support.', 'followUp' => 'Share assignment tracker by email'],
        ];
    }

    private function getBehaviorReports(): array
    {
        return [
            ['student' => 'Alice Johnson', 'category' => 'Participation', 'status' => 'positive', 'detail' => 'Consistently supports peers during group problem solving.', 'date' => '2026-05-01'],
            ['student' => 'David Miller', 'category' => 'Disruption', 'status' => 'incident', 'detail' => 'Repeated interruptions during Advanced Math review session.', 'date' => '2026-04-29'],
            ['student' => 'Grace Lee', 'category' => 'Engagement', 'status' => 'warning', 'detail' => 'Showing low participation and incomplete classwork for 2 weeks.', 'date' => '2026-04-28'],
        ];
    }

    private function getIncidentAlerts(): array
    {
        return [
            ['student' => 'David Miller', 'title' => 'Behavior incident follow-up needed', 'severity' => 'high', 'message' => 'Coordinator requested acknowledgment before end of day.', 'time' => '25 minutes ago'],
            ['student' => 'Grace Lee', 'title' => 'Risk escalation alert', 'severity' => 'medium', 'message' => 'Behavior and attendance trends now indicate academic risk.', 'time' => 'Today, 8:40 AM'],
        ];
    }

    private function getEarlyWarnings(): array
    {
        return [
            ['student' => 'Grace Lee', 'risk' => 'high', 'type' => 'academic + behavior', 'summary' => 'Low participation, 72% attendance, and declining quiz performance.', 'action' => 'Arrange parent meeting and assign weekly progress check.'],
            ['student' => 'David Miller', 'risk' => 'medium', 'type' => 'behavior', 'summary' => 'Classroom disruptions are affecting assignment completion.', 'action' => 'Contact parent and review behavior expectations.'],
        ];
    }

    private function getSuggestedInterventions(): array
    {
        return [
            ['student' => 'Grace Lee', 'suggestion' => 'Contact parent and review missing work tracker.', 'priority' => 'high'],
            ['student' => 'David Miller', 'suggestion' => 'Schedule a one-on-one reflection after class.', 'priority' => 'medium'],
            ['student' => 'Alice Johnson', 'suggestion' => 'Recognize positive participation in next progress note.', 'priority' => 'low'],
        ];
    }

    private function getReportCards(): array
    {
        return [
            ['student' => 'Alice Johnson', 'term' => 'Term 2', 'gpa' => '3.8', 'status' => 'ready', 'file' => 'alice-johnson-term2.pdf'],
            ['student' => 'Bob Williams', 'term' => 'Term 2', 'gpa' => '3.2', 'status' => 'ready', 'file' => 'bob-williams-term2.pdf'],
            ['student' => 'Grace Lee', 'term' => 'Term 2', 'gpa' => '2.7', 'status' => 'review', 'file' => 'grace-lee-term2.pdf'],
        ];
    }

    private function getTermSummaries(): array
    {
        return [
            ['student' => 'Alice Johnson', 'term' => 'Term 2', 'summary' => 'Strong assessment consistency with excellent participation and timely homework.', 'attendance' => 96],
            ['student' => 'David Miller', 'term' => 'Term 2', 'summary' => 'Performance remains uneven due to missed work and classroom behavior incidents.', 'attendance' => 78],
            ['student' => 'Grace Lee', 'term' => 'Term 2', 'summary' => 'Needs structured support across attendance, class focus, and assignment completion.', 'attendance' => 72],
        ];
    }

    private function getLongTermProgress(): array
    {
        return [
            ['student' => 'Alice Johnson', 'period' => '2025 Term 3', 'score' => 84],
            ['student' => 'Alice Johnson', 'period' => '2026 Term 1', 'score' => 87],
            ['student' => 'Alice Johnson', 'period' => '2026 Term 2', 'score' => 90],
            ['student' => 'Grace Lee', 'period' => '2025 Term 3', 'score' => 71],
            ['student' => 'Grace Lee', 'period' => '2026 Term 1', 'score' => 67],
            ['student' => 'Grace Lee', 'period' => '2026 Term 2', 'score' => 62],
        ];
    }

    private function getAiReportSummaries(): array
    {
        return [
            ['student' => 'Alice Johnson', 'summary' => 'Alice improved in Mathematics and maintained strong attendance, but should be stretched with more advanced problem sets.'],
            ['student' => 'Grace Lee', 'summary' => 'Grace needs help in Science and class engagement, with attendance support likely to improve overall performance.'],
        ];
    }

    private function getMonthlyPerformanceReports(): array
    {
        return [
            ['month' => 'March 2026', 'focus' => 'Homework completion and quiz recovery', 'studentsFlagged' => 3],
            ['month' => 'April 2026', 'focus' => 'Attendance consistency and behavior stabilization', 'studentsFlagged' => 2],
        ];
    }

    private function getTeacherPreferences(): array
    {
        return [
            'layout' => 'focus-first',
            'language' => 'English',
            'notificationTypes' => [
                'grades' => true,
                'attendance' => true,
                'assignments' => true,
                'announcements' => true,
                'meetings' => false,
            ],
            'personalizationMode' => 'busy-teacher',
            'accessibility' => [
                'highContrast' => false,
                'largeText' => true,
                'reducedMotion' => false,
                'keyboardShortcuts' => true,
            ],
        ];
    }

    private function getTeacherTodaySchedule(): array
    {
        return [
            ['time' => '09:00 AM', 'title' => 'Mathematics - Grade 10', 'type' => 'class', 'location' => 'Room 201'],
            ['time' => '11:00 AM', 'title' => 'Physics Lab Review', 'type' => 'class', 'location' => 'Lab B'],
            ['time' => '01:30 PM', 'title' => 'Grade assignment backlog', 'type' => 'deadline', 'location' => 'Teacher Portal'],
            ['time' => '03:30 PM', 'title' => 'Parent meeting with Mrs. Johnson', 'type' => 'meeting', 'location' => 'Virtual Room A'],
        ];
    }

    private function getTeacherUpcomingAssessments(): array
    {
        return [
            ['title' => 'Quadratic Equations Problem Set', 'course' => 'Mathematics', 'due' => '2026-05-10', 'kind' => 'assignment'],
            ['title' => 'Forces & Motion Quiz', 'course' => 'Physics', 'due' => '2026-05-10', 'kind' => 'test'],
            ['title' => 'Chemical Bonding Worksheet', 'course' => 'Chemistry', 'due' => '2026-05-15', 'kind' => 'assignment'],
        ];
    }

    private function getTeacherTodayFocus(): array
    {
        return [
            'headline' => 'Prioritize grading the Mathematics backlog, prepare the Physics quiz release, and follow up with at-risk students before the parent meeting window.',
            'priorities' => [
                'Finish 4 pending math submissions before 1:30 PM.',
                'Review Physics quiz settings before 11:00 AM.',
                'Message Grace Lee and David Miller support updates.',
            ],
        ];
    }

    private function getTeacherProgressSnapshot(): array
    {
        return [
            'gradesReady' => 15,
            'attendanceSubmitted' => 3,
            'tasksOpen' => 6,
            'courseHealth' => 'Strong overall with one at-risk cohort',
        ];
    }

    private function getTeacherUrgentAlerts(): array
    {
        return [
            ['label' => 'Missing work', 'message' => '6 students still missing the Quadratic Equations submission.', 'severity' => 'high'],
            ['label' => 'Exam soon', 'message' => 'Mathematics midterm starts in 13 days. Revision pack still unpublished.', 'severity' => 'medium'],
            ['label' => 'Attendance risk', 'message' => 'Two students in Physics dropped below 80% attendance.', 'severity' => 'high'],
        ];
    }

    private function getCourseLessons(int $courseId): array
    {
        $lessonsByCourse = [
            1 => [
                ['title' => 'Quadratic Functions Fundamentals', 'type' => 'document', 'completion' => 100, 'bookmarked' => true],
                ['title' => 'Vertex Form Walkthrough', 'type' => 'video', 'completion' => 85, 'bookmarked' => false],
                ['title' => 'Practice Set: Graph Interpretation', 'type' => 'link', 'completion' => 60, 'bookmarked' => true],
            ],
            2 => [
                ['title' => 'Newton\'s Three Laws Overview', 'type' => 'document', 'completion' => 100, 'bookmarked' => true],
                ['title' => 'Friction Lab Demo', 'type' => 'video', 'completion' => 70, 'bookmarked' => false],
                ['title' => 'Force Diagram Simulation', 'type' => 'link', 'completion' => 40, 'bookmarked' => true],
            ],
            3 => [
                ['title' => 'Bonding Types and Structures', 'type' => 'document', 'completion' => 100, 'bookmarked' => false],
                ['title' => 'Covalent Bond Animation', 'type' => 'video', 'completion' => 55, 'bookmarked' => true],
            ],
            4 => [
                ['title' => 'Derivative Rules Reference', 'type' => 'document', 'completion' => 100, 'bookmarked' => true],
                ['title' => 'Chain Rule Tutorial', 'type' => 'video', 'completion' => 80, 'bookmarked' => true],
            ],
        ];

        return $lessonsByCourse[$courseId] ?? [];
    }

    private function getLearningPaths(int $courseId): array
    {
        $paths = [
            1 => [
                ['step' => 'Review quadratic concepts', 'status' => 'done'],
                ['step' => 'Watch vertex form lesson', 'status' => 'current'],
                ['step' => 'Complete graph interpretation activity', 'status' => 'upcoming'],
            ],
            2 => [
                ['step' => 'Read Newton\'s Laws summary', 'status' => 'done'],
                ['step' => 'Run force simulation', 'status' => 'current'],
                ['step' => 'Submit lab reflection', 'status' => 'upcoming'],
            ],
        ];

        return $paths[$courseId] ?? [];
    }

    private function getResumeState(int $courseId): array
    {
        $resume = [
            1 => ['item' => 'Vertex Form Walkthrough', 'detail' => 'Resume at 08:34 in the example section.'],
            2 => ['item' => 'Force Diagram Simulation', 'detail' => 'Resume from checkpoint 2 of 4.'],
            3 => ['item' => 'Covalent Bond Animation', 'detail' => 'Resume before practice questions.'],
            4 => ['item' => 'Chain Rule Tutorial', 'detail' => 'Resume at worked example 3.'],
        ];

        return $resume[$courseId] ?? ['item' => 'Course overview', 'detail' => 'Start from the latest published lesson.'];
    }

    private function getSmartRecommendations(int $courseId): array
    {
        $recommendations = [
            1 => [
                'Weak area detected in graph interpretation. Revisit the practice set and assign the extension worksheet to low-performing students.',
                'Students who missed the last assignment should be routed to the worked example video first.',
            ],
            2 => [
                'Force diagrams remain the weakest topic. Surface the simulation link at the top of the lesson list.',
                'Create a quick exit-ticket after the next lab to reinforce Newton\'s Second Law.',
            ],
        ];

        return $recommendations[$courseId] ?? ['Review completion data and publish one short recap resource.'];
    }

    // ── Routes ────────────────────────────────────────────────────────────────

    #[Route('/dashboard', name: 'teacher_dashboard')]
    public function dashboard(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $actionMessages = [
                'refresh_schedule' => 'Today\'s schedule refreshed.',
                'open_upcoming' => 'Upcoming assignments and tests refreshed.',
                'refresh_announcements' => 'Recent announcements refreshed.',
                'open_quick_links' => 'Quick course links updated.',
                'refresh_focus' => 'Today\'s focus refreshed.',
                'refresh_snapshot' => 'Progress snapshot refreshed.',
                'refresh_urgent_alerts' => 'Urgent alerts refreshed.',
            ];

            $action = (string) $request->request->get('action');
            $this->addFlash('success', $actionMessages[$action] ?? 'Dashboard updated.');

            return $this->redirectToRoute('teacher_dashboard');
        }

        $courses     = $this->getCourses();
        $submissions = $this->getSubmissions();
        $pending     = array_values(array_filter($submissions, fn($s) => $s['status'] === 'pending'));
        $atRisk      = array_values(array_filter($this->getStudents(), fn($s) => $s['status'] === 'at_risk'));

        return $this->render('teacher/dashboard.html.twig', [
            'teacher'             => $this->getTeacher(),
            'courses'             => $courses,
            'total_students'      => array_sum(array_column($courses, 'students')),
            'pending_grades'      => count($pending),
            'active_quizzes'      => count(array_filter($this->getQuizzes(), fn($q) => $q['status'] === 'active')),
            'assignments'         => $this->getAssignments(),
            'announcements'       => $this->getAnnouncements(),
            'pending_submissions' => $pending,
            'at_risk'             => $atRisk,
            'todaySchedule'       => $this->getTeacherTodaySchedule(),
            'upcomingAssessments' => $this->getTeacherUpcomingAssessments(),
            'todayFocus'          => $this->getTeacherTodayFocus(),
            'progressSnapshot'    => $this->getTeacherProgressSnapshot(),
            'urgentAlerts'        => $this->getTeacherUrgentAlerts(),
        ]);
    }

    #[Route('/courses', name: 'teacher_courses')]
    public function courses(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $this->addFlash('success', 'Course "' . htmlspecialchars($request->request->get('name', 'New Course')) . '" created successfully!');
            return $this->redirectToRoute('teacher_courses');
        }
        return $this->render('teacher/courses/index.html.twig', [
            'teacher' => $this->getTeacher(),
            'courses' => $this->getCourses(),
        ]);
    }

    #[Route('/courses/{id}', name: 'teacher_course_show', requirements: ['id' => '\d+'])]
    public function courseShow(int $id, Request $request): Response
    {
        $course = null;
        foreach ($this->getCourses() as $c) {
            if ($c['id'] === $id) { $course = $c; break; }
        }
        if (!$course) { throw $this->createNotFoundException('Course not found'); }

        if ($request->isMethod('POST')) {
            $actionMessages = [
                'upload_material' => 'Material uploaded successfully!',
                'bookmark_resource' => 'Resource bookmarked successfully.',
                'refresh_completion' => 'Lesson completion tracking refreshed.',
                'open_learning_path' => 'Structured learning path loaded.',
                'resume_learning' => 'Resume state loaded.',
                'refresh_recommendations' => 'Smart recommendations refreshed.',
            ];
            $action = (string) $request->request->get('action', 'upload_material');
            $this->addFlash('success', $actionMessages[$action] ?? 'Course materials updated.');
            return $this->redirectToRoute('teacher_course_show', ['id' => $id]);
        }

        return $this->render('teacher/courses/show.html.twig', [
            'teacher'     => $this->getTeacher(),
            'course'      => $course,
            'materials'   => [
                ['id'=>1,'title'=>'Chapter 1: Introduction','type'=>'pdf','size'=>'2.4 MB','date'=>'2026-04-01','downloads'=>18],
                ['id'=>2,'title'=>'Lecture Slides Week 3','type'=>'ppt','size'=>'5.1 MB','date'=>'2026-04-15','downloads'=>24],
                ['id'=>3,'title'=>'Tutorial Video – Part 1','type'=>'video','size'=>'120 MB','date'=>'2026-04-20','downloads'=>31],
                ['id'=>4,'title'=>'Khan Academy Reference','type'=>'link','size'=>'—','date'=>'2026-04-22','downloads'=>14],
            ],
            'enrolled'    => $this->getStudents(),
            'assignments' => array_values(array_filter($this->getAssignments(), fn($a) => $a['course_id'] === $id)),
            'lessons'     => $this->getCourseLessons($id),
            'learningPath'=> $this->getLearningPaths($id),
            'resumeState' => $this->getResumeState($id),
            'recommendations' => $this->getSmartRecommendations($id),
        ]);
    }

    #[Route('/assignments', name: 'teacher_assignments')]
    public function assignments(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $actionMessages = [
                'create_assignment' => 'Assignment "' . htmlspecialchars($request->request->get('title', 'New Assignment')) . '" created!',
                'view_submission_status' => 'Submission status view refreshed.',
                'view_deadlines' => 'Homework deadlines overview updated.',
                'view_feedback' => 'Teacher feedback panel loaded.',
                'view_missing_alerts' => 'Missing and late assignment alerts refreshed.',
                'view_weekly_summary' => 'Weekly homework summary updated.',
                'view_overdue_tasks' => 'Overdue task highlights refreshed.',
            ];
            $action = (string) $request->request->get('action', 'create_assignment');
            $this->addFlash('success', $actionMessages[$action] ?? 'Assignment overview updated.');
            return $this->redirectToRoute('teacher_assignments');
        }
        return $this->render('teacher/assignments/index.html.twig', [
            'teacher'     => $this->getTeacher(),
            'assignments' => $this->getAssignments(),
            'courses'     => $this->getCourses(),
            'submissions' => $this->getSubmissions(),
            'homeworkAlerts' => $this->getHomeworkAlerts(),
            'weeklyHomeworkSummary' => $this->getWeeklyHomeworkSummary(),
            'overdueTasks' => $this->getOverdueTasks(),
            'homeworkFeedback' => $this->getTeacherHomeworkFeedback(),
        ]);
    }

    #[Route('/grading', name: 'teacher_grading')]
    public function grading(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $student = htmlspecialchars($request->request->get('student', 'Student'));
            $this->addFlash('success', "Grade saved for $student!");
            return $this->redirectToRoute('teacher_grading');
        }
        return $this->render('teacher/grading/index.html.twig', [
            'teacher'     => $this->getTeacher(),
            'submissions' => $this->getSubmissions(),
            'assignments' => $this->getAssignments(),
        ]);
    }

    #[Route('/quizzes', name: 'teacher_quizzes')]
    public function quizzes(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $this->addFlash('success', 'Quiz "' . htmlspecialchars($request->request->get('title', 'New Quiz')) . '" created!');
            return $this->redirectToRoute('teacher_quizzes');
        }
        return $this->render('teacher/quizzes/index.html.twig', [
            'teacher' => $this->getTeacher(),
            'quizzes' => $this->getQuizzes(),
            'courses' => $this->getCourses(),
        ]);
    }

    #[Route('/analytics', name: 'teacher_analytics')]
    public function analytics(): Response
    {
        return $this->render('teacher/analytics/index.html.twig', [
            'teacher'  => $this->getTeacher(),
            'courses'  => $this->getCourses(),
            'students' => $this->getStudents(),
            'weekly'   => [
                ['week'=>'Wk 1','avg'=>74],['week'=>'Wk 2','avg'=>78],
                ['week'=>'Wk 3','avg'=>72],['week'=>'Wk 4','avg'=>80],
                ['week'=>'Wk 5','avg'=>83],['week'=>'Wk 6','avg'=>79],
                ['week'=>'Wk 7','avg'=>85],['week'=>'Wk 8','avg'=>87],
            ],
        ]);
    }

    #[Route('/attendance', name: 'teacher_attendance')]
    public function attendance(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $date = htmlspecialchars($request->request->get('date', date('Y-m-d')));
            $this->addFlash('success', "Attendance saved for $date!");
            return $this->redirectToRoute('teacher_attendance');
        }
        return $this->render('teacher/attendance/index.html.twig', [
            'teacher' => $this->getTeacher(),
            'courses' => $this->getCourses(),
            'roster'  => $this->getStudents(),
        ]);
    }

    #[Route('/announcements', name: 'teacher_announcements')]
    public function announcements(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $course = htmlspecialchars($request->request->get('course', 'all students'));
            $this->addFlash('success', "Announcement sent to $course!");
            return $this->redirectToRoute('teacher_announcements');
        }
        return $this->render('teacher/announcements/index.html.twig', [
            'teacher'       => $this->getTeacher(),
            'announcements' => $this->getAnnouncements(),
            'courses'       => $this->getCourses(),
        ]);
    }

    #[Route('/messages', name: 'teacher_messages', methods: ['GET', 'POST'])]
    public function messages(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $actionMessages = [
                'send_message' => 'Message sent successfully.',
                'schedule_meeting' => 'Parent-teacher meeting scheduled.',
                'translate_message' => 'Message translation preview generated.',
                'flag_priority' => 'Priority flag applied to the conversation.',
            ];
            $action = (string) $request->request->get('action', 'send_message');
            $this->addFlash('success', $actionMessages[$action] ?? 'Communication settings updated.');
            return $this->redirectToRoute('teacher_messages');
        }

        return $this->render('teacher/messages/index.html.twig', [
            'teacher'  => $this->getTeacher(),
            'contacts' => [
                ['id'=>1,'name'=>'Alice Johnson','role'=>'Student','unread'=>2,'last_msg'=>'Thank you for the clarification!','time'=>'10:32 AM','online'=>true],
                ['id'=>2,'name'=>'Mrs. Johnson (Parent)','role'=>'Parent','unread'=>0,'last_msg'=>'Will Alice need extra tutoring?','time'=>'Yesterday','online'=>false],
                ['id'=>3,'name'=>'Bob Williams','role'=>'Student','unread'=>1,'last_msg'=>'Can I have a deadline extension?','time'=>'Yesterday','online'=>true],
                ['id'=>4,'name'=>'Carol Davis','role'=>'Student','unread'=>0,'last_msg'=>'Got it, thanks!','time'=>'Monday','online'=>false],
                ['id'=>5,'name'=>'Mr. Wilson (Admin)','role'=>'Admin','unread'=>0,'last_msg'=>'Please submit midterm grades by Friday.','time'=>'Monday','online'=>true],
            ],
            'thread'   => [
                ['me'=>false,'name'=>'Alice Johnson','text'=>'Hi Mr. Smith, I have a question about problem 4 on the homework.','time'=>'10:20 AM'],
                ['me'=>true,'name'=>'You','text'=>'Of course! Problem 4 requires the quadratic formula. Check the discriminant first.','time'=>'10:25 AM'],
                ['me'=>false,'name'=>'Alice Johnson','text'=>'If the discriminant is negative, there are no real solutions?','time'=>'10:28 AM'],
                ['me'=>true,'name'=>'You','text'=>'Exactly! You\'ve got it. Let me know if you have any more questions.','time'=>'10:30 AM'],
                ['me'=>false,'name'=>'Alice Johnson','text'=>'Thank you for the clarification!','time'=>'10:32 AM'],
            ],
            'schoolAnnouncements' => $this->getCommunicationAnnouncements(),
            'meetings' => $this->getParentTeacherMeetings(),
            'priorityThreads' => $this->getPriorityThreads(),
        ]);
    }

    #[Route('/notifications', name: 'teacher_notifications', methods: ['GET', 'POST'])]
    public function notifications(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $actionMessages = [
                'refresh_notifications' => 'Notification stream refreshed.',
                'save_notification_settings' => 'Notification settings saved.',
                'enable_smart_notifications' => 'Smart notifications enabled.',
                'set_digest_mode' => 'Digest mode preferences updated.',
                'save_multi_channel_alerts' => 'Multi-channel alert delivery updated.',
            ];

            $action = (string) $request->request->get('action');
            $this->addFlash('success', $actionMessages[$action] ?? 'Notification preferences updated.');

            return $this->redirectToRoute('teacher_notifications');
        }

        return $this->render('teacher/notifications/index.html.twig', [
            'teacher' => $this->getTeacher(),
            'notifications' => $this->getTeacherNotifications(),
            'settings' => $this->getTeacherNotificationSettings(),
            'digests' => $this->getTeacherNotificationDigests(),
        ]);
    }

    #[Route('/resources', name: 'teacher_resources', methods: ['GET', 'POST'])]
    public function resources(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $actionMessages = [
                'download_document' => 'Document download started.',
                'view_event_details' => 'Event details loaded.',
                'rsvp_event' => 'Event RSVP submitted.',
                'approve_permission_slip' => 'Digital permission slip approved.',
                'refresh_news_feed' => 'School news feed refreshed.',
            ];

            $action = (string) $request->request->get('action');
            $this->addFlash('success', $actionMessages[$action] ?? 'School resources updated.');

            return $this->redirectToRoute('teacher_resources');
        }

        return $this->render('teacher/resources/index.html.twig', [
            'teacher' => $this->getTeacher(),
            'announcements' => $this->getAnnouncements(),
            'calendar' => $this->getAcademicCalendar(),
            'documents' => $this->getSchoolDocuments(),
            'events' => $this->getSchoolEvents(),
            'newsFeed' => $this->getSchoolNewsFeed(),
            'permissionSlips' => $this->getPermissionSlips(),
        ]);
    }

    #[Route('/behavior', name: 'teacher_behavior', methods: ['GET', 'POST'])]
    public function behavior(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $actionMessages = [
                'view_behavior_reports' => 'Behavior reports refreshed.',
                'view_incident_alerts' => 'Incident alerts updated.',
                'acknowledge_notification' => 'Notification acknowledged.',
                'view_early_warnings' => 'Early warning analysis refreshed.',
                'view_suggested_actions' => 'Suggested actions loaded.',
            ];

            $action = (string) $request->request->get('action');
            $this->addFlash('success', $actionMessages[$action] ?? 'Behavior monitoring updated.');

            return $this->redirectToRoute('teacher_behavior');
        }

        return $this->render('teacher/behavior/index.html.twig', [
            'teacher' => $this->getTeacher(),
            'reports' => $this->getBehaviorReports(),
            'incidents' => $this->getIncidentAlerts(),
            'warnings' => $this->getEarlyWarnings(),
            'suggestions' => $this->getSuggestedInterventions(),
        ]);
    }

    #[Route('/reports', name: 'teacher_reports', methods: ['GET', 'POST'])]
    public function reports(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $actionMessages = [
                'download_report_card' => 'Report card download started.',
                'view_term_summaries' => 'Term summaries refreshed.',
                'track_long_term_progress' => 'Long-term progress view updated.',
                'generate_ai_summary' => 'AI-generated summaries refreshed.',
                'open_monthly_reports' => 'Monthly performance reports loaded.',
            ];

            $action = (string) $request->request->get('action');
            $this->addFlash('success', $actionMessages[$action] ?? 'Reports and insights updated.');

            return $this->redirectToRoute('teacher_reports');
        }

        return $this->render('teacher/reports/index.html.twig', [
            'teacher' => $this->getTeacher(),
            'reportCards' => $this->getReportCards(),
            'termSummaries' => $this->getTermSummaries(),
            'progress' => $this->getLongTermProgress(),
            'aiSummaries' => $this->getAiReportSummaries(),
            'monthlyReports' => $this->getMonthlyPerformanceReports(),
        ]);
    }

    #[Route('/meetings', name: 'teacher_meetings', methods: ['GET', 'POST'])]
    public function meetings(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $actionMessages = [
                'book_meeting' => 'Parent-teacher meeting booked.',
                'view_schedule' => 'Meeting schedule refreshed.',
                'enable_reminders' => 'Meeting reminders enabled.',
                'join_virtual_meeting' => 'Virtual meeting room opened.',
                'suggest_time_slots' => 'Suggested best time slots generated.',
                'connect_video_meeting' => 'Video meeting integration configured.',
                'save_meeting_notes' => 'Meeting summary and notes saved.',
            ];

            $action = (string) $request->request->get('action');
            $this->addFlash('success', $actionMessages[$action] ?? 'Meeting settings updated.');

            return $this->redirectToRoute('teacher_meetings');
        }

        return $this->render('teacher/meetings/index.html.twig', [
            'teacher' => $this->getTeacher(),
            'meetings' => $this->getParentTeacherMeetings(),
            'reminders' => $this->getMeetingReminders(),
            'suggestedSlots' => $this->getSuggestedMeetingSlots(),
            'summaries' => $this->getMeetingSummaries(),
        ]);
    }

    #[Route('/payments', name: 'teacher_payments', methods: ['GET', 'POST'])]
    public function payments(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $actionMessages = [
                'pay_fees_online' => 'Online payment initiated successfully.',
                'download_receipt' => 'Receipt download started.',
                'setup_autopay' => 'Auto-payment setup saved.',
                'set_payment_reminders' => 'Payment reminders updated.',
                'configure_installment_plan' => 'Installment plan configuration saved.',
                'connect_stripe' => 'Stripe integration settings saved.',
            ];

            $action = (string) $request->request->get('action');
            $this->addFlash('success', $actionMessages[$action] ?? 'Payment settings updated.');

            return $this->redirectToRoute('teacher_payments');
        }

        return $this->render('teacher/payments/index.html.twig', [
            'teacher' => $this->getTeacher(),
            'feeStructure' => $this->getFeeStructure(),
            'paymentHistory' => $this->getPaymentHistory(),
            'installmentPlans' => $this->getInstallmentPlans(),
        ]);
    }

    #[Route('/preferences', name: 'teacher_preferences', methods: ['GET', 'POST'])]
    public function preferences(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $actionMessages = [
                'save_layout' => 'Dashboard layout preferences saved.',
                'save_language' => 'Preferred language updated.',
                'save_notification_types' => 'Notification type preferences saved.',
                'save_personalization_mode' => 'Role-based personalization updated.',
                'save_accessibility' => 'Accessibility options saved.',
            ];

            $action = (string) $request->request->get('action');
            $this->addFlash('success', $actionMessages[$action] ?? 'Preferences updated successfully.');

            return $this->redirectToRoute('teacher_preferences');
        }

        return $this->render('teacher/preferences/index.html.twig', [
            'teacher' => $this->getTeacher(),
            'preferences' => $this->getTeacherPreferences(),
        ]);
    }

    #[Route('/live', name: 'teacher_live')]
    public function live(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $this->addFlash('success', 'Live class started! Share the link with your students.');
            return $this->redirectToRoute('teacher_live');
        }
        return $this->render('teacher/live/index.html.twig', [
            'teacher'  => $this->getTeacher(),
            'courses'  => $this->getCourses(),
            'sessions' => [
                ['id'=>1,'course'=>'Mathematics','topic'=>'Solving Quadratic Equations','date'=>'2026-05-01','time'=>'09:00 AM','duration'=>60,'participants'=>22,'status'=>'live','link'=>'meet.cityschool.edu/math-101'],
                ['id'=>2,'course'=>'Physics','topic'=>'Newton\'s Third Law Demo','date'=>'2026-05-06','time'=>'11:00 AM','duration'=>45,'participants'=>0,'status'=>'upcoming','link'=>''],
                ['id'=>3,'course'=>'Chemistry','topic'=>'Molecular Bonding Overview','date'=>'2026-05-08','time'=>'14:00 PM','duration'=>50,'participants'=>0,'status'=>'upcoming','link'=>''],
                ['id'=>4,'course'=>'Advanced Math','topic'=>'Introduction to Calculus','date'=>'2026-04-28','time'=>'10:00 AM','duration'=>60,'participants'=>18,'status'=>'recorded','link'=>'#'],
                ['id'=>5,'course'=>'Mathematics','topic'=>'Polynomial Functions','date'=>'2026-04-25','time'=>'09:00 AM','duration'=>55,'participants'=>26,'status'=>'recorded','link'=>'#'],
            ],
        ]);
    }

    #[Route('/profile', name: 'teacher_profile')]
    public function profile(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $this->addFlash('success', 'Profile updated successfully!');
            return $this->redirectToRoute('teacher_profile');
        }
        return $this->render('teacher/profile.html.twig', [
            'teacher' => $this->getTeacher(),
            'courses' => $this->getCourses(),
        ]);
    }
}
