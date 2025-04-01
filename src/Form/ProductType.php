<?php
// src/Form/ProductType.php
namespace App\Form;

use App\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('prodname', TextType::class, [
                'label' => 'Nom du produit',
            ])
            ->add('reference', TextType::class, [
                'label' => 'Référence du produit',
            ])
            ->add('description', TextType::class, [
                'label' => 'Description',
            ])
            ->add('price', TextType::class, [
                'label' => 'Prix (€)',
            ])
            ->add('weight', TextType::class, [
                'label' => 'Poids (kg)',
            ])
            ->add('category', TextType::class, [
                'label' => 'Catégorie',
            ])
            ->add('imageFile', FileType::class, [
                'label'    => 'Image du produit',
                'mapped'   => false, // Ce champ n'est pas lié directement à l'entité
                'required' => false,
                'attr' => ['accept' => 'image/*']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
         $resolver->setDefaults([
             'data_class' => Product::class,
         ]);
    }
}
