# FlowCRM API Documentation

Base URL: `http://localhost:8000/api`

## Authentication

All endpoints (except login/register) require authentication via Laravel Sanctum session cookies.

### Login
```http
POST /api/login
Content-Type: application/json

{
  "email": "admin@flowcrm.test",
  "password": "password",
  "remember": false
}

Response:
{
  "user": {
    "id": 1,
    "name": "Admin",
    "email": "admin@flowcrm.test",
    "role": "admin"
  },
  "message": "Logged in successfully"
}
```

### Logout
```http
POST /api/logout

Response:
{
  "message": "Logged out successfully"
}
```

### Get Current User
```http
GET /api/user

Response:
{
  "user": {
    "id": 1,
    "name": "Admin",
    "email": "admin@flowcrm.test",
    "role": "admin"
  }
}
```

## Customers

### List Customers
```http
GET /api/customers?page=1&per_page=15&search=john&area_id=1&lead_status_id=2&source=inbound

Response:
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "name": "John Doe",
      "company": "ABC Corp",
      "area": {
        "id": 1,
        "name": "Jakarta"
      },
      "lead_status": {
        "id": 1,
        "name": "New Lead",
        "color": "#3B82F6"
      },
      "next_action_date": "2026-01-05",
      "next_action_plan": "Follow up call"
    }
  ],
  "total": 50,
  "per_page": 15,
  "last_page": 4
}
```

### Create Customer
```http
POST /api/customers
Content-Type: application/json

{
  "name": "John Doe",
  "company": "ABC Corp",
  "email": "john@abc.com",
  "whatsapp": "+628123456789",
  "area_id": 1,
  "source": "inbound",
  "lead_status_id": 1,
  "assigned_sales_id": 2,
  "notes": "Met at conference"
}

Response:
{
  "customer": { ... },
  "message": "Customer created successfully"
}
```

### Get Customer Detail
```http
GET /api/customers/1

Response:
{
  "id": 1,
  "name": "John Doe",
  "company": "ABC Corp",
  "area": { ... },
  "lead_status": { ... },
  "interactions": [
    {
      "id": 1,
      "interaction_type": "email_inbound",
      "channel": "email",
      "subject": "Inquiry",
      "content": "...",
      "interaction_at": "2026-01-02T10:30:00Z",
      "created_by_user": {
        "id": 1,
        "name": "Admin"
      }
    }
  ]
}
```

### Update Customer
```http
PUT /api/customers/1
Content-Type: application/json

{
  "name": "John Doe Updated",
  "area_id": 2,
  "lead_status_id": 3
}

Response:
{
  "customer": { ... },
  "message": "Customer updated successfully"
}
```

### Update Next Action
```http
POST /api/customers/1/next-action
Content-Type: application/json

{
  "next_action_date": "2026-01-10",
  "next_action_plan": "Send proposal",
  "next_action_priority": "high",
  "next_action_status": "pending"
}

Response:
{
  "customer": { ... },
  "message": "Next action updated successfully"
}
```

### Delete Customer
```http
DELETE /api/customers/1

Response:
{
  "message": "Customer deleted successfully"
}
```

## Interactions

### List Interactions
```http
GET /api/interactions?customer_id=1&interaction_type=email_inbound&channel=whatsapp

Response:
{
  "data": [
    {
      "id": 1,
      "customer": { ... },
      "interaction_type": "manual_channel",
      "channel": "whatsapp",
      "summary": "Discussed pricing",
      "interaction_at": "2026-01-02T14:30:00Z",
      "created_by_user": { ... }
    }
  ]
}
```

### Create Interaction
```http
POST /api/interactions
Content-Type: application/json

{
  "customer_id": 1,
  "interaction_type": "manual_channel",
  "channel": "whatsapp",
  "summary": "Followed up via WhatsApp. Customer interested in product X.",
  "interaction_at": "2026-01-02T15:00:00"
}

Response:
{
  "interaction": { ... },
  "message": "Interaction created successfully"
}
```

## Master Data

### List Areas
```http
GET /api/areas

Response:
[
  {
    "id": 1,
    "name": "Jakarta",
    "code": "JKT",
    "is_active": true
  }
]
```

### Create Area
```http
POST /api/areas
Content-Type: application/json

{
  "name": "Jakarta",
  "code": "JKT",
  "description": "Jakarta area",
  "is_active": true
}
```

### List Lead Statuses
```http
GET /api/lead-statuses

Response:
[
  {
    "id": 1,
    "name": "New Lead",
    "code": "new",
    "color": "#3B82F6",
    "order": 1,
    "is_active": true
  }
]
```

### Create Lead Status
```http
POST /api/lead-statuses
Content-Type: application/json

{
  "name": "Qualified",
  "code": "qualified",
  "color": "#10B981",
  "order": 3,
  "is_active": true
}
```

## Dashboard

### Get Dashboard Statistics
```http
GET /api/dashboard/stats

Response:
{
  "total_customers": 150,
  "hot_leads": 12,
  "overdue_followups": 8,
  "action_today": 5,
  "dormant_leads": 20,
  "new_inbound_today": 3,
  "customers_by_area": [
    {
      "area": "Jakarta",
      "total": 50
    }
  ],
  "leads_by_status": [
    {
      "status": "New Lead",
      "color": "#3B82F6",
      "total": 30
    }
  ]
}
```

## Enums

### Interaction Types
- `email_inbound` - Email masuk (auto)
- `email_outbound` - Email keluar (auto)
- `manual_channel` - Channel manual (user input)
- `note` - Internal note

### Channels
- `email` - Email
- `whatsapp` - WhatsApp
- `telephone` - Telephone
- `instagram` - Instagram
- `tiktok` - TikTok
- `tokopedia` - Tokopedia
- `shopee` - Shopee
- `lazada` - Lazada
- `website_chat` - Website Chat
- `other` - Other

### Source
- `inbound` - Inbound lead
- `outbound` - Outbound lead

### Next Action Priority
- `low` - Low priority
- `medium` - Medium priority
- `high` - High priority

### Next Action Status
- `pending` - Pending
- `done` - Done
- `overdue` - Overdue (computed)

### User Roles
- `admin` - Administrator
- `sales` - Sales
- `marketing` - Marketing
- `manager` - Manager

## Error Responses

### Validation Error (422)
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": [
      "The email field is required."
    ]
  }
}
```

### Unauthorized (401)
```json
{
  "message": "Unauthenticated."
}
```

### Not Found (404)
```json
{
  "message": "Resource not found."
}
```

### Server Error (500)
```json
{
  "message": "Server error occurred."
}
```

## Notes

1. All timestamps are in ISO 8601 format (UTC)
2. Pagination is handled via `page` and `per_page` query parameters
3. Search is case-insensitive and searches across name, company, email, and whatsapp fields
4. Filters can be combined for more specific queries
5. CSRF token is automatically handled by Sanctum for same-origin requests
