<?php
namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventRepository::class)]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(length: 255)]
    private ?string $location = null;

    #[ORM\ManyToOne(inversedBy: 'organizedEvents')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $organizer = null;

    #[ORM\OneToMany(mappedBy: 'event', targetEntity: Invitation::class, cascade: ['persist', 'remove'])]
    private Collection $invitations;

    #[ORM\OneToMany(mappedBy: 'event', targetEntity: Task::class, cascade: ['persist', 'remove'])]
    private Collection $tasks;

    public function __construct()
    {
        $this->invitations = new ArrayCollection();
        $this->tasks = new ArrayCollection();
    }

    // ... AJOUTEZ TOUS LES GETTERS ET SETTERS ICI ...
    // Astuce IDE : clic droit -> Generate -> Getters and Setters
}