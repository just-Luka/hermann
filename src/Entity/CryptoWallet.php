<?php

namespace App\Entity;

use App\Repository\CryptoWalletRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CryptoWalletRepository::class)]
class CryptoWallet
{
    // Coins && Networks
    public const COIN_NAME_USDT = 'USDT';
    public const NETWORK_TRC20 = 'TRC20';
    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updated_at = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'wallets')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    private ?User $user = null;

    #[ORM\Column(length: 50)]
    private ?string $coin_name = null;

    #[ORM\Column(length: 70)]
    private ?string $network = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $address_base58 = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $address_hex = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $private_key = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $public_key = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 30, scale: 8)]
    private ?string $balance = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 30, scale: 8)]
    private ?string $network_balance = '0';

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $last_transaction_at = null;

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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getCoinName(): ?string
    {
        return $this->coin_name;
    }

    public function setCoinName(string $coin_name): static
    {
        $this->coin_name = $coin_name;

        return $this;
    }

    public function getNetwork(): ?string
    {
        return $this->network;
    }

    public function setNetwork(string $network): static
    {
        $this->network = $network;

        return $this;
    }

    public function getAddressBase58(): ?string
    {
        return $this->address_base58;
    }

    public function setAddressBase58(string $address_base58): static
    {
        $this->address_base58 = $address_base58;

        return $this;
    }

    public function getAddressHex(): ?string
    {
        return $this->address_hex;
    }

    public function setAddressHex(string $address_hex): static
    {
        $this->address_hex = $address_hex;

        return $this;
    }

    public function getPrivateKey(): ?string
    {
        return $this->private_key;
    }

    public function setPrivateKey(string $private_key): static
    {
        $this->private_key = $private_key;

        return $this;
    }

    public function getPublicKey(): ?string
    {
        return $this->public_key;
    }

    public function setPublicKey(string $public_key): static
    {
        $this->public_key = $public_key;

        return $this;
    }

    public function getBalance(): ?string
    {
        return $this->balance;
    }

    public function setBalance(string $balance): static
    {
        $this->balance = $balance;

        return $this;
    }

    public function setNetworkBalance(string $network_balance): static
    {
        $this->network_balance = $network_balance;

        return $this;
    }

    public function getNetworkBalance(): ?string
    {
        return $this->network_balance;
    }

    public function getLastTransactionAt(): ?\DateTimeImmutable
    {
        return $this->last_transaction_at;
    }

    public function setLastTransactionAt(\DateTimeImmutable $last_transaction_at): static
    {
        $this->last_transaction_at = $last_transaction_at;

        return $this;
    }
}
