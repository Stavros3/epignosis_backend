<?php

class VacationController
{
    private JWTHandler $jwtHandler;

    public function __construct(private VacationGateway $vacationGateway)
    {
        $this->jwtHandler = new JWTHandler();
    }

    public function processRequest(string $method, ?string $action, ?string $id): void
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

    // GET /vacations - Get all vacations (admin) or user's own vacations
    private function index(): void
    {
        $payload = $this->jwtHandler->requireAuth();
        
        $userId = $payload['user_id'];
        $roleId = $payload['role_id'];

        // Admin can see all vacations (ordered by pending first)
        if ($roleId === 1) {
            $vacations = $this->vacationGateway->getAllVacations();
        } else {
            // Regular users can only see their own vacations
            $vacations = $this->vacationGateway->getVacationsByUserId($userId);
        }

        http_response_code(200);
        echo json_encode($vacations);
    }

    // GET /vacations/{id} - Get single vacation
    private function show(int $id): void
    {
        $payload = $this->jwtHandler->requireAuth();
        
        if (!$this->vacationGateway->vacationExists($id)) {
            http_response_code(404);
            echo json_encode(['error' => 'Vacation not found']);
            return;
        }

        $vacation = $this->vacationGateway->getVacationById($id);

        // Check if user has permission to view this vacation
        // Admin can view all, regular users can only view their own
        if ($payload['role_id'] !== 1 && $vacation['user_id'] !== $payload['user_id']) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied']);
            return;
        }

        http_response_code(200);
        echo json_encode($vacation);
    }

    // POST /vacations - Create new vacation request
    private function store(): void
    {
        $payload = $this->jwtHandler->requireAuth();
        
        $data = (array) json_decode(file_get_contents('php://input'), true);
        
        // Add user_id from token
        $data['user_id'] = $payload['user_id'];

        $errors = $this->getValidationErrors($data);
        if (!empty($errors)) {
            http_response_code(422);
            echo json_encode(['errors' => $errors]);
            return;
        }

        $newVacationId = $this->vacationGateway->createVacation($data);
        
        http_response_code(201);
        echo json_encode([
            'message' => 'Vacation request created successfully',
            'id' => $newVacationId,
            'status' => 'PENDING'
        ]);
    }

    // PUT/PATCH /vacations/{id} - Update vacation status (admin only)
    private function update(int $id): void
    {
        // Require admin role
        $payload = $this->jwtHandler->requireRole(1);
        
        if (!$this->vacationGateway->vacationExists($id)) {
            http_response_code(404);
            echo json_encode(['error' => 'Vacation not found']);
            return;
        }

        $data = (array) json_decode(file_get_contents('php://input'), true);

        // Admin can only update status
        if (isset($data['status_id'])) {
            $this->updateStatus($id, $data);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'status_id is required']);
        }
    }

    // Helper method to update vacation status (admin only)
    private function updateStatus(int $id, array $data): void
    {
        $statusId = (int) $data['status_id'];

        // Validate status_id (1=APPROVED, 2=REJECTED, 3=PENDING)
        if (!in_array($statusId, [1, 2, 3])) {
            http_response_code(422);
            echo json_encode(['error' => 'Invalid status_id. Must be 1 (APPROVED), 2 (REJECTED), or 3 (PENDING)']);
            return;
        }

        $this->vacationGateway->updateVacationStatus($id, $statusId);
        
        $statusNames = [1 => 'APPROVED', 2 => 'REJECTED', 3 => 'PENDING'];
        
        http_response_code(200);
        echo json_encode([
            'message' => 'Vacation status updated successfully',
            'id' => $id,
            'status' => $statusNames[$statusId]
        ]);
    }

    // DELETE /vacations/{id} - Delete vacation (admin only)
    private function destroy(int $id): void
    {
        $this->jwtHandler->requireRole(1);
        
        if (!$this->vacationGateway->vacationExists($id)) {
            http_response_code(404);
            echo json_encode(['error' => 'Vacation not found']);
            return;
        }

        $this->vacationGateway->deleteVacation($id);
        
        http_response_code(200);
        echo json_encode(['message' => 'Vacation deleted successfully']);
    }

    // GET /vacations/statuses - Get all vacation statuses
    private function statuses(): void
    {
        $payload = $this->jwtHandler->requireAuth();
        
        $statuses = $this->vacationGateway->getAllStatuses();
        
        http_response_code(200);
        echo json_encode($statuses);
    }

    // GET /vacations/my - Explicitly get current user's vacations
    private function my(): void
    {
        $payload = $this->jwtHandler->requireAuth();
        
        $vacations = $this->vacationGateway->getVacationsByUserId($payload['user_id']);
        
        http_response_code(200);
        echo json_encode($vacations);
    }

    private function getValidationErrors(array $data, bool $isNew = true): array
    {
        $errors = [];

        if (empty($data['date_from'])) {
            $errors[] = 'Start date (date_from) is required';
        } elseif (!$this->isValidDate($data['date_from'])) {
            $errors[] = 'Start date (date_from) must be a valid date in format YYYY-MM-DD';
        }

        if (empty($data['date_to'])) {
            $errors[] = 'End date (date_to) is required';
        } elseif (!$this->isValidDate($data['date_to'])) {
            $errors[] = 'End date (date_to) must be a valid date in format YYYY-MM-DD';
        }

        // Check if date_to is after date_from
        if (!empty($data['date_from']) && !empty($data['date_to'])) {
            if (strtotime($data['date_to']) < strtotime($data['date_from'])) {
                $errors[] = 'End date must be after or equal to start date';
            }
        }

        if (empty($data['reason'])) {
            $errors[] = 'Reason is required';
        } elseif (strlen($data['reason']) < 10) {
            $errors[] = 'Reason must be at least 10 characters long';
        }

        return $errors;
    }

    private function isValidDate(string $date): bool
    {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
}
