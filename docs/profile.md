# JCMarts Profile & Address API

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

| Method | Endpoint                   | Auth needed | Description                     |
| ------ | -------------------------- | ----------- | ------------------------------- |
| GET    | `/profile`                 | Yes         | View profile                    |
| PUT    | `/profile`                 | Yes         | Update profile (name)           |
| GET    | `/addresses`               | Yes         | List addresses                  |
| POST   | `/addresses`               | Yes         | Add a delivery address          |
| PUT    | `/addresses/{addressId}`   | Yes         | Update an address               |
| DELETE | `/addresses/{addressId}`   | Yes         | Delete an address               |
| GET    | `/serviceable-pincodes`    | Yes         | List pincodes we deliver to     |

---

## 1. View profile

```text
GET /profile
```

Success response:

```json
{
  "status": true,
  "message": "Profile fetched successfully",
  "data": {
    "role": "customer",
    "id": 1,
    "name": "Customer",
    "mobile_number": "9876543210",
    "verified_status": "verified",
    "is_active": 1
  }
}
```

---

## 2. Update profile

```text
PUT /profile
```

Payload:

```json
{
  "name": "New Name"
}
```

Success response:

```json
{
  "status": true,
  "message": "Profile updated successfully",
  "data": {
    "id": 1,
    "name": "New Name",
    "mobile_number": "9876543210"
  }
}
```

---

## 3. List addresses

```text
GET /addresses
```

Returns the customer's active addresses, newest first.

Success response:

```json
{
  "status": true,
  "message": "Addresses fetched successfully",
  "data": [
    {
      "id": 2,
      "customer_id": 1,
      "address_line_1": "garden street",
      "address_line_2": "lane view",
      "location": "Main Road",
      "pincode": "629160",
      "landmark": "Near temple",
      "is_active": 1
    }
  ]
}
```

---

## 4. Add address

```text
POST /addresses
```

Payload:

```json
{
  "address_line_1": "garden street",
  "address_line_2": "lane view",
  "location": "Main Road",
  "pincode": "629160",
  "landmark": "Near temple"
}
```

The `pincode` must be one of the serviceable pincodes (see section 7).
Only addresses matching the approved pincode list are accepted.

Success response (`201 Created`):

```json
{
  "status": true,
  "message": "Address added successfully",
  "data": {
    "id": 2,
    "customer_id": 1,
    "address_line_1": "garden street",
    "address_line_2": "lane view",
    "location": "Main Road",
    "pincode": "629160",
    "landmark": "Near temple",
    "is_active": 1
  }
}
```

Error:

- `422` `pincode.in` — `"Delivery not available for the entered pincode"`

---

## 5. Update address

```text
PUT /addresses/{addressId}
```

Example: `PUT /addresses/2`. Same payload fields as adding.

Success response:

```json
{
  "status": true,
  "message": "Address updated successfully",
  "data": {
    "id": 2,
    "address_line_1": "garden street",
    "address_line_2": "lane view",
    "location": "Main Road",
    "pincode": "629160",
    "landmark": "Near temple"
  }
}
```

Error:

- `404` — `"Address not found"` (or the address does not belong to this customer).

---

## 6. Delete address

```text
DELETE /addresses/{addressId}
```

Example: `DELETE /addresses/2`. This is a soft delete (`is_active = 0`).

Success response:

```json
{
  "status": true,
  "message": "Address deleted successfully",
  "data": null
}
```

Error:

- `404` — `"Address not found"`

---

## 7. Serviceable pincodes

```text
GET /serviceable-pincodes
```

Returns the list of pincodes we currently deliver to. Use this to validate
the delivery pincode before the customer enters an address.

Success response:

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

## Notes

- Addresses created here are used at checkout (`selected_address_id`) and by
  the delivery person app — see `docs/checkout.md` and `docs/delivery.md`.