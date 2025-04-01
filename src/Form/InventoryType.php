<?php
// src/Form/InventoryType.php
namespace App\Form;

use App\Entity\Inventory;
use App\Entity\Product;
use App\Entity\Warehouse;
use App\Repository\ProductRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InventoryType extends AbstractType
{
    private ProductRepository $productRepository;

    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('product', EntityType::class, [
                'class' => Product::class,
                'choice_label' => 'prodname',
                'choice_attr' => function($product) {
                    return [
                        'data-price' => $product->getPrice(),
                        'data-weight' => $product->getWeight(),
                        'data-category' => $product->getCategory(),
                    ];
                },
                'placeholder' => 'Sélectionnez un produit',
            ])
            ->add('warehouse', EntityType::class, [
                'class' => Warehouse::class,
                'choice_label' => 'name',
            ])
            ->add('quantity', IntegerType::class, [
                'label' => 'Quantité',
            ])
            ->add('price', MoneyType::class, [
                'currency' => 'EUR',
                'required' => false,
                'label' => 'Prix Unitaire (€)',
            ])
            ->add('weight', TextType::class, [
                'required' => false,
                'label' => 'Poids Unitaire',
            ])
            ->add('category', TextType::class, [
                'label' => 'Catégorie',
                'required' => false,
                'attr' => ['readonly' => true],
            ])
            ->add('prixTotal', HiddenType::class, ['required' => false])
            ->add('poidsTotal', HiddenType::class, ['required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Inventory::class,
        ]);
    }



    private function getCategoryChoices(): array
    {
        $categories = $this->productRepository->findDistinctCategories();
    
        $choices = [];
        foreach ($categories as $category) {
            if ($category) {
                $choices[$category] = $category;
            }
        }
    
        return $choices;
    }

    public function findDistinctCategories(): array
{
    $qb = $this->createQueryBuilder('p')
        ->select('DISTINCT p.category')
        ->where('p.category IS NOT NULL')
        ->orderBy('p.category', 'ASC');

    $results = $qb->getQuery()->getResult();

    // Преобразуем результат вида [['category' => '...']] в ['...']
    return array_map(fn($row) => $row['category'], $results);
}
}