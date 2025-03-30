<?php 
namespace App\Security;

use App\Entity\Project;
use App\Entity\User;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class ProjectVoter extends Voter
{
    const VIEW = 'view';
    const CREATE = 'create';
    const UPDATE = 'update';
    const DELETE = 'delete';
    const ASSIGN_ROLE = 'assign_role';

    protected function supports(string $attribute, $subject): bool
    {
        // Check if the subject is a Project and the attribute is one of the defined actions
        return $subject instanceof Project && in_array($attribute, [self::VIEW, self::CREATE, self::UPDATE, self::DELETE, self::ASSIGN_ROLE]);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // Check if the user is authenticated
        if (!$user instanceof UserInterface) {
            return false;
        }

        /** @var Project $project */
        $project = $subject;

        switch ($attribute) {
            case self::VIEW:
                return $this->canView($project, $user);
            case self::CREATE:
                return $this->canCreate($user);
            case self::UPDATE:
                return $this->canUpdate($project, $user);
            case self::DELETE:
                return $this->canDelete($project, $user);
            case self::ASSIGN_ROLE:
                return $this->canAssignRole($project, $user);
            default:
                return false;
        }
    }

    private function canView(Project $project, User $user): bool
    {
        // Allow users to view a project if they are the owner or if they are assigned a role
        return $project->getOwner() === $user || $this->isAssignedToProject($project, $user);
    }

    private function canCreate(User $user): bool
    {
        // Allow any authenticated user to create a project (you can add custom logic here)
        return true; 
    }

    private function canUpdate(Project $project, User $user): bool
    {
        // Only allow the owner of the project to update it
        return $project->getOwner() === $user;
    }

    private function canDelete(Project $project, User $user): bool
    {
        // Only allow the owner of the project to delete it
        return $project->getOwner() === $user;
    }

    private function canAssignRole(Project $project, User $user): bool
    {
        // Only allow the owner of the project to assign roles
        return $project->getOwner() === $user;
    }

    private function isAssignedToProject(Project $project, User $user): bool
    {
        // You can check if the user is assigned to the project with a role
        foreach ($project->getRoleAssignments() as $assignment) {
            if ($assignment->getUser() === $user) {
                return true;
            }
        }
        return false;
    }
}
