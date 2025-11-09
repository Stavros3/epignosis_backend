# JWT Authentication Guide

## 1. Login & Get Token

```bash
curl -X POST http://localhost:8080/users/authenticate \
  -H "Content-Type: application/json" \
  -d '{"username": "admin", "password": "password123"}'
```

**Response:**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJh...",
  "user": { "id": 1, "username": "admin", "roles_id": 1 }
}
```

## 2. Use Token in Requests

Add `Authorization: Bearer <token>` header to all protected endpoints:

```bash
curl -X GET http://localhost:8080/users/profile \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## 3. Validate Token

```bash
curl -X POST http://localhost:8080/users/validate \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Code Examples

### Generate Token (Server-side)
```php
$token = $this->jwtHandler->generateToken([
    'user_id' => $user['id'],
    'username' => $user['username'],
    'role_id' => $user['roles_id']
]);
```

### Require Authentication
```php
// Returns 401 if token is invalid/missing
$payload = $this->jwtHandler->requireAuth();
$userId = $payload['user_id'];
```

### Require Admin Role
```php
// Returns 403 if user is not admin
$payload = $this->jwtHandler->requireRole(UserRole::ADMIN->value);
```

### Manual Validation
```php
$token = $this->jwtHandler->getTokenFromHeader();
$payload = $this->jwtHandler->validateToken($token);

if ($payload) {
    // Token valid
    $userId = $payload['user_id'];
    $roleId = $payload['role_id'];
}
```

## Angular Integration

### Service with Interceptor
```typescript
// auth.interceptor.ts
export class AuthInterceptor implements HttpInterceptor {
  intercept(req: HttpRequest<any>, next: HttpHandler) {
    const token = localStorage.getItem('token');
    if (token) {
      req = req.clone({
        setHeaders: { Authorization: `Bearer ${token}` }
      });
    }
    return next.handle(req);
  }
}

// auth.service.ts
login(username: string, password: string) {
  return this.http.post('/users/authenticate', { username, password })
    .pipe(tap(res => localStorage.setItem('token', res.token)));
}
```

## Configuration

Set JWT secret in `.env`:
```env
JWT_SECRET=your-secret-key-min-256-bits
```

**Token expires in 1 hour by default.**

## Error Responses

| Code | Error | Reason |
|------|-------|--------|
| 401 | Unauthorized | No token or invalid/expired token |
| 403 | Forbidden | Valid token but insufficient permissions |
