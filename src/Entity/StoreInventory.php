<?php
// src/Entity/StoreInventory.php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "store_inventory")]
class StoreInventory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type:"integer")]
    private ?int $id = null;

    // Ссылка на товар, доставленный в магазин
    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Product $product = null;

    // Количество доставленного товара
    #[ORM\Column(type:"integer")]
    private int $quantity = 0;

    // Дата доставки (или обновления наличия в магазине)
    #[ORM\Column(type:"datetime")]
    private \DateTimeInterface $deliveredAt;

    // Геттеры и сеттеры

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(Product $product): self
    {
        $this->product = $product;
        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function getDeliveredAt(): \DateTimeInterface
    {
        return $this->deliveredAt;
    }

    public function setDeliveredAt(\DateTimeInterface $deliveredAt): self
    {
        $this->deliveredAt = $deliveredAt;
        return $this;
    }
}
