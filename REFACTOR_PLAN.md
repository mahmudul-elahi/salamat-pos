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

## 1. Bugs found during the audit (fix first, smallest diff possible)

| # | File | Problem | Fix |
|---|------|---------|-----|
| 1.1 | `app/Http/Controllers/Ecommerce/SslCommerz/SslCommerzController.php:33` | `dd($validation);` is left in the public `POST /api/ipn` handler (SSLCommerz payment callback). Every IPN call dumps data and stops; the order status is never updated (the update code below it is commented out). | Remove the `dd()`. Ask the owner what the IPN should do on success (the intended status update is commented out), then implement it. Never echo validation output. |
| 1.2 | `app/Mail/Tags/Landlord/UserInvitationTag.php:18-19`, `app/Mail/Tags/Auth/ForgotPasswordTag.php:19` | Uses `env('APP_URL')` outside config. After `php artisan config:cache` on the server, `env()` returns `null`, so invitation and password-reset email links break. | Replace with `config('app.url')`. |
| 1.3 | `routes/landlord/coupon.php`, `routes/landlord/career.php`, `routes/landlord/support.php`, `routes/landlord/selected.php` | These groups have no `auth.permission` middleware, so any logged-in user can create/update/delete coupons, gift products, jobs and applicants. | **Ask the owner before changing.** Adding `auth.permission` needs matching permissions seeded (`database/seeders/Permission`), or non-admin roles lose access. `selected.php` (dropdown data) and `support.php` may be intentionally open; confirm. |
| 1.4 | Controllers that return `$exception->getMessage()` to the client (e.g. `Administrator/Role/RoleController.php:45,78`, `Pos/Media/MediaController.php:244`, `Pos/Courier/BdCourierController.php:171`; 41 files total — `grep -rl "getMessage()" app/Http/Controllers`) | Leaks SQL errors and internal paths to the browser. | Keep the same response keys and status, but return a generic message when `app.debug` is false and `report()` the exception. |
| 1.5 | `app/Http/Controllers/Pos/Brand/BrandController.php:78` | Debug `Log::info('Brand created successfully')`. | Remove. |

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
| 1 | Section 1 bugs (1.1, 1.2, 1.5 now; 1.3, 1.4 after owner answers) | Low |
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

1. SSLCommerz IPN (1.1): on successful validation, what should the order status become?
2. Should coupons, gift products, careers/applicants and `selected` dropdown routes require role permissions (1.3)?
3. Has `app:michael-japan-shift-command` already been run? Can it and the `Drop`/`Sync` seeders be deleted?
4. Repository pattern (3.5): keep repositories, or move everything to services?
