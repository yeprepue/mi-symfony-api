<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Repository\ProjectRepository;
use App\Repository\TaskRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MainController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function home(
        UserRepository $userRepository,
        ProjectRepository $projectRepository,
        TaskRepository $taskRepository
    ): Response {
        return $this->render('main/home.html.twig', [
            'total_users' => $userRepository->count([]),
            'total_projects' => $projectRepository->count([]),
            'total_tasks' => $taskRepository->count([]),
            'users' => $userRepository->findAll(),
        ]);
    }
}
