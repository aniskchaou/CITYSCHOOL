<?php

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/super-admin')]
#[IsGranted('ROLE_SUPER_ADMIN')]
class SuperAdminPortalController extends AbstractController
{
    private const SUPER_ADMIN_PLAN_ORDER = ['Starter', 'Basic', 'Pro', 'Premium', 'Enterprise'];

    private function getSuperAdmin(): array
    {
        return [
            'name' => 'System Owner',
            'username' => 'superadmin',
            'email' => 'superadmin@cityschool.edu',
            'phone' => '+1 (555) 000-9900',
            'role' => 'Super Admin',
            'avatar' => 'SA',
            'joined' => 'January 2021',
            'lastLogin' => '15 minutes ago',
        ];
    }

    private function getSchools(): array
    {
        return [
            [
                'id' => 1,
                'name' => 'City School - Downtown',
                'code' => 'CSDT',
                'students' => 1320,
                'teachers' => 86,
                'status' => 'active',
                'plan' => 'Enterprise',
                'utilization' => 78,
                'admin' => 'Maya Green',
                'domain' => 'downtown.cityschool.edu',
                'brandColor' => '#ea580c',
                'logo' => 'Downtown Shield',
                'language' => 'English',
                'timezone' => 'UTC',
                'gradingSystem' => 'A-F',
                'whiteLabel' => true,
                'featureFlags' => [
                    'advanced_reports' => true,
                    'parent_live_chat' => true,
                    'ai_assistant' => true,
                    'custom_domain' => true,
                ],
            ],
            [
                'id' => 2,
                'name' => 'City School - North Campus',
                'code' => 'CSNC',
                'students' => 980,
                'teachers' => 64,
                'status' => 'active',
                'plan' => 'Pro',
                'utilization' => 64,
                'admin' => 'Noah Carter',
                'domain' => 'north.cityschool.edu',
                'brandColor' => '#0369a1',
                'logo' => 'North Crest',
                'language' => 'English',
                'timezone' => 'America/New_York',
                'gradingSystem' => 'Percentage',
                'whiteLabel' => true,
                'featureFlags' => [
                    'advanced_reports' => true,
                    'parent_live_chat' => true,
                    'ai_assistant' => false,
                    'custom_domain' => true,
                ],
            ],
            [
                'id' => 3,
                'name' => 'City School - East Campus',
                'code' => 'CSEC',
                'students' => 740,
                'teachers' => 49,
                'status' => 'suspended',
                'plan' => 'Pro',
                'utilization' => 58,
                'admin' => 'Ava Brown',
                'domain' => 'east.cityschool.edu',
                'brandColor' => '#16a34a',
                'logo' => 'East Leaf',
                'language' => 'French',
                'timezone' => 'Europe/Paris',
                'gradingSystem' => 'A-F',
                'whiteLabel' => false,
                'featureFlags' => [
                    'advanced_reports' => true,
                    'parent_live_chat' => false,
                    'ai_assistant' => false,
                    'custom_domain' => false,
                ],
            ],
            [
                'id' => 4,
                'name' => 'City School - West Campus',
                'code' => 'CSWC',
                'students' => 560,
                'teachers' => 38,
                'status' => 'archived',
                'plan' => 'Starter',
                'utilization' => 41,
                'admin' => 'Liam Hill',
                'domain' => 'west.cityschool.edu',
                'brandColor' => '#7c3aed',
                'logo' => 'West Peak',
                'language' => 'English',
                'timezone' => 'UTC',
                'gradingSystem' => 'Points',
                'whiteLabel' => false,
                'featureFlags' => [
                    'advanced_reports' => false,
                    'parent_live_chat' => false,
                    'ai_assistant' => false,
                    'custom_domain' => false,
                ],
            ],
        ];
    }

    private function getPlatformRoles(): array
    {
        return [
            ['name' => 'ROLE_SUPER_ADMIN', 'scope' => 'Platform', 'permissions' => ['all.schools.manage', 'all.integrations.manage', 'all.roles.manage', 'all.settings.manage']],
            ['name' => 'ROLE_ADMIN', 'scope' => 'School', 'permissions' => ['school.users.manage', 'school.classes.manage', 'school.finance.manage']],
            ['name' => 'ROLE_TEACHER', 'scope' => 'School', 'permissions' => ['grades.update', 'attendance.mark', 'messages.send']],
            ['name' => 'ROLE_PARENT', 'scope' => 'School', 'permissions' => ['grades.view', 'attendance.view', 'payments.pay']],
            ['name' => 'ROLE_STUDENT', 'scope' => 'School', 'permissions' => ['courses.view', 'assignments.submit', 'messages.send']],
        ];
    }

    private function getIntegrations(): array
    {
        return [
            ['name' => 'Google Workspace', 'type' => 'Identity', 'status' => 'connected', 'scope' => 'platform'],
            ['name' => 'Microsoft 365', 'type' => 'Calendar', 'status' => 'connected', 'scope' => 'per-school'],
            ['name' => 'Stripe', 'type' => 'Payments', 'status' => 'connected', 'scope' => 'platform'],
            ['name' => 'Zoom', 'type' => 'Live Classes', 'status' => 'disabled', 'scope' => 'per-school'],
            ['name' => 'Twilio', 'type' => 'SMS Alerts', 'status' => 'connected', 'scope' => 'platform'],
        ];
    }

    private function getRegionalProfiles(): array
    {
        return [
            ['region' => 'Global Default', 'language' => 'English', 'timezone' => 'UTC', 'gradingSystem' => 'A-F'],
            ['region' => 'North America', 'language' => 'English', 'timezone' => 'America/New_York', 'gradingSystem' => 'Percentage'],
            ['region' => 'Europe', 'language' => 'French', 'timezone' => 'Europe/Paris', 'gradingSystem' => 'A-F'],
            ['region' => 'MENA', 'language' => 'Arabic', 'timezone' => 'Asia/Dubai', 'gradingSystem' => 'Points'],
        ];
    }

    private function getGlobalUsers(): array
    {
        return [
            ['id' => 101, 'name' => 'Emma Parker', 'username' => 'emma.parker', 'school' => 'City School - Downtown', 'role' => 'ROLE_STUDENT', 'status' => 'active', 'lastActive' => '5 minutes ago', 'engagement' => 88],
            ['id' => 102, 'name' => 'Lucas Reed', 'username' => 'lucas.reed', 'school' => 'City School - North Campus', 'role' => 'ROLE_STUDENT', 'status' => 'active', 'lastActive' => '22 minutes ago', 'engagement' => 73],
            ['id' => 103, 'name' => 'Mr. James Smith', 'username' => 'mrsmith', 'school' => 'City School - Downtown', 'role' => 'ROLE_TEACHER', 'status' => 'active', 'lastActive' => '12 minutes ago', 'engagement' => 91],
            ['id' => 104, 'name' => 'Maya Green', 'username' => 'maya.green', 'school' => 'City School - Downtown', 'role' => 'ROLE_ADMIN', 'status' => 'active', 'lastActive' => '1 hour ago', 'engagement' => 82],
            ['id' => 105, 'name' => 'Olivia Hart', 'username' => 'olivia.hart', 'school' => 'City School - East Campus', 'role' => 'ROLE_PARENT', 'status' => 'suspended', 'lastActive' => '3 days ago', 'engagement' => 19],
            ['id' => 106, 'name' => 'Noah Carter', 'username' => 'noah.carter', 'school' => 'City School - North Campus', 'role' => 'ROLE_ADMIN', 'status' => 'active', 'lastActive' => '35 minutes ago', 'engagement' => 86],
            ['id' => 107, 'name' => 'Ava Brown', 'username' => 'ava.brown', 'school' => 'City School - East Campus', 'role' => 'ROLE_ADMIN', 'status' => 'banned', 'lastActive' => '10 days ago', 'engagement' => 11],
            ['id' => 108, 'name' => 'Sophia Khan', 'username' => 'sophia.khan', 'school' => 'City School - West Campus', 'role' => 'ROLE_TEACHER', 'status' => 'active', 'lastActive' => '2 hours ago', 'engagement' => 67],
        ];
    }

    private function getSuspiciousAccounts(): array
    {
        return [
            ['username' => 'olivia.hart', 'reason' => 'Duplicate phone across 3 accounts', 'risk' => 'high'],
            ['username' => 'ava.brown', 'reason' => 'Multiple failed logins from unusual region', 'risk' => 'high'],
            ['username' => 'lucas.reed', 'reason' => 'Shared device fingerprint with suspended account', 'risk' => 'medium'],
        ];
    }

    private function getRoleHierarchyNodes(): array
    {
        return [
            ['role' => 'ROLE_SUPER_ADMIN', 'parent' => 'root', 'custom' => false],
            ['role' => 'ROLE_REGIONAL_DIRECTOR', 'parent' => 'ROLE_SUPER_ADMIN', 'custom' => true],
            ['role' => 'ROLE_ADMIN', 'parent' => 'ROLE_REGIONAL_DIRECTOR', 'custom' => false],
            ['role' => 'ROLE_ACADEMIC_MANAGER', 'parent' => 'ROLE_ADMIN', 'custom' => true],
            ['role' => 'ROLE_TEACHER', 'parent' => 'ROLE_ACADEMIC_MANAGER', 'custom' => false],
            ['role' => 'ROLE_PARENT', 'parent' => 'ROLE_TEACHER', 'custom' => false],
            ['role' => 'ROLE_STUDENT', 'parent' => 'ROLE_PARENT', 'custom' => false],
        ];
    }

    private function getGlobalAnalytics(): array
    {
        return [
            'totals' => [
                'activeUsers' => 2164,
                'attendanceRate' => 89.4,
                'avgGrade' => 81.2,
                'engagementScore' => 76.8,
                'peakTime' => '09:00-11:00',
            ],
            'schoolComparison' => [
                ['school' => 'City School - Downtown', 'attendance' => 92, 'grade' => 84, 'engagement' => 82],
                ['school' => 'City School - North Campus', 'attendance' => 90, 'grade' => 80, 'engagement' => 78],
                ['school' => 'City School - East Campus', 'attendance' => 83, 'grade' => 74, 'engagement' => 63],
                ['school' => 'City School - West Campus', 'attendance' => 79, 'grade' => 71, 'engagement' => 58],
            ],
            'drilldown' => [
                [
                    'school' => 'City School - Downtown',
                    'classes' => [
                        ['name' => 'Grade 10-A', 'avg' => 86, 'attendance' => 94, 'student' => 'Emma Parker'],
                        ['name' => 'Grade 9-B', 'avg' => 79, 'attendance' => 89, 'student' => 'Lucas Reed'],
                    ],
                ],
                [
                    'school' => 'City School - East Campus',
                    'classes' => [
                        ['name' => 'Grade 11-A', 'avg' => 72, 'attendance' => 81, 'student' => 'Mina Aziz'],
                        ['name' => 'Grade 8-C', 'avg' => 69, 'attendance' => 78, 'student' => 'Omar Naji'],
                    ],
                ],
            ],
            'predictions' => [
                ['school' => 'City School - East Campus', 'insight' => 'Attendance is declining for 3 consecutive weeks.', 'risk' => 'high'],
                ['school' => 'City School - West Campus', 'insight' => 'Potential dropout risk in Grade 8-C increased by 14%.', 'risk' => 'medium'],
                ['school' => 'City School - North Campus', 'insight' => 'Engagement likely to improve if parent chat is enabled.', 'risk' => 'low'],
            ],
            'heatmap' => [
                ['label' => 'Mon', 'slots' => [42, 63, 88, 95, 74, 59]],
                ['label' => 'Tue', 'slots' => [40, 60, 83, 90, 71, 55]],
                ['label' => 'Wed', 'slots' => [38, 57, 79, 86, 69, 53]],
                ['label' => 'Thu', 'slots' => [45, 65, 85, 92, 76, 58]],
                ['label' => 'Fri', 'slots' => [33, 50, 71, 79, 61, 49]],
            ],
        ];
    }

    private function getSubscriptions(): array
    {
        return [
            ['id' => 1, 'schoolId' => 1, 'school' => 'City School - Downtown', 'plan' => 'Enterprise', 'amount' => 2999, 'billing' => 'Monthly', 'nextInvoice' => '2026-05-15', 'status' => 'active', 'renewal' => 'auto', 'studentsBillable' => 1320, 'studentLimit' => 5000, 'storageUsedGb' => 812, 'storageLimitGb' => 2048, 'paymentHealth' => 'healthy', 'failedAttempts' => 0, 'lastFailureAt' => null, 'nextRetryAt' => null],
            ['id' => 2, 'schoolId' => 2, 'school' => 'City School - North Campus', 'plan' => 'Pro', 'amount' => 1499, 'billing' => 'Monthly', 'nextInvoice' => '2026-05-20', 'status' => 'active', 'renewal' => 'auto', 'studentsBillable' => 980, 'studentLimit' => 1500, 'storageUsedGb' => 184, 'storageLimitGb' => 300, 'paymentHealth' => 'retrying', 'failedAttempts' => 1, 'lastFailureAt' => '2026-05-02 06:15', 'nextRetryAt' => '2026-05-03 14:00'],
            ['id' => 3, 'schoolId' => 3, 'school' => 'City School - East Campus', 'plan' => 'Pro', 'amount' => 1499, 'billing' => 'Monthly', 'nextInvoice' => '2026-05-22', 'status' => 'active', 'renewal' => 'manual', 'studentsBillable' => 740, 'studentLimit' => 1500, 'storageUsedGb' => 221, 'storageLimitGb' => 300, 'paymentHealth' => 'grace_period', 'failedAttempts' => 3, 'lastFailureAt' => '2026-05-01 09:40', 'nextRetryAt' => '2026-05-04 08:00'],
            ['id' => 4, 'schoolId' => 4, 'school' => 'City School - West Campus', 'plan' => 'Starter', 'amount' => 699, 'billing' => 'Monthly', 'nextInvoice' => '2026-05-30', 'status' => 'trial', 'renewal' => 'manual', 'studentsBillable' => 560, 'studentLimit' => 600, 'storageUsedGb' => 34, 'storageLimitGb' => 100, 'paymentHealth' => 'healthy', 'failedAttempts' => 0, 'lastFailureAt' => null, 'nextRetryAt' => null],
        ];
    }

    private function getPricingPlans(): array
    {
        return [
            ['name' => 'Starter', 'price' => 699, 'billing' => 'Monthly', 'studentLimit' => 600, 'storageLimitGb' => 100, 'features' => ['Core LMS', 'Attendance'], 'campuses' => 1, 'status' => 'active'],
            ['name' => 'Basic', 'price' => 499, 'billing' => 'Monthly', 'studentLimit' => 500, 'storageLimitGb' => 50, 'features' => ['Core LMS', 'Attendance', 'Gradebook'], 'campuses' => 2, 'status' => 'active'],
            ['name' => 'Premium', 'price' => 1499, 'billing' => 'Monthly', 'studentLimit' => 1500, 'storageLimitGb' => 300, 'features' => ['All Basic', 'Parent Portal', 'Analytics'], 'campuses' => 2, 'status' => 'active'],
            ['name' => 'Enterprise', 'price' => 2999, 'billing' => 'Monthly', 'studentLimit' => 5000, 'storageLimitGb' => 2048, 'features' => ['All Premium', 'White Label', 'Priority Support'], 'campuses' => 1, 'status' => 'active'],
        ];
    }

    private function getInvoices(): array
    {
        return [
            ['invoiceNo' => 'INV-2026-0515-001', 'school' => 'City School - Downtown', 'amount' => 2999, 'date' => '2026-05-15', 'status' => 'paid', 'method' => 'Stripe AutoPay'],
            ['invoiceNo' => 'INV-2026-0520-002', 'school' => 'City School - North Campus', 'amount' => 1499, 'date' => '2026-05-20', 'status' => 'pending', 'method' => 'Card'],
            ['invoiceNo' => 'INV-2026-0522-003', 'school' => 'City School - East Campus', 'amount' => 1499, 'date' => '2026-05-22', 'status' => 'pending', 'method' => 'Bank Transfer'],
            ['invoiceNo' => 'INV-2026-0530-004', 'school' => 'City School - West Campus', 'amount' => 699, 'date' => '2026-05-30', 'status' => 'overdue', 'method' => 'Manual'],
        ];
    }

    private function getCoupons(): array
    {
        return [
            ['code' => 'WELCOME10', 'type' => 'percent', 'value' => 10, 'appliesTo' => 'New schools', 'status' => 'active'],
            ['code' => 'UPGRADE20', 'type' => 'percent', 'value' => 20, 'appliesTo' => 'Plan upgrades', 'status' => 'active'],
            ['code' => 'SUMMER150', 'type' => 'fixed', 'value' => 150, 'appliesTo' => 'Any plan', 'status' => 'expired'],
        ];
    }

    private function getSubscriptionConsoleState(Request $request): array
    {
        $session = $request->getSession();

        return [
            'plans' => $session->get('super_admin_pricing_plans', $this->getPricingPlans()),
            'subscriptions' => $session->get('super_admin_subscriptions_state', $this->getSubscriptions()),
            'invoices' => $session->get('super_admin_invoices_state', $this->getInvoices()),
        ];
    }

    private function saveSubscriptionConsoleState(Request $request, array $plans, array $subscriptions, array $invoices): void
    {
        $session = $request->getSession();
        $session->set('super_admin_pricing_plans', $plans);
        $session->set('super_admin_subscriptions_state', $subscriptions);
        $session->set('super_admin_invoices_state', $invoices);
    }

    private function getRevenueOverview(array $subscriptions, array $invoices): array
    {
        $paid = array_filter($invoices, fn($invoice) => $invoice['status'] === 'paid');
        $pending = array_filter($invoices, fn($invoice) => $invoice['status'] === 'pending');
        $overdue = array_filter($invoices, fn($invoice) => $invoice['status'] === 'overdue');
        $mrr = array_sum(array_column($subscriptions, 'amount'));

        $planBreakdown = [];
        foreach ($subscriptions as $subscription) {
            $planBreakdown[$subscription['plan']] = ($planBreakdown[$subscription['plan']] ?? 0) + $subscription['amount'];
        }

        return [
            'mrr' => $mrr,
            'arr' => $mrr * 12,
            'collected' => array_sum(array_column($paid, 'amount')),
            'pending' => array_sum(array_column($pending, 'amount')),
            'overdue' => array_sum(array_column($overdue, 'amount')),
            'planBreakdown' => $planBreakdown,
            'trend' => [
                ['month' => 'Jan', 'value' => 5200],
                ['month' => 'Feb', 'value' => 5600],
                ['month' => 'Mar', 'value' => 6120],
                ['month' => 'Apr', 'value' => 6290],
                ['month' => 'May', 'value' => $mrr],
            ],
        ];
    }

    private function getFailedPaymentQueue(array $subscriptions, array $invoices): array
    {
        $queue = [];

        foreach ($subscriptions as $subscription) {
            if (($subscription['paymentHealth'] ?? 'healthy') === 'healthy') {
                continue;
            }

            $queue[] = [
                'subscriptionId' => $subscription['id'],
                'school' => $subscription['school'],
                'plan' => $subscription['plan'],
                'status' => $subscription['paymentHealth'],
                'failedAttempts' => $subscription['failedAttempts'],
                'lastFailureAt' => $subscription['lastFailureAt'],
                'nextRetryAt' => $subscription['nextRetryAt'],
                'invoice' => current(array_filter($invoices, fn($invoice) => $invoice['school'] === $subscription['school'] && $invoice['status'] !== 'paid')) ?: null,
            ];
        }

        return $queue;
    }

    private function processSubscriptionConsoleAction(Request $request, array $plans, array $subscriptions, array $invoices): array
    {
        $action = (string) $request->request->get('action', 'save');

        switch ($action) {
            case 'create_plan':
                $planName = trim((string) $request->request->get('plan_name', '')) ?: 'Custom Plan';
                $plans[] = [
                    'name' => $planName,
                    'price' => (int) $request->request->get('plan_price', 999),
                    'billing' => (string) $request->request->get('plan_billing', 'Monthly'),
                    'studentLimit' => (int) $request->request->get('student_limit', 500),
                    'storageLimitGb' => (int) $request->request->get('storage_limit_gb', 100),
                    'features' => array_filter(array_map('trim', explode(',', (string) $request->request->get('plan_features', 'Core LMS, Attendance')))),
                    'campuses' => 0,
                    'status' => 'draft',
                ];
                $this->addFlash('success', sprintf('Pricing plan %s created successfully.', $planName));
                break;

            case 'assign_plan':
            case 'upgrade':
            case 'downgrade':
                $subId = (int) $request->request->get('sub_id', 0);
                $targetPlan = (string) $request->request->get('target_plan', '');
                foreach ($subscriptions as &$subscription) {
                    if ($subscription['id'] !== $subId) {
                        continue;
                    }

                    if ($targetPlan === '') {
                        $currentIndex = array_search($subscription['plan'], self::SUPER_ADMIN_PLAN_ORDER, true);
                        if ($action === 'upgrade') {
                            $targetPlan = self::SUPER_ADMIN_PLAN_ORDER[min(count(self::SUPER_ADMIN_PLAN_ORDER) - 1, $currentIndex + 1)] ?? $subscription['plan'];
                        } elseif ($action === 'downgrade') {
                            $targetPlan = self::SUPER_ADMIN_PLAN_ORDER[max(0, $currentIndex - 1)] ?? $subscription['plan'];
                        }
                    }

                    $planMeta = current(array_filter($plans, fn($plan) => $plan['name'] === $targetPlan));
                    if (!$planMeta) {
                        break;
                    }

                    $subscription['plan'] = $planMeta['name'];
                    $subscription['amount'] = $planMeta['price'];
                    $subscription['studentLimit'] = $planMeta['studentLimit'];
                    $subscription['storageLimitGb'] = $planMeta['storageLimitGb'];
                    $subscription['status'] = 'active';
                    break;
                }
                unset($subscription);
                $this->addFlash('success', ucfirst(str_replace('_', ' ', $action)) . ' processed successfully.');
                break;

            case 'renew':
                $subId = (int) $request->request->get('sub_id', 0);
                foreach ($subscriptions as &$subscription) {
                    if ($subscription['id'] !== $subId) {
                        continue;
                    }

                    $subscription['nextInvoice'] = (new \DateTimeImmutable($subscription['nextInvoice']))->modify('+1 month')->format('Y-m-d');
                    $subscription['status'] = 'active';
                    $invoices[] = [
                        'invoiceNo' => 'INV-' . (new \DateTimeImmutable())->format('Ymd') . '-' . $subscription['id'],
                        'school' => $subscription['school'],
                        'amount' => $subscription['amount'],
                        'date' => (new \DateTimeImmutable())->format('Y-m-d'),
                        'status' => 'paid',
                        'method' => 'Stripe AutoPay',
                    ];
                    break;
                }
                unset($subscription);
                $this->addFlash('success', 'Subscription renewal processed successfully.');
                break;

            case 'retry_failed_payment':
            case 'recover_payment':
                $subId = (int) $request->request->get('sub_id', 0);
                foreach ($subscriptions as &$subscription) {
                    if ($subscription['id'] !== $subId) {
                        continue;
                    }

                    $subscription['paymentHealth'] = 'healthy';
                    $subscription['failedAttempts'] = 0;
                    $subscription['lastFailureAt'] = null;
                    $subscription['nextRetryAt'] = null;
                    $subscription['status'] = 'active';
                }
                unset($subscription);
                foreach ($invoices as &$invoice) {
                    if (($invoice['school'] ?? '') === ((string) $request->request->get('school_name', '')) && $invoice['status'] !== 'paid') {
                        $invoice['status'] = 'paid';
                        $invoice['method'] = 'Recovered Payment';
                    }
                }
                unset($invoice);
                $this->addFlash('success', 'Failed payment recovered and school access restored.');
                break;

            case 'suspend_failed_payment':
                $subId = (int) $request->request->get('sub_id', 0);
                foreach ($subscriptions as &$subscription) {
                    if ($subscription['id'] !== $subId) {
                        continue;
                    }

                    $subscription['paymentHealth'] = 'suspended';
                    $subscription['status'] = 'suspended';
                    $subscription['nextRetryAt'] = null;
                    break;
                }
                unset($subscription);
                $this->addFlash('success', 'School account suspended after failed payment escalation.');
                break;
        }

        return [$plans, $subscriptions, $invoices];
    }

    private function getLoginActivity(): array
    {
        return [
            ['user' => 'maya.green', 'school' => 'City School - Downtown', 'ip' => '10.1.4.22', 'device' => 'Chrome / Windows', 'location' => 'New York, US', 'time' => '2026-05-02 09:12', 'risk' => 'low'],
            ['user' => 'ava.brown', 'school' => 'City School - East Campus', 'ip' => '198.51.100.34', 'device' => 'Firefox / Linux', 'location' => 'Unknown Proxy', 'time' => '2026-05-02 07:44', 'risk' => 'high'],
            ['user' => 'mrsmith', 'school' => 'City School - Downtown', 'ip' => '10.1.1.88', 'device' => 'Safari / iPad', 'location' => 'Boston, US', 'time' => '2026-05-02 08:31', 'risk' => 'low'],
            ['user' => 'olivia.hart', 'school' => 'City School - East Campus', 'ip' => '203.0.113.220', 'device' => 'Android App', 'location' => 'Dubai, AE', 'time' => '2026-05-01 22:17', 'risk' => 'medium'],
        ];
    }

    private function getSecurityPolicies(): array
    {
        return [
            'minPasswordLength' => 10,
            'requireSymbols' => true,
            'passwordRotationDays' => 90,
            'force2faAdmins' => true,
            'force2faTeachers' => false,
            'gdprDsrPortal' => true,
            'encryptionAtRest' => true,
            'encryptionInTransit' => true,
        ];
    }

    private function getGlobalAuditLogs(): array
    {
        return [
            ['actor' => 'superadmin', 'action' => 'school.suspend', 'target' => 'City School - East Campus', 'time' => '2026-05-01 18:40'],
            ['actor' => 'superadmin', 'action' => 'subscription.upgrade', 'target' => 'City School - North Campus', 'time' => '2026-05-01 16:15'],
            ['actor' => 'system', 'action' => 'backup.completed', 'target' => 'global-backup-2026-05-01', 'time' => '2026-05-01 02:00'],
            ['actor' => 'superadmin', 'action' => 'permissions.override', 'target' => 'user:olivia.hart', 'time' => '2026-04-30 21:08'],
        ];
    }

    private function getComplianceReports(): array
    {
        return [
            ['name' => 'GDPR Data Subject Requests', 'period' => 'Apr 2026', 'status' => 'ready'],
            ['name' => 'Security Access Review', 'period' => 'Q1 2026', 'status' => 'ready'],
            ['name' => 'Encryption Coverage', 'period' => 'Apr 2026', 'status' => 'pending'],
        ];
    }

    private function getIntegrationCatalog(): array
    {
        return [
            ['name' => 'Zoom', 'category' => 'Video', 'status' => 'enabled', 'marketplace' => true],
            ['name' => 'Google Meet', 'category' => 'Video', 'status' => 'disabled', 'marketplace' => true],
            ['name' => 'Stripe', 'category' => 'Payments', 'status' => 'enabled', 'marketplace' => true],
            ['name' => 'PayPal', 'category' => 'Payments', 'status' => 'disabled', 'marketplace' => true],
            ['name' => 'Azure Blob', 'category' => 'Storage', 'status' => 'enabled', 'marketplace' => false],
            ['name' => 'Google Drive', 'category' => 'Storage', 'status' => 'enabled', 'marketplace' => true],
        ];
    }

    private function getApiKeys(): array
    {
        return [
            ['name' => 'School Admin Mobile API', 'key' => 'sk_live_9f2f...a32b', 'scope' => 'school_admin', 'createdAt' => '2026-04-18', 'lastUsed' => '2026-05-02 08:20', 'status' => 'active'],
            ['name' => 'Analytics Export Service', 'key' => 'sk_live_3c1a...b877', 'scope' => 'analytics', 'createdAt' => '2026-03-11', 'lastUsed' => '2026-05-01 21:05', 'status' => 'active'],
            ['name' => 'Legacy Integration Key', 'key' => 'sk_test_7ae0...11f3', 'scope' => 'legacy', 'createdAt' => '2025-11-28', 'lastUsed' => '2026-03-30 10:40', 'status' => 'revoked'],
        ];
    }

    private function getApiUsage(): array
    {
        return [
            ['endpoint' => '/api/v1/schools', 'calls24h' => 14200, 'errorRate' => 0.2, 'p95Ms' => 188],
            ['endpoint' => '/api/v1/users', 'calls24h' => 22100, 'errorRate' => 0.4, 'p95Ms' => 202],
            ['endpoint' => '/api/v1/grades', 'calls24h' => 18400, 'errorRate' => 0.3, 'p95Ms' => 170],
            ['endpoint' => '/api/v1/invoices', 'calls24h' => 7600, 'errorRate' => 0.6, 'p95Ms' => 244],
        ];
    }

    private function getWebhooks(): array
    {
        return [
            ['name' => 'payment.paid', 'url' => 'https://hooks.schoolops.io/payments', 'status' => 'active', 'lastDelivery' => '2026-05-02 08:42'],
            ['name' => 'student.created', 'url' => 'https://hooks.schoolops.io/students', 'status' => 'active', 'lastDelivery' => '2026-05-02 08:11'],
            ['name' => 'grade.updated', 'url' => 'https://hooks.schoolops.io/grades', 'status' => 'disabled', 'lastDelivery' => '2026-04-29 14:27'],
        ];
    }

    private function getSystemAnnouncements(): array
    {
        return [
            ['title' => 'Scheduled Maintenance', 'target' => 'all schools', 'channel' => 'push+email', 'scheduledAt' => '2026-05-04 22:00', 'status' => 'scheduled'],
            ['title' => 'New Analytics Module Available', 'target' => 'admins, teachers', 'channel' => 'in-app', 'scheduledAt' => 'sent', 'status' => 'sent'],
            ['title' => 'Billing Reminder', 'target' => 'school admins', 'channel' => 'email', 'scheduledAt' => '2026-05-03 09:00', 'status' => 'scheduled'],
        ];
    }

    private function getMessageSegments(): array
    {
        return [
            ['name' => 'Role: Admin', 'count' => 84],
            ['name' => 'Role: Teacher', 'count' => 237],
            ['name' => 'Region: North America', 'count' => 1180],
            ['name' => 'Activity: Inactive 7+ days', 'count' => 312],
        ];
    }

    private function getNotificationAnalytics(): array
    {
        return [
            'pushOpenRate' => 62.4,
            'emailOpenRate' => 48.7,
            'clickRate' => 21.9,
            'campaignsSent' => 14,
            'topCampaign' => 'Parent Portal Launch',
        ];
    }

    private function getThemeProfiles(): array
    {
        return [
            ['name' => 'Sunrise Orange', 'primary' => '#f97316', 'accent' => '#9a3412', 'status' => 'active', 'source' => 'core'],
            ['name' => 'Ocean Blue', 'primary' => '#0369a1', 'accent' => '#0f172a', 'status' => 'inactive', 'source' => 'marketplace'],
            ['name' => 'Forest Green', 'primary' => '#16a34a', 'accent' => '#14532d', 'status' => 'inactive', 'source' => 'marketplace'],
        ];
    }

    private function getDefaultLayouts(): array
    {
        return [
            ['role' => 'Admin Dashboard', 'layout' => 'KPI + Activity + Alerts', 'columns' => '3-column', 'status' => 'default'],
            ['role' => 'Teacher Dashboard', 'layout' => 'Classes + Tasks + Messages', 'columns' => '2-column', 'status' => 'default'],
            ['role' => 'Parent Dashboard', 'layout' => 'Children + Grades + Fees', 'columns' => '2-column', 'status' => 'custom'],
            ['role' => 'Student Dashboard', 'layout' => 'Courses + Attendance + Announcements', 'columns' => '2-column', 'status' => 'default'],
        ];
    }

    private function getTemplateLibrary(): array
    {
        return [
            ['type' => 'Email', 'name' => 'Welcome Campaign', 'updatedAt' => '2026-04-28', 'status' => 'published'],
            ['type' => 'Email', 'name' => 'Payment Reminder', 'updatedAt' => '2026-04-20', 'status' => 'published'],
            ['type' => 'Report', 'name' => 'Monthly Academic Report', 'updatedAt' => '2026-04-24', 'status' => 'draft'],
            ['type' => 'Certificate', 'name' => 'Term Excellence Certificate', 'updatedAt' => '2026-03-30', 'status' => 'published'],
            ['type' => 'Certificate', 'name' => 'Attendance Achievement', 'updatedAt' => '2026-04-15', 'status' => 'published'],
        ];
    }

    private function getWorkflowDefinitions(): array
    {
        return [
            [
                'name' => 'Grading Process',
                'steps' => ['Collect Assessments', 'Teacher Review', 'Admin Approval', 'Publish Grades'],
                'mode' => 'rule-based',
                'status' => 'active',
            ],
            [
                'name' => 'Report Card Generation',
                'steps' => ['Aggregate Scores', 'Generate PDF', 'Quality Check', 'Notify Parents'],
                'mode' => 'automated',
                'status' => 'active',
            ],
            [
                'name' => 'Certificate Issuance',
                'steps' => ['Eligibility Check', 'Render Template', 'Principal Signature', 'Distribute'],
                'mode' => 'drag-drop',
                'status' => 'draft',
            ],
        ];
    }

    private function getThemeMarketplace(): array
    {
        return [
            ['name' => 'Modern Slate', 'vendor' => 'EduThemes', 'price' => 49, 'rating' => 4.8],
            ['name' => 'Classic Campus', 'vendor' => 'SchoolKit', 'price' => 29, 'rating' => 4.5],
            ['name' => 'Aurora Learning', 'vendor' => 'NovaLabs', 'price' => 59, 'rating' => 4.9],
        ];
    }

    private function getPerformance(): array
    {
        return [
            'uptime' => 99.98,
            'activeUsers' => 2164,
            'apiRequests' => 1840000,
            'storageUsedGb' => 1280,
            'avgLatencyMs' => 142,
            'errorRate' => 0.14,
            'emailSentToday' => 2461,
            'chatMessagesToday' => 813,
        ];
    }

    private function getGlobalSettings(): array
    {
        return [
            'defaultTimezone' => 'UTC',
            'maintenanceMode' => false,
            'emailNotifications' => true,
            'realTimeNotifications' => true,
            'calendarSync' => true,
            'fileStorageProvider' => 'Local Disk',
            'chatEnabled' => true,
            'maxUploadMb' => 50,
        ];
    }

    #[Route('/dashboard', name: 'super_admin_dashboard')]
    public function dashboard(): Response
    {
        $schools = $this->getSchools();
        $performance = $this->getPerformance();

        return $this->render('super_admin/dashboard.html.twig', [
            'super_admin' => $this->getSuperAdmin(),
            'schools' => $schools,
            'performance' => $performance,
            'total_schools' => count($schools),
            'total_students' => array_sum(array_column($schools, 'students')),
            'total_teachers' => array_sum(array_column($schools, 'teachers')),
            'active_subscriptions' => count(array_filter($this->getSubscriptions(), fn($s) => $s['status'] === 'active')),
        ]);
    }

    #[Route('/schools', name: 'super_admin_schools')]
    public function schools(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $action = (string) $request->request->get('action', 'save');

            $messages = [
                'register' => 'School registered successfully.',
                'assign_admin' => 'School admin assigned successfully.',
                'suspend' => 'School suspended successfully.',
                'archive' => 'School archived successfully.',
                'delete' => 'School deleted successfully.',
                'save' => 'School profile saved successfully.',
            ];

            $this->addFlash('success', $messages[$action] ?? 'School operation completed.');
            return $this->redirectToRoute('super_admin_schools');
        }

        return $this->render('super_admin/schools/index.html.twig', [
            'super_admin' => $this->getSuperAdmin(),
            'schools' => $this->getSchools(),
        ]);
    }

    #[Route('/global-settings', name: 'super_admin_global_settings')]
    public function globalSettings(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $this->addFlash('success', 'Global settings updated successfully.');
            return $this->redirectToRoute('super_admin_global_settings');
        }

        return $this->render('super_admin/global_settings/index.html.twig', [
            'super_admin' => $this->getSuperAdmin(),
            'settings' => $this->getGlobalSettings(),
            'schools' => $this->getSchools(),
            'roles' => $this->getPlatformRoles(),
            'integrations' => $this->getIntegrations(),
            'regions' => $this->getRegionalProfiles(),
            'environments' => ['production', 'staging'],
            'active_environment' => 'production',
        ]);
    }

    #[Route('/subscriptions', name: 'super_admin_subscriptions')]
    public function subscriptions(Request $request): Response
    {
        ['plans' => $plans, 'subscriptions' => $subscriptions, 'invoices' => $invoices] = $this->getSubscriptionConsoleState($request);

        if ($request->isMethod('POST')) {
            [$plans, $subscriptions, $invoices] = $this->processSubscriptionConsoleAction($request, $plans, $subscriptions, $invoices);
            $this->saveSubscriptionConsoleState($request, $plans, $subscriptions, $invoices);
            return $this->redirectToRoute('super_admin_subscriptions');
        }

        $revenue = $this->getRevenueOverview($subscriptions, $invoices);
        $failedPayments = $this->getFailedPaymentQueue($subscriptions, $invoices);

        return $this->render('super_admin/subscriptions/index.html.twig', [
            'super_admin' => $this->getSuperAdmin(),
            'subscriptions' => $subscriptions,
            'plans' => $plans,
            'schools' => $this->getSchools(),
            'invoices' => $invoices,
            'coupons' => $this->getCoupons(),
            'revenue' => $revenue,
            'failed_payments' => $failedPayments,
        ]);
    }

    #[Route('/data-center', name: 'super_admin_data_center')]
    public function dataCenter(): Response
    {
        return $this->render('super_admin/data_center/index.html.twig', [
            'super_admin' => $this->getSuperAdmin(),
            'schools' => $this->getSchools(),
        ]);
    }

    #[Route('/performance', name: 'super_admin_performance')]
    public function performance(): Response
    {
        return $this->render('super_admin/performance/index.html.twig', [
            'super_admin' => $this->getSuperAdmin(),
            'performance' => $this->getPerformance(),
            'schools' => $this->getSchools(),
        ]);
    }

    #[Route('/shared-actions', name: 'super_admin_shared_actions')]
    public function sharedActions(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $this->addFlash('success', 'Shared action settings saved.');
            return $this->redirectToRoute('super_admin_shared_actions');
        }

        return $this->render('super_admin/shared_actions/index.html.twig', [
            'super_admin' => $this->getSuperAdmin(),
            'performance' => $this->getPerformance(),
        ]);
    }

    #[Route('/profile', name: 'super_admin_profile')]
    public function profile(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $this->addFlash('success', 'Profile updated successfully.');
            return $this->redirectToRoute('super_admin_profile');
        }

        return $this->render('super_admin/profile.html.twig', [
            'super_admin' => $this->getSuperAdmin(),
        ]);
    }

    #[Route('/user-governance', name: 'super_admin_user_governance')]
    public function userGovernance(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $action = (string) $request->request->get('action', 'update');

            $messages = [
                'override_permissions' => 'Emergency permission override applied.',
                'impersonate' => 'Impersonation session initialized (simulation mode).',
                'suspend' => 'Account suspended successfully.',
                'ban' => 'Account banned successfully.',
                'import' => 'Bulk import completed successfully.',
                'export' => 'Bulk export prepared successfully.',
                'save_hierarchy' => 'Role hierarchy updated successfully.',
            ];

            $this->addFlash('success', $messages[$action] ?? 'User governance action completed.');
            return $this->redirectToRoute('super_admin_user_governance');
        }

        $users = $this->getGlobalUsers();

        return $this->render('super_admin/user_governance/index.html.twig', [
            'super_admin' => $this->getSuperAdmin(),
            'users' => $users,
            'suspicious_accounts' => $this->getSuspiciousAccounts(),
            'role_hierarchy' => $this->getRoleHierarchyNodes(),
            'total_users' => count($users),
            'suspended_users' => count(array_filter($users, fn($u) => $u['status'] === 'suspended')),
            'banned_users' => count(array_filter($users, fn($u) => $u['status'] === 'banned')),
        ]);
    }

    #[Route('/analytics', name: 'super_admin_analytics')]
    public function analytics(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $format = (string) $request->request->get('format', 'CSV');
            $this->addFlash('success', sprintf('Analytics report export queued in %s format.', strtoupper($format)));
            return $this->redirectToRoute('super_admin_analytics');
        }

        return $this->render('super_admin/analytics/index.html.twig', [
            'super_admin' => $this->getSuperAdmin(),
            'analytics' => $this->getGlobalAnalytics(),
        ]);
    }

    #[Route('/security-compliance', name: 'super_admin_security_compliance')]
    public function securityCompliance(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $action = (string) $request->request->get('action', 'update');

            $messages = [
                'update_policy' => 'Security policy updated successfully.',
                'force_logout' => 'System-wide emergency logout triggered.',
                'backup' => 'System backup started successfully.',
                'restore' => 'System restore simulation started.',
                'generate_compliance' => 'Compliance report generation queued.',
                'threat_scan' => 'Threat detection scan triggered.',
                'update_encryption' => 'Encryption controls updated.',
            ];

            $this->addFlash('success', $messages[$action] ?? 'Security/compliance action completed.');
            return $this->redirectToRoute('super_admin_security_compliance');
        }

        return $this->render('super_admin/security_compliance/index.html.twig', [
            'super_admin' => $this->getSuperAdmin(),
            'login_activity' => $this->getLoginActivity(),
            'security_policies' => $this->getSecurityPolicies(),
            'audit_logs' => $this->getGlobalAuditLogs(),
            'compliance_reports' => $this->getComplianceReports(),
        ]);
    }

    #[Route('/integrations-api', name: 'super_admin_integrations_api')]
    public function integrationsApi(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $action = (string) $request->request->get('action', 'update');

            $messages = [
                'toggle_integration' => 'Integration toggle updated successfully.',
                'generate_key' => 'API key generated successfully.',
                'revoke_key' => 'API key revoked successfully.',
                'save_webhook' => 'Webhook configuration saved successfully.',
                'monitor_usage' => 'API usage refresh triggered.',
                'publish_marketplace' => 'Integration published to marketplace.',
            ];

            $this->addFlash('success', $messages[$action] ?? 'Integration/API action completed.');
            return $this->redirectToRoute('super_admin_integrations_api');
        }

        return $this->render('super_admin/integrations_api/index.html.twig', [
            'super_admin' => $this->getSuperAdmin(),
            'schools' => $this->getSchools(),
            'integration_catalog' => $this->getIntegrationCatalog(),
            'api_keys' => $this->getApiKeys(),
            'api_usage' => $this->getApiUsage(),
            'webhooks' => $this->getWebhooks(),
        ]);
    }

    #[Route('/communications', name: 'super_admin_communications')]
    public function communications(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $action = (string) $request->request->get('action', 'send');

            $messages = [
                'send_global' => 'Global announcement sent successfully.',
                'schedule' => 'Announcement scheduled successfully.',
                'push_all' => 'System-wide push notification dispatched.',
                'email_campaign' => 'Email campaign started successfully.',
                'send_segment' => 'Segmented campaign queued successfully.',
            ];

            $this->addFlash('success', $messages[$action] ?? 'Communication action completed.');
            return $this->redirectToRoute('super_admin_communications');
        }

        return $this->render('super_admin/communications/index.html.twig', [
            'super_admin' => $this->getSuperAdmin(),
            'announcements' => $this->getSystemAnnouncements(),
            'segments' => $this->getMessageSegments(),
            'notif_analytics' => $this->getNotificationAnalytics(),
            'schools' => $this->getSchools(),
        ]);
    }

    #[Route('/platform-customization', name: 'super_admin_platform_customization')]
    public function platformCustomization(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $action = (string) $request->request->get('action', 'save');

            $messages = [
                'apply_theme' => 'Global UI theme applied successfully.',
                'save_layout' => 'Default dashboard layouts updated.',
                'update_template' => 'Template library updated successfully.',
                'save_workflow' => 'Workflow configuration saved successfully.',
                'open_builder' => 'Drag-and-drop workflow builder opened (preview).',
                'install_theme' => 'Theme installed from marketplace.',
                'save' => 'Platform customization settings saved.',
            ];

            $this->addFlash('success', $messages[$action] ?? 'Customization action completed.');
            return $this->redirectToRoute('super_admin_platform_customization');
        }

        return $this->render('super_admin/platform_customization/index.html.twig', [
            'super_admin' => $this->getSuperAdmin(),
            'themes' => $this->getThemeProfiles(),
            'layouts' => $this->getDefaultLayouts(),
            'templates' => $this->getTemplateLibrary(),
            'workflows' => $this->getWorkflowDefinitions(),
            'marketplace_themes' => $this->getThemeMarketplace(),
        ]);
    }
}
