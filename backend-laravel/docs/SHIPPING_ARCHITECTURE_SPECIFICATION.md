# Multi-Provider Logistics Engine Specification & Implementation Contract

## 1. System Intent & Core Architectural Principles

This document defines the strict engineering contract for migrating from the legacy single-seller-fee shipping model to a dynamic, database-driven, multi-provider logistics architecture.

### Prohibited Anti-Patterns (Explicit Negative Constraints)
To ensure the implementation remains completely configurable, scalable, and defensible for academic panel evaluation:
1. **NO Hardcoded Couriers**: The codebase MUST NOT contain hardcoded conditional statements checking for courier codes (e.g. `if ($provider === 'jnt')` or `switch ($providerCode)`). All rate rules, volumetric divisors, and active statuses MUST be retrieved dynamically from the `shipping_providers` and `shipping_rates` tables.
2. **NO Hardcoded Geographic Zones**: The codebase MUST NOT contain hardcoded geographic mappings (e.g. `if ($province === 'Laguna') $zone = 'South Luzon';`). Geographic resolution MUST evaluate hierarchically against the `shipping_zone_areas` table.
3. **NO Seller-Entered Final Shipping Price**: Sellers only enter physical package specifications (`package_weight_per_unit`, `package_length_per_unit`, `package_width_per_unit`, `package_height_per_unit`, `handling_days`). Sellers do not input the final customer shipping fee for new products.
4. **NO Client-Authoritative Fees**: The server MUST NEVER accept a `shipping_fee` or total from the frontend. The frontend submits only the `selected_provider_id`. The server strictly recalculates the authoritative quote inside a database transaction during order placement.
5. **NO Product-Level Authoritative Fee**: `products.shippingFee` is strictly legacy and must never be the authoritative source for new orders.
6. **NO Historical Mutability**: Historical orders must remain completely unaffected when courier rates or zones change in the future. The order snapshot in `order_shipping` is final and immutable.
7. **NO Arbitrary Default Zone**: Unrecognized destinations must result in an unserviceable status, never a silent fallback to a generic or default zone.
8. **NO Blind `first()` Rate Lookups**: Queries for bracket rates MUST match the chargeable weight within the exact range: `min_weight <= chargeable_weight <= max_weight`.
9. **ISOLATION GUARANTEE**: The existing product gallery and variant architectures (images, variant options, sizes) MUST remain completely independent of this shipping refactor. No variation logic is to be coupled to the shipping engine.

---

## 2. Definitive Database Schema

```
products
├── package_weight_per_unit   (DECIMAL 8,2 in kg, default 0.00)
├── package_length_per_unit   (DECIMAL 8,2 in cm, default 0.00)
├── package_width_per_unit    (DECIMAL 8,2 in cm, default 0.00)
├── package_height_per_unit   (DECIMAL 8,2 in cm, default 0.00)
├── handling_days             (INT, default 2)
└── shippingFee               (DECIMAL 10,2, NULLABLE - retained strictly for legacy fallback)

shipping_providers
├── id                         (CHAR 36 / UUID)
├── name                       (VARCHAR 100) -> e.g. "J&T Express", "SPX Express", "LBC Express"
├── code                       (VARCHAR 50, UNIQUE) -> e.g. "jnt", "spx", "lbc"
├── default_volumetric_divisor (INT, default 3500)
├── is_active                  (BOOLEAN, default true)
└── timestamps

shipping_zones
├── id                         (CHAR 36 / UUID)
├── name                       (VARCHAR 100) -> e.g. "Metro Manila (NCR)", "North Luzon", "South Luzon", "Visayas", "Mindanao"
├── code                       (VARCHAR 50, UNIQUE) -> e.g. "NCR", "LUZ_N", "LUZ_S", "VIS", "MIN"
└── timestamps

shipping_zone_areas
├── id                         (CHAR 36 / UUID)
├── zone_id                    (CHAR 36, FK -> shipping_zones.id ON DELETE CASCADE)
├── postal_code                (VARCHAR 10, NULLABLE) -> Exact normalized postal code
├── postal_code_prefix         (VARCHAR 10, NULLABLE) -> Prefix for regional grouping
├── province                   (VARCHAR 100)
├── city                       (VARCHAR 100, NULLABLE)
├── barangay                   (VARCHAR 100, NULLABLE)
└── timestamps

seller_shipping_providers
├── id                         (CHAR 36 / UUID)
├── seller_id                  (CHAR 36, FK -> users.id ON DELETE CASCADE)
├── provider_id                (CHAR 36, FK -> shipping_providers.id ON DELETE CASCADE)
├── is_enabled                 (BOOLEAN, default true)
└── timestamps

shipping_rates
├── id                         (CHAR 36 / UUID)
├── provider_id                (CHAR 36, FK -> shipping_providers.id ON DELETE CASCADE)
├── origin_zone_id             (CHAR 36, FK -> shipping_zones.id ON DELETE CASCADE)
├── destination_zone_id        (CHAR 36, FK -> shipping_zones.id ON DELETE CASCADE)
├── min_weight                 (DECIMAL 8,2, default 0.00)
├── max_weight                 (DECIMAL 8,2, NULLABLE) -> NULL represents open-ended / unlimited upper bound
├── base_rate                  (DECIMAL 10,2)
├── additional_weight_rate     (DECIMAL 10,2, default 0.00)
├── volumetric_divisor         (INT, NULLABLE) -> If NULL, falls back to provider default
├── estimated_days_min         (INT, default 2)
├── estimated_days_max         (INT, default 4)
├── is_active                  (BOOLEAN, default true)
└── timestamps

order_shipping
├── id                                  (CHAR 36 / UUID)
├── order_id                            (CHAR 36, UNIQUE, FK -> orders.id ON DELETE CASCADE)
├── provider_id                         (CHAR 36, FK -> shipping_providers.id)
├── provider_name                       (VARCHAR 100) -> Immutable calculation snapshot
├── shipping_rate_id                    (CHAR 36, NULLABLE, FK -> shipping_rates.id)
├── origin_zone_id                      (CHAR 36, NULLABLE)
├── origin_zone_name                    (VARCHAR 100) -> Immutable calculation snapshot
├── destination_zone_id                 (CHAR 36, NULLABLE)
├── destination_zone_name               (VARCHAR 100) -> Immutable calculation snapshot
├── actual_weight                       (DECIMAL 8,2) -> Immutable calculation snapshot
├── volumetric_weight                   (DECIMAL 8,2) -> Immutable calculation snapshot
├── chargeable_weight                   (DECIMAL 8,2) -> Immutable calculation snapshot
├── rate_base_snapshot                  (DECIMAL 10,2)-> Immutable calculation snapshot (base rate used)
├── additional_weight_rate_snapshot     (DECIMAL 10,2)-> Immutable calculation snapshot (extra rate used)
├── volumetric_divisor_snapshot         (INT)         -> Immutable calculation snapshot (divisor used)
├── shipping_fee                        (DECIMAL 10,2)-> Immutable authoritative order shipping fee snapshot
├── estimated_days_min                  (INT)         -> Immutable calculation snapshot
├── estimated_days_max                  (INT)         -> Immutable calculation snapshot
├── tracking_number                     (VARCHAR 100, NULLABLE) -> Mutable delivery state
├── shipping_status                     (VARCHAR 50, default 'Pending') -> Mutable delivery state
└── timestamps
```

> [!IMPORTANT]
> **Data Immutability vs Delivery State Boundary**:
> The calculation snapshot fields (`provider_name`, `origin_zone_name`, `destination_zone_name`, `actual_weight`, `volumetric_weight`, `chargeable_weight`, `rate_base_snapshot`, `additional_weight_rate_snapshot`, `volumetric_divisor_snapshot`, `shipping_fee`, `estimated_days_min`, `estimated_days_max`) are **strictly immutable** after order creation. Even if the underlying `shipping_rates` record is modified or deleted later, the order's shipping charge remains 100% auditable and explainable.
> The delivery fields (`tracking_number`, `shipping_status`) are **mutable** throughout the fulfillment lifecycle.

---

## 3. Strict Algorithmic Workflow & Contracts

> [!NOTE]
> **Logistics Classification**: This system implements a **database-configured logistics pricing simulation**, NOT a live courier external API integration. Rate matrices, zones, and divisors represent platform configurations.

### A. Zone Resolution Contract (`ShippingZoneResolverService`)
Given geographic parameters (`postal_code`, `barangay`, `city`, `province`), the resolver normalizes inputs (trimmed, uppercase/lowercase unified) and evaluates hierarchical specificity. It chooses the **most specific configured match**, terminating at the first successful tier:
1. **Tier 1: Exact Postal Code** (`postal_code` matches the normalized customer postal code exactly).
2. **Tier 2: Most-Specific Postal Code Prefix** (`postal_code_prefix` matches, ordered by `LENGTH(postal_code_prefix) DESC`).
3. **Tier 3: Barangay + City + Province Match** (`barangay`, `city`, and `province` match).
4. **Tier 4: City + Province Match** (`city` and `province` match, `barangay` is NULL).
5. **Tier 5: Province Match** (`province` matches, `city` and `barangay` are NULL).
6. **Tier 6: Failsafe (Unserviceable)**: If no rule matches, return `null`. The system MUST NOT assume a fallback default zone; it must declare the destination as **Unserviceable**.

> [!CAUTION]
> **Zone Conflict Prevention**:
> Administrative validations and database unique indexes MUST prevent conflicting active rules. No two zone areas may define identical `(province, city, barangay)` or identical `postal_code` under different zones.

### B. Non-Overlapping Bracket & Boundary Rules
1. To prevent boundary ambiguity (e.g. 1.00kg matching two rows), brackets use non-overlapping decimal increments:
   - Bracket 1: `0.00` to `1.00` kg
   - Bracket 2: `1.01` to `2.00` kg
   - Bracket 3: `2.01` to `3.00` kg
   - Unlimited/Open-Ended Bracket: `min_weight = 3.01`, `max_weight = NULL`
2. **Strict Integrity Constraints**:
   - The application request & service layer MUST validate:
     - `min_weight >= 0`
     - If `max_weight !== null`, then `max_weight > min_weight`
     - `base_rate >= 0`
     - `additional_weight_rate >= 0`
     - `volumetric_divisor > 0`
     - `estimated_days_min >= 0`
     * `estimated_days_max >= estimated_days_min`
   - Active brackets for the same `(provider_id, origin_zone_id, destination_zone_id)` MUST NOT overlap.

### C. Rate Lookup Contract (Supporting Open-Ended Rates)
For a given `provider`, `origin_zone`, `destination_zone`, and `chargeable_weight`:
```php
$rate = ShippingRate::query()
    ->where('provider_id', $provider->id)
    ->where('origin_zone_id', $originZone->id)
    ->where('destination_zone_id', $destinationZone->id)
    ->where('min_weight', '<=', $chargeableWeight)
    ->where(function ($query) use ($chargeableWeight) {
        $query->where('max_weight', '>=', $chargeableWeight)
              ->orWhereNull('max_weight');
    })
    ->where('is_active', true)
    ->orderBy('min_weight', 'asc')
    ->first();
```
* If `$rate->max_weight === null` and `$rate->additional_weight_rate > 0`:
  $$\text{Extra Weight} = \lceil \text{chargeable\_weight} - \text{rate.min\_weight} \rceil$$
  $$\text{Fee} = \text{rate.base\_rate} + (\text{Extra Weight} \times \text{rate.additional\_weight\_rate})$$
* Otherwise, `Fee = rate.base_rate`.

### D. Chargeable Weight & Consolidated Packaging Contract
For a consolidated order from a seller:
> [!NOTE]
> **Consolidated Packaging Assumption**: Package dimensions entered by the seller represent **one packed sellable unit**. Multiple units in an order are modeled as consolidated shipment volume for shipping-rate estimation.

1. Total Actual Weight = $\sum (\text{package\_weight\_per\_unit} \times \text{quantity})$
2. Total Packed Volume = $\sum (\text{package\_length\_per\_unit} \times \text{package\_width\_per\_unit} \times \text{package\_height\_per\_unit} \times \text{quantity})$
3. Volumetric Divisor selection:
   $\text{Divisor} = \text{rate.volumetric\_divisor} \mathbin{\text{??}} \text{provider.default\_volumetric\_divisor} \mathbin{\text{??}} 3500$
4. $\text{Volumetric Weight} = \text{Total Packed Volume} / \text{Divisor}$
5. $\text{Chargeable Weight} = \max(\text{Total Actual Weight}, \text{Volumetric Weight})$

### E. Quote Consistency Token (`shipping_quote_token`)
1. When quotes are generated (`POST /checkout/shipping-quotes`), the server returns quotes accompanied by a short-lived token (`shipping_quote_token`).
2. The token binds: `seller_id`, `destination_address_id`, `items_hash`, and `timestamp` (expires in 15 minutes).
3. **Purity Rule**: The quote token is **strictly a consistency and security mechanism, NEVER a trusted price**.
4. At order placement, the server verifies the token to ensure cart items, quantities, and address haven't changed, then **independently recalculates the authoritative quote** from the database inside `DB::beginTransaction()`.

---

## 4. Canonical 12-Phase Implementation Sequence

1. **PHASE 1: Database Migrations**
   * Create migration for `shipping_providers`, `shipping_zones`, `shipping_zone_areas`.
   * Create migration for `seller_shipping_providers`, `shipping_rates`, and `order_shipping` (with `max_weight` NULLABLE).
   * Update `products` table: add `package_weight_per_unit`, `package_length_per_unit`, `package_width_per_unit`, `package_height_per_unit`, `handling_days`; make `shippingFee` nullable.

2. **PHASE 2: Seeders & Baseline Configuration**
   * Seed standard providers: `jnt` (J&T Express), `spx` (SPX Express), `lbc` (LBC Express).
   * Seed standard Philippine zones: NCR, North Luzon, South Luzon, Visayas, Mindanao.
   * Populate `shipping_zone_areas` with specific postal codes, cities, and provinces.
   * Seed sample bracket rates with non-overlapping bounds (clearly labeled as dev/sample rates).

3. **PHASE 3: `ShippingZoneResolverService`**
   * Implement 5-tier hierarchical matching (Exact Postal -> Prefix -> Barangay+City+Prov -> City+Prov -> Prov).
   * Guarantee rejection/unserviceable return when destination is unmapped.

4. **PHASE 4: `ShippingCalculatorService`**
   * Implement pure calculation logic accepting Seller Origin, Buyer Destination, Cart Items, and Optional Provider filter.
   * Calculate chargeable weight and query active rate brackets (including `max_weight IS NULL`).
   * Generate quote tokens for checkout consistency.

5. **PHASE 5: Automated Calculator & Zone Unit Tests**
   * Write tests for:
     * Specificity hierarchy (exact postal code overriding broad province rule).
     * Non-overlapping boundary queries (e.g. 1.00kg vs 1.01kg).
     * Open-ended bracket calculation with incremental kg rate.
     * Volumetric weight dominating actual weight (and vice versa).
     * Destination unserviceable exceptions.
     * Seller-disabled courier exclusion.

6. **PHASE 6: Seller Product Management UI**
   * Update `seller/products/create.blade.php` and `edit.blade.php`:
     * Replace manual fee with weight (kg) and dimensions (cm).
     * Update client-side validation (Alpine.js) and controller requests.

7. **PHASE 7: Checkout Quote Endpoint & UI Selection**
   * Create API endpoint or controller action: `POST /checkout/shipping-quotes`.
   * Update `checkout/index.blade.php` to display dynamic courier options with pricing, delivery days, and bind quote token.

8. **PHASE 8: CheckoutController Server-Side Recalculation**
   * Modify `CheckoutController::store` to recalculate the quote inside `DB::beginTransaction()`.
   * Reject order if selected provider is invalid, disabled, or unserviceable for the destination.

9. **PHASE 9: `order_shipping` Snapshot Persistence**
   * Save complete immutable calculation parameters into `order_shipping`.
   * Store provider name snapshot and zone name snapshots.

10. **PHASE 10: Order Display Alignment**
    * Update Customer Order Details, Seller Order Management, and Admin Order Views to display data from `order_shipping`.

11. **PHASE 11: Safe Legacy Product Migration Gating**
    * Strict Rule: **NEVER synthesize fake weight or dimensions** for legacy products.
    * Products lacking valid packaging data cannot enter the provider-specific checkout flow; sellers are prompted to complete their packaging specs.

12. **PHASE 12: End-to-End Regression Testing**
    * Validate end-to-end checkout with single and multi-item carts.
    * Verify inventory locking, payment verification, and order confirmation emails remain intact.
