# Enumerations Reference

## Overview
This document describes the enumerations used throughout the application to replace hardcoded numbers.

---

## UserRole Enum

**Location:** `src/enums/UserRole.php`

### Values:
```php
UserRole::ADMIN = 1  // Administrator with full access
UserRole::USER = 2   // Regular user with limited access
```

### Methods:
- `getName(): string` - Returns the role name as a string
- `isValid(int $value): bool` - Static method to check if a value is a valid role
- `isAdmin(): bool` - Check if the role is admin

### Usage Examples:

```php
// Check if user is admin
if ($roleId === UserRole::ADMIN->value) {
    // Admin logic
}

// Require admin role
$this->jwtHandler->requireRole(UserRole::ADMIN->value);

// Set default role when creating user
$roles_id = $data['roles_id'] ?? UserRole::USER->value;

// Get role name
$roleName = UserRole::ADMIN->getName(); // Returns "ADMIN"
```

### Where Used:
- `UserController.php` - All admin-only operations
- `VacationController.php` - Permission checks
- `UsersGateway.php` - Default role assignment
- `JWTHandler.php` - Role validation comments

---

## VacationStatus Enum

**Location:** `src/enums/VacationStatus.php`

### Values:
```php
VacationStatus::APPROVED = 1  // Vacation approved by admin
VacationStatus::REJECTED = 2  // Vacation rejected by admin
VacationStatus::PENDING = 3   // Vacation awaiting approval (default)
```

### Methods:
- `getName(): string` - Returns the status name as a string
- `isValid(int $value): bool` - Static method to check if a value is a valid status
- `getAll(): array` - Static method that returns all statuses as [id => name] array

### Usage Examples:

```php
// Create vacation with pending status
$status_id = VacationStatus::PENDING->value;

// Check if status is valid
if (VacationStatus::isValid($statusId)) {
    // Valid status
}

// Get status name
$statusName = VacationStatus::APPROVED->getName(); // Returns "APPROVED"

// Get all statuses
$allStatuses = VacationStatus::getAll();
// Returns: [1 => 'APPROVED', 2 => 'REJECTED', 3 => 'PENDING']

// Validate status in update
if (!VacationStatus::isValid($statusId)) {
    throw new Exception('Invalid status');
}
```

### Where Used:
- `VacationController.php` - Status validation and response formatting
- `VacationGateway.php` - Default status assignment when creating vacations

---

## Benefits of Using Enums

### ✅ Type Safety
```php
// Before: Magic numbers
if ($roleId === 1) { }

// After: Clear intent
if ($roleId === UserRole::ADMIN->value) { }
```

### ✅ Autocomplete Support
IDEs can provide autocomplete for enum values, reducing typos.

### ✅ Centralized Changes
Change enum values in one place instead of throughout the codebase.

### ✅ Self-Documenting Code
```php
// Before: What does 3 mean?
$status_id = 3;

// After: Clear meaning
$status_id = VacationStatus::PENDING->value;
```

### ✅ Validation
Built-in `isValid()` methods for checking valid values.

---

## Migration Guide

### Before (Hardcoded Numbers):
```php
// User roles
if ($roleId === 1) { /* admin */ }
if ($roleId === 2) { /* user */ }

// Vacation status
if ($statusId === 1) { /* approved */ }
if ($statusId === 2) { /* rejected */ }
if ($statusId === 3) { /* pending */ }

// Status names
$statusNames = [1 => 'APPROVED', 2 => 'REJECTED', 3 => 'PENDING'];
```

### After (Using Enums):
```php
// User roles
if ($roleId === UserRole::ADMIN->value) { /* admin */ }
if ($roleId === UserRole::USER->value) { /* user */ }

// Vacation status
if ($statusId === VacationStatus::APPROVED->value) { /* approved */ }
if ($statusId === VacationStatus::REJECTED->value) { /* rejected */ }
if ($statusId === VacationStatus::PENDING->value) { /* pending */ }

// Status names
$statusNames = VacationStatus::getAll();
```

---

## Complete Code Examples

### UserController - Admin Check
```php
// GET /users - Get all users (admin only)
private function index(): void
{
    $this->jwtHandler->requireRole(UserRole::ADMIN->value);
    echo json_encode($this->usersGateway->getAllUsers());
}
```

### VacationController - Status Update
```php
private function updateStatus(int $id, array $data): void
{
    $statusId = (int) $data['status_id'];

    // Validate status using enum
    if (!VacationStatus::isValid($statusId)) {
        http_response_code(422);
        echo json_encode(['error' => 'Invalid status_id']);
        return;
    }

    $this->vacationGateway->updateVacationStatus($id, $statusId);
    
    // Get status name from enum
    $statusNames = VacationStatus::getAll();
    
    http_response_code(200);
    echo json_encode([
        'message' => 'Vacation status updated successfully',
        'id' => $id,
        'status' => $statusNames[$statusId]
    ]);
}
```

### VacationGateway - Default Status
```php
public function createVacation(array $data): int
{
    $stmt = $this->pdo->prepare(
        "INSERT INTO vacations (user_id, date_from, date_to, reason, status_id, created_at, updated_at) 
         VALUES (:user_id, :date_from, :date_to, :reason, :status_id, NOW(), NOW())"
    );

    $stmt->bindValue(':user_id', $data['user_id'], PDO::PARAM_INT);
    $stmt->bindValue(':date_from', $data['date_from'], PDO::PARAM_STR);
    $stmt->bindValue(':date_to', $data['date_to'], PDO::PARAM_STR);
    $stmt->bindValue(':reason', $data['reason'], PDO::PARAM_STR);
    $stmt->bindValue(':status_id', VacationStatus::PENDING->value, PDO::PARAM_INT);
    $stmt->execute();

    return (int)$this->pdo->lastInsertId();
}
```

---

## Autoloader Configuration

The `index.php` autoloader now looks for enums in the `src/enums/` directory:

```php
spl_autoload_register(function ($className) {
    // Try loading from src/ directory
    $filePath = __DIR__ . '/src/' . $className . '.php';
    if (file_exists($filePath)) {
        require_once $filePath;
        return;
    }
    
    // Try loading from src/enums/ directory
    $enumPath = __DIR__ . '/src/enums/' . $className . '.php';
    if (file_exists($enumPath)) {
        require_once $enumPath;
        return;
    }
});
```

---

## API Responses

Enum values are still returned as integers in API responses for database compatibility:

```json
{
  "id": 1,
  "user_id": 2,
  "role_id": 2,
  "status_id": 3,
  "status_name": "PENDING"
}
```

The database continues to use integer values (1, 2, 3) while the code uses enums for clarity and type safety.
