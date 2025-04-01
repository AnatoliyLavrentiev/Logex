<?php
// src/Entity/StoreProduct.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\StoreProductRepository;

#[ORM\Entity(repositoryClass: StoreProductRepository::class)]
#[ORM\Table(name: "store_product")]
class StoreProduct
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type:"integer")]
    private ?int $id = null;

    // Relation vers le produit
    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Product $product = null;

    // Quantité disponible en magasin
    #[ORM\Column(type:"integer")]
    private int $quantity = 0;

    #[ORM\Column(type:"datetime", nullable:true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type:"float", nullable: true)]
private ?float $unitPrice = null;

#[ORM\Column(type:"float", nullable: true)]
private ?float $unitWeight = null;

    // Getters et setters

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

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getUnitPrice(): ?float
{
    return $this->unitPrice;
}

public function setUnitPrice(?float $unitPrice): self
{
    $this->unitPrice = $unitPrice;
    return $this;
}

public function getUnitWeight(): ?float
{
    return $this->unitWeight;
}

public function setUnitWeight(?float $unitWeight): self
{
    $this->unitWeight = $unitWeight;
    return $this;
}
}
