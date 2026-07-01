<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\NativePasswordHasher;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/portal/register', name: 'portal_register', methods: ['GET', 'POST'])]
    public function studentRegister(Request $request): Response
    {
        return $this->handleRegistration(
            request: $request,
            role: 'student',
            template: 'security/student_register.html.twig',
            loginRoute: 'portal_login'
        );
    }

    #[Route('/parent/register', name: 'parent_register', methods: ['GET', 'POST'])]
    public function parentRegister(Request $request): Response
    {
        return $this->handleRegistration(
            request: $request,
            role: 'parent',
            template: 'security/parent_register.html.twig',
            loginRoute: 'parent_login'
        );
    }

    private function handleRegistration(Request $request, string $role, string $template, string $loginRoute): Response
    {
        $errors = [];
        $data = [
            'full_name' => '',
            'email' => '',
            'username' => '',
            'class_level' => '',
            'guardian_email' => '',
            'child_name' => '',
            'child_class' => '',
        ];

        if ($request->isMethod('POST')) {
            $data['full_name'] = trim((string) $request->request->get('full_name', ''));
            $data['email'] = trim((string) $request->request->get('email', ''));
            $data['username'] = trim((string) $request->request->get('username', ''));
            $data['class_level'] = trim((string) $request->request->get('class_level', ''));
            $data['guardian_email'] = trim((string) $request->request->get('guardian_email', ''));
            $data['child_name'] = trim((string) $request->request->get('child_name', ''));
            $data['child_class'] = trim((string) $request->request->get('child_class', ''));

            $password = (string) $request->request->get('password', '');
            $confirmPassword = (string) $request->request->get('password_confirm', '');

            if ($data['full_name'] === '') {
                $errors['full_name'] = 'Full name is required.';
            }

            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'A valid email is required.';
            }

            if ($data['username'] === '' || !preg_match('/^[a-zA-Z0-9._-]{3,30}$/', $data['username'])) {
                $errors['username'] = 'Username must be 3-30 chars and can include letters, numbers, dot, underscore or hyphen.';
            }

            if (strlen($password) < 8) {
                $errors['password'] = 'Password must be at least 8 characters.';
            }

            if ($password !== $confirmPassword) {
                $errors['password_confirm'] = 'Passwords do not match.';
            }

            if ($role === 'student' && $data['class_level'] === '') {
                $errors['class_level'] = 'Class level is required for student registration.';
            }

            if ($role === 'parent' && $data['child_name'] === '') {
                $errors['child_name'] = 'Child name is required for parent registration.';
            }

            if ($role === 'parent' && $data['child_class'] === '') {
                $errors['child_class'] = 'Child class is required for parent registration.';
            }

            if ($data['guardian_email'] !== '' && !filter_var($data['guardian_email'], FILTER_VALIDATE_EMAIL)) {
                $errors['guardian_email'] = 'Guardian email must be valid.';
            }

            $store = $this->readRegistrationStore();
            $pending = $store['pending'];
            foreach ($pending as $existing) {
                if (($existing['email'] ?? '') === $data['email']) {
                    $errors['email'] = 'A request with this email is already pending approval.';
                    break;
                }
            }

            if (!$errors) {
                $pending[] = [
                    'id' => 'REQ-' . strtoupper(substr(md5((string) microtime(true)), 0, 8)),
                    'role' => $role,
                    'full_name' => $data['full_name'],
                    'email' => $data['email'],
                    'username' => $data['username'],
                    'password_hash' => (new NativePasswordHasher())->hash($password),
                    'class_level' => $data['class_level'],
                    'guardian_email' => $data['guardian_email'],
                    'child_name' => $data['child_name'],
                    'child_class' => $data['child_class'],
                    'status' => 'pending_approval',
                    'created_at' => (new \DateTimeImmutable())->format('Y-m-d H:i'),
                ];

                $store['pending'] = $pending;
                $this->writeRegistrationStore($store);
                $this->addFlash('success', 'Registration request submitted successfully. Admin approval is required before first login.');

                return $this->redirectToRoute($loginRoute);
            }
        }

        return $this->render($template, [
            'errors' => $errors,
            'data' => $data,
        ]);
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
}
