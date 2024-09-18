<?php

namespace App\Entity;

use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updated_at = null;

    #[ORM\Column(length: 255)]
    private ?string $first_name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $last_name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $username = null;

    #[ORM\Column(length: 20)]
    private ?string $telegram_id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photo_url = null;

    #[ORM\Column(type: Types::BIGINT, nullable: true)]
    private ?string $telegram_auth_date = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $telegram_hash = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, options: ['default' => 0])]
    private float $balance = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->first_name;
    }

    public function setFirstName(string $first_name): static
    {
        $this->first_name = $first_name;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->last_name;
    }

    public function setLastName(?string $last_name): static
    {
        $this->last_name = $last_name;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getTelegramId(): ?string
    {
        return $this->telegram_id;
    }

    public function setTelegramId(string $telegram_id): static
    {
        $this->telegram_id = $telegram_id;

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

    public function getPhotoUrl(): ?string
    {
        return $this->photo_url;
    }

    public function setPhotoUrl(?string $photo_url): static
    {
        $this->photo_url = $photo_url;

        return $this;
    }

    public function getTelegramAuthDate(): ?string
    {
        return $this->telegram_auth_date;
    }

    public function setTelegramAuthDate(?string $telegram_auth_date): static
    {
        $this->telegram_auth_date = $telegram_auth_date;

        return $this;
    }

    public function getTelegramHash(): ?string
    {
        return $this->telegram_hash;
    }

    public function setTelegramHash(?string $telegram_hash): static
    {
        $this->telegram_hash = $telegram_hash;

        return $this;
    }

    // Getter for balance
    public function getBalance(): float
    {
        return (float) $this->balance;
    }

    // Setter for balance
    public function setBalance(float $balance): static
    {
        $this->balance = $balance;

        return $this;
    }

    public function getRoles(): array
    {
        // Return the roles or default to ROLE_USER
        return ['ROLE_USER'];
    }

    public function eraseCredentials(): void
    {
        // If you store any temporary sensitive data, clear it here
    }

    public function getUserIdentifier(): string
    {
        return $this->username;   
    }

    /**
    * @ORM\PrePersist
    */
    public function onPrePersist(): void
    {
        $this->created_at = new DateTimeImmutable();
        $this->updated_at = new DateTimeImmutable();
    }

    /**
     * @ORM\PreUpdate
     */
    public function onPreUpdate(): void
    {
        $this->updated_at = new DateTimeImmutable();
    }
}
