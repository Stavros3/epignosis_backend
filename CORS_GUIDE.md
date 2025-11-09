# CORS Configuration

## What's Configured

The API includes CORS headers in `index.php` to allow requests from Angular app on `http://localhost:4200`.

```php
header("Access-Control-Allow-Origin: http://localhost:4200");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Max-Age: 3600");

// Handle preflight OPTIONS request
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit();
}
```

## Angular Setup

### HTTP Interceptor (Recommended)
```typescript
// auth.interceptor.ts
import { HttpInterceptor, HttpRequest, HttpHandler } from '@angular/common/http';

@Injectable()
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
```

### Register Interceptor
```typescript
// app.config.ts or app.module.ts
providers: [
  {
    provide: HTTP_INTERCEPTORS,
    useClass: AuthInterceptor,
    multi: true
  }
]
```

## Production Setup

Update `index.php` for production domain:

```php
$allowedOrigins = [
    'http://localhost:4200',  // Development
    'https://yourdomain.com',  // Production
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
}
```

Or use environment variable:
```php
$allowedOrigin = getenv('ALLOWED_ORIGIN') ?: 'http://localhost:4200';
header("Access-Control-Allow-Origin: $allowedOrigin");
```

Then in `.env`:
```env
ALLOWED_ORIGIN=https://yourdomain.com
```

## Common Issues

### Still getting CORS error?
1. Clear browser cache
2. Verify Angular runs on port 4200
3. Check PHP server is on port 8080
4. Ensure no whitespace before `<?php` in index.php

### 404 on OPTIONS request?
CORS headers must be set before any routing logic in `index.php`.
