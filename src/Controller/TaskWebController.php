<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\TaskRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class TaskWebController extends AbstractController
{
    #[Route('/users/{id}/tasks', name: 'web_user_tasks')]
    public function showUserTasks(
        User $user,
        TaskRepository $taskRepository,
        UserRepository $userRepository,
        AuthorizationCheckerInterface $authChecker
    ): Response
    {
        // Obtener el usuario actualmente autenticado
        $currentUser = $this->getUser();
        
        // Verificar si el usuario tiene rol de admin
        $isAdmin = $authChecker->isGranted('ROLE_ADMIN');
        
        // Si no es admin, solo puede ver sus propias tareas
        if (!$isAdmin) {
            // Verificar que el usuario actual coincida con el usuario solicitado
            if (!$currentUser || !($currentUser instanceof User) || $currentUser->getId() !== $user->getId()) {
                throw $this->createAccessDeniedException('No tienes permiso para ver las tareas de otros usuarios.');
            }
        }
        
        // Obtener tareas del usuario con proyecto y tarifa
        $tasks = $taskRepository->findTasksWithProjectAndRate($user);
        
        $taskData = [];
        $totalHours = 0;
        $totalValue = 0;
        
        foreach ($tasks as $task) {
            // Asegurar que getHourlyRateForUser devuelve float
            $hourlyRate = (float) $task->getProject()->getHourlyRateForUser($user);
            $hoursSpent = (float) $task->getHoursSpent(); // Convertir string a float
            $taskValue = $hoursSpent * $hourlyRate;
            
            $taskData[] = [
                'task' => $task,
                'hourly_rate' => $hourlyRate,
                'total_value' => $taskValue,
            ];
            
            $totalHours += $hoursSpent;
            $totalValue += $taskValue;
        }
        
        // Obtener todos los usuarios para el navbar dropdown
        $allUsers = $userRepository->findAll();
        
        return $this->render('task_web/user_tasks.html.twig', [
            'user' => $user,
            'tasks' => $taskData,
            'total_hours' => $totalHours,
            'total_value' => $totalValue,
            'users' => $allUsers, // Para el dropdown en base.html.twig
        ]);
    }
}
