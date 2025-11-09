# CORS Configuration for Angular Integration

## What Was Fixed

The API now includes CORS headers to allow requests from your Angular app running on `http://localhost:4200`.

## CORS Headers Added

```php
header("Access-Control-Allow-Origin: http://localhost:4200");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Max-Age: 3600");
```

## Preflight Request Handling

When Angular makes requests with custom headers (like `Authorization`), the browser sends a preflight OPTIONS request first. The API now handles this:

```php
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit();
}
```

## Using in Angular

### 1. Basic HTTP Request

```typescript
import { HttpClient } from '@angular/common/http';

@Injectable()
export class AuthService {
  private apiUrl = 'http://localhost:8080/users';

  constructor(private http: HttpClient) {}

  login(username: string, password: string) {
    return this.http.post(`${this.apiUrl}/authenticate`, {
      username,
      password
    });
  }
}
```

### 2. With Authorization Header

```typescript
import { HttpClient, HttpHeaders } from '@angular/common/http';

@Injectable()
export class UserService {
  private apiUrl = 'http://localhost:8080/users';

  constructor(private http: HttpClient) {}

  getProfile(token: string) {
    const headers = new HttpHeaders({
      'Authorization': `Bearer ${token}`
    });

    return this.http.get(`${this.apiUrl}/profile`, { headers });
  }
}
```

### 3. Using HTTP Interceptor (Recommended)

Create an interceptor to automatically add the token to all requests:

```typescript
import { Injectable } from '@angular/core';
import { HttpInterceptor, HttpRequest, HttpHandler } from '@angular/common/http';

@Injectable()
export class AuthInterceptor implements HttpInterceptor {
  intercept(req: HttpRequest<any>, next: HttpHandler) {
    const token = localStorage.getItem('jwt_token');
    
    if (token) {
      const cloned = req.clone({
        headers: req.headers.set('Authorization', `Bearer ${token}`)
      });
      return next.handle(cloned);
    }
    
    return next.handle(req);
  }
}
```

Register in `app.module.ts`:

```typescript
import { HTTP_INTERCEPTORS } from '@angular/common/http';

@NgModule({
  providers: [
    {
      provide: HTTP_INTERCEPTORS,
      useClass: AuthInterceptor,
      multi: true
    }
  ]
})
export class AppModule { }
```

### 4. Complete Auth Service Example

```typescript
import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, BehaviorSubject } from 'rxjs';
import { tap } from 'rxjs/operators';

interface LoginResponse {
  message: string;
  token: string;
  user: {
    id: number;
    name: string;
    email: string;
    username: string;
    roles_id: number;
  };
}

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  private apiUrl = 'http://localhost:8080/users';
  private tokenKey = 'jwt_token';
  private currentUserSubject = new BehaviorSubject<any>(null);
  public currentUser$ = this.currentUserSubject.asObservable();

  constructor(private http: HttpClient) {
    this.loadCurrentUser();
  }

  login(username: string, password: string): Observable<LoginResponse> {
    return this.http.post<LoginResponse>(`${this.apiUrl}/authenticate`, {
      username,
      password
    }).pipe(
      tap(response => {
        localStorage.setItem(this.tokenKey, response.token);
        this.currentUserSubject.next(response.user);
      })
    );
  }

  logout(): void {
    localStorage.removeItem(this.tokenKey);
    this.currentUserSubject.next(null);
  }

  getToken(): string | null {
    return localStorage.getItem(this.tokenKey);
  }

  isAuthenticated(): boolean {
    return !!this.getToken();
  }

  isAdmin(): boolean {
    const user = this.currentUserSubject.value;
    return user && user.roles_id === 1;
  }

  private loadCurrentUser(): void {
    const token = this.getToken();
    if (token) {
      // Validate token and load user
      this.http.post(`${this.apiUrl}/validate`, {}).subscribe(
        (response: any) => {
          this.currentUserSubject.next({
            id: response.user_id,
            username: response.username,
            roles_id: response.role_id
          });
        },
        () => {
          this.logout();
        }
      );
    }
  }
}
```

## Production Configuration

For production, you should configure CORS more securely:

### Option 1: Specific Domain

```php
// In index.php
$allowedOrigins = [
    'https://yourdomain.com',
    'https://www.yourdomain.com'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    // Development fallback
    header("Access-Control-Allow-Origin: http://localhost:4200");
}
```

### Option 2: Environment Variable

```php
// In index.php
$allowedOrigin = getenv('ALLOWED_ORIGIN') ?: 'http://localhost:4200';
header("Access-Control-Allow-Origin: $allowedOrigin");
```

Then in `.env`:
```env
ALLOWED_ORIGIN=https://yourdomain.com
```

### Option 3: Multiple Origins

```php
$allowedOrigins = [
    'http://localhost:4200',
    'https://yourdomain.com',
    'https://www.yourdomain.com'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
}
```

## Security Notes

⚠️ **Important Security Considerations:**

1. **Don't use wildcard (`*`) for `Access-Control-Allow-Origin` when using credentials**
2. **Always specify exact origins in production**
3. **Use HTTPS in production**
4. **Consider using credentials (cookies) instead of localStorage for tokens**
5. **Implement CSRF protection if using cookies**

## Testing

After applying the fix, test from your Angular app:

```typescript
// In a component
this.authService.login('username', 'password').subscribe(
  response => {
    console.log('Login successful:', response);
    console.log('Token:', response.token);
  },
  error => {
    console.error('Login failed:', error);
  }
);
```

## Common Issues

### Issue 1: Still getting CORS error
- Clear browser cache
- Check if Angular is running on port 4200
- Verify PHP server is running on port 8080

### Issue 2: OPTIONS request returns 404
- Make sure the CORS code is before the routing logic
- Check that the OPTIONS handler exits before routing

### Issue 3: Headers not being sent
- Check that headers are set before any output
- Verify no whitespace before `<?php` tag

## Docker Configuration

If using Docker, make sure the ports are properly exposed in `docker-compose.yml`:

```yaml
services:
  api:
    ports:
      - "8080:80"
```

And access the API at `http://localhost:8080` from Angular.
