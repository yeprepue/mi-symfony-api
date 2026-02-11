<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\TaskRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TaskWebController extends AbstractController
{
    #[Route('/users/{id}/tasks', name: 'web_user_tasks')]
    public function showUserTasks(
        User $user,
        TaskRepository $taskRepository,
        UserRepository $userRepository
    ): Response
    {
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
