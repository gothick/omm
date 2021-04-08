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
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;


class ImageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            //->add('imageFile', VichImageType::class)
            ->add('title', TextType::class)
            ->add('description', TextareaType::class, [
                'attr' => ['rows' => 10]
            ])
            ->add('wander', EntityType::class, [
                'class' => Wander::class,
                //'multiple' => true,
                //'by_reference' => false, // You took this back out when you changed to One-to-Many https://stackoverflow.com/a/35765987/300836
                'query_builder' => function(EntityRepository $er) {
                    return $er->createQueryBuilder('w')
                        ->orderBy('w.startTime', 'DESC');
                }
            ])
            ->add('rating', ChoiceType::class, [
                'choices'  => [
                    '-' => null,
                    '★' => 1,
                    '★★' => 2,
                    '★★★' => 3,
                    '★★★★' => 4,
                    '★★★★★' => 5,
                ],
            ])
            ->add('latlng', TextType::class)
            ->add('keywords', TextType::class)
            ->add('capturedAt')
        ;
        // Transform latitude, longitude string to/from array
        $builder->get('latlng')
            ->addModelTransformer(new CallbackTransformer(
                function($latlngAsArray) {
                    if ($latlngAsArray !== null) {
                        return implode(', ', $latlngAsArray);
                    }
                },
                function($latlngAsString) {
                    return explode(', ', $latlngAsString);
                }
            ));
        $builder->get('keywords')
            ->addModelTransformer(new CallbackTransformer(
                function($keywordsAsArray) {
                    if ($keywordsAsArray !== null)
                    {
                        return implode(', ', $keywordsAsArray);
                    }
                },
                function($keywordsAsString) {
                    return explode(', ', $keywordsAsString);
                }
            ));

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Image::class,
        ]);
    }
}
