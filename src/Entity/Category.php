<?php
namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Entity\User;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['category_read'])] 
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['category_read', "project_read"])] 
    #[Assert\NotBlank(message: "The category name cannot be empty.")]
    #[Assert\Length(min: 3, minMessage: "The category name must be at least 3 characters long.")]
    private ?string $name = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'categories')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\OneToMany(targetEntity: Project::class, mappedBy: 'category')]
    private $projects;

    #[ORM\ManyToOne(targetEntity: Role::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Role $role;
    

    public function __construct()
    {
        $this->projects = new ArrayCollection();
    
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;
        return $this;
    }
    public function getRole(): Role
    {
        return $this->role;
    }

    // Setter for role
    public function setRole(Role $role): self
    {
        $this->role = $role;
        return $this;
    }

    public function getProjects()
    {
        return $this->projects;
    }

    public function addProject(Project $project): self
    {
        $this->projects[] = $project;
        return $this;
    }
}
