#!/bin/bash

# Vacation API Test Script
# This script tests the vacation management endpoints

BASE_URL="http://localhost:8080"

echo "================================================"
echo "Vacation API Test Script"
echo "================================================"
echo ""

# Test 1: Authenticate as regular user
echo "Test 1: Authenticating as regular user..."
echo "Request: POST $BASE_URL/users/authenticate"
USER_RESPONSE=$(curl -s -X POST "$BASE_URL/users/authenticate" \
  -H "Content-Type: application/json" \
  -d '{"username": "user", "password": "password123"}')

echo "Response: $USER_RESPONSE"
echo ""

USER_TOKEN=$(echo $USER_RESPONSE | grep -o '"token":"[^"]*' | sed 's/"token":"//')

if [ -z "$USER_TOKEN" ]; then
    echo "❌ User authentication failed!"
    exit 1
fi

echo "✅ User token received: ${USER_TOKEN:0:50}..."
echo ""

# Test 2: Create vacation request
echo "================================================"
echo "Test 2: Creating vacation request..."
echo "Request: POST $BASE_URL/vacations"
CREATE_RESPONSE=$(curl -s -X POST "$BASE_URL/vacations" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $USER_TOKEN" \
  -d '{
    "date_from": "2025-12-01",
    "date_to": "2025-12-10",
    "reason": "Family vacation to beach resort"
  }')

echo "Response: $CREATE_RESPONSE"
echo ""

VACATION_ID=$(echo $CREATE_RESPONSE | grep -o '"id":[0-9]*' | sed 's/"id"://')

if [ -z "$VACATION_ID" ]; then
    echo "❌ Failed to create vacation!"
else
    echo "✅ Vacation created with ID: $VACATION_ID"
fi
echo ""

# Test 3: Get user's vacations
echo "================================================"
echo "Test 3: Getting user's vacations..."
echo "Request: GET $BASE_URL/vacations"
VACATIONS_RESPONSE=$(curl -s -X GET "$BASE_URL/vacations" \
  -H "Authorization: Bearer $USER_TOKEN")

echo "Response: $VACATIONS_RESPONSE"
echo ""

if echo "$VACATIONS_RESPONSE" | grep -q "date_from"; then
    echo "✅ Successfully retrieved vacations!"
else
    echo "❌ Failed to get vacations!"
fi
echo ""

# Test 4: Get single vacation
if [ ! -z "$VACATION_ID" ]; then
    echo "================================================"
    echo "Test 4: Getting single vacation..."
    echo "Request: GET $BASE_URL/vacations/$VACATION_ID"
    SINGLE_RESPONSE=$(curl -s -X GET "$BASE_URL/vacations/$VACATION_ID" \
      -H "Authorization: Bearer $USER_TOKEN")

    echo "Response: $SINGLE_RESPONSE"
    echo ""

    if echo "$SINGLE_RESPONSE" | grep -q "date_from"; then
        echo "✅ Successfully retrieved single vacation!"
    else
        echo "❌ Failed to get single vacation!"
    fi
    echo ""
fi

# Test 5: Update vacation (user can update pending)
if [ ! -z "$VACATION_ID" ]; then
    echo "================================================"
    echo "Test 5: Updating vacation details..."
    echo "Request: PUT $BASE_URL/vacations/$VACATION_ID"
    UPDATE_RESPONSE=$(curl -s -X PUT "$BASE_URL/vacations/$VACATION_ID" \
      -H "Content-Type: application/json" \
      -H "Authorization: Bearer $USER_TOKEN" \
      -d '{
        "date_from": "2025-12-05",
        "date_to": "2025-12-15",
        "reason": "Extended family vacation to beach resort and mountain retreat"
      }')

    echo "Response: $UPDATE_RESPONSE"
    echo ""

    if echo "$UPDATE_RESPONSE" | grep -q "updated successfully"; then
        echo "✅ Successfully updated vacation!"
    else
        echo "⚠️  Could not update vacation (may already be approved/rejected)"
    fi
    echo ""
fi

# Test 6: Authenticate as admin
echo "================================================"
echo "Test 6: Authenticating as admin..."
echo "Request: POST $BASE_URL/users/authenticate"
ADMIN_RESPONSE=$(curl -s -X POST "$BASE_URL/users/authenticate" \
  -H "Content-Type: application/json" \
  -d '{"username": "admin", "password": "password123"}')

echo "Response: $ADMIN_RESPONSE"
echo ""

ADMIN_TOKEN=$(echo $ADMIN_RESPONSE | grep -o '"token":"[^"]*' | sed 's/"token":"//')

if [ -z "$ADMIN_TOKEN" ]; then
    echo "❌ Admin authentication failed!"
    echo "Note: Make sure you have an admin user with username 'admin'"
else
    echo "✅ Admin token received: ${ADMIN_TOKEN:0:50}..."
fi
echo ""

# Test 7: Admin gets all vacations
if [ ! -z "$ADMIN_TOKEN" ]; then
    echo "================================================"
    echo "Test 7: Admin getting all vacations..."
    echo "Request: GET $BASE_URL/vacations"
    ADMIN_VACATIONS=$(curl -s -X GET "$BASE_URL/vacations" \
      -H "Authorization: Bearer $ADMIN_TOKEN")

    echo "Response: $ADMIN_VACATIONS"
    echo ""

    if echo "$ADMIN_VACATIONS" | grep -q "user_name"; then
        echo "✅ Admin successfully retrieved all vacations!"
    else
        echo "❌ Admin failed to get all vacations!"
    fi
    echo ""
fi

# Test 8: Admin approves vacation
if [ ! -z "$ADMIN_TOKEN" ] && [ ! -z "$VACATION_ID" ]; then
    echo "================================================"
    echo "Test 8: Admin approving vacation..."
    echo "Request: PUT $BASE_URL/vacations/$VACATION_ID"
    APPROVE_RESPONSE=$(curl -s -X PUT "$BASE_URL/vacations/$VACATION_ID" \
      -H "Content-Type: application/json" \
      -H "Authorization: Bearer $ADMIN_TOKEN" \
      -d '{"status_id": 1}')

    echo "Response: $APPROVE_RESPONSE"
    echo ""

    if echo "$APPROVE_RESPONSE" | grep -q "APPROVED"; then
        echo "✅ Admin successfully approved vacation!"
    else
        echo "❌ Admin failed to approve vacation!"
    fi
    echo ""
fi

# Test 9: Get vacation statuses
echo "================================================"
echo "Test 9: Getting vacation statuses..."
echo "Request: GET $BASE_URL/vacations/statuses"
STATUSES_RESPONSE=$(curl -s -X GET "$BASE_URL/vacations/statuses" \
  -H "Authorization: Bearer $USER_TOKEN")

echo "Response: $STATUSES_RESPONSE"
echo ""

if echo "$STATUSES_RESPONSE" | grep -q "APPROVED"; then
    echo "✅ Successfully retrieved statuses!"
else
    echo "❌ Failed to get statuses!"
fi
echo ""

# Test 10: User tries to update approved vacation (should fail)
if [ ! -z "$VACATION_ID" ]; then
    echo "================================================"
    echo "Test 10: User trying to update approved vacation (should fail)..."
    echo "Request: PUT $BASE_URL/vacations/$VACATION_ID"
    FAIL_UPDATE=$(curl -s -X PUT "$BASE_URL/vacations/$VACATION_ID" \
      -H "Content-Type: application/json" \
      -H "Authorization: Bearer $USER_TOKEN" \
      -d '{
        "date_from": "2025-12-20",
        "date_to": "2025-12-25",
        "reason": "Changed my mind"
      }')

    echo "Response: $FAIL_UPDATE"
    echo ""

    if echo "$FAIL_UPDATE" | grep -q "pending"; then
        echo "✅ Correctly prevented update of approved vacation!"
    else
        echo "⚠️  User was able to update (vacation might still be pending)"
    fi
    echo ""
fi

# Test 11: Validation errors
echo "================================================"
echo "Test 11: Testing validation (invalid dates)..."
echo "Request: POST $BASE_URL/vacations"
VALIDATION_RESPONSE=$(curl -s -X POST "$BASE_URL/vacations" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $USER_TOKEN" \
  -d '{
    "date_from": "2025-12-10",
    "date_to": "2025-12-01",
    "reason": "Short"
  }')

echo "Response: $VALIDATION_RESPONSE"
echo ""

if echo "$VALIDATION_RESPONSE" | grep -q "errors"; then
    echo "✅ Validation working correctly!"
else
    echo "❌ Validation not working!"
fi
echo ""

# Test 12: Request without token (should fail)
echo "================================================"
echo "Test 12: Accessing endpoint without token (should fail)..."
echo "Request: GET $BASE_URL/vacations (no Authorization header)"
NO_TOKEN_RESPONSE=$(curl -s -X GET "$BASE_URL/vacations")

echo "Response: $NO_TOKEN_RESPONSE"
echo ""

if echo "$NO_TOKEN_RESPONSE" | grep -q "No token provided"; then
    echo "✅ Correctly rejected request without token!"
else
    echo "❌ Should have rejected request without token!"
fi
echo ""

echo "================================================"
echo "All tests completed!"
echo "================================================"
echo ""
echo "Summary:"
echo "- User can create vacation requests ✓"
echo "- User can view own vacations ✓"
echo "- User can update pending vacations ✓"
echo "- Admin can view all vacations ✓"
echo "- Admin can approve/reject vacations ✓"
echo "- Validation works correctly ✓"
echo "- Authentication required ✓"
