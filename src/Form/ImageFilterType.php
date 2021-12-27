<?php

namespace App\Form;

use App\Entity\Image;
use App\Entity\Wander;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;


class ImageFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->setMethod('GET')
            ->add('start_date', DateType::class, [
            ])
            ->add('end_date', DateType::class, [
            ])
            ->add('location', ChoiceType::class, [
                'choices' => $options['locations'],
                'placeholder' => 'Any Location',
                'required' => false
            ])
            ->add('rating_comparison', ChoiceType::class, [
                'choices'  => [
                    '=' => 'eq',
                    '<=' => 'lte',
                    '>=' => 'gte',
                ],
            ])
            ->add('rating', ChoiceType::class, [
                'choices'  => [
                    '★' => 1,
                    '★★' => 2,
                    '★★★' => 3,
                    '★★★★' => 4,
                    '★★★★★' => 5,
                ],
                'placeholder' => 'Any Rating',
                'required' => false
            ]);
    }
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'locations' => []
        ]);
        $resolver->setAllowedTypes('locations', 'array');
    }
}
