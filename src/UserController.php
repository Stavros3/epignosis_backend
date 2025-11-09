<?php

class UserController
{
   private JWTHandler $jwtHandler;

   public function __construct(private UsersGateway $usersGateway)
   {
       //$this->usersGateway = $usersGateway;
       $this->jwtHandler = new JWTHandler();
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
        $this->jwtHandler->requireRole(UserRole::ADMIN->value);

        echo json_encode($this->usersGateway->getAllUsers());
     }

     // GET /users/{id} - Get single user
     private function show(int $id): void
     {
        $payload = $this->jwtHandler->requireAuth();
        
        if ($payload['user_id'] !== $id) {
            $this->jwtHandler->requireRole(UserRole::ADMIN->value);
        }
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
        $this->jwtHandler->requireRole(UserRole::ADMIN->value);
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
        $this->jwtHandler->requireRole(UserRole::ADMIN->value);
        if (!$this->usersGateway->userExists($id)) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            return;
        }
        $this->usersGateway->deleteUser($id);
        http_response_code(200);
        echo json_encode(['message' => 'User deleted']);
     }

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
            // Generate JWT token
            $token = $this->jwtHandler->generateToken([
                'user_id' => $user['id'],
                'username' => $user['username'],
                'role_id' => $user['roles_id']
            ]);

            unset($user['password']);
            http_response_code(200);
            echo json_encode([
                'message' => 'Authentication successful',
                'token' => $token,
                'user' => $user
            ]);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid credentials']);
        }
     }



     // GET /users/profile - Get current user's profile (requires authentication)
     private function profile(): void
     {
        // Require authentication and get payload
        $payload = $this->jwtHandler->requireAuth();
        
        $userId = $payload['user_id'];
        $user = $this->usersGateway->getUserById($userId);
        
        if ($user) {
            http_response_code(200);
            echo json_encode(['user' => $user]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
        }
     }

     // POST /users/validate - Validate JWT token
     private function validate(): void
     {
        $payload = $this->jwtHandler->requireAuth();
        
        http_response_code(200);
        echo json_encode([
            'valid' => true,
            'user_id' => $payload['user_id'],
            'username' => $payload['username'],
            'role_id' => $payload['role_id']
        ]);
     }

     // GET /users/admin - Admin only endpoint (requires admin role)
     private function admin(): void
     {
        // Require admin role
        $payload = $this->jwtHandler->requireRole(UserRole::ADMIN->value);
        
        http_response_code(200);
        echo json_encode([
            'message' => 'Welcome admin!',
            'users' => $this->usersGateway->getAllUsers()
        ]);
     }

     // Helper method to check if user has permission for action
     private function checkPermission(int $userId): bool
     {
        $token = $this->jwtHandler->getTokenFromHeader();
        
        if (!$token) {
            return false;
        }

        $payload = $this->jwtHandler->validateToken($token);
        
        if (!$payload) {
            return false;
        }

        // Users can only modify their own data, unless they're admin
        return $payload['user_id'] === $userId || $payload['role_id'] === UserRole::ADMIN->value;
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