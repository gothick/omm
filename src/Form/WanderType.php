<?php

namespace App\Form;

use App\Entity\Image;
use App\Entity\Wander;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\Extension\Core\Type\FileType;

class WanderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class)
            ->add('description', TextareaType::class, [
                'attr' => ['rows' => 10]
            ]);
        if ('standard' === $options['type']) {
            /** @var Wander */
            $wander = $builder->getData();
            $builder
                ->add('startTime', DateTimeType::class)
                ->add('endTime', DateTimeType::class)
                ->add('gpxFilename', TextType::class, ['label' => 'GPX Filename', 'disabled' => true])
                ->add('featuredImage', EntityType::class, [
                    'required' => false,
                    'class' => Image::class,
                    'choices' => $wander->getImages(),
                    'multiple' => false,
                ]);

        } elseif ('new' === $options['type']) {
            // Our form for new Wanders includes a GPX file upload. We don't
            // let anything else change that.
            $builder
                ->add('gpxFilename', FileType::class, [
                    'label' => 'GPX track file',
                    'mapped' => false,
                    'constraints' => [
                        new File([
                            'maxSize' => '1024k',
                            'mimeTypes' => [
                                "application/gpx+xml","text/xml","application/xml","application/octet-stream"
                            ],
                            'mimeTypesMessage' =>'Please upload a valid GPX document'
                        ])
                    ],
                ]);
        }
        $builder
            ->add('save', SubmitType::class, ['label' => 'Save']);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Wander::class,
            'type' => 'standard'
        ]);
    }
}
