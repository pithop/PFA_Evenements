<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\EventRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
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
        // We fetch all users to display them in a table
        $users = $userRepo->findAll();

        return $this->render('admin/index.html.twig', [
            'userCount' => count($users),
            'eventCount' => $eventRepo->count([]),
            'users' => $users,
        ]);
    }

    #[Route('/user/{id}/toggle-active', name: 'admin_user_toggle_active')]
    public function toggleUserActive(User $user, EntityManagerInterface $em): Response
    {
        // This function toggles the isActive status of a user
        $user->setIsActive(!$user->isIsActive());
        $em->flush();

        $this->addFlash('success', 'Le statut de l\'utilisateur a été mis à jour.');
        
        return $this->redirectToRoute('app_admin');
    }
}
