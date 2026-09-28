# COMMUNICATIONHUB_007 – Enterprise API Gateway

The API Gateway is the official interface for ERP modules to request communication services without depending on SMS, email, WhatsApp, push or wallet implementation details.

## Authentication

Use one of the following headers:

- `Authorization: Bearer <token>`
- `X-CommunicationHub-Token: <token>`

A test client is seeded with token:

`communicationhub-test-token`

Change or disable this token before production use.

## Endpoints

Base prefix: `/api/communication-hub/gateway`

- `POST /send`
- `POST /send/sms`
- `POST /send/email`
- `POST /send/whatsapp`
- `POST /send/push`
- `POST /estimate-cost`
- `GET /delivery-status/{messageId}`
- `POST /otp/generate`
- `POST /otp/verify`

## Send SMS Example

```json
{
  "recipient": "94770000000",
  "body": "Your OTP is 123456",
  "source_reference": "LOGIN-001"
}
```

## Send Email Example

```json
{
  "recipient": "member@example.com",
  "subject": "Appointment Reminder",
  "body": "Your appointment is tomorrow.",
  "source_reference": "APT-001"
}
```

## OTP Generate Example

```json
{
  "identifier": "94770000000",
  "purpose": "my_health_login",
  "source_module": "MyHealthMembers"
}
```

## OTP Verify Example

```json
{
  "identifier": "94770000000",
  "purpose": "my_health_login",
  "otp": "123456"
}
```

## Notes

- All requests are logged in `communication_hub_api_request_logs`.
- API clients are stored in `communication_hub_api_clients`.
- The gateway queues messages rather than directly calling providers.
- Wallet charging remains isolated through the standalone wallet interface.
