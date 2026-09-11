# JCMarts Wishlist API

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

| Method | Endpoint                     | Auth needed | Description                        |
| ------ | ---------------------------- | ----------- | ---------------------------------- |
| GET    | `/wishlist`                  | Yes         | List wishlist items                |
| POST   | `/wishlist`                  | Yes         | Add a product to the wishlist      |
| DELETE | `/wishlist/{productId}`      | Yes         | Remove a product from the wishlist |
| POST   | `/wishlist/toggle`           | Yes         | Add or remove (toggle) a product   |
| GET    | `/wishlist/check/{productId}`| Yes         | Check if a product is wishlisted   |
| GET    | `/wishlist/count`            | Yes         | Wishlist item count                |

---

## 1. List wishlist

```text
GET /wishlist
```

Success response:

```json
{
  "status": true,
  "message": "Wishlist items fetched successfully",
  "data": {
    "items": [
      {
        "id": 2,
        "product_id": 21,
        "product": {
          "id": 21,
          "product_name": "Rice",
          "product_image": "https://jcmarts.com/storage/product/rice.png"
        }
      }
    ],
    "item_count": 1
  }
}
```

`product_image` is a full URL.

---

## 2. Add to wishlist

```text
POST /wishlist
```

Payload:

```json
{
  "product_id": 21
}
```

Success response (`201 Created`):

```json
{
  "status": true,
  "message": "Product added to wishlist successfully",
  "data": {
    "wishlist_item": {
      "id": 2,
      "product_id": 21
    },
    "item_count": 1
  }
}
```

Errors:

- `422` — `"Product not found or is inactive."`

---

## 3. Remove from wishlist

```text
DELETE /wishlist/{productId}
```

Example: `DELETE /wishlist/21`.

Success response:

```json
{
  "status": true,
  "message": "Product removed from wishlist successfully",
  "data": {
    "item_count": 0
  }
}
```

---

## 4. Toggle wishlist

```text
POST /wishlist/toggle
```

Adds the product if it is not wishlisted, or removes it if it is.

Payload:

```json
{
  "product_id": 21
}
```

Success response:

```json
{
  "status": true,
  "message": "Product added to wishlist",
  "data": {
    "is_wishlisted": true,
    "item_count": 1
  }
}
```

`is_wishlisted` is `true` if the product was added, `false` if it was removed.

---

## 5. Check wishlist status

```text
GET /wishlist/check/{productId}
```

Example: `GET /wishlist/check/21`.

Success response:

```json
{
  "status": true,
  "message": "Wishlist status checked successfully",
  "data": {
    "is_wishlisted": true
  }
}
```

---

## 6. Wishlist count

```text
GET /wishlist/count
```

Success response:

```json
{
  "status": true,
  "message": "Wishlist count fetched successfully",
  "data": {
    "item_count": 1
  }
}
```

---

## Notes

- Use `product_id` from `docs/product.md`.
- Related module: `docs/cart.md`.