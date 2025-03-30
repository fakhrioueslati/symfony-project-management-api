<?php 
namespace App\Controller;

use App\Repository\StatusRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api')]
class StatusController extends AbstractController
{
    #[Route('/status', name: 'api_status_index', methods: ['GET'])]
    public function index(StatusRepository $statusRepository, SerializerInterface $serializer): Response
    {
        try {
            $statuses = $statusRepository->findAll();
            
            $statusesArray = $serializer->normalize($statuses, null, ['groups' => 'status_read']);

            return $this->json([
                'statuses' => $statusesArray,
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'An error occurred while fetching statuses.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/status/{id}', name: 'api_status_show', methods: ['GET'])]
    public function show(int $id, StatusRepository $statusRepository, SerializerInterface $serializer): Response
    {
        try {
            $status = $statusRepository->find($id);

            if (!$status) {
                return $this->json(['error' => 'Status not found.'], Response::HTTP_NOT_FOUND);
            }

            $statusArray = $serializer->normalize($status, null, ['groups' => 'status_read']);

            return $this->json([
                'status' => $statusArray,
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'An error occurred while fetching the status.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
