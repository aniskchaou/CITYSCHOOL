<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/super-admin/login', name: 'super_admin_login')]
    public function superAdminLogin(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            return $this->redirectToRoute('super_admin_dashboard');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/super_admin_login.html.twig', [
            'last_username' => $lastUsername,
            'error'         => $error,
        ]);
    }

    #[Route('/super-admin/logout', name: 'super_admin_logout', methods: ['GET'])]
    public function superAdminLogout(): void
    {
        // Intercepted by Symfony security firewall
    }

    #[Route('/portal/login', name: 'portal_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('portal_dashboard');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error'         => $error,
        ]);
    }

    #[Route('/portal/logout', name: 'portal_logout', methods: ['GET'])]
    public function logout(): void
    {
        // Intercepted by Symfony security firewall
    }

        #[Route('/teacher/login', name: 'teacher_login')]
        public function teacherLogin(AuthenticationUtils $authenticationUtils): Response
        {
            if ($this->getUser()) {
                return $this->redirectToRoute('teacher_dashboard');
            }

            $error        = $authenticationUtils->getLastAuthenticationError();
            $lastUsername = $authenticationUtils->getLastUsername();

            return $this->render('security/teacher_login.html.twig', [
                'last_username' => $lastUsername,
                'error'         => $error,
            ]);
        }

        #[Route('/teacher/logout', name: 'teacher_logout', methods: ['GET'])]
        public function teacherLogout(): void
        {
            // Intercepted by Symfony security firewall
        }

    #[Route('/admin/login', name: 'admin_login')]
    public function adminLogin(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('admin_dashboard');
        }

        $error        = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/admin_login.html.twig', [
            'last_username' => $lastUsername,
            'error'         => $error,
        ]);
    }

    #[Route('/admin/logout', name: 'admin_logout', methods: ['GET'])]
    public function adminLogout(): void
    {
        // Intercepted by Symfony security firewall
    }

    #[Route('/parent/login', name: 'parent_login')]
    public function parentLogin(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('parent_dashboard');
        }

        $error        = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/parent_login.html.twig', [
            'last_username' => $lastUsername,
            'error'         => $error,
        ]);
    }

    #[Route('/parent/logout', name: 'parent_logout', methods: ['GET'])]
    public function parentLogout(): void
    {
        // Intercepted by Symfony security firewall
    }
}
