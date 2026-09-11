# JCMarts Offers API

Base URL:

```text
https://jcmarts.com/api/v1
```

No authentication required.

---

## 1. Offer products

```text
GET /offers
```

Returns products that are currently on offer (products with an active rate
variant that has `offer_percentage > 0`), ordered by newest first.

Success response:

```json
{
  "status": true,
  "message": "Offer products fetched successfully",
  "data": [
    {
      "product_id": 21,
      "product_name": "Apple",
      "product_image": "https://jcmarts.com/storage/product/apple.png",
      "offer_percentage": "10.00",
      "final_price": "90.00",
      "selling_price": "100.00"
    }
  ]
}
```

Fields:

| Field              | Type   | Description                          |
| ------------------ | ------ | ------------------------------------ |
| `product_id`       | int    | Product ID                           |
| `product_name`     | string | Product name                         |
| `product_image`    | string | Full URL of product image (or null)  |
| `offer_percentage` | string | Discount percentage                  |
| `final_price`      | string | Final price to charge (after offer)  |
| `selling_price`    | string | Original selling price               |

Empty response (`data: []`) means there are no active offers.

---

## Notes

- The same offer data is included inside `GET /home` as `product_offers`
  (see `docs/catalog.md`).
- Tapping a product opens `GET /products/{id}` — see `docs/product.md`.