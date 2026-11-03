# Second-Pass Audit & Verification Report: AI-Driven Vehicle Repair and Fleet Management System

**Branch:** `fix-all`  
**Reviewer:** Senior Laravel Engineer & Skeptical Second-Pass Reviewer  
**PHPUnit Test Suite:** 46 tests, 209 assertions, **100% passing** (`php artisan test`)  
**Code Quality:** Clean PSR-12 / Laravel Standards (`./vendor/bin/pint`)  

---

## 1. Previous Fixes Verified OK

Every fix documented in [`FIXES.md`](FIXES.md) was inspected directly in the codebase and verified:

1. **[SEC-01] Chatbot Invoice Payment Permission:** [`ChatbotController.php:2475, 2584`](app/Http/Controllers/ChatbotController.php#L2475) strictly checks `$user->hasPermission('invoice.manage')`. Verified and covered by test `test_junior_staff_without_invoice_permission_cannot_mark_paid_via_chatbot`.
2. **[SEC-02 & SEC-03] Anonymous Chatbot Booking Vehicle Integrity & Pending Status:** [`ChatbotController.php:1085-1145`](app/Http/Controllers/ChatbotController.php#L1085) prevents overwriting specs of existing vehicles and marks guest bookings as `'pending'`. Covered by test `test_unauthenticated_chatbot_booking_does_not_overwrite_existing_vehicle_specs`.
3. **[ARC-02] Removal of Dead `$this->role` Methods:** Removed from [`ClientController.php:71-77`](app/Http/Controllers/ClientController.php#L71). Callers now use `$request->user()->isSecondaryPic()`.
4. **[SEC-06] Mass Assignment in Admin Controllers:** [`AdminController.php:276, 300`](app/Http/Controllers/AdminController.php#L276) strictly utilizes `$request->validated()`.
5. **[BIZ-01] Job Card Stage Transition Matrix:** [`JobCardController.php:217-265`](app/Http/Controllers/JobCardController.php#L217) enforces valid workflow transitions and rolls back service history on administrative reversals. Covered by `test_job_card_stage_transition_cannot_skip_from_received_to_completed` and `test_job_card_stage_valid_progression`.
6. **[BIZ-02] Inventory Concurrency Protection:** [`JobCardController.php:326-380`](app/Http/Controllers/JobCardController.php#L326) utilizes `DB::transaction()` and `SparePart::lockForUpdate()`.
7. **[BIZ-03] Appointment Booking Capacity Race Condition:** [`AppointmentController.php:181-220`](app/Http/Controllers/AppointmentController.php#L181) uses transactional row locking to enforce daily workshop booking limits.
8. **[DB-01] Spare Parts Check Constraint:** Migration `2026_11_03_000001_add_check_constraint_to_spare_parts_table.php` enforces `CHECK (stock >= 0)`.
9. **[SEC-08] Password Policy Standardization:** [`AppServiceProvider.php`](app/Providers/AppServiceProvider.php) enforces `Password::defaults()->min(8)->letters()->mixedCase()->numbers()` across registration, password resets, and user management.
10. **[SEC-09] Sanitized Exception Messages:** [`InvoiceController.php:208-226`](app/Http/Controllers/InvoiceController.php#L208) logs errors via `Log::error()` and avoids leaking internal mail hostnames.
11. **[ARC-03] View Calculation Encapsulation:** [`JobCard.php:130-150`](app/Models/JobCard.php#L130) provides `calculated_labour_total`, `calculated_parts_total`, and `calculated_grand_total` accessors.
12. **[ENV-01] Safe Environment Defaults:** [`.env.example`](.env.example) configured with `APP_DEBUG=false`.

---

## 2. Previous Fixes That Were Incomplete or Wrong (And What Was Done)

### 🔴 Incomplete Fix: Cross-Tenant Fleet Isolation (SEC-04)
- **What the First Pass Did:** Added `abort_unless($user->company_id, 403)` only to `ClientController` and `VehicleController`.
- **What Was Missed:** The first pass completely missed 5 other controllers where corporate users could trigger queries with `company_id = NULL`:
  - [`TripLogController.php:29, 189`](app/Http/Controllers/TripLogController.php#L29)
  - [`MaintenanceAlertController.php:22, 77`](app/Http/Controllers/MaintenanceAlertController.php#L22)
  - [`FeedbackController.php:25`](app/Http/Controllers/FeedbackController.php#L25)
  - [`InvoiceController.php:30`](app/Http/Controllers/InvoiceController.php#L30)
  - [`AppointmentController.php:30, 152, 405`](app/Http/Controllers/AppointmentController.php#L30)
- **Vulnerability:** When a corporate account has `company_id = NULL`, queries like `User::where('company_id', $user->company_id)` resolved to `company_id IS NULL`, matching all individual customers, mechanics, and admins. This allowed unassigned corporate users to view and manipulate trip logs, alerts, feedback, invoices, and appointments belonging to individual customers.
- **Action Taken (Commit `cbc198e`):** Added `abort_unless($user->company_id, 403, 'Your corporate account has not been assigned to a company.');` across all 5 controllers and authorization guards.

---

### 🟠 Incomplete Fix: Chatbot Markdown Link Protocol Sanitization (SEC-07)
- **What the First Pass Did:** Checked `!/^(?:https?:\/\/|\/|#)/i.test(cleanUrl)` before emitting `<a href="${cleanUrl}">`.
- **What Was Missed:** In [`chatbot.blade.php`](resources/views/partials/chatbot.blade.php), `escapeHtml` was run on the string before the markdown regex. This converted `"` into `&quot;`. If an attacker passed `[link](https://example.com&quot; onmouseover=&quot;alert(1))`, the URL started with `https://`, passed the prefix test, and when injected via Alpine.js `x-html`, the browser's HTML parser decoded `&quot;` inside attribute context back into `"`, enabling attribute injection.
- **Action Taken (Commit `149387d`):** Upgraded the regex to strictly reject quotes, entity-encoded quotes, angle brackets, and whitespace: `/^(?:https?:\/\/|\/|#)[^\s"'>&]*$/i`.

---

### 🟠 Incomplete Fix: SQL Aggregate Parameter Interpolation (PERF-01)
- **What the First Pass Did:** Aggregated monthly fleet stats using `selectRaw()` to eliminate N+1 queries in `ReportController`.
- **What Was Missed:** Direct string interpolation `'{$startDateStr}'` inside `selectRaw()` on line 166–167.
- **Action Taken (Commit `0da445d`):** Replaced interpolation with positional parameter bindings: `selectRaw(..., [$startDateStr])`.

---

### 🟡 Incomplete Fix: Vehicle Edit Authorization (ARC-01)
- **What the First Pass Did:** Added `authorizeVehicleAccess` to `VehicleController@edit`.
- **What Was Missed:** `authorizeVehicleAccess` checks `can('view', $vehicle)`. Because `VehiclePolicy@update` blocks Secondary PIC (Viewer) accounts, Secondary PICs could not update vehicles, but were still permitted to view the `/client/vehicles/{vehicle}/edit` form.
- **Action Taken (Commit `f879e2c`):** Updated `VehicleController@edit` to enforce `abort_unless(auth()->user()->can('update', $vehicle), 403)`.

---

## 3. New Issues Found and Fixed

### 🔴 [BIZ-04] Cascading Deletion of Active Repairs & Unpaid Debts (Commit `2051487`)
- **Location:** [`VehicleController.php:166`](app/Http/Controllers/VehicleController.php#L166), [`AccountRequestController.php:235`](app/Http/Controllers/AccountRequestController.php#L235), [`AdminController.php:260`](app/Http/Controllers/AdminController.php#L260)
- **Issue:** Vehicles and user accounts could be deleted while vehicles had active repair jobs in progress or outstanding unpaid invoices. Because foreign keys on `job_cards` and `invoices` use `cascadeOnDelete()`, active job cards on workshop lifts and unpaid financial debts were deleted from the database.
- **Fix:** Added guards preventing vehicle or user account deletion if any linked job cards are not `completed` or if any invoices remain unpaid (`status != 'paid'`).

### 🔴 [BIZ-05] Payment Double-Deduction & Concurrency Race Condition (Commit `7d61819`)
- **Location:** [`InvoiceController.php:175-204`](app/Http/Controllers/InvoiceController.php#L175)
- **Issue:** `addPayment()` verified `$request->amount > $invoice->balance` outside a database transaction and without row locking. Concurrent requests could both pass the balance check and record duplicate payments exceeding the invoice total.
- **Fix:** Wrapped the payment recording routine in `DB::transaction()` and acquired a pessimistic write lock using `Invoice::where('id', $invoice->id)->lockForUpdate()->firstOrFail()`.

### 🟠 [BIZ-06] Invoice Number Collision on Concurrent Generation (Commit `c90bac5`)
- **Location:** [`InvoiceController.php:126-144`](app/Http/Controllers/InvoiceController.php#L126)
- **Issue:** `InvoiceController@generate` created invoices with a hardcoded `'invoice_number' => 'PENDING'` before renaming them. Because `invoice_number` has a unique database constraint, concurrent requests crashed with SQL error 1062 (Duplicate entry 'PENDING').
- **Fix:** Generated unique temporary tokens (`'TMP-'.Str::upper(Str::random(12))`) and locked the job card inside `DB::transaction()`.

### 🟡 [ARC-04] Phantom Resource Routes for Job Cards (Commit `3b6ebe6`)
- **Location:** [`routes/web.php:141`](routes/web.php#L141)
- **Issue:** `Route::resource('job-cards', JobCardController::class)` registered `edit`, `update`, and `destroy` routes. Because these methods were never defined on `JobCardController`, requests to these routes resulted in 500 BadMethodCallExceptions.
- **Fix:** Restricted the resource registration to `->only(['index', 'create', 'store', 'show'])`.

### 🟡 [SEC-14] Rate Limiting on Password Resets & Chatbot Utility Routes (Commit `ee52367`)
- **Location:** [`routes/web.php:31, 32, 43, 45`](routes/web.php#L31)
- **Issue:** Public routes `POST /forgot-password`, `POST /reset-password`, `GET /chatbot/schedule-availability`, and `POST /chatbot/reset-session` lacked throttling, leaving them vulnerable to denial-of-service and email flooding.
- **Fix:** Added `throttle:6,1`, `throttle:10,1`, `throttle:60,1`, and `throttle:30,1` middleware.

### 🟡 [SEC-13] Unescaped Chart.js JSON Output in Blade (Commit `0da445d`)
- **Location:** [`resources/views/reports/index.blade.php:570-588`](resources/views/reports/index.blade.php#L570)
- **Issue:** Used `{!! json_encode(...) !!}` for revenue and jobs chart datasets.
- **Fix:** Converted to the secure Laravel `@js(...)` directive.

### 🟡 [CFG-01] Incorrect Timezone for Malaysian Workshop System (Commit `acab75b`)
- **Location:** [`config/app.php:68`](config/app.php#L68), [`.env.example`](.env.example)
- **Issue:** Default timezone was configured to `'UTC'` rather than `Asia/Kuala_Lumpur`, causing an 8-hour offset that broke daily appointment queues (`today()`) and staff attendance timestamps during the morning hours.
- **Fix:** Set `'timezone' => env('APP_TIMEZONE', 'Asia/Kuala_Lumpur')`.

### 🟢 [TEST-02] Multi-Role RBAC & IDOR Feature Tests (Commit `f879e2c`)
- **Location:** [`tests/Feature/RoleBasedAccessControlAndIdorTest.php`](tests/Feature/RoleBasedAccessControlAndIdorTest.php)
- **Addition:** Added 8 permanent feature tests verifying role boundaries, cross-company isolation, secondary PIC mutation blocks, and deletion guards.

---

## 4. Still Unresolved (With Rationale)

Per your instructions (*"Do NOT start PSM 2 features in this run. Only note schema gaps"*), the following items are reserved for PSM 2:

1. **Random Forest Supervised ML Model:**
   - Current predictive maintenance uses a formula-based linear scoring heuristic. Training a scikit-learn model requires ground-truth component failure labels (`component_failed_within_30d`) and structured OBD-II trouble codes.
   - Integration will run via a decoupled Python FastAPI service called via queued HTTP jobs.
2. **Earliest Deadline First (EDF) Dynamic Scheduler:**
   - Current schedule view sorts by estimated completion time. An online EDF scheduler requires schema additions: `job_cards.deadline`, `job_cards.duration_minutes`, `job_cards.priority_level`, and workshop bay/lift resource limits.
3. **Multi-Line Purchase Orders:**
   - `purchase_orders` is 1:1 with `spare_part_id`. PSM 2 requires a `purchase_order_items` table and automatic replenishment cooldown timers.
4. **Payment Idempotency Keys:**
   - Payment webhooks need an `idempotency_key` column on `payments` to prevent double-charging on network retries.

---

## 5. Manual Testing Verification Guide

To manually verify the fixes:

### Test 1: Cross-Tenant Corporate Isolation with NULL Company
1. Create or set a corporate user with `company_id = NULL`.
2. Log in as that corporate user.
3. Attempt to visit `/client/dashboard`, `/client/vehicles`, or `/client/trip-logs`.
4. **Result:** Request is aborted with **HTTP 403 Forbidden** (*"Your corporate account has not been assigned to a company"*). Customer fleet records are not leaked.

### Test 2: Active Repair Deletion Guard
1. Log in as an individual customer with an active repair job (or create one).
2. Go to `/customer/vehicles` and attempt to delete the vehicle.
3. **Result:** Deletion is blocked with error: *"Cannot delete vehicle with active job cards in progress."* The vehicle and job card remain intact.

### Test 3: Unpaid Invoice Deletion Guard
1. Complete the repair job and generate an invoice. Leave it unpaid.
2. Attempt to delete the vehicle.
3. **Result:** Deletion is blocked with error: *"Cannot delete vehicle with outstanding unpaid invoices."*

### Test 4: Concurrent Invoice Generation Protection
1. Open two browser tabs on the same completed job card.
2. Click **Generate Invoice** on both simultaneously.
3. **Result:** One request succeeds in generating the invoice (`INV-YYYY-XXXXX`), and the other receives an error indicating the job card has already been invoiced. No SQL duplicate entry errors occur.

### Test 5: Timezone Accuracy in Daily Queue
1. Log in as staff or coordinator before 8:00 AM Malaysian time.
2. Visit `/staff/appointments/queue`.
3. **Result:** Appointments booked for today's date render correctly rather than being shifted to yesterday due to UTC offset.

---

## 6. Honest Confidence Rating

| Component | Confidence | Notes |
| :--- | :---: | :--- |
| **Authentication & Role Authorization** | **98%** | All routes, policies, and controller gates are centralized and tested against IDOR. |
| **Database Concurrency & Locking** | **95%** | Inventory deductions, appointment capacity, and payments are guarded by transactional `lockForUpdate`. |
| **Workflow State Machine Integrity** | **95%** | Transitions strictly enforced; deletion protected by active job and invoice checks. |
| **Blade & View Security (XSS / Escaping)** | **95%** | Raw HTML directives sanitized; Chart.js JSON output migrated to `@js(...)`. |
| **External Service Integrations (Gemini API)** | **80%** | Local rule-based chatbot heuristics are fully tested; live Gemini API calls depend on external API keys and network connectivity. |
