<?php

namespace App\Form;

use App\Entity\Settings;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class SettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('siteTitle', TextType::class, [
                'label' => 'Site Title'
            ])
            ->add('siteSubtitle', TextType::class, [
                'label' => 'Site Sub-title'
            ])
            ->add('twitterHandle', TextType::class, [
                'label' => 'Twitter Handle for Twitter Cards (without "@")',
                'required' => false
            ])
            ->add('gravatarEmail', TextType::class, [
                'label' => 'Gravatar Email Address for avatar on About page',
                'required' => false
            ])
            ->add('siteAbout', TextareaType::class, [
                'label' => 'About Page text',
                'attr' => ['rows' => 10]
            ]);
        $builder
            ->add('save', SubmitType::class, ['label' => 'Save']);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Settings::class,
            'type' => 'standard'
        ]);
    }
}
