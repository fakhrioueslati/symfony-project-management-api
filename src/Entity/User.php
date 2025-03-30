<?php // src/Entity/User.php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['category_read', 'user_read', "project_read",'comment_read'])]  
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank]  
    #[Assert\Length(min: 3, minMessage: "Username must be at least {{ limit }} characters long.")]
    #[Groups(['category_read', 'user_read', "project_read",'comment_read'])]  
    private ?string $username = null;

    #[ORM\Column(length: 255, unique: true)] 
    #[Assert\NotBlank]
    #[Assert\Email(message: "Please provide a valid email address.")]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 6, minMessage: "Password must be at least {{ limit }} characters long.")]
    private ?string $password = null;

    #[ORM\Column(type: "json")]
    private array $roles = [];

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Category::class)]
    private iterable $categories;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Project::class)]
    private iterable $projects;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: ProjectAssignment::class)]
    private iterable $projectAssignments;

    public function __construct()
    {
        $this->categories = new ArrayCollection();
        $this->projects = new ArrayCollection();
        $this->projectAssignments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER'; 
        return array_unique($roles); 
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email; 
    }

    public function eraseCredentials(): void
    {
    }

    public function getCategories(): iterable
    {
        return $this->categories;
    }

    public function getProjects(): iterable
    {
        return $this->projects;
    }

    public function getProjectAssignment(): iterable
    {
        return $this->ProjectAssignments;
    }
}
