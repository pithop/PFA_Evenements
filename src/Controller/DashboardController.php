<?php

namespace App\Controller;

use App\Repository\EventRepository;
use App\Repository\InvitationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(EventRepository $eventRepo, InvitationRepository $invitationRepo): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // Fetch events organized by the user
        $myOrganizedEvents = $eventRepo->findBy(['organizer' => $user]);

        // Fetch invitations for the user
        $myInvitations = $invitationRepo->findBy(['guestEmail' => $user->getEmail()]);

        return $this->render('dashboard/index.html.twig', [
            'organizedEvents' => $myOrganizedEvents,
            'invitations' => $myInvitations,
        ]);
    }
}