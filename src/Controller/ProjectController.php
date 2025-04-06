<?php 
namespace App\Controller;

use App\Entity\Project;
use App\Entity\Category;
use App\Entity\User;
use App\Entity\Role;
use App\Entity\ProjectAssignment;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\ProjectAssignmentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\SerializerInterface;
use App\Repository\RoleRepository;
use App\Repository\CategoryRepository;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use App\Service\EntityValidatorService;

#[Route('/api')]
class ProjectController extends AbstractController
{
    private $authorizationChecker;
    private $projectAssignmentService;
    private $serializer;
    private $entityManager;
    private $entityValidatorService;



    public function __construct(EntityValidatorService $entityValidatorService,EntityManagerInterface $entityManager,SerializerInterface $serializer,AuthorizationCheckerInterface $authorizationChecker, ProjectAssignmentService $projectAssignmentService)
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->projectAssignmentService = $projectAssignmentService;
        $this->serializer=$serializer;
        $this->entityManager=$entityManager;
        $this->entityValidatorService=$entityValidatorService;
    }

    #[Route('/project', name: 'api_project_index', methods: ['GET'])]
    public function index(ProjectRepository $projectRepository, UserInterface $user): Response
    {
        try {
            $projects = $projectRepository->findBy(['owner' => $user]);
            $assignedProjects = $projectRepository->findAllAssignedProjects($user);
            $projectsArray = $this->serializer->normalize($projects, null, ['groups' => 'project_read']);
            $assignedProjectsArray = $this->serializer->normalize($assignedProjects, null, ['groups' => 'project_read']);
    
            return $this->json([
                'projects' => $projectsArray,        
                'assignedProject' => $assignedProjectsArray, 
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'An error occurred while fetching projects.'.$e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/project/{id}', name: 'api_project_show', methods: ['GET'])]
public function show(int $id,ProjectRepository $projectRepository, UserInterface $user): Response
{
    try {
        $project = $projectRepository->findByIdAndOwnerOrAssignedUser($id, $user);

        if (!$project) {
            return $this->json(['error' => 'Project not found or you do not have permission to view this project.'], Response::HTTP_NOT_FOUND);
        }

        $projectArray = $this->serializer->normalize($project, null, ['groups' => 'project_read']);

        return $this->json([
            'project' => $projectArray,
        ]);
    } catch (\Exception $e) {
        return $this->json(['error' => 'An error occurred while fetching the project.'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}

#[Route('/projects/assigned-only', name: 'api_project_assigned_only', methods: ['GET'])]
public function getAssignedProjectsOnly(ProjectRepository $projectRepository, UserInterface $user): Response
{
    try {
        $projects = $projectRepository->findAllAssignedProjects($user);

        if (empty($projects)) {
            return $this->json(['error' => 'No assigned projects found for the user.'], Response::HTTP_NOT_FOUND);
        }

        $projectsArray = $this->serializer->normalize($projects, null, ['groups' => 'project_read']);

        return $this->json([
            'projects' => $projectsArray,
        ]);
    } catch (\Exception $e) {
        return $this->json(['error' => 'An error occurred while fetching projects.'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}


    #[Route('/project', name: 'api_project_create', methods: ['POST'])]
    public function create(Request $request,RoleRepository $roleRepository, UserInterface $user): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            $projectName=$data['name'] ??''; 
            
            $existingProject = $this->entityManager->getRepository(Project::class)->findOneBy([
                'owner' => $user,
                'name' => $projectName
            ]);

            if ($existingProject) {

                return $this->json(['error' => 'Project with this name already exists.'], Response::HTTP_CONFLICT);
            }

            $project = new Project();
            $project->setOwner($user);
            $project->setName($projectName);   

            if (isset($data['category_id'])) {
                $category = $this->entityManager->getRepository(Category::class)->findOneBy([
                    'id' => $data['category_id'],
                    'user' => $user 
                ]);                
                if ($category) {
                    $project->setCategory($category);
                } else {
                    return $this->json(['error' => 'Invalid category ID.'], Response::HTTP_BAD_REQUEST);
                }
            } 
            
            $validationResponse = $this->entityValidatorService->validateEntity($project);
            if ($validationResponse) {
                return $validationResponse; 
            }

              if (isset($data['roleAssignments'])) {
            foreach ($data['roleAssignments'] as $roleAssignmentData) {
                $user = $this->entityManager->getRepository(User::class)->find($roleAssignmentData['user_id']);
                $role = $this->entityManager->getRepository(Role::class)->find($roleAssignmentData['role_id']);
                
                if (!$user) {
                    return $this->json(['error' => 'User with ID ' . $roleAssignmentData['user_id'] . ' not found.'], Response::HTTP_BAD_REQUEST);
                }
        
                if (!$role) {
                    return $this->json(['error' => 'Role with ID ' . $roleAssignmentData['role_id'] . ' not found.'], Response::HTTP_BAD_REQUEST);
                }
                if ($user && $role) {
                    $roleAssignment = new ProjectAssignment();
                    $roleAssignment->setUser($user);
                    $roleAssignment->setRole($role);
                    $roleAssignment->setProject($project);
                    $project->addRoleAssignment($roleAssignment);
                }
            }
        }
            
            $this->entityManager->persist($project);
            $this->entityManager->flush();

            return $this->json(['message' => 'Project created successfully'], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(['error' => 'An error occurred while creating the project.'.$e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/project/{id}', name: 'api_project_update', methods: ['PUT'])]
    public function update($id, Request $request,UserInterface $user): Response
    {
        try {
            $project = $this->entityManager->getRepository(Project::class)->find($id);
            if (!$project) {
                return $this->json(['error' => 'Project not found.'], Response::HTTP_NOT_FOUND);
            }

            if (!$this->authorizationChecker->isGranted('update', $project)) {
                return $this->json(['error' => 'You do not have permission to update this project.'], Response::HTTP_FORBIDDEN);
            }

            $data = json_decode($request->getContent(), true);
            $projectName=$data['name'] ?? $project->getName();
            $project->setName($projectName);

            $existingProject = $this->entityManager->getRepository(Project::class)->findOneBy([
                'owner' => $user,
                'name' => $projectName
            ]);

            if ($existingProject && $existingProject->getId() !== $project->getId()){

                return $this->json(['error' => 'Project with this name already exists.'], Response::HTTP_CONFLICT);
            }

            $validationResponse = $this->entityValidatorService->validateEntity($project);
            if ($validationResponse) {
                return $validationResponse; 
            }

            $response = $this->projectAssignmentService->handleRoleAssignments($data, $project, $user, "update");
            if ($response->getStatusCode() !== Response::HTTP_OK) {
                return $response; 
            }
    
            $this->entityManager->persist($project);
            $this->entityManager->flush(); 
    
            return $this->json(['message' => 'Project updated successfully'], Response::HTTP_OK);

        } catch (\Exception $e) {
            return $this->json(['error' => 'An error occurred while updating the project'.$e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/project/{id}', name: 'api_project_delete', methods: ['DELETE'])]
    public function delete($id,UserInterface $user): Response
    {
        try {
            $project = $this->entityManager->getRepository(Project::class)->find($id);

            if (!$project) {
                return $this->json(['error' => 'Project not found.'], Response::HTTP_NOT_FOUND);
            }

            if (!$this->authorizationChecker->isGranted('delete', $project)) {
                return $this->json(['error' => 'You do not have permission to delete this project.'], Response::HTTP_FORBIDDEN);
            }

            $this->entityManager->remove($project);
            $this->entityManager->flush();

            return $this->json(['message' => 'Project deleted successfully'], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json(['error' => 'An error occurred while deleting the project.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    #[Route('/project/{id}/assign-role', name: 'api_project_assign_roles', methods: ['POST'])]
    public function assignRolesToProject($id, Request $request, UserInterface $user): Response
    {
        try {
            $project = $this->entityManager->getRepository(Project::class)->find($id);
    
            if (!$project) {
                return $this->json(['error' => 'Project not found.'], Response::HTTP_NOT_FOUND);
            }
    
            if (!$this->authorizationChecker->isGranted('assign_role', $project)) {
                return $this->json(['error' => 'You do not have permission to assign roles to this project.'], Response::HTTP_FORBIDDEN);
            }
    
            $data = json_decode($request->getContent(), true);
    
            $response = $this->projectAssignmentService->handleRoleAssignments($data, $project, $user);
            return $response;
            
        } catch (\Exception $e) {
            return $this->json(['error' => 'An error occurred while assigning roles to project. '], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
        
}
