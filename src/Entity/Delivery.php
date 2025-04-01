<?php
// src/Entity/Delivery.php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "delivery")]
class Delivery
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type:"integer")]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: "delivery", targetEntity: Order::class)]
    #[ORM\JoinColumn(name: "order_id", referencedColumnName: "id", nullable: false)]
    private Order $order;

    #[ORM\Column(type:"string", length:255)]
    private string $address;

    #[ORM\Column(type:"datetime", nullable:true)]
    private ?\DateTimeInterface $shippedAt = null;

    #[ORM\Column(type:"string", length:100, nullable:true)]
    private ?string $trackingNumber = null;

    // Значение по умолчанию "En Cours" – доставка только что создана
    #[ORM\Column(type:"string", length:50)]
    private string $status = 'En Cours';

    // Поле для даты доставки (будет заполнено при приёме доставки)
    #[ORM\Column(type:"datetime", nullable:true)]
    private ?\DateTimeInterface $deliveredAt = null;

    // Геттеры и сеттеры

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrder(): Order
    {
        return $this->order;
    }

    public function setOrder(Order $order): self
    {
        $this->order = $order;
        return $this;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function setAddress(string $address): self
    {
        $this->address = $address;
        return $this;
    }

    public function getShippedAt(): ?\DateTimeInterface
    {
        return $this->shippedAt;
    }

    public function setShippedAt(?\DateTimeInterface $shippedAt): self
    {
        $this->shippedAt = $shippedAt;
        return $this;
    }

    public function getTrackingNumber(): ?string
    {
        return $this->trackingNumber;
    }

    public function setTrackingNumber(?string $trackingNumber): self
    {
        $this->trackingNumber = $trackingNumber;
        return $this;
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

    public function getDeliveredAt(): ?\DateTimeInterface
    {
        return $this->deliveredAt;
    }

    public function setDeliveredAt(?\DateTimeInterface $deliveredAt): self
    {
        $this->deliveredAt = $deliveredAt;
        return $this;
    }
}
