# JCMarts — Project Analysis (Bugs, Improvements & Security)

- **Date:** 2026-10-26
- **Scope:** Full application review — `app/`, `routes/`, `config/`, `database/`, `resources/views/`, tests and dependencies.
- **Method:** Static review of controllers, services, models, middleware, routes, config and Livewire components. No runtime penetration testing was performed.
- **Legend:** `Confirmed` = verified in current code. `Review` = needs runtime/product confirmation.

> **Note:** `docs/to-do.txt` is an older analysis and contains several now-stale claims (see Appendix B). This document supersedes it.

---

## 1. Executive Summary

The application is a Laravel 12 monolith with two surfaces:

1. **Session-based web app** — customer storefront + admin panel (`routes/web.php`).
2. **Token API (`/api/v1`)** — Sanctum tokens for customers and delivery persons (`routes/api.php`).

The strongest areas are: server-side cart pricing (prices are re-read from `rate_master`, not trusted from the client), scoped cart/wishlist queries, rate limiting on OTP/login/API, and status-transition validation for deliveries.

The highest-risk areas are:

| # | Risk | Severity |
|---|------|----------|
| 1 | Stock release is **not idempotent** — replaying release inflates stock | Critical |
| 2 | **No Razorpay webhook / reconciliation** — captured payments can be lost and reservations released | Critical |
| 3 | Uploads store the **client-supplied file extension** on the public disk | Critical |
| 4 | Admin auth: no session regeneration, `active_session_id` never enforced, `cur_status` never rechecked | Critical |
| 5 | Checkout is **not idempotent**; repeated API calls reserve stock multiple times | High |
| 6 | Stock ledger reads/writes are race-prone; some paths write without a lock | High |
| 7 | **Zero migrations** + stale SQL dump; fresh deployment cannot reproduce schema | High |
| 8 | Refund/return amounts are not capped against the captured payment | High |
| 9 | Payment/refund **gateway fields are not hidden** from serialization/views | High |
| 10 | OTP codes are stored in plaintext; SMS runs inside DB transactions with a 20s timeout | Medium/High |

---

## 2. Architecture & Data Flow (verified)

- **Frontend auth:** custom session keys `customer_id` (`app/Http/Controllers/Frontend/*`, `CartService`, `WishlistService`), not Laravel's auth guard.
- **Admin auth:** custom session key `admin_id` (`AdminAuthController`, `AdminAuth` middleware).
- **API auth:** Sanctum; tokens resolved to `Customer` or `DeliveryPerson` models. `EnsureCustomer`/`EnsureDeliveryPerson` enforce the model type and active status.
- **Checkout flow:** cart → `createPendingOrderFromCart()` (reserves stock for 5 minutes) → Razorpay order → `verifyPayment` → `markPendingOrderAsPaid()`.
- **Release flow:** web/API customer cancel, admin manual release, and expiry cleanup (`OrderService::cleanupExpiredPendingOrders`, query at `OrderService.php:590-602`) all call `releasePendingOrder()` / `releasePendingOrderAsAdmin()`.
- **Expiry cleanup is opportunistic, not scheduled:** `cleanupExpiredPendingOrders()` is invoked only from `Api\CheckoutController::createOrder` and the admin pending-reservations pages (`AdminOrderController:43,51`). `routes/console.php` has no schedule entry.
- **Stock model:** append-only `stock_info` ledger; current stock = latest row by `id`. `rate_master.soldout_status` is synced after movements.
- **Delivery:** assignment, OTP handover, status history and location tracking are separate services; state transitions validated in `DeliveryStatusService`.

---

## 3. Critical Issues

### C1. `releasePendingOrder()` is not idempotent — stock inflation
**Location:** `app/Services/OrderService.php:292-359`, `app/Services/OrderService.php:361-406`
**Status:** Confirmed

Both release methods only guard on `hasSuccessfulPayment($order)`. They do **not** check:

- `reservation_released_at` (already released), or
- `is_active` / order status.

If release is invoked twice (API retry, customer cancel after admin release, double-click, expiry cleanup after manual release, replay of `/payment/release`), each call adds the item quantities back to stock again.

**Impact:** Inventory inflation, false "in stock" sales, financial/stock mismatch.
**Fix:**
```php
if (!$order || !$order->is_active || $order->reservation_released_at) {
    return;
}
if ($this->hasSuccessfulPayment($order)) {
    return;
}
```
Use a single canonical release service and make the operation transactional + idempotent. Add a unique `(order_id, release_reason)` or `stock_movement` idempotency key.

### C2. No Razorpay webhook or payment reconciliation
**Location:** `app/Services/Payment/RazorpayService.php`, `app/Http/Controllers/Api/CheckoutController.php`, `app/Http/Controllers/FrontendCheckoutController.php`
**Status:** Confirmed

Payment confirmation depends entirely on the browser calling `verifyPayment`. If the user closes the tab, loses network, or the callback fails after Razorpay captures money:

- The order remains pending.
- The 5-minute reservation expires and stock is released.
- Money is captured but the order is not fulfilled.

There is also **no webhook signature verification** and no scheduled reconciliation against Razorpay.

**Fix:**
- Add a `POST /api/v1/payments/razorpay/webhook` endpoint that verifies `X-Razorpay-Signature` with `hash_hmac('sha256', $payload, $secret)`.
- Make webhook handling idempotent by `gateway_order_id` / `gateway_payment_id`.
- On `payment.captured`, mark the order paid if still pending; if it was released, either re-reserve or auto-refund.
- Add a reconciliation command for `pending`/`failed` payments older than N minutes.

### C3. Arbitrary file extension stored on the public disk
**Location:** `app/Services/IndexBannerService.php:131`, `app/Services/PromoTileService.php:254`, `app/Services/ProductImageService.php:189`
**Status:** Confirmed

The services store the **client original extension**:
```php
$extension = strtolower($image->getClientOriginalExtension());
$filename = ... . '.' . $extension;
$image->storeAs('...', $filename, 'public');
```
Controllers validate `mimes:` (content-based), but a file with JPEG content named `shell.php` can pass `mimes:jpg,...` and be written as `.php`. Whether this is exploitable depends on web-server execution rules for `storage/`, but it is a known path to RCE/XSS. `AdminPromoTileController` also validates `file|mimes:...` without the `image` rule.

**Fix:**
- Use `$file->store('path', 'public')` (random hashed name) or `$file->hashName()`.
- Derive extension from the detected MIME: `$file->extension()` / `$file->guessExtension()`.
- Add the `image` validation rule (not just `mimes`).
- Strip EXIF, re-encode with GD/Imagick.
- Ensure `storage/app/public` never executes PHP (nginx `location ~ \.php$ { deny all; }`, Apache `php_flag engine off`).

### C4. Admin session security gaps
**Location:** `app/Http/Controllers/AdminAuthController.php` (login/set `active_session_id` around line 51), `app/Http/Middleware/AdminAuth.php`
**Status:** Confirmed

- No `session()->regenerate()` after successful admin login → session fixation risk.
- `active_session_id` is written at login but never read/enforced → the "single active session" feature is dead code.
- `AdminAuth` only checks `session('admin_id')`; it does **not** re-check `cur_status === 'ACTIVE'`. A deactivated admin keeps access until their session expires.
- Admin login is rate limited but has no account lockout.

**Fix:**
```php
$request->session()->regenerate();
// after role/status checks
session(['admin_id' => $admin->id, 'active_session_id' => $request->session()->getId()]);
```
In middleware: re-load the admin (cache briefly), verify `cur_status === 'ACTIVE'`, and compare `active_session_id` with the current session ID. Invalidate sessions on password change/deactivation. Use `Hash::check` only (already the case) and add per-account lockout.

### C5. Fresh deployment is not reproducible — zero migrations, stale schema
**Location:** `database/migrations/` (0 files), `database/schema/mysql-schema.sql`, `database/sql/*.sql`
**Status:** Confirmed

`database/migrations` contains **no files**, while the dump omits tables that the code uses (`deliveries`, `delivery_persons`, `delivery_person_otp`, `delivery_status_history`, `delivery_location`, `delivery_charge`, `refunds`, `return_requests`, `return_items`, `promo_tiles`, `promo_tile_products`). `php artisan migrate:fresh` creates nothing.

**Impact:** Cannot provision a new environment deterministically; schema drift between environments; tests cannot run against a real schema.
**Fix:** Generate a migrations baseline from the real schema (or `mysqldump --no-data` → one baseline migration), then add migrations for every change. Add a CI job that runs `migrate:fresh --seed`.

---

## 4. High-Severity Issues

### H1. Checkout / order creation is not idempotent
**Location:** `app/Http/Controllers/Api/CheckoutController.php:171-201`, `app/Http/Controllers/FrontendCheckoutController.php:234-282`
**Status:** Confirmed

- The **API** `createOrder` does not release a previous pending order before creating a new one. Repeated calls create multiple pending orders and reserve stock each time (stock hoarding / DoS).
- The **web** flow releases `session('pending_order_id')` first, but relies on session state and is not protected against concurrent requests.
- `verifyPayment` does not verify the **captured amount** against `order.total_amount`.

**Fix:**
- Use an idempotency key (client-generated UUID) with a unique DB constraint on `orders.idempotency_key`.
- Serialize checkout per customer with a cache lock (`Cache::lock("checkout:{$customerId}")`).
- Verify `payment.amount === round(order.total_amount * 100)` and currency before marking paid.
- Cancel any existing pending order for the customer before creating a new one.

### H2. Stock ledger is race-prone and partially unlocked
**Location:** `OrderService::createPendingOrderFromCart` (lock at 131), `createPaidOrderFromCart` (unlocked read at 451-457), `StockInfoService`
**Status:** Confirmed (pending path locks; paid path does not)

`createPaidOrderFromCart()` reads the latest stock with `->value()` and then decrements without `lockForUpdate()`. This method is currently **unused**, but it is a latent double-sell path. The pending path locks per rate, which is good, but the "latest row" pattern (`orderByDesc('id')->lockForUpdate()`) is fragile under high concurrency.

**Fix:**
- Add a single `reserveStock()` / `releaseStock()` API on `StockInfoService` that always locks.
- Store `available_stock` on `rate_master` (or a dedicated inventory table) and update it atomically:
  `UPDATE rate_master SET available_stock = available_stock - ? WHERE id = ? AND available_stock >= ?`.
- Keep the ledger append-only for audit, but never derive availability solely from "latest row" under concurrency.
- Remove or fix the unused `createPaidOrderFromCart()`.

### H3. Cart deactivation relies on attribute matching, not row identity
**Location:** `OrderService::deactivatePurchasedCartItems` (around `OrderService.php:220-290`)
**Status:** Confirmed

Cart rows are deactivated by matching `product_id` + `rate_master_id` + `quantity` rather than by cart row ID. If the cart changed (quantity update, re-add) between reservation and payment, rows can remain active and be checked out again.

**Fix:** Persist `cart_id` on `order_items` (or deactivate by explicit IDs captured at reservation time). Deactivate the exact rows that were ordered.

### H4. Checkout trusts stale cart price snapshot; no re-price
**Location:** `app/Services/CartService.php` (`unit_price` stored at add time), `OrderService::createPendingOrderFromCart`
**Status:** Confirmed

Prices are snapshotted when added to the cart. The order is created from that snapshot without recomputation. If the admin changes `final_price`/`selling_price`, the customer still pays the old price.

**Fix:** Recompute line prices at reservation time from the current `rate_master` row (inside the same lock/transaction). Inform the user of changed prices instead of silently charging stale ones.

### H5. Return/refund amounts are not capped
**Location:** `app/Services/ReturnService.php` (refund creation and `passInspectionAndRefundByAdmin`), `app/Services/OrderCancellationService.php`
**Status:** Confirmed (review totals against captured amount)

Refunds are created without verifying that `sum(refunds) + new_refund <= captured_payment_amount - delivery_charge` (as per policy). Multiple return requests or repeated approvals can over-refund. External Razorpay refunds are also executed **before** the DB status update; a DB failure leaves the gateway and DB inconsistent.

**Fix:**
- Sum prior successful refunds per payment and cap the new amount.
- Persist a `refund_requested` state, call the gateway, then confirm; use an outbox/queue with retries and a reconciliation job.
- Enforce one active return request per order/item with a unique constraint.

### H6. Payment/refund gateway fields are exposed
**Location:** `app/Models/Payment.php`, `app/Models/Refund.php`, `app/Http/Controllers/AdminPaymentController.php`, `app/Http/Controllers/AdminRefundController.php`, `Api/OrderController`
**Status:** Confirmed

Neither model defines `$hidden`. `gateway_signature`, gateway IDs, and raw payload fields can appear in JSON/API responses and admin views.

**Fix:**
```php
protected $hidden = ['gateway_signature', 'gateway_payload'];
```
Return a curated resource/array. Avoid dumping whole models to views.

### H7. Order status can be advanced without customer/admin distinction (latent)
**Location:** `app/Http/Controllers/OrderProcessController.php`, `app/Services/OrderStatusService.php`
**Status:** Confirmed latent (routes not currently registered)

`frontendUpdate` only validates that the next status follows `STATUS_FLOW`; it does not verify that the actor is an admin or that the transition is customer-allowed. The customer-facing process routes are **not registered** in `routes/web.php` (only admin `orders/{orderId}/process` at lines 129-130), so this is currently unreachable. If those routes are re-enabled, a customer could advance their own order (e.g., mark delivered).

**Fix:** Split transition policies by actor (`canCustomerCancel`, admin-only transitions). Add authorization checks in the controller or a policy. Delete or guard the dead frontend methods.

### H8. Customer can cancel up to "assigned for delivery"
**Location:** `app/Services/OrderStatusService.php` (`canCustomerCancel`)
**Status:** Confirmed (product decision needed)

Cancellation is allowed while current status index is below `delivery_person_accepts` (index 4), which includes `assigned_for_delivery`. Once a rider is assigned, cancellation may be undesirable.

**Fix:** If the policy is "cancel before assignment", compare against `assigned_for_delivery` instead. Document and test the exact allowed states.

### H9. Guest cart/session can be hijacked via guessable `X-Device-ID`
**Location:** `app/Http/Controllers/Api/CartController.php` (`resolveSession`)
**Status:** Confirmed

Guest API carts are keyed by a client-supplied `X-Device-ID`. Any party that guesses/knows the value can read/modify another guest's cart. Customer-token carts are validated for type/expiry, but the guest identifier is not signed.

**Fix:** Issue an opaque, signed guest cart token (e.g., encrypted cookie or JWT with a server-side secret), rotate on merge to a customer, and bind the cart to the token hash.

### H10. Delivery address fields reference non-existent columns
**Location:** `app/Services/DeliveryService.php:381-382`, `app/Http/Controllers/Api/DeliveryController.php:316-317,407-408`, `resources/views/admin/deliveries/unassigned.blade.php:47`
**Status:** Confirmed

`customer_address` (`app/Models/CustomerAddress.php`) has no `city` or `state` columns, but delivery summaries read `$address->city` / `$address->state`. Eloquent returns `null`, so city/state are silently omitted (not a crash).

**Fix:** Either add `city`/`state` (and possibly `district`, `country`) columns + validation, or remove the fields and derive display text from `location`/`pincode`.

### H11. Admin user management is not MASTER-gated for reads
**Location:** `app/Http/Controllers/AdminUserController.php` (`index`, `show` vs `ensureMaster`)
**Status:** Confirmed

Any authenticated admin can list/view admin accounts; only mutations are restricted to MASTER.

**Fix:** Apply the MASTER gate to all admin-user routes, or at least redact sensitive fields for non-MASTER.

### H12. Broken frontend redirects
**Location:** `routes/web.php:75-76`
**Status:** Confirmed

Redirects target `/category` and `/product`, which are not registered routes → 404.

**Fix:** Point to the real catalog/product route names or remove the routes.

---

## 5. Medium-Severity Issues

| ID | Location | Issue | Fix |
|----|----------|-------|-----|
| M1 | `CustomerAuthService` | Login does not `session()->regenerate()`; logout only calls `regenerateToken()`. | Regenerate on login and invalidate the session/token on logout. |
| M2 | `CustomerAuthService::findCustomerByMobile` | Filters `is_active = 1`; an inactive account is reported as "not registered". Registering the same mobile again risks a unique-constraint 500. | Look up regardless of active flag; resume/reactivate or return a clear error. |
| M3 | OTP services | OTP codes stored in plaintext; SMS curl call (20s timeout) runs inside DB transactions; provider errors can reveal internals; mobile numbers are logged. | Hash OTPs (bcrypt/HMAC), send SMS after commit via queue, return generic errors, mask PII in logs. |
| M4 | `OtpAttemptService` | Attempt limits exist (5 attempts / 600s), but there is no per-account lockout or resend cooldown beyond the limiter. | Add resend cooldown and exponential backoff; alert on repeated failures. |
| M5 | `Livewire\Frontend\*Card`, `SingleProductRateSelector` | `isSoldOut` is a public Livewire property hydrated from the client; it can be set to `false` to add a sold-out rate. Prices are safe (re-read from DB). | Remove client-trusted guards; validate `soldout_status` and stock inside `CartService::addItem()`. |
| M6 | `WishlistService::toggle` | Check-then-create race can hit the unique constraint and 500. | Catch duplicate-key or use `updateOrCreate`/`firstOrCreate` with a unique index. |
| M7 | `FrontendOrderService::getOrdersForCustomer` | Unbounded order list (no pagination). | Paginate; eager-load only needed columns. |
| M8 | Orders/catalog lists | N+1 queries and missing indexes on `customer_id`, `is_active`, `session_id`, `rate_master_id`, `created_date`. | Add indexes and eager loads; use `select()` projections. |
| M9 | `SERVICEABLE_PINCODES` | 22 pincodes duplicated in `FrontendCheckoutController` and `Api\ProfileController`. | Extract to `config/serviceability.php` or a DB table with `pincode`, `active`, `delivery_charge`. |
| M10 | `Payment` state machine | Multiple status rows/fields; cancellation marks payments failed but there is no central transition guard. | Introduce explicit payment states and a single `PaymentService` transition API. |
| M11 | `OrderService::createPaidOrderFromCart` | Unused method with unlocked stock decrement; dead code that future developers may call. | Remove it or add the same locking/reservation semantics as the pending path. |
| M12 | `FrontendController` | Old OTP/register/cart methods are dead (routes use `CustomerAuthController`). | Remove dead methods/views; keep one auth entry point. |
| M13 | `AdminPromoTileController` | Validates `file|mimes:...` + custom size, but no `image` rule; GIFs up to 16 MB. | Add `image`, enforce reasonable dimensions, re-encode. |
| M14 | `AdminAuthController` | Login error messages may distinguish unknown user vs bad password. | Use a generic "Invalid credentials" message. |
| M15 | `DeliveryService` / `DeliveryAssignmentService` | `availability_status` vs `status` overlap; assigning an offline rider is allowed (becomes busy), and availability can be set to available while busy. | Define one source of truth; validate availability on assignment and status transitions. |
| M16 | `DeliveryTrackingService::saveLocation` | Accepts locations for delivered/cancelled deliveries; no rate limit. | Reject terminal deliveries; throttle per delivery person; validate GPS accuracy. |
| M17 | Config | `config/telescope.php` defaults `enabled` to `true`; gate allows no one in non-local, but Telescope is still installed in production and may record data. | Gate Telescope to local/staging; `TELESCOPE_ENABLED=false` in production; remove from prod deps if unused. |
| M18 | `.env.example` | `APP_DEBUG=true`, `LOG_LEVEL=debug`, mail/DB placeholders. | Ship safe production defaults; document required env vars. |
| M19 | `OrderService::cleanupExpiredPendingOrders` | No scheduler entry in `routes/console.php`; expired 5-minute reservations are only released when someone creates an API order or opens an admin pending-reservations page. | Add `Schedule::call(...)->everyMinute()->withoutOverlapping()` (or a queued job) and monitor it. |

---

## 6. Low-Severity Issues

- `routes/web.php`: cart/wishlist/orders/checkout are public and rely on controller-level `isCustomerLoggedIn()` checks. Prefer middleware for defense in depth.
- `CartService::addItem` accepts any positive quantity; no max per line/order.
- Cart uses `session()->getId()` as the cart key; session regeneration on login can orphan guest carts (no merge strategy).
- `ProductImageService` validates jpg/jpeg/png in code, while edit views accept `.webp`; behavior is inconsistent.
- `AdminOrderController` invokes cleanup on pending-reservation pages only; there is no scheduler entry, so expired reservations can linger until an API checkout or admin page triggers cleanup (see M19).
- `Payment`/`Refund`/`Order` use manual `created_date`/`updated_date` instead of timestamps; easy to forget updates.
- Money is handled as floats in several places. Use integer paise or `decimal` consistently.
- `Order` has no human-readable order number (exposes sequential IDs).
- `AdminOrderService` paginates 20 but has no filters/sorting server-side.
- `frontend/single_product.blade.php` has a non-submitting `form-review` form without `@csrf` (harmless but dead UI).
- `resources/views/admin/deliveries/unassigned.blade.php` renders `$address->city/state` (see H10).
- `AdminRefundController`/`AdminPaymentController` are read-only; no audit trail for who viewed/exported data.

---

## 7. Security Enhancements (recommended)

### 7.1 Payments
- Webhook with HMAC signature verification; idempotency keys.
- Verify amount + currency server-side before marking paid.
- Refund caps, refund state machine, reconciliation job.
- Never log full gateway payloads/signatures.

### 7.2 Authentication & sessions
- `session()->regenerate()` on every privilege change (customer/admin login, OTP verify).
- Enforce admin `active_session_id` and re-check `cur_status` per request.
- Rotate/revoke API tokens on deactivation/password change.
- Add per-account login lockout and generic error messages.
- Set secure cookie flags: `SESSION_SECURE_COOKIE=true`, `SESSION_SAME_SITE=lax` (or `strict`), `SESSION_ENCRYPT=true` where practical.

### 7.3 Input & output
- Upload via `store()` + `hashName()` + MIME-derived extension; add `image` rule; re-encode images.
- Add a strict Content-Security-Policy and security headers (`X-Content-Type-Options`, `Referrer-Policy`, `X-Frame-Options`/`frame-ancestors`).
- Use API Resources / `$hidden` to prevent model over-exposure.
- Escape all output (no `{!! !!}` found — keep it that way).
- Validate `per_page`, sort and filter parameters (currently partly validated).

### 7.4 API
- Add `config/cors.php` with explicit allowed origins, methods and headers.
- Use token abilities/scopes and shorter expiration; disable unused token features.
- Replace guessable `X-Device-ID` with a signed guest token.
- Add request size limits and per-endpoint throttles (checkout, release, refund).
- Enforce order/return ownership checks in every customer endpoint (verify with tests).

### 7.5 Operations
- `APP_DEBUG=false` and `APP_ENV=production` in production.
- Disable Telescope (or restrict to local) and `/horizon`/`/pulse` if present.
- Ensure `.env` is never committed (currently untracked — good) and rotate any keys that may have been shared.
- Disable PHP execution under `storage/` and `public/uploads`.
- Add secret scanning + `composer audit`/`npm audit` to CI.
- Database least-privilege user; encrypted backups; retention policy.
- Mask mobile numbers/addresses in logs and error trackers.

---

## 8. Performance & Reliability Improvements

1. **Stock/inventory:** atomic `available_stock` updates + append-only ledger; remove "latest row" dependency for availability.
2. **Transactions:** keep external network calls (SMS, Razorpay) outside DB transactions; use queues + outbox.
3. **Queues:** SMS, emails, refunds, webhook processing, cleanup jobs (queue is configured as `database` but mostly unused).
4. **Caching:** cache catalog/category/brand/pincode lookups; invalidate on admin writes. Cache admin session status briefly.
5. **Pagination:** orders, returns, deliveries, audit lists; cap `per_page`.
6. **Indexes:** `cart(session_id, is_active)`, `orders(customer_id, is_active, created_date)`, `order_items(order_id, rate_master_id)`, `stock_info(rate_master_id, id)`, `wishlists(customer_id, product_id)`, `delivery_status_history(delivery_id, id)`.
7. **Eager loading:** ensure all list endpoints use `with()` projections (mostly done; audit `FrontendOrderService`, `AdminOrderService`).
8. **Idempotency:** checkout, payment verify, release, refund, webhook, assignment.
9. **Cleanup:** add a scheduler entry for `cleanupExpiredPendingOrders()` (currently only opportunistic), make it idempotent, and monitor it.
10. **Observability:** structured logs with request/order IDs, metrics for failed payments/reservations, alerts on stock mismatches and refund failures.

---

## 9. Code Quality & Maintainability

- **Consolidate duplicated logic:** `CartService` vs `ApiCartService`; product/banner/promo image handling; pincode lists; order summary building.
- **Form Requests:** move validation out of controllers (many controllers inline validate).
- **Policies/Gates:** one authorization layer for admin roles, order ownership, returns, delivery actions.
- **Enums:** order/payment/delivery/return statuses as PHP enums instead of magic strings.
- **DTOs:** typed checkout/order payloads instead of arrays passed across layers.
- **Money value object:** integer paise, formatting only at the edges.
- **Remove dead code:** `FrontendController` legacy methods, `OrderProcessController::frontendUpdate`, `createPaidOrderFromCart` (or fix it), unreachable routes.
- **Naming/consistency:** `cur_status` vs `status`, `is_active` booleans vs `ACTIVE` strings, mixed date columns.
- **Static analysis:** add Larastan/PHPStan (level 5+), Pint for style, Rector for upgrades.
- **Documentation:** keep `docs/*.md` in sync; add an architecture/ADR folder.

---

## 10. Testing & CI Gaps

Current tests are minimal (`tests/Feature/AuthRateLimitTest.php`, `DeliveryLogoutAvailabilityTest.php`, examples). Missing critical coverage:

- Stock reservation → release → re-release (idempotency).
- Concurrent checkout / double click (idempotency key + lock).
- Payment verify success/failure/mismatched amount/webhook replay.
- Refund caps and partial refunds.
- Cart row deactivation by ID.
- Admin login session regeneration and deactivated-admin lockout.
- Upload extension/MIME enforcement.
- Return window and cancellation policies.
- Authorization/IDOR tests for customer/admin/delivery surfaces.

**CI:** `composer install`, `php artisan test`, `migrate:fresh --seed`, Larastan, Pint, `composer audit`, `npm ci && npm run build`, `npm audit`.

---

## 11. Prioritized Action Plan

### P0 — do immediately
1. Make `releasePendingOrder`/`releasePendingOrderAsAdmin` idempotent (C1).
2. Add Razorpay webhook + signature verification + reconciliation (C2).
3. Fix upload naming/extension + add `image` rule (C3).
4. Admin session regeneration + active-session + status recheck (C4).
5. Add migrations baseline so the schema is reproducible (C5).

### P1 — next sprint
6. Idempotent checkout + amount verification (H1).
7. Serialize/lock stock operations; remove unused unlocked path (H2).
8. Deactivate cart rows by ID (H3).
9. Re-price at checkout (H4).
10. Cap refunds + refund state machine (H5).
11. Hide gateway fields (H6).
12. Signed guest cart token (H9).
13. MASTER gate on admin-user reads (H11).
14. Fix broken redirects and address columns (H10, H12).
15. Schedule `cleanupExpiredPendingOrders()` and monitor it (M19).

### P2 — hardening & quality
16. Hash OTPs, queue SMS, generic errors (M3/M4).
17. Livewire server-side soldout/stock checks (M5).
18. Wishlist/unique-constraint safety (M6).
19. Pagination + indexes (M7/M8).
20. Extract pincodes/config; dedupe services (M9, Section 9).
21. Security headers, CORS, cookie flags, Telescope gating (Section 7).
22. Expand test suite + CI gates (Section 10).
23. Remove dead code and consistent enums/DTOs (Sections 7–9).

---

## Appendix A — Key Files Reviewed

**Routes/config:** `routes/web.php`, `routes/api.php`, `bootstrap/app.php`, `config/*` (app, auth, cache, database, filesystems, logging, queue, sanctum, session, telescope, razorpay), `.env.example`, `phpunit.xml`, `composer.json`, `package.json`.

**Auth/security:** `AdminAuthController`, `AdminAuth`, `EnsureCustomer`, `EnsureDeliveryPerson`, `CustomerAuthService`, `OtpSmsService`, `OtpAttemptService`, `AppServiceProvider`.

**Commerce:** `OrderService`, `AdminOrderService`, `OrderStatusService`, `CartService`, `ApiCartService`, `FrontendCheckoutController`, `Api\CheckoutController`, `RazorpayService`, `OrderCancellationService`, `ReturnService`, `FrontendOrderService`, `FrontendProductService`, `FrontendCatalogService`, `ProductService`, `ProductImageService`, `IndexBannerService`, `PromoTileService`, `SubCategoryService`, `RateMasterService`, `StockInfoService`.

**Delivery:** `DeliveryService`, `DeliveryStatusService`, `DeliveryAssignmentService`, `DeliveryTrackingService`, `Api\DeliveryController`, `Api\DeliveryAuthController`, `AdminDeliveryController`, `AdminDeliveryChargeController`.

**Admin CRUD:** `AdminUserController`, `AdminOrderController`, `AdminPaymentController`, `AdminRefundController`, `AdminReturnController`, `AdminPromoTileController`, `IndexBannerController`, `ProductController`, `SubCategoryController`, `ProductImageController`, `CustomerController`, `OrderProcessController`.

**Storefront/API:** `FrontendController`, `FrontendCartController`, `FrontendOrderController`, `FrontendWishlistController`, `FrontendPageController`, `Api\AuthController`, `Api\CartController`, `Api\OrderController`, `Api\ProfileController`, `Api\WishlistController`, `WishlistService`, Livewire frontend components.

**Models:** `Order`, `Customer`, `CustomerAddress`, `RateMaster`, `Payment`, `Refund`, `ReturnRequest`, `ReturnItem`, `Cart`, `StockInfo`, `Delivery`, `DeliveryPerson`, `Product`, `PromoTile`, `Wishlist`, `DeliveryStatusHistory`, `OrderStatus`.

**Views/tests/data:** `resources/views/**` (forms/CSRF/upload audit), `tests/Feature/*`, `database/schema/mysql-schema.sql`, `database/sql/*`, `database/migrations` (empty), `docs/*`.

---

## Appendix B — Stale Claims in `docs/to-do.txt` (not valid anymore)

These were checked against current code and are **not** current bugs:

- "Stock is never decremented" — false: `OrderService::createPendingOrderFromCart` decrements/increments via `stock_info`.
- "CartService is missing `stock_dependent` eager load" — false: `CartService.php:89` includes `rate:id,uom_id,stock_dependent`.
- "Admin login has a plaintext-password fallback" — false: `AdminAuthController` uses `Hash::check` only.
- "No rate limiting" — false: API/OTP/login limiters are configured in `AppServiceProvider` and applied in routes.
- "`config/sanctum.php` missing" — false: it exists and sets a 60-day expiration.
- "Missing `DeliveryStatusHistory` import" — false: the import is present.
- "Cart API does not validate customer tokens" — false: `Api\CartController::resolveSession` validates Customer type + token expiry; it still does not check `is_active` (covered above).
- "`ProductService` leaks `cost_price` via API" — false: `API_PRODUCT_COLUMNS` / `API_RATE_COLUMNS` exclude cost/audit columns (keep it that way).

---

*End of analysis.*
