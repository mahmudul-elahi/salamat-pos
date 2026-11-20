# Salamat POS — Structure Cleanup Plan

Stack: Laravel 13 (PHP 8.5), Sanctum API, Vue 3 SPA (`resources/js/src`), PHPUnit 12, Pint.

This plan lists every place that does not follow a consistent structure, and the order to fix it in. It is written for an AI coding agent. Work one phase at a time and stop for review after each phase.

---

## 0. Hard rules (apply to every task)

1. **Do not change any API response.** Same HTTP status, same JSON keys, same nesting, same key order where the frontend depends on it, same value types. The Vue app and the e‑commerce frontend read these responses directly. If a refactor cannot keep the response byte‑compatible, skip it and note why.
   - Most endpoints return `successResponse($message, $data)` → `{"message": ..., "result": ...}` (see `app/Helpers/Global/ResponseHelper.php`). Others return `response()->json([...])`, `response(...)`, or a bare array. **Keep each endpoint's current shape.** Do not "unify" them into `successResponse` if that changes the keys.
2. **Do not rename routes, route names, or URLs.** Permissions are derived from route names (`app/Http/Middleware/PermissionMiddleware.php` + `Traits/RouteToPermissionStringTrait.php`). Renaming a route silently breaks role permissions.
3. **Do not change the database schema** (no new migrations, no edits to existing migrations) unless a task says so.
4. **Do not add or remove composer/npm dependencies.**
5. When moving or renaming a PHP class, update every `use` statement, run `composer dump-autoload`, and grep for the old FQCN (including strings in config and seeders).
6. After any PHP change, run `vendor/bin/pint --dirty --format agent`.
7. Before refactoring an endpoint, write a feature test that captures its current JSON response (`assertExactJson` or `assertJsonStructure` + key values), then refactor, then confirm the test still passes. Only 1 real feature test exists today (`tests/Feature/OutletApiTest.php`), so this is how "response unchanged" is proven.
8. Follow the conventions in `AGENTS.md` (curly braces always, constructor promotion, explicit return types, PHPDoc over inline comments).

---

## 1. Bugs and logic errors (fix before any refactor)

Found by reading the security core, the money/stock services, the e‑commerce order flow, webhooks, auth and payroll, and by checking all 850 routes. Each item was verified in code; the ones marked *proven* were also reproduced. Fixing a bug may change **data behaviour**. It must still not change the **response shape** (rule 0.1). Each fix needs a regression test.

Severity: **Critical** = anyone on the internet, or any logged-in user, can steal or damage data. **High** = wrong money or stock in normal daily use. **Medium** = wrong in specific cases.

### 1A. Critical: security

| # | Where | Problem | Fix |
|---|-------|---------|-----|
| C1 | `routes/common/setting.php:11-16` → `Landlord/Setting/Email/EmailSettingController` | `GET` and `POST /api/admin/email-settings` and `GET /api/admin/settings` have **no `auth:sanctum`**. Anyone on the internet can read the SMTP host, username and **password** (`SettingRepository::getDeliverySettings` returns every value), and can **replace the mail server**. That would send password-reset emails to an attacker's server. | Move these three routes inside the `auth:sanctum` + `auth.permission` group. Never return `email_password` from `index()`; return a masked value. If the frontend needs public settings before login, use `app/public/settings` and filter out secrets there. |
| C2 | `app/Domain/DomainTenantFinder.php:13`, `app/Scopes/TenantScope.php`, `app/Models/Traits/Relationship/BelongToTenant.php:43` | **Cross‑shop data access.** The current shop (tenant) comes from the client‑controlled `X-domain` header. Nothing checks that the logged‑in user belongs to that shop (`users.tenants()` / `tenant_user`). Any staff user can send another shop's slug and read or write its sales, purchases, stock and expenses. `BelongToTenant::assignTenantId()` also accepts `request('tenant_id')` from the body. Only `IncomeCategoryController` and `InvestmentCategoryController` check membership by hand. | Add a middleware right after `auth:sanctum`: if the user is not an app admin and `tenant()` is set, abort with 403 unless `auth()->user()->tenants()->whereKey(tenant()->id)->exists()`. Apply the same check to any `tenant_id` taken from the request body (one shared rule, e.g. `App\Rules\UserBelongsToTenant`). Then audit the 23 models that have a `tenant_id` column but no `BelongToTenant` scope (`Order`, `Product`, `ProductSerial`, `Ledger`, `Supplier`, `PaymentMethod`, `Page`, `Slider`, `DamageProduct`, `ChatConversation`, …): each must filter by tenant in every query, or be documented as shared across shops. |
| C3 | `views/landlord/Ecommerce/Order/Component/CustomerNote.vue`, `Ecommerce/Review/CommentColumn.vue`, `common/component/{AreaColumn,TagsColumn,DescriptionColumn}.vue`, `global/datatable/Datatable.vue` (`column.modifier`), `global/modal/ConfirmationModal.vue` | **Stored XSS in the admin panel.** Customer order notes and product reviews are written by the public on the e‑commerce site and rendered with `v-html`. The Sanctum token is in `localStorage` (`utils/token.js`) and never expires (`config/sanctum.php` `expiration => null`). One malicious review can steal an admin's token permanently. | Render plain text with `{{ }}` for note, comment, address and tags. Where HTML is really needed (descriptions, Tiptap output), sanitize it with an allow‑list before rendering. Adding DOMPurify needs owner approval (rule 0.4); alternatively strip tags on the server when saving. Set a Sanctum `expiration`. |
| C4 | `routes/auth/index.php` (login, forgot‑password, confirm‑password, user‑join) | **No rate limiting anywhere** (`grep -rn throttle routes bootstrap` is empty). Login passwords can be brute‑forced. Login also reveals which emails exist (`'email.exists' => 'User not found'`). | Add `throttle:` middleware to the auth routes (e.g. 5/min per email+IP for login and forgot-password). Change the message to a generic "Invalid credentials" (this changes only the message text, so check whether the frontend shows it). |
| C5 | `app/Http/Controllers/Ecommerce/SslCommerz/SslCommerzController.php:33` | `dd($validation);` in the public `POST /api/ipn` SSLCommerz callback. Every IPN call dumps data and stops. The order status update below it is commented out, so online payments are **never marked as paid**. | Remove `dd()`. Ask the owner which status a validated payment should set, then implement it. Make it idempotent (the IPN can arrive more than once). |
| C6 | `app/Http/Controllers/Auth/UserJoinController.php:24,42` | *Proven.* `now()->diffInDays($userProfile->created_at) > 3`: on Carbon 3, diffing from now back to a past date is **negative** (−10 for 10 days ago), so **invitation links never expire**. | Use `$userProfile->created_at->diffInDays(now()) > 3` or `$userProfile->created_at->lt(now()->subDays(3))`. |
| C7 | Routes with only `auth:sanctum` (115 under `api/admin`, see `php artisan route:list`): `ecommerce/coupons*`, `ecommerce/gift-products*`, `pos/entry-jobs*`, `pos/applicants*`, `hrm/attendances*`, `landlord/support/*`, barcode PDF and stock‑calculation routes | Any logged‑in user, whatever their role, can create, edit or delete coupons and gift products. | **Ask the owner** which of these must be permission-checked, seed the permissions, then add `auth.permission`. |
| C8 | 41 controllers return `$exception->getMessage()` (e.g. `Administrator/Role/RoleController.php:45,78`, `Pos/Media/MediaController.php:244`, `Pos/Courier/BdCourierController.php:171`) | Leaks SQL and file-path details to the browser. | Same status and keys, but a generic message when `app.debug=false`, and `report($e)`. |

### 1B. High: money and stock are wrong

| # | Where | Problem | Fix |
|---|-------|---------|-----|
| H1 | `SaleService::stockIncrementAndStatusUpdate()` (`app/Services/Pos/Sale/SaleService.php:647`, early `return` at `:675`). Same copy‑paste in `PurchaseService::stockAndSerialNumberUpdate()` (`:507`, `:534`), `SaleReturnService::stockIncrementAndStatusUpdate()` (`:363`), `PurchaseReturnService::stockDecrementAndStatusUpdate()` (`:388`) | **Deleting a sale, purchase or return restores stock only for the first one or two items.** Inside the per‑item loop, the serial query covers all items. The first pass resets every serial, so on the next pass the query is empty and `return $this` exits the loop. Items 3+ (or items 2+ without serials) are never added back to stock. | Restore stock for every item first, then do one serial update outside the loop. Use `continue`, never `return`, inside the loop. |
| H2 | `SaleService::updateSaleItems()` `:574`, `PurchaseService::updateItems()` `:422`, `SaleReturnService` `:~144`, `PurchaseReturnService` `:277` | **Editing a sale, purchase or return to add two or more new items always fails.** For each new item it calls `tenantNew…SerialLog($newSerials)` with **all** new serials. The first call marks them all, so the second call throws "not available" / "already purchased". | Compute `$newSerials` once, call the serial log once **before** the loop, then create the items. |
| H3 | `SaleService::destroy` flow (`SaleController::destroy` `:278`) | Deleting a sale deletes its `transactions` but **not** the `ledgers`, `incomes` or `investments` that `saleTransaction()` created, nor the exchange `Purchase` or `SaleProductTemp` rows. The customer balance (`CustomerReportController`) is sales debit minus ledger credit, so after a delete the customer shows a **credit equal to what they paid**, and director income stays inflated. | Delete or reverse the linked ledger, income and investment rows (they have `ledger_id`), and the exchange purchase, in the same DB transaction. Do the same for purchase delete (supplier side) and the returns. |
| H4 | `SaleService::create()` `:52` and `updateSale()` `:521` vs `views/landlord/pos/sales/CreateSale.vue:308` | The frontend `grand_total` **includes** shipping. The backend stores `grand_total - shipping_cost`. A customer who pays the full amount is marked `partially_paid` (status compares `grand_total` to the amount paid), and their ledger shows a credit equal to the shipping. | **Ask the owner** whether shipping is collected by the shop or by the courier. Then make the stored total and the status comparison agree. |
| H5 | `SaleService::stockUpdate()` `:434` | For `sale_type = exchange`, stock is incremented (`updateOrCreate … quantity + n`) and then the same item **falls through and is decremented**, so net stock stays 0. A device received in exchange never appears in stock. | Add `continue;` after the exchange increment. Run `php artisan stock:recalculate` afterwards to repair existing data (dry run first). |
| H6 | `OrderController::approveOrderWithSerials()` `:735`, lines `:844`, `:846` | When an e‑commerce order is fulfilled from **several warehouses**, it creates one sale per warehouse, and **every** sale gets the full `shipping_charge` and the full coupon (`discount_total`). Shipping and discount are counted N times. | Put shipping and coupon on one sale only, or split them proportionally like `ProfitLossSummaryService::allocateCoupon()`. |
| H7 | `OrderController::approveOrderWithSerials()` `:957` → `SaleService::stockUpdate()` | `stockUpdate()` filters by `$request['tenant_id']` only when the **user** is app admin. When a non‑admin approves, stock is decremented in the shop from the `X-domain` header, **not** the warehouse each serial was taken from. | Always filter by the explicit `tenant_id` of the sale being created, bypassing the scope (`withoutGlobalScope(TenantScope::class)->where('tenant_id', …)`). |
| H8 | `SaleService::createEcommerceItem()` `:89` | Marks serials as `sale` with `whereIn('serial_number')` across **all shops and all variants**, without checking that they are in stock. | Filter by `tenant_id` and `product_variant_id`, and require status `purchase`/`sale_return` (same as `strictSerialValidation`). |
| H9 | `app/Services/Hrm/Payrun/PayrunCalculationService.php:256` (also `:74-78`) | *Proven.* Automatic periods use `endOfMonth()` / `endOfWeek()` (23:59:59.999), so `diffInDays($end) + 1` = **32** for a 31‑day month and **8** for a week. In `daily_log` mode an employee with perfect attendance is paid **96.9% (monthly)** or **87.5% (weekly)** of salary. | `$totalDays = $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) + 1`, or count the loop days. Ask the owner whether past payslips must be corrected. |
| H10 | `PurchaseService::updateItems()` `:440` | When an edit adds an item whose variant has **no `stocks` row** yet in that shop, `increment()` updates 0 rows, so the stock is silently lost. (`updateStock()` on create uses `updateOrCreate`, which is correct.) | Use the same `updateOrCreate`/upsert as create. |
| H11 | `OrderController::orderStatus()` `:678` and `destroy()` `:728` | An order that was already **approved** (sales created, stock deducted) can be set to `cancel`/`return`, or deleted, without reversing its sales or stock. | Block these transitions after approval, or reverse the created sales via `SaleService`. Also stop `approveOrderWithSerials` from running twice on the same order (check status first). |

### 1C. Medium

| # | Where | Problem | Fix |
|---|-------|---------|-----|
| M1 | `StockTransferController::approve()` `:163` | Status check and update with no row lock. Clicking approve twice (or two users at once) moves the stock **twice**. The `TenantProductSerial` row is created for the target shop even if one already exists. | `StockTransfer::whereKey($id)->lockForUpdate()->first()` inside the transaction and re‑check status. Use `updateOrCreate` for the target serial. Do the same for sale, purchase and order approval. |
| M2 | `routes/console.php:11,15` | Schedules `app:subscriber-expire-reminder` and `app:subscriber-expire`, which **do not exist** (template leftovers). The scheduler errors every day at 00:01. | Remove both entries. |
| M3 | `routes/console.php:7` | `queue:work` is started from the scheduler every minute. That works, but on a VPS a long PDF or report job blocks other jobs, and worker crashes go unnoticed. | Run workers with Supervisor or systemd on the VPS and remove this schedule entry. (Deployment note for the owner.) |
| M4 | `SaleService::orderCreateSale()` `:297` | `$request['grand_total'] - $request['coupon_amount'] ?? 0`: `??` binds looser than `-`, so a missing `coupon_amount` raises "undefined array key". | `$request['grand_total'] - ($request['coupon_amount'] ?? 0)`. |
| M5 | `SaleService::upsertProductSerial()` (exchange branch, `:~190`) | If the serial exists but this shop has no `tenant_product_serials` row, `update()` touches 0 rows, so the exchanged device is not put back into stock. | `updateOrCreate(['product_serial_id', 'tenant_id'], ['status' => 'purchase'])`. |
| M6 | `SaleService::tenantNewSaleProductSerialLog()` `:255`, `PurchaseService::tenantProductSerialLog()` `:226` (and the return equivalents) | `ProductSerial::…->first()` can be `null`, and the next line reads `->id`, giving a 500 instead of a validation error. | Throw a `GeneralException("Serial … does not exist", 400)` when it is null. |
| M7 | Sale, purchase and return services | The backend trusts `sub_total`, `grand_total`, `unit_price` and discounts from the client; it never recomputes them. Any staff user can save a sale whose total doesn't match its items. Stock is also decremented with no `quantity >= n` check, so it can go negative. | Recalculate totals on the server from the items and reject a mismatch (the response shape stays the same). Decide with the owner whether negative stock is allowed. |
| M8 | `PathaoController::webhook()` | Looks up `CourierOrder` by `consignment_id` **without** `where('provider', 'pathao')` (Steadfast and RedX do filter). If IDs ever collide, another courier's order gets updated. | Add the provider filter. |
| M9 | `app/Mail/Tags/Landlord/UserInvitationTag.php:18-19`, `app/Mail/Tags/Auth/ForgotPasswordTag.php:19` | `env('APP_URL')` outside config returns `null` after `config:cache` on the VPS, so invite and reset links break. | `config('app.url')`. |
| M10 | `app/Http/Controllers/Pos/Brand/BrandController.php:78` | Debug `Log::info('Brand created successfully')`. | Remove. |

**Not yet reviewed line by line:** reports (`Pos/Report/*`, `Pos/Statement/*` except where noted), HRM controllers other than payroll, media library, chat/WhatsApp/Messenger services, Blade PDF views, most Vue components. Review them with the same checks after the fixes above (tenant filter, loop `return`, ledger cleanup, client‑trusted totals, `v-html`).

---

## 2. Dead code and leftovers to delete

Verify with `grep -rn "<ClassName>" app routes config database bootstrap resources` before deleting each one. All of these had zero real usages at audit time.

| Item | Why it's dead |
|------|---------------|
| `app/Http/Controllers/InstallDemoDataController.php` | No route points to it. Leftover of the purchased template's cPanel installer. |
| `app/Http/Middleware/InstallMiddleware.php` + its `'install'` alias in `bootstrap/app.php` | Alias is registered but never used on any route. |
| `app/Http/Requests/Installer/` (`Database/DatabaseRequest.php`, `User/UserSetupRequest.php`) | Not referenced by any controller. |
| `app/Helpers/Traits/RevalidatesFrontend.php` | Duplicate of `RevalidatesFrontendTrait.php` (used in 17 files). Only `Pos/Brand/BrandController.php` uses the non‑Trait one. Switch it to `RevalidatesFrontendTrait`, confirm both have the same behaviour, then delete the duplicate. |
| `app/Console/Commands/Michael/MichaelJapanShiftCommand.php` | One‑off data command (`app:michael-japan-shift-command`, description still "Command description"). **Ask the owner** whether it was already run; if yes, delete it. |
| `database/seeders/Drop/PreviousMigrationTableDrop.php`, `database/seeders/Sync/SyncPartyMapperSeeder.php` | One‑off data fix seeders. **Ask the owner** before deleting. Make sure `DatabaseSeeder` does not call them. |
| Commented‑out code blocks | 322+ lines across the app. Worst files: `Pos/Statement/StatementController.php` (351 commented lines, including three old copies of `index()` at lines ~971, ~1056, ~1186), `Pos/Customer/CustomerPaymentController.php` (99), `Pos/Stock/PurchasesStockController.php` (91), `Pos/Media/MediaController.php` (50), `Ecommerce/Order/OrderController.php` (35), `Pos/Product/ProductController.php` (34), `Pos/People/ContactController.php` (34), `Services/Pos/Purchase/PurchaseService.php` (33), `Jobs/Report/GenerateStockStatementPdfJob.php` (30). Delete them; git history keeps the old versions. Do not delete PHPDoc. |
| `console.log` in the Vue app | 24 occurrences under `resources/js/src`. Remove. |

---

## 3. Folder and naming consistency (backend)

The app is split into these domains. Every layer (Controllers, Requests, Models, Services, Filters) should use the **same** domain folder names:

`Administrator`, `Auth`, `Common`, `Ecommerce`, `Hrm`, `Landlord`, `Pos`, `Profile`

### 3.1 Typos and casing in folder names

| Current | Rename to | Notes |
|---------|-----------|-------|
| `app/Http/Controllers/Pos/Investent/` | `Pos/Investment/` | Typo. Models are already `Models/Pos/Investment`. |
| `app/Http/Controllers/Pos/verify/` | `Pos/Verify/` | Lowercase breaks PSR‑4 on case‑sensitive filesystems (the VPS). |
| `app/Services/SSCommerz/` | merge into one SSLCommerz location | Three places for one gateway: `Services/SSCommerz/SSLCommerzPaymentService.php`, `Library/SslCommerz/*`, `Http/Controllers/Ecommerce/SslCommerz/`. Pick `SslCommerz` casing everywhere (e.g. `app/Services/SslCommerz/` for the service + library classes). |

### 3.2 Files sitting at the wrong level

| File | Move to |
|------|---------|
| `app/Http/Controllers/Pos/EntryJobController.php`, `Pos/JobApplicationController.php` | `app/Http/Controllers/Ecommerce/Career/` (routes live in `routes/landlord/career.php`; models `app/Models/Pos/EntryJob.php`, `JobApplication.php` → `app/Models/Ecommerce/Career/`). |
| `app/Http/Controllers/CustomerReportDownloadController.php`, `SupplierReportDownloadController.php` | `app/Http/Controllers/Pos/Report/` next to the other report controllers. |
| `app/Http/Controllers/Coupon/`, `app/Http/Controllers/GiftProduct/` | `app/Http/Controllers/Ecommerce/Coupon/`, `Ecommerce/GiftProduct/` (their routes are under the `ecommerce` prefix). Move `app/Models/Coupon/*` and `app/Models/Product/GiftProduct*.php` to match. |
| `app/Models/Product/` (only `GiftProduct`, `GiftProductVariant`) | `app/Models/Ecommerce/GiftProduct/`. Having both `Models/Product` and `Models/Pos/Product` is confusing. |
| `app/Http/Controllers/Hrm/*.php` (23 controllers flat in one folder) | Group by feature like the rest of the app: `Hrm/Attendance/`, `Hrm/Leave/`, `Hrm/Employee/`, `Hrm/Payrun/`, `Hrm/Asset/`, `Hrm/Organization/` (Department, Designation, WorkShift, BreakTime, Holiday), `Hrm/Event/` (Announcement, Event). Same grouping for `app/Models/Hrm/` (32 models flat). |

### 3.3 Duplicate models — must be resolved

| Duplicate | Used by |
|-----------|---------|
| `app/Models/Attribute/Attribute.php` (extends `BaseModel`) **and** `app/Models/Pos/Product/Attribute.php` (extends `Model`) | `Models/Attribute/*` is used by `Pos/Attribute/AttributeController.php`; `Models/Pos/Product/*` is used in 3 other places. |
| `app/Models/Attribute/AttributeValue.php` **and** `app/Models/Pos/Product/AttributeValue.php` | same as above |

Check `$table`, `$fillable`, casts and relations of both. If they map to the same table, keep **one** (`app/Models/Pos/Product/`), merge any missing relations/casts into it, update all imports, delete the other. Do not change the table.

### 3.4 Form Requests don't mirror controllers

Controllers live in `Pos/...` but many of their requests live in `Requests/Landlord/...` (e.g. `Pos/Brand/BrandController` → `Requests/Landlord/Brand`; same for Attribute, Category, Customer, Dealer, Expense, News, PaymentMethod, Shop, Slider, Supplier). Move each request so its path mirrors its controller's path: `Http/Controllers/Pos/Brand/BrandController` ↔ `Http/Requests/Pos/Brand/BrandRequest`.

### 3.5 Repositories folder

`app/Repositories/BaseRepository.php` is a **directory** containing `BaseRepository.php` (so there's a folder and a file with the same name). Only `User` and `Settings` repositories exist; everything else uses `app/Services`. Flatten to `app/Repositories/BaseRepository.php` (a file), or, preferably, fold the two repositories into their services. Pick one pattern and record it in `AGENTS.md`.

---

## 4. Controller layer (the biggest structural problem)

Current state (186 controllers):

- 86 controllers validate inline (`$request->validate()` / `Validator::make`); only 55 use Form Requests.
- 57 `store`/`update` methods type‑hint plain `Request`.
- 67 controllers use `DB::` directly, 18 use `DB::table` (query builder in the controller).
- Only 52 controllers delegate to a Service; the rest hold all the business logic.
- 651 controller methods have no return type.
- 0 policies; authorization is route‑name based (keep it that way).
- 2 API Resources in the whole app; responses are built by hand.

### 4.1 Target pattern for every controller

```
Controller method  → Form Request (validation + authorize(): true)
                   → Service (business logic, DB transactions)
                   → same response helper the endpoint uses today
```

- Thin controllers: no queries longer than one line, no `DB::transaction` (move it into the service).
- Every public method has a return type (`JsonResponse`, `Response`, `StreamedResponse`, `View`, `BinaryFileResponse`).
- Keep responses identical (rule 0.1). Do **not** introduce API Resources unless the resource outputs exactly the same array as today, proven by a test.

### 4.2 Split these fat controllers first (by size)

| Lines | Controller | Suggested split |
|------:|-----------|-----------------|
| 1287 | `Pos/Statement/StatementController.php` | `index()` alone is ~400 lines (556–950). Delete the 3 commented‑out old `index()` copies. Move query building into `app/Services/Pos/Statement/StatementService.php`. |
| 1024 | `Ecommerce/Order/OrderController.php` | `app/Services/Ecommerce/Order/OrderService.php` (create/update/status), plus a dedicated service for courier hand‑off. |
| 924 | `Pos/Selected/SelectedController.php` | Dropdown/"select" endpoints for many unrelated models. Split per domain (e.g. `SelectedProductController`, `SelectedPeopleController`, …) **keeping the same route names** — only change the controller the route points at. |
| 776 | `Pos/Statement/DailyClosingController.php` | `Services/Pos/Statement/DailyClosingService.php` |
| 772 | `Hrm/AttendanceController.php` | `Services/Hrm/Attendance/AttendanceService.php` |
| 719 | `Pos/Product/ProductController.php` | Existing `Services/Product` — move create/update/variant logic there. |
| 681 | `Hrm/EmployeeDetailController.php` | Split per section (address, bank, salary, …) or a service per section. |
| 610 | `Pos/Customer/CustomerPaymentController.php` | `Services/Pos/Customer/CustomerPaymentService.php` |
| 533 | `Pos/Product/ProductBarcodeController.php` | Barcode/PDF generation into a service; also remove the commented `return view(...)` blocks. |
| 502 | `Pos/Report/SupplierReportController.php` | Query into the existing `Filters/Pos/Report` or a report service. |
| 377 / 366 / 305 | `Pos/Courier/PathaoController.php`, `SteadfastController.php`, `RedxController.php` | API calls belong in `app/Services/Courier/*` (already exists — 5 files). Controllers should only call the service. Webhook handlers should share one structure. |

Then continue with every controller over ~250 lines (`find app/Http/Controllers -name '*.php' -exec wc -l {} + | sort -rn`).

### 4.3 Validation

For each of the 86 inline‑validating controllers, move the rules into a Form Request at the mirrored path (see 3.4). Keep the **exact same rules and messages** — a validation failure must still return the same 422 JSON.

---

## 5. Models

171 models. Make these consistent:

| Issue | Count | Fix |
|-------|------:|-----|
| Base class mixed: `extends Model` (90), `extends BaseModel` (61, `app/Models/Common/BaseModel.php`), `extends TenantBaseModel` (15) | — | Document when each base is used (tenant‑scoped vs global). Make sure every model with a `tenant_id` column uses `TenantBaseModel` / the `BelongToTenant` trait. Check each one carefully; a wrong scope leaks data between tenants. |
| `protected $casts` property (53) vs `casts()` method (1) | 54 | Laravel 13 convention is the `casts()` method. Convert. |
| Relation methods without return types | ~147 | Add `: HasMany`, `: BelongsTo`, etc. |
| `$guarded = []` | 3 | Replace with explicit `$fillable`. |
| Factories | 3 (User, EntryJob, JobApplication) for 171 models | Add factories for every model used in the tests you write in rule 0.7 — at minimum Product, ProductVariant, Sale, Purchase, Customer, Supplier, Order, Warehouse/Shop, Employee. |
| Hardcoded status strings (`'pending'`, `'completed'`, `'approved'`, …) | 71 in `app/` | Use the enums in `app/Enums` (add new cases where needed). Stored values must stay exactly the same strings. |

---

## 6. Routes

- `routes/api.php` registers three separate `Route::group(['prefix' => 'admin', 'middleware' => 'auth:sanctum'])` blocks. Merge them into one group (no URL/name change).
- Inline FQCNs in `routes/api.php` (e.g. `\App\Http\Controllers\Pos\Page\PageBlockController::class`) → add a `use` import.
- Route files mix `$router->` and `Route::` styles; pick one (`Route::`) per file.
- Some route files mix domains: `routes/landlord/coupon.php` and `career.php` are e‑commerce features under `routes/landlord/`. Moving the files is fine; URLs and names must not change.

---

## 7. Frontend (`resources/js/src`) — 584 `.vue` files

The frontend is in better shape (577/584 use `<script setup>`; API calls go through `services/axios` + `services/endpoints`). Fix:

| Issue | Where | Fix |
|-------|-------|-----|
| Folder casing is mixed under `views/landlord/` | `Ecommerce`, `Ecommerce/Order`, `Ecommerce/Review`, `Ecommerce/SellDeviceRequest`, `Ecommerce/SEO`, `pos/HelperComponent` vs. camelCase everywhere else (`pos`, `hrm`, `cashTransfer`, …) | Rename to camelCase (`ecommerce`, `order`, `review`, `sellDeviceRequest`, `seo`, `helperComponent`) and update all imports + `router/*.js`. Same for `services/endpoints/Ecommerce.js` → `ecommerce.js`. Route **paths** visible in the browser must not change. |
| Hardcoded `/admin/...` API URLs in components | `Ecommerce/page/PageModal.vue`, `Ecommerce/career/Applicants.vue`, `Ecommerce/mediaLibrary/MediaLibrary.vue`, `pos/mediaModal/MediaModal.vue`, `pos/purchases/PurchaseTransactionListModal.vue`, `pos/purchases/EditPurchase.vue`, `hrm/employee/details/Documents.vue` | Add constants to `services/endpoints/*.js` and import them. |
| `services/endpoints/pos.js` has 1 export while `landlord.js` has 299 | — | Move POS endpoints from `landlord.js` into `pos.js` (and Ecommerce/HRM ones into their files) so the file matches the view folder. Keep the URL strings identical. |
| 8 components still use the Options API (`export default {`) | `grep -rl "export default {" --include=*.vue resources/js/src` | Convert to `<script setup>`. |
| Very large components | `global/input/types/PhoneNumberInput.vue` (1515 lines — likely a country list inline: move the data to `utils/`), `pos/people/contact/OnlineCustomerViewModal.vue` (1453), `common/layout/inc/LandlordSidebar.vue` (1365 — move the menu config to a JS file), `hrm/payrun/ManualPayrun.vue` (1309), `pos/product/EditProduct.vue` (1122) + `CreateProduct.vue` (863), `pos/sales/CreateSale.vue` (1057) + `EditSale.vue` (844), `pos/purchases/CreatePurchase.vue` (763) | Extract child components and composables. Create/Edit pairs should share one form component. |
| `console.log` | 24 | Remove. |

---

## 8. Tests

Only `tests/Feature/OutletApiTest.php` is real (plus 2 example tests). Before Phases 3–5 touch an endpoint, add a feature test that records its current response (rule 0.7). Use factories and `RefreshDatabase`. Create tests with `php artisan make:test --phpunit <Name>Test`. Delete `tests/Feature/ExampleTest.php` and `tests/Unit/ExampleTest.php` once real tests exist.

---

## 9. Suggested order of work

| Phase | Scope | Risk |
|-------|-------|------|
| 1 | Section 1: 1A Critical first (C1, C2, C3, C5, C6 immediately), then 1B High, then 1C. C7 and H4 wait for owner answers. Each fix ships with a regression test. | Medium: touches money/stock |
| 2 | Section 2 dead code + commented code + `console.log` | Low |
| 3 | Section 3 folder/namespace moves (one domain per commit) | Medium: imports |
| 4 | Section 8 response‑snapshot tests for the endpoints touched next | None |
| 5 | Section 4 controllers → Form Requests + Services, biggest first | Medium: verify with tests |
| 6 | Section 5 models | Medium: tenant scopes |
| 7 | Section 6 routes tidy-up | Low (names/URLs unchanged) |
| 8 | Section 7 frontend | Medium: imports |

After each phase: `vendor/bin/pint --dirty --format agent`, `php artisan test --compact`, `php artisan route:list` (compare the full route list against the list from before the phase: no URL or name may differ), and `npm run build` for frontend phases.

---

## 10. Questions for the project owner (ask, don't guess)

1. SSLCommerz IPN (C5): on successful validation, what should the order status become?
2. Which auth-only routes must require role permissions (C7)?
3. Shipping (H4): is shipping paid to the shop (part of the sale total) or collected by the courier?
4. Payroll (H9): must already-generated payslips be recalculated?
5. Is negative stock ever allowed (M7)?
6. Has `app:michael-japan-shift-command` already been run? Can it and the `Drop`/`Sync` seeders be deleted?
7. Repository pattern (3.5): keep repositories, or move everything to services?
