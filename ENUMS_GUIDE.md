# Enumerations Reference

## UserRole Enum
**Location:** `src/enums/UserRole.php`

```php
UserRole::ADMIN = 1  // Full access
UserRole::USER = 2   // Limited access
```

### Usage
```php
// Check if admin
if ($roleId === UserRole::ADMIN->value) { }

// Require admin role
$this->jwtHandler->requireRole(UserRole::ADMIN->value);

// Set default role
$roles_id = $data['roles_id'] ?? UserRole::USER->value;
```

---

## VacationStatus Enum
**Location:** `src/enums/VacationStatus.php`

```php
VacationStatus::APPROVED = 1
VacationStatus::REJECTED = 2
VacationStatus::PENDING = 3  // Default
```

### Usage
```php
// Validate status
if (VacationStatus::isValid($statusId)) { }

// Get status name
$name = VacationStatus::APPROVED->getName(); // "APPROVED"

// Get all statuses
$all = VacationStatus::getAll();
// Returns: [1 => 'APPROVED', 2 => 'REJECTED', 3 => 'PENDING']

// Set default status
$status = VacationStatus::PENDING->value;
```

---

## Benefits

✅ **No magic numbers** - Clear intent instead of `if ($role === 1)`  
✅ **Type safety** - IDE autocomplete  
✅ **Single source of truth** - Change values in one place  
✅ **Built-in validation** - `isValid()` methods  

---

## Before vs After

### Before (Hardcoded)
```php
if ($roleId === 1) { /* admin */ }
if ($statusId === 3) { /* pending */ }
$statusNames = [1 => 'APPROVED', 2 => 'REJECTED', 3 => 'PENDING'];
```

### After (With Enums)
```php
if ($roleId === UserRole::ADMIN->value) { /* admin */ }
if ($statusId === VacationStatus::PENDING->value) { /* pending */ }
$statusNames = VacationStatus::getAll();
```
