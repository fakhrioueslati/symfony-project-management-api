<?php 
// src/Entity/RolePermission.php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'App\Repository\RolePermissionRepository')]
class RolePermission
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Role::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Role $role;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Permission::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Permission $permission;

    public function getRole(): Role
    {
        return $this->role;
    }

    public function setRole(Role $role): self
    {
        $this->role = $role;
        return $this;
    }

    public function getPermission(): Permission
    {
        return $this->permission;
    }

    public function setPermission(Permission $permission): self
    {
        $this->permission = $permission;
        return $this;
    }
}
