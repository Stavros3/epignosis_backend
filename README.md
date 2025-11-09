# epignosis_backend

A RESTful API with JWT authentication, role-based access control, and vacation management system.

## Features

- ✅ RESTful API endpoints for user management
- ✅ Vacation request and approval system
- ✅ JWT (JSON Web Token) authentication
- ✅ Role-based access control
- ✅ Password hashing with bcrypt
- ✅ CORS support for Angular integration
- ✅ Docker support
- ✅ MySQL database

## JWT Authentication

This API uses JWT tokens for authentication and authorization. See [JWT_USAGE_EXAMPLES.md](JWT_USAGE_EXAMPLES.md) for detailed usage examples.

### Quick Start

1. **Authenticate and get a token:**
```bash
curl -X POST http://localhost/users/authenticate \
  -H "Content-Type: application/json" \
  -d '{"username": "your_username", "password": "your_password"}'
```

2. **Use the token in subsequent requests:**
```bash
curl -X GET http://localhost/users/profile \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Available Endpoints

#### Public Endpoints (No authentication required)
- `POST /users` - Create a new user
- `POST /users/authenticate` - Login and get JWT token

#### Protected Endpoints (Require authentication)
- `GET /users/profile` - Get current user's profile
- `POST /users/validate` - Validate JWT token
- `PUT /users/{id}` - Update user (own profile or admin)

#### Admin-Only Endpoints (Require role_id = 1)
- `GET /users` - Get all users
- `GET /users/admin` - Admin dashboard
- `DELETE /users/{id}` - Delete user

## Vacation Management

This API includes a complete vacation request and approval system. See [VACATION_API_DOCUMENTATION.md](VACATION_API_DOCUMENTATION.md) for detailed documentation.

### Vacation Endpoints

#### For All Users
- `POST /vacations` - Create vacation request
- `GET /vacations` - Get vacations (users see own, admins see all)
- `GET /vacations/my` - Get current user's vacations
- `GET /vacations/{id}` - Get single vacation
- `PUT /vacations/{id}` - Update vacation details (own pending only)
- `DELETE /vacations/{id}` - Delete vacation (own pending only)

#### For Admins Only
- `PUT /vacations/{id}` with `status_id` - Approve/reject vacations
- Full access to all vacation operations

### Vacation Statuses
- **1** = APPROVED
- **2** = REJECTED
- **3** = PENDING (default)

### Quick Example
```bash
# Create vacation request
curl -X POST http://localhost:8080/vacations \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "date_from": "2025-12-01",
    "date_to": "2025-12-10",
    "reason": "Family vacation to beach resort"
  }'

# Approve vacation (admin only)
curl -X PUT http://localhost:8080/vacations/1 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -d '{"status_id": 1}'
```

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

## API Documentation

- **[JWT_USAGE_EXAMPLES.md](JWT_USAGE_EXAMPLES.md)** - JWT authentication guide
- **[VACATION_API_DOCUMENTATION.md](VACATION_API_DOCUMENTATION.md)** - Complete vacation API docs
- **[VACATION_QUICK_REFERENCE.md](VACATION_QUICK_REFERENCE.md)** - Quick reference guide
- **[CORS_CONFIGURATION.md](CORS_CONFIGURATION.md)** - CORS setup for Angular

## Security Notes

- Always use HTTPS in production
- Change the JWT_SECRET to a strong, unique value
- Store tokens securely on the client side
- Implement token refresh for long sessions

