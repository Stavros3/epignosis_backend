<?php

class UserController
{
   public function __construct(private UsersGateway $usersGateway)
   {
       //$this->usersGateway = $usersGateway;
   }

    public function processRequest (string $method, ?string $id): void
    {

        if ($id) {
            $this->processResourceRequest($method, $id);
        } else {
            $this->processCollectionRequest($method);
        }


        //return dump json
        /* echo json_encode([
            'method' => $method,
            'id' => $id
        ]); */
      //var_dump($method, $id);
        
        /* switch ($method) {
            case 'GET':
                $this->index();
                break;
            default:
                http_response_code(405);
                echo "Method Not Allowed";
                break;
        } */
    }



     private function processResourceRequest(string $method, ?string $id): void 
     {
        // Implement resource-specific request processing here
     }

     private function processCollectionRequest(string $method): void 
     {
        // Implement collection-specific request processing here

        switch ($method) {
            case 'GET':
                echo json_encode($this->usersGateway->getAllUsers());
                break;
                case 'POST':
                // Handle user creation
                $data = (array) json_decode(file_get_contents('php://input'), true);
                $errors = $this->getValidationErrors($data);
                if (!empty($errors)) {
                    http_response_code(422);
                    echo json_encode(['errors' => $errors]);
                    break;
                }
                //$data = json_decode($data, true);
                var_dump($data);

                $newUserId = $this->usersGateway->createUser($data);
                http_response_code(201);
                echo json_encode(['message' => 'User created', 'id' => $newUserId]); 
                break;
            default:
                http_response_code(405);
                echo "Method Not Allowed";
                break;
        }
     }

     private function getValidationErrors(array $data, bool $isNew = true): array
     {
        $errors = [];

            if ($isNew && empty($data['password'])) {
                $errors[] = 'Password is required';
            }
    
            if (empty($data['name'])) {
                $errors[] = 'Name is required';
            }
    
            if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Valid email is required';
            }
    
            if (empty($data['username'])) {
                $errors[] = 'Username is required';
            }

        return $errors;
     }
}