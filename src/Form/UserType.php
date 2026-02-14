<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Correo electrónico',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'correo@ejemplo.com'
                ]
            ])
            ->add('roles', ChoiceType::class, [
                'label' => 'Roles',
                'choices' => [
                    'Usuario' => 'ROLE_USER',
                    'Administrador' => 'ROLE_ADMIN',
                ],
                'multiple' => true,
                'expanded' => false,
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
        ;

        // Only add password field if creating new user or editing
        $builder->add('password', RepeatedType::class, [
            'type' => PasswordType::class,
            'invalid_message' => 'Las contraseñas deben coincidir.',
            'options' => ['attr' => ['class' => 'form-control']],
            'required' => true,
            'first_options'  => ['label' => 'Contraseña'],
            'second_options' => ['label' => 'Confirmar contraseña'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
