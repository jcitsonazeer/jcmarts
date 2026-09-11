# JCMarts Brand API

Base URL:

```text
https://jcmarts.com/api/v1
```

No authentication required.

---

## 1. Brands

```text
GET /brands
```

Returns all **active** brands, ordered by brand name.

Success response:

```json
{
  "status": true,
  "message": "Brands fetched successfully",
  "data": [
    {
      "id": 1,
      "brand_name": "Local",
      "is_active": 1
    }
  ]
}
```

---

## Notes

- Brand data is also embedded inside products (as `brand`) in
  `docs/product.md` and `docs/catalog.md`.