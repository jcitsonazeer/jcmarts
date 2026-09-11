# JCMarts Order API

Base URL:

```text
https://jcmarts.com/api/v1
```

All endpoints in this module require a **customer** token:

```text
Authorization: Bearer {token}
```

---

## Endpoint overview

| Method | Endpoint                     | Auth needed | Description                            |
| ------ | ---------------------------- | ----------- | -------------------------------------- |
| GET    | `/orders`                    | Yes         | List customer orders                   |
| GET    | `/orders/{orderId}`          | Yes         | Full order details                     |
| GET    | `/orders/returns/reasons`    | Yes         | Valid return reasons                   |
| POST   | `/orders/{orderId}/cancel`   | Yes         | Request a cancellation                 |
| POST   | `/orders/{orderId}/return`   | Yes         | Submit a return request                |

> Important: `/orders/returns/reasons` is a fixed route and must stay before
> `/orders/{orderId}` in Flutter's URL routing.

---

## 1. List orders

```text
GET /orders
```

Optional query parameter:

| Parameter | Type | Description                                       |
| --------- | ---- | ------------------------------------------------- |
| `q`       | str  | (optional) Search by order id / status / payment  |

Success response:

```json
{
  "status": true,
  "message": "Orders fetched successfully",
  "data": [
    {
      "id": 24,
      "current_order_status": "delivered",
      "total_amount": "119.00",
      "currency": "INR",
      "items_count": 2,
      "current_payment_method": "razorpay",
      "current_payment_status": "paid",
      "created_date": "2026-09-01T12:00:00.000000Z"
    }
  ]
}
```

---

## 2. Order details

```text
GET /orders/{orderId}
```

Example: `GET /orders/24`.

Success response:

```json
{
  "status": true,
  "message": "Order fetched successfully",
  "data": {
    "id": 24,
    "sub_total": "119.00",
    "delivery_charge": "0.00",
    "packing_charge": "0.00",
    "other_charge": "0.00",
    "total_amount": "119.00",
    "currency": "INR",
    "current_order_status": "out_for_delivery",
    "current_payment_method": "razorpay",
    "current_payment_status": "paid",
    "current_payment_paid_at": "2026-09-01T12:05:00.000000Z",
    "can_customer_cancel": true,
    "can_customer_return": false,
    "return_allowed_until": null,
    "return_period_expired": false,
    "returnable_items": [],
    "order_status_timeline": [],
    "items": [],
    "address": {
      "id": 2,
      "address_line_1": "garden street",
      "address_line_2": "lane view",
      "pincode": "629160"
    },
    "statuses": [],
    "payments": [],
    "refunds": [],
    "return_requests": [],
    "created_date": "2026-09-01T12:00:00.000000Z"
  }
}
```

Error:

- `404` — `"Order not found"` (or the order does not belong to this customer).

---

## 3. Return reasons

```text
GET /orders/returns/reasons
```

Returns the list of valid reasons that can be used when submitting a return
request.

Success response:

```json
{
  "status": true,
  "message": "Return reasons fetched successfully",
  "data": {
    "reasons": [
      "Product damaged",
      "Wrong item delivered"
    ]
  }
}
```

---

## 4. Cancel order

```text
POST /orders/{orderId}/cancel
```

Example: `POST /orders/24/cancel`. No payload body needed.

Only allowed while `can_customer_cancel` is true. Submits a cancellation
request; stock is restored only after admin approval.

Success response:

```json
{
  "status": true,
  "message": "Order cancellation request submitted successfully",
  "data": null
}
```

Error:

- `422` — with a message describing why cancellation is not allowed.

---

## 5. Return order

```text
POST /orders/{orderId}/return
```

Example: `POST /orders/24/return`.

Payload:

```json
{
  "reason": "Product damaged",
  "customer_note": "The packet was torn",
  "items": {
    "12": { "quantity": 1 },
    "15": { "quantity": 2 }
  }
}
```

Field notes:

| Field           | Type   | Description                                           |
| --------------- | ------ | ----------------------------------------------------- |
| `reason`        | string | Required. Must match one of the return reasons        |
| `customer_note` | string | Optional (max 1000 chars)                             |
| `items`         | object | Required. Key = `order_item_id`, value = `{ quantity }` |

Success response:

```json
{
  "status": true,
  "message": "Return request submitted successfully",
  "data": null
}
```

Error:

- `422` — with a message describing the failure (e.g. return period expired,
  invalid reason, wrong quantities).

---

## Notes

- Orders are created and paid through `docs/checkout.md`.
- Once delivered, delivery tracking is available in `docs/delivery.md`.