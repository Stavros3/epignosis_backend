<?php

class JWTHandler
{
    private string $secretKey;
    private string $algorithm = 'HS256';
    private int $expirationTime = 3600; // 1 hour

    public function __construct(?string $secretKey = null)
    {
        // Use environment variable or provided key
        $this->secretKey = $secretKey ?? getenv('JWT_SECRET') ?: 'your-secret-key-change-this-in-production';
    }

    /**
     * Generate JWT token
     */
    public function generateToken(array $payload): string
    {
        $header = [
            'typ' => 'JWT',
            'alg' => $this->algorithm
        ];

        // Add issued at and expiration time
        $payload['iat'] = time();
        $payload['exp'] = time() + $this->expirationTime;

        // Encode header and payload
        $headerEncoded = $this->base64UrlEncode(json_encode($header));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));

        // Create signature
        $signature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", $this->secretKey, true);
        $signatureEncoded = $this->base64UrlEncode($signature);

        // Return complete JWT
        return "$headerEncoded.$payloadEncoded.$signatureEncoded";
    }

    /**
     * Validate and decode JWT token
     */
    public function validateToken(string $token): ?array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return null;
        }

        [$headerEncoded, $payloadEncoded, $signatureProvided] = $parts;

        // Verify signature
        $signature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", $this->secretKey, true);
        $signatureEncoded = $this->base64UrlEncode($signature);

        if (!hash_equals($signatureEncoded, $signatureProvided)) {
            return null; // Invalid signature
        }

        // Decode payload
        $payload = json_decode($this->base64UrlDecode($payloadEncoded), true);

        // Check expiration
        if (!isset($payload['exp']) || $payload['exp'] < time()) {
            return null; // Token expired
        }

        return $payload;
    }

    /**
     * Extract token from Authorization header
     */
    public function getTokenFromHeader(): ?string
    {
        $headers = getallheaders();
        
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
            
            // Check for "Bearer " prefix
            if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Get user ID from token
     */
    public function getUserIdFromToken(?string $token = null): ?int
    {
        if ($token === null) {
            $token = $this->getTokenFromHeader();
        }

        if (!$token) {
            return null;
        }

        $payload = $this->validateToken($token);
        
        return $payload['user_id'] ?? null;
    }

    /**
     * Get user role from token
     */
    public function getUserRoleFromToken(?string $token = null): ?int
    {
        if ($token === null) {
            $token = $this->getTokenFromHeader();
        }

        if (!$token) {
            return null;
        }

        $payload = $this->validateToken($token);
        
        return $payload['role_id'] ?? null;
    }

    /**
     * Check if user has required role
     */
    public function hasRole(int $requiredRoleId, ?string $token = null): bool
    {
        $userRoleId = $this->getUserRoleFromToken($token);
        
        if ($userRoleId === null) {
            return false;
        }

        // Assuming lower role_id means higher privileges (e.g., 1 = admin, 2 = user)
        // Admins (role_id = 1) can access everything
        return $userRoleId <= $requiredRoleId;
    }

    /**
     * Require authentication - returns payload or sends 401
     */
    public function requireAuth(): ?array
    {
        $token = $this->getTokenFromHeader();

        if (!$token) {
            http_response_code(401);
            echo json_encode(['error' => 'No token provided']);
            exit();
        }

        $payload = $this->validateToken($token);

        if (!$payload) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid or expired token']);
            exit();
        }

        return $payload;
    }

    /**
     * Require specific role - returns payload or sends 403
     */
    public function requireRole(int $requiredRoleId): ?array
    {
        $payload = $this->requireAuth();

        if (!$this->hasRole($requiredRoleId)) {
            http_response_code(403);
            echo json_encode(['error' => 'Insufficient permissions']);
            exit();
        }

        return $payload;
    }

    /**
     * Base64 URL encode
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64 URL decode
     */
    private function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * Set custom expiration time (in seconds)
     */
    public function setExpirationTime(int $seconds): void
    {
        $this->expirationTime = $seconds;
    }
}
