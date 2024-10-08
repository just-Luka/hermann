<?php

namespace App\Entity;

use App\Repository\CapitalAccountRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CapitalAccountRepository::class)]
#[ORM\HasLifecycleCallbacks]
class CapitalAccount
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $is_main = false;

    #[ORM\Column(type: 'string', unique: false)] // allows duplicates
    private ?string $email = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $account_name = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private ?float $available_balance = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private ?float $allocated_balance = null;

    #[ORM\Column(type: 'integer')]
    private ?int $assigned_users_count = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cst = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $x_security_token = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $restrict_user_assign = false;

    #[ORM\Column(type: 'string', length: 50)]
    private ?string $api_identifier = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $created_at = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updated_at = null;

    // Getters and setters for each field

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIsMain(): bool
    {
        return $this->is_main;
    }

    public function setIsMain(bool $is_main): self
    {
        $this->is_main = $is_main;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getAccountName(): ?string
    {
        return $this->account_name;
    }

    public function setAccountName(?string $account_name): self
    {
        $this->account_name = $account_name;
        return $this;
    }

    public function getAvailableBalance(): ?float
    {
        return $this->available_balance;
    }

    public function setAvailableBalance(?float $available_balance): self
    {
        $this->available_balance = $available_balance;
        return $this;
    }

    public function getAllocatedBalance(): ?float
    {
        return $this->allocated_balance;
    }

    public function setAllocatedBalance(?float $allocated_balance): self
    {
        $this->allocated_balance = $allocated_balance;
        return $this;
    }

    public function getAssignedUsersCount(): ?int
    {
        return $this->assigned_users_count;
    }

    public function setAssignedUsersCount(?int $assigned_users_count): self
    {
        $this->assigned_users_count = $assigned_users_count;
        return $this;
    }

    public function getCst(): ?string
    {
        return $this->cst;
    }

    public function setCst(string $cst): static
    {
        $this->cst = $cst;

        return $this;
    }

    public function getXSecurityToken(): ?string
    {
        return $this->x_security_token;
    }

    public function setXSecurityToken(string $x_security_token): static
    {
        $this->x_security_token = $x_security_token;

        return $this;
    }

    public function getRestrictUserAssign(): bool
    {
        return $this->restrict_user_assign;
    }

    public function setRestrictUserAssign(bool $restrict_user_assign): self
    {
        $this->restrict_user_assign = $restrict_user_assign;
        return $this;
    }

    public function getApiIdentifier(): ?string
    {
        return $this->api_identifier;
    }

    public function setApiIdentifier(?string $api_identifier): self
    {
        $this->api_identifier = $api_identifier;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(?\DateTimeInterface $created_at): self
    {
        $this->created_at = $created_at;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(?\DateTimeInterface $updated_at): self
    {
        $this->updated_at = $updated_at;
        return $this;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->created_at = new \DateTime();
        $this->updated_at = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updated_at = new \DateTime();
    }
}
