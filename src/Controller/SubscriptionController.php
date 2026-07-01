<?php

namespace App\Controller;

use App\Entity\School;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\NativePasswordHasher;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/subscribe', name: 'subscribe_')]
class SubscriptionController extends AbstractController
{
    private const TRIAL_LENGTH_DAYS = 14;

    /** Price IDs per plan per cycle — configure in .env */
    private const PLANS = [
        'Basic'      => ['monthly' => 'STRIPE_PRICE_BASIC_MONTHLY',   'yearly' => 'STRIPE_PRICE_BASIC_YEARLY'],
        'Pro'        => ['monthly' => 'STRIPE_PRICE_PRO_MONTHLY',     'yearly' => 'STRIPE_PRICE_PRO_YEARLY'],
        'Enterprise' => ['monthly' => 'STRIPE_PRICE_ENT_MONTHLY',     'yearly' => 'STRIPE_PRICE_ENT_YEARLY'],
    ];

    private const PLAN_AMOUNTS = [
        'Basic'      => ['monthly' => 79,  'yearly' => 756],
        'Pro'        => ['monthly' => 149, 'yearly' => 1428],
        'Enterprise' => ['monthly' => 299, 'yearly' => 2868],
    ];

    private const TIMEZONES = [
        'UTC'              => 'UTC (Universal)',
        'America/New_York' => 'Eastern Time (ET)',
        'America/Chicago'  => 'Central Time (CT)',
        'America/Denver'   => 'Mountain Time (MT)',
        'America/Los_Angeles' => 'Pacific Time (PT)',
        'Europe/London'    => 'London (GMT)',
        'Europe/Paris'     => 'Paris / Berlin (CET)',
        'Asia/Dubai'       => 'Dubai (GST)',
        'Asia/Kolkata'     => 'India (IST)',
        'Asia/Singapore'   => 'Singapore (SGT)',
        'Australia/Sydney' => 'Sydney (AEST)',
    ];

    private const COUNTRIES = [
        'US' => 'United States', 'CA' => 'Canada', 'GB' => 'United Kingdom',
        'FR' => 'France', 'DE' => 'Germany', 'AE' => 'UAE', 'SA' => 'Saudi Arabia',
        'IN' => 'India', 'SG' => 'Singapore', 'AU' => 'Australia', 'NG' => 'Nigeria',
        'ZA' => 'South Africa', 'KE' => 'Kenya', 'MA' => 'Morocco', 'EG' => 'Egypt',
        'BR' => 'Brazil', 'MX' => 'Mexico', 'JP' => 'Japan', 'CN' => 'China', 'OTHER' => 'Other',
    ];

    private const TRIAL_LIMITS = [
        'Advanced analytics dashboards',
        'API access and custom integrations',
        'White-label branding and custom domain',
        'Priority SLA support',
    ];

    // ─── STEP 1: Create School Account ────────────────────────────────────────

    #[Route('', name: 'step1', methods: ['GET', 'POST'])]
    public function step1(Request $request): Response
    {
        $errors = [];
        $data   = $request->getSession()->get('subscribe_step1', []);

        if ($request->isMethod('GET')) {
            $plan = (string) $request->query->get('plan', '');
            $cycle = (string) $request->query->get('cycle', '');
            $mode = (string) $request->query->get('mode', '');

            if ($plan || $cycle || $mode) {
                $request->getSession()->set('subscribe_preferences', [
                    'plan' => $plan,
                    'cycle' => $cycle,
                    'mode' => $this->normalizeMode($mode),
                ]);
            }
        }

        if ($request->isMethod('POST')) {
            $name     = trim($request->request->get('school_name', ''));
            $email    = trim($request->request->get('email', ''));
            $password = $request->request->get('password', '');
            $confirm  = $request->request->get('password_confirm', '');

            if (!$name)                       $errors['school_name'] = 'School name is required.';
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Valid email required.';
            if (strlen($password) < 8)        $errors['password'] = 'Password must be at least 8 characters.';
            if ($password !== $confirm)       $errors['password_confirm'] = 'Passwords do not match.';

            if (!$errors) {
                $request->getSession()->set('subscribe_step1', [
                    'school_name' => $name,
                    'email'       => $email,
                    'password'    => $password,
                ]);
                return $this->redirectToRoute('subscribe_step2');
            }

            $data = [
                'school_name' => $name,
                'email' => $email,
            ];
        }

        return $this->render('subscribe/step1.html.twig', [
            'errors' => $errors,
            'data'   => $data,
            'step'   => 1,
        ]);
    }

    // ─── STEP 2: Select Plan ──────────────────────────────────────────────────

    #[Route('/plan', name: 'step2', methods: ['GET', 'POST'])]
    public function step2(Request $request): Response
    {
        if (!$request->getSession()->has('subscribe_step1')) {
            return $this->redirectToRoute('subscribe_step1');
        }

        $errors = [];
        $saved  = $request->getSession()->get('subscribe_step2', []);
        $preferences = $request->getSession()->get('subscribe_preferences', []);

        // Allow pre-selecting plan via query param (e.g. from pricing page)
        $preselect = $request->query->get('plan', $preferences['plan'] ?? $saved['plan'] ?? 'Pro');
        $preCycle  = $request->query->get('cycle', $preferences['cycle'] ?? $saved['cycle'] ?? 'monthly');
        $mode = $this->normalizeMode((string) $request->query->get('mode', $preferences['mode'] ?? $saved['mode'] ?? 'paid'));

        if ($request->isMethod('POST')) {
            $plan  = $request->request->get('plan', '');
            $cycle = $request->request->get('billing_cycle', 'monthly');
            $mode = $this->normalizeMode((string) $request->request->get('mode', $mode));

            if (!array_key_exists($plan, self::PLANS)) $errors['plan'] = 'Please select a valid plan.';
            if (!in_array($cycle, ['monthly', 'yearly'], true)) $cycle = 'monthly';

            if (!$errors) {
                $request->getSession()->set('subscribe_step2', compact('plan', 'cycle', 'mode'));
                return $this->redirectToRoute('subscribe_step3');
            }
        }

        return $this->render('subscribe/step2.html.twig', [
            'errors'     => $errors,
            'preselect'  => $preselect,
            'preCycle'   => $preCycle,
            'mode'       => $mode,
            'plans'      => self::PLAN_AMOUNTS,
            'step'       => 2,
        ]);
    }

    // ─── STEP 3: School Info ──────────────────────────────────────────────────

    #[Route('/school-info', name: 'step3', methods: ['GET', 'POST'])]
    public function step3(Request $request): Response
    {
        if (!$request->getSession()->has('subscribe_step2')) {
            return $this->redirectToRoute('subscribe_step2');
        }

        $errors = [];
        $data   = $request->getSession()->get('subscribe_step3', []);

        if ($request->isMethod('POST')) {
            $address  = trim($request->request->get('address', ''));
            $country  = $request->request->get('country', '');
            $timezone = $request->request->get('timezone', '');

            if (!$address)                              $errors['address']  = 'Address is required.';
            if (!array_key_exists($country, self::COUNTRIES)) $errors['country']  = 'Select a valid country.';
            if (!array_key_exists($timezone, self::TIMEZONES)) $errors['timezone'] = 'Select a valid timezone.';

            if (!$errors) {
                $request->getSession()->set('subscribe_step3', compact('address', 'country', 'timezone'));
                return $this->redirectToRoute('subscribe_step4');
            }

            $data = compact('address', 'country', 'timezone');
        }

        return $this->render('subscribe/step3.html.twig', [
            'errors'    => $errors,
            'data'      => $data,
            'countries' => self::COUNTRIES,
            'timezones' => self::TIMEZONES,
            'step'      => 3,
        ]);
    }

    // ─── STEP 4: Payment — create Stripe Checkout Session ─────────────────────

    #[Route('/payment', name: 'step4', methods: ['GET', 'POST'])]
    public function step4(Request $request, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        if (!$request->getSession()->has('subscribe_step3')) {
            return $this->redirectToRoute('subscribe_step3');
        }

        $step1 = $request->getSession()->get('subscribe_step1');
        $step2 = $request->getSession()->get('subscribe_step2');
        $mode = $this->normalizeMode((string) ($step2['mode'] ?? 'paid'));

        $plan  = $step2['plan'];
        $cycle = $step2['cycle'];

        // Summary for review
        $summary = [
            'school_name' => $step1['school_name'],
            'email'       => $step1['email'],
            'plan'        => $plan,
            'cycle'       => $cycle,
            'mode'        => $mode,
            'amount'      => self::PLAN_AMOUNTS[$plan][$cycle],
            'trialEndsAt' => (new \DateTimeImmutable())->modify('+' . self::TRIAL_LENGTH_DAYS . ' days'),
            'limitedFeatures' => self::TRIAL_LIMITS,
            'step3'       => $request->getSession()->get('subscribe_step3'),
        ];

        if ($request->isMethod('POST')) {
            if ($mode === 'trial') {
                $school = $this->createSchoolFromWizard($request, 'trialing');
                $this->persistSchool($em, $school);
                $this->storePortalContext($request, $school);
                $this->sendLifecycleEmail($mailer, $school, 'trial');
                $this->clearWizardSession($request);

                return $this->redirectToRoute('subscribe_trial_dashboard');
            }

            $stripeKey = $_ENV['STRIPE_SECRET_KEY'] ?? '';

            if (!$stripeKey || str_starts_with($stripeKey, 'sk_test_YOUR')) {
                // Demo mode — skip Stripe and go directly to mock success
                $request->getSession()->set('subscribe_demo_mode', true);
                return $this->redirectToRoute('subscribe_success', ['session_id' => 'demo_' . uniqid()]);
            }

            try {
                $stripe = new StripeClient($stripeKey);

                $envKey  = self::PLANS[$plan][$cycle];
                $priceId = $_ENV[$envKey] ?? null;

                if (!$priceId) {
                    // Fallback: create a one-time price on the fly (test mode)
                    $priceId = $stripe->prices->create([
                        'currency'     => 'usd',
                        'unit_amount'  => self::PLAN_AMOUNTS[$plan][$cycle] * 100,
                        'recurring'    => ['interval' => $cycle === 'yearly' ? 'year' : 'month'],
                        'product_data' => ['name' => "City School {$plan} ({$cycle})"],
                    ])->id;
                }

                $checkoutSession = $stripe->checkout->sessions->create([
                    'mode'          => 'subscription',
                    'customer_email' => $step1['email'],
                    'line_items'    => [[
                        'price'    => $priceId,
                        'quantity' => 1,
                    ]],
                    'success_url'   => $this->generateUrl('subscribe_success', ['session_id' => '{CHECKOUT_SESSION_ID}'], UrlGeneratorInterface::ABSOLUTE_URL),
                    'cancel_url'    => $this->generateUrl('subscribe_step4', [], UrlGeneratorInterface::ABSOLUTE_URL),
                    'metadata'      => [
                        'school_name' => $step1['school_name'],
                        'plan'        => $plan,
                        'billing_cycle' => $cycle,
                    ],
                    'subscription_data' => [
                        'trial_period_days' => 14,
                    ],
                ]);

                $request->getSession()->set('subscribe_checkout_session_id', $checkoutSession->id);

                return $this->redirect($checkoutSession->url);

            } catch (ApiErrorException $e) {
                $this->addFlash('error', 'Payment setup failed: ' . $e->getMessage());
            }
        }

        return $this->render('subscribe/step4.html.twig', [
            'summary' => $summary,
            'step'    => 4,
        ]);
    }

    #[Route('/trial/dashboard', name: 'trial_dashboard', methods: ['GET'])]
    public function trialDashboard(Request $request): Response
    {
        $portalContext = $request->getSession()->get('school_portal_context');

        if (!is_array($portalContext) || ($portalContext['status'] ?? null) !== 'trialing') {
            return $this->redirectToRoute('subscribe_step1');
        }

        $daysRemaining = (int) ($portalContext['trial_days_remaining'] ?? 0);
        $expired = (bool) ($portalContext['trial_expired'] ?? false);

        return $this->render('subscribe/trial_dashboard.html.twig', [
            'portalContext' => $portalContext,
            'daysRemaining' => $daysRemaining,
            'expired' => $expired,
            'limitedFeatures' => self::TRIAL_LIMITS,
        ]);
    }

    // ─── SUCCESS: Post-payment activation ─────────────────────────────────────

    #[Route('/success', name: 'success', methods: ['GET'])]
    public function success(
        Request $request,
        EntityManagerInterface $em,
        MailerInterface $mailer
    ): Response {
        $sessionId  = $request->query->get('session_id', '');
        $demoMode   = $request->getSession()->get('subscribe_demo_mode', false);

        $step1 = $request->getSession()->get('subscribe_step1');
        $step2 = $request->getSession()->get('subscribe_step2');
        $step3 = $request->getSession()->get('subscribe_step3');

        if (!$step1 || !$step2 || !$step3) {
            return $this->redirectToRoute('subscribe_step1');
        }

        $stripeSubId = null;
        $stripeCustomerId = null;

        if (!$demoMode && $sessionId && !str_starts_with($sessionId, 'demo_')) {
            $stripeKey = $_ENV['STRIPE_SECRET_KEY'] ?? '';
            if ($stripeKey && !str_starts_with($stripeKey, 'sk_test_YOUR')) {
                try {
                    $stripe   = new StripeClient($stripeKey);
                    $checkout = $stripe->checkout->sessions->retrieve($sessionId);
                    if ($checkout->payment_status !== 'paid' && $checkout->status !== 'complete') {
                        $this->addFlash('error', 'Payment not confirmed yet. Please try again.');
                        return $this->redirectToRoute('subscribe_step4');
                    }
                    $stripeSubId      = $checkout->subscription;
                    $stripeCustomerId = $checkout->customer;
                } catch (ApiErrorException $e) {
                    // Log and continue — don't block activation on Stripe API error
                }
            }
        }

        // Persist School entity
        $school = new School();
        $school->setName($step1['school_name']);
        $school->setEmail($step1['email']);
        $hasher = new NativePasswordHasher();
        $school->setPasswordHash($hasher->hash($step1['password']));
        $school->setAddress($step3['address'] ?? null);
        $school->setCountry($step3['country'] ?? null);
        $school->setTimezone($step3['timezone'] ?? null);
        $school->setPlan($step2['plan']);
        $school->setBillingCycle($step2['cycle']);
        $school->setStripeCustomerId($stripeCustomerId);
        $school->setStripeSubscriptionId($stripeSubId);
        $school->setStripeCheckoutSessionId($sessionId);
        $school->setStatus('active');
        $school->setActivatedAt(new \DateTimeImmutable());

        $this->persistSchool($em, $school);
        $this->storePortalContext($request, $school);

        $this->sendLifecycleEmail($mailer, $school, 'paid');

        $this->clearWizardSession($request);

        return $this->render('subscribe/success.html.twig', [
            'school_name'   => $school->getName(),
            'email'         => $school->getEmail(),
            'plan'          => $school->getPlan(),
            'billing_cycle' => $school->getBillingCycle(),
            'amount'        => self::PLAN_AMOUNTS[$school->getPlan()][$school->getBillingCycle()],
            'mode'          => 'paid',
        ]);
    }

    // ─── WEBHOOK: Stripe event handler ────────────────────────────────────────

    #[Route('/webhook', name: 'webhook', methods: ['POST'])]
    public function webhook(Request $request, EntityManagerInterface $em): Response
    {
        $payload    = $request->getContent();
        $sigHeader  = $request->headers->get('stripe-signature', '');
        $webhookSecret = $_ENV['STRIPE_WEBHOOK_SECRET'] ?? '';

        if (!$webhookSecret || !$sigHeader) {
            return new Response('Webhook secret not configured', 400);
        }

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return new Response('Invalid signature', 400);
        }

        switch ($event->type) {
            case 'checkout.session.completed':
                $session = $event->data->object;
                try {
                    $school = $em->getRepository(School::class)
                        ->findOneBy(['stripeCheckoutSessionId' => $session->id]);
                    if ($school) {
                        $school->setStatus('active');
                        $school->setActivatedAt(new \DateTimeImmutable());
                        $school->setStripeCustomerId($session->customer);
                        $school->setStripeSubscriptionId($session->subscription);
                        $em->flush();
                    }
                } catch (\Throwable $e) { /* DB not configured */ }
                break;

            case 'customer.subscription.deleted':
                $sub = $event->data->object;
                try {
                    $school = $em->getRepository(School::class)
                        ->findOneBy(['stripeSubscriptionId' => $sub->id]);
                    if ($school) {
                        $school->setStatus('cancelled');
                        $em->flush();
                    }
                } catch (\Throwable $e) { /* DB not configured */ }
                break;
        }

        return new Response('OK', 200);
    }

    private function normalizeMode(string $mode): string
    {
        return $mode === 'trial' ? 'trial' : 'paid';
    }

    private function createSchoolFromWizard(Request $request, string $status): School
    {
        $step1 = $request->getSession()->get('subscribe_step1', []);
        $step2 = $request->getSession()->get('subscribe_step2', []);
        $step3 = $request->getSession()->get('subscribe_step3', []);

        $school = new School();
        $school->setName((string) ($step1['school_name'] ?? 'School Admin Trial'));
        $school->setEmail((string) ($step1['email'] ?? 'trial@cityschool.local'));

        $hasher = new NativePasswordHasher();
        $school->setPasswordHash($hasher->hash((string) ($step1['password'] ?? bin2hex(random_bytes(8)))));
        $school->setAddress($step3['address'] ?? null);
        $school->setCountry($step3['country'] ?? null);
        $school->setTimezone($step3['timezone'] ?? null);
        $school->setPlan($step2['plan'] ?? 'Pro');
        $school->setBillingCycle($step2['cycle'] ?? 'monthly');
        $school->setStatus($status);

        if ($status === 'trialing') {
            $trialStartsAt = new \DateTimeImmutable();
            $school->setTrialStartsAt($trialStartsAt);
            $school->setTrialEndsAt($trialStartsAt->modify('+' . self::TRIAL_LENGTH_DAYS . ' days'));
        } else {
            $school->setActivatedAt(new \DateTimeImmutable());
        }

        return $school;
    }

    private function persistSchool(EntityManagerInterface $em, School $school): void
    {
        try {
            $em->persist($school);
            $em->flush();
        } catch (\Throwable $e) {
            // Database may not be configured in local preview; session fallback still works.
        }
    }

    private function storePortalContext(Request $request, School $school): void
    {
        $trialDaysRemaining = $school->getTrialDaysRemaining();

        $request->getSession()->set('school_portal_context', [
            'school_id' => $school->getId(),
            'school_name' => $school->getName(),
            'email' => $school->getEmail(),
            'plan' => $school->getPlan(),
            'billing_cycle' => $school->getBillingCycle(),
            'status' => $school->getStatus(),
            'trial_days_remaining' => $trialDaysRemaining,
            'trial_expired' => $school->isTrialExpired(),
            'trial_ends_at' => $school->getTrialEndsAt()?->format(DATE_ATOM),
        ]);
    }

    private function sendLifecycleEmail(MailerInterface $mailer, School $school, string $mode): void
    {
        try {
            $subject = $mode === 'trial'
                ? 'Your City School free trial is now active'
                : 'Welcome to City School SaaS – Subscription Confirmed';

            $email = (new Email())
                ->from($_ENV['TRIAL_REMINDER_FROM_EMAIL'] ?? 'no-reply@cityschool.edu')
                ->to($school->getEmail())
                ->subject($subject)
                ->html($this->renderView('subscribe/email_confirmation.html.twig', [
                    'school_name' => $school->getName(),
                    'plan' => $school->getPlan(),
                    'billing_cycle' => $school->getBillingCycle(),
                    'amount' => self::PLAN_AMOUNTS[$school->getPlan()][$school->getBillingCycle()],
                    'mode' => $mode,
                    'trialEndsAt' => $school->getTrialEndsAt(),
                    'trialLengthDays' => self::TRIAL_LENGTH_DAYS,
                ]));

            $mailer->send($email);
        } catch (\Throwable $e) {
            // Mailer may not be configured in local preview.
        }
    }

    private function clearWizardSession(Request $request): void
    {
        $session = $request->getSession();
        foreach (['subscribe_step1', 'subscribe_step2', 'subscribe_step3', 'subscribe_demo_mode', 'subscribe_checkout_session_id', 'subscribe_preferences'] as $key) {
            $session->remove($key);
        }
    }
}
