<?php 
// src/Entity/ProjectAssignment.php
namespace App\Entity;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'App\Repository\ProjectAssignmentRepository')]
class ProjectAssignment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['project_read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: 'App\Entity\User')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['project_read'])]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: 'App\Entity\Project')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Project $project = null;

    #[ORM\ManyToOne(targetEntity: 'App\Entity\Role')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['project_read'])]
    private ?Role $role = null;

    public function getId(): ?int
    {
        return $this->id;
    }
    

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(Project $project): self
    {
        $this->project = $project;
        return $this;
    }

    public function getRole(): ?Role
    {
        return $this->role;
    }

    public function setRole(Role $role): self
    {
        $this->role = $role;
        return $this;
    }
}
