<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

class EnrollmentController extends AbstractController
{
    #[Route('/admissions', name: 'admissions')]
    public function index()
    {
        return $this->render('enrollment/index.html.twig', [
            'controller_name' => 'EnrollmentController',
        ]);
    }
}
