# JCMarts — Return Pickup & Delivery Workflow (Flutter Delivery App)

This document describes the **return pickup** workflow end to end and the
API contract the Flutter delivery app needs to support it.

A return pickup is a normal `deliveries` row with `return_id` set. Instead of
delivering an order to the customer, the delivery person travels to the
customer, collects the returned product(s), and brings them back to the
admin/warehouse hub.

**Return pickups do NOT use `out_for_delivery`.** After the driver collects
the product (`picked_up`), the only allowed next step is `delivered`, which is
displayed in the app as **"Delivered to Hub"** (backend status value remains
`delivered`). The normal delivery workflow is unchanged and is documented in
`docs/delivery.md`.

No new API endpoints were created. The return pickup reuses the existing
delivery-person endpoints. Response payloads are extended with `return_id`,
`return_status`, `is_return_pickup`, `status_label` and (detail view only)
`return_request` + `return_items`.

Production base URL:

```text
https://jcmarts.com/api/v1
```

| Method | Full Endpoint                                                          | Auth needed |
| ------ | ---------------------------------------------------------------------- | ----------- |
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
Authorization: Bearer {delivery_person_token}
```

A customer token calling these endpoints gets `403 Forbidden`.

---

## Important authentication rule

- Login returns a `token` + `role` = `delivery_person`.
- Every protected delivery endpoint requires a delivery-person token
  (`auth:sanctum` + `ensure.delivery`).
- A **customer token** calling these endpoints gets **403 Forbidden**.
- A delivery token can only see **its own** deliveries (other people's
  deliveries return `404` / `403`).

---

## 1. How to recognise a return pickup

Every delivery list/detail payload includes three pickup marker fields:

| Field              | Normal delivery      | Return pickup            |
| ------------------ | -------------------- | ------------------------ |
| `return_id`        | `null`               | `<returns.id>`           |
| `return_status`    | `null`               | e.g. `pickup_scheduled`  |
| `is_return_pickup` | `false`              | `true`                   |

`order_id` still points at the originating order. The app should render a
**"Return Pickup"** badge when `is_return_pickup` is `true`, and when the
driver sets `delivered` it must be labelled **"Delivered to Hub"** (see
section 4).

Pickup semantics:

- `assigned` — admin assigned this driver to collect the returned product.
- `accepted` — driver accepted the pickup task.
- `picked_up` — product collected from the customer (end of customer interaction).
- `delivered` — product handed over at the destination (admin/warehouse hub).
  Displayed as **"Delivered to Hub"**. Final status.

---

## 2. Workflow overview

```text
Customer (web or app)          Admin (web)                 Delivery Person (app)
---------------------          -------------------------   -------------------------
POST /orders/{id}/return  →    GET /admin/returns           GET /delivery/assigned-orders
reason + item quantities       GET /admin/returns/{id}       (pickup appears here with
return_requested               Approve → return_approved     is_return_pickup = true)
                               Reject  → return_rejected    POST /deliveries/{id}/status
                               "Schedule Pickup & Assign     accepted → picked_up →
                               Delivery Person"               delivered ("Delivered to Hub")
                               → pickup_scheduled             POST /deliveries/{id}/location
                               → delivery created with        (GPS, every N minutes;
                                 return_id set                admin tracks on
                                                             admin/deliveries/{id})
                               On 'delivered' →               On failure/reject →
                               return auto flips to           return reopens to
                               product_received               return_approved
                               Product received →             (driver freed; admin can
                               inspection → refund →          reassign)
                               return_closed
```

Return statuses (`returns.status`):
`return_requested → return_approved → pickup_scheduled → product_received → refund_initiated → return_closed`
Terminals: `return_rejected`, `inspection_failed`.

Delivery statuses (`deliveries.status`):
`not_assigned → assigned → accepted → picked_up → delivered` (pickup flow)
`not_assigned → assigned → accepted → picked_up → out_for_delivery → delivered` (normal flow)
Terminals: `delivered`, `rejected`, `cancelled`, `failed`.

---

## 3. Return pickup status flow

This is the **complete** transition table for a return pickup. `out_for_delivery`
is intentionally **not** part of the pickup flow.

| Current status | Allowed next status(es)                |
| -------------- | -------------------------------------- |
| `assigned`     | `accepted`, `rejected`, `cancelled`    |
| `accepted`     | `picked_up`, `cancelled`, `failed`     |
| `picked_up`    | `delivered`                            |
| `delivered`    | (none — final status)                  |
| `rejected`     | (none — final status)                  |
| `cancelled`    | (none — final status)                  |
| `failed`       | (none — final status)                  |

Notes for the app:

- After `picked_up`, the only action available is **"Delivered to Hub"**
  (status `delivered`). No "Out for Delivery" button may be shown.
- The backend enforces this table server-side. Any other transition returns
  `422` with an error message (see section 5.4).
- `allowed_next_statuses` in the API responses always reflects this table for
  pickups, and the normal table for normal deliveries.

Display labels:

| `status` value     | Display label (pickup)               |
| ------------------ | ------------------------------------ |
| `assigned`         | `Pickup Assigned`                    |
| `accepted`         | `Pickup Accepted by Delivery Person` |
| `picked_up`        | `Picked Up`                          |
| `delivered`        | **`Delivered to Hub`**               |
| `rejected`         | `Rejected`                           |
| `cancelled`        | `Cancelled`                          |
| `failed`           | `Failed`                             |

The API also returns a ready-to-render `status_label` on delivery payloads
(see section 5). For a normal delivery, `delivered` renders as `Delivered`.

---

## 4. Pickup semantics & automatic side effects

### 4.1 `picked_up` → product collected

Set when the driver collects the returned product(s) from the customer.
Records `picked_up_at`. After this point the customer interaction is over.

### 4.2 `delivered` → Delivered to Hub

Set when the driver hands the returned product(s) over at the admin/warehouse
hub. Records `delivered_at`. On this transition, **automatically**:

1. The linked return flips from `pickup_scheduled` to **`product_received`**
   (with a `return_status_histories` entry, `changed_by_type = delivery_person`).
2. The delivery person is set back to `availability_status = available`
   (once they have no other active delivery).
3. The order timeline is **not** touched (a pickup is not a delivery of the
   order).

### 4.3 `rejected` / `cancelled` / `failed` → return reopens

Set when the pickup cannot be completed:

- `rejected` — driver rejected the pickup task (from `assigned`).
- `cancelled` — pickup cancelled (from `assigned` or `accepted`).
- `failed` — pickup attempt failed (from `accepted`).

On any of these, **automatically**:

1. The linked return **reopens from `pickup_scheduled` to `return_approved`**
   (`pickup_scheduled_at` cleared, history entry recorded with the acting
   actor type).
2. The delivery person is set back to `availability_status = available`
   (once they have no other active delivery).
3. The admin can assign another delivery person from the return process page
   (`GET /admin/returns/{id}/process`). Re-assigning resets the same
   `deliveries` row (old timestamps cleared, history preserved).

### 4.4 What does NOT happen for a pickup

- The order receives no `order_delivered` status and its `delivered_at` is
  never set by a pickup (though the existing convention still mirrors the
  return-status transitions, e.g. `product_received`, into `order_status`).
- `out_for_delivery_at` is never set for a pickup.

---

## 5. API reference (delivery person)

All endpoints require `auth:sanctum` + `ensure.delivery` (delivery-person
token). A customer token gets `403 Forbidden`.

### 5.1 Dashboard / Summary

```text
GET /delivery/dashboard
```

Returns basic stats + the current active delivery/pickup (if any).

Success response `200`:

```json
{
  "status": true,
  "message": "Delivery dashboard fetched successfully",
  "data": {
    "delivery_person": { "id": 3, "name": "Ravi", "vehicle_type": "bike", "vehicle_number": "KA01AB1234", "availability_status": "busy" },
    "stats": { "active_deliveries": 1, "completed_deliveries": 12 },
    "current_active_delivery": {
      "id": 13,
      "order_id": 42,
      "return_id": 7,
      "return_status": "pickup_scheduled",
      "is_return_pickup": true,
      "status": "assigned",
      "status_label": "Pickup Assigned",
      "delivery_address": "12, MG Road, HSR Layout, 560102",
      "assigned_at": "2026-11-04T11:00:00.000000Z",
      "customer": { "id": 9, "name": "Priya", "mobile_number": "9876543210" },
      "address": { "id": 5, "address_line_1": "12, MG Road", "address_line_2": null, "city": null, "state": null, "pincode": "560102" }
    }
  }
}
```

`current_active_delivery` is `null` when there is no active delivery/pickup.
For a normal delivery: `"return_id": null, "return_status": null, "is_return_pickup": false`.

---

### 5.2 Assigned Orders (list)

```text
GET /delivery/assigned-orders
```

All deliveries **and return pickups** currently assigned to this delivery
person (statuses: `assigned`, `accepted`, `picked_up`, `out_for_delivery`).

Success response `200`:

```json
{
  "status": true,
  "message": "Assigned orders fetched successfully",
  "data": {
    "deliveries": [
      {
        "id": 13,
        "order_id": 42,
        "return_id": 7,
        "return_status": "pickup_scheduled",
        "is_return_pickup": true,
        "status": "assigned",
        "status_label": "Pickup Assigned",
        "delivery_address": "12, MG Road, HSR Layout, 560102",
        "assigned_at": "2026-11-04T11:00:00.000000Z",
        "accepted_at": null,
        "picked_up_at": null,
        "out_for_delivery_at": null,
        "delivered_at": null,
        "customer": { "id": 9, "name": "Priya", "mobile_number": "9876543210" },
        "order_total": "2499.00",
        "item_count": 3
      }
    ]
  }
}
```

The app should render a **"Return Pickup"** badge when `is_return_pickup` is
`true`. For a normal delivery: `"return_id": null, "return_status": null,
"is_return_pickup": false`.

---

### 5.3 Delivery Detail (single)

```text
GET /delivery/deliveries/{deliveryId}
```

Full detail of one delivery/pickup that belongs to this driver. If the
delivery does not exist or does not belong to this person → `404`.

Success response `200`:

```json
{
  "status": true,
  "message": "Delivery fetched successfully",
  "data": {
    "id": 13,
    "order_id": 42,
    "return_id": 7,
    "return_status": "pickup_scheduled",
    "is_return_pickup": true,
    "status": "accepted",
    "status_label": "Pickup Accepted by Delivery Person",
    "delivery_address": "12, MG Road, HSR Layout, 560102",
    "assigned_at": "2026-11-04T11:00:00.000000Z",
    "accepted_at": "2026-11-04T11:20:00.000000Z",
    "picked_up_at": null,
    "out_for_delivery_at": null,
    "delivered_at": null,
    "allowed_next_statuses": ["picked_up", "cancelled", "failed"],
    "customer": { "id": 9, "name": "Priya", "mobile_number": "9876543210" },
    "order": { "id": 42, "total_amount": "2499.00", "delivery_charge": "50.00", "currency": "INR" },
    "address": { "id": 5, "address_line_1": "12, MG Road", "address_line_2": null, "city": null, "state": null, "pincode": "560102" },
    "items": [
      { "id": 12, "product_name": "Wall Clock", "quantity": 1, "unit_price": "1299.00", "line_total": "1299.00" },
      { "id": 15, "product_name": "Desk Lamp", "quantity": 2, "unit_price": "150.00", "line_total": "300.00" }
    ],
    "return_request": {
      "id": 7,
      "reason": "Damaged Product",
      "customer_note": "Box was crushed on arrival",
      "status": "pickup_scheduled",
      "refund_amount": "1599.00"
    },
    "return_items": [
      { "id": 20, "product_id": 301, "product_name": "Wall Clock", "quantity": 1, "unit_price": "1299.00", "line_total": "1299.00" }
    ],
    "timeline": [
      { "old_status": null, "new_status": "assigned", "changed_at": "2026-11-04T11:00:00.000000Z" },
      { "old_status": "assigned", "new_status": "accepted", "changed_at": "2026-11-04T11:20:00.000000Z" }
    ]
  }
}
```

For a normal delivery: `"return_request": null, "return_items": []`.

The app should collect **only** the items in `return_items` (not `items`,
which is the full original order).

### Delivery status values

```text
assigned
accepted
picked_up
out_for_delivery   (normal deliveries only — never for a return pickup)
delivered
rejected
failed
cancelled
```

### Allowed status flow (return pickup)

```text
assigned  ->  accepted  ->  picked_up  ->  delivered ("Delivered to Hub")
             \-> rejected / cancelled        \-> cancelled / failed
```

`allowed_next_statuses` in the response tells the app which statuses can be
chosen next, and already accounts for whether this is a pickup or a normal
delivery. You can also use the table in section 3.

---

### 5.4 Update Delivery Status

```text
POST /delivery/deliveries/{deliveryId}/status
```

Body:

```json
{
  "status": "picked_up"
}
```

Valid values depend on the current status **and** whether it is a pickup.

For a **return pickup** (`is_return_pickup = true`):

| Current status     | Allowed next statuses             |
| ------------------ | --------------------------------- |
| `assigned`         | `accepted`, `rejected`, `cancelled` |
| `accepted`         | `picked_up`, `cancelled`, `failed`  |
| `picked_up`        | `delivered`                        |
| `delivered`        | (none — terminal)                  |
| `rejected/cancelled/failed` | (none — terminal)         |

For a **normal delivery** (`is_return_pickup = false`, unchanged):

| Current status     | Allowed next statuses                                      |
| ------------------ | ---------------------------------------------------------- |
| `assigned`         | `accepted`, `rejected`, `cancelled`                        |
| `accepted`         | `picked_up`, `cancelled`, `failed`                         |
| `picked_up`        | `out_for_delivery`, `failed`                               |
| `out_for_delivery` | `delivered`, `failed`                                      |
| `delivered`        | (none — terminal)                                          |
| `rejected/cancelled/failed` | (none — terminal)                              |

> Sending `out_for_delivery` for a return pickup returns `422`.
> Sending `out_for_delivery` for a normal delivery is still supported.

**Button label in the app:** when `is_return_pickup = true` and
`allowed_next_statuses` contains `delivered`, render the action button as
**"Delivered to Hub"** (not "Delivered", not "Out for Delivery"). The backend
status value to send is `delivered`.

Success response `200` (after the driver accepts):

```json
{
  "status": true,
  "message": "Delivery status updated successfully",
  "data": {
    "delivery": {
      "id": 13,
      "order_id": 42,
      "return_id": 7,
      "return_status": "pickup_scheduled",
      "is_return_pickup": true,
      "status": "accepted",
      "status_label": "Pickup Accepted by Delivery Person",
      "delivery_address": "12, MG Road, HSR Layout, 560102",
      "assigned_at": "2026-11-04T11:00:00.000000Z",
      "customer": { "id": 9, "name": "Priya", "mobile_number": "9876543210" },
      "address": { "id": 5, "address_line_1": "12, MG Road", "address_line_2": null, "city": null, "state": null, "pincode": "560102" }
    },
    "allowed_next_statuses": ["picked_up", "cancelled", "failed"]
  }
}
```

Success response `200` (after the driver marks it `delivered` — note
`return_status` has flipped and the driver is freed):

```json
{
  "status": true,
  "message": "Delivery status updated successfully",
  "data": {
    "delivery": {
      "id": 13,
      "order_id": 42,
      "return_id": 7,
      "return_status": "product_received",
      "is_return_pickup": true,
      "status": "delivered",
      "status_label": "Delivered to Hub",
      "delivery_address": "12, MG Road, HSR Layout, 560102",
      "assigned_at": "2026-11-04T11:00:00.000000Z",
      "customer": { "id": 9, "name": "Priya", "mobile_number": "9876543210" },
      "address": { "id": 5, "address_line_1": "12, MG Road", "address_line_2": null, "city": null, "state": null, "pincode": "560102" }
    },
    "allowed_next_statuses": []
  }
}
```

Errors:

- Invalid transition → `422` with a message, e.g.:

```json
{
  "status": false,
  "message": "Cannot change delivery status from \"picked_up\" to \"out_for_delivery\".",
  "data": null
}
```

- Delivery not found / not yours → `404`:

```json
{
  "status": false,
  "message": "Delivery not found",
  "data": null
}
```

---

### 5.5 Delivery History (completed)

```text
GET /delivery/history
```

Delivered deliveries **and completed pickups** for this driver, same list
shape as `assigned-orders` (with `is_return_pickup` / `status_label`), newest
first. `is_return_pickup: true` entries were "Delivered to Hub".

Success response `200`:

```json
{
  "status": true,
  "message": "Delivery history fetched successfully",
  "data": {
    "deliveries": [
      {
        "id": 13,
        "order_id": 42,
        "return_id": 7,
        "return_status": "product_received",
        "is_return_pickup": true,
        "status": "delivered",
        "status_label": "Delivered to Hub",
        "delivery_address": "12, MG Road, HSR Layout, 560102",
        "assigned_at": "2026-11-04T11:00:00.000000Z",
        "accepted_at": "2026-11-04T11:20:00.000000Z",
        "picked_up_at": "2026-11-04T12:00:00.000000Z",
        "out_for_delivery_at": null,
        "delivered_at": "2026-11-04T13:30:00.000000Z",
        "customer": { "id": 9, "name": "Priya", "mobile_number": "9876543210" },
        "order_total": "2499.00",
        "item_count": 3
      }
    ]
  }
}
```

---

### 5.6 Save Live Location (GPS)

```text
POST /delivery/deliveries/{deliveryId}/location
```

Body:

```json
{
  "latitude": 12.9123456,
  "longitude": 77.6443210
}
```

Success response `200`:

```json
{
  "status": true,
  "message": "Location saved successfully",
  "data": {
    "location": {
      "id": 501,
      "delivery_id": 13,
      "latitude": 12.9123456,
      "longitude": 77.6443210,
      "recorded_at": "2026-11-04T12:05:00.000000Z"
    }
  }
}
```

Errors:

- Delivery not found / not yours → `403`:

```json
{
  "status": false,
  "message": "Delivery not found.",
  "data": null
}
```

- Invalid coordinates → `422` (validation error).

Points are stored in `delivery_locations` and shown on the admin delivery
show page — this is the tracking trail until the returned product reaches the
hub.

---

### 5.7 Update Availability

```text
PUT /delivery/availability
```

Body:

```json
{
  "availability_status": "available"
}
```

Allowed values: `available`, `offline`. Drivers cannot set `busy` manually; it
is managed automatically by assignment and by completion/failure of a
delivery or pickup (see section 4).

Success response `200`:

```json
{
  "status": true,
  "message": "Availability updated successfully",
  "data": {
    "delivery_person": { "id": 3, "availability_status": "available" }
  }
}
```

---

## 6. Customer side (for completeness)

#### `GET /orders/returns/reasons`

Lists selectable return reasons. Auth: `auth:sanctum` + `ensure.customer`.

Response `200`:

```json
{
  "status": true,
  "message": "Return reasons fetched successfully",
  "data": {
    "reasons": ["Wrong Item", "Damaged Product", "Defective Product", "Missing Parts", "Not as Described", "Ordered by Mistake", "Other"]
  }
}
```

#### `POST /orders/{orderId}/return`

Create a return request for a delivered, paid, in-window (7 days) order.
Auth: `auth:sanctum` + `ensure.customer`.

Request:

```json
{
  "reason": "Damaged Product",
  "customer_note": "Box was crushed on arrival",
  "items": {
    "12": { "quantity": 1 },
    "15": { "quantity": 2 }
  }
}
```

`items` keys are `order_item_id`s; quantities cannot exceed what is still
returnable. Response `201`:

```json
{
  "status": true,
  "message": "Return request created successfully",
  "data": {
    "id": 7,
    "order_id": 42,
    "reason": "Damaged Product",
    "status": "return_requested",
    "refund_amount": "1599.00",
    "requested_at": "2026-11-04T10:15:00.000000Z"
  }
}
```

Errors: `422` (validation / nothing returnable / window expired / not paid /
already returned), `404` (order not found).

#### Order detail also exposes return state

`GET /orders/{id}` includes: `can_customer_return`, `return_allowed_until`,
`returnable_items[]`, `return_requests[]`, `refunds[]`.

---

## 7. Admin web flow

| Step | Screen | Action |
| ---- | ------ | ------ |
| 1 | `GET /admin/returns` | List requests, filter by status |
| 2 | `GET /admin/returns/{id}` | Details, items, refund info, GPS track link, status history |
| 3 | `GET /admin/returns/{id}/process` | Approve / Reject |
| 4 | same page (after approve) | "Schedule Pickup & Assign Delivery Person" — dropdown lists only active drivers with `availability_status = available` and no active delivery/pickup |
| 5 | `GET /admin/deliveries/{deliveryId}` | Live GPS trail + timeline until the pickup is `delivered` ("Delivered to Hub") |
| 6 | back on process page | **Mark Product Received** (auto on driver `delivered`) → Inspection Failed or Inspection Passed → Razorpay refund → `return_closed` |

Assignment rules (enforced server-side in a transaction with row locks):

- Return must be `return_approved`.
- Driver must be `active`, `availability_status = 'available'`, and have **no**
  delivery/pickup in `assigned|accepted|picked_up|out_for_delivery`.
- An already-assigned or completed pickup cannot be reassigned; after a
  failed/rejected/cancelled pickup the return reopens to `return_approved` and
  a new assignment creates/resets the same delivery row (old timestamps
  cleared, history preserved).

---

## 8. Data model

Existing tables, columns and records are unchanged. The pickup reuses
`deliveries`, `delivery_status_histories`, `delivery_locations`, `returns`,
`return_items`, `return_status_histories` and `order_status` (order timeline;
return-status transitions are mirrored into it, pickup delivery statuses are not).

Relevant columns on `deliveries`:

| Column | Purpose |
| ------ | ------- |
| `order_id` | Originating order |
| `return_id` | Set (non-null) ⇒ this row is a return pickup; `nullOnDelete` |
| `status` | Pickup status (see sections 3–4) |
| `assigned_at` / `accepted_at` / `picked_up_at` / `delivered_at` | Pickup timestamps. `out_for_delivery_at` is never set for a pickup |
| `delivery_address` | Customer's address (pickup location) |

`return_status_histories` records every return status change with
`changed_by_type` = `admin` / `delivery_person` / `customer`.

No database tables or columns were added or modified for the pickup flow.

---

## 9. Timestamps recorded

`returns`: `requested_at`, `approved_at` / `rejected_at`, `pickup_scheduled_at`,
`received_at`, `inspected_at`, `closed_at`.

`deliveries` (pickup): `assigned_at`, `accepted_at`, `picked_up_at`,
`delivered_at` (= "reached hub", a.k.a. "Delivered to Hub").
`out_for_delivery_at` stays `null` for pickups.

Full history: `return_status_histories` (return), `delivery_status_histories`
(pickup), `delivery_locations` (GPS path), `order_status` (order timeline —
mirrors return-status transitions only).

---

## 10. Implementation notes (backend)

- The return pickup uses a dedicated transition map
  (`DeliveryStatusService::PICKUP_TRANSITIONS`), selected automatically when
  the delivery row has `return_id` set. The normal delivery transition map
  (`STATUS_TRANSITIONS`) is unchanged.
- `getAllowedNextStatuses($status, $isReturnPickup)` and
  `validateStatusTransition($status, $newStatus, $isReturnPickup)` take the
  pickup flag; API and admin callers pass `(bool) $delivery->return_id`.
- `formatStatusLabel($status, true)` renders `delivered` as **"Delivered to
  Hub"** for pickups (and `assigned`/`accepted` as `Pickup Assigned` /
  `Pickup Accepted by Delivery Person`). API payloads include `status_label`.
- On `delivered`: `ReturnService::markProductReceivedForDelivery()` flips the
  return to `product_received`, and the driver is freed.
- On `rejected` / `cancelled` / `failed`:
  `ReturnService::reopenReturnAfterPickup()` reopens the return to
  `return_approved`, and the driver is freed.
- No database migration was required; no columns or tables changed.

---

## 11. Testing instructions

### Manual end-to-end (admin UI + API/Postman)

1. Log in as a customer, buy & get an order marked delivered (`order_delivered`), ensure a paid payment exists.
2. `GET /api/v1/orders/returns/reasons` → 200; `POST /api/v1/orders/{id}/return` with items → 201, return #N in `return_requested`.
3. Admin → `/admin/returns` → open the request → **Approve** → status `return_approved`; check `return_status_histories` has `return_requested → return_approved`.
4. On the process page pick an **available** driver → **Schedule Pickup & Assign Delivery Person** → return flips to `pickup_scheduled`; a `deliveries` row with `return_id = N`, status `assigned` exists; driver becomes `busy`; `delivery_status_histories` has `null → assigned`.
5. Try assigning the same driver again (expect *"not available / already assigned"* error) and try a driver with an active delivery (expect the same).
6. As the driver (token), `GET /api/v1/delivery/assigned-orders` → pickup listed with `is_return_pickup: true`; `GET /api/v1/delivery/deliveries/{id}` → `return_request` + `return_items` present, `allowed_next_statuses` = `["accepted","rejected","cancelled"]`.
7. `POST .../status` with `accepted`, then `picked_up`, posting GPS pings via `POST .../location` in between. After `picked_up`, `allowed_next_statuses` must be exactly `["delivered"]`.
8. Try `POST .../status {"status":"out_for_delivery"}` on the pickup → expect **422** ("Cannot change delivery status ...").
9. Admin → returns details page → **Track Delivery (GPS)** → `/admin/deliveries/{id}` shows the GPS table and a timeline ending at **Delivered to Hub**; the return page shows the status-history table.
10. Driver sets `delivered` (**"Delivered to Hub"**). Return auto-flips to `product_received` (`return_status_histories`: `pickup_scheduled → product_received`, `changed_by_type = delivery_person`); driver is freed to `available`; `allowed_next_statuses` = `[]`.
11. Admin → process page → **Inspection Passed** (+ sellable stock optional) → Razorpay refund → return becomes `refund_initiated` or `return_closed`; payments/refunds updated.
12. Failure path: assign a driver, driver rejects (`POST .../status {"status":"rejected"}`) → return reopens to `return_approved`; driver is freed; admin can assign a different driver; pickup timestamps are cleared and `delivery_status_histories` keeps the full sequence. Repeat with `cancelled` and `failed`.
13. Normal-delivery sanity check: an order (non-return) delivery still allows `picked_up → out_for_delivery → delivered` and shows `Delivered` (not "Delivered to Hub").

### Automated

```bash
php artisan test
```

Existing suites do not touch the return flow; they must stay green.

---

## Summary of endpoints

| Method | Endpoint                                  | Auth needed |
|--------|-------------------------------------------|-------------|
| GET    | `/orders/returns/reasons`                 | Customer    |
| POST   | `/orders/{orderId}/return`                | Customer    |
| GET    | `/delivery/dashboard`                     | Delivery    |
| GET    | `/delivery/assigned-orders`               | Delivery    |
| GET    | `/delivery/deliveries/{deliveryId}`       | Delivery    |
| POST   | `/delivery/deliveries/{deliveryId}/status`| Delivery    |
| GET    | `/delivery/history`                       | Delivery    |
| PUT    | `/delivery/availability`                  | Delivery    |
| POST   | `/delivery/deliveries/{deliveryId}/location` | Delivery |

All delivery endpoints require a delivery-person token and reject customer
tokens with `403 Forbidden`.
