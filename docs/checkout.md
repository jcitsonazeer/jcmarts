# JCMarts Checkout & Payment API

Base URL:

```text
https://jcmarts.com/api/v1
```

All endpoints in this module require a **customer** token:

```text
Authorization: Bearer {token}
```

Payment is processed through **Razorpay**. Flutter opens the Razorpay SDK with
the returned `key`, `amount`, `currency`, and `razorpay_order_id`, then sends
the payment details back to the verify endpoint.

---

## Endpoint overview

| Method | Endpoint                 | Auth needed | Description                       |
| ------ | ------------------------ | ----------- | --------------------------------- |
| GET    | `/checkout`              | Yes         | Checkout summary data            |
| POST   | `/payment/create-order`  | Yes         | Create pending order + Razorpay order |
| POST   | `/payment/verify`        | Yes         | Verify payment and confirm order  |
| POST   | `/payment/release`       | Yes         | Release reserved stock (abandoned payment) |

---

## 1. Checkout data

```text
GET /checkout
```

Returns the customer's cart items, saved addresses, and an order summary.

Success response:

```json
{
  "status": true,
  "message": "Checkout data fetched successfully",
  "data": {
    "cart_items": [
      {
        "id": 3,
        "quantity": 2,
        "unit_price": "90.00",
        "product": {
          "id": 21,
          "product_name": "Rice",
          "product_image": "https://jcmarts.com/storage/product/rice.png"
        },
        "rate": {
          "id": 45,
          "uom_id": 2,
          "final_price": "90.00",
          "selling_price": "100.00",
          "uom": { "id": 2, "primary_uom": "kg", "secondary_uom": null }
        }
      }
    ],
    "addresses": [
      {
        "id": 2,
        "address_line_1": "garden street",
        "address_line_2": "lane view",
        "location": "Main Road",
        "pincode": "629160",
        "landmark": "Near temple"
      }
    ],
    "summary": {
      "sub_total": 180.0,
      "delivery_charge": 0.0,
      "packing_charge": 0.0,
      "other_charge": 0.0,
      "total": 180.0
    }
  }
}
```

Error:

- `422` — `"Your cart is empty"`

---

## 2. Create payment order

```text
POST /payment/create-order
```

Creates a pending order from the cart (reserves stock) and starts a Razorpay
order.

Payload:

```json
{
  "selected_address_id": 2
}
```

Success response:

```json
{
  "status": true,
  "message": "Payment order created successfully",
  "data": {
    "pending_order_id": 24,
    "razorpay_order_id": "order_Ozy2",
    "amount": 18000,
    "currency": "INR",
    "key": "rzp_test_xxx"
  }
}
```

Field notes:

| Field               | Description                                            |
| ------------------- | ------------------------------------------------------ |
| `pending_order_id`  | JCMarts pending order id — send back on verify/release |
| `razorpay_order_id` | Razorpay order id for the SDK and verify               |
| `amount`            | Amount in **paise** (e.g. `18000` = Rs 180.00)         |
| `currency`          | Currency code (INR)                                    |
| `key`               | Razorpay key ID for the SDK                            |

Errors:

- `422` — `"Please select a valid delivery address"`
- `422` — `"Your cart is empty"`
- `422` — `"Minimum order value ..."` (below the delivery minimum)
- `422` — `"Minimum payable amount is Rs 1"`
- `422` — `"Payment could not be started. Please try again"`

---

## 3. Verify payment

```text
POST /payment/verify
```

Called after the Razorpay SDK finishes. Verifies the signature and finalizes
the order.

Payload:

```json
{
  "pending_order_id": 24,
  "razorpay_order_id": "order_Ozy2",
  "razorpay_payment_id": "pay_Ozy3",
  "razorpay_signature": "signature_here"
}
```

Success response:

```json
{
  "status": true,
  "message": "Payment successful. Order placed.",
  "data": null
}
```

Error:

- `422` — `"Payment verification failed"` (stock reservation is released
  automatically in this case).

---

## 4. Release pending payment

```text
POST /payment/release
```

Call this if the user abandons the Razorpay flow (payment sheet closed without
paying) so the reserved stock is released.

Payload:

```json
{
  "pending_order_id": 24,
  "razorpay_order_id": "order_Ozy2"
}
```

Success response:

```json
{
  "status": true,
  "message": "Reserved stock released",
  "data": null
}
```

---

## Notes

- After successful payment the order is final — see it under
  `docs/order.md`.
- Addresses come from `docs/profile.md`.
- Cart totals come from the customer cart (see `docs/cart.md`).