# CSM Technologies
# Quality System Document 
# Personal Finance Management — API Documentation
# VERSION 1.0

**Base URL:** `http://127.0.0.1:8000/api`
**Auth:** Bearer JWT Token — `Authorization: Bearer <token>`
**Content-Type:** `application/json`

---

## Authentication

### 1. Register
`POST /register`
Rate limit: 30 requests/min

**Request Body:**
```json
{
  "username": "admin",
  "password": "admin@123",
  "role": "EMPLOYEE"
}
```

| Field      | Required | Values                          |
|------------|----------|---------------------------------|
| `username` | Yes      | string, max 100, unique         |
| `password` | Yes      | string, min 6                   |
| `role`     | Yes      | `ADMIN`, `MANAGER`, `EMPLOYEE`  |

**Response 201:**
```json
{
  "message": "User Registered",
  "user": {
    "id": 1,
    "username": "admin",
    "role": "EMPLOYEE",
    "is_active": 1
  }
}
```

**Response 422 — Validation Error:**
```json
{
  "message": ["The username has already been taken."]
}
```

---

### 2. Login
`POST /login`
Rate limit: 30 requests/min

**Request Body:**
```json
{
  "username": "admin",
  "password": "secret123"
}
```

**Response 200:**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "user": {
    "id": 1,
    "username": "admin",
    "role": "EMPLOYEE",
    "is_active": 1
  }
}
```

**Response 401 — Wrong Username:**
```json
{ "message": "Invalid Username" }
```

**Response 401 — Wrong Password:**
```json
{ "message": "Invalid Password" }
```

**Response 403 — Inactive Account:**
```json
{ "message": "Account is inactive" }
```

---

### 3. Logout
`POST /logout`
Requires: Bearer Token

**Response 200:**
```json
{ "message": "Logged out successfully" }
```

**Response 401:**
```json
{ "message": "Invalid or Expired Token" }
```

---

### 4. Refresh Token
`POST /refresh-token`
Requires: Bearer Token

**Response 200:**
```json
{ "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..." }
```

**Response 401 — Token Expired:**
```json
{ "message": "Token expired, please login again" }
```

---

### 5. Forgot Password
`POST /forgot-password`

**Request Body:**
```json
{ "username": "john_doe" }
```

**Response 200:**
```json
{
  "message": "OTP generated successfully",
  "otp": 4823
}
```

**Response 404:**
```json
{ "message": "Username not found" }
```

---

### 6. Verify OTP
`POST /verify-otp`

**Request Body:**
```json
{
  "username": "admin",
  "otp": "4823"
}
```

**Response 200:**
```json
{
  "message": "OTP verified",
  "secret_key": "..."
}
```

**Response 422:**
```json
{ "message": "Invalid OTP" }
```

---

### 7. Reset Password
`POST /reset-password`

**Request Body:**
```json
{
  "username": "admin",
  "otp": "4823",
  "password": "newpassword123"
}
```

| Field      | Required | Rule          |
|------------|----------|---------------|
| `username` | Yes      | string        |
| `otp`      | Yes      | string        |
| `password` | Yes      | min 8 chars   |

**Response 200:**
```json
{ "success": true, "message": "Password reset successfully." }
```

**Response 400:**
```json
{ "success": false, "message": "Invalid OTP." }
```

---

## Employee Management
> Requires Bearer Token. Role: `ADMIN`, `MANAGER` only.

### 8. List Employees
`GET /employees`

**Response 200:**
```json
[
  { "id": 1, "name": "John Doe", "email": "john@example.com" }
]
```

### 9. Create Employee
`POST /employees`

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "department_id": 1
}
```

### 10. Update Employee
`PUT /employees/{id}`

### 11. Delete Employee
`DELETE /employees/{id}`

---

## Categories
> Requires Bearer Token. All roles.

### 12. List Categories
`GET /categories`

Response is **Redis cached** (1 hour TTL). Cache invalidated on create/update/delete.

`source` field: `"redis"` = cache hit, `"database"` = cache miss.

**Response 200:**
```json
{
  "source": "redis",
  "data": [
    { "id": 1, "category_name": "Salary", "category_type": "INCOME", "is_default": 1 },
    { "id": 4, "category_name": "Groceries", "category_type": "EXPENSE", "is_default": 1 }
  ]
}
```

---

### 13. Get Default Categories
`GET /categories/default`

**Response 200:**
```json
[
  { "id": 1, "category_name": "Salary", "category_type": "INCOME", "is_default": 1 }
]
```

---

### 14. Get Single Category
`GET /categories/{id}`

**Response 200:**
```json
{ "id": 1, "category_name": "Salary", "category_type": "INCOME", "is_default": 1 }
```

**Response 404:**
```json
{ "message": "Category Not Found" }
```

---

### 15. Create Category
`POST /categories`

**Request Body:**
```json
{
  "category_name": "Medical",
  "category_type": "EXPENSE"
}
```

| Field           | Required | Values              |
|-----------------|----------|---------------------|
| `category_name` | Yes      | string, max 100     |
| `category_type` | Yes      | `INCOME`, `EXPENSE` |

**Response 200:**
```json
{
  "message": "Category Created Successfully",
  "data": { "id": 17, "category_name": "Medical", "category_type": "EXPENSE" }
}
```

**Response 422 — Duplicate:**
```json
{ "message": "Category already exists" }
```

---

### 16. Update Category
`PUT /categories/{id}`

**Request Body:**
```json
{
  "category_name": "Medical Expense",
  "category_type": "EXPENSE"
}
```

**Response 200:**
```json
{ "message": "Category Updated Successfully" }
```

---

### 17. Delete Category
`DELETE /categories/{id}`

Cannot delete default categories (`is_default = 1`).

**Response 200:**
```json
{ "message": "Category Deleted Successfully" }
```

**Response 422:**
```json
{ "message": "Default Category Cannot Be Deleted" }
```

---

## Transactions
> Requires Bearer Token. All roles.
> Each user sees only their own transactions (multi-user isolation).

### 18. List Transactions
`GET /transactions`

Supports optional query filters:

| Query Param        | Type   | Example                    |
|--------------------|--------|----------------------------|
| `date_from`        | date   | `2026-06-01`               |
| `date_to`          | date   | `2026-06-30`               |
| `category_id`      | int    | `4`                        |
| `transaction_type` | string | `INCOME` or `EXPENSE`      |

**Example with filters:**
```
GET /transactions?transaction_type=EXPENSE&date_from=2026-06-01&date_to=2026-06-30
```

**Response 200:**
```json
[
  {
    "id": 12,
    "transaction_date": "2026-06-10",
    "amount": "25000.00",
    "transaction_type": "EXPENSE",
    "description": "test",
    "payment_mode": "CASH",
    "category_id": 8,
    "created_by": 13,
    "category": {
      "id": 8,
      "category_name": "Entertainment",
      "category_type": "EXPENSE"
    }
  }
]
```

> **Note:** Filter works on `transaction_date` column. Make sure your transactions have dates in the range you are filtering.

---

### 19. Create Transaction
`POST /transactions`

**Request Body:**
```json
{
  "transaction_date": "2026-06-18",
  "amount": 5000,
  "transaction_type": "EXPENSE",
  "category_id": 4,
  "description": "Monthly grocery",
  "payment_mode": "UPI"
}
```

| Field              | Required | Values                                        |
|--------------------|----------|-----------------------------------------------|
| `transaction_date` | Yes      | date `YYYY-MM-DD`                             |
| `amount`           | Yes      | numeric, min 0.01                             |
| `transaction_type` | Yes      | `INCOME`, `EXPENSE`                           |
| `category_id`      | Yes      | integer                                       |
| `description`      | No       | string                                        |
| `payment_mode`     | No       | `CASH`, `CARD`, `UPI`, `NET_BANKING`, `OTHER` (default: `CASH`) |

**Response 200:**
```json
{
  "message": "Transaction Created Successfully",
  "data": {
    "id": 17,
    "transaction_date": "2026-06-18",
    "amount": "5000.00",
    "transaction_type": "EXPENSE",
    "payment_mode": "UPI"
  }
}
```

---

### 20. Update Transaction
`PUT /transactions/{id}`

Same fields as Create.

**Response 200:**
```json
{ "message": "Transaction Updated Successfully" }
```

**Response 404:**
```json
{ "message": "Transaction Not Found" }
```

---

### 21. Delete Transaction
`DELETE /transactions/{id}`

Soft delete — record is not permanently removed.

**Response 200:**
```json
{ "message": "Transaction Deleted Successfully" }
```

---

### 22. Get Single Transaction
`GET /transactions/{id}`

**Response 200:**
```json
{
  "id": 11,
  "transaction_date": "2026-06-10",
  "amount": "911.00",
  "transaction_type": "INCOME",
  "payment_mode": "CASH",
  "description": "business income",
  "category": { "id": 3, "category_name": "Business Income" }
}
```

---

## Budgets
> Requires Bearer Token. All roles.
> Each user sees only their own budgets.

### 23. List Budgets
`GET /budgets`

**Response 200:**
```json
[
  {
    "id": 1,
    "category_id": 1,
    "budget_month": 6,
    "budget_year": 2026,
    "budget_amount": "25000.00",
    "created_by": 1,
    "category_name": "Salary"
  }
]
```

---

### 24. Create Budget
`POST /budgets`

**Request Body:**
```json
{
  "category_id": 4,
  "budget_month": 6,
  "budget_year": 2026,
  "budget_amount": 10000
}
```

| Field           | Required | Values                   |
|-----------------|----------|--------------------------|
| `category_id`   | Yes      | integer                  |
| `budget_month`  | Yes      | integer, 1–12            |
| `budget_year`   | Yes      | integer, min 2000        |
| `budget_amount` | Yes      | numeric, min 1           |

**Response 200:**
```json
{ "message": "Budget Saved Successfully" }
```

---

### 25. Update Budget
`PUT /budgets/{id}`

Same fields as Create.

**Response 200:**
```json
{ "message": "Budget Updated Successfully" }
```

---

### 26. Delete Budget
`DELETE /budgets/{id}`

Soft delete.

**Response 200:**
```json
{ "message": "Budget Deleted Successfully" }
```

---

### 27. Budget Tracking
`GET /budget-tracking`

Category-wise budget vs actual spending with status.

**Response 200:**
```json
[
  {
    "category_name": "Groceries",
    "budget_amount": "10000.00",
    "spent_amount": "7500.00",
    "percentage": 75.00,
    "status": "Warning"
  },
  {
    "category_name": "Healthcare",
    "budget_amount": "5000.00",
    "spent_amount": "1500.00",
    "percentage": 30.00,
    "status": "Safe"
  },
  {
    "category_name": "Entertainment",
    "budget_amount": "3000.00",
    "spent_amount": "3500.00",
    "percentage": 116.67,
    "status": "Exceeded"
  }
]
```

**Budget Status Rules:**

| Condition          | Status       |
|--------------------|--------------|
| Spent < 70%        | `Safe`       |
| Spent 70% – 100%   | `Warning`    |
| Spent > 100%       | `Exceeded`   |

---

### 28. Budget Overview
`GET /budget-overview`

Total budget summary for logged-in user.

**Response 200:**
```json
{
  "budget": 50000,
  "spent": 42000,
  "remaining": 8000,
  "status": "Warning"
}
```

---

## Dashboard & Analytics
> Requires Bearer Token. All roles.
> Response is **Redis cached** — 5 min TTL, per user.

### 29. Dashboard
`GET /dashboard`

**Response 200:**
```json
{
  "source": "database",
  "data": {
    "financial_overview": {
      "total_income": 500000.00,
      "total_expense": 42000.00,
      "net_savings": 458000.00,
      "net_balance": 458000.00,
      "savings_percentage": 91.60
    },
    "top_spending_categories": [
      { "category_name": "Entertainment", "total_spent": 25000.00 },
      { "category_name": "Groceries", "total_spent": 12000.00 }
    ],
    "spending_breakdown": [
      { "category_name": "Entertainment", "amount": 25000.00 },
      { "category_name": "Groceries", "amount": 12000.00 }
    ],
    "monthly_chart": [
      { "month": "2026-01", "income": 50000.00, "expense": 8000.00 },
      { "month": "2026-06", "income": 50000.00, "expense": 34000.00 }
    ],
    "budget_progress": [
      {
        "category_name": "Groceries",
        "budget_amount": "10000.00",
        "spent": 7500.00,
        "remaining": 2500.00,
        "percentage": 75.00,
        "status": "Warning"
      }
    ],
    "budget_utilization": [
      {
        "category": "Groceries",
        "budget": "10000.00",
        "spent": 7500.00,
        "percentage": 75.00,
        "status": "Warning"
      }
    ],
    "recent_transactions": [
      {
        "id": 15,
        "transaction_date": "2026-06-17",
        "amount": "5000.00",
        "transaction_type": "EXPENSE",
        "description": "Grocery shopping",
        "payment_mode": "UPI",
        "category_name": "Groceries"
      }
    ]
  }
}
```

`source` field:
- `"database"` → Fresh DB query, response cached for 5 min
- `"redis"` → Served from cache (fast response)

---

## Common Error Responses

| Status | Scenario | Response |
|--------|----------|----------|
| `401` | No token / invalid token | `{ "message": "Invalid or Expired Token" }` |
| `403` | Role not allowed | `{ "message": "Forbidden Access" }` |
| `404` | Record not found | `{ "message": "Transaction Not Found" }` |
| `422` | Validation failed | `{ "errors": { "field": ["message"] } }` |
| `429` | Rate limit hit | `{ "message": "Too Many Requests. Please try again later." }` |

---

## Audit Log

Ye events automatically `audit_logs` table mein store hote hain:

| Action                | Trigger                          |
|-----------------------|----------------------------------|
| `LOGIN`               | Successful login                 |
| `LOGIN_FAILED`        | Wrong username or password       |
| `LOGOUT`              | User logout                      |
| `TRANSACTION_CREATED` | New transaction added            |
| `TRANSACTION_UPDATED` | Transaction modified             |
| `BUDGET_CREATED`      | New budget set                   |
| `BUDGET_UPDATED`      | Budget modified                  |

Each record stores: `user_id`, `action`, `description`, `ip_address`, `created_at`

---

## Redis Caching Summary

| Endpoint          | Cache Key                    | TTL    | Invalidated When                      |
|-------------------|------------------------------|--------|---------------------------------------|
| `GET /categories` | `categories_all`             | 1 hour | Category create / update / delete     |
| `GET /dashboard`  | `dashboard_user_{user_id}`   | 5 min  | Automatic TTL expiry                  |
