<?php 
namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Task;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use App\Security\CommentVoter; 

#[Route('/api/comments')]
class CommentController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private AuthorizationCheckerInterface $authorizationChecker;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker, EntityManagerInterface $entityManager)
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->entityManager = $entityManager;
    }

    #[Route('/task/{id}', name: 'task_comments_list', methods: ['GET'])]
    public function getTaskComments(int $id, CommentRepository $commentRepository): Response
    {
        $comments = $commentRepository->findBy(['task' => $id]);

        return $this->json($comments, Response::HTTP_OK, [], ['groups' => 'comment_read']);
    }

    #[Route('/task/{id}', name: 'task_comment_create', methods: ['POST'])]
    public function createComment(Request $request, int $id, UserInterface $user): Response
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['content']) || empty($data['content'])) {
                return $this->json(['error' => 'Comment content is required.'], Response::HTTP_BAD_REQUEST);
            }

            $task = $this->entityManager->getRepository(Task::class)->find($id);

            if (!$task) {
                return $this->json(['error' => 'Task not found.'], Response::HTTP_NOT_FOUND);
            }

            $comment = new Comment();
            $comment->setContent($data['content']);
            $comment->setUser($user);
            $comment->setTask($task);

            // Check permissions before persisting
            if (!$this->authorizationChecker->isGranted(CommentVoter::CREATE, $comment)) {
                return $this->json(['error' => 'You do not have permission to comment on this task.'], Response::HTTP_FORBIDDEN);
            }

            $this->entityManager->persist($comment);
            $this->entityManager->flush();

            return $this->json(['message' => 'Comment created successfully'], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(['error' => 'An error occurred while creating the comment: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'comment_update', methods: ['PUT'])]
    public function updateComment(int $id, Request $request): Response
    {
        try {
            $comment = $this->entityManager->getRepository(Comment::class)->find($id);

            if (!$comment) {
                return $this->json(['error' => 'Comment not found.'], Response::HTTP_NOT_FOUND);
            }

            // Check permissions before updating
            if (!$this->authorizationChecker->isGranted(CommentVoter::UPDATE, $comment)) {
                return $this->json(['error' => 'You do not have permission to update this comment'], Response::HTTP_FORBIDDEN);
            }

            $data = json_decode($request->getContent(), true);

            if (isset($data['content'])) {
                $comment->setContent($data['content']);
            }

            if (isset($data['task'])) {
                $task = $this->entityManager->getRepository(Task::class)->find($data['task']);
                if ($task) {
                    $comment->setTask($task);
                } else {
                    return $this->json(['error' => 'Task not found.'], Response::HTTP_BAD_REQUEST);
                }
            }

            $this->entityManager->flush();

            return $this->json(['message' => 'Comment updated successfully'], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json(['error' => 'An error occurred while updating the comment: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'comment_delete', methods: ['DELETE'])]
    public function deleteComment(int $id): Response
    {
        try {
            $comment = $this->entityManager->getRepository(Comment::class)->find($id);

            if (!$comment) {
                return $this->json(['error' => 'Comment not found.'], Response::HTTP_NOT_FOUND);
            }

            if (!$this->authorizationChecker->isGranted(CommentVoter::DELETE, $comment)) {
                return $this->json(['error' => 'You do not have permission to delete this comment.'], Response::HTTP_FORBIDDEN);
            }

            $this->entityManager->remove($comment);
            $this->entityManager->flush();

            return $this->json(['message' => 'Comment deleted successfully.'], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json(['error' => 'An error occurred while deleting the comment: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
