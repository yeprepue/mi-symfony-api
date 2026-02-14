<?php

namespace App\Form;

use App\Entity\Task;
use App\Entity\Project;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;

class TaskType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('description', TextType::class, [
                'label' => 'Descripción',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Descripción de la tarea'
                ]
            ])
            ->add('hoursSpent', NumberType::class, [
                'label' => 'Horas invertidas',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '0.00',
                    'step' => '0.01'
                ],
                'required' => false
            ])
            ->add('owner', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'email',
                'label' => 'Usuario',
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
            ->add('project', EntityType::class, [
                'class' => Project::class,
                'choice_label' => 'name',
                'label' => 'Proyecto',
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Task::class,
        ]);
    }
}
