<?php

namespace App\Entity;

use App\Repository\QueuedDepositRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QueuedDepositRepository::class)]
class QueuedDeposit
{
    public const STATUS_AWAITING_DEPOSIT = 'AWAITING_DEPOSIT';
    public const STATUS_PAYED_MORE = 'PAYED_MORE';
    public const STATUS_PAYED_LESS = 'PAYED_LESS';
    public const STATUS_PAYED_OK = 'PAYED_OK';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updated_at = null;

    #[ORM\ManyToOne(targetEntity: CryptoWallet::class, inversedBy: 'crypto_wallet')]
    #[ORM\JoinColumn(name: 'crypto_wallet_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?CryptoWallet $crypto_wallet = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2)]
    private ?string $amount = null;

    #[ORM\Column(length: 30)]
    private ?string $status = self::STATUS_AWAITING_DEPOSIT;

    #[ORM\Column(type: 'boolean')]
    private bool $cron_ignore = false;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCryptoWallet(): ?CryptoWallet
    {
        return $this->crypto_wallet;
    }

    public function setCryptoWallet(CryptoWallet $crypto_wallet): static
    {
        $this->crypto_wallet = $crypto_wallet;

        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        if (!in_array($status, [self::STATUS_PAYED_MORE, self::STATUS_PAYED_LESS, self::STATUS_PAYED_OK, self::STATUS_AWAITING_DEPOSIT])) {
            throw new \InvalidArgumentException('Invalid status');
        }

        $this->status = $status;
        return $this;
    }
    
    public function getCronIgnore(): bool
    {
        return $this->cron_ignore;
    }

    public function setCronIgnore(bool $cronIgnore): self
    {
        $this->cron_ignore = $cronIgnore;

        return $this;
    }
}
