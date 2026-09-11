# JCMarts Promo Tile API (Flutter)

This document describes the Promo Tile API used by the mobile app (Flutter).
Promo Tiles are clickable banner-like tiles displayed on the app home screen.
Tapping a Promo Tile that has products attached should navigate the app to a
Products list page filtered by that Promo Tile — exactly like the web frontend.

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
2. Each Promo Tile contains only the display fields (`promo_title`, `promo_image`,
   `sort_order`, `is_active`) plus a `products_count`.
3. The app renders the Promo Tile images in a carousel/grid on the home screen.
4. When the user taps a Promo Tile:
   - If `products_count == 0`, the app does **not** navigate anywhere
     (same behaviour as the web frontend).
   - If `products_count > 0`, the app navigates to its normal Product List
     screen and calls `GET /products?promo_tile={tileId}`.
5. The product list response has the **same structure** as the normal product
   list — no special promo-tile product endpoint is needed.

### Final API structure

```text
GET /api/v1/promo-tiles
        ↓
Home screen Promo Tiles
        ↓
User taps Tile ID = 19
        ↓
GET /api/v1/products?promo_tile=19
        ↓
Normal Flutter Product List
        ↓
User taps product
        ↓
GET /api/v1/products/{id}
        ↓
Product Details
```

> Note: The `/promo-tiles` response intentionally does **not** embed the full
> product list. Products are fetched on demand via `GET /products?promo_tile={id}`,
> so the home screen never downloads hundreds of products at once.

---

## Endpoint overview

| Method | Endpoint                 | Auth needed | Description                          |
| ------ | ------------------------ | ----------- | ------------------------------------ |
| GET    | `/promo-tiles`           | No          | Fetch active promo tiles (no products) |
| GET    | `/products?promo_tile=`  | No          | Fetch products attached to a promo tile |

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
      "id": 19,
      "promo_title": "Fresh Fruits",
      "promo_image": "https://jcmarts.com/storage/promo_tile/fruits_1690000000_abcdef.png",
      "sort_order": 1,
      "is_active": true,
      "products_count": 5
    },
    {
      "id": 18,
      "promo_title": "Daily Essentials",
      "promo_image": "https://jcmarts.com/storage/promo_tile/essentials_1690000000_zzz.png",
      "sort_order": 2,
      "is_active": true,
      "products_count": 0
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

| Field            | Type   | Description                                          |
| ---------------- | ------ | ---------------------------------------------------- |
| `id`             | int    | Promo Tile unique ID (used for the `promo_tile` filter) |
| `promo_title`    | string | Title/label shown with the tile                      |
| `promo_image`    | string | Full URL of the promo image (or `null`)              |
| `sort_order`     | int    | Display order (ascending)                            |
| `is_active`      | bool  | Always `true` (inactive tiles are excluded)          |
| `products_count` | int    | Number of products attached to the tile              |

---

## 2. Fetch products for a Promo Tile

```text
GET /products?promo_tile={promo_tile_id}
```

No authentication required.

The existing product list endpoint is reused. When `promo_tile` is supplied it
returns only **active** products attached to that Promo Tile (products must also
have at least one active rate, same rule as the normal product list). All the
usual product filters can be combined with it.

### Query parameters

| Parameter        | Type | Description                                               |
| ---------------- | ---- | --------------------------------------------------------- |
| `promo_tile`     | int  | Promo Tile ID to filter products by (e.g. `19`)           |
| `sub_category_id`| int  | (optional) Filter by sub-category                         |
| `search`         | str  | (optional) Search by product name                         |
| `per_page`       | int  | (optional) Page size, default `10`, max `100`             |
| `page`           | int  | (optional) Page number                                    |

### Example request

```text
GET /products?promo_tile=19&per_page=10&page=1
```

### Success response

The response structure is **identical** to the normal `GET /products` response.
Only the list content is filtered by the promo tile.

```json
{
  "status": true,
  "message": "Product list fetched successfully",
  "filters": {
    "sub_category_id": null,
    "promo_tile": "19",
    "search": null,
    "per_page": 10
  },
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 21,
        "sub_category_id": 3,
        "brand_id": 2,
        "product_name": "Apple",
        "product_image": "https://jcmarts.com/storage/product/apple_1690000000_zzz.png",
        "description": null,
        "warranty_info": null,
        "is_active": 1,
        "sub_category": {
          "id": 3,
          "category_id": 1,
          "sub_category_name": "Fruits"
        },
        "rates": [
          {
            "id": 45,
            "product_id": 21,
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
    ],
    "per_page": 10,
    "total": 5,
    "last_page": 1
  }
}
```

### Success — no products attached (empty result)

```json
{
  "status": true,
  "message": "Product list fetched successfully",
  "filters": {
    "sub_category_id": null,
    "promo_tile": "19",
    "search": null,
    "per_page": 10
  },
  "data": {
    "current_page": 1,
    "data": [],
    "per_page": 10,
    "total": 0,
    "last_page": 1
  }
}
```

---

## 3. Product details

Once the product list is shown, tapping a product opens the standard product
details screen using the existing endpoint:

```text
GET /products/{id}
```

Example:

```text
GET /products/21
```

---

## Flutter implementation flow

1. **Home screen**: call `GET /promo-tiles`, store the tiles, render images.
2. **Tap a tile** with `products_count > 0` → navigate to Product List screen.
3. Product List screen calls `GET /products?promo_tile={tileId}` on load.
4. Render the returned products exactly like the normal product list.
5. **Tap a product** → call `GET /products/{id}` → Product Details screen.

### Empty / no-product behaviour

- A tile with `products_count == 0` must **not** navigate to the Product List
  screen (matching the website behaviour).
- If the product list request returns `data.data == []` (e.g. products were
  deactivated after the tile loaded), the Product List screen should simply show
  its normal empty state.

---

## Related existing endpoints (used together with promo tiles)

| Endpoint                          | Purpose                              |
| --------------------------------- | ------------------------------------ |
| `GET /products?promo_tile={id}`   | Product list filtered by a promo tile |
| `GET /products/{id}`              | Full product details                 |
| `GET /products/{id}/rates`        | Price/rate variants for a product    |
| `GET /home`                       | Home page data (categories, offers)  |
| `GET /featured-products`          | Featured products                    |
| `GET /offers`                     | Offer products                       |

---

## Notes

- Promo tile images are served from `/storage/promo_tile/...`.
- Product images are served from `/storage/product/...`.
- Only active products with at least one active rate are returned by the
  `promo_tile` product filter.
- Both endpoints are **public** — no login token required.
- No dedicated `/promo-tiles/{id}/products` endpoint is needed; the existing
  `/products` endpoint handles the filtering via `promo_tile`.