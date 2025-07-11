<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Invitation;
use App\Entity\Task;
use App\Form\EventType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')] // Protège toutes les routes de ce contrôleur
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
    public function invite(Event $event, Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
    {
        if ($this->getUser() !== $event->getOrganizer()) {
            throw $this->createAccessDeniedException();
        }

        $emailAddress = $request->request->get('email');
        if ($emailAddress) {
            $invitation = new Invitation();
            $invitation->setEvent($event);
            $invitation->setGuestEmail($emailAddress);
            $invitation->setStatus('sent');
            $entityManager->persist($invitation);
            $entityManager->flush();
            $this->addFlash('success', "Invitation envoyée à $emailAddress.");

            // --- Logique d'envoi d'email ---
            $eventUrl = $this->generateUrl('event_show', ['id' => $event->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
            $email = (new Email())
                ->from('invite@eventplanner.com')
                ->to($emailAddress)
                ->subject('Vous êtes invité à l\'événement : ' . $event->getTitle())
                ->html($this->renderView('emails/invitation.html.twig', [
                    'event' => $event,
                    'event_url' => $eventUrl
                ]));

            try {
                $mailer->send($email);
            } catch (\Exception $e) {
                $this->addFlash('warning', 'L\'invitation a été enregistrée, mais l\'email n\'a pas pu être envoyé.');
            }
        }

        return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
    }

    #[Route('/invitation/{id}/{status}', name: 'invitation_rsvp')]
    public function rsvp(Invitation $invitation, string $status, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
    {
        if ($this->getUser()->getUserIdentifier() !== $invitation->getGuestEmail()) {
            throw $this->createAccessDeniedException();
        }

        if (in_array($status, ['accepted', 'declined', 'maybe'])) {
            $invitation->setStatus($status);
            $entityManager->flush();
            $this->addFlash('success', 'Votre réponse a bien été prise en compte.');

            // --- Logique d'envoi de notification à l'organisateur ---
            $organizer = $invitation->getEvent()->getOrganizer();
            $event = $invitation->getEvent();
            $email = (new Email())
                ->from('notifications@eventplanner.com')
                ->to($organizer->getEmail())
                ->subject('Nouvelle réponse pour votre événement : ' . $event->getTitle())
                ->html(sprintf(
                    '<p>%s a répondu "%s" à votre invitation pour l\'événement "%s".</p>',
                    $this->getUser()->getFirstName(),
                    $status,
                    $event->getTitle()
                ));
            
            try {
                $mailer->send($email);
            } catch (\Exception $e) {
                // Ne pas bloquer l'utilisateur si l'email ne part pas
            }
        }

        return $this->redirectToRoute('event_show', ['id' => $invitation->getEvent()->getId()]);
    }

    #[Route('/event/{id}/add-task', name: 'event_add_task', methods: ['POST'])]
    public function addTask(Event $event, Request $request, EntityManagerInterface $entityManager): Response
    {
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
        if ($this->getUser() !== $task->getEvent()->getOrganizer()) {
            throw $this->createAccessDeniedException();
        }
        
        $task->setIsDone(!$task->isIsDone());
        $entityManager->flush();

        return $this->redirectToRoute('event_show', ['id' => $task->getEvent()->getId()]);
    }
}
