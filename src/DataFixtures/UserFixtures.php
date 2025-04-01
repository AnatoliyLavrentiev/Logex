<?php
// src/DataFixtures/UserFixtures.php

namespace App\DataFixtures;

use App\Entity\Utilisateur;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;
    
    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }
    
    public function load(ObjectManager $manager): void
    {
        // Création de l'administrateur avec login "Admin" et mot de passe "1706"
        $admin = new Utilisateur();
        $admin->setUsername('Admin');
        $admin->setPassword($this->passwordHasher->hashPassword($admin, '1706'));
        $admin->setRoles(['ROLE_ADMIN']);
        $manager->persist($admin);
        
        // Création de l'utilisateur Hugo
        $hugo = new Utilisateur();
        $hugo->setUsername('Hugo');
        $hugo->setPassword($this->passwordHasher->hashPassword($hugo, '1706'));
        $hugo->setRoles(['ROLE_USER']);
        $manager->persist($hugo);
        
        // Création de l'utilisateur Thibault
        $thibault = new Utilisateur();
        $thibault->setUsername('Thibault');
        $thibault->setPassword($this->passwordHasher->hashPassword($thibault, 'password'));
        $thibault->setRoles(['ROLE_USER']);
        $manager->persist($thibault);
        
        // Création de l'utilisateur Marie
        $marie = new Utilisateur();
        $marie->setUsername('Marie');
        $marie->setPassword($this->passwordHasher->hashPassword($marie, 'password'));
        $marie->setRoles(['ROLE_USER']);
        $manager->persist($marie);
        
        $manager->flush();
    }
}
