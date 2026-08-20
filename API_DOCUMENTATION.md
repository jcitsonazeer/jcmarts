# JC MARTS — Flutter API Documentation

**Base URL:** `{your-domain}/api/v1`

**Last Updated:** August 2026

**Total Endpoints:** 43

---

## Table of Contents

1. [Quick Reference](#quick-reference)
2. [Common Information](#common-information)
3. [Authentication API](#authentication-api)
4. [Catalog / Browse API](#catalog--browse-api)
5. [Cart API](#cart-api)
6. [Wishlist API](#wishlist-api)
7. [Profile & Address API](#profile--address-api)
8. [Checkout & Payment API](#checkout--payment-api)
9. [Orders, Cancellation & Returns API](#orders-cancellation--returns-api)
10. [Flutter Integration Guide](#flutter-integration-guide)

---

## Quick Reference — All 43 Endpoints

| # | Method | Endpoint | Auth | Description |
|---|--------|----------|------|-------------|
| 1 | POST | `/api/v1/otp/send` | No | Send login OTP |
| 2 | POST | `/api/v1/otp/verify` | No | Verify login OTP, get token |
| 3 | POST | `/api/v1/register/otp/send` | No | Send registration OTP |
| 4 | POST | `/api/v1/register/otp/verify` | No | Verify registration OTP, get token |
| 5 | POST | `/api/v1/logout` | Yes | Logout (delete token) |
| 6 | GET | `/api/v1/home` | No | Home page data |
| 7 | GET | `/api/v1/banners` | No | Banner images |
| 8 | GET | `/api/v1/categories` | No | Categories + subcategories |
| 9 | GET | `/api/v1/sub-categories` | No | Active subcategories |
| 10 | GET | `/api/v1/brands` | No | Active brands |
| 11 | GET | `/api/v1/products` | No | Product list (paginated) |
| 12 | GET | `/api/v1/products/search` | No | Search products |
| 13 | GET | `/api/v1/products/{id}` | No | Product detail |
| 14 | GET | `/api/v1/featured-products` | No | Featured products |
| 15 | GET | `/api/v1/offers` | No | Offer products |
| 16 | GET | `/api/v1/cart` | Optional | Get cart items |
| 17 | POST | `/api/v1/cart` | Optional | Add to cart |
| 18 | PUT | `/api/v1/cart/{cartId}` | Optional | Update cart quantity |
| 19 | DELETE | `/api/v1/cart/{cartId}` | Optional | Remove cart item |
| 20 | GET | `/api/v1/cart/count` | Optional | Cart item count |
| 21 | POST | `/api/v1/cart/merge` | Yes | Merge guest cart after login |
| 22 | GET | `/api/v1/wishlist` | Yes | Get wishlist |
| 23 | POST | `/api/v1/wishlist` | Yes | Add to wishlist |
| 24 | DELETE | `/api/v1/wishlist/{productId}` | Yes | Remove from wishlist |
| 25 | POST | `/api/v1/wishlist/toggle` | Yes | Toggle wishlist |
| 26 | GET | `/api/v1/wishlist/check/{productId}` | Yes | Check wishlist status |
| 27 | GET | `/api/v1/wishlist/count` | Yes | Wishlist count |
| 28 | GET | `/api/v1/profile` | Yes | Get profile |
| 29 | PUT | `/api/v1/profile` | Yes | Update profile name |
| 30 | GET | `/api/v1/addresses` | Yes | List addresses |
| 31 | POST | `/api/v1/addresses` | Yes | Add address |
| 32 | PUT | `/api/v1/addresses/{addressId}` | Yes | Update address |
| 33 | DELETE | `/api/v1/addresses/{addressId}` | Yes | Delete address |
| 34 | GET | `/api/v1/serviceable-pincodes` | Yes | Get serviceable pincodes |
| 35 | GET | `/api/v1/checkout` | Yes | Checkout data |
| 36 | POST | `/api/v1/payment/create-order` | Yes | Create Razorpay order |
| 37 | POST | `/api/v1/payment/verify` | Yes | Verify payment |
| 38 | POST | `/api/v1/payment/release` | Yes | Release payment reservation |
| 39 | GET | `/api/v1/orders` | Yes | List orders |
| 40 | GET | `/api/v1/orders/returns/reasons` | Yes | Return reasons |
| 41 | GET | `/api/v1/orders/{orderId}` | Yes | Order detail |
| 42 | POST | `/api/v1/orders/{orderId}/cancel` | Yes | Cancel order |
| 43 | POST | `/api/v1/orders/{orderId}/return` | Yes | Return order |

**Auth column legend:**
- **No** = No authentication required
- **Yes** = Requires `Authorization: Bearer {token}` header
- **Optional** = Works without auth (guest cart) OR with auth (customer cart)

---

## Common Information

### Response Format

Every response follows this structure:

```json
{
    "status": true,
    "message": "Success message here",
    "data": { ... }
}
```

On error:

```json
{
    "status": false,
    "message": "Error description",
    "data": null
}
```

### HTTP Status Codes

| Code | Meaning |
|------|---------|
| 200 | Success |
| 201 | Created (POST that creates a resource) |
| 401 | Unauthorized (missing/invalid token or device ID) |
| 404 | Not Found |
| 422 | Validation Error (invalid payload or business rule) |
| 500 | Server Error |

### Required Headers

| Header | When | Example |
|--------|------|---------|
| `Authorization` | All auth-required endpoints | `Bearer eyJhbGciOiJIUzI1NiIs...` |
| `Content-Type` | All POST/PUT requests | `application/json` |
| `X-Device-ID` | Guest cart operations only | `550e8400-e29b-41d4-a716-446655440000` |

### Image URLs

All image fields in responses are returned as **full URLs**:
- Product images: `http://your-domain/storage/product/{filename}`
- Subcategory images: `http://your-domain/storage/sub_category/{filename}`
- Banner images: `http://your-domain/storage/index_banner/{filename}`

If the image field is `null`, no image has been uploaded.

---

## Authentication API

### 1. Send Login OTP

Send a 6-digit OTP to the customer's mobile number. If the number is not registered, a new customer is auto-created.

| Detail | Value |
|--------|-------|
| **Endpoint** | `POST /api/v1/otp/send` |
| **Auth Required** | No |
| **Content-Type** | `application/json` |

**Request:**
```json
{
    "mobile_number": "9876543210"
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `mobile_number` | string | Yes | 10-15 digit mobile number |

**Response (200 OK):**
```json
{
    "status": true,
    "message": "OTP sent successfully",
    "data": {
        "mobile_number": "9876543210",
        "expires_in_seconds": 180
    }
}
```

> In non-production environments, the response includes `"otp": "123456"` for testing.

---

### 2. Verify Login OTP

Verify the OTP and receive a Bearer token for authenticated requests.

| Detail | Value |
|--------|-------|
| **Endpoint** | `POST /api/v1/otp/verify` |
| **Auth Required** | No |
| **Content-Type** | `application/json` |

**Request:**
```json
{
    "mobile_number": "9876543210",
    "otp": "123456"
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `mobile_number` | string | Yes | Same number used in send OTP |
| `otp` | string | Yes | 6-digit OTP |

**Response (200 OK):**
```json
{
    "status": true,
    "message": "Login successful",
    "data": {
        "token": "1|abc123def456...",
        "token_type": "Bearer",
        "customer": {
            "id": 5,
            "name": "John Doe",
            "mobile_number": "9876543210",
            "verified_status": "verified"
        }
    }
}
```

> **Store the `token` securely.** Use it as `Authorization: Bearer {token}` in all subsequent requests.

**Error (401 — Invalid OTP):**
```json
{
    "status": false,
    "message": "Invalid OTP",
    "data": null
}
```

**Error (401 — Expired OTP):**
```json
{
    "status": false,
    "message": "OTP expired",
    "data": null
}
```

---

### 3. Send Registration OTP

Send OTP for new customer registration. Requires a name.

| Detail | Value |
|--------|-------|
| **Endpoint** | `POST /api/v1/register/otp/send` |
| **Auth Required** | No |
| **Content-Type** | `application/json` |

**Request:**
```json
{
    "name": "John Doe",
    "mobile_number": "9876543210"
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `name` | string | Yes | Customer's full name |
| `mobile_number` | string | Yes | 10-15 digit mobile number |

**Response (200 OK):**
```json
{
    "status": true,
    "message": "Registration OTP sent successfully",
    "data": {
        "mobile_number": "9876543210",
        "expires_in_seconds": 180
    }
}
```

**Error (422 — Already registered):**
```json
{
    "status": false,
    "message": "This mobile number is already registered. Please login.",
    "data": null
}
```

---

### 4. Verify Registration OTP

Complete registration and receive a Bearer token.

| Detail | Value |
|--------|-------|
| **Endpoint** | `POST /api/v1/register/otp/verify` |
| **Auth Required** | No |
| **Content-Type** | `application/json` |

**Request:**
```json
{
    "mobile_number": "9876543210",
    "otp": "123456"
}
```

**Response (200 OK):**
```json
{
    "status": true,
    "message": "Registration completed successfully",
    "data": {
        "token": "2|xyz789abc123...",
        "token_type": "Bearer",
        "customer": {
            "id": 5,
            "name": "John Doe",
            "mobile_number": "9876543210",
            "verified_status": "verified"
        }
    }
}
```

---

### 5. Logout

Delete the current access token.

| Detail | Value |
|--------|-------|
| **Endpoint** | `POST /api/v1/logout` |
| **Auth Required** | Yes (Bearer Token) |
| **Payload** | None |

**Response (200 OK):**
```json
{
    "status": true,
    "message": "Logged out successfully",
    "data": null
}
```

---

## Catalog / Browse API

> All catalog endpoints are **public** (no auth required).

### 6. Home Page Data

Get homepage data: product categories, offers, and featured products.

| Detail | Value |
|--------|-------|
| **Endpoint** | `GET /api/v1/home` |
| **Auth Required** | No |

**Response (200 OK):**
```json
{
    "status": true,
    "message": "Home page data fetched successfully",
    "data": {
        "product_categories": [
            {
                "id": 6,
                "sub_category_name": "Fruits",
                "sub_category_image": "http://your-domain/storage/sub_category/fruits.png"
            }
        ],
        "product_offers": [ "..." ],
        "featured_products": [ "..." ]
    }
}
```

---

### 7. Banners

Get homepage banner images.

| Detail | Value |
|--------|-------|
| **Endpoint** | `GET /api/v1/banners` |
| **Auth Required** | No |

**Response (200 OK):**
```json
{
    "status": true,
    "message": "Banners fetched successfully",
    "data": [
        {
            "id": 1,
            "banner_image": "http://your-domain/storage/index_banner/banner1.jpg"
        }
    ]
}
```

---

### 8. Categories

Get all categories with their subcategories.

| Detail | Value |
|--------|-------|
| **Endpoint** | `GET /api/v1/categories` |
| **Auth Required** | No |

**Response (200 OK):**
```json
{
    "status": true,
    "message": "Categories fetched successfully",
    "data": [
        {
            "id": 1,
            "category_name": "Fruits",
            "subCategories": [
                {
                    "id": 6,
                    "category_id": 1,
                    "sub_category_name": "Fruits",
                    "sub_category_image": "http://your-domain/storage/sub_category/fruits.png"
                }
            ]
        }
    ]
}
```

---

### 9. Sub-Categories

Get all active subcategories.

| Detail | Value |
|--------|-------|
| **Endpoint** | `GET /api/v1/sub-categories` |
| **Auth Required** | No |

**Response (200 OK):**
```json
{
    "status": true,
    "message": "Active sub categories fetched successfully",
    "data": [
        {
            "id": 6,
            "category_id": 1,
            "sub_category_name": "Fruits",
            "sub_category_image": "http://your-domain/storage/sub_category/fruits.png"
        }
    ]
}
```

---

### 10. Brands

Get all active brands.

| Detail | Value |
|--------|-------|
| **Endpoint** | `GET /api/v1/brands` |
| **Auth Required** | No |

**Response (200 OK):**
```json
{
    "status": true,
    "message": "Brands fetched successfully",
    "data": [
        {
            "id": 1,
            "brand_name": "Fresh Farm"
        }
    ]
}
```

---

### 11. Products (List)

Get a paginated list of products. Supports filtering by subcategory and search.

| Detail | Value |
|--------|-------|
| **Endpoint** | `GET /api/v1/products` |
| **Auth Required** | No |

**Query Parameters:**
| Param | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| `sub_category_id` | integer | No | — | Filter by subcategory |
| `search` | string | No | — | Search keyword |
| `per_page` | integer | No | 10 | Items per page (1-100) |

**Example:** `GET /api/v1/products?sub_category_id=6&per_page=20`

**Response (200 OK):**
```json
{
    "status": true,
    "message": "Product list fetched successfully",
    "filters": {
        "sub_category_id": 6,
        "search": null,
        "per_page": 10
    },
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 10,
                "product_name": "Apple",
                "product_image": "http://your-domain/storage/product/apple.jpg",
                "rates": [ "..." ]
            }
        ],
        "per_page": 10,
        "total": 25
    }
}
```

---

### 12. Products (Search)

Same as products list — both endpoints behave identically.

| Detail | Value |
|--------|-------|
| **Endpoint** | `GET /api/v1/products/search` |
| **Auth Required** | No |

**Query Parameters:** Same as Products (List).

---

### 13. Product Detail

Get full details for a single product including all rate variants and images.

| Detail | Value |
|--------|-------|
| **Endpoint** | `GET /api/v1/products/{id}` |
| **Auth Required** | No |

**URL Parameters:**
| Param | Type | Description |
|-------|------|-------------|
| `id` | integer | Product ID |

**Response (200 OK):**
```json
{
    "status": true,
    "message": "Product details fetched successfully",
    "data": {
        "id": 10,
        "product_name": "Apple",
        "product_image": "http://your-domain/storage/product/apple.jpg",
        "single_image_1": "http://your-domain/storage/product/apple_side.jpg",
        "single_image_2": "http://your-domain/storage/product/apple_back.jpg",
        "single_image_3": null,
        "single_image_4": null,
        "rates": [
            {
                "id": 25,
                "uom_id": 1,
                "selling_price": "120.00",
                "offer_percentage": "10",
                "offer_price": "12.00",
                "final_price": "108.00",
                "soldout_status": "NO",
                "selected_display": true,
                "stock_dependent": "YES",
                "is_active": true,
                "uom": {
                    "id": 1,
                    "primary_uom": "Kg",
                    "secondary_uom": "Kg"
                }
            }
        ]
    }
}
```

**Error (404):**
```json
{
    "status": false,
    "message": "Product not found",
    "data": null
}
```

---

### 14. Featured Products

| Detail | Value |
|--------|-------|
| **Endpoint** | `GET /api/v1/featured-products` |
| **Auth Required** | No |

**Response (200 OK):**
```json
{
    "status": true,
    "message": "Featured products fetched successfully",
    "data": [ "..." ]
}
```

---

### 15. Offers

| Detail | Value |
|--------|-------|
| **Endpoint** | `GET /api/v1/offers` |
| **Auth Required** | No |

**Response (200 OK):**
```json
{
    "status": true,
    "message": "Offer products fetched successfully",
    "data": [ "..." ]
}
```

---

## Cart API

> Cart routes work for **both guests and authenticated users**. No `auth:sanctum` middleware is required.

| Scenario | How the cart is identified |
|----------|---------------------------|
| Guest (not logged in) | `X-Device-ID` header -> `session_id = "device_{uuid}"` |
| Logged in | Bearer token -> `session_id = "customer_{id}"` |

### 16. Get Cart Items

| Detail | Value |
|--------|-------|
| **Endpoint** | `GET /api/v1/cart` |
| **Auth Required** | Optional (guest or auth) |

**Headers (guest):**
```
X-Device-ID: 550e8400-e29b-41d4-a716-446655440000
```

**Headers (logged in):**
```
Authorization: Bearer eyJhbGciOiJIUzI1NiIs...
```

**Response (200 OK):**
```json
{
    "status": true,
    "message": "Cart items fetched successfully",
    "data": {
        "items": [
            {
                "id": 1,
                "product_id": 10,
                "rate_master_id": 25,
                "quantity": 2,
                "unit_price": "499.00",
                "product": {
                    "id": 10,
                    "product_name": "Apple",
                    "product_image": "http://your-domain/storage/product/apple.jpg"
                },
                "rate": {
                    "id": 25,
                    "uom_id": 1,
                    "final_price": "499.00",
                    "selling_price": "599.00",
                    "uom": {
                        "id": 1,
                        "primary_uom": "Kg",
                        "secondary_uom": "Kg"
                    }
                }
            }
        ],
        "item_count": 2,
        "sub_total": "998.00"
    }
}
```

**Error (401 — No auth and no device ID):**
```json
{
    "status": false,
    "message": "X-Device-ID header is required for guest cart operations.",
    "data": null
}
```

---

### 17. Add Item to Cart

| Detail | Value |
|--------|-------|
| **Endpoint** | `POST /api/v1/cart` |
| **Auth Required** | Optional (guest or auth) |
| **Content-Type** | `application/json` |

**Request:**
```json
{
    "product_id": 10,
    "rate_master_id": 25,
    "quantity": 1
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `product_id` | integer | Yes | Product ID |
| `rate_master_id` | integer | Yes | Rate variant ID |
| `quantity` | integer | No | Quantity (default: 1, min: 1, max: 100) |

**Response (201 Created):**
```json
{
    "status": true,
    "message": "Item added to cart successfully",
    "data": {
        "cart_item": { "..." },
        "item_count": 3,
        "sub_total": "1497.00"
    }
}
```

> If the same product + rate already exists in the cart, the quantity is incremented.

---

### 18. Update Cart Item Quantity

| Detail | Value |
|--------|-------|
| **Endpoint** | `PUT /api/v1/cart/{cartId}` |
| **Auth Required** | Optional (guest or auth) |
| **Content-Type** | `application/json` |

**URL Parameters:**
| Param | Type | Description |
|-------|------|-------------|
| `cartId` | integer | Cart item ID |

**Request:**
```json
{
    "quantity": 3
}
```

**Response (200 OK):**
```json
{
    "status": true,
    "message": "Cart quantity updated successfully",
    "data": {
        "item_count": 5,
        "sub_total": "2495.00"
    }
}
```

---

### 19. Remove Cart Item

| Detail | Value |
|--------|-------|
| **Endpoint** | `DELETE /api/v1/cart/{cartId}` |
| **Auth Required** | Optional (guest or auth) |

**URL Parameters:**
| Param | Type | Description |
|-------|------|-------------|
| `cartId` | integer | Cart item ID |

**Response (200 OK):**
```json
{
    "status": true,
    "message": "Item removed from cart successfully",
    "data": {
        "item_count": 4,
        "sub_total": "1996.00"
    }
}
```

---

### 20. Get Cart Item Count

| Detail | Value |
|--------|-------|
| **Endpoint** | `GET /api/v1/cart/count` |
| **Auth Required** | Optional (guest or auth) |

**Response (200 OK):**
```json
{
    "status": true,
    "message": "Cart count fetched successfully",
    "data": {
        "item_count": 4
    }
}
```

---

### 21. Merge Guest Cart (After Login)

Transfer guest cart items into the authenticated customer's cart. **Call this immediately after login.**

| Detail | Value |
|--------|-------|
| **Endpoint** | `POST /api/v1/cart/merge` |
| **Auth Required** | Yes |
| **Headers** | `Authorization: Bearer {token}` + `X-Device-ID: {uuid}` |

**Merge behavior:**

| Guest Cart | Customer Cart | After Merge |
|-----------|---------------|-------------|
| Product X, qty 2 | Product X, qty 1 | Product X, qty 3 (merged) |
| Product Y, qty 1 | — | Product Y, qty 1 (moved) |
| — | Product Z, qty 2 | Product Z, qty 2 (unchanged) |

**Response (200 OK):**
```json
{
    "status": true,
    "message": "Guest cart merged successfully",
    "data": {
        "items": [ "..." ],
        "item_count": 5,
        "sub_total": "2495.00"
    }
}
```

**If no X-Device-ID header (no guest cart):**
```json
{
    "status": true,
    "message": "No guest cart to merge. Returning current cart.",
    "data": {
        "items": [],
        "item_count": 0,
        "sub_total": "0.00"
    }
}
```

---

## Wishlist API

> Wishlist **requires login**. No guest wishlist.

### 22. Get Wishlist Items

| Detail | Value |
|--------|-------|
| **Endpoint** | `GET /api/v1/wishlist` |
| **Auth Required** | Yes |

**Response (200 OK):**
```json
{
    "status": true,
    "message": "Wishlist items fetched successfully",
    "data": {
        "items": [
            {
                "id": 1,
                "customer_id": 5,
                "product_id": 10,
                "product": {
                    "id": 10,
                    "product_name": "Apple",
                    "product_image": "http://your-domain/storage/product/apple.jpg",
                    "rates": [ "..." ]
                }
            }
        ],
        "item_count": 1
    }
}
```

---

### 23. Add Product to Wishlist

| Detail | Value |
|--------|-------|
| **Endpoint** | `POST /api/v1/wishlist` |
| **Auth Required** | Yes |
| **Content-Type** | `application/json` |

**Request:**
```json
{
    "product_id": 10
}
```

**Response (201 Created):**
```json
{
    "status": true,
    "message": "Product added to wishlist successfully",
    "data": {
        "wishlist_item": {
            "id": 1,
            "product_id": 10
        },
        "item_count": 3
    }
}
```

---

### 24. Remove Product from Wishlist

| Detail | Value |
|--------|-------|
| **Endpoint** | `DELETE /api/v1/wishlist/{productId}` |
| **Auth Required** | Yes |

**Response (200 OK):**
```json
{
    "status": true,
    "message": "Product removed from wishlist successfully",
    "data": {
        "item_count": 2
    }
}
```

---

### 25. Toggle Wishlist

Best for heart/like buttons on product cards.

| Detail | Value |
|--------|-------|
| **Endpoint** | `POST /api/v1/wishlist/toggle` |
| **Auth Required** | Yes |
| **Content-Type** | `application/json` |

**Request:**
```json
{
    "product_id": 10
}
```

**Response (200 OK):**
```json
{
    "status": true,
    "message": "Product added to wishlist",
    "data": {
        "is_wishlisted": true,
        "item_count": 3
    }
}
```

---

### 26. Check Wishlist Status

Check if a product is wishlisted (for showing heart icon state on product detail page).

| Detail | Value |
|--------|-------|
| **Endpoint** | `GET /api/v1/wishlist/check/{productId}` |
| **Auth Required** | Yes |

**Response (200 OK):**
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

### 27. Get Wishlist Count

| Detail | Value |
|--------|-------|
| **Endpoint** | `GET /api/v1/wishlist/count` |
| **Auth Required** | Yes |

**Response (200 OK):**
```json
{
    "status": true,
    "message": "Wishlist count fetched successfully",
    "data": {
        "item_count": 3
    }
}
```

---

## Profile & Address API

> All profile and address endpoints **require login**.

### 28. Get Profile

| Detail | Value |
|--------|-------|
| **Endpoint** | `GET /api/v1/profile` |
| **Auth Required** | Yes |

**Response (200 OK):**
```json
{
    "status": true,
    "message": "Profile fetched successfully",
    "data": {
        "id": 5,
        "name": "John Doe",
        "mobile_number": "9876543210",
        "verified_status": 1,
        "is_active": true
    }
}
```

---

### 29. Update Profile

| Detail | Value |
|--------|-------|
| **Endpoint** | `PUT /api/v1/profile` |
| **Auth Required** | Yes |
| **Content-Type** | `application/json` |

**Request:**
```json
{
    "name": "John Updated"
}
```

**Response (200 OK):**
```json
{
    "status": true,
    "message": "Profile updated successfully",
    "data": {
        "id": 5,
        "name": "John Updated",
        "mobile_number": "9876543210"
    }
}
```

---

### 30. Get Addresses

| Detail | Value |
|--------|-------|
| **Endpoint** | `GET /api/v1/addresses` |
| **Auth Required** | Yes |

**Response (200 OK):**
```json
{
    "status": true,
    "message": "Addresses fetched successfully",
    "data": [
        {
            "id": 1,
            "customer_id": 5,
            "address_line_1": "123 Main Street",
            "address_line_2": "Near Central Park",
            "location": "Kolachel",
            "pincode": "629151",
            "landmark": "Opposite Temple",
            "is_active": true,
            "created_date": "2025-01-15T10:30:00.000000Z"
        }
    ]
}
```

---

### 31. Add Address

| Detail | Value |
|--------|-------|
| **Endpoint** | `POST /api/v1/addresses` |
| **Auth Required** | Yes |
| **Content-Type** | `application/json` |

**Request:**
```json
{
    "address_line_1": "123 Main Street",
    "address_line_2": "Near Central Park",
    "location": "Kolachel",
    "pincode": "629151",
    "landmark": "Opposite Temple"
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `address_line_1` | string | Yes | First line of address (max 255) |
| `address_line_2` | string | Yes | Second line of address (max 255) |
| `location` | string | Yes | Locality / area name (max 150) |
| `pincode` | string | Yes | 6-digit pincode (must be serviceable) |
| `landmark` | string | Yes | Nearby landmark (max 255) |

**Response (201 Created):**
```json
{
    "status": true,
    "message": "Address added successfully",
    "data": {
        "id": 2,
        "customer_id": 5,
        "address_line_1": "123 Main Street",
        "address_line_2": "Near Central Park",
        "location": "Kolachel",
        "pincode": "629151",
        "landmark": "Opposite Temple",
        "is_active": true
    }
}
```

**Error (422 — Non-serviceable pincode):**
```json
{
    "status": false,
    "message": "Delivery not available for the entered pincode",
    "data": null
}
```

**Serviceable pincodes:**
`629151`, `629152`, `629153`, `629154`, `629158`, `629160`, `629162`, `629163`, `629165`, `629167`, `629168`, `629171`, `629172`, `629173`, `629177`, `629179`, `629188`, `629190`, `629191`, `629194`, `629195`, `629197`

---

### 32. Update Address

| Detail | Value |
|--------|-------|
| **Endpoint** | `PUT /api/v1/addresses/{addressId}` |
| **Auth Required** | Yes |
| **Content-Type** | `application/json` |

**URL Parameters:**
| Param | Type | Description |
|-------|------|-------------|
| `addressId` | integer | Address ID |

**Request:** Same fields as Add Address.

**Response (200 OK):**
```json
{
    "status": true,
    "message": "Address updated successfully",
    "data": { "..." }
}
```

**Error (404):**
```json
{
    "status": false,
    "message": "Address not found",
    "data": null
}
```

---

### 33. Delete Address

| Detail | Value |
|--------|-------|
| **Endpoint** | `DELETE /api/v1/addresses/{addressId}` |
| **Auth Required** | Yes |

**Response (200 OK):**
```json
{
    "status": true,
    "message": "Address deleted successfully",
    "data": null
}
```

---

### 34. Get Serviceable Pincodes

| Detail | Value |
|--------|-------|
| **Endpoint** | `GET /api/v1/serviceable-pincodes` |
| **Auth Required** | Yes |

**Response (200 OK):**
```json
{
    "status": true,
    "message": "Serviceable pincodes fetched successfully",
    "data": {
        "pincodes": [
            "629151", "629152", "629153", "629154", "629158",
            "629160", "629162", "629163", "629165", "629167",
            "629168", "629171", "629172", "629173", "629177",
            "629179", "629188", "629190", "629191", "629194",
            "629195", "629197"
        ]
    }
}
```

---

## Checkout & Payment API

> All checkout endpoints **require login** and an **active cart with items**.

### 35. Get Checkout Data

Returns cart items, addresses, and order summary for the checkout screen.

| Detail | Value |
|--------|-------|
| **Endpoint** | `GET /api/v1/checkout` |
| **Auth Required** | Yes |

**Response (200 OK):**
```json
{
    "status": true,
    "message": "Checkout data fetched successfully",
    "data": {
        "cart_items": [ "..." ],
        "addresses": [ "..." ],
        "summary": {
            "sub_total": 998.00,
            "delivery_charge": 0.0,
            "packing_charge": 0.0,
            "other_charge": 0.0,
            "total": 998.00
        }
    }
}
```

**Error (422 — Empty cart):**
```json
{
    "status": false,
    "message": "Your cart is empty",
    "data": null
}
```

---

### 36. Create Razorpay Order

Creates a pending order (reserves stock for 5 minutes) and initiates a Razorpay payment order.

| Detail | Value |
|--------|-------|
| **Endpoint** | `POST /api/v1/payment/create-order` |
| **Auth Required** | Yes |
| **Content-Type** | `application/json` |

**Request:**
```json
{
    "selected_address_id": 1
}
```

**Response (200 OK):**
```json
{
    "status": true,
    "message": "Payment order created successfully",
    "data": {
        "pending_order_id": 42,
        "razorpay_order_id": "order_PqNnXyZ123abc",
        "amount": 99800,
        "currency": "INR",
        "key": "rzp_test_xxxxxxxxxxxxx"
    }
}
```

| Field | Description |
|-------|-------------|
| `pending_order_id` | Internal order ID — send in verify/release |
| `razorpay_order_id` | Razorpay order ID — send in verify/release |
| `amount` | Amount in **paise** (99800 = Rs 998) |
| `key` | Razorpay key for the Flutter SDK |

---

### 37. Verify Razorpay Payment

After the Razorpay SDK completes payment, call this to verify and finalize the order.

| Detail | Value |
|--------|-------|
| **Endpoint** | `POST /api/v1/payment/verify` |
| **Auth Required** | Yes |
| **Content-Type** | `application/json` |

**Request:**
```json
{
    "pending_order_id": 42,
    "razorpay_order_id": "order_PqNnXyZ123abc",
    "razorpay_payment_id": "pay_ABC123xyz",
    "razorpay_signature": "signature_hash_here"
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `pending_order_id` | integer | Yes | From create-order response |
| `razorpay_order_id` | string | Yes | From create-order response |
| `razorpay_payment_id` | string | Yes | From Razorpay SDK success callback |
| `razorpay_signature` | string | Yes | From Razorpay SDK success callback |

**Response (200 OK):**
```json
{
    "status": true,
    "message": "Payment successful. Order placed.",
    "data": null
}
```

**Error (422 — Verification failed):**
```json
{
    "status": false,
    "message": "Payment verification failed",
    "data": null
}
```

---

### 38. Release Pending Payment

If the user closes the Razorpay SDK without paying, call this to release the stock reservation.

| Detail | Value |
|--------|-------|
| **Endpoint** | `POST /api/v1/payment/release` |
| **Auth Required** | Yes |
| **Content-Type** | `application/json` |

**Request:**
```json
{
    "pending_order_id": 42,
    "razorpay_order_id": "order_PqNnXyZ123abc"
}
```

**Response (200 OK):**
```json
{
    "status": true,
    "message": "Reserved stock released",
    "data": null
}
```

---

## Orders, Cancellation & Returns API

> All order endpoints **require login**. Customers can only access their own orders.

### 39. List Orders

| Detail | Value |
|--------|-------|
| **Endpoint** | `GET /api/v1/orders` |
| **Auth Required** | Yes |
| **Query Params** | `?q=<search>` (optional — search by order ID, status, or payment) |

**Response (200 OK):**
```json
{
    "status": true,
    "message": "Orders fetched successfully",
    "data": [
        {
            "id": 42,
            "current_order_status": "order_placed",
            "total_amount": 998.00,
            "currency": "INR",
            "items_count": 2,
            "current_payment_method": "razorpay",
            "current_payment_status": "paid",
            "created_date": "2025-03-15T14:30:00.000000Z"
        }
    ]
}
```

---

### 40. Get Return Reasons

| Detail | Value |
|--------|-------|
| **Endpoint** | `GET /api/v1/orders/returns/reasons` |
| **Auth Required** | Yes |

**Response (200 OK):**
```json
{
    "status": true,
    "message": "Return reasons fetched successfully",
    "data": {
        "reasons": [
            "Wrong Item",
            "Damaged Product",
            "Defective Product",
            "Missing Parts",
            "Not as Described",
            "Ordered by Mistake",
            "Other"
        ]
    }
}
```

---

### 41. Get Order Details

| Detail | Value |
|--------|-------|
| **Endpoint** | `GET /api/v1/orders/{orderId}` |
| **Auth Required** | Yes |

**URL Parameters:**
| Param | Type | Description |
|-------|------|-------------|
| `orderId` | integer | Order ID |

**Response (200 OK):**
```json
{
    "status": true,
    "message": "Order fetched successfully",
    "data": {
        "id": 42,
        "sub_total": 998.00,
        "delivery_charge": 0.0,
        "packing_charge": 0.0,
        "other_charge": 0.0,
        "total_amount": 998.00,
        "currency": "INR",
        "current_order_status": "order_delivered",
        "current_payment_method": "razorpay",
        "current_payment_status": "paid",
        "current_payment_paid_at": "2025-03-15T14:32:00.000000Z",
        "can_customer_cancel": false,
        "can_customer_return": true,
        "return_allowed_until": "2025-03-22T14:30:00.000000Z",
        "return_period_expired": false,
        "returnable_items": [ "..." ],
        "order_status_timeline": [ "..." ],
        "items": [ "..." ],
        "address": { "..." },
        "statuses": [ "..." ],
        "payments": [ "..." ],
        "refunds": [ "..." ],
        "return_requests": [ "..." ],
        "created_date": "2025-03-15T14:30:00.000000Z"
    }
}
```

| Field | Description |
|-------|-------------|
| `can_customer_cancel` | `true` if the customer can cancel now |
| `can_customer_return` | `true` if the customer can request a return |
| `return_allowed_until` | Deadline for return (7 days from delivery) |
| `return_period_expired` | `true` if return window has passed |
| `returnable_items` | Items eligible for return with `returnable_quantity` |

---

### 42. Request Order Cancellation

| Detail | Value |
|--------|-------|
| **Endpoint** | `POST /api/v1/orders/{orderId}/cancel` |
| **Auth Required** | Yes |
| **Payload** | None |

**Response (200 OK):**
```json
{
    "status": true,
    "message": "Order cancellation request submitted successfully",
    "data": null
}
```

**Error (422 — Cannot cancel):**
```json
{
    "status": false,
    "message": "This order cannot be cancelled now",
    "data": null
}
```

---

### 43. Request Return

| Detail | Value |
|--------|-------|
| **Endpoint** | `POST /api/v1/orders/{orderId}/return` |
| **Auth Required** | Yes |
| **Content-Type** | `application/json` |

**Request:**
```json
{
    "reason": "Damaged Product",
    "customer_note": "Item arrived with a broken screen",
    "items": {
        "12": { "quantity": 1 },
        "15": { "quantity": 2 }
    }
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `reason` | string | Yes | Must match one of the return reasons |
| `customer_note` | string | No | Additional notes (max 1000 chars) |
| `items` | object | Yes | Key = `order_item_id`, Value = `{ quantity: int }` |

> Use `returnable_items` from order detail response to find the correct `order_item_id` and `returnable_quantity`.

**Response (200 OK):**
```json
{
    "status": true,
    "message": "Return request submitted successfully",
    "data": null
}
```

**Error (422 — Return period expired):**
```json
{
    "status": false,
    "message": "Return period expired for this order",
    "data": null
}
```

---

## Flutter Integration Guide

### Device ID Setup

On first app launch, generate a UUID and store it locally:

```dart
import 'package:uuid/uuid.dart';
import 'package:shared_preferences/shared_preferences.dart';

Future<String> getDeviceId() async {
  final prefs = await SharedPreferences.getInstance();
  String? deviceId = prefs.getString('device_id');
  if (deviceId == null) {
    deviceId = const Uuid().v4();
    await prefs.setString('device_id', deviceId);
  }
  return deviceId;
}
```

### Authentication Flow

1. Customer enters mobile number
2. Call `POST /api/v1/otp/send` with `mobile_number`
3. Receive OTP (from SMS, or from response in non-production)
4. Call `POST /api/v1/otp/verify` with `mobile_number` + `otp`
5. Receive `token` in response
6. Store token securely (`flutter_secure_storage` or `shared_preferences`)
7. **Immediately** call `POST /api/v1/cart/merge` with both `Authorization` and `X-Device-ID` headers
8. Use `Authorization: Bearer {token}` for all subsequent requests

### Complete User Journey

```
1. App opens -> Generate/store device UUID
2. Browse products (no auth needed)
3. Tap "Add to Cart" -> POST /api/v1/cart (with X-Device-ID header)
4. View cart -> GET /api/v1/cart (with X-Device-ID header)
5. Tap "Checkout" -> Show login/register screen
6. Login via OTP -> Receive token
7. Call POST /api/v1/cart/merge (with token + X-Device-ID)
8. Call GET /api/v1/checkout -> Shows addresses + summary
9. Add/select address -> POST /api/v1/addresses or select existing
10. Call POST /api/v1/payment/create-order -> Receives Razorpay order details
11. Open Razorpay SDK with the order details
12. On success -> POST /api/v1/payment/verify
13. On abandon/close -> POST /api/v1/payment/release
14. View orders -> GET /api/v1/orders
15. Cancel order -> POST /api/v1/orders/{id}/cancel
16. Return order -> GET /api/v1/orders/returns/reasons, then POST /api/v1/orders/{id}/return
```

### Checkout & Payment Flow

- `GET /api/v1/checkout` returns cart items, addresses, and price summary
- `POST /api/v1/payment/create-order` reserves stock and creates a Razorpay order — returns `pending_order_id`, `razorpay_order_id`, `amount`, and `key`
- Use `amount` and `key` to open the Razorpay Flutter SDK
- On successful payment, the SDK returns `razorpay_payment_id` and `razorpay_signature` — send all three plus `pending_order_id` and `razorpay_order_id` to `POST /api/v1/payment/verify`
- If the user closes the Razorpay SDK without paying, call `POST /api/v1/payment/release`
- Stock is reserved for **5 minutes** — if payment is not completed, the reservation expires automatically

### Order Statuses

| Status | Meaning |
|--------|---------|
| `order_placed` | Order confirmed, payment received |
| `cancellation_requested` | Customer requested cancellation |
| `cancelled_by_customer` | Admin approved cancellation |
| `return_requested` | Customer requested a return |
| `return_approved` | Admin approved the return |
| `pickup_scheduled` | Return pickup scheduled |
| `product_received` | Returned product received |
| `inspection_passed` | Inspection passed, refund initiated |
| `inspection_failed` | Inspection failed |
| `refund_initiated` | Razorpay refund started |
| `refund_completed` | Razorpay refund processed |
| `return_closed` | Return process complete |
| `order_delivered` | Order delivered to customer |

### Return Policy

- Returns are only available for **delivered** orders
- Return window is **7 days** from delivery date
- `GET /api/v1/orders/{orderId}` returns `can_customer_return`, `return_allowed_until`, and `returnable_items`
- Use `returnable_items` to show which items can be returned and their `returnable_quantity`
- The `items` payload for return uses `order_item_id` as the key (from the order detail response)

### Price Handling

- `unit_price` in cart: The price at the time the item was added to cart
- `final_price` in rate: The current offer price (selling_price - offer_price)
- `selling_price` in rate: The original price before any offer
- If `final_price` is 0 or null, use `selling_price` as the effective price

### Rate Master (Product Variants)

- Each product can have multiple rate variants (different UOMs, prices)
- When adding to cart, you must specify both `product_id` AND `rate_master_id`
- Use `GET /api/v1/products/{id}` to get available rate variants
- The `selected_display` field in rate_master indicates which variant to show by default on product cards
