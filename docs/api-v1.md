# JCMarts API V1 — Documentation Index

This is the single entry point to the JCMarts API used by the Flutter app.
Each module has its own document inside the `docs` folder.

Base URL for local testing:

```text
http://127.0.0.1:8000/api/v1
```

Production URL:

```text
https://jcmarts.com/api/v1
```

Common headers:

```text
Accept: application/json
Content-Type: application/json
```

Protected API header (after login):

```text
Authorization: Bearer {token}
```

---

## Modules

| Module        | Document             | Description                                        |
| ------------- | -------------------- | -------------------------------------------------- |
| Auth          | `docs/auth.md`       | Customer login / registration / logout (OTP)       |
| Catalog       | `docs/catalog.md`    | Home page, banners, featured products              |
| Offers        | `docs/offers.md`     | Offer products                                     |
| Category      | `docs/category.md`   | Categories & sub-categories                        |
| Brand         | `docs/brand.md`      | Brands                                             |
| Product       | `docs/product.md`    | Product list, search, details, rates               |
| Promo Tile    | `docs/promo-tile.md` | Promo tiles + tile → products flow                 |
| Cart          | `docs/cart.md`       | Cart for guests and customers, guest merge         |
| Wishlist      | `docs/wishlist.md`   | Customer wishlist                                  |
| Profile       | `docs/profile.md`    | Profile, addresses, serviceable pincodes           |
| Checkout      | `docs/checkout.md`   | Checkout + Razorpay payment flow                   |
| Order         | `docs/order.md`      | Order list, details, cancel, return                |
| Delivery      | `docs/delivery.md`   | Delivery person app (auth, deliveries, GPS)        |

---

## Endpoint summary

| Method | Endpoint                          | Module    | Auth needed |
| ------ | --------------------------------- | --------- | ----------- |
| POST   | `/otp/send`                       | Auth      | No          |
| POST   | `/otp/verify`                     | Auth      | No          |
| POST   | `/register/otp/send`              | Auth      | No          |
| POST   | `/register/otp/verify`            | Auth      | No          |
| POST   | `/logout`                         | Auth      | Yes         |
| GET    | `/home`                           | Catalog   | No          |
| GET    | `/banners`                        | Catalog   | No          |
| GET    | `/featured-products`              | Catalog   | No          |
| GET    | `/offers`                         | Offers    | No          |
| GET    | `/categories`                     | Category  | No          |
| GET    | `/sub-categories`                 | Category  | No          |
| GET    | `/brands`                         | Brand     | No          |
| GET    | `/products`                       | Product   | No          |
| GET    | `/products/search`                | Product   | No          |
| GET    | `/products/{id}`                  | Product   | No          |
| GET    | `/products/{id}/rates`            | Product   | No          |
| GET    | `/promo-tiles`                    | Promo Tile| No          |
| GET    | `/cart`                           | Cart      | No (token/device) |
| POST   | `/cart`                           | Cart      | No (token/device) |
| PUT    | `/cart/{cartId}`                  | Cart      | No (token/device) |
| DELETE | `/cart/{cartId}`                  | Cart      | No (token/device) |
| GET    | `/cart/count`                     | Cart      | No (token/device) |
| POST   | `/cart/merge`                     | Cart      | Yes         |
| GET    | `/wishlist`                       | Wishlist  | Yes         |
| POST   | `/wishlist`                       | Wishlist  | Yes         |
| DELETE | `/wishlist/{productId}`           | Wishlist  | Yes         |
| POST   | `/wishlist/toggle`                | Wishlist  | Yes         |
| GET    | `/wishlist/check/{productId}`     | Wishlist  | Yes         |
| GET    | `/wishlist/count`                 | Wishlist  | Yes         |
| GET    | `/profile`                        | Profile   | Yes         |
| PUT    | `/profile`                        | Profile   | Yes         |
| GET    | `/addresses`                      | Profile   | Yes         |
| POST   | `/addresses`                      | Profile   | Yes         |
| PUT    | `/addresses/{addressId}`          | Profile   | Yes         |
| DELETE | `/addresses/{addressId}`          | Profile   | Yes         |
| GET    | `/serviceable-pincodes`           | Profile   | Yes         |
| GET    | `/checkout`                       | Checkout  | Yes         |
| POST   | `/payment/create-order`           | Checkout  | Yes         |
| POST   | `/payment/verify`                 | Checkout  | Yes         |
| POST   | `/payment/release`                | Checkout  | Yes         |
| GET    | `/orders`                         | Order     | Yes         |
| GET    | `/orders/{orderId}`               | Order     | Yes         |
| GET    | `/orders/returns/reasons`         | Order     | Yes         |
| POST   | `/orders/{orderId}/cancel`        | Order     | Yes         |
| POST   | `/orders/{orderId}/return`        | Order     | Yes         |
| POST   | `/delivery/otp/send`              | Delivery  | No          |
| POST   | `/delivery/otp/verify`            | Delivery  | No          |
| POST   | `/delivery/logout`                | Delivery  | Yes (delivery) |
| GET    | `/delivery/me`                    | Delivery  | Yes (delivery) |
| GET    | `/delivery/dashboard`             | Delivery  | Yes (delivery) |
| GET    | `/delivery/assigned-orders`       | Delivery  | Yes (delivery) |
| GET    | `/delivery/deliveries/{deliveryId}`| Delivery | Yes (delivery) |
| POST   | `/delivery/deliveries/{deliveryId}/status` | Delivery | Yes (delivery) |
| GET    | `/delivery/history`               | Delivery  | Yes (delivery) |
| PUT    | `/delivery/availability`          | Delivery  | Yes (delivery) |
| POST   | `/delivery/deliveries/{deliveryId}/location` | Delivery | Yes (delivery) |

Auth column note:

- `No` — public endpoint.
- `Yes` — requires a customer token.
- `No (token/device)` — works for guests (`X-Device-ID` header) OR customers
  (Bearer token); at least one is required.
- `Yes (delivery)` — requires a **delivery person** token. Customer tokens get
  `403 Forbidden`.

---

## Typical Flutter flows

- **Home:** `docs/catalog.md` + `docs/promo-tile.md`
- **Browse:** `docs/category.md` → `docs/product.md`
- **Promo Tile → Products:** `docs/promo-tile.md`
- **Cart (guest → login → merge):** `docs/cart.md` + `docs/auth.md`
- **Checkout → Payment → Order:** `docs/checkout.md` → `docs/order.md`
- **Delivery app:** `docs/delivery.md`