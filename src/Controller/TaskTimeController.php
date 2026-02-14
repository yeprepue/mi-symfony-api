<?php

namespace App\Controller;

use App\Entity\Task;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[Route('/api/tasks')]
class TaskTimeController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * Obtener todas las tareas del usuario actual
     */
    #[Route('', name: 'api_tasks_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'No autenticado'
            ], Response::HTTP_UNAUTHORIZED);
        }

        /** @var TaskRepository $taskRepository */
        $taskRepository = $this->entityManager->getRepository(Task::class);
        $tasks = $taskRepository->findBy(['owner' => $user]);

        $tasksData = [];
        foreach ($tasks as $task) {
            $tasksData[] = [
                'id' => $task->getId(),
                'description' => $task->getDescription(),
                'project' => [
                    'id' => $task->getProject()?->getId(),
                    'name' => $task->getProject()?->getName(),
                ],
                'hoursSpent' => $task->getHoursSpent(),
                'currentHours' => $task->getCurrentHours(),
                'isRunning' => $task->isIsRunning(),
                'startedAt' => $task->getStartedAt()?->format('Y-m-d H:i:s'),
                'lastResumeAt' => $task->getLastResumeAt()?->format('Y-m-d H:i:s'),
                'finishedAt' => $task->getFinishedAt()?->format('Y-m-d H:i:s'),
                'createdAt' => $task->getCreatedAt()?->format('Y-m-d H:i:s'),
            ];
        }

        return $this->json([
            'success' => true,
            'data' => $tasksData
        ]);
    }

    /**
     * Iniciar una tarea
     */
    #[Route('/{id}/start', name: 'api_task_start', methods: ['POST'])]
    public function start(int $id): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'No autenticado'
            ], Response::HTTP_UNAUTHORIZED);
        }

        /** @var TaskRepository $taskRepository */
        $taskRepository = $this->entityManager->getRepository(Task::class);
        $task = $taskRepository->find($id);

        if (!$task) {
            return $this->json([
                'success' => false,
                'message' => 'Tarea no encontrada'
            ], Response::HTTP_NOT_FOUND);
        }

        // Verificar que la tarea pertenece al usuario
        if ($task->getOwner()->getId() !== $user->getId()) {
            return $this->json([
                'success' => false,
                'message' => 'No autorizado'
            ], Response::HTTP_FORBIDDEN);
        }

        // Si ya está corriendo, no hacer nada
        if ($task->isIsRunning()) {
            return $this->json([
                'success' => false,
                'message' => 'La tarea ya está en ejecución'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Pausar cualquier otra tarea que esté corriendo
        $runningTasks = $taskRepository->findBy(['owner' => $user, 'isRunning' => true]);
        foreach ($runningTasks as $runningTask) {
            $runningTask->pause();
        }

        // Iniciar esta tarea
        $task->start();
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Tarea iniciada',
            'data' => [
                'id' => $task->getId(),
                'isRunning' => $task->isIsRunning(),
                'startedAt' => $task->getStartedAt()?->format('Y-m-d H:i:s'),
                'currentHours' => $task->getCurrentHours(),
            ]
        ]);
    }

    /**
     * Pausar una tarea
     */
    #[Route('/{id}/pause', name: 'api_task_pause', methods: ['POST'])]
    public function pause(int $id): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'No autenticado'
            ], Response::HTTP_UNAUTHORIZED);
        }

        /** @var TaskRepository $taskRepository */
        $taskRepository = $this->entityManager->getRepository(Task::class);
        $task = $taskRepository->find($id);

        if (!$task) {
            return $this->json([
                'success' => false,
                'message' => 'Tarea no encontrada'
            ], Response::HTTP_NOT_FOUND);
        }

        // Verificar que la tarea pertenece al usuario
        if ($task->getOwner()->getId() !== $user->getId()) {
            return $this->json([
                'success' => false,
                'message' => 'No autorizado'
            ], Response::HTTP_FORBIDDEN);
        }

        if (!$task->isIsRunning()) {
            return $this->json([
                'success' => false,
                'message' => 'La tarea no está en ejecución'
            ], Response::HTTP_BAD_REQUEST);
        }

        $task->pause();
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Tarea pausada',
            'data' => [
                'id' => $task->getId(),
                'isRunning' => $task->isIsRunning(),
                'hoursSpent' => $task->getHoursSpent(),
                'accumulatedTime' => $task->getAccumulatedTime(),
            ]
        ]);
    }

    /**
     * Detener una tarea
     */
    #[Route('/{id}/stop', name: 'api_task_stop', methods: ['POST'])]
    public function stop(int $id): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'No autenticado'
            ], Response::HTTP_UNAUTHORIZED);
        }

        /** @var TaskRepository $taskRepository */
        $taskRepository = $this->entityManager->getRepository(Task::class);
        $task = $taskRepository->find($id);

        if (!$task) {
            return $this->json([
                'success' => false,
                'message' => 'Tarea no encontrada'
            ], Response::HTTP_NOT_FOUND);
        }

        // Verificar que la tarea pertenece al usuario
        if ($task->getOwner()->getId() !== $user->getId()) {
            return $this->json([
                'success' => false,
                'message' => 'No autorizado'
            ], Response::HTTP_FORBIDDEN);
        }

        $task->stop();
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Tarea detenida',
            'data' => [
                'id' => $task->getId(),
                'isRunning' => $task->isIsRunning(),
                'hoursSpent' => $task->getHoursSpent(),
                'accumulatedTime' => $task->getAccumulatedTime(),
                'finishedAt' => $task->getFinishedAt()?->format('Y-m-d H:i:s'),
            ]
        ]);
    }

    /**
     * Obtener estado actual de una tarea
     */
    #[Route('/{id}/status', name: 'api_task_status', methods: ['GET'])]
    public function status(int $id): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'No autenticado'
            ], Response::HTTP_UNAUTHORIZED);
        }

        /** @var TaskRepository $taskRepository */
        $taskRepository = $this->entityManager->getRepository(Task::class);
        $task = $taskRepository->find($id);

        if (!$task) {
            return $this->json([
                'success' => false,
                'message' => 'Tarea no encontrada'
            ], Response::HTTP_NOT_FOUND);
        }

        // Verificar que la tarea pertenece al usuario
        if ($task->getOwner()->getId() !== $user->getId()) {
            return $this->json([
                'success' => false,
                'message' => 'No autorizado'
            ], Response::HTTP_FORBIDDEN);
        }

        return $this->json([
            'success' => true,
            'data' => [
                'id' => $task->getId(),
                'description' => $task->getDescription(),
                'hoursSpent' => $task->getHoursSpent(),
                'currentHours' => $task->getCurrentHours(),
                'isRunning' => $task->isIsRunning(),
                'startedAt' => $task->getStartedAt()?->format('Y-m-d H:i:s'),
                'lastResumeAt' => $task->getLastResumeAt()?->format('Y-m-d H:i:s'),
                'finishedAt' => $task->getFinishedAt()?->format('Y-m-d H:i:s'),
                'accumulatedTime' => $task->getAccumulatedTime(),
            ]
        ]);
    }
}
