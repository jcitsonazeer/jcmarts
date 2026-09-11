# JCMarts Cart API

Base URL:

```text
https://jcmarts.com/api/v1
```

## How cart identity works

Cart works for **both** guests and logged-in customers:

- **Guest:** send the `X-Device-ID` header (any unique device string).
  The cart is stored under `device_{uuid}`.
- **Customer:** send `Authorization: Bearer {token}`. The cart is stored under
  `customer_{id}`.

If neither a token nor `X-Device-ID` is provided, cart endpoints return
`401` with `"X-Device-ID header is required for guest cart operations."`

---

## Endpoint overview

| Method | Endpoint            | Auth needed | Description                    |
| ------ | ------------------- | ----------- | ------------------------------ |
| GET    | `/cart`             | No (see above) | List cart items            |
| POST   | `/cart`             | No (see above) | Add item to cart          |
| PUT    | `/cart/{cartId}`    | No (see above) | Update quantity           |
| DELETE | `/cart/{cartId}`    | No (see above) | Remove item               |
| GET    | `/cart/count`       | No (see above) | Cart item count           |
| POST   | `/cart/merge`       | Yes         | Merge guest cart into customer cart |

Headings below assume the session identity is present.

---

## 1. List cart items

```text
GET /cart
```

Headers:

- Guest: `X-Device-ID: {device_uuid}`
- Customer: `Authorization: Bearer {token}`

Success response:

```json
{
  "status": true,
  "message": "Cart items fetched successfully",
  "data": {
    "items": [
      {
        "id": 3,
        "quantity": 2,
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
          "uom": {
            "id": 2,
            "primary_uom": "kg",
            "secondary_uom": null
          }
        }
      }
    ],
    "item_count": 2,
    "sub_total": "180.00"
  }
}
```

`product_image` is a full URL. `sub_total` is formatted with 2 decimals.

---

## 2. Add item to cart

```text
POST /cart
```

Payload:

```json
{
  "product_id": 21,
  "rate_master_id": 45,
  "quantity": 2
}
```

`quantity` is optional (default `1`), min `1`, max `100`.

Success response (`201 Created`):

```json
{
  "status": true,
  "message": "Item added to cart successfully",
  "data": {
    "cart_item": {
      "id": 3,
      "quantity": 2,
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
    },
    "item_count": 2,
    "sub_total": "180.00"
  }
}
```

Error:

- `422` — Invalid product or rate variant (`"Invalid product or rate variant. Please check your selection."`).

---

## 3. Update quantity

```text
PUT /cart/{cartId}
```

Payload:

```json
{
  "quantity": 3
}
```

Success response:

```json
{
  "status": true,
  "message": "Cart quantity updated successfully",
  "data": {
    "item_count": 3,
    "sub_total": "270.00"
  }
}
```

Error:

- `404` — Cart item not found.

---

## 4. Remove item

```text
DELETE /cart/{cartId}
```

Success response:

```json
{
  "status": true,
  "message": "Item removed from cart successfully",
  "data": {
    "item_count": 2,
    "sub_total": "180.00"
  }
}
```

Error:

- `404` — Cart item not found.

---

## 5. Cart count

```text
GET /cart/count
```

Success response:

```json
{
  "status": true,
  "message": "Cart count fetched successfully",
  "data": {
    "item_count": 3
  }
}
```

---

## 6. Merge guest cart (after login)

```text
POST /cart/merge
```

Protected (customer token). Pass the guest `X-Device-ID` header so Laravel can
find the guest cart to transfer.

Headers:

```text
Authorization: Bearer {token}
X-Device-ID: {device_uuid}
```

Success response:

```json
{
  "status": true,
  "message": "Guest cart merged successfully",
  "data": {
    "items": [],
    "item_count": 0,
    "sub_total": "0.00"
  }
}
```

Duplicate items get their quantities added. If no `X-Device-ID` is sent, the
customer's current cart is returned with message
`"No guest cart to merge. Returning current cart."`

---

## Notes

- After login, the login/registration responses automatically merge the guest
  cart if `X-Device-ID` was sent (see `docs/auth.md`), so an explicit
  `/cart/merge` call is usually not needed.
- Checkout reads the customer's cart — see `docs/checkout.md`.
- `rate_master_id` comes from `GET /products/{id}/rates` (see `docs/product.md`).