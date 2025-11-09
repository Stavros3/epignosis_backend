# Vacation Management System - Quick Reference

## Files Created

✅ **VacationController.php** - Handles all vacation-related HTTP requests
✅ **VacationGateway.php** - Database operations for vacations
✅ **VACATION_API_DOCUMENTATION.md** - Complete API documentation
✅ **vacation_schema.sql** - Database schema setup
✅ **index.php** - Updated to route /vacations requests

## Quick Setup

### 1. Create Database Tables
```bash
# Connect to MySQL and run the schema
mysql -u appuser -p my_database < vacation_schema.sql
```

Or manually run:
```sql
CREATE TABLE vacations_status (
    id INT PRIMARY KEY AUTO_INCREMENT,
    status VARCHAR(50) NOT NULL
);

INSERT INTO vacations_status (id, status) VALUES
(1, 'APPROVED'), (2, 'REJECTED'), (3, 'PENDING');

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
```

### 2. Test the API

#### Create Vacation (Any User)
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

#### Get Vacations
```bash
# Regular user - gets only their own
# Admin - gets all vacations (pending first)
curl -X GET http://localhost:8080/vacations \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### Approve Vacation (Admin Only)
```bash
curl -X PUT http://localhost:8080/vacations/1 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -d '{"status_id": 1}'
```

#### Reject Vacation (Admin Only)
```bash
curl -X PUT http://localhost:8080/vacations/1 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -d '{"status_id": 2}'
```

## API Endpoints Summary

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | /vacations | User/Admin | Get vacations (user: own, admin: all) |
| GET | /vacations/my | User/Admin | Get current user's vacations |
| GET | /vacations/{id} | User/Admin | Get single vacation |
| POST | /vacations | User/Admin | Create vacation request |
| PUT | /vacations/{id} | User/Admin | Update vacation (user: details, admin: status) |
| DELETE | /vacations/{id} | User/Admin | Delete vacation |
| GET | /vacations/statuses | User/Admin | Get all statuses |

## Status Codes

- **1** = APPROVED
- **2** = REJECTED
- **3** = PENDING (default)

## Key Features Implemented

### For Regular Users (role_id = 2):
✅ Create vacation requests (auto-set to PENDING)
✅ View own vacations
✅ Update own PENDING vacations
✅ Delete own PENDING vacations
❌ Cannot approve/reject vacations
❌ Cannot see other users' vacations

### For Admins (role_id = 1):
✅ View ALL vacations (sorted by PENDING first)
✅ Approve/reject any vacation
✅ Update vacation status
✅ Delete any vacation
✅ Full access to all vacation operations

## Validation Rules

### Creating/Updating Vacation:
- `date_from` required, valid YYYY-MM-DD format
- `date_to` required, valid YYYY-MM-DD format
- `date_to` must be >= `date_from`
- `reason` required, minimum 10 characters

### Updating Status (Admin):
- `status_id` must be 1, 2, or 3

## Business Rules

1. **New vacations** are automatically set to PENDING (status_id = 3)
2. **Regular users** can only update/delete PENDING vacations
3. **Admins** can update status of any vacation
4. **Admin list** shows PENDING vacations first (sorted by status_id DESC)
5. **User list** shows their own vacations sorted by created_at DESC

## Integration with Angular

```typescript
// Create vacation
this.http.post('http://localhost:8080/vacations', {
  date_from: '2025-12-01',
  date_to: '2025-12-10',
  reason: 'Family vacation'
});

// Get vacations (respects role automatically)
this.http.get('http://localhost:8080/vacations');

// Approve (admin only)
this.http.put('http://localhost:8080/vacations/1', {
  status_id: 1
});
```

## Code Structure

### VacationController.php Methods:
- `index()` - GET /vacations (role-based)
- `show($id)` - GET /vacations/{id}
- `store()` - POST /vacations
- `update($id)` - PUT /vacations/{id}
- `destroy($id)` - DELETE /vacations/{id}
- `statuses()` - GET /vacations/statuses
- `my()` - GET /vacations/my

### VacationGateway.php Methods:
- `getAllVacations()` - Get all with user info
- `getVacationsByUserId($userId)` - Get user's vacations
- `getVacationById($id)` - Get single vacation
- `createVacation($data)` - Create new vacation
- `updateVacationStatus($id, $statusId)` - Update status
- `updateVacation($id, $data)` - Update details
- `deleteVacation($id)` - Delete vacation
- `vacationExists($id)` - Check existence
- `isVacationOwner($vacationId, $userId)` - Check ownership
- `getVacationStatus($vacationId)` - Get status
- `getAllStatuses()` - Get all statuses

## Common Use Cases

### User Creates Vacation Request
1. User logs in → gets JWT token
2. User POST to /vacations with dates and reason
3. System creates vacation with status PENDING
4. User can view in their vacation list

### Admin Approves Vacation
1. Admin logs in → gets JWT token
2. Admin GET /vacations → sees all pending vacations first
3. Admin PUT /vacations/{id} with status_id: 1
4. Vacation is approved

### User Updates Pending Vacation
1. User GET /vacations → sees own vacations
2. User PUT /vacations/{id} with new dates/reason
3. Only works if status is still PENDING

## Error Handling

All endpoints return appropriate HTTP status codes:
- **200** - Success
- **201** - Created
- **400** - Bad request (validation error)
- **401** - Unauthorized (no token)
- **403** - Forbidden (insufficient permissions)
- **404** - Not found
- **422** - Validation errors
- **500** - Server error

## Testing Checklist

✅ User can create vacation
✅ User sees only own vacations
✅ User can update own PENDING vacation
✅ User cannot update APPROVED/REJECTED vacation
✅ User cannot see other users' vacations
✅ Admin sees all vacations
✅ Admin sees PENDING vacations first
✅ Admin can approve vacations
✅ Admin can reject vacations
✅ Date validation works
✅ Reason validation works (min 10 chars)
✅ CORS headers work with Angular
