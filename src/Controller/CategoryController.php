<?php 
namespace App\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\SerializerInterface;
use App\Repository\RoleRepository;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use App\Service\EntityValidatorService;

#[Route('/api')]
class CategoryController extends AbstractController
{
    private $authorizationChecker;
    private $serializer;
    private $entityManager;
    private $entityValidatorService;


    public function __construct(EntityValidatorService $entityValidatorService,SerializerInterface $serializer,EntityManagerInterface $entityManager,AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->entityManager=$entityManager;
        $this->serializer=$serializer;
        $this->entityValidatorService=$entityValidatorService;

    }
    
    #[Route('/category', name: 'api_category_index', methods: ['GET'])]
    public function index(CategoryRepository $categoryRepository, UserInterface $user): Response
    {
        try {
            $categories = $categoryRepository->findBy(['user' => $user]);
            $categoriesArray = $this->serializer->normalize($categories, null, ['groups' => 'category_read']);

            return $this->json([
                'categories' => $categoriesArray,
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'An error occurred while fetching categories.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/category', name: 'api_category_create', methods: ['POST'])]
    public function create(Request $request, RoleRepository $roleRepository, UserInterface $user): Response
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['name'])) {
                return $this->json(['error' => 'Please provide the Category name.'], Response::HTTP_BAD_REQUEST);
            }

            $categoryName=$data['name'];
            $existingCategory = $this->entityManager->getRepository(Category::class)->findOneBy([
                'user' => $user,
                'name' => $categoryName
            ]);

            if ($existingCategory) {
                return $this->json(['error' => 'Category with this name already exists for the user.'], Response::HTTP_CONFLICT);
            }

            $category = new Category();
            $category->setUser($user);
            $category->setName($categoryName);


            $defaultRole = $roleRepository->findOneBy(['name' => 'Owner']);
            
            if ($defaultRole) {
                $category->setRole($defaultRole); 
            } else {
                return $this->json(['error' => 'Role "Owner" not found.'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $validationResponse = $this->entityValidatorService->validateEntity($category);
            if ($validationResponse) {
                return $validationResponse; 
            }

            $this->entityManager->persist($category);
            $this->entityManager->flush();

            return $this->json(['message' => 'Category created successfully'], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(['error' => 'An error occurred while creating the category.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/category/{id}', name: 'api_category_update', methods: ['PUT'])]
    public function update($id, Request $request, UserInterface $user): Response
    {
        try {
            $category = $this->entityManager->getRepository(Category::class)->find($id);

            if (!$category) {
                return $this->json(['error' => 'Category not found.'], Response::HTTP_NOT_FOUND);
            }
     
            if (!$this->authorizationChecker->isGranted('update', $category)) {
                return $this->json(['error' => 'You do not have permission to update this category.'], Response::HTTP_FORBIDDEN);
            }

            $data = json_decode($request->getContent(), true);
            $categoryName=$data['name'] ?? $category->getName();


            $existingCategory = $this->entityManager->getRepository(Category::class)->findOneBy([
                'user' => $user,
                'name' => $categoryName
            ]);

            if($existingCategory && $existingCategory->getId() !== $category->getId()){
                return $this->json(['error' => 'A category with this name already exists.'], Response::HTTP_CONFLICT);

            }

            $category->setName($categoryName);
            $this->entityManager->flush();

            return $this->json(['message' => 'Category updated successfully'], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json(['error' => 'An error occurred while updating the category.'.$e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/category/{id}', name: 'api_category_delete', methods: ['DELETE'])]
    public function delete($id, UserInterface $user): Response
    {
        try {
            $category = $this->entityManager->getRepository(Category::class)->find($id);

            if (!$category) {
                return $this->json(['error' => 'Category not found.'], Response::HTTP_NOT_FOUND);
            }

            if (!$this->authorizationChecker->isGranted('delete', $category)) {
                return $this->json(['error' => 'You do not have permission to delete this category.'], Response::HTTP_FORBIDDEN);
            }

            $this->entityManager->remove($category);
            $this->entityManager->flush();

            return $this->json(['message' => 'Category deleted successfully'], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json(['error' => 'An error occurred while deleting the category.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
