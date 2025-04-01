<?php
// src/Entity/Order.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
#[ORM\Table(name: "orders")]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type:"integer")]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 20, unique: true)]
    private ?string $orderNumber = null;

    #[ORM\Column(type:"datetime")]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type:"string", length:50)]
    private string $status = 'new';

    #[ORM\Column(type:"json", nullable:true)]
    private ?array $clientData = null;

    #[ORM\Column(type:"float")]
    private float $totalAmount = 0.0;

    #[ORM\ManyToOne(targetEntity: Shop::class, inversedBy: "orders")]
    #[ORM\JoinColumn(nullable: false)]
    private ?Shop $shop = null;

    #[ORM\OneToMany(mappedBy: "order", targetEntity: OrderItem::class, cascade: ["persist"], orphanRemoval: true)]
    private Collection $orderItems;

    #[ORM\OneToOne(mappedBy: "order", targetEntity: Delivery::class, cascade: ["persist", "remove"])]
    private ?Delivery $delivery = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->orderItems = new ArrayCollection();
    }

    // ... (getters et setters existants)

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getClientData(): ?array
    {
        return $this->clientData;
    }

    public function setClientData(?array $clientData): self
    {
        $this->clientData = $clientData;
        return $this;
    }

    public function getTotalAmount(): float
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(float $totalAmount): self
    {
        $this->totalAmount = $totalAmount;
        return $this;
    }

    public function getShop(): ?Shop
    {
        return $this->shop;
    }

    public function setShop(Shop $shop): self
    {
        $this->shop = $shop;
        return $this;
    }

    /**
     * @return Collection|OrderItem[]
     */
    public function getOrderItems(): Collection
    {
        return $this->orderItems;
    }

    public function addOrderItem(OrderItem $orderItem): self
    {
        if (!$this->orderItems->contains($orderItem)) {
            $this->orderItems[] = $orderItem;
            $orderItem->setOrder($this);
        }
        return $this;
    }

    public function removeOrderItem(OrderItem $orderItem): self
    {
        if ($this->orderItems->removeElement($orderItem)) {
            if ($orderItem->getOrder() === $this) {
                $orderItem->setOrder(null);
            }
        }
        return $this;
    }

    public function getDelivery(): ?Delivery
    {
        return $this->delivery;
    }

    public function setDelivery(?Delivery $delivery): self
    {
        $this->delivery = $delivery;
        return $this;
    }

    public function getOrderNumber(): ?string
{
    return $this->orderNumber;
}

public function setOrderNumber(string $orderNumber): self
{
    $this->orderNumber = $orderNumber;
    return $this;
}

    // Méthode pour calculer la TVA à 20%
    public function getTVA(): float
    {
        return $this->totalAmount * 0.20;
    }
}
