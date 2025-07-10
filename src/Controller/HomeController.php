<?php
// src/Controller/HomeController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        // If the user is already logged in, redirect them to the dashboard
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        // Otherwise, show the homepage
        return $this->render('home/index.html.twig');
    }
}