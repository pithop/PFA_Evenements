<?php
namespace App\Entity;

use App\Repository\InvitationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InvitationRepository::class)]
class Invitation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $guestEmail = null;
    
    // Statuts possibles: 'sent', 'accepted', 'declined', 'maybe' [cite: 30, 33]
    #[ORM\Column(length: 50)]
    private ?string $status = 'sent';

    #[ORM\ManyToOne(inversedBy: 'invitations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Event $event = null;
    
    // ... AJOUTEZ TOUS LES GETTERS ET SETTERS ICI ...
}