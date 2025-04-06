<?php 
namespace App\Service;

use App\Entity\Task;
use App\Entity\ProjectAssignment;
use App\Entity\TaskAssignment;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class TaskAssignmentService
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function handleTaskAssignments(array $data, Task $task, UserInterface $user, $typeOfRequest = 'assign'): Response
    {
       
        if (!isset($data['taskAssignments']) || !is_array($data['taskAssignments'])) {
            if ($typeOfRequest === "update") {
                return new JsonResponse([], Response::HTTP_OK); 
            }
            return new JsonResponse(['error' => 'taskAssignments is required and should be an array.'], Response::HTTP_BAD_REQUEST);
        }

        if ($typeOfRequest === 'assign' && empty($data['taskAssignments'])) {
            return new JsonResponse(['error' => 'taskAssignments cannot be empty when assigning.'], Response::HTTP_BAD_REQUEST);
        }

        $currentAssignments = $this->entityManager->getRepository(TaskAssignment::class)->findBy(['task' => $task]);

        $currentUserIds = [];
        foreach ($currentAssignments as $assignment) {
            $currentUserIds[$assignment->getUser()->getId()] = $assignment;
        }

        $newAssignments = [];

        foreach ($data['taskAssignments'] as $assignment) {
            $result = $this->handleAssignment($assignment, $task, $user, $typeOfRequest, $currentUserIds);
            if ($result instanceof JsonResponse) {
                return $result;
            }
            $newAssignments[] = $assignment; 
        }

        if ($typeOfRequest !== 'assign') {
            foreach ($currentAssignments as $assignment) {
                if (!in_array($assignment->getUser()->getId(), $newAssignments)) {
                    $this->entityManager->remove($assignment);
                }
            }
        }

        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Task assignments processed successfully.'], Response::HTTP_OK);
    }

    private function handleAssignment($assignment, Task $task, UserInterface $user, string $typeOfRequest, array $currentUserIds): ?JsonResponse
    {
        if (!isset($assignment)) {
            return $this->handleError('User ID is required for each assignment.');
        }

        $assignedUser = $this->entityManager->getRepository(User::class)->find($assignment);
        if (!$assignedUser) {
            return $this->handleError('User with ID ' . $assignment . ' not found.');
        }

        $project = $task->getProject(); 
        $projectAssignment = $this->entityManager->getRepository(ProjectAssignment::class)->findOneBy([
            'project' => $project,
            'user' => $assignedUser
        ]);
    
        if (!$projectAssignment) {
            return $this->handleError('User with ID ' . $assignedUser->getId() . ' is not assigned to this project.');
        }


        return $this->assignOrUpdateTask($assignedUser, $task, $typeOfRequest, $currentUserIds);
    }

    private function assignOrUpdateTask(User $assignedUser, Task $task, string $typeOfRequest, array $currentUserIds): ?JsonResponse
    {
        if (isset($currentUserIds[$assignedUser->getId()])) {
            if ($typeOfRequest === 'assign') {
                return $this->handleError('User with ID ' . $assignedUser->getId() . ' is already assigned to the task.');
            }

        } else {
            $taskAssignment = new TaskAssignment();
            $taskAssignment->setUser($assignedUser);
            $taskAssignment->setTask($task);

            $this->entityManager->persist($taskAssignment);
        }

        return null; 
    }

    private function handleError(string $message): JsonResponse
    {
        return new JsonResponse(['error' => $message], Response::HTTP_BAD_REQUEST);
    }
}
