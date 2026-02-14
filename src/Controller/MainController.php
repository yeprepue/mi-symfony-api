<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\ProjectRepository;
use App\Repository\TaskRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class MainController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function home(
        UserRepository $userRepository,
        ProjectRepository $projectRepository,
        TaskRepository $taskRepository,
        AuthorizationCheckerInterface $authChecker
    ): Response {
        // Si el usuario no es admin, redirigir directamente a sus tareas
        if (!$authChecker->isGranted('ROLE_ADMIN')) {
            $user = $this->getUser();
            if ($user instanceof User) {
                return $this->redirectToRoute('web_user_tasks', ['id' => $user->getId()]);
            }
        }
        
        return $this->render('main/home.html.twig', [
            'total_users' => $userRepository->count([]),
            'total_projects' => $projectRepository->count([]),
            'total_tasks' => $taskRepository->count([]),
            'users' => $userRepository->findAll(),
        ]);
    }
}
