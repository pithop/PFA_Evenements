<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Invitation;
use App\Form\EventType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\Task;
use App\Repository\TaskRepository;

#[IsGranted('ROLE_USER')] // Protect all routes in this controller
class EventController extends AbstractController
{
    #[Route('/event/new', name: 'event_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $event = new Event();
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $event->setOrganizer($this->getUser());
            $entityManager->persist($event);
            $entityManager->flush();

            $this->addFlash('success', 'Événement créé avec succès !');
            return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
        }

        return $this->render('event/new.html.twig', [
            'eventForm' => $form->createView(),
        ]);
    }

    #[Route('/event/{id}', name: 'event_show')]
    public function show(Event $event): Response
    {
        return $this->render('event/show.html.twig', [
            'event' => $event,
        ]);
    }

    #[Route('/event/{id}/invite', name: 'event_invite', methods: ['POST'])]
    public function invite(Event $event, Request $request, EntityManagerInterface $entityManager): Response
    {
        // Security check
        if ($this->getUser() !== $event->getOrganizer()) {
            throw $this->createAccessDeniedException();
        }

        $email = $request->request->get('email');
        if ($email) {
            $invitation = new Invitation();
            $invitation->setEvent($event);
            $invitation->setGuestEmail($email);
            $invitation->setStatus('sent');
            $entityManager->persist($invitation);
            $entityManager->flush();
            $this->addFlash('success', "Invitation envoyée à $email.");
        }

        return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
    }

    #[Route('/invitation/{id}/{status}', name: 'invitation_rsvp')]
    public function rsvp(Invitation $invitation, string $status, EntityManagerInterface $entityManager): Response
    {
        // Security check
        if ($this->getUser()->getUserIdentifier() !== $invitation->getGuestEmail()) {
            throw $this->createAccessDeniedException();
        }

        if (in_array($status, ['accepted', 'declined', 'maybe'])) {
            $invitation->setStatus($status);
            $entityManager->flush();
            $this->addFlash('success', 'Votre réponse a bien été prise en compte.');
        }

        return $this->redirectToRoute('event_show', ['id' => $invitation->getEvent()->getId()]);
    }
    #[Route('/event/{id}/add-task', name: 'event_add_task', methods: ['POST'])]
    public function addTask(Event $event, Request $request, EntityManagerInterface $entityManager): Response
    {
        // Sécurité : seul l'organisateur peut ajouter une tâche
        if ($this->getUser() !== $event->getOrganizer()) {
            throw $this->createAccessDeniedException();
        }

        $taskName = $request->request->get('task_name');
        if ($taskName) {
            $task = new Task();
            $task->setEvent($event);
            $task->setName($taskName);
            $task->setIsDone(false);
            $entityManager->persist($task);
            $entityManager->flush();
            $this->addFlash('success', 'Tâche ajoutée avec succès.');
        }

        return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
    }

    #[Route('/task/{id}/toggle', name: 'task_toggle')]
    public function toggleTaskStatus(Task $task, EntityManagerInterface $entityManager): Response
    {
        // Sécurité : seul l'organisateur peut modifier une tâche de son événement
        if ($this->getUser() !== $task->getEvent()->getOrganizer()) {
            throw $this->createAccessDeniedException();
        }
        
        $task->setIsDone(!$task->isIsDone());
        $entityManager->flush();

        return $this->redirectToRoute('event_show', ['id' => $task->getEvent()->getId()]);
    }
}