# JCMarts Product API

Base URL:

```text
https://jcmarts.com/api/v1
```

No authentication required for any endpoint in this module.

---

## Endpoint overview

| Method | Endpoint               | Auth needed | Description                     |
| ------ | ---------------------- | ----------- | ------------------------------- |
| GET    | `/products`            | No          | Product list (with filters)     |
| GET    | `/products/search`     | No          | Product search (same filters)   |
| GET    | `/products/{id}`       | No          | Full product details            |
| GET    | `/products/{id}/rates` | No          | Active price/rate variants      |

---

## 1. Product list

```text
GET /products
```

### Query parameters

| Parameter         | Type | Description                                             |
| ----------------- | ---- | ------------------------------------------------------- |
| `sub_category_id` | int  | (optional) Filter by sub-category                       |
| `promo_tile`      | int  | (optional) Filter by Promo Tile ID (see `docs/promo-tile.md`) |
| `search`          | str  | (optional) Search by product name (contains match)      |
| `per_page`        | int  | (optional) Page size, default `10`, max `100`           |
| `page`            | int  | (optional) Page number                                  |

Only **active** products with at least one **active rate** are returned,
ordered by newest product first.

### Example request

```text
GET /products?sub_category_id=1&search=rice&per_page=10&page=1
GET /products?promo_tile=19&per_page=10&page=1
```

### Success response

```json
{
  "status": true,
  "message": "Product list fetched successfully",
  "filters": {
    "sub_category_id": "1",
    "promo_tile": null,
    "search": "rice",
    "per_page": 10
  },
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 21,
        "sub_category_id": 3,
        "brand_id": 2,
        "product_name": "Rice",
        "product_image": "https://jcmarts.com/storage/product/rice.png",
        "description": null,
        "warranty_info": null,
        "is_active": 1,
        "sub_category": {
          "id": 3,
          "category_id": 1,
          "sub_category_name": "Grains"
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
    "total": 25,
    "last_page": 3
  }
}
```

The `data` key is a standard Laravel paginator (`current_page`, `data`,
`per_page`, `total`, `last_page`, `first_page_url`, `next_page_url`, etc.).

### Success — empty result

Same shape as above with `data.data = []`, `total = 0`, `last_page = 1`.

---

## 2. Product search

```text
GET /products/search
```

Same behaviour and query parameters as `GET /products`
(`sub_category_id`, `promo_tile`, `search`, `per_page`, `page`).

Message: `"Product search fetched successfully"`.

---

## 3. Product details

```text
GET /products/{id}
```

Example: `GET /products/21`.

Success response:

```json
{
  "status": true,
  "message": "Product details fetched successfully",
  "data": {
    "id": 21,
    "sub_category_id": 3,
    "brand_id": 2,
    "product_name": "Rice",
    "product_image": "https://jcmarts.com/storage/product/rice.png",
    "single_image_1": "https://jcmarts.com/storage/product/rice_1.png",
    "single_image_2": null,
    "single_image_3": null,
    "single_image_4": null,
    "description": "Long grain rice",
    "warranty_info": null,
    "is_active": 1,
    "created_date": "2026-09-01T12:00:00.000000Z",
    "updated_date": null,
    "sub_category": {
      "id": 3,
      "category_id": 1,
      "sub_category_name": "Grains",
      "category": { "id": 1, "category_name": "Fresh Foods" }
    },
    "brand": { "id": 2, "brand_name": "Local" },
    "created_by": null,
    "updated_by": null
  }
}
```

All image fields (`product_image`, `single_image_1..4`) are full URLs
(or `null`).

Error: `404` with `{ "status": false, "message": "Product not found" }` when
the product does not exist or is inactive.

---

## 4. Product rates (price variants)

```text
GET /products/{id}/rates
```

Returns the active rate variants for a product, ordered so the default
(`selected_display = 1`) rate comes first.

Success response:

```json
{
  "status": true,
  "message": "Product rates fetched successfully",
  "data": {
    "product_id": 21,
    "product_name": "Rice",
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
}
```

Rate fields:

| Field              | Type   | Description                                   |
| ------------------ | ------ | --------------------------------------------- |
| `id`               | int    | Rate ID (use as `rate_master_id` in cart)     |
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

## Notes

- The `rate_master_id` from `GET /products/{id}/rates` is required when adding
  a product to the cart (see `docs/cart.md`).
- Promo Tile products use the same endpoint via `?promo_tile={id}`
  (see `docs/promo-tile.md`).