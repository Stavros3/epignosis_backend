<?php

class UserController
{
   public function __construct(private UsersGateway $usersGateway)
   {
       //$this->usersGateway = $usersGateway;
   }

    public function processRequest (string $method, ?string $action, ?string $id): void
    {
        try {
            // If action is numeric, it's an ID for resource-specific operations
            if ($action && is_numeric($action)) {
                $this->callMethod($method, (int) $action);
                return;
            }

            // Handle specific action methods
            if ($action) {
                $this->callMethod($action, $id);
                return;
            }

            // Default collection requests (index, store)
            $this->callMethod($method);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    private function callMethod(string $actionOrMethod, mixed $param = null): void
    {
        // Map HTTP methods to controller method names
        $methodMap = [
            'GET' => $param !== null && is_int($param) ? 'show' : 'index',
            'POST' => 'store',
            'PUT' => 'update',
            'PATCH' => 'update',
            'DELETE' => 'destroy'
        ];

        // Determine the method name to call
        if (isset($methodMap[$actionOrMethod])) {
            $methodName = $methodMap[$actionOrMethod];
        } else {
            // It's a custom action, convert to camelCase if needed
            $methodName = $actionOrMethod;
        }

        // Check if method exists
        if (!method_exists($this, $methodName)) {
            http_response_code(404);
            echo json_encode(['error' => "Action '{$methodName}' not found"]);
            return;
        }

        // Call the method with parameter if provided
        if ($param !== null) {
            $this->$methodName($param);
        } else {
            $this->$methodName();
        }
    }



     // GET /users - Get all users
     private function index(): void
     {
        echo json_encode($this->usersGateway->getAllUsers());
     }

     // GET /users/{id} - Get single user
     private function show(int $id): void
     {
        if (!$this->usersGateway->userExists($id)) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            return;
        }
        echo json_encode($this->usersGateway->getUserById($id));
     }

     // POST /users - Create new user
     private function store(): void
     {
        $data = (array) json_decode(file_get_contents('php://input'), true);
        $errors = $this->getValidationErrors($data);
        if (!empty($errors)) {
            http_response_code(422);
            echo json_encode(['errors' => $errors]);
            return;
        }
        $newUserId = $this->usersGateway->createUser($data);
        http_response_code(201);
        echo json_encode(['message' => 'User created', 'id' => $newUserId]);
     }

     // PUT/PATCH /users/{id} - Update user
     private function update(int $id): void
     {
        if (!$this->usersGateway->userExists($id)) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            return;
        }
        $data = (array) json_decode(file_get_contents('php://input'), true);
        $errors = $this->getValidationErrors($data, false);
        if (!empty($errors)) {
            http_response_code(422);
            echo json_encode(['errors' => $errors]);
            return;
        }
        $this->usersGateway->updateUser($id, $data);
        echo json_encode(['message' => 'User updated', 'id' => $id]);
     }

     // DELETE /users/{id} - Delete user
     private function destroy(int $id): void
     {
        if (!$this->usersGateway->userExists($id)) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            return;
        }
        $this->usersGateway->deleteUser($id);
        http_response_code(200);
        echo json_encode(['message' => 'User deleted']);
     }

     // POST /users/create - Alternative create endpoint
    /*  private function create(): void
     {
        $this->store();
     } */

     // POST /users/authenticate or /users/authenticateUser
     private function authenticate(): void
     {
        $data = (array) json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['username']) || empty($data['password'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Username and password are required']);
            return;
        }

        $user = $this->usersGateway->authenticateUser($data['username'], $data['password']);
        
        if ($user) {
            unset($user['password']);
            http_response_code(200);
            echo json_encode(['message' => 'Authentication successful', 'user' => $user]);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid credentials']);
        }
     }

     /* private function authenticateUser(): void
     {
        $this->authenticate();
     } */

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