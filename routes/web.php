<?php

    use Illuminate\Support\Facades\Route;
    use App\Http\Controllers\AuthController;
    use App\Http\Controllers\AdminController;
    use App\Http\Controllers\StaffController;
    use App\Http\Controllers\CoordinatorController;
    use App\Http\Controllers\ClientController;
    use App\Http\Controllers\CustomerController;
    use App\Http\Controllers\AppointmentController;
    use App\Http\Controllers\JobCardController;
    use App\Http\Controllers\SparePartController;
    use App\Http\Controllers\VehicleController;
    use App\Http\Controllers\ReportController;
    use App\Http\Controllers\MaintenanceAlertController;
    use App\Http\Controllers\ChatbotController;
    use App\Http\Controllers\AccountRequestController;
    use App\Http\Controllers\ProfileController;
    use App\Http\Controllers\InvoiceController;
    use App\Http\Controllers\StaffManagementController;
    use App\Http\Controllers\SupplierController;
    use App\Http\Controllers\PurchaseOrderController;

    Route::get('/', fn() => redirect('/login'));
    Route::post('/chatbot/reply', [ChatbotController::class, 'reply'])->name('chatbot.reply');

    // Auth
    Route::get('/login',    [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login',   [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register',[AuthController::class, 'register']);
    Route::post('/logout',  [AuthController::class, 'logout'])->name('logout');

    // Profile (all roles)
    Route::middleware(['auth'])->group(function () {
    Route::get('/profile',  [ProfileController::class, 'show'])->name('profile');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
    });

// ----------------------------------------------------------------
// Permission-gated actions — available to admin automatically, and to
// any staff member holding the specific permission (e.g. an accountant
// or inventory manager sub-role). Kept outside the /admin prefix so
// non-admin staff with the right permission can also reach it.
// ----------------------------------------------------------------
Route::middleware(['auth', 'permission:inventory.manage'])->group(function () {
    Route::post('/inventory/spare-parts',              [SparePartController::class, 'store'])->name('inventory.spare-parts.store');
    Route::put('/inventory/spare-parts/{sparePart}',    [SparePartController::class, 'update'])->name('inventory.spare-parts.update');
    Route::delete('/inventory/spare-parts/{sparePart}', [SparePartController::class, 'destroy'])->name('inventory.spare-parts.destroy');
});

// Job Type (labour) pricing — same pattern as inventory above. The
// listing page itself is gated too, since it's an internal pricing
// config page, not something every staff member needs to see.
Route::middleware(['auth', 'permission:pricing.manage'])->prefix('pricing')->group(function () {
    Route::get('/job-types',              [AdminController::class, 'jobTypes'])->name('pricing.job-types');
    Route::post('/job-types',             [AdminController::class, 'storeJobType'])->name('pricing.job-types.store');
    Route::put('/job-types/{jobType}',    [AdminController::class, 'updateJobType'])->name('pricing.job-types.update');
    Route::delete('/job-types/{jobType}', [AdminController::class, 'deleteJobType'])->name('pricing.job-types.destroy');
});

// ----------------------------------------------------------------
// Admin
// ----------------------------------------------------------------
    Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');
    Route::post('/users/{user}/specialties', [AdminController::class, 'updateSpecialties'])->name('admin.users.specialties');
    Route::post('/users/{user}/role',        [AdminController::class, 'updateRole'])->name('admin.users.role');

    // Users
    Route::get('/users',                 [AdminController::class, 'users'])->name('admin.users');
    Route::post('/users',                [AdminController::class, 'storeUser'])->name('admin.users.store');
    Route::get('/users/{user}',          [AdminController::class, 'showUser'])->name('admin.users.show');
    Route::post('/users/{user}',         [AdminController::class, 'updateUser'])->name('admin.users.update');
    Route::patch('/users/{user}/toggle', [AdminController::class, 'toggleStatus'])->name('admin.users.toggle');
    Route::delete('/users/{user}',       [AdminController::class, 'deleteUser'])->name('admin.users.destroy');

    // Companies
    Route::get('/companies',             [AdminController::class, 'companies'])->name('admin.companies');
    Route::post('/companies',            [AdminController::class, 'storeCompany'])->name('admin.companies.store');
    Route::get('/companies/{company}',   [AdminController::class, 'showCompany'])->name('admin.companies.show');
    Route::get('/account-requests',                      [AccountRequestController::class, 'index'])->name('admin.account-requests');
    Route::patch('/account-requests/{accountRequest}/approve', [AccountRequestController::class, 'approve'])->name('admin.account-requests.approve');
    Route::patch('/account-requests/{accountRequest}/reject',  [AccountRequestController::class, 'reject'])->name('admin.account-requests.reject');

    // Appointments (regular + walk-in + complete)
    Route::get('/appointments',                         [AppointmentController::class, 'index'])->name('admin.appointments');
    Route::get('/appointments/queue',                   [AppointmentController::class, 'queue'])->name('admin.appointments.queue');
    Route::get('/appointments/walkin',                  [AppointmentController::class, 'walkinCreate'])->name('admin.appointments.walkin');
    Route::post('/appointments/walkin',                 [AppointmentController::class, 'walkinStore'])->name('admin.appointments.walkin.store');
    Route::get('/appointments/{appointment}',           [AppointmentController::class, 'show'])->name('admin.appointments.show');
    Route::patch('/appointments/{appointment}/confirm', [AppointmentController::class, 'confirm'])->name('admin.appointments.confirm');
    Route::patch('/appointments/{appointment}/cancel',  [AppointmentController::class, 'cancel'])->name('admin.appointments.cancel');
    Route::patch('/appointments/{appointment}/complete',[AppointmentController::class, 'complete'])->name('admin.appointments.complete');

    // Job Cards
    Route::get('/job-cards/schedule', [JobCardController::class, 'schedule'])->name('admin.job-cards.schedule');
    Route::resource('job-cards', JobCardController::class);
    Route::patch('/job-cards/{jobCard}/stage',    [JobCardController::class, 'updateStage'])->name('job-cards.stage');
    Route::post('/job-cards/{jobCard}/parts',     [JobCardController::class, 'addPart'])->name('job-cards.add-part');
    Route::post('/job-cards/{jobCard}/symptoms',  [JobCardController::class, 'updateSymptoms'])->name('job-cards.symptoms');
    Route::post('/job-cards/{jobCard}/labour',              [JobCardController::class, 'addLabour'])->name('job-cards.add-labour');
    Route::delete('/job-cards/labour/{labourCharge}',       [JobCardController::class, 'removeLabour'])->name('job-cards.remove-labour');

    // Inventory (view only here — add/edit/delete handled by the permission-gated group above)
    Route::get('/spare-parts', [SparePartController::class, 'index'])->name('admin.spare-parts.index');

    // Vehicles
    Route::resource('vehicles', VehicleController::class);

    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('admin.reports');

    // Maintenance
    Route::get('/maintenance',                  [MaintenanceAlertController::class, 'index'])->name('admin.maintenance');
    Route::post('/maintenance',                 [MaintenanceAlertController::class, 'store'])->name('admin.maintenance.store');
    Route::patch('/maintenance/{alert}/read',   [MaintenanceAlertController::class, 'markRead'])->name('admin.maintenance.read');

    // Invoices & Payments
    Route::get('/invoices',                      [InvoiceController::class, 'index'])->name('admin.invoices');
    Route::post('/invoices/generate',            [InvoiceController::class, 'generate'])->name('admin.invoices.generate');
    Route::get('/invoices/{invoice}',            [InvoiceController::class, 'show'])->name('admin.invoices.show');
    Route::post('/invoices/{invoice}/payments',  [InvoiceController::class, 'addPayment'])->name('admin.invoices.payments');
    Route::post('/invoices/{invoice}/resend',    [InvoiceController::class, 'resend'])->name('admin.invoices.resend');

    // Staff Management — attendance, leave, performance, salary
    Route::get('/staff-management/attendance',            [StaffManagementController::class, 'attendance'])->name('admin.staff-management.attendance');
    Route::get('/staff-management/leave',                 [StaffManagementController::class, 'leave'])->name('admin.staff-management.leave');
    Route::patch('/staff-management/leave/{leaveRequest}/approve', [StaffManagementController::class, 'approveLeave'])->name('admin.staff-management.leave.approve');
    Route::patch('/staff-management/leave/{leaveRequest}/reject',  [StaffManagementController::class, 'rejectLeave'])->name('admin.staff-management.leave.reject');
    Route::get('/staff-management/performance',           [StaffManagementController::class, 'performance'])->name('admin.staff-management.performance');
    Route::post('/staff-management/performance/email',    [StaffManagementController::class, 'emailPerformanceReport'])->name('admin.staff-management.performance.email');
    Route::get('/staff-management/salary',                [StaffManagementController::class, 'salary'])->name('admin.staff-management.salary');
    Route::post('/staff-management/salary',                [StaffManagementController::class, 'storeSalaryPayment'])->name('admin.staff-management.salary.store');

    // Suppliers & Purchase Orders
    Route::get('/suppliers',               [SupplierController::class, 'index'])->name('admin.suppliers');
    Route::post('/suppliers',              [SupplierController::class, 'store'])->name('admin.suppliers.store');
    Route::put('/suppliers/{supplier}',    [SupplierController::class, 'update'])->name('admin.suppliers.update');
    Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy'])->name('admin.suppliers.destroy');
    Route::get('/purchase-orders',                        [PurchaseOrderController::class, 'index'])->name('admin.purchase-orders');
    Route::post('/purchase-orders',                       [PurchaseOrderController::class, 'store'])->name('admin.purchase-orders.store');
    Route::patch('/purchase-orders/{purchaseOrder}/order', [PurchaseOrderController::class, 'markOrdered'])->name('admin.purchase-orders.order');
    Route::patch('/purchase-orders/{purchaseOrder}/receive',[PurchaseOrderController::class, 'markReceived'])->name('admin.purchase-orders.receive');
    Route::patch('/purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel'])->name('admin.purchase-orders.cancel');
});

// ----------------------------------------------------------------
// Staff
// ----------------------------------------------------------------
    Route::middleware(['auth', 'role:staff'])->prefix('staff')->group(function () {
    Route::get('/dashboard',                         [StaffController::class, 'index'])->name('staff.dashboard');

    // Walk-in appointment (staff can create too)
    Route::get('/appointments/walkin',               [AppointmentController::class, 'walkinCreate'])->name('staff.appointments.walkin');
    Route::post('/appointments/walkin',              [AppointmentController::class, 'walkinStore'])->name('staff.appointments.walkin.store');
    Route::get('/appointments/queue',                [AppointmentController::class, 'queue'])->name('staff.appointments.queue');

    Route::get('/job-cards/schedule',                [JobCardController::class, 'schedule'])->name('staff.job-cards.schedule');
    Route::get('/job-cards/{jobCard}',               [JobCardController::class, 'show'])->name('staff.job-cards.show');
    Route::patch('/job-cards/{jobCard}/stage',       [JobCardController::class, 'updateStage'])->name('staff.job-cards.stage');
    Route::post('/job-cards/{jobCard}/parts',        [JobCardController::class, 'addPart'])->name('staff.job-cards.add-part');
    Route::post('/job-cards/{jobCard}/symptoms',     [JobCardController::class, 'updateSymptoms'])->name('staff.job-cards.symptoms');
    Route::get('/inventory',                         [SparePartController::class, 'index'])->name('staff.inventory');

    // Job Cards
    Route::post('/job-cards/{jobCard}/labour',              [JobCardController::class, 'addLabour'])->name('staff.job-cards.add-labour');
    Route::delete('/job-cards/labour/{labourCharge}',       [JobCardController::class, 'removeLabour'])->name('staff.job-cards.remove-labour');

    // Invoices & Payments
    Route::get('/invoices',                      [InvoiceController::class, 'index'])->name('staff.invoices');
    Route::post('/invoices/generate',            [InvoiceController::class, 'generate'])->name('staff.invoices.generate');
    Route::get('/invoices/{invoice}',            [InvoiceController::class, 'show'])->name('staff.invoices.show');
    Route::post('/invoices/{invoice}/payments',  [InvoiceController::class, 'addPayment'])->name('staff.invoices.payments');
    Route::post('/invoices/{invoice}/resend',    [InvoiceController::class, 'resend'])->name('staff.invoices.resend');

    // Staff Management — own attendance + leave
    Route::get('/staff-management/attendance',              [StaffManagementController::class, 'attendance'])->name('staff.staff-management.attendance');
    Route::post('/staff-management/attendance/clock-in',    [StaffManagementController::class, 'clockIn'])->name('staff.staff-management.clock-in');
    Route::post('/staff-management/attendance/clock-out',   [StaffManagementController::class, 'clockOut'])->name('staff.staff-management.clock-out');
    Route::get('/staff-management/leave',                   [StaffManagementController::class, 'leave'])->name('staff.staff-management.leave');
    Route::post('/staff-management/leave',                  [StaffManagementController::class, 'storeLeave'])->name('staff.staff-management.leave.store');
});

// ----------------------------------------------------------------
// Coordinator — read-only repair progress monitoring
// ----------------------------------------------------------------
    Route::middleware(['auth', 'role:coordinator'])->prefix('coordinator')->group(function () {
    Route::get('/dashboard',                         [CoordinatorController::class, 'dashboard'])->name('coordinator.dashboard');
    Route::get('/job-cards',                         [CoordinatorController::class, 'jobCards'])->name('coordinator.job-cards');
    Route::get('/job-cards/{jobCard}',                [CoordinatorController::class, 'showJobCard'])->name('coordinator.job-cards.show');
    Route::post('/job-cards/{jobCard}/checkin',       [CoordinatorController::class, 'storeCheckin'])->name('coordinator.job-cards.checkin');
});

// ----------------------------------------------------------------
// Corporate
// ----------------------------------------------------------------
Route::middleware(['auth', 'role:corporate'])->prefix('client')->group(function () {
    Route::get('/dashboard',              [ClientController::class, 'index'])->name('client.dashboard');
    Route::resource('appointments',       AppointmentController::class);
    Route::resource('vehicles',           VehicleController::class);
    Route::get('/maintenance',            [MaintenanceAlertController::class, 'index'])->name('client.maintenance');
    Route::post('/account-requests', [AccountRequestController::class, 'store'])->name('client.account-requests.store');
    Route::patch('/maintenance/{alert}/read', [MaintenanceAlertController::class, 'markRead'])->name('client.maintenance.read');
    Route::get('/company', [ClientController::class, 'company'])->name('client.company');
    Route::get('/invoices',           [InvoiceController::class, 'index'])->name('client.invoices');
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('client.invoices.show');
});

// ----------------------------------------------------------------
// Individual
// ----------------------------------------------------------------
    Route::middleware(['auth', 'role:individual'])->prefix('customer')->group(function () {
    Route::get('/dashboard',              [CustomerController::class, 'index'])->name('customer.dashboard');
    Route::resource('appointments',       AppointmentController::class);
    Route::resource('vehicles',           VehicleController::class);
    Route::delete('/account/delete', [AccountRequestController::class, 'deleteOwnAccount'])->name('customer.account.delete');
    Route::get('/maintenance',            [MaintenanceAlertController::class, 'index'])->name('customer.maintenance');
    Route::patch('/maintenance/{alert}/read', [MaintenanceAlertController::class, 'markRead'])->name('customer.maintenance.read');
    Route::get('/invoices',           [InvoiceController::class, 'index'])->name('customer.invoices');
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('customer.invoices.show');
});