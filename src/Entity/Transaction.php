<?php

namespace App\Entity;

use App\Repository\TransactionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TransactionRepository::class)]
class Transaction
{
    // Define possible statuses and types as constants
    public const STATUS_COMPLETED = 'COMPLETED';
    public const STATUS_PROGRESS = 'PROGRESS';
    public const STATUS_RETURNED = 'RETURNED';

    public const TYPE_DEPOSIT = 'DEPOSIT';
    public const TYPE_WITHDRAW = 'WITHDRAW';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updated_at = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $ex_transaction_id = null;

    #[ORM\Column(length: 50)]
    private ?string $symbol = null;

    #[ORM\Column(length: 255)]
    private ?string $token_address = null;

    #[ORM\Column(type: Types::INTEGER)]
    private ?int $decimals = null;

    #[ORM\Column(type: Types::BIGINT)]
    private ?int $block_timestamp = null;

    #[ORM\Column(length: 255)]
    private ?string $from_address = null;

    #[ORM\Column(length: 255)]
    private ?string $to_address = null;

    #[ORM\Column(length: 50)]
    private ?string $ex_type = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 8)]
    private ?string $value = null;

    #[ORM\Column(length: 10)]
    private ?string $status = self::STATUS_PROGRESS;

    #[ORM\Column(length: 10)]
    private ?string $type = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): self
    {
        $this->created_at = $created_at;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(\DateTimeImmutable $updated_at): self
    {
        $this->updated_at = $updated_at;
        return $this;
    }

    public function getExTransactionId(): ?string
    {
        return $this->ex_transaction_id;
    }

    public function setExTransactionId(string $ex_transaction_id): self
    {
        $this->ex_transaction_id = $ex_transaction_id;
        return $this;
    }

    public function getSymbol(): ?string
    {
        return $this->symbol;
    }

    public function setSymbol(string $symbol): self
    {
        $this->symbol = $symbol;
        return $this;
    }

    public function getTokenAddress(): ?string
    {
        return $this->token_address;
    }

    public function setTokenAddress(string $token_address): self
    {
        $this->token_address = $token_address;
        return $this;
    }

    public function getDecimals(): ?int
    {
        return $this->decimals;
    }

    public function setDecimals(int $decimals): self
    {
        $this->decimals = $decimals;
        return $this;
    }

    public function getBlockTimestamp(): ?int
    {
        return $this->block_timestamp;
    }

    public function setBlockTimestamp(int $block_timestamp): self
    {
        $this->block_timestamp = $block_timestamp;
        return $this;
    }

    public function getFromAddress(): ?string
    {
        return $this->from_address;
    }

    public function setFrom(string $from_address): self
    {
        $this->from_address = $from_address;
        return $this;
    }

    public function getTo(): ?string
    {
        return $this->to_address;
    }

    public function setTo(string $to_address): self
    {
        $this->to_address = $to_address;
        return $this;
    }

    public function getExType(): ?string
    {
        return $this->ex_type;
    }

    public function setExType(string $ex_type): self
    {
        $this->ex_type = $ex_type;
        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(string $value): self
    {
        $this->value = $value;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        if (!in_array($status, [self::STATUS_COMPLETED, self::STATUS_PROGRESS, self::STATUS_RETURNED])) {
            throw new \InvalidArgumentException('Invalid status');
        }

        $this->status = $status;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        if (!in_array($type, [self::TYPE_DEPOSIT, self::TYPE_WITHDRAW])) {
            throw new \InvalidArgumentException('Invalid transaction type');
        }

        $this->type = $type;
        return $this;
    }
}
