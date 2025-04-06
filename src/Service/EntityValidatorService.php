<?php 
namespace App\Service;

use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\Response;

class EntityValidatorService
{
    private $validator;

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    public function validateEntity($entity): ?Response
    {
        try {
            $errors = $this->validator->validate($entity);
            
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                return new Response(
                    json_encode(['error' => 'Validation failed', 'messages' => $errorMessages]),
                    Response::HTTP_BAD_REQUEST,
                    ['Content-Type' => 'application/json']
                );
            }
            
            return null;
        } catch (\Exception $e) {
            return new Response(
                json_encode(['error' => 'An unexpected error occurred during validation.']),
                Response::HTTP_INTERNAL_SERVER_ERROR,
                ['Content-Type' => 'application/json']
            );
        }
    }
}
