<?php

// src/Repository/ProductRepository.php
namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * Retourne un tableau associatif de catégories distinctes.
     * Exemple : [ 'Zuku' => 'Zuku', 'Autre' => 'Autre', ... ]
     */
    public function findDistinctCategories(): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('DISTINCT p.category AS category')
            ->where('p.category IS NOT NULL');
        $results = $qb->getQuery()->getScalarResult();
        $categories = [];
        foreach ($results as $result) {
            $categories[$result['category']] = $result['category'];
        }
        return $categories;
    }
}
