<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\TaskRepository;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class TaskApiController extends AbstractController
{
    #[Route('/api/users/{id}/tasks', name: 'api_user_tasks', methods: ['GET'])]
    public function getUserTasks(User $user, TaskRepository $taskRepository): JsonResponse
    {
        // Obtener tareas del usuario con proyecto y tarifa
        $tasks = $taskRepository->findTasksWithProjectAndRate($user);

        // Asegúrate que esté así:
        $formattedTasks = [];
        foreach ($tasks as $task) {
            $formattedTasks[] = [  // <- Usar [] para agregar al array
                'id' => $task->getId(),
                'description' => $task->getDescription(),
                'hours_spent' => $task->getHoursSpent(),
                'created_at' => $task->getCreatedAt()->format('Y-m-d H:i:s'),
                'project' => [
                    'id' => $task->getProject()->getId(),
                    'name' => $task->getProject()->getName(),
                ],
                'hourly_rate' => (float) $task->getProject()->getHourlyRateForUser($user),
                'total_value' => (float) $task->getHoursSpent() *
                    (float) $task->getProject()->getHourlyRateForUser($user),
            ];
        }

        return $this->json([
            'success' => true,
            'user_id' => $user->getId(),
            'user_email' => $user->getEmail(),
            'tasks' => $formattedTasks,
            'count' => count($formattedTasks),
        ]);
    }
}
