<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\ProjectRepository;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
class Project
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['project_read','task_read','task_assigned'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['project_read','task_read','task_assigned'])]
    #[Assert\NotBlank(message: "Project Name is required.")]
    #[Assert\Length(min: 3, minMessage: "Project name must be at least {{ limit }} characters long.")]
    private string $name;

    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['project_read'])]
    #[Assert\NotNull(message: "category_id is required.")]
    private ?Category $category = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['project_read'])]
    #[Assert\NotBlank(message: "User is required.")]
    private ?User $owner = null;

    #[ORM\OneToMany(mappedBy: 'project', targetEntity: ProjectAssignment::class, cascade: ['persist', 'remove'])]
    #[Groups(['project_read'])]
    private Collection $roleAssignments;

    #[ORM\OneToMany(mappedBy: 'project', targetEntity: Task::class, cascade: ['persist', 'remove'])]
    #[Groups(['project_read'])]
    private Collection $tasks;

    public function __construct()
    {
        $this->roleAssignments = new ArrayCollection();
        $this->tasks = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): self
    {
        $this->owner = $owner;
        return $this;
    }

    public function getRoleAssignments(): Collection
    {
        return $this->roleAssignments;
    }

    public function addRoleAssignment(ProjectAssignment $roleAssignment): self
    {
        if (!$this->roleAssignments->contains($roleAssignment)) {
            $this->roleAssignments[] = $roleAssignment;
            $roleAssignment->setProject($this);
        }

        return $this;
    }

    public function removeRoleAssignment(ProjectAssignment $roleAssignment): self
    {
        $this->roleAssignments->removeElement($roleAssignment);
        return $this;
    }

    public function getTasks(): Collection
    {
        return $this->tasks;
    }

    public function addTask(Task $task): self
    {
        if (!$this->tasks->contains($task)) {
            $this->tasks[] = $task;
            $task->setProject($this);
        }

        return $this;
    }

    public function removeTask(Task $task): self
    {
        $this->tasks->removeElement($task);
        return $this;
    }
}
