<?php
// src/Form/StoreProductType.php

namespace App\Form;

use App\Entity\StoreProduct;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StoreProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Nous ne modifions ici que la quantité en stock,
        // car les autres informations proviennent normalement du produit
        $builder
            ->add('quantity', IntegerType::class, [
                'label' => 'Quantité en stock',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => StoreProduct::class,
        ]);
    }
}
