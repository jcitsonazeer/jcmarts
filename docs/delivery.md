# JCMarts Delivery Person API (Flutter)

This document describes the **new** Delivery Person endpoints only.
These are the endpoints a Flutter developer needs for the Delivery Person part
of the app. Existing Customer endpoints (login, cart, orders, etc.) are NOT
changed and are documented separately.

Production URL:
```text
https://jcmarts.com/api/v1/delivery
```


| Method | Full Endpoint                                                          | Auth needed |
| ------ | ---------------------------------------------------------------------- | ----------- |
| POST   | `https://jcmarts.com/api/v1/delivery/otp/send`                         | No          |
| POST   | `https://jcmarts.com/api/v1/delivery/otp/verify`                       | No          |
| POST   | `https://jcmarts.com/api/v1/delivery/logout`                           | Yes         |
| GET    | `https://jcmarts.com/api/v1/delivery/me`                               | Yes         |
| GET    | `https://jcmarts.com/api/v1/delivery/dashboard`                        | Yes         |
| GET    | `https://jcmarts.com/api/v1/delivery/assigned-orders`                  | Yes         |
| GET    | `https://jcmarts.com/api/v1/delivery/deliveries/{deliveryId}`          | Yes         |
| POST   | `https://jcmarts.com/api/v1/delivery/deliveries/{deliveryId}/status`   | Yes         |
| GET    | `https://jcmarts.com/api/v1/delivery/history`                          | Yes         |
| PUT    | `https://jcmarts.com/api/v1/delivery/availability`                     | Yes         |
| POST   | `https://jcmarts.com/api/v1/delivery/deliveries/{deliveryId}/location` | Yes         |



Common headers:

```text
Accept: application/json
Content-Type: application/json
```

Protected API header (required after login):

```text
Authorization: Bearer {token}
```

---

## Important authentication rule

The `role` is decided by **Laravel**, never trusted from the app.

- Login returns a `token` + `role` = `delivery_person`.
- Every protected delivery endpoint requires a delivery-person token
  (`auth:sanctum` + `ensure.delivery`).
- A **customer token** calling these endpoints gets **403 Forbidden**.
- A delivery token can only see **its own** deliveries (other people's
  deliveries return 404 / 403).

The Flutter app keeps a single active token + role at a time.

---

## 1. Send OTP (Delivery Person Login)

```text
POST /otp/send
```

Payload:

```json
{
  "mobile": "7777777777"
}
```

Behavior:

- OTP is sent **only if** the mobile number belongs to a registered and
  `active` delivery person.
- If not registered → `404 Not Found`.
- If inactive → `403 Forbidden`.

Success response:

```json
{
  "status": true,
  "message": "OTP sent successfully",
  "data": {
    "mobile": "7777777777",
    "expires_in_seconds": 180,
    "otp": "123456"
  }
}
```

The `otp` field is returned only outside production (for testing).

---

## 2. Verify OTP (Delivery Person Login)

```text
POST /otp/verify
```

Payload:

```json
{
  "mobile": "7777777777",
  "otp": "123456"
}
```

Success response:

```json
{
  "status": true,
  "message": "Delivery person login successful",
  "data": {
    "token": "4|cgGfgd0...",
    "token_type": "Bearer",
    "role": "delivery_person",
    "delivery_person": {
      "id": 1,
      "name": "Kevin",
      "mobile": "7777777777",
      "vehicle_type": null,
      "vehicle_number": "",
      "status": "active",
      "availability_status": "offline"
    }
  }
}
```

Errors:

- Invalid OTP → `401 Invalid OTP`
- OTP expired → `401 OTP expired`

---

## 3. Logout (Delivery Person)

```text
POST /logout
```

Protected. Deletes the current delivery token.

Success response:

```json
{
  "status": true,
  "message": "Logged out successfully",
  "data": null
}
```

---

## 4. Current Delivery Person Profile

```text
GET /me
```

Protected. Returns the logged-in delivery person.

Success response:

```json
{
  "status": true,
  "message": "Delivery person profile fetched successfully",
  "data": {
    "role": "delivery_person",
    "delivery_person": {
      "id": 1,
      "name": "Kevin",
      "mobile": "7777777777",
      "email": null,
      "vehicle_type": null,
      "vehicle_number": "",
      "status": "active",
      "availability_status": "offline"
    }
  }
}
```

---

## 5. Dashboard / Summary

```text
GET /dashboard
```

Protected. Returns basic stats + the current active delivery (if any).

Success response:

```json
{
  "status": true,
  "message": "Delivery dashboard fetched successfully",
  "data": {
    "delivery_person": {
      "id": 1,
      "name": "Kevin",
      "vehicle_type": null,
      "vehicle_number": "",
      "availability_status": "offline"
    },
    "stats": {
      "active_deliveries": 1,
      "completed_deliveries": 5
    },
    "current_active_delivery": {
      "id": 12,
      "order_id": 24,
      "status": "out_for_delivery",
      "delivery_address": "garden street, lane view, 629160",
      "assigned_at": "2026-08-28T05:52:16.000000Z",
      "customer": {
        "id": 1,
        "name": "naz",
        "mobile_number": "9095680351"
      },
      "address": {
        "id": 2,
        "address_line_1": "garden street",
        "address_line_2": "lane view",
        "city": null,
        "state": null,
        "pincode": "629160"
      }
    }
  }
}
```

`current_active_delivery` is `null` when the delivery person has no active
delivery.

---

## 6. Assigned Orders (list)

```text
GET /assigned-orders
```

Protected. All deliveries currently assigned to this delivery person
(statuses: `assigned`, `accepted`, `picked_up`, `out_for_delivery`).

Success response:

```json
{
  "status": true,
  "message": "Assigned orders fetched successfully",
  "data": {
    "deliveries": [
      {
        "id": 12,
        "order_id": 24,
        "status": "out_for_delivery",
        "delivery_address": "garden street, lane view, 629160",
        "assigned_at": "2026-08-28T05:52:16.000000Z",
        "accepted_at": "2026-08-28T05:52:54.000000Z",
        "picked_up_at": "2026-08-28T05:53:00.000000Z",
        "out_for_delivery_at": "2026-08-28T05:54:20.000000Z",
        "delivered_at": null,
        "customer": {
          "id": 1,
          "name": "naz",
          "mobile_number": "9095680351"
        },
        "order_total": "119.00",
        "item_count": 2
      }
    ]
  }
}
```

---

## 7. Delivery Detail (single)

```text
GET /deliveries/{deliveryId}
```

Protected. Full detail of one delivery that belongs to this delivery person
(order, customer, address, items, status timeline, allowed next statuses).

If the delivery does not exist or does not belong to this person → `404`.

Success response:

```json
{
  "status": true,
  "message": "Delivery fetched successfully",
  "data": {
    "id": 12,
    "order_id": 24,
    "status": "delivered",
    "delivery_address": "garden street, lane view, 629160",
    "assigned_at": "2026-08-28T05:52:16.000000Z",
    "accepted_at": "2026-08-28T05:52:54.000000Z",
    "picked_up_at": "2026-08-28T05:53:00.000000Z",
    "out_for_delivery_at": "2026-08-28T05:54:20.000000Z",
    "delivered_at": "2026-08-28T05:54:26.000000Z",
    "allowed_next_statuses": [],
    "customer": {
      "id": 1,
      "name": "naz",
      "mobile_number": "9095680351"
    },
    "order": {
      "id": 24,
      "total_amount": "119.00",
      "delivery_charge": "0.00",
      "currency": "INR"
    },
    "address": {
      "id": 2,
      "address_line_1": "garden street",
      "address_line_2": "lane view",
      "city": null,
      "state": null,
      "pincode": "629160"
    },
    "items": [
      {
        "id": 7,
        "product_name": "Rasberry",
        "quantity": 1,
        "unit_price": "56.00",
        "line_total": "56.00"
      }
    ],
    "timeline": [
      {
        "old_status": null,
        "new_status": "assigned",
        "changed_at": "2026-08-28T05:52:16.000000Z"
      },
      {
        "old_status": "assigned",
        "new_status": "accepted",
        "changed_at": "2026-08-28T05:52:54.000000Z"
      }
    ]
  }
}
```

### Delivery status values

```text
assigned
accepted
picked_up
out_for_delivery
delivered
rejected
failed
cancelled
```

### Allowed status flow (order of transitions)

```text
assigned  ->  accepted  ->  picked_up  ->  out_for_delivery  ->  delivered
             \-> rejected / cancelled           \-> failed
```

`allowed_next_statuses` in the response tells the app which statuses can be
chosen next. You can also use the table above.

---

## 8. Update Delivery Status

```text
POST /deliveries/{deliveryId}/status
```

Protected. Body:

```json
{
  "status": "accepted"
}
```

Valid values depend on the current status:

| Current status     | Allowed next statuses                                      |
|--------------------|------------------------------------------------------------|
| `assigned`         | `accepted`, `rejected`, `cancelled`                        |
| `accepted`         | `picked_up`, `cancelled`, `failed`                         |
| `picked_up`        | `out_for_delivery`, `failed`                               |
| `out_for_delivery` | `delivered`, `failed`                                      |
| `delivered`        | (none — terminal)                                          |
| `rejected/cancelled/failed` | (none — terminal)                                   |

Success response:

```json
{
  "status": true,
  "message": "Delivery status updated successfully",
  "data": {
    "delivery": {
      "id": 12,
      "order_id": 24,
      "status": "accepted",
      "delivery_address": "garden street, lane view, 629160",
      "assigned_at": "2026-08-28T05:52:16.000000Z",
      "customer": {
        "id": 1,
        "name": "naz",
        "mobile_number": "9095680351"
      },
      "address": {
        "id": 2,
        "address_line_1": "garden street",
        "address_line_2": "lane view",
        "city": null,
        "state": null,
        "pincode": "629160"
      }
    },
    "allowed_next_statuses": ["picked_up", "cancelled", "failed"]
  }
}
```

Errors:

- Invalid transition → `422` with a message.
- Delivery not found / not yours → `404`.

---

## 9. Delivery History (completed)

```text
GET /history
```

Protected. Delivered deliveries for this delivery person.

Success response:

```json
{
  "status": true,
  "message": "Delivery history fetched successfully",
  "data": {
    "deliveries": [
      {
        "id": 11,
        "order_id": 23,
        "status": "delivered",
        "delivery_address": "garden street, lane view, 629160",
        "assigned_at": "2026-08-28T05:52:16.000000Z",
        "accepted_at": "2026-08-28T05:52:54.000000Z",
        "picked_up_at": "2026-08-28T05:53:00.000000Z",
        "out_for_delivery_at": "2026-08-28T05:54:20.000000Z",
        "delivered_at": "2026-08-28T05:54:26.000000Z",
        "customer": {
          "id": 1,
          "name": "naz",
          "mobile_number": "9095680351"
        },
        "order_total": "119.00",
        "item_count": 2
      }
    ]
  }
}
```

---

## 10. Update Availability

```text
PUT /availability
```

Protected. Body:

```json
{
  "availability_status": "available"
}
```

Allowed values: `available`, `offline`.

Success response:

```json
{
  "status": true,
  "message": "Availability updated successfully",
  "data": {
    "delivery_person": {
      "id": 1,
      "availability_status": "available"
    }
  }
}
```

---

## 11. Save Live Location (GPS)

```text
POST /deliveries/{deliveryId}/location
```

Protected. Body:

```json
{
  "latitude": 12.970000,
  "longitude": 77.590000
}
```

Success response:

```json
{
  "status": true,
  "message": "Location saved successfully",
  "data": {
    "location": {
      "id": 45,
      "delivery_id": 12,
      "latitude": "12.97000000",
      "longitude": "77.59000000",
      "recorded_at": "2026-08-29T09:00:00.000000Z"
    }
  }
}
```

If the delivery does not belong to this delivery person → `403`.

---

## Summary of endpoints

| Method | Endpoint                                  | Auth needed |
|--------|-------------------------------------------|-------------|
| POST   | `/otp/send`                               | No          |
| POST   | `/otp/verify`                             | No          |
| POST   | `/logout`                                 | Yes         |
| GET    | `/me`                                     | Yes         |
| GET    | `/dashboard`                              | Yes         |
| GET    | `/assigned-orders`                        | Yes         |
| GET    | `/deliveries/{deliveryId}`                | Yes         |
| POST   | `/deliveries/{deliveryId}/status`         | Yes         |
| GET    | `/history`                                | Yes         |
| PUT    | `/availability`                           | Yes         |
| POST   | `/deliveries/{deliveryId}/location`       | Yes         |

All protected endpoints require a delivery-person token and reject customer
tokens with `403 Forbidden`.
