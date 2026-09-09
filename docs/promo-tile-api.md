# JCMarts Promo Tile API (Flutter)

This document describes the Promo Tile API used by the mobile app (Flutter).
Promo Tiles are clickable banner-like tiles displayed on the app home screen.
Tapping a Promo Tile that has products attached should navigate the app to a
Products list page filtered by that Promo Tile.

Production URL:

```text
https://jcmarts.com/api/v1
```

Common headers:

```text
Accept: application/json
Content-Type: application/json
```

---

## Promo Tile → Products navigation flow (mobile)

1. The app calls `GET /promo-tiles` and receives the list of **active** Promo Tiles.
2. Each Promo Tile contains basic info for display (`promo_title`, `promo_image`)
   and a `products_count`.
3. The app renders the Promo Tile images in a carousel/grid on the home screen.
4. When the user taps a Promo Tile:
   - If `products_count > 0`, the app navigates to its Product List screen and
     calls `GET /products?sub_category_id=...` **plus** the promo tile filter.
   - Because the existing product list API filters by `sub_category_id` / `search`
     (see `GET /products`), the Promo Tile products are also included in each tile's
     `products` array — so the app can show the tile's products directly, or navigate
     to the product details using the standard product flow.
5. The `products` key of each Promo Tile directly lists the products attached to
   that tile (image URL, name, prices, rates, brand, etc.), so Flutter does **not**
   need any extra call just to render the tile's products.

> Note: Clicking a Promo Tile with **no** attached products (`products_count == 0`)
> should not navigate anywhere (same behaviour as the web frontend).

---

## Endpoint overview

| Method | Endpoint        | Auth needed | Description                     |
| ------ | --------------- | ----------- | ------------------------------- |
| GET    | `/promo-tiles`  | No          | Fetch active promo tiles + products |

---

## 1. List Promo Tiles

```text
GET /promo-tiles
```

No authentication required. No body / query parameters required.

Only **active** promo tiles are returned, ordered by `sort_order` (ascending)
then by newest `id` first — matching the web frontend display order.

### Success response

```json
{
  "status": true,
  "message": "Promo tiles fetched successfully",
  "data": [
    {
      "id": 1,
      "promo_title": "Fresh Fruits",
      "promo_image": "https://jcmarts.com/storage/promo_tile/fruits_1690000000_abcdef.png",
      "sort_order": 1,
      "is_active": 1,
      "created_by_id": 1,
      "created_date": "2026-09-07T12:00:00.000000Z",
      "updated_by_id": null,
      "updated_date": null,
      "products_count": 2,
      "products": [
        {
          "id": 12,
          "sub_category_id": 3,
          "brand_id": 2,
          "product_name": "Apple",
          "product_image": "https://jcmarts.com/storage/product/apple_1690000000_zzz.png",
          "is_active": 1,
          "brand": {
            "id": 2,
            "brand_name": "Local"
          },
          "rates": [
            {
              "id": 45,
              "product_id": 12,
              "uom_id": 2,
              "selling_price": "100.00",
              "offer_percentage": "10.00",
              "offer_price": "90.00",
              "final_price": "90.00",
              "soldout_status": 0,
              "stock_dependent": 1,
              "is_active": 1,
              "selected_display": 1,
              "uom": {
                "id": 2,
                "primary_uom": "kg",
                "secondary_uom": null
              }
            }
          ]
        }
      ]
    }
  ]
}
```

### Success — empty list (no active promo tiles)

```json
{
  "status": true,
  "message": "Promo tiles fetched successfully",
  "data": []
}
```

### Promo tile fields

| Field            | Type   | Description                                        |
| ---------------- | ------ | -------------------------------------------------- |
| `id`             | int    | Promo Tile unique ID                               |
| `promo_title`    | string | Title/label shown with the tile                    |
| `promo_image`    | string | Full URL of the promo image (or `null`)            |
| `sort_order`     | int    | Display order (ascending)                          |
| `is_active`      | int    | `1` = active, `0` = inactive (inactive are excluded) |
| `created_by_id`  | int    | Admin who created it                               |
| `created_date`   | string | Creation date                                      |
| `updated_by_id`  | int    | Admin who last updated it                          |
| `updated_date`   | string | Last update date                                   |
| `products_count` | int    | Number of products attached to the tile            |
| `products`       | array  | Attached active products (see below)               |

### Attached product fields (inside `products`)

| Field             | Type   | Description                                   |
| ----------------- | ------ | --------------------------------------------- |
| `id`              | int    | Product ID                                    |
| `sub_category_id` | int    | Sub-category the product belongs to           |
| `brand_id`        | int    | Brand ID (or `null`)                          |
| `product_name`    | string | Product name                                  |
| `product_image`   | string | Full URL of product image (or `null`)         |
| `is_active`       | int    | Always `1` (inactive products are excluded)   |
| `brand`           | object | Brand name, or `null`                         |
| `rates`           | array  | Active price/rate variants for the product    |

Rate fields (inside each `rates` item) are the same fields already returned by
`GET /products/{id}/rates`:

| Field              | Type   | Description                                   |
| ------------------ | ------ | --------------------------------------------- |
| `id`               | int    | Rate ID                                       |
| `product_id`       | int    | Product ID                                    |
| `uom_id`           | int    | Unit of measure ID                            |
| `selling_price`    | string | Selling price (original)                      |
| `offer_percentage` | string | Discount percentage                           |
| `offer_price`      | string | Offer price                                   |
| `final_price`      | string | Final price to charge the customer            |
| `soldout_status`   | int    | `1` if sold out                               |
| `stock_dependent`  | int    | `1` if stock dependent                        |
| `is_active`        | int    | `1` if active                                 |
| `selected_display` | int    | `1` = default/displayed rate                  |
| `uom`              | object | `{ id, primary_uom, secondary_uom }`          |

---

## Navigation: promo tile → product details

Once the app has a promo tile's `products` array, tapping any product should open
the standard product details screen using the existing flow:

```text
GET /products/{id}
```

And to show the full product list filtered by a promo tile, the app already has
the product IDs inside `products` — no dedicated promo-tile product list endpoint
is required. The web frontend navigates to the products page with the
`promo_tile` filter; the mobile app can simply render the tile's `products`
directly from this response.

---

## Related existing endpoints (used together with promo tiles)

| Endpoint                          | Purpose                              |
| --------------------------------- | ------------------------------------ |
| `GET /products/{id}`              | Full product details                 |
| `GET /products/{id}/rates`        | Price/rate variants for a product    |
| `GET /home`                       | Home page data (categories, offers)  |
| `GET /featured-products`          | Featured products                    |
| `GET /offers`                     | Offer products                       |

---

## Notes

- Promo tile images are served from `/storage/promo_tile/...`.
- Product images are served from `/storage/product/...`.
- Only active products with at least one active rate are included in `products`.
- This endpoint is **public** — no login token required.
