<?php
namespace App\Security;

use App\Entity\Task;
use App\Entity\User;
use App\Entity\ProjectAssignment;
use App\Entity\RolePermission;
use App\Entity\Permission;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class TaskVoter extends Voter
{
    const VIEW = 'view';
    const CREATE = 'create';
    const UPDATE = 'update';
    const DELETE = 'delete';
    const ASSIGN = 'assign';
    const COMMENT = 'comment';

    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    protected function supports(string $attribute, $subject): bool
    {
        return $subject instanceof Task && in_array($attribute, [
            self::VIEW, self::CREATE, self::UPDATE, self::DELETE, self::ASSIGN, self::COMMENT
        ]);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }

        /** @var Task $task */
        $task = $subject;
        $project = $task->getProject();

        // Grant access if the user is the project owner
        if ($this->isOwner($project, $user)) {
            return true;
        }

        // Get the user's project assignment
        $assignment = $this->getProjectAssignment($project, $user);
        if (!$assignment) {
            return false;
        }

        // Map action names to permission names in the database
        $permissionMap = [
            self::VIEW => 'View Task',
            self::CREATE => 'Create Task',
            self::UPDATE => 'Edit Task',
            self::DELETE => 'Delete Task',
            self::ASSIGN => 'Assign Task',
            self::COMMENT => 'Comment Task',
        ];

        return isset($permissionMap[$attribute]) && $this->hasPermission($assignment, $permissionMap[$attribute]);
    }

    private function getProjectAssignment($project, User $user): ?ProjectAssignment
    {
        return $this->entityManager->getRepository(ProjectAssignment::class)
            ->findOneBy(['project' => $project, 'user' => $user]);
    }

    private function isOwner($project, User $user): bool
    {
        return $project->getOwner() === $user;
    }

    private function hasPermission(ProjectAssignment $assignment, string $permissionName): bool
    {
        $role = $assignment->getRole();
        if (!$role) {
            return false;
        }

        // Fetch the Permission entity by name
        $permission = $this->entityManager->getRepository(Permission::class)
            ->findOneBy(['name' => $permissionName]);

        if (!$permission) {
            return false; // If the permission doesn't exist, deny access
        }

        // Check if RolePermission exists for this role and permission
        return (bool) $this->entityManager->getRepository(RolePermission::class)
            ->findOneBy(['role' => $role, 'permission' => $permission]);
    }
}
