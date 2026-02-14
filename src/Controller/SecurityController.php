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
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/forgot-password', name: 'app_forgot_password')]
    public function forgotPassword(Request $request, EntityManagerInterface $entityManager): Response
    {
        $error = null;
        $success = false;
        $debugToken = null;

        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            
            /** @var UserRepository $userRepository */
            $userRepository = $entityManager->getRepository(User::class);
            $user = $userRepository->findOneBy(['email' => $email]);

            // No revelar si el usuario existe o no
            if ($user) {
                // Generar token de recuperación
                $resetToken = bin2hex(random_bytes(32));
                $user->setResetToken($resetToken);
                $user->setResetTokenExpiresAt(new \DateTime('+1 hour'));
                $entityManager->flush();
                $debugToken = $resetToken;
            }
            
            $success = true;
        }

        return $this->render('security/forgot_password.html.twig', [
            'error' => $error,
            'success' => $success,
            'debug_token' => $debugToken,
        ]);
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password')]
    public function resetPassword(string $token, Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        /** @var UserRepository $userRepository */
        $userRepository = $entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['resetToken' => $token]);

        $error = null;
        $success = false;

        if (!$user) {
            $error = 'Token inválido';
        } elseif ($user->getResetTokenExpiresAt() < new \DateTime()) {
            $error = 'El token ha expirado';
        } elseif ($request->isMethod('POST')) {
            $newPassword = $request->request->get('new_password');
            $confirmPassword = $request->request->get('confirm_password');

            if (strlen($newPassword) < 8) {
                $error = 'La contraseña debe tener al menos 8 caracteres';
            } elseif ($newPassword !== $confirmPassword) {
                $error = 'Las contraseñas no coinciden';
            } else {
                // Actualizar contraseña
                $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
                $user->setResetToken(null);
                $user->setResetTokenExpiresAt(null);
                $entityManager->flush();
                $success = true;
            }
        }

        return $this->render('security/reset_password.html.twig', [
            'token' => $token,
            'error' => $error,
            'success' => $success,
        ]);
    }
}
