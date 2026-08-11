# JCMarts API V1

Base URL for local testing:

```text
http://127.0.0.1:8000/api/v1
```

Production URL:

```text
https://yourdomain.com/api/v1
```

Common headers:

```text
Accept: application/json
Content-Type: application/json
```

Protected API header:

```text
Authorization: Bearer {token}
```

## 1. Send OTP

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

The `otp` field is returned only outside production for testing.

## 2. Verify OTP

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
    "customer": {
      "id": 1,
      "name": "Customer",
      "mobile_number": "9876543210",
      "verified_status": "verified"
    }
  }
}
```

Flutter should store `data.token` and send it in protected API headers.

## 3. Home

```text
GET /home
```

Response contains:

```text
product_categories
product_offers
featured_products
```

## 4. Categories

```text
GET /categories
```

Response contains categories with sub-categories.

## 5. Product List

```text
GET /products
```

Query parameters:

```text
sub_category_id
search
per_page
page
```

Example:

```text
GET /products?sub_category_id=1&search=rice&per_page=10&page=1
```

## 6. Product Details

```text
GET /products/{id}
```

Example:

```text
GET /products/1
```

## Logout

```text
POST /logout
```

Headers:

```text
Authorization: Bearer {token}
Accept: application/json
```
