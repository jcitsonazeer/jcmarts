# JCMarts Auth API (Customer)

Base URL:

```text
https://jcmarts.com/api/v1
```

Common headers:

```text
Accept: application/json
Content-Type: application/json
```

Protected API header (after login):

```text
Authorization: Bearer {token}
```

---

## Endpoint overview

| Method | Endpoint                | Auth needed | Description                    |
| ------ | ----------------------- | ----------- | ------------------------------ |
| POST   | `/otp/send`             | No          | Send login OTP for a customer  |
| POST   | `/otp/verify`           | No          | Verify OTP and log in          |
| POST   | `/register/otp/send`    | No          | Send registration OTP          |
| POST   | `/register/otp/verify`  | No          | Verify registration OTP        |
| POST   | `/logout`               | Yes         | Log out the customer           |

### Validation rules

`mobile_number` / `mobile` must be 10–15 digits. `mobile` is accepted as an
alternate field name. OTP is always 6 digits.

---

## 1. Send Welcome/Login OTP

```text
POST /otp/send
```

Payload:

```json
{
  "mobile_number": "9876543210",
  "name": "Customer"
}
```

`name` is optional. This endpoint works only for **already-registered,
verified** customers.

Success response:

```json
{
  "status": true,
  "message": "OTP sent successfully",
  "data": {
    "mobile_number": "9876543210",
    "expires_in_seconds": 180,
    "otp": "123456"
  }
}
```

The `otp` field is returned only outside production (for testing).

Errors:

- `404` — Mobile number not registered. Please sign up first.
- `422` — Account is inactive.

---

## 2. Verify OTP / Login

```text
POST /otp/verify
```

Payload:

```json
{
  "mobile_number": "9876543210",
  "otp": "123456"
}
```

Success response:

```json
{
  "status": true,
  "message": "Login successful",
  "data": {
    "token": "token_here",
    "token_type": "Bearer",
    "role": "customer",
    "customer": {
      "id": 1,
      "name": "Customer",
      "mobile_number": "9876543210",
      "verified_status": "verified"
    },
    "cart": null
  }
}
```

Flutter should store `data.token` and send it in the `Authorization: Bearer`
header for protected APIs.

**Guest cart auto-merge:** if the request includes the `X-Device-ID` header,
the guest cart for that device is merged into the customer cart right after
login, and `data.cart` returns the merged `{ items, item_count, sub_total }`.

Errors:

- `401` Invalid OTP
- `401` OTP expired

---

## 3. Send Registration OTP

```text
POST /register/otp/send
```

Creates a new customer (or resumes a pending registration).

Payload:

```json
{
  "name": "Customer",
  "mobile_number": "9876543210"
}
```

`name` is required for registration.

Success response:

```json
{
  "status": true,
  "message": "Registration OTP sent successfully",
  "data": {
    "mobile_number": "9876543210",
    "expires_in_seconds": 180,
    "otp": "123456"
  }
}
```

Errors:

- `422` — This mobile number is already registered. Please login.
- `422` — This account is inactive.

---

## 4. Verify Registration OTP

```text
POST /register/otp/verify
```

Payload:

```json
{
  "mobile_number": "9876543210",
  "otp": "123456"
}
```

Success response:

```json
{
  "status": true,
  "message": "Registration completed successfully",
  "data": {
    "token": "token_here",
    "token_type": "Bearer",
    "role": "customer",
    "customer": {
      "id": 1,
      "name": "Customer",
      "mobile_number": "9876543210",
      "verified_status": "verified"
    },
    "cart": null
  }
}
```

Same `X-Device-ID` guest-cart auto-merge behaviour as login.

Errors:

- `401` Invalid OTP
- `401` OTP expired

---

## 5. Logout

```text
POST /logout
```

Protected (customer token). Deletes the current token.

Success response:

```json
{
  "status": true,
  "message": "Logged out successfully",
  "data": null
}
```

---

## Notes

- `role` is decided by Laravel (`customer` / `delivery_person`), never trusted
  from the app.
- A **delivery** token calling customer endpoints (and vice versa) gets
  `403 Forbidden`.
- Related modules: `docs/cart.md`, `docs/profile.md`.