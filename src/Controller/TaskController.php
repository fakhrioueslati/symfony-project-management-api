<?php
namespace App\Controller;

use App\Entity\Task;
use App\Entity\Project;
use App\Entity\TaskAssignment;
use App\Entity\Category;
use App\Entity\User;
use App\Entity\Status;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use App\Service\TaskAssignmentService;
use App\Service\EntityValidatorService;


#[Route('/api')]
class TaskController extends AbstractController
{
    private $authorizationChecker;
    private $taskAssignmentService;
    private $entityValidatorService;
    private $serializer;
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager,SerializerInterface $serializer,EntityValidatorService $entityValidatorService,AuthorizationCheckerInterface $authorizationChecker,TaskAssignmentService $taskAssignmentService)
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->taskAssignmentService = $taskAssignmentService;
        $this->entityValidatorService=$entityValidatorService;
        $this->serializer=$serializer;
        $this->entityManager=$entityManager;
        
    }

    #[Route('/task', name: 'api_task_index', methods: ['GET'])]
    public function index(TaskRepository $taskRepository,UserInterface $user): Response
    {
        try {
            $ownedTasks = $taskRepository->findTasksByOwner($user);
        
            $assignedTasks = $taskRepository->findAllAssignedTasks($user);
    
            $ownedTasksArray = $this->serializer->normalize($ownedTasks, null, ['groups' => 'task_read']);
            $assignedTasksArray = $this->serializer->normalize($assignedTasks, null, ['groups' => 'task_read']);
    
            return $this->json([
                'tasks' => $ownedTasksArray,        
                'assignedTasks' => $assignedTasksArray, 
            ]);
     

        } catch (\Exception $e) {
            return $this->json(['error' => 'An error occurred while fetching tasks.'.$e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/task/{id}', name: 'api_task_show', methods: ['GET'])]
    public function show(int $id, TaskRepository $taskRepository,UserInterface $user): Response
    {
        try {
            $task = $taskRepository->find($id);
            
            if (!$task) {
                return $this->json(['error' => 'Task not found.'], Response::HTTP_NOT_FOUND);
            }
            if (!$this->authorizationChecker->isGranted('view', $task)) {
                return $this->json(['error' => 'You do not have permission to view this task.'], Response::HTTP_FORBIDDEN);
            }

            $taskArray = $this->serializer->normalize($task, null, ['groups' => 'task_read']);

            return $this->json([
                'task' => $taskArray,
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'An error occurred while fetching the task.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/tasks/assigned-only', name: 'api_task_assigned_only', methods: ['GET'])]
    public function getAssignedTasksOnly(TaskRepository $taskRepository,UserInterface $user): Response
    {
        try {
            $tasks = $taskRepository->findAllAssignedTasks($user);

            if (empty($tasks)) {
                return $this->json(['error' => 'No assigned tasks found for the user.'], Response::HTTP_NOT_FOUND);
            }

            $tasksArray = $this->serializer->normalize($tasks, null, ['groups' => 'task_assigned']);

            return $this->json([
                'tasks' => $tasksArray,
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'An error occurred while fetching tasks.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/task', name: 'api_task_create', methods: ['POST'])]
    public function create(Request $request,UserInterface $user): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            if (!isset($data['title']) || !isset($data['startDate']) || !isset($data['project_id'])) {
                return $this->json(['error' => 'Please provide title, startDate, and project_id.'], Response::HTTP_BAD_REQUEST);
            }
            

            $task = new Task();
            $taskTitle=$data['title'];
            $taskDescription=$data['description']??"";
            $startDate=$data['startDate'] ;
            $endDate=$data['endDate']?? null;
            

            $defaultStatus = $this->entityManager->getRepository(Status::class)->findOneBy(['name' => 'To Do']);
            if ($defaultStatus) {
                $task->setStatus($defaultStatus);
            }

         
            if (empty($startDate)) {
                return $this->json(['error' => 'Start date is required.'], Response::HTTP_BAD_REQUEST);
            } else {
                $startDate = \DateTime::createFromFormat('Y-m-d\TH:i:s', $startDate);
                if (!$startDate) {
                    return $this->json(['error' => 'Invalid start date format. Use "YYYY-MM-DDTHH:MM:SS" format.'], Response::HTTP_BAD_REQUEST);
                }
            }
    
            if (isset($endDate)) { 
                if (empty($endDate)) { 
                    return $this->json(['error' => 'End date is required.'], Response::HTTP_BAD_REQUEST);
                }
                
                $endDate = \DateTime::createFromFormat('Y-m-d\TH:i:s', $endDate);
                
                if (!$endDate) {
                    return $this->json(['error' => 'Invalid end date format. Use "YYYY-MM-DDTHH:MM:SS" format.'], Response::HTTP_BAD_REQUEST);
                }
            }
            
    
            $task->setTitle($taskTitle);
            $task->setDescription($taskDescription);    
            $task->setStartDate($startDate);    
            $task->setEndDate($endDate);    

            if (isset($data['project_id'])) {
                $project = $this->entityManager->getRepository(Project::class)->findOneBy([
                    'id' => $data['project_id']]);
                if ($project) {
                    $task->setProject($project);
                } else {
                    return $this->json(['error' => 'Invalid project ID.'], Response::HTTP_BAD_REQUEST);
                }
            }
            else {
                return $this->json(['error' => 'Project id is required.'], Response::HTTP_BAD_REQUEST);

            }
            if (!$this->authorizationChecker->isGranted('create', $task)) {
                return $this->json(['error' => 'You do not have permission to create this task.'], Response::HTTP_FORBIDDEN);
            }

            $validationResponse = $this->entityValidatorService->validateEntity($task);
            if ($validationResponse) {
                return $validationResponse; 
            }

            
            if (isset($data['taskAssignments'])) {
                foreach ($data['taskAssignments'] as $taskAssignmentData) {
                    $user = $this->entityManager->getRepository(User::class)->find($taskAssignmentData);
                    
                    if (!$user) {
                        return $this->json(['error' => 'User with ID ' . $taskAssignmentData . ' not found.'], Response::HTTP_BAD_REQUEST);
                    }
   
                    if ($user) {
                        $taskAssignment = new TaskAssignment();
                        $taskAssignment->setUser($user);
                        $taskAssignment->setTask($task);
                        $task->addAssignment($taskAssignment);
                    }
                }
            }

            $this->entityManager->persist($task);
            $this->entityManager->flush();

            return $this->json(['message' => 'Task created successfully'], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(['error' => 'An error occurred while creating the task.'.$e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/task/{id}', name: 'api_task_update', methods: ['PUT'])]
    public function update(int $id, Request $request,UserInterface $user): Response
    {
        try {
            $task = $this->entityManager->getRepository(Task::class)->find($id);

            if (!$task) {
                return $this->json(['error' => 'Task not found.'], Response::HTTP_NOT_FOUND);
            }
            $data = json_decode($request->getContent(), true);

            $task->setTitle($data['title'] ?? $task->getTitle());
            $task->setDescription($data['description'] ?? $task->getDescription());

            // Handle Start Date
            if (isset($data['startDate'])) { 
                if ($data['startDate'] !== null) { 
                    $startDate = \DateTime::createFromFormat('Y-m-d\TH:i:s', $data['startDate']);
                    if (!$startDate) {
                        return $this->json(['error' => 'Invalid start date format. Use "YYYY-MM-DDTHH:MM:SS".'], Response::HTTP_BAD_REQUEST);
                    }
                    $task->setStartDate($startDate);
                }
            } else {
                $task->setStartDate($task->getStartDate()); 
            }

            if (isset($data['endDate'])) { 
                if ($data['endDate'] !== null) { 
                    $endDate = \DateTime::createFromFormat('Y-m-d\TH:i:s', $data['endDate']);
                    if (!$endDate) {
                        return $this->json(['error' => 'Invalid end date format. Use "YYYY-MM-DDTHH:MM:SS".'], Response::HTTP_BAD_REQUEST);
                    }
                    $task->setEndDate($endDate);
                }
            } else {
                $task->setEndDate($task->getEndDate());
            }


            if (!$this->authorizationChecker->isGranted('update', $task)) {
                return $this->json(['error' => 'You do not have permission to update this task.'], Response::HTTP_FORBIDDEN);
            }

            $validationResponse = $this->entityValidatorService->validateEntity($task);
            if ($validationResponse) {
                return $validationResponse; 
            }

            $response = $this->taskAssignmentService->handleTaskAssignments($data, $task, $user, "update");
            if ($response->getStatusCode() !== Response::HTTP_OK) {
                return $response; 
            }
            $this->entityManager->persist($task);
            $this->entityManager->flush(); 

            return $this->json(['message' => 'Task updated successfully'], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json(['error' => 'An error occurred while updating the task.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/task/{id}', name: 'api_task_delete', methods: ['DELETE'])]
    public function delete(int $id,UserInterface $user): Response
    {
        try {
            $task = $this->entityManager->getRepository(Task::class)->find($id);

            if (!$task) {
                return $this->json(['error' => 'Task not found.'], Response::HTTP_NOT_FOUND);
            }
            if (!$this->authorizationChecker->isGranted('delete', $task)) {
                return $this->json(['error' => 'You do not have permission to delete this task.'], Response::HTTP_FORBIDDEN);
            }


            $this->entityManager->remove($task);
            $this->entityManager->flush();

            return $this->json(['message' => 'Task deleted successfully'], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json(['error' => 'An error occurred while deleting the task.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/task/{id}/assign-task', name: 'api_task_assign', methods: ['POST'])]
    public function assignUsersToTask($id, Request $request,UserInterface $user): Response
    {
        try {
            $task = $this->entityManager->getRepository(Task::class)->find($id);
    
            if (!$task) {
                return $this->json(['error' => 'Task not found.'], Response::HTTP_NOT_FOUND);
            }
            
            if (!$this->authorizationChecker->isGranted('assign', $task)) {
                return $this->json(['error' => 'You do not have permission to assign users to this task.'], Response::HTTP_FORBIDDEN);
            }
    
            $data = json_decode($request->getContent(), true);
    
            $response = $this->taskAssignmentService->handleTaskAssignments($data, $task, $user);
            if ($response->getStatusCode() !== Response::HTTP_OK) {
                return $response; 
            }
            return $response; 

            
        } catch (\Exception $e) {
            return $this->json(['error' => 'An error occurred while assigning user to task. '], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
