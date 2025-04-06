<?php 
namespace App\Security;

use App\Entity\Category;
use App\Entity\User;
use App\Entity\Role;
use App\Entity\Permission;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class CategoryPermissionVoter extends Voter
{
    const VIEW = 'view';
    const UPDATE = 'update';
    const DELETE = 'delete';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::UPDATE, self::DELETE], true) &&
               $subject instanceof Category;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        switch ($attribute) {
            case self::VIEW:
                return $this->hasPermission($user, $attribute, $subject);

            case self::UPDATE:
                return $this->canUpdate($user, $subject);

            case self::DELETE:
                return $this->canDelete($user, $subject);
        }

        return false;
    }

    private function hasPermission(User $user, string $attribute, Category $category): bool
    {
        foreach ($user->getRoles() as $role) {
            if ($role instanceof Role) {
                foreach ($role->getPermissions() as $permission) {
                    if ($this->isPermissionValid($permission, $attribute, $category)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    private function canUpdate(User $user, Category $category): bool
    {
        if ($category->getUser() === $user) {
            return true;
        }

        return $this->hasPermission($user, self::UPDATE, $category);
    }

    private function canDelete(User $user, Category $category): bool
    {
        if ($category->getUser() === $user) {
            return true;
        }

        return $this->hasPermission($user, self::DELETE, $category);
    }

    private function isPermissionValid(Permission $permission, string $attribute, Category $category): bool
    {
        return strtolower($permission->getAction()) === $attribute &&
               strtolower($permission->getEntity()) === strtolower(get_class($category));
    }
}
