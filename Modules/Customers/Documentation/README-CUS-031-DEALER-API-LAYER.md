# CUS_031 Dealer Mobile App API Layer

## Purpose
Adds a standalone API layer for the Distribution Dealer portal inside `Modules/Customers`.

## Important
Run SQL first:

```sql
Customers/Database/sql/CUS_031_customer_dealer_api_tokens.sql
```

## Login
`POST /api/dealer/login`

Payload:

```json
{
  "passcode": "1234",
  "company_number": "OPTIONAL",
  "device_name": "Android App"
}
```

Returns a Bearer token.

## Authenticated API Calls
Send either:

```http
Authorization: Bearer <token>
```

or:

```http
X-Dealer-Token: <token>
```

## Main Endpoints

- `GET /api/dealer/me`
- `GET /api/dealer/profile`
- `GET /api/dealer/dashboard`
- `GET /api/dealer/analytics`
- `GET /api/dealer/credit-summary`
- `GET /api/dealer/ledger`
- `GET /api/dealer/statements`
- `GET /api/dealer/invoices`
- `GET /api/dealer/payments`
- `GET /api/dealer/orders`
- `POST /api/dealer/orders`
- `GET /api/dealer/deliveries`
- `GET /api/dealer/notifications`
- `GET /api/dealer/messages`
- `GET /api/dealer/loyalty`

## Safety
- API is isolated from ERP login and ERP sidebars.
- Dealer token can only access its own contact/customer data.
- No Contact, Petro, PetroPD, or Finance files are changed.
