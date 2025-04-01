<?php
// src/Form/WarehouseType.php
namespace App\Form;

use App\Entity\Warehouse;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WarehouseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de l\'entrepôt',
            ])
            ->add('location', TextType::class, [
                'label'    => 'Ville',
                'required' => false,
            ])
            ->add('adresse', TextType::class, [
                'label'    => 'Adresse',
                'required' => false,
            ])
            ->add('capaciteStockage', IntegerType::class, [
                'label'    => 'Capacité de stockage (m³)',
                'required' => false,
                'attr'     => ['readonly' => 'readonly'],
            ])
            ->add('zoneStockage', IntegerType::class, [
                'label'    => 'Zone dédiée au stockage (m²)',
                'mapped'   => false,
                'required' => false,
            ])
            ->add('hauteurStockage', IntegerType::class, [
                'label'    => 'Hauteur de stockage max (m)',
                'mapped'   => false,
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Warehouse::class,
        ]);
    }
}
