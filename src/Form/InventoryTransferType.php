<?php
// src/Form/InventoryTransferType.php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\Warehouse;

class InventoryTransferType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // В опциях передаётся массив объектов Warehouse (склады)
        $builder
            ->add('destinationWarehouse', ChoiceType::class, [
                'choices' => $options['warehouses'],
                // При выборе отображается имя склада
                'choice_label' => function(Warehouse $warehouse) {
                    return $warehouse->getName();
                },
                // Если понадобится, можно задать значение choice_value как ID
                'choice_value' => function (?Warehouse $warehouse) {
                    return $warehouse ? $warehouse->getId() : '';
                },
                'placeholder' => 'Выберите склад назначения',
                'label' => 'Склад назначения',
            ])
            ->add('quantity', IntegerType::class, [
                'label' => 'Количество для переноса',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        // Обязательно передавать опцию "warehouses" – массив объектов Warehouse,
        // которые будут доступны для выбора
        $resolver->setRequired('warehouses');
        $resolver->setDefaults([]);
    }
}
