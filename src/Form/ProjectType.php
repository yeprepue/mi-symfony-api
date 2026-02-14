<?php

namespace App\Form;

use App\Entity\Project;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class ProjectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nombre del Proyecto',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Nombre del proyecto'
                ]
            ])
            ->add('descripcion', TextareaType::class, [
                'label' => 'Descripción',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Descripción del proyecto',
                    'rows' => 4
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Project::class,
        ]);
    }
}
