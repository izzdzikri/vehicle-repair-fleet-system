# Security, Architecture, Database & Quality Audit Report
**Project:** AI-Driven Vehicle Repair and Fleet Management System  
**Framework:** Laravel 13, Blade, Tailwind CSS, Alpine.js, MySQL 8.0  
**Audit Date:** November 2026  
**Reviewer:** Senior Laravel Engineer & Security Reviewer  

---

## Executive Summary
This audit evaluated the architecture, role-based authorization, database schema integrity, business logic workflows, PSM 2 readiness, and testing quality of the Vehicle Repair and Fleet Management codebase.

While the system possesses an impressive range of domain capabilities (two-factor authentication, corporate multi-PIC hierarchy, PDF invoicing, check-ins, telemetry-based prediction heuristics, and multi-turn chat), it suffers from architectural debt and critical security flaws, particularly around the conversational AI chatbot, authorization decentralization, and state machine transitions.

---

## Findings Ranked by Severity

### 🔴 Critical Severity

#### [SEC-01] Chatbot Bypasses Permission Checks to Mark Invoices Paid
- **File & Line:** [`app/Http/Controllers/ChatbotController.php:2584-2615`](app/Http/Controllers/ChatbotController.php#L2584-L2615)
- **Description:** While the web interface ([`InvoiceController.php:181`](app/Http/Controllers/InvoiceController.php#L181)) strictly requires `auth()->user()->hasPermission('invoice.manage')` to record invoice payments, `ChatbotController@executeInvoicePayment` only checks `in_array($user->role, ['admin', 'staff'])`. Any mechanic or junior workshop staff member lacking invoice permissions can use the chatbot to mark invoices as fully paid in cash (generating official receipts and clearing the balance).
- **Remediation:** Enforce `$user->hasPermission('invoice.manage')` directly inside `executeInvoicePayment`.

#### [SEC-02] Anonymous Chatbot Booking Overwrites Existing Vehicle Specs
- **File & Line:** [`app/Http/Controllers/ChatbotController.php:1037-1060`](app/Http/Controllers/ChatbotController.php#L1037-L1060)
- **Description:** The route `POST /chatbot/reply` is publicly accessible without authentication. If a guest enters an existing vehicle plate number (e.g. belonging to another customer or corporate fleet), the method updates that vehicle's `brand`, `model`, `year`, and `mileage` with arbitrary unauthenticated input.
- **Remediation:** Disallow modifying existing vehicle records through the unauthenticated chatbot booking flow unless ownership is proven.

#### [SEC-03] Inverted Appointment Status on Public Chatbot Booking
- **File & Line:** [`app/Http/Controllers/ChatbotController.php:1070-1071`](app/Http/Controllers/ChatbotController.php#L1070-L1071)
- **Description:** Chatbot booking sets `'status' => $user ? 'pending' : 'confirmed'` and `'is_walkin' => $user ? false : true`. Unauthenticated public guests have their appointments automatically confirmed into the workshop queue, while authenticated customers are relegated to pending. This allows malicious actors to flood workshop capacity with confirmed bookings.
- **Remediation:** Set unauthenticated chatbot bookings to `'pending'` and assign them to an unverified walk-in triage state.

#### [ARC-01] Complete Absence of Policies and Form Requests (IDOR Vulnerability Vector)
- **File & Line:** Workspace structure: `app/Policies/` and `app/Http/Requests/` directories are absent.
- **Description:** Authorization and validation logic are embedded inside controllers with ad-hoc private methods. There are no centralized model policies, leaving endpoints susceptible to IDOR or inconsistent privilege enforcement.
- **Remediation:** Generate dedicated Laravel Policy classes (`VehiclePolicy`, `JobCardPolicy`, `InvoicePolicy`, `AppointmentPolicy`) and Form Request classes.

#### [ARC-02] `ClientController` Calling `$this->role` on Controller Instance
- **File & Line:** [`app/Http/Controllers/ClientController.php:71-77`](app/Http/Controllers/ClientController.php#L71-L77)
- **Description:** Methods `isSecondaryPic()` and `isPrimaryPic()` inside `ClientController` evaluate `$this->role === 'corporate'`. `$this` refers to the controller instance, which has no `$role` property. In PHP 8.2+, this triggers deprecation notices and always returns `false`.
- **Remediation:** Remove these methods from the controller and invoke `$user->isSecondaryPic()` on the authenticatable user model.

---

### 🟠 High Severity

#### [SEC-04] Cross-Tenant Fleet Exposure via NULL Company ID
- **File & Line:** [`app/Http/Controllers/ClientController.php:20-27`](app/Http/Controllers/ClientController.php#L20-L27)
- **Description:** If a corporate user has `company_id = NULL`, querying `User::where('company_id', $companyId)->pluck('id')` evaluates to `company_id IS NULL`, returning all individual customers, admins, and mechanics. The corporate user is then displayed private customer vehicles in their fleet view.
- **Remediation:** Add `abort_unless($user->company_id, 403)` at the beginning of corporate portal controllers.

#### [SEC-05] Plaintext Two-Factor Authentication Secrets
- **File & Line:** [`app/Http/Controllers/TwoFactorSettingsController.php:27`](app/Http/Controllers/TwoFactorSettingsController.php#L27), [`app/Models/User.php:66-70`](app/Models/User.php#L66-L70)
- **Description:** `two_factor_secret` is stored unencrypted in the database. The User model lacks an `'encrypted'` cast for TOTP secrets and recovery codes. A database disclosure directly compromises 2FA security.
- **Remediation:** Add `'two_factor_secret' => 'encrypted'` and `'two_factor_recovery_codes' => 'encrypted:array'` to `User::$casts`.

#### [SEC-06] Mass Assignment via `$request->all()` in Admin Controllers
- **File & Line:** [`app/Http/Controllers/AdminController.php:276, 300`](app/Http/Controllers/AdminController.php#L276)
- **Description:** Methods `storeCompany` and `storeJobType` perform request validation but pass raw `$request->all()` directly to Eloquent `create()`. Any column configured in `$fillable` (e.g. `status`) can be manipulated by malicious request payloads.
- **Remediation:** Replace `$request->all()` with `$request->validated()`.

#### [SEC-07] Chatbot Markdown Link XSS via `javascript:` URI Pseudo-Protocol
- **File & Line:** [`resources/views/partials/chatbot.blade.php:240-244`](resources/views/partials/chatbot.blade.php#L240-L244)
- **Description:** The client-side markdown formatter converts `[title](url)` into `<a href="${url}">` without validating that the scheme is `http`, `https`, or a relative path. Malicious link payloads can execute arbitrary JavaScript in the user's browser context.
- **Remediation:** Restrict the URL matching regex to safe protocols (`https?://` or `/`).

#### [BIZ-01] Job Card Workflow: Stage Skipping and Reversal Inconsistencies
- **File & Line:** [`app/Http/Controllers/JobCardController.php:204-242`](app/Http/Controllers/JobCardController.php#L204-L242)
- **Description:** `updateStage()` validates that the stage is in the enum list, but allows arbitrary stage jumping (e.g., straight from `received` to `completed`). Furthermore, if a job is moved back from `completed` to `repairing`, `completed_at` is cleared, but the automatically generated `ServiceHistory` record and `Appointment` completion are never reverted.
- **Remediation:** Enforce a strict state transition matrix and atomic status synchronization.

#### [BIZ-02] Inventory Stock Deduction Race Condition
- **File & Line:** [`app/Http/Controllers/JobCardController.php:281-287`](app/Http/Controllers/JobCardController.php#L281-L287)
- **Description:** Stock availability is checked outside the database transaction without row locking (`lockForUpdate`). Concurrent additions of the same part can cause `stock` to decrement into negative numbers.
- **Remediation:** Move stock verification inside the transaction and lock the `spare_parts` row with `lockForUpdate()`.

#### [BIZ-03] Appointment Booking Capacity Race Condition
- **File & Line:** [`app/Http/Controllers/AppointmentController.php:172-179`](app/Http/Controllers/AppointmentController.php#L172-L179)
- **Description:** Appointment counts are checked against capacity without pessimistic locking or database transaction wrapping. In addition, walk-in appointments bypass the capacity limit entirely ([line 221](app/Http/Controllers/AppointmentController.php#L221)).
- **Remediation:** Enforce booking capacity checks within transactional locks and apply limits consistently to walk-in requests.

#### [PERF-01] Severe N+1 Query Loops in Reports Dashboard
- **File & Line:** [`app/Http/Controllers/ReportController.php:144-172`](app/Http/Controllers/ReportController.php#L144-L172)
- **Description:** Loading the admin reports page executes 4 database queries per corporate company inside a PHP collection `map()` loop (200+ queries for 50 companies). Lines 50–59 execute 24 un-indexed full-table scans for monthly revenue.
- **Remediation:** Replace collection mapping with aggregated SQL queries using joins and `GROUP BY`.

---

### 🟡 Medium Severity

#### [DB-01] Missing Check Constraint on Spare Parts Stock
- **File & Line:** [`database/migrations/2026_05_19_162343_create_spare_parts_table.php:15`](database/migrations/2026_05_19_162343_create_spare_parts_table.php#L15)
- **Description:** The `stock` column is a signed integer without an unsigned modifier or database check constraint (`CHECK (stock >= 0)`).
- **Remediation:** Add a new migration enforcing non-negative stock constraint.

#### [SEC-08] Weak Password Policy Across Authentication & User Management
- **File & Line:** [`AuthController.php:79, 134`](app/Http/Controllers/AuthController.php#L79), [`ProfileController.php:40`](app/Http/Controllers/ProfileController.php#L40), [`AdminController.php:169, 184`](app/Http/Controllers/AdminController.php#L169)
- **Description:** Passwords only enforce `min:6` without uppercase, numbers, or breach detection rules.
- **Remediation:** Standardize on `Password::defaults()` using `min(8)->letters()->mixedCase()->numbers()`.

#### [SEC-09] Raw Exception Message Leaked to Client
- **File & Line:** [`app/Http/Controllers/InvoiceController.php:232`](app/Http/Controllers/InvoiceController.php#L232)
- **Description:** Mail delivery errors leak raw `$e->getMessage()` to user session flash messages, which may reveal internal mail server hostnames, ports, and credentials.
- **Remediation:** Log the raw exception with `Log::error()` and return a generic error message to the user.

#### [ARC-03] Business Logic in Blade Views
- **File & Line:** [`resources/views/job-cards/show.blade.php:150-160`](resources/views/job-cards/show.blade.php#L150-L160)
- **Description:** Part cost aggregations and total pricing calculations are computed dynamically within `@php` blocks inside the Blade template.
- **Remediation:** Expose computed properties/accessors on the `JobCard` model (e.g. `$jobCard->calculated_total_cost`).

#### [TEST-01] Tests Dependent on Pre-Existing Seed Data without Factories
- **File & Line:** [`tests/Feature/JobCardPartRollbackTest.php:18-24, 65-69`](tests/Feature/JobCardPartRollbackTest.php#L18-L24)
- **Description:** Tests rely on querying `JobCard::first()` and `SparePart::first()` from pre-seeded data rather than creating dedicated test models via factories. Furthermore, assertions wrapped in `if ($jobCardPart)` silently pass with zero assertions if the table is empty.
- **Remediation:** Build model factories and eliminate conditional assertions.

---

### 🟢 Low Severity

#### [ENV-01] Insecure Default `APP_DEBUG=true` in Configuration Example
- **File & Line:** [`.env.example:4`](.env.example#L4)
- **Description:** Deployments initialized from `.env.example` enable verbose debugging by default.
- **Remediation:** Set `APP_DEBUG=false` in `.env.example`.

#### [DOC-01] Missing Project README Setup & Documentation
- **File & Line:** [`README.md:1-58`](README.md#L1-L58)
- **Description:** The README is the stock Laravel boilerplate, lacking installation, environment setup, queue worker, and seeding instructions.
- **Remediation:** Document setup steps, container commands, default roles, and testing procedures.

---

## PSM 2 Readiness Analysis & Schema Gap Report

### 1. Random Forest Predictive Maintenance
- **Current Architecture:** [`app/Services/MaintenancePredictionService.php:14`](app/Services/MaintenancePredictionService.php#L14) utilizes a rule-based weighted linear combination ($0.4 \times \text{km} + 0.3 \times \text{time} + 0.2 \times \text{history} + 0.1 \times \text{trips}$), not machine learning.
- **Data Model Sufficiency:** Insufficient for training a supervised Random Forest model.
- **Missing Features ($X$):**
  - `vehicles`: Missing engine displacement (`cc`), transmission type (`auto`/`manual`), fuel injection system, drivetrain.
  - `service_history`: Has free-text `description` without normalized `job_type_id` foreign keys or structured component taxonomy.
  - `telemetry/sensor_readings`: No time-series tables for diagnostic trouble codes (OBD-II DTCs), coolant temperature, or battery voltage.
- **Missing Labels ($y$):**
  - No ground-truth outcome flag (e.g., `component_failed_within_30d: 0/1` or `time_to_failure_hours`).
- **Target Integration:** Stand up a decoupled Python FastAPI microservice serving a serialized scikit-learn pipeline (`joblib`). Laravel dispatches feature payloads to the Python service via queued HTTP requests.

### 2. Earliest Deadline First (EDF) Scheduling
- **Current Architecture:** [`app/Http/Controllers/JobCardController.php:159-168`](app/Http/Controllers/JobCardController.php#L159-L168) only executes an SQL sort: `orderByRaw('estimated_completion IS NULL, estimated_completion ASC')`.
- **Missing Schema Columns:**
  - `job_cards.deadline` (datetime): True customer delivery deadline.
  - `job_cards.estimated_duration_minutes` (int): Processing time required.
  - `job_cards.priority_level` (enum: emergency, high, normal, low): Required for preemptive scheduling.
  - `job_cards.bay_id` / `workstation_id`: Workshop physical lift/bay capacity constraints.
- **Target Integration:** Build `app/Services/Scheduling/EdfSchedulerService.php` executing online queue scheduling with tardiness minimization.

### 3. Invoicing & Supplier Auto-Ordering
- **Invoices/Payments:** Missing `payments.idempotency_key` (to block double-click duplicate payments) and `invoices.due_date`.
- **Purchase Orders:** Currently structured as 1:1 with `spare_part_id`. Requires a `purchase_order_items` table to support multi-line procurement orders, along with `reorder_threshold` cooldown guards.
