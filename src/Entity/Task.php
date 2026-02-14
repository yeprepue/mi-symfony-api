<?php

namespace App\Entity;

use App\Repository\TaskRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TaskRepository::class)]
class Task
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $hoursSpent = '0.00';

    #[ORM\Column]
    private ?\DateTime $createdAt = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'tasks')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'tasks')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Project $project = null;

    // Campos para control de tiempo
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $startedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $lastResumeAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $finishedAt = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isRunning = false;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $accumulatedTime = '0.00';

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->hoursSpent = '0.00';
        $this->accumulatedTime = '0.00';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getHoursSpent(): ?string
    {
        return $this->hoursSpent;
    }

    public function setHoursSpent(string $hoursSpent): static
    {
        $this->hoursSpent = $hoursSpent;

        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): static
    {
        $this->project = $project;

        return $this;
    }

    // Getters y setters para control de tiempo

    public function getStartedAt(): ?\DateTime
    {
        return $this->startedAt;
    }

    public function setStartedAt(?\DateTime $startedAt): static
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getLastResumeAt(): ?\DateTime
    {
        return $this->lastResumeAt;
    }

    public function setLastResumeAt(?\DateTime $lastResumeAt): static
    {
        $this->lastResumeAt = $lastResumeAt;

        return $this;
    }

    public function getFinishedAt(): ?\DateTime
    {
        return $this->finishedAt;
    }

    public function setFinishedAt(?\DateTime $finishedAt): static
    {
        $this->finishedAt = $finishedAt;

        return $this;
    }

    public function isIsRunning(): bool
    {
        return $this->isRunning;
    }

    public function setIsRunning(bool $isRunning): static
    {
        $this->isRunning = $isRunning;

        return $this;
    }

    public function getAccumulatedTime(): ?string
    {
        return $this->accumulatedTime;
    }

    public function setAccumulatedTime(?string $accumulatedTime): static
    {
        $this->accumulatedTime = $accumulatedTime;

        return $this;
    }

    /**
     * Inicia el temporizador de la tarea
     */
    public function start(): void
    {
        $this->isRunning = true;
        $this->startedAt = new \DateTime();
        $this->lastResumeAt = new \DateTime();
    }

    /**
     * Pausa el temporizador de la tarea
     */
    public function pause(): void
    {
        if ($this->isRunning && $this->lastResumeAt) {
            $now = new \DateTime();
            $diff = $now->getTimestamp() - $this->lastResumeAt->getTimestamp();
            $hours = $diff / 3600;
            
            $currentAccumulated = floatval($this->accumulatedTime ?? '0');
            $this->accumulatedTime = number_format($currentAccumulated + $hours, 2, '.', '');
        }
        $this->isRunning = false;
    }

    /**
     * Detiene el temporizador y calcula las horas finales
     */
    public function stop(): void
    {
        if ($this->isRunning && $this->lastResumeAt) {
            $now = new \DateTime();
            $diff = $now->getTimestamp() - $this->lastResumeAt->getTimestamp();
            $hours = $diff / 3600;
            
            $currentAccumulated = floatval($this->accumulatedTime ?? '0');
            $this->accumulatedTime = number_format($currentAccumulated + $hours, 2, '.', '');
        }
        
        $this->isRunning = false;
        $this->finishedAt = new \DateTime();
        $this->hoursSpent = $this->accumulatedTime;
    }

    /**
     * Obtiene las horas actuales (incluyendo tiempo en curso)
     */
    public function getCurrentHours(): string
    {
        if ($this->isRunning && $this->lastResumeAt) {
            $now = new \DateTime();
            $diff = $now->getTimestamp() - $this->lastResumeAt->getTimestamp();
            $hours = $diff / 3600;
            
            $currentAccumulated = floatval($this->accumulatedTime ?? '0');
            return number_format($currentAccumulated + $hours, 2, '.', '');
        }
        
        return $this->accumulatedTime ?? '0.00';
    }
}
