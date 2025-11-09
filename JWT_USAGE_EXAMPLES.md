# JWT Authentication Usage Guide

## Overview
Your API now supports JWT (JSON Web Token) authentication with role-based access control.

## 1. Authentication - Getting a JWT Token

### Endpoint: `POST /users/authenticate`

**Request:**
```bash
curl -X POST http://localhost/users/authenticate \
  -H "Content-Type: application/json" \
  -d '{
    "username": "john_doe",
    "password": "password123"
  }'
```

**Response (Success):**
```json
{
  "message": "Authentication successful",
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyX2lkIjoxLCJ1c2VybmFtZSI6ImpvaG5fZG9lIiwicm9sZV9pZCI6MSwiaWF0IjoxNzMxMTY0MDAwLCJleHAiOjE3MzExNjc2MDB9.xyz123...",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "username": "john_doe",
    "roles_id": 1
  }
}
```

**Response (Failure):**
```json
{
  "error": "Invalid credentials"
}
```

---

## 2. Validating a JWT Token

### Endpoint: `POST /users/validate`

**Request:**
```bash
curl -X POST http://localhost/users/validate \
  -H "Authorization: Bearer YOUR_JWT_TOKEN_HERE"
```

**Response (Valid Token):**
```json
{
  "valid": true,
  "user_id": 1,
  "username": "john_doe",
  "role_id": 1
}
```

**Response (Invalid/Expired Token):**
```json
{
  "error": "Invalid or expired token"
}
```

---

## 3. Protected Endpoints (Require Authentication)

### Get Current User Profile

**Endpoint:** `GET /users/profile`

**Request:**
```bash
curl -X GET http://localhost/users/profile \
  -H "Authorization: Bearer YOUR_JWT_TOKEN_HERE"
```

**Response:**
```json
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "username": "john_doe",
    "roles_id": 1
  }
}
```

---

## 4. Role-Based Access Control

### Admin-Only Endpoint

**Endpoint:** `GET /users/admin`

This endpoint requires `role_id = 1` (admin role).

**Request:**
```bash
curl -X GET http://localhost/users/admin \
  -H "Authorization: Bearer YOUR_ADMIN_JWT_TOKEN_HERE"
```

**Response (Admin User - role_id = 1):**
```json
{
  "message": "Welcome admin!",
  "users": [
    {
      "id": 1,
      "name": "Admin User",
      "email": "admin@example.com",
      "username": "admin",
      "roles_id": 1
    },
    {
      "id": 2,
      "name": "Regular User",
      "email": "user@example.com",
      "username": "user",
      "roles_id": 2
    }
  ]
}
```

**Response (Non-Admin User - role_id = 2):**
```json
{
  "error": "Insufficient permissions"
}
```

---

## 5. Using JWT in Code

### A. Generate JWT Token (in UserController)

```php
// After successful authentication
$token = $this->jwtHandler->generateToken([
    'user_id' => $user['id'],
    'username' => $user['username'],
    'role_id' => $user['roles_id']
]);
```

### B. Validate JWT Token

```php
// Get token from Authorization header
$token = $this->jwtHandler->getTokenFromHeader();

// Validate and decode token
$payload = $this->jwtHandler->validateToken($token);

if ($payload) {
    $userId = $payload['user_id'];
    $roleId = $payload['role_id'];
    // Token is valid, proceed
} else {
    // Token is invalid or expired
}
```

### C. Require Authentication (Auto-handles errors)

```php
// This will automatically return 401 if token is missing/invalid
$payload = $this->jwtHandler->requireAuth();

// If we reach here, token is valid
$userId = $payload['user_id'];
```

### D. Require Specific Role

```php
// Require admin role (role_id = 1)
$payload = $this->jwtHandler->requireRole(1);

// If we reach here, user has admin role
// Otherwise, 403 Forbidden is returned
```

### E. Check Role Manually

```php
// Check if user has specific role
if ($this->jwtHandler->hasRole(1)) {
    // User is admin
} else {
    // User is not admin
}

// Get user's role ID
$roleId = $this->jwtHandler->getUserRoleFromToken();
```

---

## 6. Role System

The system assumes a hierarchical role system where:
- **Lower role_id = Higher privileges**
- `role_id = 1`: Admin (full access)
- `role_id = 2`: Regular User (limited access)

You can modify the `hasRole()` method in `JWTHandler.php` to implement your own role checking logic.

---

## 7. Configuration

### JWT Secret Key

Set your JWT secret key using environment variable:

```env
JWT_SECRET=your-super-secret-key-here
```

Or modify the default in `JWTHandler.php`:

```php
public function __construct(?string $secretKey = null)
{
    $this->secretKey = $secretKey ?? getenv('JWT_SECRET') ?: 'your-secret-key-here';
}
```

### Token Expiration

Default expiration is 1 hour (3600 seconds). You can change it:

```php
$jwtHandler = new JWTHandler();
$jwtHandler->setExpirationTime(7200); // 2 hours
```

---

## 8. Error Responses

| Status Code | Error | Description |
|-------------|-------|-------------|
| 400 | Bad Request | Missing username or password |
| 401 | Unauthorized | Invalid credentials or token |
| 403 | Forbidden | Insufficient role permissions |
| 404 | Not Found | User not found |

---

## 9. Testing with Postman/Insomnia

1. **Authenticate:**
   - POST to `/users/authenticate`
   - Get the `token` from response

2. **Use Protected Endpoints:**
   - Add header: `Authorization: Bearer YOUR_TOKEN`
   - Make requests to protected endpoints

3. **Test Role Access:**
   - Login as admin user → access `/users/admin`
   - Login as regular user → try `/users/admin` → should get 403

---

## 10. Security Best Practices

1. **Always use HTTPS in production**
2. **Use strong JWT secret key** (at least 256 bits)
3. **Store tokens securely** on client side (httpOnly cookies preferred)
4. **Implement token refresh mechanism** for long sessions
5. **Set appropriate expiration times**
6. **Never store sensitive data** in JWT payload (it's base64 encoded, not encrypted)
