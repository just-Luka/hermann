<?php

namespace App\Entity;

use App\Repository\CapitalSecurityRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CapitalSecurityRepository::class)]
class CapitalSecurity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $cst = null;

    #[ORM\Column(length: 255)]
    private ?string $x_security_token = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updated_at = null;

    public function getId(): ?int
    {
        return $this->id;
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
