#!/bin/bash

# JWT Authentication Test Script
# This script tests the JWT authentication endpoints

BASE_URL="http://localhost/users"

echo "================================================"
echo "JWT Authentication Test Script"
echo "================================================"
echo ""

# Test 1: Authenticate and get JWT token
echo "Test 1: Authenticating user..."
echo "Request: POST $BASE_URL/authenticate"
RESPONSE=$(curl -s -X POST "$BASE_URL/authenticate" \
  -H "Content-Type: application/json" \
  -d '{"username": "admin", "password": "password123"}')

echo "Response: $RESPONSE"
echo ""

# Extract token from response
TOKEN=$(echo $RESPONSE | grep -o '"token":"[^"]*' | sed 's/"token":"//')

if [ -z "$TOKEN" ]; then
    echo "❌ Authentication failed! No token received."
    echo "Make sure you have a user with username 'admin' and password 'password123'"
    exit 1
fi

echo "✅ Token received: ${TOKEN:0:50}..."
echo ""

# Test 2: Validate token
echo "================================================"
echo "Test 2: Validating JWT token..."
echo "Request: POST $BASE_URL/validate"
RESPONSE=$(curl -s -X POST "$BASE_URL/validate" \
  -H "Authorization: Bearer $TOKEN")

echo "Response: $RESPONSE"
echo ""

if echo "$RESPONSE" | grep -q '"valid":true'; then
    echo "✅ Token is valid!"
else
    echo "❌ Token validation failed!"
fi
echo ""

# Test 3: Get user profile
echo "================================================"
echo "Test 3: Getting user profile (protected endpoint)..."
echo "Request: GET $BASE_URL/profile"
RESPONSE=$(curl -s -X GET "$BASE_URL/profile" \
  -H "Authorization: Bearer $TOKEN")

echo "Response: $RESPONSE"
echo ""

if echo "$RESPONSE" | grep -q '"user"'; then
    echo "✅ Profile retrieved successfully!"
else
    echo "❌ Failed to get profile!"
fi
echo ""

# Test 4: Access admin endpoint
echo "================================================"
echo "Test 4: Accessing admin endpoint..."
echo "Request: GET $BASE_URL/admin"
RESPONSE=$(curl -s -X GET "$BASE_URL/admin" \
  -H "Authorization: Bearer $TOKEN")

echo "Response: $RESPONSE"
echo ""

if echo "$RESPONSE" | grep -q '"message":"Welcome admin!"'; then
    echo "✅ Admin access granted!"
elif echo "$RESPONSE" | grep -q '"error":"Insufficient permissions"'; then
    echo "⚠️  User is not admin (role_id != 1)"
else
    echo "❌ Failed to access admin endpoint!"
fi
echo ""

# Test 5: Request without token (should fail)
echo "================================================"
echo "Test 5: Accessing protected endpoint without token..."
echo "Request: GET $BASE_URL/profile (no Authorization header)"
RESPONSE=$(curl -s -X GET "$BASE_URL/profile")

echo "Response: $RESPONSE"
echo ""

if echo "$RESPONSE" | grep -q '"error":"No token provided"'; then
    echo "✅ Correctly rejected request without token!"
else
    echo "❌ Should have rejected request without token!"
fi
echo ""

# Test 6: Request with invalid token
echo "================================================"
echo "Test 6: Accessing protected endpoint with invalid token..."
echo "Request: GET $BASE_URL/profile (invalid token)"
RESPONSE=$(curl -s -X GET "$BASE_URL/profile" \
  -H "Authorization: Bearer invalid_token_here")

echo "Response: $RESPONSE"
echo ""

if echo "$RESPONSE" | grep -q '"error":"Invalid or expired token"'; then
    echo "✅ Correctly rejected invalid token!"
else
    echo "❌ Should have rejected invalid token!"
fi
echo ""

echo "================================================"
echo "All tests completed!"
echo "================================================"
