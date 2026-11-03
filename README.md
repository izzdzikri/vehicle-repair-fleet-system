# AI-Driven Vehicle Repair and Fleet Management System
### Final Year Project (PSM) — Teraju Setia Enterprise

An enterprise-grade workshop and corporate fleet management system developed with Laravel, Blade, Alpine.js, Tailwind CSS, and MariaDB/MySQL. The system streamlines automotive maintenance operations with a 6-stage job card workflow, corporate multi-PIC fleet governance, intelligent chatbot appointment booking, predictive maintenance scheduling, and real-time inventory tracking.

---

## 🛠 Tech Stack

- **Backend Framework:** Laravel 12 / 13 (PHP 8.2+ / PHP 8.4)
- **Frontend & UI:** Blade Templates, Tailwind CSS, Alpine.js, Chart.js
- **Database:** MariaDB 11.x / MySQL 8.0+
- **Security & Authorization:** Laravel Gates, Policies, Form Requests, Rate Limiting, 2FA with Encrypted Secrets
- **Testing & Quality:** PHPUnit, Laravel Pint (PSR-12 code style)
- **Queue & Background Jobs:** Database Queue Driver

---

## 🚀 Key Features

1. **Strict 6-Stage Job Card Workflow:**
   - Sequential progression: `received` ➔ `diagnosing` ➔ `parts_pending` ➔ `in_repair` ➔ `ready_qc` ➔ `completed`.
   - Backward rollback allowed for diagnosis re-evaluations while maintaining parts & labour consistency.
   - Concurrency protection: `lockForUpdate` prevents simultaneous stock deductions and negative inventory.
   - Database-level `CHECK (stock >= 0)` constraint on spare parts.

2. **Corporate Multi-PIC Fleet Portal:**
   - Multi-tenant company isolation: corporate representatives can only view and manage vehicles belonging to their registered company (`company_id`).
   - Granular authorization via `VehiclePolicy`, `JobCardPolicy`, and `AppointmentPolicy`.
   - Primary & Secondary PIC role demarcation for fleet approvals.

3. **Intelligent Chatbot & Diagnostic Booking Engine:**
   - Dual engine: Rule-based heuristic pattern matching with optional Google Gemini AI integration.
   - Multi-language support (Bahasa Melayu & English).
   - Race condition prevention on appointment slots with database transactions and pessimistic row locks.
   - Strict input validation preventing vehicle tampering and unauthenticated data manipulation.
   - Throttled endpoint (`throttle:60,1`) with XSS protocol sanitization in markdown links.

4. **Predictive Maintenance & Health Scoring:**
   - Formula-based mileage and timeline interval degradation tracking.
   - Automated high/medium priority alerts for upcoming brake, oil, timing belt, and battery replacements.
   - PSM 2 ML readiness: Schema prepared for scikit-learn Random Forest model ingestion.

5. **Financial Management & Billing:**
   - Automated subtotal, SST tax calculation, payments tracking, and PDF invoice generation.
   - Encapsulated financial calculations via Eloquent accessors (`labour_total`, `parts_total`, `grand_total`).
   - Granular invoice management permissions guarding manual payment approvals.

---

## 📋 System Requirements & Prerequisites

- **PHP:** 8.2 or higher (tested on PHP 8.4.21)
  - Required Extensions: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `curl`
- **Database:** MariaDB 11.x or MySQL 8.0+
- **Composer:** 2.x
- **Node.js:** v18+ & NPM (for Tailwind asset compilation)
- **Container Environment (Optional):** Supports execution via Podman / Docker containers and Distrobox toolboxes.

---

## ⚙️ Installation & Setup Guide

### 1. Clone the Repository
```bash
git clone https://github.com/your-username/vehicle-repair-fleet-system.git
cd vehicle-repair-fleet-system
```

### 2. Environment Configuration
Copy the template configuration file:
```bash
cp .env.example .env
```
Update your `.env` file with your database credentials:
```env
APP_NAME="Teraju Fleet & Repair Management System"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=teraju_db
DB_USERNAME=teraju_user
DB_PASSWORD=your_password
```

### 3. Install PHP Dependencies
```bash
composer install
```

### 4. Generate Application Encryption Key
```bash
php artisan key:generate
```

### 5. Run Database Migrations & Seed Sample Data
Execute the database migrations along with the complete realistic demo dataset (FreshSeeder + Psm2FeatureSeeder):
```bash
php artisan migrate:fresh --seed
```

### 6. Install & Compile Frontend Assets
```bash
npm install
npm run build
# Or for live hot-reloading during development:
npm run dev
```

### 7. Run the Application & Background Queue
Start the PHP development server:
```bash
php artisan serve
```
In a separate terminal, start the queue worker for background notification processing:
```bash
php artisan queue:work
```
Access the application at `http://localhost:8000`.

---

## 👥 Default Demo Credentials

All seeded user accounts share the default development password: `password`

| Role | Portal URL | Seeded Email | Description |
| :--- | :--- | :--- | :--- |
| **Admin** | `/admin/dashboard` | `admin@teraju.my` | Full system governance, staff management, revenue reports |
| **Staff (Master Mechanic)** | `/staff/dashboard` | `ridhwan@teraju.my` | Job card stage management, parts requisition, QC sign-off |
| **Staff (Specialist)** | `/staff/dashboard` | `nabilah@teraju.my` | Diagnostic inspection & repair execution |
| **Coordinator** | `/coordinator/dashboard` | `aisyah@teraju.my` | Appointment booking intake, walk-in customer reception |
| **Corporate PIC (Primary)** | `/client/dashboard` | `hafiz@mulialogistik.com.my` | Syarikat Logistik Mulia fleet monitoring & maintenance requests |
| **Corporate PIC (Secondary)**| `/client/dashboard` | `fifi@mulialogistik.com.my` | Fleet dispatch operations & trip logs |
| **Individual Customer** | `/customer/dashboard`| `ain@gmail.com` | Personal vehicle service tracking & appointment history |

---

## 🧪 Testing & Code Quality

### Running Automated Tests
The application includes a comprehensive PHPUnit suite covering security policies, workflow validation, and race condition handling:
```bash
php artisan test
```
To run a specific test suite:
```bash
php artisan test tests/Feature/SecurityPolicyAndWorkflowTest.php
```

### Code Formatting with Laravel Pint
To verify and format PHP code against PSR-12 and Laravel standards:
```bash
./vendor/bin/pint
# Check without modifying files:
./vendor/bin/pint --test
```

---

## 🛡 Security Architecture Highlights

- **Policy-Enforced Access Control:** Every access to `Vehicle`, `JobCard`, `Invoice`, and `Appointment` runs through Laravel Policies, eliminating Insecure Direct Object References (IDOR).
- **Multi-Tenant Data Isolation:** Corporate users are scoped strictly to their company records (`company_id`). Null-company users are blocked with HTTP 403 Forbidden.
- **Race Condition Immunity:** Stock deductions and appointment booking capacity utilize `lockForUpdate` within database transactions to ensure serializable execution under high concurrency.
- **XSS & Protocol Whitelisting:** Blade templates enforce strict HTML entity escaping, and client-side markdown parsers strictly whitelist safe schemes (`http`, `https`, `mailto`, `tel`).
- **Encrypted 2FA Secrets:** Two-factor authentication keys and recovery secrets are cast to `encrypted` in the database.
- **Strict Mass Assignment:** Controllers exclusively utilize `$request->validated()` from dedicated Form Requests (`StoreAppointmentRequest`, `RecordPaymentRequest`, `StoreVehicleRequest`, `UpdateJobCardStageRequest`).

---

## 🔮 PSM 2 Roadmap & Next Steps

1. **Random Forest Predictive Engine:**
   - Dedicated Python/FastAPI microservice for training on historical telemetry (`mileage`, `operating_hours`, `symptoms`, `part_replacement_history`).
   - Predicting Remaining Useful Life (RUL) and workshop service duration.
2. **Earliest Deadline First (EDF) Dynamic Dispatch:**
   - Algorithmic scheduler prioritizing vehicles based on corporate SLA deadlines, emergency severity, and mechanic skill sets.
3. **Automated Supplier Replenishment:**
   - Automatic purchase order creation when stock reaches `min_stock` threshold, integrated with supplier API webhooks.
