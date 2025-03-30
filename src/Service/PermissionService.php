<?php

namespace App\Service;

use App\Entity\Project;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class PermissionService
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function isOwner(UserInterface $user, Project $project): bool
    {
        return $project->getOwner() === $user;
    }

    public function hasAccess(UserInterface $user, Project $project): bool
    {
        return $this->isOwner($user, $project) || $this->isInWorkingGroup($user, $project);
    }

    public function isInWorkingGroup(UserInterface $user, Project $project): bool
    {
        foreach ($project->getWorkingGroups() as $workingGroup) {
            if ($workingGroup->getUser() === $user) {
                return true;
            }
        }
        return false;
    }
}
