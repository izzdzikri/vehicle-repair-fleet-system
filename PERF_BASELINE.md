# Performance Baseline Report: AI-Driven Vehicle Repair and Fleet Management System

**Branch:** `optimize`  
**Measurement Environment:** MariaDB 11.8.9, PHP 8.4.21, Laravel 13  
**Database:** `teraju_perf` (Synthetic High-Volume Dataset)  
**Data Volume:**
- **Companies:** 50
- **Users:** 550 (200+ Corporate Fleet PICs, Mechanics, Admins)
- **Vehicles:** 5,000
- **Appointments:** 20,007
- **Job Cards:** 20,000
- **Activity Logs:** 50,000

**PHPUnit Baseline Status:** 46 tests, 209 assertions, 100% passing (`duration: 6.67s`)  
**Laravel Pint Status:** 100% passed (`./vendor/bin/pint --test`)  

---

## 1. Baseline Measurements by Endpoint

Measurements were taken using `DB::listen` query profiling, `hrtime(true)` high-resolution execution timers, and `memory_get_peak_usage()` tracking:

| Endpoint | Method | Role | Status | Queries | Query Time | Response Time | Peak Memory |
| :--- | :--- | :--- | :---: | :---: | :---: | :---: | :---: |
| **Admin Dashboard** | `GET /admin/dashboard` | Admin | 200 | **165,198** | **43,753.23 ms** | **24,718.42 ms** | 105.56 MB |
| **Staff Dashboard** | `GET /staff/dashboard` | Staff | 200 | 48 | 96.84 ms | 306.63 ms | 105.56 MB |
| **Coordinator Dashboard** | `GET /coordinator/dashboard` | Coordinator | 200 | 65 | 250.75 ms | 485.72 ms | 105.56 MB |
| **Corporate Client Dashboard** | `GET /client/dashboard` | Corporate PIC | 200 | 168 | 53.34 ms | 19.95 ms | 105.56 MB |
| **Customer Dashboard** | `GET /customer/dashboard` | Customer | 200 | 119 | 42.14 ms | 13.80 ms | 105.56 MB |
| **Admin Job Cards List** | `GET /admin/job-cards` | Admin | 200 | 272 | 174.48 ms | 37.00 ms | 105.56 MB |
| **Coordinator Job Cards List** | `GET /coordinator/job-cards` | Coordinator | 200 | 90 | 460.17 ms | 1,430.86 ms | 105.56 MB |
| **Job Card Detail (Show)** | `GET /admin/job-cards/{id}` | Admin | 200 | 170 | 46.80 ms | 31.96 ms | 105.56 MB |
| **Admin Vehicles List** | `GET /admin/vehicles` | Admin | 200 | 77 | 26.84 ms | 7.97 ms | 105.56 MB |
| **Corporate Vehicles List** | `GET /client/vehicles` | Corporate PIC | 200 | 108 | 29.16 ms | 6.72 ms | 105.56 MB |
| **Corporate Company View** | `GET /client/company` | Corporate PIC | 200 | 104 | 27.43 ms | 6.99 ms | 105.56 MB |
| **Corporate Trip Logs View** | `GET /client/trip-logs` | Corporate PIC | 200 | **658** | 160.16 ms | 28.49 ms | 105.56 MB |
| **Corporate Fleet Statement** | `GET /client/statement` | Corporate PIC | 200 | 150 | 42.30 ms | 8.16 ms | 105.56 MB |
| **Admin Reports & Charts** | `GET /admin/reports` | Admin | 200 | **592** | **1,368.00 ms** | 103.59 ms | 105.56 MB |
| **Chatbot Reply Endpoint** | `POST /chatbot/reply` | Guest | 419* | 34 | 10.37 ms | 10.23 ms | 105.56 MB |
| **Login Page** | `GET /login` | Guest | 200 | 3 | 1.99 ms | 27.33 ms | 6.49 MB |
| **Login Action** | `POST /login` | Guest | 419* | 4 | 1.40 ms | 6.53 ms | 6.73 MB |

*\*419 on raw POST indicates CSRF middleware verification active.*

---

## 2. Identified Primary Bottlenecks (Ranked by Cost)

### 🔴 Critical Bottleneck #1: Admin Dashboard Demand Forecasting Loop & Autorun
- **Location:** [`AdminController.php:33-37, 73-77`](app/Http/Controllers/AdminController.php#L33), [`MaintenancePredictionService.php:737-780`](app/Services/MaintenancePredictionService.php#L737)
- **Symptoms:** 165,198 database queries executed on a single page request. Over 43 seconds of query execution time.
- **Root Cause:**
  - `getDemandForecast(60)` performs an unconstrained `Vehicle::with(['owner.company'])->get()`, loading all 5,000 vehicles into memory.
  - Inside a nested PHP loop (for each of 5,000 vehicles and 10 job types), it executes `ServiceHistory::where('vehicle_id', ...)` queries ($5,000 \times 10 = 50,000$ queries).
  - `Cache::remember('maintenance_predictions_autorun')` executes `runAll()` synchronously during web requests on cold cache, performing full chunked table scans.
- **Remediation Strategy:**
  - Cache `demandForecast` and `offSeasonSavings` with appropriate TTL, or compute aggregations using direct SQL joins on `service_history` and `job_cards` rather than 50,000 individual queries.
  - Defer `runAll()` background prediction runs to an asynchronous queue job (`dispatch()`) or scheduled console command rather than executing synchronously inside web request threads.

### 🟠 Bottleneck #2: Corporate Trip Logs View N+1 Usage Stats
- **Location:** [`TripLogController.php:68-71`](app/Http/Controllers/TripLogController.php#L68)
- **Symptoms:** 658 database queries.
- **Root Cause:**
  - `TripLogController@index` iterates over all vehicles in the company's fleet and calls `$predictionService->getUsageStats($v)` on each vehicle in a `foreach` loop. Each `getUsageStats` call queries `TripLog::where('vehicle_id', ...)`.
- **Remediation Strategy:**
  - Batch calculate or eager load trip metrics, or compute usage stats only for the selected vehicle instead of calculating all 50+ fleet vehicles on every page load.

### 🟠 Bottleneck #3: Admin Reports & Corporate Fleet Statements N+1 Aggregates
- **Location:** [`ReportController.php`](app/Http/Controllers/ReportController.php), [`ClientController.php:142-156`](app/Http/Controllers/ClientController.php#L142)
- **Symptoms:** 592 queries on `/admin/reports`, 150 queries on `/client/statement`.
- **Root Cause:**
  - Client statement maps over `$vehicles` and filters loaded collections or runs individual vehicle queries in PHP.
- **Remediation Strategy:**
  - Optimize with SQL `GROUP BY vehicle_id` aggregations.

### 🟡 Bottleneck #4: Job Card List and Detail N+1 Eager Loading
- **Location:** [`JobCardController.php:15-60`](app/Http/Controllers/JobCardController.php#L15), [`views/job-cards/index.blade.php`](resources/views/job-cards/index.blade.php)
- **Symptoms:** 272 queries on `/admin/job-cards`, 170 queries on `/admin/job-cards/{id}`.
- **Root Cause:**
  - Missing eager loading of relations (e.g. `vehicle.owner`, `parts.sparePart`, `appointment`, `staff`).
- **Remediation Strategy:**
  - Add eager loading (`with()`) on `job_cards.index` and `show`.
