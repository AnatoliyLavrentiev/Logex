<?php
namespace App\Form;

use App\Entity\Utilisateur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Bundle\SecurityBundle\Security;

class GestionRoleType extends AbstractType
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $isSuperAdmin = $this->security->isGranted('ROLE_SUPER_ADMIN');

        $roleChoices = [
            'Utilisateur' => 'ROLE_USER',
            'Magasin' => 'ROLE_MAGASIN',
            'Entrepôt' => 'ROLE_ENTREPOT',
            'Administrateur' => 'ROLE_ADMIN',
            'Créer des commandes' => 'ROLE_ORDER_CREATE',
            'Lire les entrepôts' => 'ROLE_WAREHOUSE_READ',
            'Lire les produits' => 'ROLE_PRODUCT_READ',
            'Lire l’inventaire' => 'ROLE_INVENTORY_READ',
            'Lire le magasin' => 'ROLE_STORE_READ',
            'Lire les livraisons' => 'ROLE_DELIVERY_READ',
        ];

        if ($isSuperAdmin) {
            $roleChoices['Super Administrateur'] = 'ROLE_SUPER_ADMIN';
        }

        $builder
            ->add('username', TextType::class, [
                'label' => "Nom d'utilisateur",
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'Mot de passe',
                'required' => false,
                'mapped' => false,
                'constraints' => [
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Le mot de passe doit contenir au moins {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('roles', ChoiceType::class, [
                'label' => 'Rôles',
                'choices' => $roleChoices,
                'multiple' => true,
                'expanded' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Utilisateur::class,
        ]);
    }
}