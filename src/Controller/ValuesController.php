<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

class ValuesController extends AbstractController
{
    #[Route('/values', name: 'values')]
    public function index()
    {
        return $this->render('values/index.html.twig', [
            'controller_name' => 'ValuesController',
        ]);
    }
}
