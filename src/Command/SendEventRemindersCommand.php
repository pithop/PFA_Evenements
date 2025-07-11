<?php

namespace App\Command;

use App\Repository\EventRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(
    name: 'app:send-event-reminders',
    description: 'Finds events happening tomorrow and sends reminders to confirmed guests.',
)]
class SendEventRemindersCommand extends Command
{
    private $eventRepository;
    private $mailer;

    public function __construct(EventRepository $eventRepository, MailerInterface $mailer)
    {
        $this->eventRepository = $eventRepository;
        $this->mailer = $mailer;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $tomorrow = new \DateTime('tomorrow');
        $events = $this->eventRepository->createQueryBuilder('e')
            ->where('e.startDate >= :start')
            ->andWhere('e.startDate < :end')
            ->setParameter('start', $tomorrow->format('Y-m-d 00:00:00'))
            ->setParameter('end', $tomorrow->format('Y-m-d 23:59:59'))
            ->getQuery()
            ->getResult();

        if (empty($events)) {
            $io->info('No events happening tomorrow. No reminders sent.');
            return Command::SUCCESS;
        }

        $io->info(sprintf('Found %d event(s) for tomorrow. Sending reminders...', count($events)));

        foreach ($events as $event) {
            $confirmedGuests = $event->getInvitations()->filter(fn($i) => $i->getStatus() === 'accepted');
            
            if ($confirmedGuests->isEmpty()) {
                continue;
            }

            $guestEmails = $confirmedGuests->map(fn($i) => $i->getGuestEmail())->toArray();

            $email = (new Email())
                ->from('reminders@eventplanner.com')
                ->to(...$guestEmails)
                ->subject('Rappel : L\'événement "' . $event->getTitle() . '" est demain !')
                ->html(sprintf('<p>Ceci est un rappel pour l\'événement <strong>%s</strong> qui a lieu demain à %s.</p>', $event->getTitle(), $event->getStartDate()->format('H:i')));

            $this->mailer->send($email);
            $io->success(sprintf('Reminder sent for "%s" to %d guest(s).', $event->getTitle(), count($guestEmails)));
        }

        return Command::SUCCESS;
    }
}
