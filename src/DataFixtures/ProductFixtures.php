<?php
// src/DataFixtures/ProductFixtures.php

namespace App\DataFixtures;

use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ProductFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $nomsProduits = [
            "Riz Basmati Auchan 1kg",
            "Farine de blé Francine 1kg",
            "Pommes Golden Bio 1kg",
            "Banane Cavendish 1kg",
            "Tomates Cerise 500g",
            "Carottes Bio 1kg",
            "Brocoli Frais 500g",
            "Fromage Cheddar 200g",
            "Lait Entier 1L",
            "Yaourt Nature 500g",
            "Pain Complet 500g",
            "Œufs Fermiers 12pcs",
            "Beurre Doux 250g",
            "Jus d'Orange 1L",
            "Huile d'Olive Vierge Extra 500ml",
            "Spaghettis Italiens 500g",
            "Chocolat Noir 100g",
            "Miel de Fleurs 500g",
            "Café Moulu 250g",
            "Amandes Grillées 200g"
        ];
        
        $descriptions = [
            "Produit de qualité, parfait pour vos plats.",
            "Produit frais et naturel, idéal pour une alimentation saine.",
            "Sélectionné pour son goût exceptionnel et son authenticité.",
            "Produit local, élaboré dans le respect des traditions.",
            "Excellente qualité, recommandé pour toute la famille."
        ];
        
        $categories = [
            "Épicerie", "Boulangerie", "Fruits", "Légumes", "Produits Laitiers",
            "Viandes", "Poissons", "Boissons", "Confiseries", "Produits Frais"
        ];
        
        for ($i = 0; $i < 100; $i++) {
            $produit = new Product();
            $nom = $nomsProduits[array_rand($nomsProduits)];
            $produit->setProdname($nom);
            $produit->setReference(rand(100000, 999999));
            $produit->setDescription($descriptions[array_rand($descriptions)]);
            $prix = round(mt_rand(100, 5000) / 100, 2);
            $produit->setPrice($prix);
            $poids = round(mt_rand(50, 250) / 100, 2);
            $produit->setWeight($poids);
            $produit->setCategory($categories[array_rand($categories)]);
            
            $manager->persist($produit);
        }
        
        $manager->flush();
    }
}
