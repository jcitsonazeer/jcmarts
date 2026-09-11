# JCMarts Catalog API (Home screen)

Base URL:

```text
https://jcmarts.com/api/v1
```

No authentication required for any endpoint in this module.

---

## Endpoint overview

| Method | Endpoint         | Auth needed | Description                  |
| ------ | ---------------- | ----------- | ---------------------------- |
| GET    | `/home`          | No          | Home page data (single call) |
| GET    | `/banners`       | No          | Home page banners            |
| GET    | `/featured-products` | No      | Featured products            |

---

## 1. Home page data

```text
GET /home
```

Returns everything the home screen needs in one request: top sub-categories,
offer products, and featured products.

Success response:

```json
{
  "status": true,
  "message": "Home page data fetched successfully",
  "data": {
    "product_categories": [
      {
        "id": 3,
        "category_id": 1,
        "sub_category_name": "Fruits",
        "sub_category_image": "https://jcmarts.com/storage/sub_category/fruits.png"
      }
    ],
    "product_offers": [
      {
        "product_id": 21,
        "product_name": "Apple",
        "product_image": "https://jcmarts.com/storage/product/apple.png",
        "offer_percentage": "10.00",
        "final_price": "90.00",
        "selling_price": "100.00"
      }
    ],
    "featured_products": [
      {
        "id": 21,
        "brand_id": 2,
        "product_name": "Apple",
        "product_image": "https://jcmarts.com/storage/product/apple.png",
        "is_active": 1,
        "brand": {
          "id": 2,
          "brand_name": "Local"
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
    ]
  }
}
```

Key fields:

| Field               | Contents                                             |
| ------------------- | ---------------------------------------------------- |
| `product_categories`| Top sub-categories (image is a full URL)             |
| `product_offers`    | Products currently on offer with discounted price    |
| `featured_products` | Featured products with brand + active rates          |

---

## 2. Banners

```text
GET /banners
```

Success response:

```json
{
  "status": true,
  "message": "Banners fetched successfully",
  "data": [
    {
      "id": 2,
      "banner_image": "https://jcmarts.com/storage/index_banner/banner.png",
      "sub_category_id": 3,
      "offer_details_id": 1,
      "sub_category": {
        "id": 3,
        "sub_category_name": "Fruits"
      },
      "offer_detail": {
        "id": 1,
        "offer_name": "Daily Offers"
      }
    }
  ]
}
```

---

## 3. Featured products

```text
GET /featured-products
```

Same product shape as `featured_products` inside `/home`.

Success response:

```json
{
  "status": true,
  "message": "Featured products fetched successfully",
  "data": [
    {
      "id": 21,
      "brand_id": 2,
      "product_name": "Apple",
      "product_image": "https://jcmarts.com/storage/product/apple.png",
      "is_active": 1,
      "brand": { "id": 2, "brand_name": "Local" },
      "rates": []
    }
  ]
}
```

---

## Notes

- Promo Tiles shown on the home screen are a separate endpoint — see
  `docs/promo-tile.md`.
- Offer products are also available standalone — see `docs/offers.md`.
- Related modules: `docs/category.md`, `docs/brand.md`, `docs/product.md`.