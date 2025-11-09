# epignosis_backend

# PHP REST API - Epignosis Backend

A RESTful API built with PHP for managing users and vacation requests with JWT authentication, role-based access control, and CORS support for Angular integration.

**Frontend Project:** [epignosis_frontend](https://github.com/Stavros3/epignosis_frontend)

## Features

- ✅ RESTful API endpoints for user management
- ✅ Vacation request and approval system
- ✅ JWT (JSON Web Token) authentication
- ✅ Role-based access control
- ✅ Password hashing with bcrypt
- ✅ CORS support for Angular integration
- ✅ Docker support
- ✅ MySQL database

## Quick Start

### 1. Authenticate
```bash
curl -X POST http://localhost:8080/users/authenticate \
  -H "Content-Type: application/json" \
  -d '{"username": "admin", "password": "password123"}'
```

### 2. Create Vacation Request
```bash
curl -X POST http://localhost:8080/vacations \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "date_from": "2025-12-01",
    "date_to": "2025-12-10",
    "reason": "Family vacation"
  }'
```

### 3. Approve Vacation (Admin)
```bash
curl -X PUT http://localhost:8080/vacations/1 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -d '{"status_id": 1}'
```

## API Endpoints

### Users
- `POST /users/authenticate` - Login & get JWT token
- `GET /users/profile` - Get current user profile (auth required)
- `POST /users/validate` - Validate JWT token
- `GET /users` - Get all users (admin only)
- `DELETE /users/{id}` - Delete user (admin only)

### Vacations
- `POST /vacations` - Create vacation request
- `GET /vacations` - Get vacations (user: own, admin: all)
- `GET /vacations/{id}` - Get single vacation
- `PUT /vacations/{id}` - Approve/reject vacation (admin only)
- `DELETE /vacations/{id}` - Delete vacation (admin only)

## Environment Variables

Copy `.env.example` to `.env` and configure:

```env
DB_HOST=mysql
DB_DATABASE=my_database
DB_USERNAME=appuser
DB_PASSWORD=apppass
JWT_SECRET=your-super-secret-key-here
```

## Setup

1. Clone the repository
2. Copy `.env.example` to `.env` and configure
3. Run with Docker: `docker-compose up -d`
4. Set up database tables: `mysql -u appuser -p my_database < vacation_schema.sql`
5. Access API at `http://localhost:8080`

## Documentation

## Documentation

- [API Documentation](API_DOCUMENTATION.md) - Complete API reference with examples
- [JWT Authentication Guide](JWT_GUIDE.md) - JWT implementation and usage
- [Vacation System Guide](VACATION_GUIDE.md) - Vacation management system
- [CORS Configuration](CORS_GUIDE.md) - CORS setup for Angular
- [Enums Guide](ENUMS_GUIDE.md) - Type-safe enumerations

## Security Notes

- Always use HTTPS in production
- Change the JWT_SECRET to a strong, unique value
- Store tokens securely on the client side
- Implement token refresh for long sessions

