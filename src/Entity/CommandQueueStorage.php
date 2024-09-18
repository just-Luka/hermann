<?php

namespace App\Entity;

use App\Repository\CommandQueueStorageRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\User;
use Doctrine\DBAL\Types\Types;

#[ORM\Entity(repositoryClass: CommandQueueStorageRepository::class)]
class CommandQueueStorage
{
    // Command - /open
    public const QUESTION_SEARCH_ASSET = 'SEARCH_ASSET';
    public const QUESTION_CHOOSING_ASSET = 'CHOOSING_ASSET';
    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: User::class)] // Create a OneToOne relationship with the User entity
    #[ORM\JoinColumn(nullable: false)] // This ensures the user_id is not null
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    private ?string $command_name = null;

    #[ORM\Column(length: 255)]
    private ?string $last_question = null;

    #[ORM\Column(type: Types::JSON)]
    private array $instructions = [];

    #[ORM\Column]
    private ?int $count = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updated_at = null;

    // Getters and Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getCommandName(): ?string
    {
        return $this->command_name;
    }

    public function setCommandName(string $command_name): static
    {
        $this->command_name = $command_name;

        return $this;
    }

    public function getLastQuestion(): ?string
    {
        return $this->last_question;
    }

    public function setLastQuestion(string $last_question): static
    {
        $this->last_question = $last_question;

        return $this;
    }

    public function getInstructions(): array
    {
        return $this->instructions;
    }

    public function setInstructions(array $instructions): static
    {
        $this->instructions = $instructions;

        return $this;
    }

    public function getCount(): ?int
    {
        return $this->count;
    }

    public function setCount(int $count): static
    {
        $this->count = $count;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(\DateTimeImmutable $updated_at): static
    {
        $this->updated_at = $updated_at;

        return $this;
    }
}