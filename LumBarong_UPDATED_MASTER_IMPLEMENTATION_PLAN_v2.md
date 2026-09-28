# LumBarong — Whole-System Implementation Plan

**Based on:** Whole-System Diagnosis Report dated 2026-09-28  
**Format:** Problem → Solution → Implementation → Acceptance Check  
**Goal:** Stabilize the Laravel application without creating another parallel architecture.

---

## PHASE 0 — SECURITY AND BUSINESS-LOGIC BLOCKERS

### PROBLEM 01 — Checkout trusts client-supplied product price

**Problem**  
`CheckoutController` uses request/cart values such as `price` when calculating subtotals, totals, and `OrderItem` prices. A modified browser request can therefore attempt to lower the product price before order creation.

**Solution**  
Make the database product record the only pricing authority at final checkout.

**Implementation**
1. In `backend-laravel/app/Http/Controllers/CheckoutController.php`, stop using client `price` as a financial value.
2. Accept only `productId`, `quantity`, selected variation/options, and other non-financial identifiers from the client.
3. Inside one `DB::transaction()`, load every product with `lockForUpdate()`.
4. Read the current authoritative product price from the locked model.
5. Rebuild every `OrderItem`, subtotal, discount, and total from server values.
6. Never persist the client subtotal/total as authoritative.
7. Return the server-calculated totals to the frontend for display.

**Acceptance Check**  
Changing the request from `₱1,000` to `₱1` cannot change the stored order total or order-item price.

---

### PROBLEM 02 — Checkout trusts client-supplied seller ID

**Problem**  
A request can attempt to attach a product to another seller by changing `sellerId`.

**Solution**  
Derive seller ownership from the locked product record.

**Implementation**
1. Remove request `sellerId` from all financial/order-authority calculations.
2. Load the product and use its real `seller_id`/seller relation.
3. Group orders by the database seller ID only.
4. Validate seller account eligibility server-side.
5. Add a regression test using a real product while submitting another seller's ID.

**Acceptance Check**  
The stored order seller always matches the product owner in the database.

---

### PROBLEM 03 — Client can manipulate subtotal, total, or shipping fee

**Problem**  
Any browser-calculated subtotal/total/shipping values must be considered untrusted input.

**Solution**  
Calculate all monetary values again on the server at order placement.

**Implementation**
1. Treat frontend totals as display-only.
2. Recalculate product subtotal from locked database prices.
3. Recalculate discounts from server rules.
4. Recalculate shipping using the selected address and current seller/product data.
5. Recalculate final payable amount.
6. Ignore client `subtotal`, `total`, and `shippingFee` for persisted financial truth.
7. Record the final server values in the order/payment snapshot.

**Acceptance Check**  
Changing any browser-calculated amount does not alter the actual amount stored or charged.

---

### PROBLEM 04 — Multi-seller injection can corrupt a single-seller checkout

**Problem**  
A manipulated cart can attempt to inject another seller into a checkout that was intended to contain only selected items or one seller grouping.

**Solution**  
Server-side cart validation must reconstruct the seller grouping from product ownership.

**Implementation**
1. Load every selected product from the database.
2. Derive seller IDs from products.
3. Rebuild seller groups server-side.
4. Reject any request structure that requires a seller relationship not supported by the products.
5. Test mixed-seller and single-seller carts explicitly.

**Acceptance Check**  
A client cannot force a product under a seller that does not own it.

---

### PROBLEM 05 — Legacy `/api/v1/orders` uses a different order-creation pipeline

**Problem**  
`OrderController::createOrder()` can create orders through a separate path from the newer checkout transaction, with different payment/shipping/verification behavior.

**Solution**  
Create one canonical order-creation service and make every entry point use it.

**Implementation**
1. Create `app/Services/CreateOrderService.php`.
2. Move canonical transaction logic into the service:
   - product loading and locks
   - price validation
   - seller resolution
   - stock validation
   - shipping calculation
   - payment transaction creation
   - order creation
   - order-item creation
   - order-shipping snapshot
   - audit logging
3. Refactor `CheckoutController` to call the service.
4. Refactor `OrderController::createOrder()` to call the same service.
5. Keep API-specific validation/serialization outside the service.
6. Remove duplicate order-calculation logic after migration.

**Acceptance Check**  
Web checkout and API checkout produce the same business states and financial rules for the same transaction.

---

### PROBLEM 06 — Customer can set `Completed` through generic order-status endpoint

**Problem**  
`OrderController::updateOrderStatus()` maps customer-submitted statuses such as `received by buyer`/`completed` into canonical `Completed`, creating a weaker path than the dedicated delivery-confirmation flow.

**Solution**  
Replace arbitrary customer status updates with narrow actor-specific commands.

**Implementation**
1. Remove customer authority over arbitrary canonical statuses.
2. Allow customer only to call `confirmReceived()` for eligible orders.
3. Keep seller commands separate: accept/prepare/ship/update tracking.
4. Allow system/carrier events to control transport statuses where applicable.
5. Reserve admin manual overrides for exceptional cases and require an audit reason.
6. Add authorization tests for every actor.

**Acceptance Check**  
A customer POST cannot directly move an eligible order from `Pending` to `Completed` through the generic endpoint.

---

### PROBLEM 07 — Fulfillment `To Ship` incorrectly verifies payment

**Problem**  
Transitioning an order to `To Ship` also sets payment state to `Verified`.

**Solution**  
Payment and fulfillment must be separate state machines.

**Implementation**
1. Define payment states: `UNVERIFIED`, `REVIEW`, `VERIFIED`, `REJECTED`, `VOID`.
2. Define fulfillment states independently.
3. Remove the side effect that turns payment verified when fulfillment becomes `To Ship`.
4. Before `To Ship`, check that payment is already in an allowed state.
5. Update payment only through a payment-specific service/command.
6. Audit every payment state transition.

**Acceptance Check**  
Changing fulfillment status never changes payment status unless an explicit payment action occurs.

---

### PROBLEM 08 — Receipt reference matching is not strict enough

**Problem**  
The receipt verifier initializes reference matching too permissively, allowing a transaction to proceed when an entered reference exists but the receipt itself did not independently provide a matching reference.

**Solution**  
PASS requires independently detected evidence from OCR/vision and an exact normalized match.

**Implementation**
1. In `AiService.php`, make missing detected reference an automatic non-PASS condition when a reference is required.
2. Normalize reference strings consistently: trim, uppercase where appropriate, remove harmless spacing, preserve significant digits.
3. Compare `submitted_reference` against `detected_reference` exactly after normalization.
4. Require the detected reference to originate from the uploaded receipt image.
5. Return explicit evidence fields such as:
   - `submitted_reference`
   - `detected_reference`
   - `reference_match`
   - `reference_confidence`
6. Define:
   - `PASS` = readable and matching reference + amount match + provider/evidence checks
   - `REVIEW` = unreadable/ambiguous evidence
   - `REJECT` = clear mismatch/invalid/underpaid/reused reference

**Acceptance Check**  
Submitting the correct reference manually with a receipt containing a different reference cannot return `PASS`.

---

### PROBLEM 09 — `REVIEW` can fall through into accepted checkout behavior

**Problem**  
The checkout path rejects explicit `REJECT`, but `REVIEW` may continue unless downstream states enforce manual verification.

**Solution**  
Make `REVIEW` a first-class unverified state that cannot authorize fulfillment.

**Implementation**
1. Persist the exact verification result.
2. Map `REVIEW` to payment state `REVIEW`/`UNVERIFIED`.
3. Block seller fulfillment actions while payment is not verified.
4. Provide admin/seller review UI for supported manual verification.
5. Require an explicit audited transition to `VERIFIED`.
6. Do not silently convert AI uncertainty into approval.

**Acceptance Check**  
A `REVIEW` receipt cannot be fulfilled until a valid verification transition occurs.

---

### PROBLEM 10 — Payment reference uniqueness must be atomic

**Problem**  
AI detection alone cannot prevent two simultaneous transactions from claiming the same payment reference.

**Solution**  
Use database-backed atomic reference claiming.

**Implementation**
1. Keep `PaymentTransaction.active_reference` as the canonical active claim.
2. Normalize the reference before storing it.
3. Enforce a unique constraint for active claims according to the current schema design.
4. Claim the reference inside the same DB transaction as order/payment creation.
5. Handle duplicate-key race conditions as a rejected/reviewed payment, not a successful claim.
6. Release the claim only on defined terminal states such as `REJECTED`/`VOID`.

**Acceptance Check**  
Two concurrent orders using the same reference cannot both become verified/live.

---

## PHASE 0B — FILE AND DATA SECURITY

### PROBLEM 11 — KYC/payment/refund/packing files are publicly stored

**Problem**  
Sensitive documents are stored under publicly reachable locations, including seller requirements, payment screenshots, refund proof, and commission-related evidence.

**Solution**  
Move sensitive uploads to private storage and expose them only through authorized controllers.

**Implementation**
1. Inventory every upload field and storage path.
2. Classify media:
   - public product media
   - private KYC
   - private payment proof
   - private refund proof
   - restricted packing proof
3. Change sensitive disks to private filesystem/object storage.
4. Store database paths/IDs instead of public URLs for sensitive documents.
5. Create authorization policies for each document type.
6. Update seller/admin/customer screens to request authorized download/view endpoints.
7. Remove direct public links to raw file paths.

**Acceptance Check**  
Knowing a payment/KYC filename does not allow an unauthenticated user to download it.

---

### PROBLEM 12 — `/storage/{path}` and `/uploads/{path}` are catch-all file routes

**Problem**  
Wildcard file-serving routes return files based on user-supplied paths without sufficient object authorization and without a robust canonical path containment check.

**Solution**  
Delete catch-all sensitive file serving and replace it with resource-based authorized endpoints.

**Implementation**
1. Remove or restrict `GET /storage/{path}` and `GET /uploads/{path}`.
2. For legitimate public media, use a fixed public storage path with no sensitive data.
3. For private files, use endpoints such as `/seller/documents/{id}` or `/orders/{order}/payment-proof`.
4. Resolve the database object first.
5. Authorize the current user against that object.
6. Resolve the filesystem path server-side.
7. Add `realpath()` containment verification before serving.
8. Reject `..`, null bytes, unexpected path forms, and invalid IDs.

**Acceptance Check**  
Traversal strings and guessed paths never escape the intended storage root or bypass authorization.

---

### PROBLEM 13 — Public product API exposes too much seller/user data

**Problem**  
`ProductController::serializeProduct()` and eager-loaded seller relations can expose fields such as payment identifiers, KYC-related URLs, addresses, coordinates, tokens, and authentication metadata.

**Solution**  
Expose an explicit public seller resource instead of serializing the full `User` model.

**Implementation**
1. Create `PublicSellerResource` or equivalent serializer.
2. Whitelist only fields that are genuinely public, for example:
   - seller ID
   - display name/shop name
   - public profile photo
   - verification badge/state intended for customers
   - public rating/review summary
3. Remove GCash/Maya numbers from the public product response.
4. Remove BIR/KYC paths.
5. Remove private shop address and coordinates.
6. Remove FCM/auth/reset/login/internal metadata.
7. Audit all `with('seller')`, `toArray()`, `toJson()`, and direct `User` serialization in public controllers.

**Acceptance Check**  
Public product responses contain only the approved seller whitelist.

---

## PHASE 0C — AI SECURITY

### PROBLEM 14 — AI HTTP requests disable TLS certificate verification

**Problem**  
`AiService.php` uses `verify => false` in multiple HTTP client calls.

**Solution**  
Restore certificate verification.

**Implementation**
1. Remove `verify => false`.
2. Use the operating system CA bundle or configured trusted certificates.
3. Make any custom CA path explicit through environment configuration.
4. Add an automated check preventing insecure HTTP client configuration from being committed.
5. Verify all external AI clients, not only the current receipt path.

**Acceptance Check**  
Outbound AI requests validate TLS certificates in staging/production.

---

### PROBLEM 15 — AI model fallback may be fake/incomplete

**Problem**  
The code builds a model list but then slices it to one model, which can make fallback unreachable.

**Solution**  
Implement an actual sequential fallback strategy with observable failure handling.

**Implementation**
1. Define ordered primary/fallback models in configuration.
2. Attempt primary model.
3. On defined availability/timeout/model-not-found failures, try the next model.
4. Do not retry permanent validation failures indefinitely.
5. Log model failure metadata without logging secrets.
6. Return a stable application-level result regardless of model used.
7. Test primary success and fallback success paths.

**Acceptance Check**  
Disabling/failing the primary model causes the configured fallback model to be attempted.

---

### PROBLEM 16 — AI endpoints are too publicly callable

**Problem**  
AI routes include receipt verification, payment-reference checks, seller generation tools, stylist chat, sizing, and password analysis outside the principal authenticated route grouping.

**Solution**  
Add route-specific authorization and rate limits based on the business use case.

**Implementation**
1. Classify each AI endpoint:
   - customer authenticated
   - seller authenticated
   - admin-only
   - internal-only
2. Add `auth`/Sanctum where business data is involved.
3. Add `throttle` middleware for all AI endpoints.
4. Restrict receipt verification to an authenticated order/payment context.
5. Validate upload type/size before AI processing.
6. Add request quotas and abuse logging.
7. Avoid exposing unrestricted model-generation endpoints publicly.

**Acceptance Check**  
Anonymous/high-frequency requests cannot consume Gemini resources or process arbitrary payment files without authorization.

---

## PHASE 1 — ROUTING AND APPLICATION INTEGRITY

### PROBLEM 17 — API routes point to non-existent AddressController methods

**Problem**  
`routes/api.php` references `getAddresses`, `createAddress`, `updateAddress`, `deleteAddress`, and `setDefaultAddress`, while the controller exposes `index`, `store`, `update`, `destroy`, and `setDefault`.

**Solution**  
Make routes and controller method names consistent.

**Implementation**
1. Update route targets to the real controller methods, or add compatibility wrappers only when necessary.
2. Prefer Laravel REST conventions for the canonical API.
3. Update frontend callers to the canonical endpoints.
4. Add route/controller smoke tests.

**Acceptance Check**  
Every declared address route resolves to an existing callable controller method.

---

### PROBLEM 18 — API route points to non-existent CategoryController method

**Problem**  
`CategoryController::getCategories` is referenced but `index` is the current implementation.

**Solution**  
Bind the route to the canonical `index` method or add a clearly named compatibility method.

**Implementation**  
Update route, tests, and frontend calls together; remove the unused legacy name.

**Acceptance Check**  
Categories endpoint returns successfully from a fresh application install.

---

### PROBLEM 19 — API route points to non-existent AnalyticsController method

**Problem**  
`getSellerAnalytics` is referenced, while `sellerAnalytics` exists.

**Solution**  
Align the route with the real controller action and authorization middleware.

**Implementation**  
Update route → controller binding, verify seller ownership, and add response-contract tests.

**Acceptance Check**  
Seller analytics endpoint resolves and rejects unauthorized seller access.

---

### PROBLEM 20 — API routes point to non-existent RefundController methods

**Problem**  
`createRefundRequest`, `getSellerRefundRequests`, and `updateRefundStatus` do not match the current controller method names `store`, `sellerIndex`, and `updateStatus`.

**Solution**  
Canonicalize the refund API around REST-style controller actions.

**Implementation**
1. Update route bindings.
2. Verify customer/seller/admin middleware for each endpoint.
3. Update Blade/JS API consumers.
4. Add refund endpoint smoke tests.

**Acceptance Check**  
All refund endpoints resolve and enforce the intended actor permissions.

---

### PROBLEM 21 — Superadmin routes point to missing controller methods

**Problem**  
`SuperAdminController` routes reference missing methods including `toggleShopStatus`, `deleteShop`, `orders`, `systemHealth`, and `clearSystemCache`.

**Solution**  
Map each route to an existing canonical action or implement the missing business operation deliberately; do not create empty compatibility methods.

**Implementation**
1. For `toggleShopStatus`, map to the actual status toggle implementation if semantics match.
2. For `deleteShop`, map only if it has the same deletion scope/audit behavior as existing seller deletion; otherwise implement a dedicated safe action.
3. Implement or intentionally remove `orders` and `systemHealth` if the UI still requires them.
4. Map `clearSystemCache` to the current cache-clearing method only after confirming permissions/audit behavior.
5. Add superadmin authorization tests.

**Acceptance Check**  
Every enabled superadmin UI action resolves to a real, authorized implementation.

---

### PROBLEM 22 — Selected checkout form posts to a missing route

**Problem**  
`resources/views/cart/index.blade.php` posts `/checkout/selected`, while `CheckoutController::fromSelected()` exists without the corresponding route.

**Solution**  
Register the missing route and name it consistently.

**Implementation**
1. Add `POST /checkout/selected` to `routes/web.php`.
2. Point it to `CheckoutController::fromSelected`.
3. Apply the correct authentication middleware.
4. Name it `checkout.selected`.
5. Change the Blade form/JS to use `route('checkout.selected')` instead of hard-coded URL text.

**Acceptance Check**  
Selected-item checkout from the cart reaches the controller and creates the expected checkout session/view.

---

### PROBLEM 23 — Missing named route `checkout`

**Problem**  
`CartController.php` calls `route('checkout')`, but the route is named `checkout.index`.

**Solution**  
Standardize the route name.

**Implementation**
1. Change the caller to `route('checkout.index')`.
2. Search the whole repository for other `route('checkout')` references.
3. Add a route-name smoke test.

**Acceptance Check**  
Buy-now/cart navigation does not throw a missing-route exception.

---

### PROBLEM 24 — Duplicate `/api/v1` route definitions across web and API files

**Problem**  
The same `/api/v1/upload` and `/api/v1/reports...` signatures exist through both routing surfaces with different middleware/auth assumptions.

**Solution**  
Use one canonical API routing layer.

**Implementation**
1. Keep API endpoints in `routes/api.php`.
2. Group them under a single `v1` prefix/contract.
3. Apply Sanctum/auth/throttle middleware there.
4. Remove duplicated `/api/v1` definitions from `routes/web.php`.
5. Update consumers to the canonical API.
6. Run route-list comparison after cleanup.

**Acceptance Check**  
Every `/api/v1/...` URI has one canonical definition and one intended middleware stack.

---

### PROBLEM 25 — Duplicate `superadmin.audit-logs` named route

**Problem**  
The same named route is declared twice.

**Solution**  
Keep one declaration and remove the duplicate.

**Implementation**  
Search for all references, keep the implementation used by the UI, delete the duplicate, and add route-name uniqueness checks in CI.

**Acceptance Check**  
`superadmin.audit-logs` appears exactly once in the route definition.

---

## PHASE 2 — DATABASE SOURCE OF TRUTH

### PROBLEM 26 — `RefundRequest` model does not match the current migration schema

**Problem**  
The model uses table `refundrequests` and camelCase fields, while the current migration creates `refund_requests` and snake_case columns.

**Solution**  
Standardize the model and code on the current migration schema.

**Implementation**
1. Set `protected $table = 'refund_requests';` or remove the explicit table if Laravel's convention matches.
2. Change model attributes/query keys to current column names.
3. Update relationships and controller references.
4. Search all code for both naming generations.
5. Create a fresh migration test from zero.

**Acceptance Check**  
`migrate:fresh --seed` followed by refund creation succeeds without legacy-table assumptions.

---

### PROBLEM 27 — Database dump `database.sql` is behind the migration history

**Problem**  
The SQL dump reflects an older schema while 82 migrations represent a newer schema with payment, shipping, analytics, and audit structures.

**Solution**  
Make migrations and seeders the authoritative schema.

**Implementation**
1. Use migrations/seeders for fresh installs.
2. Verify every current model against the resulting schema.
3. Rebuild the database from zero.
4. Export a new `database.sql` only after validation.
5. Mark old dump/schema artifacts as legacy or remove them.
6. Document that manual edits to `database.sql` are not the source of truth.

**Acceptance Check**  
A fresh install from migrations reproduces the schema required by all current application code.

---

### PROBLEM 28 — SQL dump encoding is UTF-16 LE with BOM

**Problem**  
The current dump can cause compatibility problems with standard MySQL/phpMyAdmin import workflows.

**Solution**  
Distribute UTF-8 SQL or clearly document a controlled import process.

**Implementation**
1. Export a fresh SQL dump from the authoritative database.
2. Save as UTF-8 without BOM unless tooling specifically requires otherwise.
3. Test import into a clean MySQL database.
4. Verify Philippine peso characters and other non-ASCII text after import.

**Acceptance Check**  
The SQL dump imports cleanly in the supported deployment toolchain with no character corruption.

---

### PROBLEM 29 — Schema naming conventions are mixed across generations

**Problem**  
The current migrations mostly use Laravel snake_case while legacy models/dumps use camelCase/concatenated table names.

**Solution**  
Choose one database naming standard and remove legacy assumptions.

**Implementation**
1. Use snake_case table/column names for the current Laravel schema.
2. Update models, queries, relationships, validation, resources, and tests.
3. Add a one-time audit/search for legacy names such as `refundrequests`, `productviews`, `sellerfunnelevents`, and `systemsettings`.
4. Remove compatibility code once all callers migrate.

**Acceptance Check**  
All runtime queries reference the same schema generation created by current migrations.

---

## PHASE 3 — REFUND AND UPLOAD AUTHORIZATION

### PROBLEM 30 — Refund item is not constrained to the submitted order

**Problem**  
`RefundController::store()` verifies the order and separately accepts an `orderItemId`, but does not fully bind that item to the selected order.

**Solution**  
Query the item through the order relationship/compound condition.

**Implementation**
1. Load the order belonging to the authenticated customer.
2. Fetch `OrderItem` with both order ID and requested item ID.
3. Reject if no such child object exists.
4. Persist refund references from that authorized object only.
5. Test using an item from another order owned by the same customer.

**Acceptance Check**  
A refund for Order A cannot reference an item belonging to Order B.

---

### PROBLEM 31 — Refund video proof validation is too weak

**Problem**  
The upload rule is effectively `required|file`, with storage to public storage.

**Solution**  
Use type/size/content validation plus private storage.

**Implementation**
1. Validate MIME type and extension using Laravel validation.
2. Apply a reasonable maximum size.
3. Use safe server-generated filenames/IDs.
4. Store privately.
5. Save metadata such as original filename only when needed.
6. Add authorization to viewing/downloading.
7. Reject executable or unexpected content types.

**Acceptance Check**  
Unsupported files are rejected, oversized files are rejected, and accepted proofs are not publicly accessible.

---

### PROBLEM 32 — Packing proof has two validation paths

**Problem**  
One order-status path accepts `packingPhoto` directly, while the dedicated upload path has stronger image validation.

**Solution**  
Use one packing-proof service and validation policy.

**Implementation**
1. Extract packing proof handling into a dedicated service/method.
2. Make all endpoints call it.
3. Centralize MIME/type/size validation.
4. Store privately or behind access control.
5. Add audit metadata for uploader and order.

**Acceptance Check**  
Every packing proof, regardless of entry point, receives identical validation and storage policy.

---

## PHASE 4 — SHIPPING

### PROBLEM 33 — `is_default` vs `isDefault` address column mismatch

**Problem**  
`ProductShippingController.php` orders by `is_default`, while the current model/migration uses `isDefault`.

**Solution**  
Standardize on the actual current schema column name.

**Implementation**
1. Verify the authoritative migration column.
2. Update `ProductShippingController.php` to the canonical name.
3. Search every address query for the old field.
4. Add tests for default-address fallback.

**Acceptance Check**  
Checkout/shipping quote fallback works for an authenticated customer without an explicit address ID.

---

### PROBLEM 34 — Shipping calculator still contains hardcoded geography/pricing logic

**Problem**  
`ShippingCalculatorService.php` retains legacy defaults and geography/provider branches even though the current architecture is database-driven.

**Solution**  
Make DB-backed provider/zone/rate tables the sole pricing/geography source.

**Implementation**
1. Keep seller origin address/coordinates as input data.
2. Resolve origin and destination zones through `ShippingZoneResolverService`.
3. Calculate chargeable weight from dimensions/weight.
4. Load provider rates from `shipping_rates`.
5. Return provider-specific quotes.
6. Remove municipality-specific hardcoded branches from the calculator.
7. Keep `store_pickup`/seller-direct only if formally modeled as DB-backed provider types and intentionally supported.
8. Add origin/destination test cases including same municipality, same province, different province, and outside-Laguna destinations.

**Acceptance Check**  
Changing location data in the DB changes the quote without editing PHP geography conditionals.

---

### PROBLEM 35 — Shipping quote must remain non-authoritative until final server calculation

**Problem**  
The quote/token system is useful for consistency but must not become a financial authority.

**Solution**  
Keep quote tokens as short-lived consistency proofs while recalculating final charges server-side.

**Implementation**
1. Bind quote token to seller, address, cart hash, and timestamp.
2. Enforce expiry.
3. On order placement, reload the address/cart/product data.
4. Recalculate shipping from current server data.
5. Compare against the quote only for consistency/audit, not authority.
6. Persist an immutable `order_shipping` snapshot.

**Acceptance Check**  
A modified quote amount/token cannot force an incorrect final shipping fee.

---

## PHASE 5 — ANALYTICS

### PROBLEM 36 — Synthetic analytics values are presented as actual metrics

**Problem**  
`AnalyticsController` includes generated values such as `$activeOrders->count() * 1.2` and a minimum views floor, which are not actual observed metrics.

**Solution**  
Separate real metrics from demo/estimated metrics.

**Implementation**
1. Remove synthetic calculations from production analytics endpoints.
2. Calculate checkouts/orders/revenue from authoritative order/payment data.
3. Calculate views from validated event data.
4. If a prototype needs demo values, mark them explicitly as demo/estimated and keep them out of production dashboards.
5. Add metric-definition documentation.

**Acceptance Check**  
Every production dashboard metric can be traced to stored events/transactions or an explicitly documented mathematical aggregation.

---

### PROBLEM 37 — Public funnel event endpoint can be poisoned

**Problem**  
`trackProductFunnelEvent()` accepts event types from clients without a clearly enforced whitelist and can be abused for arbitrary/high-volume analytics events.

**Solution**  
Use a strict event schema, authorization where appropriate, rate limiting, and deduplication.

**Implementation**
1. Create an enum/allow-list of valid event types.
2. Reject unknown values.
3. Add rate limiting.
4. Require authentication for business-critical funnel events.
5. Store actor/session context safely.
6. Deduplicate repeated events within a defined time window.
7. Never accept client-supplied authoritative totals/revenue.

**Acceptance Check**  
Unknown event types and uncontrolled event floods are rejected/throttled.

---

### PROBLEM 38 — Product view counter can be inflated by refresh/bots

**Problem**  
`getProductById()` increments views on every read and creates a view record without sufficient de-duplication/rate control.

**Solution**  
Separate raw requests from valid view events.

**Implementation**
1. Generate a session/visitor key appropriate to privacy requirements.
2. Define a time-window deduplication rule.
3. Rate-limit repeated reads.
4. Keep raw request logs separate from business analytics when needed.
5. Aggregate views from validated `product_views` events.

**Acceptance Check**  
Refreshing a product repeatedly does not create an unlimited number of counted views in the defined analytics window.

---

## PHASE 6 — AUTHENTICATION AND ACCOUNT STATE

### PROBLEM 39 — Seller email verification is conflated with admin approval

**Problem**  
`SellerMiddleware` can populate `email_verified_at` when `isVerified` and account status indicate admin approval/activation.

**Solution**  
Track email ownership, admin approval, and account status as separate state dimensions.

**Implementation**
1. Preserve `email_verified_at` only for a successful email OTP verification.
2. Keep seller/admin approval fields separate.
3. Keep account active/suspended status separate.
4. Update middleware to require the appropriate independent states.
5. Review registration, rejection, re-approval, and resend-OTP flows.
6. Add state-transition tests.

**Acceptance Check**  
Admin approval alone cannot create a false email verification timestamp.

---

### PROBLEM 40 — OTP codes can be logged in debug mode

**Problem**  
`EmailNotificationService` can log full verification codes when debug/local conditions are enabled, while `.env.example` has `APP_DEBUG=true`.

**Solution**  
Make OTP logging explicitly local/test-only and never available in production.

**Implementation**
1. Add a dedicated `LOG_OTP_CODES=false` configuration flag.
2. Enable only in local/test environments.
3. Never log full OTPs in staging/production.
4. Mask codes if diagnostics are needed.
5. Review logs after deployment to ensure secrets do not appear.

**Acceptance Check**  
Production logs never contain usable verification codes.

---

### PROBLEM 41 — All proxies are trusted

**Problem**  
`bootstrap/app.php` uses `trustProxies(at: '*')`, which can make forwarded host/scheme/IP headers authoritative from unexpected sources.

**Solution**  
Trust only the real reverse proxies/load balancers.

**Implementation**
1. Identify deployment proxy addresses/ranges.
2. Replace `*` with the actual trusted proxy configuration.
3. Configure trusted hosts/schemes as appropriate.
4. Verify URL generation, secure cookies, IP logging, and rate limiting behind the real proxy.

**Acceptance Check**  
A direct client cannot spoof security-relevant forwarded headers as though they came from a trusted proxy.

---

## PHASE 7 — FRONTEND AND API CONTRACT CLEANUP

### PROBLEM 42 — Frontend contains legacy API paths that no longer match controllers

**Problem**  
Components such as `address-manager.blade.php` call endpoints whose route bindings currently reference missing controller methods, while other components mix API and session-web patterns.

**Solution**  
Define one canonical frontend contract for each feature.

**Implementation**
1. Inventory all `fetch()`, Axios, form `action`, and AJAX URLs.
2. Map every call to an actual current route.
3. Update Address, Refund, Analytics, Orders, Reports, and Shipping components.
4. Prefer Laravel route helpers for web endpoints.
5. Use `/api/v1` only for true API clients.
6. Remove stale endpoints after consumers migrate.

**Acceptance Check**  
No frontend component calls an endpoint absent from the authoritative route list.

---

### PROBLEM 43 — Orders-management component calls a missing endpoint

**Problem**  
`orders-management.blade.php` uses `/api/v1/orders/my-orders`, while the current API route set does not expose that endpoint.

**Solution**  
Either point the component to the canonical current order endpoint or add the endpoint intentionally to the API contract.

**Implementation**
1. Decide whether the component is a web session component or an API client.
2. For web session use, call a route/controller designed for the Blade page.
3. For API use, add `/api/v1/orders/my-orders` with explicit authorization and pagination.
4. Update tests and documentation.

**Acceptance Check**  
The orders management UI loads orders without a 404 and cannot access another user's orders.

---

### PROBLEM 44 — Hard-coded frontend URLs make route drift easier

**Problem**  
Direct strings such as `/checkout/selected` make endpoint renames easy to miss.

**Solution**  
Use Laravel-generated route URLs where Blade is available and one centralized API route contract where JS is external.

**Implementation**
1. Replace Blade hard-coded internal URLs with `route()`.
2. Expose named route URLs to Alpine/JS when needed.
3. Keep API base path/version in one configuration point.
4. Remove duplicate literal URLs from components.

**Acceptance Check**  
Renaming an internal web route requires one server-side route change plus generated URL resolution, not a repository-wide string hunt.

---

## PHASE 8 — DOCUMENTATION AND SOURCE-OF-TRUTH CLEANUP

### PROBLEM 45 — README describes Laravel 11 while the project is Laravel 12

**Problem**  
The README and dependency configuration are inconsistent.

**Solution**  
Rewrite setup documentation from the actual current project.

**Implementation**
1. State Laravel 12 and current PHP requirements from `composer.json`.
2. Document the real `backend-laravel/` structure.
3. Remove claims about files/configuration that are not shipped.
4. Document `.env.example` accurately.
5. Document Composer/Vite/build steps from the current repository.

**Acceptance Check**  
A new developer following README instructions can reach the current Laravel application without relying on obsolete architecture.

---

### PROBLEM 46 — `docs/API.md` still documents Express/Node APIs

**Problem**  
The API documentation references Node/Express paths and `localhost:5000`, which do not describe the current Laravel API.

**Solution**  
Replace the document with a route-derived Laravel API reference.

**Implementation**
1. Document current `/api/v1` endpoints only.
2. Include middleware/auth requirements.
3. Include request/response examples from actual controllers/resources.
4. Document payment/shipping/order state transitions.
5. Remove Express/Node references unless they are genuinely still deployed.

**Acceptance Check**  
Every documented API endpoint exists in the current route list.

---

### PROBLEM 47 — `docs/SYSTEM_MAP.md` describes an obsolete directory architecture

**Problem**  
The system map refers to `/backend`, `/frontend`, and `/flutter_app`, while the archive is primarily a Laravel `backend-laravel` system with Blade/Alpine frontend.

**Solution**  
Regenerate the system map from the current application structure.

**Implementation**
1. Document actual top-level directories.
2. Show request flow from Blade/API → routes → controllers → services → models → DB.
3. Document external services such as Gemini, mail, storage, payment providers, and Reverb.
4. Document web/API boundaries and middleware.

**Acceptance Check**  
The system map matches the checked-in directories and route architecture.

---

## PHASE 9 — TESTING AND CI

### PROBLEM 48 — No executed runtime validation is guaranteed by the repository alone

**Problem**  
The uploaded archive lacks `vendor/`, so static syntax success does not prove Laravel can boot, routes resolve, migrations work, or feature tests pass.

**Solution**  
Make a clean-environment CI pipeline mandatory.

**Implementation**
1. Install Composer dependencies from lockfile.
2. Run PHP syntax/static checks.
3. Boot Laravel and run `php artisan route:list`.
4. Run `php artisan migrate:fresh --seed` against a test database.
5. Run PHPUnit/Pest test suite.
6. Run `npm ci` and `npm run build`.
7. Fail CI on route/controller mismatches, migration failures, test failures, or frontend build failures.

**Acceptance Check**  
A clean checkout from the repository can build and test without undocumented manual database fixes.

---

### PROBLEM 49 — Missing security/business regression coverage

**Problem**  
The project has a meaningful test suite, but the newly identified financial, authorization, file-security, route, shipping, and payment edge cases need explicit regression tests.

**Solution**  
Add tests for every high-risk defect before marking it fixed.

**Implementation**
Create tests for:
1. client price tampering
2. client seller-ID tampering
3. client subtotal/total tampering
4. manipulated shipping fee
5. multi-seller injection
6. selected checkout route
7. route/controller consistency
8. refund item from another order
9. private file authorization
10. path traversal
11. public seller-resource field whitelist
12. receipt detected reference mismatch
13. unreadable receipt reference → REVIEW
14. REVIEW blocks fulfillment
15. duplicate reference race
16. customer cannot direct-complete
17. `To Ship` does not verify payment
18. shipping default address fallback
19. fresh migrations from zero
20. frontend build and endpoint smoke tests

**Acceptance Check**  
Each repaired high-risk defect has a regression test that fails before the fix and passes after it.

---

## PHASE 10 — CANONICAL ARCHITECTURE REFACTOR

### PROBLEM 50 — Multiple sources of business truth exist

**Problem**  
Order creation, payment verification, fulfillment transitions, seller serialization, uploads, and shipping still have multiple code paths.

**Solution**  
Create one canonical implementation for each business authority.

**Implementation**

### Canonical order service
```text
CheckoutController ─┐
OrderController    ─┼──> CreateOrderService
Future API          ─┘         │
                               ├─ product lock
                               ├─ price validation
                               ├─ seller validation
                               ├─ stock validation
                               ├─ shipping calculation
                               ├─ payment transaction
                               ├─ order/order items
                               ├─ order shipping
                               └─ audit trail
```

### Canonical payment state machine
```text
UNVERIFIED
   ↓
REVIEW ─────→ REJECTED
   ↓
VERIFIED
   ↓
fulfillment may proceed
```

### Canonical fulfillment state machine
```text
Pending
  ↓
To Ship
  ↓
Shipped
  ↓
In Transit
  ↓
Delivered
  ↓
Completed
```

### Canonical seller public representation
```text
PublicSellerResource
```

### Canonical file policy
```text
Product media      → public/media storage
KYC documents      → private
Payment proofs     → private
Refund proofs      → private
Packing proofs     → private/restricted
```

**Acceptance Check**  
A business rule can be changed in one authoritative service/state machine without having to update multiple competing implementations.

---

# Recommended implementation order

## Step 1 — Financial and authorization lockdown

Implement Problems **01–16** first. Do not continue with cosmetic cleanup while client-controlled money, payment verification, or private-document access remains exploitable.

## Step 2 — Route and database stabilization

Implement Problems **17–35**. The application must have one route contract and one schema generation before deeper frontend refactoring.

## Step 3 — Analytics and account-state hardening

Implement Problems **36–41**.

## Step 4 — Frontend/API migration

Implement Problems **42–44** after canonical backend endpoints are stable.

## Step 5 — Documentation and CI

Implement Problems **45–49** so future contributors cannot reintroduce the same drift.

## Step 6 — Architectural consolidation

Implement Problem **50** continuously across the earlier phases, then remove obsolete duplicate implementations only after regression tests pass.

---

# Definition of Done

The project should only be considered stabilized when all of the following are true:

- Every route points to a real controller method.
- Every frontend endpoint exists and uses the intended middleware.
- Checkout financial values are server-authoritative.
- Product ownership/seller IDs are server-derived.
- Payment verification is independent of fulfillment state.
- Receipt PASS requires a detected reference that matches the submitted reference.
- REVIEW cannot authorize fulfillment.
- Duplicate payment references cannot be claimed twice.
- KYC/payment/refund/packing files are private or explicitly authorized.
- Public APIs use explicit resources instead of raw `User` serialization.
- AI calls verify TLS and public AI endpoints are authenticated/throttled appropriately.
- Shipping geography/rates are DB-driven and address fields use one schema convention.
- Refunds can reference only items belonging to the selected order.
- Analytics contain real, auditable metrics.
- Seller email verification is not inferred from admin approval.
- The database can be rebuilt from migrations and seeders alone.
- README/API/SYSTEM_MAP describe the actual Laravel 12 system.
- CI runs migrations, route checks, tests, and frontend build successfully.


# UPDATED MASTER ADDENDUM — CLAUDE RUNTIME FINDINGS INTEGRATED

**Update date:** 2026-09-28

This addendum merges the additional findings from the Claude runtime audit into the original 50-problem plan. Overlapping problems were not duplicated. The existing Problem 01, 12, and 16 are additionally marked below as runtime-confirmed based on the reported sandbox reproduction.

## EVIDENCE STATUS

### Runtime-reported / reproduced
- **Problem 01 — Checkout trusts client-supplied product price:** reproduced by creating a ₱750 product order for ₱1.00.
- **Problem 01 — Negative quantity:** reproduced with quantity `-5`, producing a negative total under the tested path.
- **Problem 12 — Path traversal:** `/uploads/{path}` reportedly returned a `.env` file in the sandbox test; production exploitability still depends on web-server URL/path handling.
- **Problem 16 — Unauthenticated AI routes:** anonymous requests reportedly returned successful responses for several AI endpoints.
- **Problems 22–23 — Checkout routing defects:** reproduced as frontend/backend route mismatches.
- **Problem 14 — Disabled TLS verification:** confirmed in source.

### Source-confirmed / static
- Missing controller methods behind multiple routes.
- Duplicate `/api/v1` route surfaces.
- Refund model/schema mismatch.
- Shipping `is_default` vs `isDefault` mismatch.
- Multiple sources of order/payment/business truth.

### Requires runtime verification
- Full Sanctum token lifecycle.
- MySQL-specific foreign-key behavior for the report timeline migration.
- Whether server/proxy configuration blocks encoded path traversal in production.
- Whether existing account-level login lockout mitigates the absence of route throttling.

---

# ADDITIONAL PROBLEMS FROM RUNTIME AUDIT

## PHASE 0D — ADDITIONAL SECURITY AND DATA-INTEGRITY BLOCKERS

### PROBLEM 51 — Inventory concurrency protection must be regression-tested across every checkout path

**Problem**  
The current main checkout already uses transactional locking in important locations, but Gemini/Claude identified inventory race conditions as a key risk area. The risk becomes real again if another legacy checkout/API path bypasses the locking logic.

**Solution**  
Make stock validation and deduction a single atomic operation shared by all order-creation entry points.

**Implementation**
1. Keep product/variation row locking inside the canonical `CreateOrderService`.
2. Lock every stock-bearing row before checking availability.
3. Re-check stock after locking.
4. Deduct stock only inside the same transaction that creates the order.
5. Remove duplicate stock-deduction implementations from controllers.
6. Add concurrent checkout feature tests against the same SKU/variation.
7. Test both web checkout and `/api/v1/orders` (or remove the legacy endpoint).

**Acceptance Check**  
Two simultaneous checkout attempts cannot drive available stock below zero, and at most the available quantity is successfully sold.

---

### PROBLEM 52 — File upload validation must verify content, not just filename/extension

**Problem**  
Verification documents, payment proofs, product media, avatars, banners, and similar uploads are security-sensitive. Client-provided filenames and extensions are not trustworthy indicators of actual content.

**Solution**  
Use server-side MIME/content validation, size limits, safe generated filenames, and storage isolation.

**Implementation**
1. Inventory every upload endpoint in `UploadController`, product-management controllers, refund/return flows, and profile/banner flows.
2. Validate uploaded files using Laravel file validation with appropriate MIME constraints.
3. Reject executable/script content and unsupported formats.
4. Generate server-side filenames; never use raw user filenames as storage keys.
5. Store private documents outside public web roots.
6. Re-encode image uploads when feasible to normalize content.
7. Add malformed-file and spoofed-extension tests.

**Acceptance Check**  
A `.php` or renamed executable disguised as an allowed extension cannot be stored as an executable/publicly served asset.

---

### PROBLEM 53 — Financial calculations need deterministic centavo-safe arithmetic

**Problem**  
Commission, shipping, discounts, and order totals can accumulate rounding differences when implemented with floating-point arithmetic.

**Solution**  
Use integer centavos for authoritative money storage/calculation, or a consistently configured decimal strategy.

**Implementation**
1. Inventory every money column and calculation in orders, order items, shipping, commissions, refunds, and payment transactions.
2. Prefer integer centavos for application-level arithmetic where practical.
3. Where database decimal columns are retained, normalize rounding at explicit boundaries.
4. Define one rounding policy for unit prices, percentages, shipping, discounts, and final totals.
5. Store the exact charged/server-authoritative total as a snapshot.
6. Add tests for values such as 0.01, percentage commissions, repeated fractional shipping amounts, and multi-item totals.

**Acceptance Check**  
The same order always produces exactly the same stored subtotal, shipping, commission, refund, and final total without floating-point drift.

---

### PROBLEM 54 — Soft-deleted users may conflict with unique email validation

**Problem**  
The database supports soft deletion, but ordinary `unique:users,email` validation may still treat soft-deleted rows as active records.

**Solution**  
Define explicit uniqueness rules that ignore soft-deleted rows while still preventing duplicate active accounts.

**Implementation**
1. Audit registration, email change, restore, and admin-user-edit validation rules.
2. Use `Rule::unique('users', 'email')->whereNull('deleted_at')->ignore($user->id)` where appropriate.
3. Confirm the database unique index strategy matches the intended lifecycle.
4. Add tests for active duplicate, soft-deleted duplicate, restore, and email-change scenarios.

**Acceptance Check**  
A soft-deleted account does not unnecessarily block a new active registration, while two active accounts still cannot share an email.

---

### PROBLEM 55 — No consistent endpoint-level rate limiting

**Problem**  
The runtime audit reports no `throttle` middleware and repeated login failures were accepted in rapid succession. High-cost AI, OTP, upload, and authentication endpoints are vulnerable to abuse if account-level controls fail or are bypassed.

**Solution**  
Apply Laravel rate limiters based on endpoint purpose, identity, IP, and cost.

**Implementation**
1. Define named rate limiters in `RouteServiceProvider` or the current Laravel rate-limiter configuration.
2. Apply strict limits to login, registration, password reset, OTP send/resend, email verification, and verification checks.
3. Apply separate limits to AI generation, receipt verification, payment-reference checking, and image processing.
4. Apply upload limits and file-size limits.
5. Combine IP-based and authenticated-user/account-based keys where appropriate.
6. Return standard `429` responses and clear retry information.
7. Keep the existing OTP 60-second cooldown/5-minute validity/5-per-hour business rules as additional safeguards rather than replacements.

**Acceptance Check**  
Rapid abusive requests are consistently rate-limited and legitimate users can still complete normal authentication/verification workflows.

---

### PROBLEM 56 — Sanctum API authentication appears incomplete

**Problem**  
The project uses `auth:sanctum`, but the runtime audit reports no `personal_access_tokens` migration and no `config/sanctum.php`. The actual token issuance/consumption lifecycle must therefore be verified before treating `/api/v1` as a supported API.

**Solution**  
Choose one canonical API authentication strategy and make it complete end-to-end.

**Implementation**
1. Audit `AuthController::login`, token creation, token revocation, and `User::tokens()` usage.
2. If Sanctum is intended, install/configure the complete supported Sanctum stack and create the token table through migrations.
3. Add feature tests for login → token issuance → authenticated API request → logout/revocation.
4. Explicitly define abilities/scopes if different API roles need different permissions.
5. If `/api/v1` is legacy and unused, remove the routes and stale documentation instead of leaving a partially configured auth system.

**Acceptance Check**  
Every protected API endpoint either has a tested working authentication mechanism or is removed from the supported surface.

---

### PROBLEM 57 — State-changing GET routes and CSRF-exempt logout reduce request integrity

**Problem**  
The audit identified GET routes for product approval/rejection and logout routes exempted from normal CSRF protection. GET should not be used for destructive or state-changing operations.

**Solution**  
Use appropriate HTTP methods and preserve CSRF protection for session-authenticated state changes.

**Implementation**
1. Change product approval/rejection endpoints to `POST`/`PATCH`/`PUT` according to the action semantics.
2. Remove unnecessary CSRF exemptions from logout and other state-changing web routes.
3. Update Blade forms/fetch calls to send the correct method and CSRF token.
4. Add tests proving unauthenticated, cross-site, and GET requests cannot change state.
5. Review all other GET routes for hidden mutations.

**Acceptance Check**  
A GET request alone cannot approve, reject, delete, logout, or otherwise mutate protected application state.

---

### PROBLEM 58 — User model fillable fields create latent privilege-escalation risk

**Problem**  
`User::$fillable` includes sensitive fields such as `id`, `role`, `status`, and `isVerified`. This may not currently be exploitable, but any future use of `$request->all()` or unfiltered mass assignment could allow privilege escalation.

**Solution**  
Make privileged attributes non-mass-assignable and update them only through explicit server-controlled operations.

**Implementation**
1. Remove `id`, `role`, `status`, `isVerified`, and similarly privileged fields from `$fillable`.
2. Use explicit property assignment for admin-controlled state changes.
3. Define request DTOs/Form Requests containing only user-editable fields.
4. Search the project for `create($request->all())`, `update($request->all())`, `fill($request->all())`, and equivalent patterns.
5. Add authorization tests for role/status/isVerified manipulation attempts.

**Acceptance Check**  
A normal customer request cannot modify their own role, status, verification state, or identity-defining primary key.

---

### PROBLEM 59 — Receipt fallback logic can reject legitimate receipts based on filename/image heuristics

**Problem**  
When Gemini is unavailable, the fallback reportedly rejects images when filenames contain words such as `photo`, `picture`, or `model`, and can reject near-square images. This creates false negatives unrelated to the actual payment evidence.

**Solution**  
Make fallback verification evidence-based rather than filename- or shape-based.

**Implementation**
1. Remove filename keyword rejection logic.
2. Do not treat near-square dimensions as evidence of fraud or invalidity.
3. Use OCR as the first fallback for reference number, amount, provider name, date/time, and receipt markers.
4. If OCR cannot confidently read required evidence, return `REVIEW`, not `REJECT`, unless another rule clearly proves invalidity.
5. Log the exact reason for fallback decisions.
6. Add real and synthetic receipt-image fixtures covering common filenames and image dimensions.

**Acceptance Check**  
A legitimate receipt named `photo_2026-09-28.jpg` or a square screenshot is not rejected solely because of its filename or dimensions.

---

### PROBLEM 60 — Debug defaults can leak implementation details

**Problem**  
`.env.example` ships with `APP_DEBUG=true`, and debug error output can disclose paths, stack traces, SQL details, and environment information if deployed incorrectly.

**Solution**  
Use secure production defaults and explicitly document local-development overrides.

**Implementation**
1. Change production-safe example defaults to `APP_DEBUG=false`.
2. Ensure deployment documentation explicitly requires debug to be disabled.
3. Review exception rendering for sensitive endpoint responses.
4. Confirm logs are protected and do not expose secrets or OTPs.
5. Add deployment validation that rejects production configurations with debug enabled.

**Acceptance Check**  
Production-mode errors return generic safe responses without filesystem paths, stack traces, SQL, credentials, or internal implementation details.

---

### PROBLEM 61 — User-uploaded media and QA documents are committed to source control

**Problem**  
Avatars, banners, or other user-uploaded artifacts and a QA PDF are reportedly committed into the repository. This increases repository size and may expose personal or operational data.

**Solution**  
Keep runtime/user-generated files outside version control unless they are intentionally approved public fixtures.

**Implementation**
1. Inventory tracked media/document files.
2. Remove sensitive/user-generated artifacts from git history as appropriate.
3. Add correct `.gitignore` rules for runtime storage directories.
4. Keep only intentionally public static assets and test fixtures.
5. Move test-only documents to a dedicated fixture directory with synthetic/non-sensitive content.

**Acceptance Check**  
A fresh clone contains source-controlled assets only where their inclusion is intentional and documented.

---

### PROBLEM 62 — `User::hasSeenGuide()` performs schema discovery on every call

**Problem**  
Calling `Schema::hasColumn` during normal model behavior adds unnecessary database/schema inspection overhead and couples runtime user operations to schema metadata checks.

**Solution**  
Make schema compatibility a migration concern, not a per-request feature check.

**Implementation**
1. Confirm the target column exists in all supported migrated schemas.
2. Remove repeated runtime `Schema::hasColumn` checks from normal request paths.
3. Keep compatibility handling only where a deliberate migration transition requires it.
4. Add a migration test proving the column is present.

**Acceptance Check**  
Normal calls to `hasSeenGuide()` do not execute schema metadata queries repeatedly.

---

### PROBLEM 63 — Oversized controllers/services increase defect and authorization drift risk

**Problem**  
The largest classes include `WebAuthController` (~2,620 lines), `AdminController` (~1,849), and `AiService` (~1,670). Large mixed-responsibility classes make validation, authorization, business rules, and test coverage harder to reason about.

**Solution**  
Refactor around bounded business services without creating duplicate logic.

**Implementation**
1. Extract authentication workflows from `WebAuthController` into dedicated services/actions.
2. Split `AdminController` by domain: users, products, orders, reports, analytics, shipping, etc.
3. Split `AiService` into narrowly scoped services such as receipt verification, product assistance, recommendations, and AI chat where appropriate.
4. Keep one canonical implementation per business rule.
5. Add feature tests before and after each extraction.

**Acceptance Check**  
Refactoring reduces duplication and class size without changing business behavior, authorization, or API contracts unexpectedly.

---

# MASTER IMPLEMENTATION ORDER

Use the following execution order instead of fixing issues randomly:

### STEP 1 — Freeze unsafe financial/order creation

Implement Problems **01–10, 51, and 53** first.

The system must stop trusting client-controlled money, seller ownership, quantities, and payment acceptance before cosmetic or architectural cleanup.

### STEP 2 — Secure private files and uploads

Implement Problems **11, 12, 13, 52, and 61**.

Ensure KYC/payment/refund evidence is private and all uploads are content-validated before expanding features.

### STEP 3 — Secure AI and payment verification

Implement Problems **08, 09, 10, 14, 15, 16, 55, and 59**.

The receipt-reference rule must be enforced server-side and AI endpoints must be authenticated/rate-limited.

### STEP 4 — Repair and consolidate routing/API authentication

Implement Problems **17–25, 43, 44, 56, and 57**.

Decide whether `/api/v1` remains a supported API. Do not keep a partially functional duplicate API layer.

### STEP 5 — Normalize database truth

Implement Problems **26–30, 33, 39, 54, and 62**.

Make migrations, models, validation, and table/column names agree before adding more business features.

### STEP 6 — Harden shipping, refunds, and fulfillment

Implement Problems **31–35**, then retest all order-state/payment-state interactions.

### STEP 7 — Secure analytics and account controls

Implement Problems **36–41, 58, and 60**.

Metrics should be defensible, account states should be explicit, and privileged attributes should not be mass assignable.

### STEP 8 — Clean frontend/API contracts and documentation

Implement Problems **42, 43, 44, 45, 46, and 47**.

Update the frontend only after the canonical backend routes are established.

### STEP 9 — Repair and expand tests

Implement Problems **48 and 49**, plus the regression coverage specified in Problems **01, 08, 10, 12, 16, 51, 55, 56, 57, and 58**.

### STEP 10 — Consolidate the architecture

Implement Problem **50** and Problem **63** last, after behavior is protected by tests.

---

# REQUIRED RELEASE GATES

Before considering the system ready for deployment, all of the following must pass:

1. A manipulated product price cannot lower the order amount.
2. Zero/negative/non-integer quantities cannot create orders.
3. A submitted seller ID cannot override product ownership.
4. A client-supplied subtotal/total/shipping fee is never trusted.
5. Duplicate payment references cannot be claimed concurrently.
6. A receipt with a different reference cannot pass verification.
7. A receipt with no readable reference cannot pass solely from a typed reference.
8. `REVIEW` payments cannot be fulfilled.
9. Fulfillment transitions do not modify payment verification state.
10. Private KYC/payment/refund files cannot be downloaded without authorization.
11. Path traversal cannot escape the intended storage root.
12. AI endpoints cannot be called anonymously where authentication is required.
13. AI/OTP/login/payment-verification endpoints are rate-limited.
14. All supported API routes resolve to existing controller methods.
15. Sanctum either works end-to-end or the unused API surface is removed.
16. No state-changing operation is performed through an unsafe GET route.
17. Privileged user fields cannot be mass assigned.
18. A fresh database created entirely from migrations matches the application's expectations.
19. The checkout/cart frontend reaches real backend routes without 404/500 route drift.
20. Automated security and business-logic regression tests cover all previously confirmed vulnerabilities.

---

# FINAL DEVELOPMENT RULE

**Do not patch each symptom independently.** Every order, payment, shipping, stock, refund, seller, and account-state operation must have one authoritative backend implementation, one persistence model, and one tested authorization path. Frontend code should request or display those server-authoritative results rather than becoming a second source of business truth.

---

# NETWORK RESILIENCE ADDENDUM — SLOW INTERNET / INTERMITTENT CONNECTION

This section adds the network-resilience findings from the dedicated slow-internet audit. These are numbered **Problem 64–75** so the original 63 problems remain traceable.

## PHASE 0E — NETWORK AND TRANSACTION RESILIENCE BLOCKERS

### PROBLEM 64 — Receipt verification combines image upload and AI processing inside a 25-second browser timeout

**Problem**  
The checkout browser sends the receipt image directly to `/ai/receipt/verify` and aborts the request after 25 seconds. The timeout includes image upload time as well as backend/AI processing time. Slow mobile connections can therefore time out before the server finishes receiving or processing the receipt.

**Solution**  
Separate receipt upload from verification and give upload and verification independent lifecycle/status handling.

**Implementation**
1. Compress/downscale receipt images in the browser before upload while preserving reference/amount readability.
2. Upload the receipt to a private server endpoint first.
3. Return a `receipt_id`/verification UUID immediately after durable storage.
4. Perform AI/OCR verification against the stored receipt asynchronously or through a separate verification request.
5. Replace a single 25-second combined timeout with explicit upload, verification, and overall workflow timeouts.
6. Preserve resumable/retry-safe behavior using the receipt hash and verification ID.

**Acceptance Check**  
A receipt uploaded over a slow connection does not have to complete AI processing before the browser can move to the next state, and a temporarily slow upload does not cause the entire payment flow to be discarded.

---

### PROBLEM 65 — Gemini fallback model chain can outlive the browser timeout

**Problem**  
The server may try multiple Gemini Vision models sequentially, each with its own timeout. The browser can stop waiting while the server is still working, causing duplicated work, inconsistent UI state, and repeated AI calls when the customer retries.

**Solution**  
Use a bounded server-side verification budget and controlled fallback policy.

**Implementation**
1. Select one primary model and at most one intentional fallback for a receipt verification attempt.
2. Set explicit connect and total operation timeouts.
3. Retry only transient failures and only within a strict total time budget.
4. Record model attempts and final verification state.
5. Stop immediately on definitive mismatch/invalid evidence instead of trying more models.
6. Do not let a browser retry start a new AI chain when an existing verification job is already running.

**Acceptance Check**  
One receipt verification has a predictable maximum server processing window and repeated browser retries do not multiply the AI request chain.

---

### PROBLEM 66 — Browser timeout can be presented as `REVIEW` without distinguishing network failure from verification review

**Problem**  
When the client cannot complete receipt verification, the UI can move into a manual-review-like state. This can make a timeout, offline condition, server failure, and genuine AI `REVIEW` result look similar.

**Solution**  
Represent operational failures as separate states from payment evidence decisions.

**Implementation**
1. Define receipt lifecycle states such as `PENDING_UPLOAD`, `UPLOADED`, `VERIFYING`, `PASS`, `REVIEW`, `REJECT`, `UNAVAILABLE`, `FAILED`.
2. Store the authoritative state server-side.
3. Display `UNAVAILABLE`/`FAILED` distinctly from `REVIEW`.
4. If the business permits checkout while verification is unavailable, create an explicit manual-review payment state rather than pretending AI returned `REVIEW`.
5. Block fulfillment until the resulting payment state is approved through the intended process.

**Acceptance Check**  
Users and admins can tell whether a receipt was actually reviewed by AI, is pending review, or could not be verified because of a network/service failure.

---

### PROBLEM 67 — The same payment receipt is verified twice

**Problem**  
The browser verifies the receipt once and the final checkout request verifies/uploads it again. On slow networks this increases latency, bandwidth consumption, and AI usage while allowing two verification attempts to produce different results.

**Solution**  
Persist the uploaded receipt and its verification result and reference it from checkout.

**Implementation**
1. Create a server-side receipt verification record.
2. Calculate and store a SHA-256 file hash.
3. Associate the receipt with the authenticated customer and intended payment attempt.
4. Store detected reference, detected amount, provider evidence, confidence, result, and expiry.
5. Final checkout accepts only a server-issued verification ID, not client-generated AI result JSON.
6. Validate ownership, expiration, reference consistency, amount, and payment method before order creation.
7. Reuse the existing result unless a defined high-risk condition requires re-verification.

**Acceptance Check**  
One uploaded receipt causes at most one normal verification workflow, and final checkout does not re-upload/re-scan the same image unnecessarily.

---

### PROBLEM 68 — Final checkout lacks idempotency for slow responses and repeated submission

**Problem**  
A browser can successfully create an order on the server but fail to receive the response because of a slow or dropped connection. Refreshing or clicking again can submit another POST request. A UI flag such as `isPlacingOrder` protects only the current page instance.

**Solution**  
Make checkout submission idempotent on the server.

**Implementation**
1. Generate a unique `idempotency_key` for each logical checkout attempt.
2. Persist it with the customer and resulting order/attempt.
3. Add a unique database constraint for the appropriate customer/attempt scope.
4. Perform the idempotency lookup and order creation atomically.
5. On a repeated request with the same key, return the original order instead of creating another one.
6. Do not automatically retry non-idempotent checkout POST requests without the key.
7. Show an order-status recovery screen when the response is lost but the server-side attempt may already exist.

**Acceptance Check**  
Repeated submission of the same logical checkout attempt creates exactly one order for COD and online payment.

---

### PROBLEM 69 — Payment-reference availability checking is vulnerable to stale responses and silent network failures

**Problem**  
The reference-check AJAX request is debounced, but there is no clear request identity/cancellation strategy and some errors are silently ignored. Under slow internet, a previous response can arrive after a newer reference was entered.

**Solution**  
Make reference checking request-aware and server-authoritative.

**Implementation**
1. Cancel or supersede the previous reference request when input changes.
2. Assign a monotonically increasing request sequence number.
3. Update the UI only if the response belongs to the latest sequence.
4. Add an explicit timeout.
5. Display `checking`, `available`, `duplicate`, and `unavailable` states distinctly.
6. Always repeat the authoritative duplicate/transaction check at final checkout.
7. Never treat a missing response as `available`.

**Acceptance Check**  
Typing references quickly under an artificial high-latency network cannot cause an old duplicate/available result to overwrite the current reference state.

---

### PROBLEM 70 — Shipping quote requests can race and show stale fees after address changes

**Problem**  
Changing an address can trigger multiple quote requests. A slower older request can finish after the newer one and overwrite the UI with a quote for the wrong destination.

**Solution**  
Only the newest address/quote request may update checkout state.

**Implementation**
1. Add an `AbortController` for the current quote request.
2. Abort the previous request when address/cart/provider inputs change.
3. Generate a quote request sequence/version.
4. Ignore responses with an older sequence.
5. Clear stale quote results while a new authoritative quote is pending.
6. Keep server-side shipping recalculation at order placement as the final financial authority.

**Acceptance Check**  
After rapidly switching between addresses, the displayed shipping fee and selected provider always correspond to the latest selected address.

---

### PROBLEM 71 — Shipping, PSGC, and geocoding requests can remain pending indefinitely

**Problem**  
External location/shipping calls can lack explicit client-side timeouts. Slow or unavailable third-party services can leave selectors, address maps, or quote areas stuck in loading states.

**Solution**  
Use bounded requests with retry/fallback behavior and never make nonessential location services a hard dependency for checkout.

**Implementation**
1. Add standard timeout handling to PSGC requests.
2. Add timeout/cancellation to Nominatim/map requests.
3. Cache stable PSGC reference data locally or on the server where appropriate.
4. Provide a retry action.
5. Preserve user-entered address fields when geocoding fails.
6. Allow checkout with a valid saved/manual address when map/geocoding is unavailable, subject to existing address validation rules.
7. Treat shipping quote failure separately from address validation failure.

**Acceptance Check**  
A third-party location service outage does not permanently trap the user in a loading state or erase a valid address.

---

### PROBLEM 72 — Cart/add-to-cart mutations lack a common timeout and request-state contract

**Problem**  
Individual Blade views implement fetch behavior differently. Some operations recover UI state on failure, but timeout, retry, loading, and offline handling are inconsistent.

**Solution**  
Create one frontend request utility for application APIs.

**Implementation**
1. Add a shared request wrapper around `fetch()`.
2. Provide default timeout and cancellation support.
3. Standardize JSON/error parsing.
4. Expose `loading`, `success`, `network_error`, `timeout`, and `server_error` states.
5. Prevent uncontrolled duplicate mutation requests.
6. Allow safe retry only for explicitly idempotent operations.
7. Keep the server authoritative for quantity, price, stock, and cart state.

**Acceptance Check**  
Cart/add-to-cart interactions behave consistently under latency, packet loss, timeout, and server-error simulation.

---

### PROBLEM 73 — Chat and notification polling can create request backlogs on slow connections

**Problem**  
Fixed-interval polling can start a new request before the previous request completes. Under a 3–8 second response time, multiple overlapping requests can accumulate and return out of order.

**Solution**  
Use completion-based polling with in-flight guards and adaptive backoff.

**Implementation**
1. Replace `setInterval()` polling with a recursive `fetch → process → schedule` pattern.
2. Ensure only one poll is active per resource.
3. Abort stale polling on page/component destruction.
4. Increase polling delay during slow responses or when the browser is hidden.
5. Back off after repeated network failures.
6. Resume promptly after reconnecting.

**Acceptance Check**  
A slow connection never creates multiple simultaneous chat/notification polling requests for the same resource.

---

### PROBLEM 74 — Session heartbeat and other background requests lack consistent timeout behavior

**Problem**  
The session heartbeat has protection against overlapping requests, but a request can still remain pending for too long. A generic network failure must not be mistaken for session expiration.

**Solution**  
Introduce bounded heartbeat requests and explicit network-vs-auth error classification.

**Implementation**
1. Abort heartbeat after a defined timeout.
2. Treat `401`/explicit session termination as authentication failure.
3. Treat network timeout/offline as a connectivity state, not an automatic logout.
4. Retry heartbeat after reconnect with backoff.
5. Apply the same classification to other background session checks.

**Acceptance Check**  
Temporary connectivity loss does not unexpectedly log a customer out, while an actual invalid/expired session still redirects correctly.

---

### PROBLEM 75 — Synchronous email delivery can delay responses after successful transactions

**Problem**  
Email notifications are sent synchronously. A slow SMTP connection can make a user-facing request appear to hang even though the database transaction has already succeeded.

**Solution**  
Queue non-critical email notifications after the transaction commits.

**Implementation**
1. Configure a real queue backend appropriate for deployment.
2. Convert email notifications to queued jobs.
3. Dispatch notifications after successful transaction commit.
4. Add retry/backoff rules for transient SMTP failures.
5. Ensure notification job failure does not roll back an already-created order.
6. Record notification status separately from order/payment state.
7. Avoid sending duplicate emails when the same event is retried by using an event/notification identifier.

**Acceptance Check**  
A slow SMTP server cannot keep a completed checkout request open indefinitely, and the order remains valid even when an email delivery attempt fails.

---

# NETWORK-RESILIENCE IMPLEMENTATION FOUNDATION

The 12 network problems above should not be solved as 12 unrelated patches. Build the following common infrastructure first.

## A. Shared frontend HTTP client

Create a Vite-bundled utility such as:

`resources/js/services/httpClient.js`

Responsibilities:
- default timeout
- AbortController support
- JSON parsing
- HTTP error normalization
- retry classification
- request IDs
- idempotency-key support for mutations
- connectivity-state updates
- standardized toast/error handling

Only explicitly safe/idempotent GET/HEAD operations should automatically retry.

## B. Network state manager

Create a small shared state mechanism for:
- `online`
- `offline`
- `reconnecting`
- `server_unavailable`

Use `navigator.onLine` as a UI hint, not as proof that the backend is reachable. Confirm recovery through an actual lightweight request.

## C. Server idempotency middleware/service

Implement one reusable mechanism for high-risk mutations:
- checkout
- payment submission/claim
- seller payout/commission actions where applicable
- refund creation where duplicate submission is harmful

The mechanism should store:
- authenticated user
- idempotency key
- endpoint/action
- request fingerprint where useful
- final response/order reference
- expiry

## D. Payment verification state machine

Use one server-owned state machine:

`PENDING_UPLOAD → UPLOADED → VERIFYING → PASS / REVIEW / UNAVAILABLE / REJECT`

Then map only approved states into order/payment authorization.

## E. Queue long-running/noncritical work

Use queues for:
- email notifications
- non-blocking AI work where feasible
- image processing
- notification fan-out
- other operations that do not need to complete before a transaction response

Never move security-critical authorization itself into an asynchronous step without a durable status model.

---

# REVISED MASTER EXECUTION ORDER

### STEP 1 — Secure checkout and payment authority

Implement Problems **01–10, 51, 53, 64, 65, 66, 67, 68, and 69**.

The first production gate is that slow internet cannot turn an uncertain payment state, duplicate submission, or client-controlled financial value into an invalid order.

### STEP 2 — Secure private files and uploads

Implement Problems **11, 12, 13, 52, and 61**.

### STEP 3 — Secure AI and resource-intensive endpoints

Implement Problems **14–16, 55, and 59**, then apply the shared frontend request/time-limit strategy from Problems **64–75**.

### STEP 4 — Repair routing and API authentication

Implement Problems **17–25, 43, 44, 56, and 57**.

### STEP 5 — Normalize database/schema truth

Implement Problems **26–30, 33, 39, 54, and 62**.

### STEP 6 — Harden shipping, location, refund, and fulfillment

Implement Problems **31–35 and 70–71**.

### STEP 7 — Add global network-resilience infrastructure

Implement Problems **72–75** and the shared HTTP client, network-state manager, idempotency service, polling/backoff strategy, and queue configuration.

### STEP 8 — Secure analytics and account controls

Implement Problems **36–41, 58, and 60**.

### STEP 9 — Clean frontend/API contracts and documentation

Implement Problems **42, 43, 44, 45, 46, and 47**.

### STEP 10 — Repair and expand automated testing

Implement Problems **48–49** plus regression tests for every confirmed security, financial, workflow, and network-resilience defect.

### STEP 11 — Refactor after behavior is protected

Implement Problems **50 and 63** only after the canonical service boundaries and regression tests are stable.

---

# REQUIRED SLOW-INTERNET TEST MATRIX

Before production, test the system under at least these conditions:

| Scenario | Required Result |
|---|---|
| 2G/very slow upload | Receipt workflow remains recoverable |
| 3G with 5–10 MB receipt | Upload has visible progress/retry behavior |
| 10–20 sec Gemini latency | UI remains in `VERIFYING`, not false success |
| Gemini timeout | `UNAVAILABLE`/manual-review state is explicit |
| Gemini model failure | Bounded fallback, no runaway sequential calls |
| Connection drops during receipt upload | Upload can be retried without corrupting payment state |
| Connection drops after order commit | Retrying same checkout key returns original order |
| Double-click Place Order | One order only |
| Browser refresh during checkout | No duplicate order |
| Reference typed rapidly | Old responses cannot overwrite new value |
| Slow shipping response | Older quote cannot overwrite newer address quote |
| PSGC unavailable | Saved/manual address remains usable |
| Nominatim unavailable | Checkout is not permanently blocked by map service |
| Slow chat polling | No overlapping polls |
| Offline during heartbeat | User is not falsely logged out |
| Slow SMTP | Checkout response is not blocked by email delivery |
| CDN unavailable | Critical application functionality has a defined fallback |
| Server 500/503 | User gets a recoverable error state, not a blank/hanging page |
| Network reconnect | Safe pending/read operations resume appropriately |

---

# FINAL RELEASE GATES — UPDATED

In addition to the original security/business gates, the network-resilient implementation must satisfy:

21. Receipt upload and AI verification are separate recoverable stages.
22. A receipt verification attempt has a bounded total server time budget.
23. The same receipt is not unnecessarily re-uploaded/re-scanned at final checkout.
24. Browser timeout never becomes proof of payment verification.
25. Final checkout is idempotent and duplicate submissions return the original order/attempt.
26. Payment-reference checking ignores stale responses and distinguishes unavailable from available.
27. Shipping quote responses are ordered/cancelable and cannot overwrite a newer address quote.
28. PSGC/geocoding calls have bounded timeouts and retry/fallback behavior.
29. Cart/add-to-cart requests use consistent timeout and loading/error semantics.
30. Polling never creates uncontrolled overlapping requests.
31. Network failures do not automatically trigger logout unless the backend explicitly reports an invalid session.
32. User-facing transactions are not blocked by synchronous email delivery.
33. Slow/offline behavior is covered by automated regression tests or reproducible browser/network tests.

---

# MASTER STATUS CLASSIFICATION

For implementation tracking, every problem should carry one evidence label:

- **RUNTIME-CONFIRMED** — reproduced by executing the application.
- **SOURCE-CONFIRMED** — directly verified in the uploaded source.
- **REQUIRES-RUNTIME-VERIFICATION** — plausible/structural issue that needs a specific environment test.
- **ARCHITECTURE/HYGIENE** — maintainability or documentation issue rather than an immediate exploit.

Do not mark a hypothesis as a confirmed vulnerability until its exact execution path is demonstrated.

---

# FINAL ARCHITECTURE TARGET

The finished system should have these properties:

**One authoritative checkout service**  
All web/API order creation enters the same server-side calculation and transaction pipeline.

**One authoritative payment state machine**  
Receipt evidence, reference uniqueness, amount matching, AI/OCR outcomes, manual review, and payment verification are persisted and auditable.

**One authoritative shipping calculation**  
Frontend quotes are advisory; final server recalculation is authoritative, and stale browser responses cannot overwrite newer selections.

**One authoritative request strategy**  
Frontend requests use shared timeouts, cancellation, safe retry rules, error states, and idempotency for dangerous mutations.

**One authoritative database schema**  
Migrations, models, validation rules, and production schema agree.

**One authoritative authorization model**  
Session/API authentication and role permissions protect every supported endpoint; legacy duplicates are removed.

**Graceful degradation under weak internet**  
The system should fail into recoverable states instead of hanging, duplicating transactions, falsely confirming payments, losing user input, or silently discarding successful server operations.
