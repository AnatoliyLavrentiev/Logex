<?php

namespace App\Form;

use App\Entity\Inventory;
use App\Entity\OrderItem;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class OrderItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('inventory', EntityType::class, [
                'class' => Inventory::class,
                'choice_label' => function (Inventory $inventory) {
                    $product = $inventory->getProduct();
                    return sprintf('%s (stock: %d)', $product->getProdname(), $inventory->getQuantity());
                },
                'label' => '<div class="text-center mb-2"><i class="fa-solid fa-box-open me-2"></i>Produit en stock</div>',
                'label_html' => true,
                'placeholder' => 'Sélectionnez un produit',
                'attr' => [
    'class' => 'form-select shadow-sm p-2 bg-body text-body border-secondary',
]
            ])
            ->add('quantity', IntegerType::class, [
                'label' => '<div class="mb-2 text-center mt-3"><i class="fa-solid fa-cubes me-2"></i>Quantité à commander</div>',
                'label_html' => true,
                'attr' => [
    'min' => 1,
    'class' => 'form-control shadow-sm p-2 bg-body text-body border-secondary',
],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez entrer une quantité.'
                    ]),
                    new Assert\GreaterThanOrEqual([
                        'value' => 1,
                        'message' => 'La quantité doit être au moins 1.'
                    ]),
                    new Callback(function ($value, ExecutionContextInterface $context) {
                        $form = $context->getRoot();
                        $orderItem = $form->getData();

                        if ($orderItem instanceof OrderItem) {
                            $inventory = $orderItem->getInventory();

                            if ($inventory && $value > $inventory->getQuantity()) {
                                $context->buildViolation('Stock insuffisant pour ce produit.')
                                    ->atPath('quantity')
                                    ->addViolation();
                            }
                        }
                    }),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OrderItem::class,
        ]);
    }
}