<?php
// src/Form/ConfigurationType.php

namespace App\Form;

use App\Entity\Configuration;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConfigurationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nomDuSite', TextType::class, [
                'label' => 'Nom du Site',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
            ])
            ->add('emailContact', TextType::class, [
                'label' => 'Email de Contact',
            ])
            ->add('modeMaintenance', CheckboxType::class, [
                'label' => 'Mode Maintenance',
                'required' => false,
            ])
            // Champs SEO
            ->add('metaKeywords', TextType::class, [
                'label' => 'Mots-clés SEO',
                'required' => false,
            ])
            ->add('metaDescription', TextType::class, [
                'label' => 'Description Meta',
                'required' => false,
            ])
            // Réseaux sociaux
            ->add('facebookUrl', TextType::class, [
                'label' => 'URL Facebook',
                'required' => false,
            ])
            ->add('twitterUrl', TextType::class, [
                'label' => 'URL Twitter',
                'required' => false,
            ])
            // Google Analytics
            ->add('googleAnalyticsId', TextType::class, [
                'label' => 'ID Google Analytics',
                'required' => false,
            ])
            // Favicon
            ->add('faviconUrl', TextType::class, [
                'label' => 'URL du Favicon',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Configuration::class,
        ]);
    }
}
