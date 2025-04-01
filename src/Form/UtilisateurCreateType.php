<?php
namespace App\Form;

use App\Entity\Utilisateur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bundle\SecurityBundle\Security;

class UtilisateurCreateType extends AbstractType
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
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

        // Только супер-админ может назначить другого супер-админа
        if ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
            $roleChoices['Super Administrateur'] = 'ROLE_SUPER_ADMIN';
        }

        $builder
            ->add('username', TextType::class, [
                'label' => 'Nom d’utilisateur',
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Mot de passe',
                'mapped' => false,
            ])
            ->add('roles', ChoiceType::class, [
                'label' => 'Rôles',
                'choices' => $roleChoices,
                'expanded' => true,
                'multiple' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Utilisateur::class,
            'current_user' => null, // 👈 добавляем опцию
        ]);
    }
}