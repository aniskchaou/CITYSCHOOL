<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Yaml\Yaml;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminPortalController extends AbstractController
{
    private const DEMO_SHARED_PASSWORD = 'student123';

    private const SUBSCRIPTION_PRICES = [
        'Basic' => ['monthly' => 79, 'yearly' => 756, 'storage_limit_gb' => 50],
        'Pro' => ['monthly' => 149, 'yearly' => 1428, 'storage_limit_gb' => 300],
        'Enterprise' => ['monthly' => 299, 'yearly' => 2868, 'storage_limit_gb' => 2048],
    ];

    private const PLAN_ORDER = [
        'Basic' => 1,
        'Pro' => 2,
        'Enterprise' => 3,
    ];

    private const PAYMENT_RETRY_OFFSETS = ['+6 hours', '+30 hours', '+78 hours'];

    // ─── Mock Data ────────────────────────────────────────────────────────────

    private function getAdmin(): array
    {
        return [
            'name'       => 'Admin User',
            'username'   => 'admin',
            'email'      => 'admin@cityschool.edu',
            'role'       => 'Super Admin',
            'phone'      => '+1 (555) 000-0001',
            'avatar'     => '/dummy/student-lg-1.jpg',
            'joined'     => 'January 2020',
            'department' => 'Administration',
            'lastLogin'  => '2 hours ago',
        ];
    }

    private function getBillingSubscriptionState(Request $request): array
    {
        $session = $request->getSession();
        $portalContext = $session->get('school_portal_context', []);
        $studentCount = count(array_filter($this->getUsers(), fn($user) => $user['role'] === 'ROLE_STUDENT'));
        $seedPlan = $portalContext['plan'] ?? 'Pro';
        $seedCycle = $portalContext['billing_cycle'] ?? 'monthly';
        $storageLimit = self::SUBSCRIPTION_PRICES[$seedPlan]['storage_limit_gb'] ?? 300;

        $defaultState = [
            'school_name' => $portalContext['school_name'] ?? 'City School',
            'status' => $portalContext['status'] ?? 'active',
            'current_plan' => $seedPlan,
            'billing_cycle' => $seedCycle,
            'next_payment_date' => (new \DateTimeImmutable('+21 days'))->format('Y-m-d'),
            'renewal_mode' => 'auto',
            'payment_method' => [
                'provider' => 'stripe',
                'brand' => 'Visa',
                'last4' => '4242',
                'exp' => '08/28',
                'paypal_email' => null,
            ],
            'student_count' => $studentCount,
            'storage_used_gb' => 18.4,
            'storage_limit_gb' => $storageLimit,
            'pending_plan_change' => null,
            'pending_plan_effective_date' => null,
            'last_invoice_ref' => 'SaaS-INV-2026-0501',
            'last_proration_amount' => 0,
            'feature_unlocks' => [],
            'renewal_notice' => null,
            'payment_health' => 'healthy',
            'payment_failures' => 0,
            'failed_invoice_ref' => null,
            'latest_payment_failure_at' => null,
            'grace_period_ends_at' => null,
            'feature_restriction' => false,
            'suspension_at' => null,
            'retry_schedule' => [],
            'warnings' => [],
            'payment_method_refresh_pending' => false,
            'cancellation_requested' => false,
            'cancellation_mode' => null,
            'cancellation_effective_date' => null,
            'cancellation_feedback' => null,
            'downgrade_offer_plan' => 'Basic',
            'data_export_ready' => false,
        ];

        $state = array_replace($defaultState, $session->get('billing_subscription_state', []));
        if (!is_array($state['payment_method'] ?? null)) {
            $state['payment_method'] = $defaultState['payment_method'];
        }
        $state['payment_method'] = array_replace($defaultState['payment_method'], $state['payment_method']);
        $state['payment_method']['provider'] = ($state['payment_method']['provider'] ?? 'stripe') === 'paypal' ? 'paypal' : 'stripe';
        if ($state['payment_method']['provider'] === 'paypal' && !$state['payment_method']['paypal_email']) {
            $state['payment_method']['paypal_email'] = 'billing@cityschool.edu';
        }
        $state['storage_limit_gb'] = self::SUBSCRIPTION_PRICES[$state['current_plan']]['storage_limit_gb'] ?? $state['storage_limit_gb'];
        $state['usage_percent'] = (int) round(($state['storage_used_gb'] / max(1, $state['storage_limit_gb'])) * 100);
        $state['next_payment_amount'] = self::SUBSCRIPTION_PRICES[$state['current_plan']][$state['billing_cycle']] ?? 0;
        $state['retry_schedule'] = is_array($state['retry_schedule']) ? $state['retry_schedule'] : [];
        $state['warnings'] = is_array($state['warnings']) ? $state['warnings'] : [];

        if ($state['grace_period_ends_at']) {
            $graceDaysRemaining = (int) (new \DateTimeImmutable())->setTime(0, 0)->diff((new \DateTimeImmutable($state['grace_period_ends_at']))->setTime(0, 0))->format('%r%a');
            $state['grace_days_remaining'] = $graceDaysRemaining;
        } else {
            $state['grace_days_remaining'] = null;
        }

        $state = $this->evaluateBillingLifecycle($state);

        return $state;
    }

    private function saveBillingSubscriptionState(Request $request, array $state): void
    {
        $request->getSession()->set('billing_subscription_state', $state);

        $portalContext = $request->getSession()->get('school_portal_context', []);
        if (is_array($portalContext)) {
            $portalContext['plan'] = $state['current_plan'];
            $portalContext['billing_cycle'] = $state['billing_cycle'];
            $portalContext['status'] = $state['status'];
            $request->getSession()->set('school_portal_context', $portalContext);
        }
    }

    private function getPlatformInvoices(array $billingState): array
    {
        return [
            [
                'ref' => $billingState['last_invoice_ref'],
                'type' => 'Subscription Invoice',
                'amount' => $billingState['next_payment_amount'],
                'issued_on' => '2026-05-01',
                'status' => 'paid',
            ],
            [
                'ref' => 'SaaS-INV-2026-0401',
                'type' => 'Subscription Invoice',
                'amount' => $billingState['next_payment_amount'],
                'issued_on' => '2026-04-01',
                'status' => 'paid',
            ],
        ];
    }

    private function getPlanCatalog(array $billingState): array
    {
        $currentAmount = self::SUBSCRIPTION_PRICES[$billingState['current_plan']][$billingState['billing_cycle']] ?? 0;
        $catalog = [];

        foreach (self::SUBSCRIPTION_PRICES as $plan => $pricing) {
            $amount = $pricing[$billingState['billing_cycle']];
            $catalog[] = [
                'name' => $plan,
                'amount' => $amount,
                'storage_limit_gb' => $pricing['storage_limit_gb'],
                'students_limit' => match ($plan) {
                    'Basic' => 300,
                    'Pro' => 1200,
                    default => 'Unlimited',
                },
                'is_current' => $plan === $billingState['current_plan'],
                'delta' => $amount - $currentAmount,
                'change_type' => self::PLAN_ORDER[$plan] > self::PLAN_ORDER[$billingState['current_plan']] ? 'upgrade' : (self::PLAN_ORDER[$plan] < self::PLAN_ORDER[$billingState['current_plan']] ? 'downgrade' : 'current'),
            ];
        }

        return $catalog;
    }

    private function processBillingSubscriptionAction(Request $request, array $state): array
    {
        $action = (string) $request->request->get('action', '');

        if ($action === '') {
            return $state;
        }

        switch ($action) {
            case 'simulate_payment_failure':
                $state = $this->markPaymentFailed($state);
                $this->addFlash('error', 'Payment failed. Automatic retries scheduled and the admin has been alerted to update the payment method.');
                break;

            case 'retry_payment_now':
                if (($state['payment_failures'] ?? 0) <= 0) {
                    $this->addFlash('success', 'No failed payment is pending retry.');
                    break;
                }

                if (($state['payment_failures'] ?? 0) >= 3 && empty($state['payment_method_refresh_pending'])) {
                    $state['payment_health'] = 'grace_period';
                    $state['status'] = 'grace_period';
                    $state['feature_restriction'] = true;
                    $this->addFlash('error', 'Retry failed again. Grace period remains active until the payment method is updated.');
                    break;
                }

                $state['payment_health'] = 'healthy';
                $state['payment_failures'] = 0;
                $state['failed_invoice_ref'] = null;
                $state['latest_payment_failure_at'] = null;
                $state['grace_period_ends_at'] = null;
                $state['feature_restriction'] = false;
                $state['suspension_at'] = null;
                $state['retry_schedule'] = [];
                $state['warnings'] = [];
                $state['payment_method_refresh_pending'] = false;
                $state['status'] = 'active';
                $this->addFlash('success', 'Payment retry succeeded. Subscription access remains active and warnings were cleared.');
                break;

            case 'change_plan':
                $targetPlan = (string) $request->request->get('target_plan', $state['current_plan']);
                if (!isset(self::PLAN_ORDER[$targetPlan]) || $targetPlan === $state['current_plan']) {
                    $this->addFlash('success', 'Your subscription is already on that plan.');
                    break;
                }

                $currentAmount = self::SUBSCRIPTION_PRICES[$state['current_plan']][$state['billing_cycle']];
                $targetAmount = self::SUBSCRIPTION_PRICES[$targetPlan][$state['billing_cycle']];

                if (self::PLAN_ORDER[$targetPlan] > self::PLAN_ORDER[$state['current_plan']]) {
                    $state['current_plan'] = $targetPlan;
                    $state['pending_plan_change'] = null;
                    $state['pending_plan_effective_date'] = null;
                    $state['last_proration_amount'] = max(0, $targetAmount - $currentAmount);
                    $state['feature_unlocks'] = ['Advanced analytics', 'API access', 'Priority support'];
                    $state['storage_limit_gb'] = self::SUBSCRIPTION_PRICES[$targetPlan]['storage_limit_gb'];
                    $this->addFlash('success', sprintf('Plan upgraded to %s. Prorated billing applied: $%d. Features unlocked immediately.', $targetPlan, $state['last_proration_amount']));
                } else {
                    $state['pending_plan_change'] = $targetPlan;
                    $state['pending_plan_effective_date'] = $state['next_payment_date'];
                    $state['last_proration_amount'] = 0;
                    $this->addFlash('success', sprintf('Downgrade to %s scheduled for %s.', $targetPlan, $state['next_payment_date']));
                }
                break;

            case 'update_payment_method':
                $provider = (string) $request->request->get('payment_provider', 'stripe');
                if ($provider === 'paypal') {
                    $paypalEmail = trim((string) $request->request->get('paypal_email', ''));
                    if (!filter_var($paypalEmail, FILTER_VALIDATE_EMAIL)) {
                        $this->addFlash('error', 'Enter a valid PayPal email address.');
                        break;
                    }

                    $state['payment_method'] = [
                        'provider' => 'paypal',
                        'brand' => 'PayPal',
                        'last4' => 'N/A',
                        'exp' => 'N/A',
                        'paypal_email' => $paypalEmail,
                    ];
                } else {
                    $brand = trim((string) $request->request->get('card_brand', 'Visa')) ?: 'Visa';
                    $last4 = substr(preg_replace('/\D+/', '', (string) $request->request->get('card_number', '4242')), -4);
                    $exp = trim((string) $request->request->get('card_expiry', '08/28')) ?: '08/28';
                    $state['payment_method'] = [
                        'provider' => 'stripe',
                        'brand' => $brand,
                        'last4' => $last4 ?: '4242',
                        'exp' => $exp,
                        'paypal_email' => null,
                    ];
                }
                if (($state['payment_failures'] ?? 0) > 0) {
                    $state['payment_method_refresh_pending'] = true;
                    $state['warnings'][] = 'Payment method updated. Automatic retry is queued.';
                }
                if ($state['payment_method']['provider'] === 'paypal') {
                    $this->addFlash('success', sprintf('Payment method updated to PayPal (%s).', $state['payment_method']['paypal_email']));
                } else {
                    $this->addFlash('success', sprintf('Payment method updated to %s ending in %s.', $state['payment_method']['brand'], $state['payment_method']['last4']));
                }
                break;

            case 'toggle_renewal_mode':
                $mode = (string) $request->request->get('renewal_mode', 'auto');
                $state['renewal_mode'] = $mode === 'manual' ? 'manual' : 'auto';
                $providerLabel = ($state['payment_method']['provider'] ?? 'stripe') === 'paypal' ? 'PayPal' : 'Stripe';
                $state['renewal_notice'] = $state['renewal_mode'] === 'manual'
                    ? 'Manual renewal enabled. The system will notify you before expiry so you can click Renew Now.'
                    : sprintf('Auto renewal enabled. %s will charge automatically and send the invoice confirmation.', $providerLabel);
                $this->addFlash('success', $state['renewal_notice']);
                break;

            case 'renew_now':
                $interval = $state['billing_cycle'] === 'yearly' ? '+1 year' : '+1 month';
                $state['next_payment_date'] = (new \DateTimeImmutable($state['next_payment_date']))->modify($interval)->format('Y-m-d');
                $state['last_invoice_ref'] = 'SaaS-INV-' . (new \DateTimeImmutable())->format('Y-m') . '-RNW';
                $state['status'] = 'active';
                $state['payment_health'] = 'healthy';
                $state['payment_failures'] = 0;
                $state['feature_restriction'] = false;
                $state['grace_period_ends_at'] = null;
                $state['warnings'] = [];
                if ($state['pending_plan_change']) {
                    $state['current_plan'] = $state['pending_plan_change'];
                    $state['storage_limit_gb'] = self::SUBSCRIPTION_PRICES[$state['current_plan']]['storage_limit_gb'];
                    $state['pending_plan_change'] = null;
                    $state['pending_plan_effective_date'] = null;
                }
                $this->addFlash('success', 'Renewal completed. Invoice generated and confirmation queued.');
                break;

            case 'download_invoice':
                $this->addFlash('success', sprintf('Invoice %s is ready for download.', (string) $request->request->get('invoice_ref', $state['last_invoice_ref'])));
                break;

            case 'cancel_subscription':
                $mode = (string) $request->request->get('cancel_mode', 'period_end');
                $feedback = trim((string) $request->request->get('cancel_feedback', ''));
                $exportRequested = $request->request->getBoolean('export_data');
                $offerDowngrade = $request->request->getBoolean('offer_downgrade');

                if ($offerDowngrade && $state['current_plan'] !== 'Basic') {
                    $state['pending_plan_change'] = $state['downgrade_offer_plan'];
                    $state['pending_plan_effective_date'] = $state['next_payment_date'];
                    $state['cancellation_requested'] = false;
                    $state['cancellation_mode'] = null;
                    $state['cancellation_effective_date'] = null;
                    $state['cancellation_feedback'] = $feedback ?: 'Selected downgrade offer instead of cancellation.';
                    $this->addFlash('success', sprintf('Downgrade offer accepted. Plan will move to %s on %s.', $state['downgrade_offer_plan'], $state['next_payment_date']));
                    break;
                }

                $state['cancellation_requested'] = true;
                $state['cancellation_mode'] = $mode === 'immediate' ? 'immediate' : 'period_end';
                $state['cancellation_feedback'] = $feedback ?: null;
                $state['data_export_ready'] = $exportRequested;
                $state['pending_plan_change'] = null;
                $state['pending_plan_effective_date'] = null;

                if ($state['cancellation_mode'] === 'immediate') {
                    $state['status'] = 'cancelled';
                    $state['cancellation_effective_date'] = (new \DateTimeImmutable())->format('Y-m-d');
                    $state['feature_restriction'] = true;
                    $this->addFlash('success', 'Subscription cancelled immediately. Data export is being prepared before access is fully locked.');
                } else {
                    $state['status'] = 'cancelling';
                    $state['cancellation_effective_date'] = $state['next_payment_date'];
                    $this->addFlash('success', sprintf('Subscription will end on %s. Access remains available until then.', $state['next_payment_date']));
                }
                break;
        }

        $state['usage_percent'] = (int) round(($state['storage_used_gb'] / max(1, $state['storage_limit_gb'])) * 100);
        $state['next_payment_amount'] = self::SUBSCRIPTION_PRICES[$state['current_plan']][$state['billing_cycle']] ?? 0;

        return $state;
    }

    private function markPaymentFailed(array $state): array
    {
        $attempts = (int) ($state['payment_failures'] ?? 0) + 1;
        $failedAt = new \DateTimeImmutable();

        $state['payment_failures'] = $attempts;
        $state['latest_payment_failure_at'] = $failedAt->format(DATE_ATOM);
        $state['failed_invoice_ref'] = 'SaaS-INV-' . $failedAt->format('Y-m') . '-FAIL';
        $state['retry_schedule'] = array_map(
            fn(string $offset) => $failedAt->modify($offset)->format('Y-m-d H:i'),
            self::PAYMENT_RETRY_OFFSETS
        );
        $state['warnings'] = [
            'Payment failed. We will retry automatically at different times before restricting access.',
            'Update the payment method to avoid service interruption.',
        ];

        if ($attempts >= 3) {
            $state['payment_health'] = 'grace_period';
            $state['status'] = 'grace_period';
            $state['grace_period_ends_at'] = $failedAt->modify('+7 days')->format('Y-m-d');
            $state['feature_restriction'] = true;
            $state['warnings'][] = 'Grace period started. Core access remains available temporarily, but premium features are restricted.';
        } else {
            $state['payment_health'] = 'retrying';
        }

        return $state;
    }

    private function evaluateBillingLifecycle(array $state): array
    {
        if (($state['payment_health'] ?? 'healthy') !== 'grace_period' || empty($state['grace_period_ends_at'])) {
            return $state;
        }

        $graceEndsAt = new \DateTimeImmutable($state['grace_period_ends_at']);
        if ($graceEndsAt >= new \DateTimeImmutable('today')) {
            return $state;
        }

        $state['payment_health'] = 'suspended';
        $state['status'] = 'suspended';
        $state['feature_restriction'] = true;
        $state['suspension_at'] = (new \DateTimeImmutable())->format('Y-m-d');
        $state['warnings'][] = 'Grace period ended. Account is now suspended until payment is recovered.';

        return $state;
    }

    private function getUsers(): array
    {
        return [
            ['id' => 1,  'name' => 'Alice Johnson',         'username' => 'alice',         'email' => 'alice@school.edu',      'role' => 'ROLE_STUDENT', 'class' => 'Grade 10-A',     'status' => 'active',   'joined' => '2024-09-01'],
            ['id' => 2,  'name' => 'Bob Smith',              'username' => 'bob',           'email' => 'bob@school.edu',        'role' => 'ROLE_STUDENT', 'class' => 'Grade 10-B',     'status' => 'active',   'joined' => '2024-09-01'],
            ['id' => 3,  'name' => 'Carol White',            'username' => 'carol',         'email' => 'carol@school.edu',      'role' => 'ROLE_STUDENT', 'class' => 'Grade 11-A',     'status' => 'active',   'joined' => '2023-09-01'],
            ['id' => 4,  'name' => 'David Brown',            'username' => 'david',         'email' => 'david@school.edu',      'role' => 'ROLE_STUDENT', 'class' => 'Grade 9-C',      'status' => 'inactive', 'joined' => '2025-09-01'],
            ['id' => 5,  'name' => 'Emma Davis',             'username' => 'emma',          'email' => 'emma@school.edu',       'role' => 'ROLE_STUDENT', 'class' => 'Grade 12-A',     'status' => 'active',   'joined' => '2022-09-01'],
            ['id' => 6,  'name' => 'Mr. James Smith',        'username' => 'mrsmith',       'email' => 'j.smith@school.edu',    'role' => 'ROLE_TEACHER', 'class' => 'Mathematics',    'status' => 'active',   'joined' => '2018-09-01'],
            ['id' => 7,  'name' => 'Ms. Sarah Lee',          'username' => 'sarahlee',      'email' => 's.lee@school.edu',      'role' => 'ROLE_TEACHER', 'class' => 'Science',        'status' => 'active',   'joined' => '2019-01-15'],
            ['id' => 8,  'name' => 'Mr. Tom Clark',          'username' => 'tomclark',      'email' => 't.clark@school.edu',    'role' => 'ROLE_TEACHER', 'class' => 'English',        'status' => 'active',   'joined' => '2020-08-20'],
            ['id' => 9,  'name' => 'Mrs. Johnson (Parent)',  'username' => 'parentjohnson', 'email' => 'p.johnson@gmail.com',   'role' => 'ROLE_PARENT',  'class' => 'Alice Johnson',  'status' => 'active',   'joined' => '2024-09-05'],
            ['id' => 10, 'name' => 'Mr. Smith Sr. (Parent)', 'username' => 'parentsmith',   'email' => 'b.smith@gmail.com',     'role' => 'ROLE_PARENT',  'class' => 'Bob Smith',      'status' => 'active',   'joined' => '2024-09-05'],
        ];
    }

    private function getClasses(): array
    {
        return [
            ['id' => 1, 'name' => 'Grade 9-A',  'teacher' => 'Ms. Sarah Lee',   'students' => 28, 'subjects' => 7, 'room' => 'Room 101', 'status' => 'active'],
            ['id' => 2, 'name' => 'Grade 9-B',  'teacher' => 'Mr. Tom Clark',   'students' => 25, 'subjects' => 7, 'room' => 'Room 102', 'status' => 'active'],
            ['id' => 3, 'name' => 'Grade 9-C',  'teacher' => 'Mr. James Smith', 'students' => 27, 'subjects' => 7, 'room' => 'Room 103', 'status' => 'active'],
            ['id' => 4, 'name' => 'Grade 10-A', 'teacher' => 'Mr. James Smith', 'students' => 30, 'subjects' => 8, 'room' => 'Room 201', 'status' => 'active'],
            ['id' => 5, 'name' => 'Grade 10-B', 'teacher' => 'Ms. Sarah Lee',   'students' => 29, 'subjects' => 8, 'room' => 'Room 202', 'status' => 'active'],
            ['id' => 6, 'name' => 'Grade 11-A', 'teacher' => 'Mr. Tom Clark',   'students' => 26, 'subjects' => 9, 'room' => 'Room 301', 'status' => 'active'],
            ['id' => 7, 'name' => 'Grade 12-A', 'teacher' => 'Mr. James Smith', 'students' => 22, 'subjects' => 9, 'room' => 'Room 401', 'status' => 'active'],
        ];
    }

    private function getSubjects(): array
    {
        return [
            ['id' => 1, 'name' => 'Mathematics',        'code' => 'MATH-101', 'teacher' => 'Mr. James Smith', 'classes' => 4, 'students' => 110, 'credits' => 4, 'color' => '#7c3aed'],
            ['id' => 2, 'name' => 'Physics',            'code' => 'PHY-101',  'teacher' => 'Mr. James Smith', 'classes' => 3, 'students' => 80,  'credits' => 3, 'color' => '#0891b2'],
            ['id' => 3, 'name' => 'Chemistry',          'code' => 'CHEM-101', 'teacher' => 'Ms. Sarah Lee',   'classes' => 3, 'students' => 75,  'credits' => 3, 'color' => '#22c55e'],
            ['id' => 4, 'name' => 'Biology',            'code' => 'BIO-101',  'teacher' => 'Ms. Sarah Lee',   'classes' => 3, 'students' => 82,  'credits' => 3, 'color' => '#f59e0b'],
            ['id' => 5, 'name' => 'English Literature', 'code' => 'ENG-101',  'teacher' => 'Mr. Tom Clark',   'classes' => 4, 'students' => 115, 'credits' => 3, 'color' => '#ef4444'],
            ['id' => 6, 'name' => 'History',            'code' => 'HIST-101', 'teacher' => 'Mr. Tom Clark',   'classes' => 3, 'students' => 78,  'credits' => 2, 'color' => '#8b5cf6'],
            ['id' => 7, 'name' => 'Computer Science',   'code' => 'CS-101',   'teacher' => 'Mr. James Smith', 'classes' => 2, 'students' => 45,  'credits' => 3, 'color' => '#06b6d4'],
            ['id' => 8, 'name' => 'Physical Education', 'code' => 'PE-101',   'teacher' => 'Ms. Sarah Lee',   'classes' => 7, 'students' => 187, 'credits' => 1, 'color' => '#84cc16'],
        ];
    }

    private function getSchedules(): array
    {
        return [
            ['id' => 1, 'subject' => 'Mathematics',        'class' => 'Grade 10-A', 'teacher' => 'Mr. James Smith', 'day' => 'Monday',    'time_start' => '08:00', 'time_end' => '09:30', 'room' => 'Room 201',     'color' => '#7c3aed'],
            ['id' => 2, 'subject' => 'Physics',            'class' => 'Grade 10-A', 'teacher' => 'Mr. James Smith', 'day' => 'Monday',    'time_start' => '10:00', 'time_end' => '11:30', 'room' => 'Lab 1',        'color' => '#0891b2'],
            ['id' => 3, 'subject' => 'English Literature', 'class' => 'Grade 10-A', 'teacher' => 'Mr. Tom Clark',   'day' => 'Tuesday',   'time_start' => '08:00', 'time_end' => '09:30', 'room' => 'Room 201',     'color' => '#ef4444'],
            ['id' => 4, 'subject' => 'Chemistry',          'class' => 'Grade 11-A', 'teacher' => 'Ms. Sarah Lee',   'day' => 'Tuesday',   'time_start' => '10:00', 'time_end' => '11:30', 'room' => 'Lab 2',        'color' => '#22c55e'],
            ['id' => 5, 'subject' => 'History',            'class' => 'Grade 9-A',  'teacher' => 'Mr. Tom Clark',   'day' => 'Wednesday', 'time_start' => '08:00', 'time_end' => '09:30', 'room' => 'Room 104',     'color' => '#8b5cf6'],
            ['id' => 6, 'subject' => 'Computer Science',   'class' => 'Grade 12-A', 'teacher' => 'Mr. James Smith', 'day' => 'Wednesday', 'time_start' => '13:00', 'time_end' => '14:30', 'room' => 'Computer Lab', 'color' => '#06b6d4'],
            ['id' => 7, 'subject' => 'Biology',            'class' => 'Grade 10-B', 'teacher' => 'Ms. Sarah Lee',   'day' => 'Thursday',  'time_start' => '08:00', 'time_end' => '09:30', 'room' => 'Lab 3',        'color' => '#f59e0b'],
            ['id' => 8, 'subject' => 'Physical Education', 'class' => 'Grade 9-B',  'teacher' => 'Ms. Sarah Lee',   'day' => 'Friday',    'time_start' => '10:00', 'time_end' => '11:30', 'room' => 'Gym',          'color' => '#84cc16'],
        ];
    }

    private function getTerms(): array
    {
        return [
            ['id' => 1, 'name' => 'Fall 2025',   'start' => '2025-09-01', 'end' => '2025-12-20', 'status' => 'completed', 'weeks' => 16],
            ['id' => 2, 'name' => 'Spring 2026', 'start' => '2026-01-15', 'end' => '2026-05-30', 'status' => 'active',    'weeks' => 19],
            ['id' => 3, 'name' => 'Summer 2026', 'start' => '2026-06-15', 'end' => '2026-08-15', 'status' => 'upcoming',  'weeks' => 9],
            ['id' => 4, 'name' => 'Fall 2026',   'start' => '2026-09-01', 'end' => '2026-12-20', 'status' => 'upcoming',  'weeks' => 16],
        ];
    }

    private function getAcademicYears(): array
    {
        return [
            ['name' => '2025-2026', 'term_model' => 'Semester', 'terms' => ['Fall 2025', 'Spring 2026', 'Summer 2026'], 'status' => 'active'],
            ['name' => '2026-2027', 'term_model' => 'Trimester', 'terms' => ['Term 1', 'Term 2', 'Term 3'], 'status' => 'draft'],
        ];
    }

    private function getGradingSystems(): array
    {
        return [
            ['name' => 'Letter (A-F)', 'scale' => 'A, B, C, D, E, F', 'pass_mark' => 'D', 'status' => 'active'],
            ['name' => 'Percentage', 'scale' => '0-100%', 'pass_mark' => '50%', 'status' => 'active'],
            ['name' => 'GPA 4.0', 'scale' => '0.0-4.0', 'pass_mark' => '2.0', 'status' => 'draft'],
        ];
    }

    private function getCustomGradingModels(): array
    {
        return [
            ['school' => 'City School Main Campus', 'model' => 'Weighted 40/60 (CA/Exam)', 'scope' => 'All Grades'],
            ['school' => 'City School STEM Branch', 'model' => 'Competency Bands (E-M-A)', 'scope' => 'Grades 9-10'],
        ];
    }

    private function getCurriculumTemplates(): array
    {
        return [
            ['name' => 'Lower Secondary Core', 'grades' => '7-9', 'subjects' => 8, 'last_used' => '2026-01-12'],
            ['name' => 'Upper Secondary Science', 'grades' => '10-12', 'subjects' => 10, 'last_used' => '2026-03-07'],
        ];
    }

    private function getMultilingualCourses(): array
    {
        return [
            ['course' => 'Mathematics', 'languages' => ['English', 'French', 'Arabic']],
            ['course' => 'History', 'languages' => ['English', 'French']],
            ['course' => 'Biology', 'languages' => ['English', 'Arabic']],
        ];
    }

    private function getRooms(): array
    {
        return [
            ['name' => 'Room 101', 'type' => 'Classroom', 'capacity' => 35],
            ['name' => 'Room 201', 'type' => 'Classroom', 'capacity' => 35],
            ['name' => 'Lab 1', 'type' => 'Science Lab', 'capacity' => 28],
            ['name' => 'Lab 2', 'type' => 'Science Lab', 'capacity' => 28],
            ['name' => 'Computer Lab', 'type' => 'ICT Lab', 'capacity' => 30],
            ['name' => 'Gym', 'type' => 'Sports Hall', 'capacity' => 120],
        ];
    }

    private function getScheduleConflicts(): array
    {
        return [
            ['type' => 'Teacher Overlap', 'detail' => 'Mr. James Smith is assigned to Grade 10-A and Grade 12-A on Monday 08:00.', 'severity' => 'warning'],
            ['type' => 'Room Overlap', 'detail' => 'Lab 1 is allocated to Physics and Chemistry on Tuesday 10:00.', 'severity' => 'danger'],
        ];
    }

    private function getStudentAttendance(): array
    {
        return [
            ['student' => 'Alice Johnson', 'class' => 'Grade 10-A', 'present' => 84, 'total' => 90, 'rate' => 93, 'status' => 'good'],
            ['student' => 'Bob Smith', 'class' => 'Grade 10-B', 'present' => 72, 'total' => 90, 'rate' => 80, 'status' => 'warning'],
            ['student' => 'Carol White', 'class' => 'Grade 11-A', 'present' => 86, 'total' => 90, 'rate' => 96, 'status' => 'good'],
            ['student' => 'David Brown', 'class' => 'Grade 9-C', 'present' => 58, 'total' => 90, 'rate' => 64, 'status' => 'critical'],
            ['student' => 'Emma Davis', 'class' => 'Grade 12-A', 'present' => 88, 'total' => 90, 'rate' => 98, 'status' => 'good'],
        ];
    }

    private function getTeacherAttendance(): array
    {
        return [
            ['teacher' => 'Mr. James Smith', 'department' => 'Mathematics', 'present' => 86, 'total' => 90, 'rate' => 96, 'status' => 'good'],
            ['teacher' => 'Ms. Sarah Lee', 'department' => 'Science', 'present' => 83, 'total' => 90, 'rate' => 92, 'status' => 'good'],
            ['teacher' => 'Mr. Tom Clark', 'department' => 'English', 'present' => 78, 'total' => 90, 'rate' => 87, 'status' => 'warning'],
        ];
    }

    private function getAbsenceRequests(): array
    {
        return [
            ['id' => 1, 'name' => 'Bob Smith', 'type' => 'Student', 'from' => '2026-04-29', 'to' => '2026-05-01', 'reason' => 'Medical leave', 'status' => 'pending'],
            ['id' => 2, 'name' => 'Mr. Tom Clark', 'type' => 'Teacher', 'from' => '2026-05-03', 'to' => '2026-05-03', 'reason' => 'Personal emergency', 'status' => 'pending'],
            ['id' => 3, 'name' => 'Alice Johnson', 'type' => 'Student', 'from' => '2026-04-20', 'to' => '2026-04-20', 'reason' => 'Family event', 'status' => 'approved'],
        ];
    }

    private function getAttendanceRules(): array
    {
        return [
            ['name' => 'Student Warning Threshold', 'value' => 'Below 85%', 'scope' => 'Students', 'status' => 'active'],
            ['name' => 'Student Critical Threshold', 'value' => 'Below 75%', 'scope' => 'Students', 'status' => 'active'],
            ['name' => 'Teacher Warning Threshold', 'value' => 'Below 90%', 'scope' => 'Teachers', 'status' => 'active'],
        ];
    }

    private function getAttendanceAlerts(): array
    {
        return [
            ['audience' => 'Parent', 'target' => 'David Brown', 'message' => 'Attendance dropped to 64%', 'severity' => 'danger'],
            ['audience' => 'Admin', 'target' => 'Mr. Tom Clark', 'message' => 'Attendance reached warning level (87%)', 'severity' => 'warning'],
        ];
    }

    private function getExamConfigurations(): array
    {
        return [
            ['id' => 1, 'name' => 'Math Midterm', 'type' => 'Midterm', 'class' => 'Grade 10-A', 'date' => '2026-05-10', 'status' => 'scheduled'],
            ['id' => 2, 'name' => 'Science Quiz 4', 'type' => 'Quiz', 'class' => 'Grade 10-B', 'date' => '2026-05-08', 'status' => 'scheduled'],
            ['id' => 3, 'name' => 'English Final', 'type' => 'Final', 'class' => 'Grade 11-A', 'date' => '2026-05-28', 'status' => 'draft'],
        ];
    }

    private function getGradingPolicies(): array
    {
        return [
            ['name' => 'Midterm Weight', 'value' => '40%', 'status' => 'active'],
            ['name' => 'Final Weight', 'value' => '50%', 'status' => 'active'],
            ['name' => 'Quiz/CA Weight', 'value' => '10%', 'status' => 'active'],
            ['name' => 'Passing Grade', 'value' => '50%', 'status' => 'active'],
        ];
    }

    private function getStudentGradeReports(): array
    {
        return [
            ['student' => 'Alice Johnson', 'class' => 'Grade 10-A', 'avg' => 88, 'status' => 'approved'],
            ['student' => 'Bob Smith', 'class' => 'Grade 10-B', 'avg' => 72, 'status' => 'pending'],
            ['student' => 'Carol White', 'class' => 'Grade 11-A', 'avg' => 91, 'status' => 'approved'],
            ['student' => 'David Brown', 'class' => 'Grade 9-C', 'avg' => 61, 'status' => 'pending'],
        ];
    }

    private function getClassPerformanceReports(): array
    {
        return [
            ['class' => 'Grade 10-A', 'average' => 84, 'pass_rate' => 93],
            ['class' => 'Grade 10-B', 'average' => 78, 'pass_rate' => 86],
            ['class' => 'Grade 11-A', 'average' => 87, 'pass_rate' => 95],
        ];
    }

    private function getTrendSeries(): array
    {
        return [
            ['period' => 'Jan', 'class_avg' => 75, 'school_avg' => 73],
            ['period' => 'Feb', 'class_avg' => 77, 'school_avg' => 74],
            ['period' => 'Mar', 'class_avg' => 79, 'school_avg' => 75],
            ['period' => 'Apr', 'class_avg' => 82, 'school_avg' => 77],
            ['period' => 'May', 'class_avg' => 84, 'school_avg' => 78],
        ];
    }

    private function getTeacherCourses(): array
    {
        return [
            ['id' => 1, 'course' => 'Mathematics - Algebra II', 'teacher' => 'Mr. James Smith', 'class' => 'Grade 10-A', 'compliance' => 'compliant', 'materials' => 12, 'last_updated' => '2026-05-01', 'status' => 'active'],
            ['id' => 2, 'course' => 'Physics Fundamentals', 'teacher' => 'Ms. Sarah Lee', 'class' => 'Grade 10-B', 'compliance' => 'review', 'materials' => 9, 'last_updated' => '2026-04-29', 'status' => 'active'],
            ['id' => 3, 'course' => 'English Composition', 'teacher' => 'Mr. Tom Clark', 'class' => 'Grade 11-A', 'compliance' => 'compliant', 'materials' => 14, 'last_updated' => '2026-04-28', 'status' => 'active'],
            ['id' => 4, 'course' => 'History Essentials 2024', 'teacher' => 'Mr. Tom Clark', 'class' => 'Grade 9-A', 'compliance' => 'archive-candidate', 'materials' => 7, 'last_updated' => '2025-08-10', 'status' => 'legacy'],
        ];
    }

    private function getUploadedMaterials(): array
    {
        return [
            ['id' => 1, 'title' => 'Algebra Unit 5 Worksheets', 'course' => 'Mathematics - Algebra II', 'teacher' => 'Mr. James Smith', 'type' => 'Worksheet', 'status' => 'pending'],
            ['id' => 2, 'title' => 'Physics Lab Safety Slides', 'course' => 'Physics Fundamentals', 'teacher' => 'Ms. Sarah Lee', 'type' => 'Slides', 'status' => 'pending'],
            ['id' => 3, 'title' => 'Essay Rubric Template', 'course' => 'English Composition', 'teacher' => 'Mr. Tom Clark', 'type' => 'Rubric', 'status' => 'approved'],
        ];
    }

    private function getCurriculumComplianceChecks(): array
    {
        return [
            ['course' => 'Mathematics - Algebra II', 'framework' => 'National STEM v3', 'coverage' => 96, 'status' => 'compliant'],
            ['course' => 'Physics Fundamentals', 'framework' => 'National STEM v3', 'coverage' => 81, 'status' => 'review'],
            ['course' => 'English Composition', 'framework' => 'Language Arts v2', 'coverage' => 92, 'status' => 'compliant'],
        ];
    }

    private function getArchivedCourses(): array
    {
        return [
            ['course' => 'History Essentials 2023', 'teacher' => 'Mr. Tom Clark', 'archived_on' => '2025-07-01'],
            ['course' => 'Biology Intro 2023', 'teacher' => 'Ms. Sarah Lee', 'archived_on' => '2025-07-01'],
        ];
    }

    private function getContentQualityScores(): array
    {
        return [
            ['course' => 'Mathematics - Algebra II', 'score' => 92, 'quality' => 'excellent'],
            ['course' => 'Physics Fundamentals', 'score' => 76, 'quality' => 'needs-improvement'],
            ['course' => 'English Composition', 'score' => 88, 'quality' => 'good'],
        ];
    }

    private function getCentralResourceLibrary(): array
    {
        return [
            ['resource' => 'STEM Assessment Rubric Pack', 'category' => 'Assessment', 'owner' => 'Academic Office', 'downloads' => 54],
            ['resource' => 'Parent Communication Templates', 'category' => 'Communication', 'owner' => 'Admin Office', 'downloads' => 37],
            ['resource' => 'Inclusive Teaching Slides', 'category' => 'Pedagogy', 'owner' => 'Teacher Excellence Team', 'downloads' => 41],
        ];
    }

    private function getMessageSchedules(): array
    {
        return [
            ['title' => 'Exam Week Reminder', 'target' => 'students', 'channel' => 'app', 'send_at' => '2026-05-08 07:00', 'status' => 'scheduled'],
            ['title' => 'Fee Due Notice', 'target' => 'parents', 'channel' => 'email', 'send_at' => '2026-05-10 09:00', 'status' => 'scheduled'],
            ['title' => 'Staff PD Session', 'target' => 'staff', 'channel' => 'app', 'send_at' => '2026-05-06 08:00', 'status' => 'queued'],
        ];
    }

    private function getMessagingPolicies(): array
    {
        return [
            ['name' => 'Quiet Hours', 'rule' => 'No non-emergency messages after 21:00', 'status' => 'active'],
            ['name' => 'Escalation Window', 'rule' => 'Unread emergency alerts escalate after 10 minutes', 'status' => 'active'],
            ['name' => 'Parent Consent', 'rule' => 'SMS allowed only for opted-in guardians', 'status' => 'active'],
        ];
    }

    private function getNotificationTracking(): array
    {
        return [
            ['message' => 'Exam Week Reminder', 'target' => 'Grade 10', 'delivered' => 210, 'read' => 178],
            ['message' => 'Fee Due Notice', 'target' => 'Parents Group A', 'delivered' => 132, 'read' => 101],
            ['message' => 'Emergency Drill Alert', 'target' => 'All', 'delivered' => 624, 'read' => 602],
        ];
    }

    private function getPayments(): array
    {
        return [
            ['id' => 1, 'student' => 'Alice Johnson', 'class' => 'Grade 10-A', 'amount' => 1500, 'type' => 'Tuition Fee', 'status' => 'paid',    'date' => '2026-01-10', 'method' => 'Bank Transfer'],
            ['id' => 2, 'student' => 'Bob Smith',     'class' => 'Grade 10-B', 'amount' => 1500, 'type' => 'Tuition Fee', 'status' => 'paid',    'date' => '2026-01-12', 'method' => 'Credit Card'],
            ['id' => 3, 'student' => 'Carol White',   'class' => 'Grade 11-A', 'amount' => 1800, 'type' => 'Tuition Fee', 'status' => 'pending', 'date' => '2026-01-15', 'method' => '—'],
            ['id' => 4, 'student' => 'David Brown',   'class' => 'Grade 9-C',  'amount' => 1200, 'type' => 'Tuition Fee', 'status' => 'overdue', 'date' => '2025-12-01', 'method' => '—'],
            ['id' => 5, 'student' => 'Emma Davis',    'class' => 'Grade 12-A', 'amount' => 1800, 'type' => 'Tuition Fee', 'status' => 'paid',    'date' => '2026-01-08', 'method' => 'Online'],
            ['id' => 6, 'student' => 'Alice Johnson', 'class' => 'Grade 10-A', 'amount' => 150,  'type' => 'Lab Fee',     'status' => 'paid',    'date' => '2026-02-01', 'method' => 'Cash'],
            ['id' => 7, 'student' => 'Bob Smith',     'class' => 'Grade 10-B', 'amount' => 150,  'type' => 'Lab Fee',     'status' => 'pending', 'date' => '2026-02-01', 'method' => '—'],
        ];
    }

    private function getFeeStructures(): array
    {
        return [
            ['name' => 'Tuition Fee', 'amount' => 1500, 'frequency' => 'Per Term', 'grade_scope' => 'Grades 9-10', 'status' => 'active'],
            ['name' => 'Senior Tuition Fee', 'amount' => 1800, 'frequency' => 'Per Term', 'grade_scope' => 'Grades 11-12', 'status' => 'active'],
            ['name' => 'Lab Fee', 'amount' => 150, 'frequency' => 'Per Term', 'grade_scope' => 'Science Streams', 'status' => 'active'],
            ['name' => 'Transport Fee', 'amount' => 220, 'frequency' => 'Monthly', 'grade_scope' => 'Optional', 'status' => 'draft'],
        ];
    }

    private function getAssignedFees(): array
    {
        return [
            ['student' => 'Alice Johnson', 'class' => 'Grade 10-A', 'fee' => 'Tuition Fee', 'amount' => 1500, 'due_date' => '2026-05-20', 'status' => 'assigned'],
            ['student' => 'Bob Smith', 'class' => 'Grade 10-B', 'fee' => 'Tuition Fee', 'amount' => 1500, 'due_date' => '2026-05-20', 'status' => 'assigned'],
            ['student' => 'Emma Davis', 'class' => 'Grade 12-A', 'fee' => 'Senior Tuition Fee', 'amount' => 1800, 'due_date' => '2026-05-20', 'status' => 'assigned'],
        ];
    }

    private function getInvoicesAndReceipts(): array
    {
        return [
            ['ref' => 'INV-2026-0101', 'student' => 'Alice Johnson', 'type' => 'Invoice', 'amount' => 1500, 'issued_on' => '2026-05-01', 'status' => 'sent'],
            ['ref' => 'RCP-2026-0032', 'student' => 'Alice Johnson', 'type' => 'Receipt', 'amount' => 1500, 'issued_on' => '2026-05-03', 'status' => 'paid'],
            ['ref' => 'INV-2026-0110', 'student' => 'Bob Smith', 'type' => 'Invoice', 'amount' => 1500, 'issued_on' => '2026-05-01', 'status' => 'pending'],
        ];
    }

    private function getDiscountScholarships(): array
    {
        return [
            ['student' => 'Carol White', 'program' => 'Academic Merit Scholarship', 'type' => 'Scholarship', 'value' => '25%', 'status' => 'active'],
            ['student' => 'David Brown', 'program' => 'Need-Based Support', 'type' => 'Discount', 'value' => '15%', 'status' => 'active'],
            ['student' => 'Emma Davis', 'program' => 'Sibling Discount', 'type' => 'Discount', 'value' => '10%', 'status' => 'active'],
        ];
    }

    private function getPaymentReminderQueue(): array
    {
        return [
            ['student' => 'Bob Smith', 'channel' => 'Email + SMS', 'next_send' => '2026-05-06 09:00', 'status' => 'queued'],
            ['student' => 'David Brown', 'channel' => 'Email', 'next_send' => '2026-05-06 09:05', 'status' => 'queued'],
        ];
    }

    private function getFinancialForecasting(): array
    {
        return [
            ['period' => 'Jun 2026', 'expected_revenue' => 42000, 'expected_collection' => 38500, 'confidence' => 86],
            ['period' => 'Jul 2026', 'expected_revenue' => 43800, 'expected_collection' => 40200, 'confidence' => 84],
            ['period' => 'Aug 2026', 'expected_revenue' => 44700, 'expected_collection' => 41100, 'confidence' => 82],
        ];
    }

    private function getSystemUsageStats(): array
    {
        return [
            ['module' => 'Student Portal', 'daily_active' => 412, 'sessions' => 1360, 'avg_minutes' => 14],
            ['module' => 'Parent Portal', 'daily_active' => 268, 'sessions' => 720, 'avg_minutes' => 9],
            ['module' => 'Teacher Portal', 'daily_active' => 54, 'sessions' => 210, 'avg_minutes' => 22],
            ['module' => 'Admin Portal', 'daily_active' => 7, 'sessions' => 46, 'avg_minutes' => 31],
        ];
    }

    private function getAtRiskStudents(): array
    {
        return [
            ['student' => 'David Brown', 'class' => 'Grade 9-C', 'attendance' => 64, 'avg_grade' => 61, 'engagement' => 49, 'risk' => 'high'],
            ['student' => 'Bob Smith', 'class' => 'Grade 10-B', 'attendance' => 80, 'avg_grade' => 72, 'engagement' => 63, 'risk' => 'medium'],
            ['student' => 'Lina Ahmed', 'class' => 'Grade 11-A', 'attendance' => 83, 'avg_grade' => 69, 'engagement' => 58, 'risk' => 'medium'],
        ];
    }

    private function getCustomReportBlocks(): array
    {
        return [
            ['name' => 'Attendance by Grade', 'category' => 'Attendance', 'selected' => true],
            ['name' => 'Assessment Performance', 'category' => 'Grades', 'selected' => true],
            ['name' => 'Engagement Heatmap', 'category' => 'Engagement', 'selected' => false],
            ['name' => 'System Usage Detail', 'category' => 'Usage', 'selected' => true],
        ];
    }

    private function getPermissionMatrix(): array
    {
        return [
            ['role' => 'ROLE_ADMIN', 'users' => 2, 'permissions' => ['users.manage', 'fees.manage', 'reports.export', 'security.manage']],
            ['role' => 'ROLE_TEACHER', 'users' => 3, 'permissions' => ['attendance.mark', 'grades.update', 'content.upload']],
            ['role' => 'ROLE_PARENT', 'users' => 2, 'permissions' => ['student.view', 'messages.read', 'payments.view']],
            ['role' => 'ROLE_STUDENT', 'users' => 5, 'permissions' => ['grades.view', 'assignments.submit', 'announcements.read']],
        ];
    }

    private function getSensitiveDataPolicies(): array
    {
        return [
            ['name' => 'Financial Records', 'access_level' => 'Admin + Finance', 'protection' => 'masked unless elevated', 'status' => 'active'],
            ['name' => 'Medical / Special Needs', 'access_level' => 'Admin + Counselor', 'protection' => 'restricted view', 'status' => 'active'],
            ['name' => 'Disciplinary Reports', 'access_level' => 'Admin only', 'protection' => 'full restriction', 'status' => 'active'],
        ];
    }

    private function getCustomRoles(): array
    {
        return [
            ['name' => 'Exam Coordinator', 'scope' => 'Exams & Invigilation', 'status' => 'active'],
            ['name' => 'Finance Reviewer', 'scope' => 'Fees & Receipts', 'status' => 'active'],
            ['name' => 'Content Auditor', 'scope' => 'Course Compliance', 'status' => 'draft'],
        ];
    }

    private function getLoginPolicies(): array
    {
        return [
            ['name' => 'MFA Required for Admins', 'rule' => '2FA mandatory for ROLE_ADMIN', 'status' => 'active'],
            ['name' => 'Password Rotation', 'rule' => 'Change every 90 days', 'status' => 'active'],
            ['name' => 'Session Timeout', 'rule' => 'Auto logout after 60 minutes', 'status' => 'active'],
            ['name' => 'Geo/IP Restriction', 'rule' => 'Admin login limited to approved IP ranges', 'status' => 'draft'],
        ];
    }

    private function getFineGrainedPermissions(): array
    {
        return [
            ['feature' => 'Grades', 'action' => 'approve_final_results', 'granted_to' => 'Exam Coordinator'],
            ['feature' => 'Finance', 'action' => 'generate_receipt', 'granted_to' => 'Finance Reviewer'],
            ['feature' => 'Courses', 'action' => 'approve_material', 'granted_to' => 'Content Auditor'],
        ];
    }

    private function getTemporaryAccessAssignments(): array
    {
        return [
            ['user' => 'Ms. Sarah Lee', 'role' => 'Exam Coordinator', 'expires_at' => '2026-06-15 18:00', 'status' => 'active'],
            ['user' => 'Mr. James Smith', 'role' => 'Content Auditor', 'expires_at' => '2026-05-31 17:00', 'status' => 'active'],
        ];
    }

    private function getBrandAssets(): array
    {
        return [
            ['type' => 'Primary Logo', 'file' => 'city-school-logo.svg', 'status' => 'active'],
            ['type' => 'Favicon', 'file' => 'city-school-favicon.png', 'status' => 'active'],
        ];
    }

    private function getThemeOptions(): array
    {
        return [
            ['name' => 'Royal Purple', 'primary' => '#7c3aed', 'accent' => '#0891b2', 'status' => 'active'],
            ['name' => 'Emerald Blue', 'primary' => '#0f766e', 'accent' => '#0ea5e9', 'status' => 'draft'],
        ];
    }

    private function getEmailTemplates(): array
    {
        return [
            ['name' => 'Welcome Email', 'subject' => 'Welcome to City School', 'status' => 'active'],
            ['name' => 'Fee Reminder', 'subject' => 'Payment Due Reminder', 'status' => 'active'],
            ['name' => 'Attendance Alert', 'subject' => 'Attendance Notification', 'status' => 'active'],
        ];
    }

    private function getReportCardDesignProfiles(): array
    {
        return [
            ['name' => 'Classic Portrait', 'layout' => 'A4 portrait', 'status' => 'active'],
            ['name' => 'Modern KPI Card', 'layout' => 'A4 landscape', 'status' => 'draft'],
        ];
    }

    private function getRoleDashboardProfiles(): array
    {
        return [
            ['role' => 'ROLE_ADMIN', 'widgets' => 'Finance, Risks, Usage, Alerts', 'status' => 'active'],
            ['role' => 'ROLE_TEACHER', 'widgets' => 'Classes, Attendance, Grading, Messages', 'status' => 'active'],
            ['role' => 'ROLE_PARENT', 'widgets' => 'Child Progress, Fees, Alerts', 'status' => 'active'],
        ];
    }

    private function getIntegrationCatalog(): array
    {
        return [
            ['name' => 'Video Class Suite', 'type' => 'Video', 'provider' => 'Built-in / Zoom', 'status' => 'enabled'],
            ['name' => 'Cloud Storage', 'type' => 'Storage', 'provider' => 'OneDrive', 'status' => 'enabled'],
            ['name' => 'External Tool Hub', 'type' => 'LTI Tools', 'provider' => 'Mixed', 'status' => 'enabled'],
            ['name' => 'API Gateway', 'type' => 'API', 'provider' => 'Internal', 'status' => 'restricted'],
        ];
    }

    private function getExternalTools(): array
    {
        return [
            ['tool' => 'QuizMaster Pro', 'category' => 'Assessment', 'scope' => 'Teachers', 'status' => 'active'],
            ['tool' => 'STEM Simulation Lab', 'category' => 'Learning', 'scope' => 'Classes 9-12', 'status' => 'active'],
            ['tool' => 'Parent Notifier', 'category' => 'Communication', 'scope' => 'Admin', 'status' => 'pending-review'],
        ];
    }

    private function getApiClients(): array
    {
        return [
            ['client' => 'Mobile App', 'access' => 'read:announcements, read:grades', 'rate_limit' => '120 req/min', 'status' => 'active'],
            ['client' => 'BI Connector', 'access' => 'read:reports', 'rate_limit' => '60 req/min', 'status' => 'active'],
            ['client' => 'Partner Portal', 'access' => 'read:students (masked)', 'rate_limit' => '30 req/min', 'status' => 'restricted'],
        ];
    }

    private function getCalendarSyncAccounts(): array
    {
        return [
            ['account' => 'admin@cityschool.edu', 'platform' => 'Google Calendar', 'scope' => 'Academic Calendar', 'status' => 'synced'],
            ['account' => 'ops@cityschool.edu', 'platform' => 'Outlook Calendar', 'scope' => 'Exams & Events', 'status' => 'synced'],
        ];
    }

    private function getPluginRegistry(): array
    {
        return [
            ['plugin' => 'Attendance Insights', 'version' => '1.4.2', 'status' => 'installed'],
            ['plugin' => 'Exam Seating Planner', 'version' => '2.0.1', 'status' => 'installed'],
            ['plugin' => 'Parent Digest Builder', 'version' => '0.9.8', 'status' => 'beta'],
        ];
    }

    private function getIssueTickets(): array
    {
        return [
            ['id' => 'CMP-2104', 'title' => 'Grade dispute in Grade 10-B', 'reporter' => 'Parent: B. Smith', 'type' => 'Complaint', 'priority' => 'high', 'status' => 'open'],
            ['id' => 'RPT-2109', 'title' => 'Inappropriate discussion message', 'reporter' => 'Student: A. Johnson', 'type' => 'Content Report', 'priority' => 'medium', 'status' => 'in-review'],
            ['id' => 'CF-2113', 'title' => 'Teacher-student scheduling conflict', 'reporter' => 'Teacher: T. Clark', 'type' => 'Conflict', 'priority' => 'medium', 'status' => 'open'],
        ];
    }

    private function getModerationQueue(): array
    {
        return [
            ['channel' => 'Class Forum 10-B', 'content' => 'Comment flagged for tone', 'owner' => 'student.bob', 'status' => 'pending'],
            ['channel' => 'Parent Group', 'content' => 'Off-topic promotional link', 'owner' => 'parent.johnson', 'status' => 'pending'],
        ];
    }

    private function getIncidentLogs(): array
    {
        return [
            ['incident' => 'INC-8821', 'category' => 'Behavior', 'opened' => '2026-05-01 10:20', 'owner' => 'Admin', 'status' => 'investigating'],
            ['incident' => 'INC-8827', 'category' => 'Data Access', 'opened' => '2026-05-02 08:45', 'owner' => 'Security Team', 'status' => 'mitigated'],
            ['incident' => 'INC-8830', 'category' => 'Conflict Resolution', 'opened' => '2026-05-02 12:10', 'owner' => 'Counselor', 'status' => 'open'],
        ];
    }

    private function getEscalationQueue(): array
    {
        return [
            ['ticket' => 'CMP-2104', 'reason' => 'Repeated complaint escalation', 'target' => 'Super Admin', 'status' => 'queued'],
            ['ticket' => 'INC-8830', 'reason' => 'Policy-sensitive conflict', 'target' => 'Super Admin', 'status' => 'queued'],
        ];
    }

    private function getAuditLogs(): array
    {
        return [
            ['id' => 1,  'user' => 'admin',    'action' => 'user.create',     'description' => 'Created user alice (Student)',                         'ip' => '192.168.1.10', 'timestamp' => '2026-05-02 14:32', 'severity' => 'info'],
            ['id' => 2,  'user' => 'admin',    'action' => 'user.role_change', 'description' => 'Changed role of mrsmith to ROLE_TEACHER',              'ip' => '192.168.1.10', 'timestamp' => '2026-05-02 13:15', 'severity' => 'warning'],
            ['id' => 3,  'user' => 'mrsmith',  'action' => 'grade.update',    'description' => 'Updated grade for Alice Johnson — Math Midterm: 82→88', 'ip' => '192.168.1.25', 'timestamp' => '2026-05-02 11:00', 'severity' => 'info'],
            ['id' => 4,  'user' => 'admin',    'action' => 'settings.update', 'description' => 'Updated system setting: max_upload_size = 50 MB',       'ip' => '192.168.1.10', 'timestamp' => '2026-05-01 16:45', 'severity' => 'info'],
            ['id' => 5,  'user' => 'alice',    'action' => 'login.failed',    'description' => 'Failed login attempt from IP 203.0.113.5',              'ip' => '203.0.113.5',  'timestamp' => '2026-05-01 09:22', 'severity' => 'danger'],
            ['id' => 6,  'user' => 'admin',    'action' => 'payment.record',  'description' => 'Recorded payment of $1,500 for Alice Johnson',          'ip' => '192.168.1.10', 'timestamp' => '2026-04-30 15:00', 'severity' => 'info'],
            ['id' => 7,  'user' => 'admin',    'action' => 'class.create',    'description' => 'Created new class: Grade 10-C',                         'ip' => '192.168.1.10', 'timestamp' => '2026-04-29 10:30', 'severity' => 'info'],
            ['id' => 8,  'user' => 'admin',    'action' => 'bulk.import',     'description' => 'Imported 45 student records from CSV',                  'ip' => '192.168.1.10', 'timestamp' => '2026-04-28 09:00', 'severity' => 'info'],
            ['id' => 9,  'user' => 'sarahlee', 'action' => 'attendance.mark', 'description' => 'Marked attendance for Grade 9-A (28/30 present)',       'ip' => '192.168.1.30', 'timestamp' => '2026-04-28 08:45', 'severity' => 'info'],
            ['id' => 10, 'user' => 'admin',    'action' => 'user.delete',     'description' => 'Deactivated user: oldstudent2020',                      'ip' => '192.168.1.10', 'timestamp' => '2026-04-27 11:15', 'severity' => 'danger'],
        ];
    }

    private function getAnnouncements(): array
    {
        return [
            ['id' => 1, 'title' => 'End of Term Examinations',     'body' => 'Final exams scheduled for May 20–30, 2026. All students must register by May 10.',        'target' => 'all',      'pinned' => true,  'sent_by' => 'admin', 'created_at' => '2026-04-28', 'views' => 342],
            ['id' => 2, 'title' => 'New Library Resources',        'body' => 'The school library has added 200 new digital resources. Access via the student portal.',    'target' => 'students', 'pinned' => false, 'sent_by' => 'admin', 'created_at' => '2026-04-20', 'views' => 189],
            ['id' => 3, 'title' => 'Staff Meeting — May 5',        'body' => 'Mandatory staff meeting on May 5 at 14:00 in Conference Room A.',                          'target' => 'teachers', 'pinned' => true,  'sent_by' => 'admin', 'created_at' => '2026-04-18', 'views' => 12],
        ];
    }

    private function getRoleTemplates(): array
    {
        return [
            ['name' => 'Homeroom Teacher', 'permissions' => ['attendance.mark', 'grades.update', 'parent.message']],
            ['name' => 'Subject Teacher', 'permissions' => ['grades.update', 'assignments.create']],
            ['name' => 'Parent Liaison', 'permissions' => ['student.view', 'parent.message', 'alerts.send']],
        ];
    }

    private function getSmartGroups(): array
    {
        return [
            ['name' => 'Grade 10 High Performers', 'rule' => 'grade=10 and avg>=85', 'users' => 42],
            ['name' => 'At-Risk Attendance', 'rule' => 'attendance<80%', 'users' => 31],
            ['name' => 'Parents with Pending Fees', 'rule' => 'role=parent and fee_status=pending', 'users' => 18],
        ];
    }

    private function getAutoEnrollmentRules(): array
    {
        return [
            ['name' => 'New Student -> Grade 10', 'trigger' => 'new_student and age=15', 'action' => 'assign class Grade 10-A', 'status' => 'active'],
            ['name' => 'Parent Auto-Link', 'trigger' => 'new_parent email domain match', 'action' => 'link to student account', 'status' => 'active'],
            ['name' => 'Teacher Subject Mapping', 'trigger' => 'new_teacher department=Science', 'action' => 'suggest Chemistry/Biology', 'status' => 'draft'],
        ];
    }

    private function getStaffProfiles(): array
    {
        return [
            ['id' => 1, 'name' => 'Mr. James Smith', 'role' => 'Teacher', 'subject' => 'Mathematics', 'classes' => ['Grade 10-A', 'Grade 12-A'], 'hours' => 24, 'attendance' => '97%', 'contract' => 'Permanent', 'performance' => 91],
            ['id' => 2, 'name' => 'Ms. Sarah Lee', 'role' => 'Teacher', 'subject' => 'Science', 'classes' => ['Grade 9-A', 'Grade 10-B'], 'hours' => 22, 'attendance' => '95%', 'contract' => 'Permanent', 'performance' => 88],
            ['id' => 3, 'name' => 'Mr. Tom Clark', 'role' => 'Teacher', 'subject' => 'English', 'classes' => ['Grade 9-B', 'Grade 11-A'], 'hours' => 26, 'attendance' => '93%', 'contract' => 'Contract', 'performance' => 84],
            ['id' => 4, 'name' => 'Mrs. Diana Ross', 'role' => 'Counselor', 'subject' => 'Student Support', 'classes' => ['All'], 'hours' => 18, 'attendance' => '99%', 'contract' => 'Permanent', 'performance' => 90],
        ];
    }

    private function registrationStorePath(): string
    {
        return $this->getParameter('kernel.project_dir') . '/var/data/self_registration.json';
    }

    private function readRegistrationStore(): array
    {
        $path = $this->registrationStorePath();
        if (!is_file($path)) {
            return ['pending' => [], 'approved' => []];
        }

        $raw = file_get_contents($path);
        if (!is_string($raw) || $raw === '') {
            return ['pending' => [], 'approved' => []];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return ['pending' => [], 'approved' => []];
        }

        return [
            'pending' => is_array($decoded['pending'] ?? null) ? $decoded['pending'] : [],
            'approved' => is_array($decoded['approved'] ?? null) ? $decoded['approved'] : [],
        ];
    }

    private function writeRegistrationStore(array $store): void
    {
        $path = $this->registrationStorePath();
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents(
            $path,
            json_encode([
                'pending' => array_values(is_array($store['pending'] ?? null) ? $store['pending'] : []),
                'approved' => array_values(is_array($store['approved'] ?? null) ? $store['approved'] : []),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    private function syncDynamicMemoryUser(string $username, string $passwordHash, string $role): void
    {
        $path = $this->getParameter('kernel.project_dir') . '/config/packages/security_dynamic_users.yaml';
        $config = is_file($path) ? (Yaml::parseFile($path) ?? []) : [];

        if (!is_array($config)) {
            $config = [];
        }

        if (!isset($config['security']) || !is_array($config['security'])) {
            $config['security'] = [];
        }
        if (!isset($config['security']['providers']) || !is_array($config['security']['providers'])) {
            $config['security']['providers'] = [];
        }
        if (!isset($config['security']['providers']['portal_users']) || !is_array($config['security']['providers']['portal_users'])) {
            $config['security']['providers']['portal_users'] = [];
        }
        if (!isset($config['security']['providers']['portal_users']['memory']) || !is_array($config['security']['providers']['portal_users']['memory'])) {
            $config['security']['providers']['portal_users']['memory'] = [];
        }
        if (!isset($config['security']['providers']['portal_users']['memory']['users']) || !is_array($config['security']['providers']['portal_users']['memory']['users'])) {
            $config['security']['providers']['portal_users']['memory']['users'] = [];
        }

        $config['security']['providers']['portal_users']['memory']['users'][$username] = [
            'password' => $passwordHash,
            'roles' => [$role],
        ];

        file_put_contents($path, Yaml::dump($config, 6, 4));
    }

    // ─── Routes ───────────────────────────────────────────────────────────────

    #[Route('/dashboard', name: 'admin_dashboard')]
    public function dashboard(Request $request): Response
    {
        $users    = $this->getUsers();
        $classes  = $this->getClasses();
        $payments = $this->getPayments();
        $logs     = $this->getAuditLogs();
        $trialContext = $request->getSession()->get('school_portal_context');

        if (is_array($trialContext) && !empty($trialContext['trial_ends_at'])) {
            $trialEndsAt = new \DateTimeImmutable($trialContext['trial_ends_at']);
            $daysRemaining = (int) (new \DateTimeImmutable())->setTime(0, 0)->diff($trialEndsAt->setTime(0, 0))->format('%r%a');
            $trialContext['trial_days_remaining'] = $daysRemaining;
            $trialContext['trial_expired'] = $daysRemaining < 0;
            $request->getSession()->set('school_portal_context', $trialContext);
        }

        $students = array_filter($users, fn($u) => $u['role'] === 'ROLE_STUDENT');
        $teachers = array_filter($users, fn($u) => $u['role'] === 'ROLE_TEACHER');
        $paid     = array_filter($payments, fn($p) => $p['status'] === 'paid');
        $pending  = array_filter($payments, fn($p) => $p['status'] === 'pending');
        $overdue  = array_filter($payments, fn($p) => $p['status'] === 'overdue');

        return $this->render('admin/dashboard.html.twig', [
            'admin'          => $this->getAdmin(),
            'total_users'    => count($users),
            'total_students' => count($students),
            'total_teachers' => count($teachers),
            'total_classes'  => count($classes),
            'revenue_paid'   => array_sum(array_column(array_values($paid), 'amount')),
            'pending_count'  => count($pending),
            'overdue_count'  => count($overdue),
            'recent_logs'    => array_slice($logs, 0, 5),
            'announcements'  => $this->getAnnouncements(),
            'trial_context'  => is_array($trialContext) ? $trialContext : null,
        ]);
    }

    #[Route('/users', name: 'admin_users')]
    public function users(Request $request): Response
    {
        $store = $this->readRegistrationStore();
        $pendingRequests = $store['pending'];
        $approvedUsers = $store['approved'];

        if ($request->isMethod('POST')) {
            $action = (string) $request->request->get('action', 'create');
            $messages = [
                'create' => 'User created successfully.',
                'update' => 'User updated successfully.',
                'delete' => 'User removed successfully.',
                'bulk_import' => 'Bulk user import queued successfully.',
                'assign_class' => 'Student class/section assignment saved.',
                'link_parent' => 'Parent linked to student successfully.',
                'reset_password' => 'Password reset link generated.',
                'activate' => 'User account activated.',
                'deactivate' => 'User account deactivated.',
                'apply_role_template' => 'Role template applied successfully.',
                'save_auto_rule' => 'Auto-enrollment rule saved.',
            ];

            if ($action === 'approve_registration' || $action === 'reject_registration') {
                $requestId = (string) $request->request->get('request_id', '');
                $targetIdx = null;

                foreach ($pendingRequests as $idx => $pending) {
                    if (($pending['id'] ?? '') === $requestId) {
                        $targetIdx = $idx;
                        break;
                    }
                }

                if ($targetIdx === null) {
                    $this->addFlash('error', 'Registration request was not found or already processed.');
                    return $this->redirectToRoute('admin_users');
                }

                $pending = $pendingRequests[$targetIdx];

                if ($action === 'approve_registration') {
                    $role = ($pending['role'] ?? '') === 'parent' ? 'ROLE_PARENT' : 'ROLE_STUDENT';
                    $approvedUsers[] = [
                        'id' => count($this->getUsers()) + count($approvedUsers) + 1,
                        'request_id' => (string) ($pending['id'] ?? ''),
                        'name' => (string) ($pending['full_name'] ?? 'New User'),
                        'username' => (string) ($pending['username'] ?? 'newuser'),
                        'email' => (string) ($pending['email'] ?? 'pending@cityschool.edu'),
                        'role' => $role,
                        'class' => $role === 'ROLE_PARENT'
                            ? ((string) ($pending['child_name'] ?? 'Child') . ' (' . (string) ($pending['child_class'] ?? 'N/A') . ')')
                            : (string) ($pending['class_level'] ?? 'Pending class assignment'),
                        'status' => 'active',
                        'joined' => (new \DateTimeImmutable())->format('Y-m-d'),
                        'approved_at' => (new \DateTimeImmutable())->format('Y-m-d H:i'),
                    ];

                    $passwordHash = (string) ($pending['password_hash'] ?? '');
                    if ($passwordHash !== '') {
                        $this->syncDynamicMemoryUser(
                            username: (string) ($pending['username'] ?? ''),
                            passwordHash: $passwordHash,
                            role: $role
                        );
                    }

                    $this->addFlash('success', sprintf('Registration approved for %s. User is now visible in User Management.', (string) ($pending['full_name'] ?? 'new user')));
                } else {
                    $this->addFlash('success', sprintf('Registration request rejected for %s.', (string) ($pending['full_name'] ?? 'the applicant')));
                }

                array_splice($pendingRequests, $targetIdx, 1);
                $store['pending'] = $pendingRequests;
                $store['approved'] = $approvedUsers;
                $this->writeRegistrationStore($store);

                return $this->redirectToRoute('admin_users');
            }

            $this->addFlash('success', $messages[$action] ?? 'User operation completed.');
            return $this->redirectToRoute('admin_users');
        }

        $users = array_merge($this->getUsers(), $approvedUsers);

        return $this->render('admin/users/index.html.twig', [
            'admin'          => $this->getAdmin(),
            'users'          => $users,
            'classes'        => $this->getClasses(),
            'role_templates' => $this->getRoleTemplates(),
            'smart_groups'   => $this->getSmartGroups(),
            'auto_rules'     => $this->getAutoEnrollmentRules(),
            'pending_requests' => $pendingRequests,
            'approved_requests' => $approvedUsers,
        ]);
    }

    #[Route('/users/impersonate-student', name: 'admin_impersonate_student', methods: ['POST'])]
    public function impersonateStudent(Request $request): RedirectResponse
    {
        $username = trim((string) $request->request->get('username', ''));

        if ($username === '') {
            $this->addFlash('error', 'Student username is required for impersonation.');
            return $this->redirectToRoute('admin_users');
        }

        $request->getSession()->set('_security.portal.target_path', $this->generateUrl('portal_dashboard'));

        $request->request->set('_username', $username);
        $request->request->set('_password', self::DEMO_SHARED_PASSWORD);

        return $this->redirectToRoute('portal_login');
    }

    #[Route('/staff', name: 'admin_staff')]
    public function staff(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $action = (string) $request->request->get('action', 'save');
            $messages = [
                'add_staff' => 'Staff profile saved successfully.',
                'assign_subject' => 'Subject assigned to teacher.',
                'allocate_class' => 'Class allocation updated.',
                'update_workload' => 'Teaching workload updated.',
                'mark_attendance' => 'Staff attendance updated.',
                'save_contract' => 'Contract details saved.',
                'suggest_balance' => 'Workload balancing suggestions generated.',
                'auto_substitute' => 'Substitute teacher assigned automatically.',
            ];

            $this->addFlash('success', $messages[$action] ?? 'Staff management action completed.');
            return $this->redirectToRoute('admin_staff');
        }

        return $this->render('admin/staff/index.html.twig', [
            'admin'    => $this->getAdmin(),
            'staff'    => $this->getStaffProfiles(),
            'subjects' => $this->getSubjects(),
            'classes'  => $this->getClasses(),
        ]);
    }

    #[Route('/classes', name: 'admin_classes')]
    public function classes(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $this->addFlash('success', 'Class saved successfully.');
            return $this->redirectToRoute('admin_classes');
        }

        return $this->render('admin/classes/index.html.twig', [
            'admin'    => $this->getAdmin(),
            'classes'  => $this->getClasses(),
            'teachers' => array_filter($this->getUsers(), fn($u) => $u['role'] === 'ROLE_TEACHER'),
        ]);
    }

    #[Route('/subjects', name: 'admin_subjects')]
    public function subjects(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $this->addFlash('success', 'Subject saved successfully.');
            return $this->redirectToRoute('admin_subjects');
        }

        return $this->render('admin/subjects/index.html.twig', [
            'admin'    => $this->getAdmin(),
            'subjects' => $this->getSubjects(),
            'teachers' => array_filter($this->getUsers(), fn($u) => $u['role'] === 'ROLE_TEACHER'),
        ]);
    }

    #[Route('/schedules', name: 'admin_schedules')]
    public function schedules(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $action = (string) $request->request->get('action', 'save_schedule');
            $messages = [
                'save_schedule' => 'Class schedule saved successfully.',
                'assign_teacher_slot' => 'Teacher assigned to selected time slot.',
                'allocate_room' => 'Room allocation updated successfully.',
                'resolve_conflict' => 'Schedule conflict handled successfully.',
                'dynamic_update' => 'Timetable updated dynamically.',
                'auto_generate' => 'Auto timetable generation completed.',
                'detect_conflicts' => 'Conflict detection scan completed.',
                'drag_drop_save' => 'Drag-and-drop schedule changes saved.',
            ];

            $this->addFlash('success', $messages[$action] ?? 'Schedule operation completed.');
            return $this->redirectToRoute('admin_schedules');
        }

        return $this->render('admin/schedules/index.html.twig', [
            'admin'     => $this->getAdmin(),
            'schedules' => $this->getSchedules(),
            'classes'   => $this->getClasses(),
            'subjects'  => $this->getSubjects(),
            'teachers'  => array_filter($this->getUsers(), fn($u) => $u['role'] === 'ROLE_TEACHER'),
            'rooms'     => $this->getRooms(),
            'conflicts' => $this->getScheduleConflicts(),
        ]);
    }

    #[Route('/academic', name: 'admin_academic')]
    public function academic(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $action = (string) $request->request->get('action', 'save_term');
            $messages = [
                'create_class' => 'Class, grade, and section created successfully.',
                'save_subject_curriculum' => 'Subject and curriculum mapping saved.',
                'save_term' => 'Academic year / semester / term updated successfully.',
                'assign_class_teacher' => 'Class teacher assignment saved.',
                'configure_grading' => 'Grading system configured successfully.',
                'save_custom_grading' => 'Custom grading model saved for selected school.',
                'apply_curriculum_template' => 'Curriculum template applied successfully.',
                'save_multilanguage' => 'Multi-language course support settings updated.',
            ];

            $this->addFlash('success', $messages[$action] ?? 'Academic structure operation completed.');
            return $this->redirectToRoute('admin_academic');
        }

        return $this->render('admin/academic/index.html.twig', [
            'admin'                 => $this->getAdmin(),
            'terms'                 => $this->getTerms(),
            'classes'               => $this->getClasses(),
            'subjects'              => $this->getSubjects(),
            'teachers'              => array_filter($this->getUsers(), fn($u) => $u['role'] === 'ROLE_TEACHER'),
            'academic_years'        => $this->getAcademicYears(),
            'grading_systems'       => $this->getGradingSystems(),
            'custom_grading_models' => $this->getCustomGradingModels(),
            'curriculum_templates'  => $this->getCurriculumTemplates(),
            'multilingual_courses'  => $this->getMultilingualCourses(),
        ]);
    }

    #[Route('/courses', name: 'admin_courses')]
    public function courses(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $action = (string) $request->request->get('action', 'monitor_courses');
            $messages = [
                'monitor_courses' => 'Teacher course catalog refreshed successfully.',
                'review_material' => 'Uploaded material reviewed.',
                'approve_material' => 'Material approved and published.',
                'reject_material' => 'Material rejected and returned to teacher.',
                'check_compliance' => 'Curriculum compliance checks completed.',
                'archive_course' => 'Course archived successfully.',
                'run_quality_scoring' => 'Content quality scoring completed.',
                'save_resource_library' => 'Central resource library updated.',
            ];

            $this->addFlash('success', $messages[$action] ?? 'Course oversight action completed.');
            return $this->redirectToRoute('admin_courses');
        }

        return $this->render('admin/courses/index.html.twig', [
            'admin'                 => $this->getAdmin(),
            'courses'               => $this->getTeacherCourses(),
            'materials'             => $this->getUploadedMaterials(),
            'compliance_checks'     => $this->getCurriculumComplianceChecks(),
            'archived_courses'      => $this->getArchivedCourses(),
            'quality_scores'        => $this->getContentQualityScores(),
            'resource_library'      => $this->getCentralResourceLibrary(),
        ]);
    }

    #[Route('/announcements', name: 'admin_announcements')]
    public function announcements(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $action = (string) $request->request->get('action', 'send_announcement');
            $messages = [
                'send_announcement' => 'Announcement sent successfully.',
                'schedule_message' => 'Message scheduled successfully.',
                'save_policy' => 'Messaging policy updated.',
                'broadcast_emergency' => 'Emergency alert broadcast started.',
                'send_targeted' => 'Targeted message dispatched successfully.',
                'refresh_tracking' => 'Notification tracking refreshed.',
                'save_multichannel' => 'Multi-channel delivery settings saved.',
            ];

            $this->addFlash('success', $messages[$action] ?? 'Communication action completed.');
            return $this->redirectToRoute('admin_announcements');
        }

        return $this->render('admin/announcements/index.html.twig', [
            'admin'                 => $this->getAdmin(),
            'announcements'         => $this->getAnnouncements(),
            'message_schedules'     => $this->getMessageSchedules(),
            'messaging_policies'    => $this->getMessagingPolicies(),
            'notification_tracking' => $this->getNotificationTracking(),
            'classes'               => $this->getClasses(),
        ]);
    }

    #[Route('/attendance', name: 'admin_attendance')]
    public function attendance(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $action = (string) $request->request->get('action', 'monitor_students');
            $messages = [
                'monitor_students' => 'Student attendance monitoring updated.',
                'monitor_teachers' => 'Teacher attendance monitoring updated.',
                'approve_absence' => 'Absence request approved.',
                'reject_absence' => 'Absence request rejected.',
                'generate_attendance_report' => 'Attendance report generated successfully.',
                'save_attendance_rule' => 'Attendance threshold rules saved.',
                'realtime_tracking' => 'Real-time attendance tracking enabled.',
                'send_low_attendance_alerts' => 'Low attendance alerts dispatched.',
                'save_biometric_qr' => 'Biometric / QR integration settings saved.',
            ];

            $this->addFlash('success', $messages[$action] ?? 'Attendance action completed.');
            return $this->redirectToRoute('admin_attendance');
        }

        return $this->render('admin/attendance/index.html.twig', [
            'admin'               => $this->getAdmin(),
            'student_attendance'  => $this->getStudentAttendance(),
            'teacher_attendance'  => $this->getTeacherAttendance(),
            'absence_requests'    => $this->getAbsenceRequests(),
            'attendance_rules'    => $this->getAttendanceRules(),
            'attendance_alerts'   => $this->getAttendanceAlerts(),
        ]);
    }

    #[Route('/grades', name: 'admin_grades')]
    public function grades(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $action = (string) $request->request->get('action', 'configure_exam');
            $messages = [
                'configure_exam' => 'Exam configuration saved.',
                'save_grading_policy' => 'Grading policy updated successfully.',
                'approve_final_results' => 'Final results approved.',
                'generate_report_cards' => 'Report cards generated successfully.',
                'generate_report_cards_pdf' => 'Automated PDF report cards generated.',
                'run_performance_comparison' => 'Class vs school comparison generated.',
                'run_trend_analysis' => 'Trend analysis completed.',
            ];

            $this->addFlash('success', $messages[$action] ?? 'Grades and exams action completed.');
            return $this->redirectToRoute('admin_grades');
        }

        return $this->render('admin/grades/index.html.twig', [
            'admin'               => $this->getAdmin(),
            'exam_configs'        => $this->getExamConfigurations(),
            'grading_policies'    => $this->getGradingPolicies(),
            'student_reports'     => $this->getStudentGradeReports(),
            'class_reports'       => $this->getClassPerformanceReports(),
            'trend_series'        => $this->getTrendSeries(),
            'classes'             => $this->getClasses(),
        ]);
    }

    #[Route('/reports', name: 'admin_reports')]
    public function reports(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $action = (string) $request->request->get('action', 'view_dashboard');
            $messages = [
                'view_dashboard' => 'Analytics dashboards refreshed.',
                'drilldown' => 'Drill-down view generated successfully.',
                'export_csv' => 'CSV report export prepared.',
                'export_pdf' => 'PDF report export prepared.',
                'monitor_usage' => 'System usage metrics refreshed.',
                'predictive_insights' => 'Predictive insights generated.',
                'visual_dashboard' => 'Visual KPI dashboard updated.',
                'save_custom_report' => 'Custom report builder configuration saved.',
            ];

            $this->addFlash('success', $messages[$action] ?? 'Analytics action completed.');
            return $this->redirectToRoute('admin_reports');
        }

        return $this->render('admin/reports/index.html.twig', [
            'admin'                => $this->getAdmin(),
            'users'                => $this->getUsers(),
            'classes'              => $this->getClasses(),
            'subjects'             => $this->getSubjects(),
            'system_usage'         => $this->getSystemUsageStats(),
            'at_risk_students'     => $this->getAtRiskStudents(),
            'trend_series'         => $this->getTrendSeries(),
            'custom_report_blocks' => $this->getCustomReportBlocks(),
        ]);
    }

    #[Route('/payments', name: 'admin_payments')]
    public function payments(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $action = (string) $request->request->get('action', 'record_payment');
            $messages = [
                'create_fee_structure' => 'Fee structure saved successfully.',
                'assign_fee' => 'Fee assigned to selected students.',
                'record_payment' => 'Payment recorded successfully.',
                'generate_invoice' => 'Invoice generated successfully.',
                'generate_receipt' => 'Receipt generated successfully.',
                'save_discount' => 'Discount or scholarship applied.',
                'stripe_integration' => 'Stripe integration settings saved.',
                'payment_reminder_automation' => 'Payment reminder automation enabled.',
                'financial_forecast' => 'Financial forecast report generated.',
            ];

            $this->addFlash('success', $messages[$action] ?? 'Financial action completed.');
            return $this->redirectToRoute('admin_payments');
        }

        return $this->render('admin/payments/index.html.twig', [
            'admin'               => $this->getAdmin(),
            'payments'            => $this->getPayments(),
            'users'               => array_filter($this->getUsers(), fn($u) => $u['role'] === 'ROLE_STUDENT'),
            'fee_structures'      => $this->getFeeStructures(),
            'assigned_fees'       => $this->getAssignedFees(),
            'invoices_receipts'   => $this->getInvoicesAndReceipts(),
            'discounts'           => $this->getDiscountScholarships(),
            'reminder_queue'      => $this->getPaymentReminderQueue(),
            'financial_forecasts' => $this->getFinancialForecasting(),
        ]);
    }

    #[Route('/billing-subscription', name: 'admin_billing')]
    public function billingSubscription(Request $request, MailerInterface $mailer): Response
    {
        $state = $this->getBillingSubscriptionState($request);

        if ($request->isMethod('POST')) {
            $action = (string) $request->request->get('action', '');

            if ($action === 'send_test_email') {
                $to = trim((string) $request->request->get('test_email', '')) ?: $this->getAdmin()['email'];

                if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
                    $this->addFlash('error', 'Please provide a valid test email address.');
                    return $this->redirectToRoute('admin_billing');
                }

                try {
                    $email = (new Email())
                        ->from('no-reply@cityschool.edu')
                        ->to($to)
                        ->subject('City School Billing Mail Test')
                        ->html('<p>This is a test email from City School billing settings.</p><p>If you received this, your mail transport is working.</p>');

                    $mailer->send($email);
                    $this->addFlash('success', sprintf('Test email sent successfully to %s.', $to));
                } catch (\Throwable $e) {
                    $this->addFlash('error', 'Failed to send test email. Check MAILER_DSN and provider credentials.');
                }

                return $this->redirectToRoute('admin_billing');
            }

            $state = $this->processBillingSubscriptionAction($request, $state);
            $this->saveBillingSubscriptionState($request, $state);

            return $this->redirectToRoute('admin_billing');
        }

        return $this->render('admin/billing/index.html.twig', [
            'admin' => $this->getAdmin(),
            'subscription' => $state,
            'plan_catalog' => $this->getPlanCatalog($state),
            'subscription_invoices' => $this->getPlatformInvoices($state),
            'student_usage' => array_slice(array_filter($this->getUsers(), fn($u) => $u['role'] === 'ROLE_STUDENT'), 0, 5),
        ]);
    }

    #[Route('/permissions', name: 'admin_permissions')]
    public function permissions(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $action = (string) $request->request->get('action', 'save_role_permissions');
            $messages = [
                'save_role_permissions' => 'Role-based permissions updated successfully.',
                'save_sensitive_access' => 'Sensitive data access rules saved.',
                'create_custom_role' => 'Custom role created successfully.',
                'save_login_policy' => 'Login policy updated successfully.',
                'save_fine_grained' => 'Fine-grained permissions updated.',
                'grant_temporary_access' => 'Temporary role access granted.',
            ];

            $this->addFlash('success', $messages[$action] ?? 'Permissions and access control updated.');
            return $this->redirectToRoute('admin_permissions');
        }

        return $this->render('admin/permissions/index.html.twig', [
            'admin'                 => $this->getAdmin(),
            'permission_matrix'     => $this->getPermissionMatrix(),
            'sensitive_policies'    => $this->getSensitiveDataPolicies(),
            'custom_roles'          => $this->getCustomRoles(),
            'login_policies'        => $this->getLoginPolicies(),
            'fine_grained'          => $this->getFineGrainedPermissions(),
            'temporary_access_list' => $this->getTemporaryAccessAssignments(),
        ]);
    }

    #[Route('/branding', name: 'admin_branding')]
    public function branding(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $action = (string) $request->request->get('action', 'upload_logo');
            $messages = [
                'upload_logo' => 'School logo updated successfully.',
                'save_theme' => 'Theme colors updated successfully.',
                'save_email_template' => 'Email template saved.',
                'save_report_card_design' => 'Report card design profile saved.',
                'apply_white_label' => 'White-label branding applied.',
                'save_role_dashboard' => 'Custom dashboard layout saved for role.',
            ];

            $this->addFlash('success', $messages[$action] ?? 'Branding settings updated.');
            return $this->redirectToRoute('admin_branding');
        }

        return $this->render('admin/branding/index.html.twig', [
            'admin'                  => $this->getAdmin(),
            'brand_assets'           => $this->getBrandAssets(),
            'theme_options'          => $this->getThemeOptions(),
            'email_templates'        => $this->getEmailTemplates(),
            'report_card_designs'    => $this->getReportCardDesignProfiles(),
            'role_dashboards'        => $this->getRoleDashboardProfiles(),
        ]);
    }

    #[Route('/integrations', name: 'admin_integrations')]
    public function integrations(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $action = (string) $request->request->get('action', 'enable_video_tools');
            $messages = [
                'enable_video_tools' => 'Video class tools configuration saved.',
                'connect_cloud_storage' => 'Cloud storage connected successfully.',
                'manage_external_tools' => 'External tools configuration updated.',
                'configure_limited_api' => 'Limited API access policies saved.',
                'connect_google_classroom' => 'Google Classroom integration enabled.',
                'sync_calendar' => 'Google/Outlook calendar sync configured.',
                'save_plugin_system' => 'Plugin system settings saved.',
            ];

            $this->addFlash('success', $messages[$action] ?? 'Integration settings updated.');
            return $this->redirectToRoute('admin_integrations');
        }

        return $this->render('admin/integrations/index.html.twig', [
            'admin'          => $this->getAdmin(),
            'integrations'   => $this->getIntegrationCatalog(),
            'external_tools' => $this->getExternalTools(),
            'api_clients'    => $this->getApiClients(),
            'calendar_sync'  => $this->getCalendarSyncAccounts(),
            'plugins'        => $this->getPluginRegistry(),
        ]);
    }

    #[Route('/issues', name: 'admin_issues')]
    public function issues(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $action = (string) $request->request->get('action', 'handle_complaint');
            $messages = [
                'handle_complaint' => 'Complaint/report has been assigned for handling.',
                'moderate_content' => 'Discussion/content moderation action applied.',
                'resolve_conflict' => 'Student/teacher conflict marked as resolved.',
                'escalate_super_admin' => 'Issue escalated to Super Admin.',
                'create_ticket' => 'Internal support ticket created.',
                'update_incident_log' => 'Incident tracking log updated.',
            ];

            $this->addFlash('success', $messages[$action] ?? 'Issue moderation action completed.');
            return $this->redirectToRoute('admin_issues');
        }

        return $this->render('admin/issues/index.html.twig', [
            'admin'            => $this->getAdmin(),
            'tickets'          => $this->getIssueTickets(),
            'moderation_queue' => $this->getModerationQueue(),
            'incidents'        => $this->getIncidentLogs(),
            'escalations'      => $this->getEscalationQueue(),
        ]);
    }

    #[Route('/settings', name: 'admin_settings')]
    public function settings(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $this->addFlash('success', 'Settings saved successfully.');
            return $this->redirectToRoute('admin_settings');
        }

        return $this->render('admin/settings/index.html.twig', [
            'admin' => $this->getAdmin(),
        ]);
    }

    #[Route('/audit', name: 'admin_audit')]
    public function audit(): Response
    {
        return $this->render('admin/audit/index.html.twig', [
            'admin' => $this->getAdmin(),
            'logs'  => $this->getAuditLogs(),
        ]);
    }

    #[Route('/profile', name: 'admin_profile')]
    public function profile(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $this->addFlash('success', 'Profile updated successfully.');
            return $this->redirectToRoute('admin_profile');
        }

        return $this->render('admin/profile.html.twig', [
            'admin' => $this->getAdmin(),
        ]);
    }
}
