<?php
// src/Form/ProductEditType.php
namespace App\Form;

use App\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductEditType extends AbstractType
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
            ->add('price', MoneyType::class, [
                'label' => 'Prix (€)',
                'currency' => 'EUR',
            ])
            ->add('weight', TextType::class, [
                'label' => 'Poids (kg)',
            ])
            ->add('category', TextType::class, [
                'label' => 'Catégorie',
            ])
            // ->add('description', TextareaType::class, [
            //     'label'    => 'Description',
            //     'required' => false,
            // ])
            ->add('imageFile', FileType::class, [ // Изменено с 'image' на 'imageFile'
                'label'    => 'Image du produit',
                'required' => false,
                'mapped'   => false, // Управляем загрузкой вручную
            ]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'     => Product::class,
            'csrf_protection'=> false, // Désactivez la protection CSRF pour ce formulaire imbriqué
        ]);
    }
}
