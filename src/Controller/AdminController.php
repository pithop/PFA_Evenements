<?php

namespace App\Controller;

use App\Repository\EventRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('', name: 'app_admin')]
    public function index(UserRepository $userRepo, EventRepository $eventRepo): Response
    {
        return $this->render('admin/index.html.twig', [
            'userCount' => $userRepo->count([]),
            'eventCount' => $eventRepo->count([]),
        ]);
    }
}