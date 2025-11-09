# Vacation API Documentation

## Overview
The Vacation API allows authenticated users to create vacation requests and admins to approve/reject them.

## Database Schema

### `vacations` table:
- `id` (int) - Primary key
- `user_id` (int) - Foreign key to users table
- `date_from` (date) - Start date of vacation
- `date_to` (date) - End date of vacation
- `reason` (varchar) - Reason for vacation request
- `status_id` (int) - Foreign key to vacations_status (default: 3)
- `created_at` (timestamp)
- `updated_at` (timestamp)

### `vacations_status` table:
- `id` (int) - Primary key
- `status` (varchar)
  - `1` = APPROVED
  - `2` = REJECTED
  - `3` = PENDING (default)

## Authentication
All endpoints require JWT authentication via `Authorization: Bearer <token>` header.

---

## Endpoints

### 1. Get Vacations
**Endpoint:** `GET /vacations`

**Authorization:**
- **Admin (role_id = 1):** Returns ALL vacations, ordered by status (PENDING first)
- **Regular User:** Returns only their own vacations

**Request:**
```bash
curl -X GET http://localhost:8080/vacations \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

**Response (Admin):**
```json
[
  {
    "id": 1,
    "user_id": 2,
    "user_name": "John Doe",
    "username": "john_doe",
    "date_from": "2025-12-01",
    "date_to": "2025-12-10",
    "reason": "Family vacation to beach resort",
    "status_id": 3,
    "status_name": "PENDING",
    "created_at": "2025-11-09 10:30:00",
    "updated_at": "2025-11-09 10:30:00"
  },
  {
    "id": 2,
    "user_id": 3,
    "user_name": "Jane Smith",
    "username": "jane_smith",
    "date_from": "2025-11-15",
    "date_to": "2025-11-20",
    "reason": "Personal reasons - attending wedding",
    "status_id": 1,
    "status_name": "APPROVED",
    "created_at": "2025-11-08 14:20:00",
    "updated_at": "2025-11-09 09:15:00"
  }
]
```

**Response (Regular User):**
```json
[
  {
    "id": 1,
    "user_id": 2,
    "date_from": "2025-12-01",
    "date_to": "2025-12-10",
    "reason": "Family vacation to beach resort",
    "status_id": 3,
    "status_name": "PENDING",
    "created_at": "2025-11-09 10:30:00",
    "updated_at": "2025-11-09 10:30:00"
  }
]
```

---

### 2. Get Current User's Vacations
**Endpoint:** `GET /vacations/my`

**Authorization:** Any authenticated user

**Request:**
```bash
curl -X GET http://localhost:8080/vacations/my \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

**Response:** Same as regular user GET /vacations

---

### 3. Get Single Vacation
**Endpoint:** `GET /vacations/{id}`

**Authorization:**
- **Admin:** Can view any vacation
- **Regular User:** Can only view their own vacations

**Request:**
```bash
curl -X GET http://localhost:8080/vacations/1 \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

**Response:**
```json
{
  "id": 1,
  "user_id": 2,
  "user_name": "John Doe",
  "username": "john_doe",
  "date_from": "2025-12-01",
  "date_to": "2025-12-10",
  "reason": "Family vacation to beach resort",
  "status_id": 3,
  "status_name": "PENDING",
  "created_at": "2025-11-09 10:30:00",
  "updated_at": "2025-11-09 10:30:00"
}
```

**Error (403 Forbidden):**
```json
{
  "error": "Access denied"
}
```

---

### 4. Create Vacation Request
**Endpoint:** `POST /vacations`

**Authorization:** Any authenticated user

**Required Fields:**
- `date_from` (string, YYYY-MM-DD format)
- `date_to` (string, YYYY-MM-DD format)
- `reason` (string, min 10 characters)

**Request:**
```bash
curl -X POST http://localhost:8080/vacations \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -d '{
    "date_from": "2025-12-01",
    "date_to": "2025-12-10",
    "reason": "Family vacation to beach resort"
  }'
```

**Response (201 Created):**
```json
{
  "message": "Vacation request created successfully",
  "id": 5,
  "status": "PENDING"
}
```

**Validation Errors (422):**
```json
{
  "errors": [
    "Start date (date_from) is required",
    "End date must be after or equal to start date",
    "Reason must be at least 10 characters long"
  ]
}
```

---

### 5. Update Vacation Status (Admin Only)
**Endpoint:** `PUT /vacations/{id}` or `PATCH /vacations/{id}`

**Authorization:** Admin only (role_id = 1)

**Required Fields:**
- `status_id` (int: 1=APPROVED, 2=REJECTED, 3=PENDING)

**Request:**
```bash
curl -X PUT http://localhost:8080/vacations/1 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer ADMIN_JWT_TOKEN" \
  -d '{
    "status_id": 1
  }'
```

**Response (200 OK):**
```json
{
  "message": "Vacation status updated successfully",
  "id": 1,
  "status": "APPROVED"
}
```

**Examples:**
```bash
# Approve vacation
curl -X PUT http://localhost:8080/vacations/1 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -d '{"status_id": 1}'

# Reject vacation
curl -X PUT http://localhost:8080/vacations/1 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -d '{"status_id": 2}'

# Set back to pending
curl -X PUT http://localhost:8080/vacations/1 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -d '{"status_id": 3}'
```

---

### 6. Update Vacation Details (User's Own Pending Vacations)
**Endpoint:** `PUT /vacations/{id}` or `PATCH /vacations/{id}`

**Authorization:** Vacation owner (and vacation must be PENDING)

**Required Fields:** At least one of:
- `date_from` (string, YYYY-MM-DD format)
- `date_to` (string, YYYY-MM-DD format)
- `reason` (string, min 10 characters)

**Request:**
```bash
curl -X PUT http://localhost:8080/vacations/1 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer USER_JWT_TOKEN" \
  -d '{
    "date_from": "2025-12-05",
    "date_to": "2025-12-15",
    "reason": "Extended family vacation to beach resort and mountain retreat"
  }'
```

**Response (200 OK):**
```json
{
  "message": "Vacation updated successfully",
  "id": 1
}
```

**Error (400 Bad Request) - If vacation is not pending:**
```json
{
  "error": "You can only update pending vacation requests"
}
```

---

### 7. Delete Vacation
**Endpoint:** `DELETE /vacations/{id}`

**Authorization:**
- **Admin:** Can delete any vacation
- **Regular User:** Can only delete their own PENDING vacations

**Request:**
```bash
curl -X DELETE http://localhost:8080/vacations/1 \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

**Response (200 OK):**
```json
{
  "message": "Vacation deleted successfully"
}
```

**Error (400 Bad Request) - Regular user trying to delete approved/rejected:**
```json
{
  "error": "You can only delete pending vacation requests"
}
```

---

### 8. Get Vacation Statuses
**Endpoint:** `GET /vacations/statuses`

**Authorization:** Any authenticated user

**Request:**
```bash
curl -X GET http://localhost:8080/vacations/statuses \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

**Response:**
```json
[
  {
    "id": 1,
    "status": "APPROVED"
  },
  {
    "id": 2,
    "status": "REJECTED"
  },
  {
    "id": 3,
    "status": "PENDING"
  }
]
```

---

## Business Rules

### User Permissions (Regular User - role_id = 2):
✅ Create vacation requests
✅ View own vacations
✅ Update own PENDING vacations
✅ Delete own PENDING vacations
❌ Cannot update vacation status
❌ Cannot view other users' vacations
❌ Cannot update/delete approved or rejected vacations

### Admin Permissions (role_id = 1):
✅ View ALL vacations (sorted by PENDING first)
✅ Update any vacation status (approve/reject)
✅ Delete any vacation
✅ View any user's vacation
✅ All regular user permissions

---

## Validation Rules

### Date Validation:
- `date_from` and `date_to` are required
- Must be valid dates in `YYYY-MM-DD` format
- `date_to` must be after or equal to `date_from`

### Reason Validation:
- Required field
- Minimum 10 characters

### Status Validation:
- Must be 1 (APPROVED), 2 (REJECTED), or 3 (PENDING)

---

## Example Workflow

### User Creates Vacation Request:
```bash
# 1. User authenticates
TOKEN=$(curl -s -X POST http://localhost:8080/users/authenticate \
  -H "Content-Type: application/json" \
  -d '{"username":"john_doe","password":"password123"}' | jq -r '.token')

# 2. User creates vacation request
curl -X POST http://localhost:8080/vacations \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "date_from": "2025-12-01",
    "date_to": "2025-12-10",
    "reason": "Family vacation to beach resort"
  }'

# 3. User checks their vacations
curl -X GET http://localhost:8080/vacations \
  -H "Authorization: Bearer $TOKEN"
```

### Admin Approves Vacation:
```bash
# 1. Admin authenticates
ADMIN_TOKEN=$(curl -s -X POST http://localhost:8080/users/authenticate \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin123"}' | jq -r '.token')

# 2. Admin views all pending vacations
curl -X GET http://localhost:8080/vacations \
  -H "Authorization: Bearer $ADMIN_TOKEN"

# 3. Admin approves vacation
curl -X PUT http://localhost:8080/vacations/1 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -d '{"status_id": 1}'
```

---

## Error Responses

| Status Code | Error | Description |
|-------------|-------|-------------|
| 400 | Bad Request | Invalid data or business rule violation |
| 401 | Unauthorized | Missing or invalid JWT token |
| 403 | Forbidden | Insufficient permissions |
| 404 | Not Found | Vacation not found |
| 422 | Unprocessable Entity | Validation errors |
| 500 | Internal Server Error | Server error |

---

## Angular Integration Example

```typescript
import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

interface Vacation {
  id?: number;
  user_id?: number;
  date_from: string;
  date_to: string;
  reason: string;
  status_id?: number;
  status_name?: string;
  created_at?: string;
  updated_at?: string;
}

@Injectable({
  providedIn: 'root'
})
export class VacationService {
  private apiUrl = 'http://localhost:8080/vacations';

  constructor(private http: HttpClient) {}

  // Get all vacations (admin sees all, user sees own)
  getVacations(): Observable<Vacation[]> {
    return this.http.get<Vacation[]>(this.apiUrl);
  }

  // Get single vacation
  getVacation(id: number): Observable<Vacation> {
    return this.http.get<Vacation>(`${this.apiUrl}/${id}`);
  }

  // Create vacation request
  createVacation(vacation: Vacation): Observable<any> {
    return this.http.post(this.apiUrl, vacation);
  }

  // Update vacation (user updates details, admin updates status)
  updateVacation(id: number, data: Partial<Vacation>): Observable<any> {
    return this.http.put(`${this.apiUrl}/${id}`, data);
  }

  // Approve vacation (admin only)
  approveVacation(id: number): Observable<any> {
    return this.http.put(`${this.apiUrl}/${id}`, { status_id: 1 });
  }

  // Reject vacation (admin only)
  rejectVacation(id: number): Observable<any> {
    return this.http.put(`${this.apiUrl}/${id}`, { status_id: 2 });
  }

  // Delete vacation
  deleteVacation(id: number): Observable<any> {
    return this.http.delete(`${this.apiUrl}/${id}`);
  }

  // Get vacation statuses
  getStatuses(): Observable<any[]> {
    return this.http.get<any[]>(`${this.apiUrl}/statuses`);
  }
}
```

---

## SQL Schema Setup

```sql
-- Create vacations_status table
CREATE TABLE vacations_status (
    id INT PRIMARY KEY AUTO_INCREMENT,
    status VARCHAR(50) NOT NULL
);

-- Insert default statuses
INSERT INTO vacations_status (id, status) VALUES
(1, 'APPROVED'),
(2, 'REJECTED'),
(3, 'PENDING');

-- Create vacations table
CREATE TABLE vacations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    date_from DATE NOT NULL,
    date_to DATE NOT NULL,
    reason VARCHAR(255) NOT NULL,
    status_id INT DEFAULT 3,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (status_id) REFERENCES vacations_status(id)
);

-- Add indexes for better performance
CREATE INDEX idx_user_id ON vacations(user_id);
CREATE INDEX idx_status_id ON vacations(status_id);
CREATE INDEX idx_created_at ON vacations(created_at);
```
