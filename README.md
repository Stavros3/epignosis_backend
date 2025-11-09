# epignosis_backend

A RESTful API with JWT authentication and role-based access control.

## Features

- ✅ RESTful API endpoints for user management
- ✅ JWT (JSON Web Token) authentication
- ✅ Role-based access control
- ✅ Password hashing with bcrypt
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
4. Access API at `http://localhost`

## Security Notes

- Always use HTTPS in production
- Change the JWT_SECRET to a strong, unique value
- Store tokens securely on the client side
- Implement token refresh for long sessions

