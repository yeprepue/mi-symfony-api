<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api')]
class AuthApiController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private JWTTokenManagerInterface $jwtManager,
        private ValidatorInterface $validator,
    ) {}

    #[Route('/login', name: 'api_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['email']) || !isset($data['password'])) {
            return $this->json([
                'success' => false,
                'message' => 'Email y contraseña son requeridos'
            ], Response::HTTP_BAD_REQUEST);
        }

        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => $data['email']]);

        if (!$user || !$this->passwordHasher->isPasswordValid($user, $data['password'])) {
            return $this->json([
                'success' => false,
                'message' => 'Credenciales inválidas'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $token = $this->jwtManager->create($user);

        return $this->json([
            'success' => true,
            'message' => 'Login exitoso',
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'roles' => $user->getRoles()
                ]
            ]
        ]);
    }

    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email']) || !isset($data['password'])) {
            return $this->json([
                'success' => false,
                'message' => 'Email y contraseña son requeridos'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Validar email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return $this->json([
                'success' => false,
                'message' => 'Email inválido'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Validar contraseña (mínimo 8 caracteres)
        if (strlen($data['password']) < 8) {
            return $this->json([
                'success' => false,
                'message' => 'La contraseña debe tener al menos 8 caracteres'
            ], Response::HTTP_BAD_REQUEST);
        }

        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        
        // Verificar si el usuario ya existe
        if ($userRepository->findOneBy(['email' => $data['email']])) {
            return $this->json([
                'success' => false,
                'message' => 'El email ya está registrado'
            ], Response::HTTP_CONFLICT);
        }

        // Crear nuevo usuario
        $user = new User();
        $user->setEmail($data['email']);
        $user->setPassword($this->passwordHasher->hashPassword($user, $data['password']));
        $user->setRoles(['ROLE_USER']);

        // Validar usuario
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            return $this->json([
                'success' => false,
                'message' => (string) $errors
            ], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Generar token JWT
        $token = $this->jwtManager->create($user);

        return $this->json([
            'success' => true,
            'message' => 'Usuario registrado exitosamente',
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'roles' => $user->getRoles()
                ]
            ]
        ], Response::HTTP_CREATED);
    }

    #[Route('/me', name: 'api_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'No autenticado'
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'success' => true,
            'data' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles()
            ]
        ]);
    }

    #[Route('/refresh-token', name: 'api_refresh_token', methods: ['POST'])]
    public function refreshToken(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'No autenticado'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $token = $this->jwtManager->create($user);

        return $this->json([
            'success' => true,
            'data' => [
                'token' => $token
            ]
        ]);
    }

    #[Route('/forgot-password', name: 'api_forgot_password', methods: ['POST'])]
    public function forgotPassword(Request $request, MailerInterface $mailer): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email'])) {
            return $this->json([
                'success' => false,
                'message' => 'Email es requerido'
            ], Response::HTTP_BAD_REQUEST);
        }

        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => $data['email']]);

        // No revelar si el usuario existe o no
        if (!$user) {
            return $this->json([
                'success' => true,
                'message' => 'Si el email existe, recibirás un enlace de recuperación'
            ]);
        }

        // Generar token de recuperación
        $resetToken = bin2hex(random_bytes(32));
        $user->setResetToken($resetToken);
        $user->setResetTokenExpiresAt(new \DateTime('+1 hour'));

        $this->entityManager->flush();

        // En un entorno de producción, enviarías un email real
        // Por ahora, retornamos el token (solo para desarrollo)
        // En producción, usarías: $mailer->send($email);
        
        return $this->json([
            'success' => true,
            'message' => 'Si el email existe, recibirás un enlace de recuperación',
            'debug_token' => $resetToken // Eliminar en producción
        ]);
    }

    #[Route('/reset-password', name: 'api_reset_password', methods: ['POST'])]
    public function resetPassword(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['token']) || !isset($data['new_password'])) {
            return $this->json([
                'success' => false,
                'message' => 'Token y nueva contraseña son requeridos'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Validar nueva contraseña
        if (strlen($data['new_password']) < 8) {
            return $this->json([
                'success' => false,
                'message' => 'La contraseña debe tener al menos 8 caracteres'
            ], Response::HTTP_BAD_REQUEST);
        }

        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['resetToken' => $data['token']]);

        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'Token inválido'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Verificar si el token ha expirado
        if ($user->getResetTokenExpiresAt() < new \DateTime()) {
            return $this->json([
                'success' => false,
                'message' => 'El token ha expirado'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Actualizar contraseña
        $user->setPassword($this->passwordHasher->hashPassword($user, $data['new_password']));
        $user->setResetToken(null);
        $user->setResetTokenExpiresAt(null);

        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Contraseña actualizada exitosamente'
        ]);
    }

    #[Route('/change-password', name: 'api_change_password', methods: ['POST'])]
    public function changePassword(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'No autenticado'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['current_password']) || !isset($data['new_password'])) {
            return $this->json([
                'success' => false,
                'message' => 'Contraseña actual y nueva contraseña son requeridas'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Verificar contraseña actual
        if (!$this->passwordHasher->isPasswordValid($user, $data['current_password'])) {
            return $this->json([
                'success' => false,
                'message' => 'Contraseña actual incorrecta'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Validar nueva contraseña
        if (strlen($data['new_password']) < 8) {
            return $this->json([
                'success' => false,
                'message' => 'La nueva contraseña debe tener al menos 8 caracteres'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Actualizar contraseña
        $user->setPassword($this->passwordHasher->hashPassword($user, $data['new_password']));
        $this->entityManager->flush();

        // Generar nuevo token
        $token = $this->jwtManager->create($user);

        return $this->json([
            'success' => true,
            'message' => 'Contraseña actualizada exitosamente',
            'data' => [
                'token' => $token
            ]
        ]);
    }
}
