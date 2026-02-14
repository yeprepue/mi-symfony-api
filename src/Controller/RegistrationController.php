<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $error = null;
        $success = false;

        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $password = $request->request->get('password');
            $confirmPassword = $request->request->get('confirm_password');

            // Validar email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Email inválido';
            } elseif (strlen($password) < 8) {
                $error = 'La contraseña debe tener al menos 8 caracteres';
            } elseif ($password !== $confirmPassword) {
                $error = 'Las contraseñas no coinciden';
            } else {
                /** @var UserRepository $userRepository */
                $userRepository = $entityManager->getRepository(User::class);
                
                // Verificar si el usuario ya existe
                if ($userRepository->findOneBy(['email' => $email])) {
                    $error = 'El email ya está registrado';
                } else {
                    // Crear nuevo usuario
                    $user = new User();
                    $user->setEmail($email);
                    $user->setPassword($passwordHasher->hashPassword($user, $password));
                    $user->setRoles(['ROLE_USER']);
                    
                    $entityManager->persist($user);
                    $entityManager->flush();
                    
                    $success = true;
                }
            }
        }

        return $this->render('security/register.html.twig', [
            'error' => $error,
            'success' => $success,
        ]);
    }
}
