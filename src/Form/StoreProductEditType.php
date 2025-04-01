<?php
// src/Form/StoreProductEditType.php
namespace App\Form;

use App\Entity\StoreProduct;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Form\ProductEditType;

class StoreProductEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Formulaire imbriqué pour modifier les informations du produit ;
            // la protection CSRF est désactivée dans ProductEditType
            ->add('product', ProductEditType::class, [
                'label' => false,
            ])
            ->add('quantity', IntegerType::class, [
                'label' => 'Quantité en stock',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'    => StoreProduct::class,
            'csrf_protection' => false, // 🔥 Добавь это!
        ]);
    }
}
