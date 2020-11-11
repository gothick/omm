<?php

namespace App\Form;

use App\Entity\Wander;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class WanderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class)
            ->add('startTime', DateTimeType::class, ['label' => 'End Time'])
            ->add('endTime', DateTimeType::class, ['label' => 'End Time'])
            ->add('description', TextareaType::class)
            ->add('gpxFilename', TextType::class, ['label' => 'GPX Filename'])
            ->add('save', SubmitType::class, ['label' => 'Save']);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Wander::class,
        ]);
    }
}
