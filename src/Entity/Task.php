<?php 
namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\TaskRepository;
use App\Repository\StatusRepository;

#[ORM\Entity(repositoryClass: TaskRepository::class)]
class Task
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['task_read','task_assigned'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['task_read','task_assigned'])]
    #[Assert\NotBlank(message: "Task title is required.")]  
    #[Assert\Length(min: 3, minMessage: "Task title must be at least {{ limit }} characters long.")]
    private string $title;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['task_read','task_assigned'])]
    #[Assert\Length(
        max: 500, 
        maxMessage: "Description cannot be longer than {{ limit }} characters.",
        )]
    private ?string $description = null;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['task_read', 'task_assigned'])]
    #[Assert\NotNull(message: "The start date is required.")]
    #[Assert\NotBlank(message: "Start date is required.")]
    #[Assert\Type(type: "\DateTimeInterface", message: "The start date must be a valid datetime.")]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['task_read', 'task_assigned'])]
    #[Assert\Type(type: "\DateTimeInterface", message: "The end date must be a valid datetime.")]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'tasks')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['task_read','task_assigned'])]
    #[Assert\NotNull(message: "Task must belong to a project.")]
    private ?Project $project = null;

    #[ORM\ManyToOne(targetEntity: Status::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['task_read','task_assigned'])]
    private ?Status $status = null;

    #[ORM\OneToMany(mappedBy: 'task', targetEntity: TaskAssignment::class, cascade: ['persist', 'remove'])]
    private Collection $assignments;

    public function __construct()
    {
        $this->assignments = new ArrayCollection();

      
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): self
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeInterface $endDate): self
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): self
    {
        $this->project = $project;
        return $this;
    }

    public function getStatus(): ?Status
    {
        return $this->status;
    }

    public function setStatus(Status $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getAssignments(): Collection
    {
        return $this->assignments;
    }

    public function addAssignment(TaskAssignment $assignment): self
    {
        if (!$this->assignments->contains($assignment)) {
            $this->assignments[] = $assignment;
            $assignment->setTask($this);
        }
        return $this;
    }

    public function removeAssignment(TaskAssignment $assignment): self
    {
        if ($this->assignments->contains($assignment)) {
            $this->assignments->removeElement($assignment);
        }
        return $this;
    }
}
