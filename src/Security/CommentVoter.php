<?php 
namespace App\Security;

use App\Entity\Comment;
use App\Entity\User;
use App\Entity\Project;
use App\Entity\Task;
use App\Entity\ProjectAssignment;
use App\Entity\TaskAssignment;
use App\Entity\RolePermission;
use App\Entity\Permission;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class CommentVoter extends Voter
{
    const VIEW = 'view';
    const CREATE = 'create';
    const UPDATE = 'update';
    const DELETE = 'delete';

    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    protected function supports(string $attribute, $subject): bool
    {
        return $subject instanceof Comment && in_array($attribute, [
            self::VIEW, self::CREATE, self::UPDATE, self::DELETE
        ]);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }

        $comment = $subject;
        $task = $comment->getTask();
        $project = $task->getProject();

        $taskAssignment = $this->getTaskAssignment($task, $user);

        if ($attribute === self::CREATE) {
            if ($taskAssignment || $this->isManager($user, $project)  || $this->isOwner($project, $user)) {
                return true;
            }
            return false; 
        }

        $projectAssignment = $this->getProjectAssignment($project, $user);

        if ($this->isOwner($project, $user)) {
            return true;
        }

        if ($attribute === self::UPDATE && $comment->getUser() === $user) {
            return true;
        }

        $permissionMap = [
            self::VIEW => 'View Comment',
            self::CREATE => 'Add Comment',
            self::UPDATE => 'Edit Comment',
            self::DELETE => 'Delete Comment',
        ];

        return isset($permissionMap[$attribute]) && $this->hasPermission($projectAssignment, $permissionMap[$attribute]);
    }

    private function getTaskAssignment(Task $task, User $user): ?TaskAssignment
    {
        return $this->entityManager->getRepository(TaskAssignment::class)
            ->findOneBy(['task' => $task, 'user' => $user]);
    }

    private function getProjectAssignment(Project $project, User $user): ?ProjectAssignment
    {
        return $this->entityManager->getRepository(ProjectAssignment::class)
            ->findOneBy(['project' => $project, 'user' => $user]);
    }

    private function isOwner(Project $project, User $user): bool
    {
        return $project->getOwner() === $user;
    }

    private function isManager(User $user, Project $project): bool
    {
        $projectAssignment = $this->getProjectAssignment($project, $user);

        if (!$projectAssignment) {
            return false; 
        }

        return $projectAssignment->getRole()->getName() === 'Manager'; // Adjust role name as needed
    }

    private function hasPermission(ProjectAssignment $assignment, string $permissionName): bool
    {
        $role = $assignment->getRole();
        if (!$role) {
            return false;
        }

        $permission = $this->entityManager->getRepository(Permission::class)
            ->findOneBy(['name' => $permissionName]);

        if (!$permission) {
            return false; 
        }

        return (bool) $this->entityManager->getRepository(RolePermission::class)
            ->findOneBy(['role' => $role, 'permission' => $permission]);
    }
}
