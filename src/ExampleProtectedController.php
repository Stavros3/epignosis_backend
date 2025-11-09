<?php

/**
 * EXAMPLE: How to protect your existing endpoints with JWT
 * 
 * This file shows different patterns for adding authentication
 * and authorization to your controller methods.
 */

class ExampleProtectedController
{
    private JWTHandler $jwtHandler;

    public function __construct()
    {
        $this->jwtHandler = new JWTHandler();
    }

    // ========================================
    // Pattern 1: Require Authentication Only
    // ========================================
    
    /**
     * Any authenticated user can access this endpoint
     */
    private function getUserProfile(int $id): void
    {
        // Require valid JWT token
        $payload = $this->jwtHandler->requireAuth();
        
        // Now you can safely access the user's data from payload
        $currentUserId = $payload['user_id'];
        $currentUserRole = $payload['role_id'];
        
        // Your existing logic here...
        echo json_encode(['id' => $id, 'authenticated_user' => $currentUserId]);
    }

    // ========================================
    // Pattern 2: Require Specific Role
    // ========================================
    
    /**
     * Only admin users (role_id = 1) can access this endpoint
     */
    private function deleteUser(int $id): void
    {
        // Require admin role - automatically returns 403 if not admin
        $payload = $this->jwtHandler->requireRole(1);
        
        // If we reach here, user is admin
        // Your delete logic here...
        echo json_encode(['message' => 'User deleted', 'admin' => $payload['username']]);
    }

    // ========================================
    // Pattern 3: Optional Authentication
    // ========================================
    
    /**
     * Endpoint works with or without authentication
     * but provides different data based on auth status
     */
    private function getPublicContent(): void
    {
        $token = $this->jwtHandler->getTokenFromHeader();
        $isAuthenticated = false;
        $userId = null;

        if ($token) {
            $payload = $this->jwtHandler->validateToken($token);
            if ($payload) {
                $isAuthenticated = true;
                $userId = $payload['user_id'];
            }
        }

        if ($isAuthenticated) {
            // Return personalized content
            echo json_encode(['content' => 'Personalized', 'user_id' => $userId]);
        } else {
            // Return public content
            echo json_encode(['content' => 'Public']);
        }
    }

    // ========================================
    // Pattern 4: Check Ownership or Admin
    // ========================================
    
    /**
     * User can only update their own data, unless they're admin
     */
    private function updateUser(int $id): void
    {
        $payload = $this->jwtHandler->requireAuth();
        
        $currentUserId = $payload['user_id'];
        $currentUserRole = $payload['role_id'];
        
        // Check if user is updating their own data OR is admin
        if ($currentUserId !== $id && $currentUserRole !== 1) {
            http_response_code(403);
            echo json_encode(['error' => 'You can only update your own profile']);
            return;
        }
        
        // Proceed with update...
        echo json_encode(['message' => 'User updated', 'id' => $id]);
    }

    // ========================================
    // Pattern 5: Manual Role Checking
    // ========================================
    
    /**
     * Different responses based on user role
     */
    private function getDashboard(): void
    {
        $payload = $this->jwtHandler->requireAuth();
        
        $userRole = $payload['role_id'];
        
        if ($userRole === 1) {
            // Admin dashboard
            echo json_encode(['dashboard' => 'admin', 'data' => ['all_users', 'stats', 'reports']]);
        } elseif ($userRole === 2) {
            // Regular user dashboard
            echo json_encode(['dashboard' => 'user', 'data' => ['my_profile', 'my_activity']]);
        } else {
            echo json_encode(['dashboard' => 'guest', 'data' => ['limited_access']]);
        }
    }

    // ========================================
    // Pattern 6: Extract User Info
    // ========================================
    
    /**
     * Get user information from JWT without full validation
     * (useful for logging, audit trails, etc.)
     */
    private function logActivity(): void
    {
        // Quick extraction without requireAuth
        $userId = $this->jwtHandler->getUserIdFromToken();
        $roleId = $this->jwtHandler->getUserRoleFromToken();
        
        if ($userId) {
            // Log the activity with user context
            error_log("User $userId (role $roleId) performed action");
        }
        
        echo json_encode(['logged' => true]);
    }

    // ========================================
    // Pattern 7: Custom Role Logic
    // ========================================
    
    /**
     * Custom permission checking based on multiple factors
     */
    private function accessResource(int $resourceId): void
    {
        $payload = $this->jwtHandler->requireAuth();
        
        $userId = $payload['user_id'];
        $roleId = $payload['role_id'];
        
        // Custom logic: Admin OR owner OR specific permission
        $isAdmin = $roleId === 1;
        $isOwner = $this->checkResourceOwner($resourceId, $userId);
        $hasSpecialPermission = $this->checkSpecialPermission($userId, $resourceId);
        
        if (!($isAdmin || $isOwner || $hasSpecialPermission)) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied']);
            return;
        }
        
        // Proceed with resource access
        echo json_encode(['resource' => $resourceId, 'access' => 'granted']);
    }

    // Helper methods (examples)
    private function checkResourceOwner(int $resourceId, int $userId): bool
    {
        // Your logic to check if user owns the resource
        return true;
    }

    private function checkSpecialPermission(int $userId, int $resourceId): bool
    {
        // Your logic to check special permissions
        return false;
    }

    // ========================================
    // Pattern 8: Middleware-Style Protection
    // ========================================
    
    /**
     * Add this at the beginning of processRequest to protect all endpoints
     */
    public function processRequestProtected(string $method, ?string $action, ?string $id): void
    {
        // Public endpoints (no auth required)
        $publicEndpoints = ['authenticate', 'register'];
        
        // Check if this is a public endpoint
        if (!in_array($action, $publicEndpoints)) {
            // Require authentication for all other endpoints
            $payload = $this->jwtHandler->requireAuth();
            
            // Optionally store payload for use in methods
            // $this->currentUser = $payload;
        }
        
        // Continue with normal routing...
        // ... rest of your processRequest logic
    }

    // ========================================
    // Pattern 9: Error Handling
    // ========================================
    
    /**
     * Graceful error handling with try-catch
     */
    private function sensitiveOperation(): void
    {
        try {
            $payload = $this->jwtHandler->requireAuth();
            
            // Your sensitive operation here
            echo json_encode(['status' => 'success']);
            
        } catch (Exception $e) {
            // Handle any errors
            http_response_code(500);
            echo json_encode(['error' => 'Operation failed', 'message' => $e->getMessage()]);
        }
    }
}

/**
 * QUICK REFERENCE CHEAT SHEET
 * 
 * 1. Require Authentication:
 *    $payload = $this->jwtHandler->requireAuth();
 * 
 * 2. Require Admin Role:
 *    $payload = $this->jwtHandler->requireRole(1);
 * 
 * 3. Check if has role:
 *    if ($this->jwtHandler->hasRole(1)) { ... }
 * 
 * 4. Get user ID:
 *    $userId = $this->jwtHandler->getUserIdFromToken();
 * 
 * 5. Get user role:
 *    $roleId = $this->jwtHandler->getUserRoleFromToken();
 * 
 * 6. Manual validation:
 *    $token = $this->jwtHandler->getTokenFromHeader();
 *    $payload = $this->jwtHandler->validateToken($token);
 * 
 * 7. Generate token:
 *    $token = $this->jwtHandler->generateToken([
 *        'user_id' => $id,
 *        'username' => $username,
 *        'role_id' => $roleId
 *    ]);
 */
