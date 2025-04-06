<?php 

namespace App\Service;

use App\Entity\Project;
use App\Entity\ProjectAssignment;
use App\Entity\User;
use App\Entity\Role;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class ProjectAssignmentService
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function handleRoleAssignments(array $data, Project $project, UserInterface $user, $typeOfRequest = 'assign'): Response
    {
        if (!isset($data['roleAssignments']) || !is_array($data['roleAssignments'])) {
            if ($typeOfRequest === 'update') {
                return new Response(null, Response::HTTP_OK);
            }
            else {
            return $this->handleError(
                "roleAssignments is required and must be an array. Example format: [{'user_id': X, 'role_id': Y}]"
            );
        }
        }

        $invalidAssignment = array_filter($data['roleAssignments'], function ($assignment) {
            return !isset($assignment['user_id']) || !isset($assignment['role_id']);
        });

        if (empty($data['roleAssignments']) && $typeOfRequest === 'assign') {
            return $this->handleError("roleAssignments is required and must be an array. Example format: [{'user_id': X, 'role_id': Y}]");
        }
        
        if (!empty($invalidAssignment)) {
            return $this->handleError("roleAssignments must be of this format: [{'user_id': X, 'role_id': Y}]");
        }
        

        $currentAssignments = $this->entityManager->getRepository(ProjectAssignment::class)->findBy(['project' => $project]);

        $currentUserIds = [];
        foreach ($currentAssignments as $assignment) {
            $currentUserIds[$assignment->getUser()->getId()] = $assignment;
        }

        $newAssignments = [];

        foreach ($data['roleAssignments'] as $assignment) {
            $result = $this->handleAssignment($assignment, $project, $user, $typeOfRequest, $currentUserIds);
            if ($result instanceof JsonResponse) {
                return $result;
            }
            $newAssignments[] = $assignment['user_id']; 
        }

        if ($typeOfRequest !== 'assign') {
            foreach ($currentAssignments as $assignment) {
                if (!in_array($assignment->getUser()->getId(), $newAssignments)) {
                    $this->entityManager->remove($assignment);
                }
            }
        }

        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Roles processed successfully.'], Response::HTTP_OK);
    }

    private function handleAssignment(array $assignment, Project $project, UserInterface $user, string $typeOfRequest, array $currentUserIds): ?JsonResponse
    {
        if (!isset($assignment['user_id']) || !isset($assignment['role_id'])) {
            return $this->handleError('Both user_id and role_id are required for each assignment.');
        }

        $role = $this->entityManager->getRepository(Role::class)->find($assignment['role_id']);
        if (!$role) {
            return $this->handleError('Role with ID ' . $assignment['role_id'] . ' not found.');
        }

        $assignedUser = $this->entityManager->getRepository(User::class)->find($assignment['user_id']);
        if (!$assignedUser) {
            return $this->handleError('User with ID ' . $assignment['user_id'] . ' not found.');
        }

        if ($assignedUser === $user) {
            return $this->handleError('You cannot assign the project to yourself.');
        }

        return $this->assignOrUpdateRole($assignedUser, $project, $role, $typeOfRequest, $currentUserIds);
    }

    private function assignOrUpdateRole(User $assignedUser, Project $project, Role $role, string $typeOfRequest, array $currentUserIds): ?JsonResponse
    {
        if (isset($currentUserIds[$assignedUser->getId()])) {
            if ($typeOfRequest === 'assign') {
                return $this->handleError('User with ID ' . $assignedUser->getId() . ' is already assigned to the project.');
            }

            $existingAssignment = $currentUserIds[$assignedUser->getId()];
            $existingAssignment->setRole($role);
            $this->entityManager->persist($existingAssignment);
        } else {
            $projectAssignment = new ProjectAssignment();
            $projectAssignment->setUser($assignedUser);
            $projectAssignment->setProject($project);
            $projectAssignment->setRole($role);

            $this->entityManager->persist($projectAssignment);
        }

        return null;
    }

    private function handleError(string $message): JsonResponse
    {
        return new JsonResponse(['error' => $message], Response::HTTP_BAD_REQUEST);
    }
}
