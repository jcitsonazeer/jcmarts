# JCMarts Category API

Base URL:

```text
https://jcmarts.com/api/v1
```

No authentication required.

---

## Endpoint overview

| Method | Endpoint       | Auth needed | Description                          |
| ------ | -------------- | ----------- | ------------------------------------ |
| GET    | `/categories`  | No          | Categories with their sub-categories |
| GET    | `/sub-categories` | No      | Active sub-categories                |

---

## 1. Categories

```text
GET /categories
```

Returns all categories, each with its sub-categories (ordered by
sub-category name).

Success response:

```json
{
  "status": true,
  "message": "Categories fetched successfully",
  "data": [
    {
      "id": 1,
      "category_name": "Fresh Foods",
      "sub_categories": [
        {
          "id": 3,
          "category_id": 1,
          "sub_category_name": "Fruits",
          "sub_category_image": "https://jcmarts.com/storage/sub_category/fruits.png",
          "is_active": 1
        }
      ]
    }
  ]
}
```

`sub_category_image` is a full URL (or `null`).

---

## 2. Sub-categories

```text
GET /sub-categories
```

Returns only **active** sub-categories, flat list.

Success response:

```json
{
  "status": true,
  "message": "Active sub categories fetched successfully",
  "data": [
    {
      "id": 3,
      "category_id": 1,
      "sub_category_name": "Fruits",
      "sub_category_image": "https://jcmarts.com/storage/sub_category/fruits.png"
    }
  ]
}
```

---

## Notes

- Use a sub-category's `id` as the `sub_category_id` filter on
  `GET /products` (see `docs/product.md`).
- The home screen's `product_categories` are a smaller version of the
  sub-categories here (see `docs/catalog.md`).