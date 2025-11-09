# API Documentation

Base URL: `http://localhost:8080`

## Authentication

All protected endpoints require a JWT token in the Authorization header:
```
Authorization: Bearer YOUR_JWT_TOKEN
```

---

## Users Endpoints

### POST /users/authenticate
Login and receive a JWT token.

**Request:**
```json
{
  "username": "admin",
  "password": "password123"
}
```

**Response (200):**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "user": {
    "id": 1,
    "name": "Admin User",
    "username": "admin",
    "role_id": 1
  }
}
```

### GET /users/profile
Get current authenticated user's profile. **Requires Authentication**

**Response (200):**
```json
{
  "id": 1,
  "name": "John Doe",
  "username": "johndoe",
  "role_id": 2
}
```

### POST /users/validate
Validate a JWT token. **Requires Authentication**

**Response (200):**
```json
{
  "valid": true,
  "user_id": 1,
  "role_id": 2
}
```

### GET /users
Get all users. **Admin Only**

**Response (200):**
```json
[
  {
    "id": 1,
    "name": "Admin User",
    "username": "admin",
    "role_id": 1
  },
  {
    "id": 2,
    "name": "John Doe",
    "username": "johndoe",
    "role_id": 2
  }
]
```

### DELETE /users/{id}
Delete a user. **Admin Only**

**Response (204):** No content

---

## Vacations Endpoints

### POST /vacations
Create a new vacation request. **Requires Authentication**

**Request:**
```json
{
  "date_from": "2025-12-01",
  "date_to": "2025-12-10",
  "reason": "Family vacation to beach resort"
}
```

**Response (201):**
```json
{
  "id": 5,
  "user_id": 2,
  "date_from": "2025-12-01",
  "date_to": "2025-12-10",
  "reason": "Family vacation to beach resort",
  "status_id": 3,
  "status_name": "PENDING"
}
```

### GET /vacations
Get vacations (users see their own, admins see all). **Requires Authentication**

**Response (200):**
```json
[
  {
    "id": 5,
    "user_id": 2,
    "user_name": "John Doe",
    "date_from": "2025-12-01",
    "date_to": "2025-12-10",
    "reason": "Family vacation",
    "status_id": 3,
    "status_name": "PENDING"
  },
  {
    "id": 3,
    "user_id": 2,
    "user_name": "John Doe",
    "date_from": "2025-08-15",
    "date_to": "2025-08-22",
    "reason": "Summer break",
    "status_id": 1,
    "status_name": "APPROVED"
  }
]
```

### GET /vacations/my
Get only current user's vacations. **Requires Authentication**

**Response (200):** Same format as GET /vacations

### GET /vacations/{id}
Get a single vacation by ID. **Requires Authentication**

**Response (200):**
```json
{
  "id": 5,
  "user_id": 2,
  "user_name": "John Doe",
  "date_from": "2025-12-01",
  "date_to": "2025-12-10",
  "reason": "Family vacation",
  "status_id": 3,
  "status_name": "PENDING"
}
```

### PUT /vacations/{id}
Approve or reject a vacation. **Admin Only**

**Request:**
```json
{
  "status_id": 1
}
```

**Response (200):**
```json
{
  "id": 5,
  "user_id": 2,
  "user_name": "John Doe",
  "date_from": "2025-12-01",
  "date_to": "2025-12-10",
  "reason": "Family vacation",
  "status_id": 1,
  "status_name": "APPROVED"
}
```

### DELETE /vacations/{id}
Delete a vacation. **Admin Only**

**Response (204):** No content

---

## Status Codes

- **200** - Success
- **201** - Created
- **204** - No Content (successful deletion)
- **400** - Bad Request (validation error)
- **401** - Unauthorized (missing or invalid token)
- **403** - Forbidden (insufficient permissions)
- **404** - Not Found
- **422** - Unprocessable Entity (validation failed)

---

## Enumerations

### User Roles
- `1` - ADMIN (full access)
- `2` - USER (limited access)

### Vacation Status
- `1` - APPROVED
- `2` - REJECTED
- `3` - PENDING (default)

---

## Example Workflow

```bash
# 1. Login
curl -X POST http://localhost:8080/users/authenticate \
  -H "Content-Type: application/json" \
  -d '{"username": "admin", "password": "password123"}'

# Save the token from response
TOKEN="eyJ0eXAiOiJKV1QiLCJhbGc..."

# 2. Get your profile
curl -X GET http://localhost:8080/users/profile \
  -H "Authorization: Bearer $TOKEN"

# 3. Create vacation request
curl -X POST http://localhost:8080/vacations \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "date_from": "2025-12-01",
    "date_to": "2025-12-10",
    "reason": "Holiday vacation"
  }'

# 4. Get all vacations (as admin)
curl -X GET http://localhost:8080/vacations \
  -H "Authorization: Bearer $TOKEN"

# 5. Approve vacation (admin only)
curl -X PUT http://localhost:8080/vacations/1 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"status_id": 1}'
```
