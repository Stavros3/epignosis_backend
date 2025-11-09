# Vacation API Guide

## Quick Setup

### 1. Create Database Tables
```bash
mysql -u appuser -p my_database < vacation_schema.sql
```

### 2. Vacation Statuses
- `1` = APPROVED
- `2` = REJECTED  
- `3` = PENDING (default)

## API Endpoints

| Method | Endpoint | Access | Description |
|--------|----------|--------|-------------|
| GET | `/vacations` | User/Admin | User: own vacations, Admin: all (PENDING first) |
| GET | `/vacations/{id}` | User/Admin | Get single vacation |
| POST | `/vacations` | User/Admin | Create vacation request |
| PUT | `/vacations/{id}` | Admin only | Approve/reject vacation |
| DELETE | `/vacations/{id}` | Admin only | Delete vacation |
| GET | `/vacations/statuses` | User/Admin | Get all statuses |

## Examples

### Create Vacation Request
```bash
curl -X POST http://localhost:8080/vacations \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "date_from": "2025-12-01",
    "date_to": "2025-12-10",
    "reason": "Family vacation to beach resort"
  }'
```

**Response:**
```json
{
  "message": "Vacation request created successfully",
  "id": 5,
  "status": "PENDING"
}
```

### Get Vacations
```bash
# Regular user sees only their own
# Admin sees all (PENDING first)
curl -X GET http://localhost:8080/vacations \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Approve Vacation (Admin Only)
```bash
curl -X PUT http://localhost:8080/vacations/1 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -d '{"status_id": 1}'
```

### Reject Vacation (Admin Only)
```bash
curl -X PUT http://localhost:8080/vacations/1 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -d '{"status_id": 2}'
```

## Validation Rules

- `date_from` required (YYYY-MM-DD format)
- `date_to` required (YYYY-MM-DD format, must be >= date_from)
- `reason` required (min 10 characters)

## Permissions

### Regular Users (role_id = 2)
- ✅ Create vacation requests
- ✅ View own vacations
- ❌ Cannot approve/reject
- ❌ Cannot delete vacations
- ❌ Cannot see other users' vacations

### Admins (role_id = 1)
- ✅ View all vacations (PENDING shown first)
- ✅ Approve/reject any vacation
- ✅ Delete any vacation

## Angular Integration

```typescript
// vacation.service.ts
export class VacationService {
  private apiUrl = 'http://localhost:8080/vacations';

  createVacation(data: any) {
    return this.http.post(this.apiUrl, data);
  }

  getVacations() {
    return this.http.get(this.apiUrl);
  }

  approveVacation(id: number) {
    return this.http.put(`${this.apiUrl}/${id}`, { status_id: 1 });
  }

  rejectVacation(id: number) {
    return this.http.put(`${this.apiUrl}/${id}`, { status_id: 2 });
  }
}
```

## Code Examples

### Controller - Create Vacation
```php
private function store(): void
{
    $payload = $this->jwtHandler->requireAuth();
    $data = (array) json_decode(file_get_contents('php://input'), true);
    $data['user_id'] = $payload['user_id'];
    
    $errors = $this->getValidationErrors($data);
    if (!empty($errors)) {
        http_response_code(422);
        echo json_encode(['errors' => $errors]);
        return;
    }
    
    $vacationId = $this->vacationGateway->createVacation($data);
    http_response_code(201);
    echo json_encode(['id' => $vacationId, 'status' => 'PENDING']);
}
```

### Controller - Approve/Reject (Admin Only)
```php
private function update(int $id): void
{
    $this->jwtHandler->requireRole(UserRole::ADMIN->value);
    $data = (array) json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['status_id'])) {
        $this->vacationGateway->updateVacationStatus($id, $data['status_id']);
        echo json_encode(['message' => 'Status updated']);
    }
}
```

## Error Responses

| Code | Error | Reason |
|------|-------|--------|
| 400 | Bad Request | Invalid data |
| 401 | Unauthorized | No/invalid token |
| 403 | Forbidden | Insufficient permissions |
| 404 | Not Found | Vacation not found |
| 422 | Validation Error | Invalid input data |
