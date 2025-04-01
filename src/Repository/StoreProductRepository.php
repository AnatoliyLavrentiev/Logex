<?php
// src/Repository/StoreProductRepository.php

namespace App\Repository;

use App\Entity\StoreProduct;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StoreProduct>
 *
 * @method StoreProduct|null find($id, $lockMode = null, $lockVersion = null)
 * @method StoreProduct|null findOneBy(array $criteria, array $orderBy = null)
 * @method StoreProduct[]    findAll()
 * @method StoreProduct[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StoreProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
         parent::__construct($registry, StoreProduct::class);
    }

    // Ajoutez ici vos méthodes personnalisées si nécessaire
}
