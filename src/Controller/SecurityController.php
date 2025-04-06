<?php 

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface; // Correct interface import
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\EntityValidatorService; 


#[Route('/api')]  
class SecurityController extends AbstractController
{
    private $jwtManager;
    private $passwordHasher;
    private $entityManager;
    private $validator;

    public function __construct(
        JWTTokenManagerInterface $jwtManager,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        EntityValidatorService $entityValidator
    ) {
        $this->jwtManager = $jwtManager;
        $this->passwordHasher = $passwordHasher;
        $this->entityManager = $entityManager;
        $this->entityValidator = $entityValidator;
    }

    #[Route('/register', name: 'app_register', methods: ['POST'])]
    public function register(Request $request, UserRepository $userRepository): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $missingFields = [];

            if (empty($data['email'])) {
                $missingFields[] = 'email';
            }
            if (empty($data['password'])) {
                $missingFields[] = 'password';
            }
            if (empty($data['username'])) {
                $missingFields[] = 'username';
            }

            if (!empty($missingFields)) {
                return $this->json(['error' => 'Missing fields', 'fields' => $missingFields], 400);
            }

            $existingUser = $userRepository->findOneBy(['email' => $data['email']]);
            if ($existingUser) {
                return $this->json(['error' => 'Email already exists'], 409);
            }

            $existingUsername = $userRepository->findOneBy(['username' => $data['username']]);
            if ($existingUsername) {
                return $this->json(['error' => 'Username already exists'], 409);
            }

            $user = new User();
            $user->setEmail($data['email']);
            $user->setUsername($data['username']);
            $user->setRoles(['ROLE_USER']);
            $user->setPassword($data['password']);

            $validationErrorResponse = $this->entityValidator->validateEntity($user);
            if ($validationErrorResponse) {
                return $validationErrorResponse; 
            }

            $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            return $this->json(['message' => 'User successfully registered.'], 201);

        } catch (\Exception $e) {
            return $this->json(['error' => 'An error occurred during registration: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/login', name: 'app_login', methods: ['POST'])]
    public function login(Request $request, UserRepository $userRepository): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (empty($data['email']) || empty($data['password'])) {
                return $this->json(['error' => 'Email and password are required'], 400);
            }

            $user = $userRepository->findOneBy(['email' => $data['email']]);
            if (!$user) {
                return $this->json(['error' => 'User not found'], 404);
            }

            $isPasswordValid = $this->passwordHasher->isPasswordValid($user, $data['password']);
            if (!$isPasswordValid) {
                return $this->json(['error' => 'Email or Password is incorrect'], 401);
            }

            $jwt = $this->jwtManager->create($user);
            
            return $this->json([
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'username' => $user->getUsername(),
                'token' => $jwt,

            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    

}
