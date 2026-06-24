<?php

    use Illuminate\Support\Facades\Route;
    use App\Http\Controllers\AuthController;
    use App\Http\Controllers\AdminController;
    use App\Http\Controllers\StaffController;
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
// Admin
// ----------------------------------------------------------------
    Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');
    Route::post('/users/{user}/specialties', [AdminController::class, 'updateSpecialties'])->name('admin.users.specialties');

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
    // Job Types
    Route::get('/job-types',              [AdminController::class, 'jobTypes'])->name('admin.job-types');
    Route::post('/job-types',             [AdminController::class, 'storeJobType'])->name('admin.job-types.store');
    Route::delete('/job-types/{jobType}', [AdminController::class, 'deleteJobType'])->name('admin.job-types.destroy');
    Route::put('/job-types/{jobType}', [AdminController::class, 'updateJobType'])->name('admin.job-types.update');

    // Appointments (regular + walk-in + complete)
    Route::get('/appointments',                         [AppointmentController::class, 'index'])->name('admin.appointments');
    Route::get('/appointments/walkin',                  [AppointmentController::class, 'walkinCreate'])->name('admin.appointments.walkin');
    Route::post('/appointments/walkin',                 [AppointmentController::class, 'walkinStore'])->name('admin.appointments.walkin.store');
    Route::get('/appointments/{appointment}',           [AppointmentController::class, 'show'])->name('admin.appointments.show');
    Route::patch('/appointments/{appointment}/confirm', [AppointmentController::class, 'confirm'])->name('admin.appointments.confirm');
    Route::patch('/appointments/{appointment}/cancel',  [AppointmentController::class, 'cancel'])->name('admin.appointments.cancel');
    Route::patch('/appointments/{appointment}/complete',[AppointmentController::class, 'complete'])->name('admin.appointments.complete');

    // Job Cards
    Route::resource('job-cards', JobCardController::class);
    Route::patch('/job-cards/{jobCard}/stage',    [JobCardController::class, 'updateStage'])->name('job-cards.stage');
    Route::post('/job-cards/{jobCard}/parts',     [JobCardController::class, 'addPart'])->name('job-cards.add-part');
    Route::post('/job-cards/{jobCard}/symptoms',  [JobCardController::class, 'updateSymptoms'])->name('job-cards.symptoms');
    Route::post('/job-cards/{jobCard}/labour',              [JobCardController::class, 'addLabour'])->name('job-cards.add-labour');
    Route::delete('/job-cards/labour/{labourCharge}',       [JobCardController::class, 'removeLabour'])->name('job-cards.remove-labour');
    
    // Inventory
    Route::resource('spare-parts', SparePartController::class);
    Route::put('/spare-parts/{sparePart}',    [SparePartController::class, 'update'])->name('spare-parts.update');
    Route::delete('/spare-parts/{sparePart}', [SparePartController::class, 'destroy'])->name('spare-parts.destroy');

    // Vehicles
    Route::resource('vehicles', VehicleController::class);

    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('admin.reports');

    // Maintenance
    Route::get('/maintenance',                  [MaintenanceAlertController::class, 'index'])->name('admin.maintenance');
    Route::post('/maintenance',                 [MaintenanceAlertController::class, 'store'])->name('admin.maintenance.store');
    Route::patch('/maintenance/{alert}/read',   [MaintenanceAlertController::class, 'markRead'])->name('admin.maintenance.read');
});

// ----------------------------------------------------------------
// Staff
// ----------------------------------------------------------------
    Route::middleware(['auth', 'role:staff'])->prefix('staff')->group(function () {
    Route::get('/dashboard',                         [StaffController::class, 'index'])->name('staff.dashboard');

    // Walk-in appointment (staff can create too)
    Route::get('/appointments/walkin',               [AppointmentController::class, 'walkinCreate'])->name('staff.appointments.walkin');
    Route::post('/appointments/walkin',              [AppointmentController::class, 'walkinStore'])->name('staff.appointments.walkin.store');

    Route::get('/job-cards/{jobCard}',               [JobCardController::class, 'show'])->name('staff.job-cards.show');
    Route::patch('/job-cards/{jobCard}/stage',       [JobCardController::class, 'updateStage'])->name('staff.job-cards.stage');
    Route::post('/job-cards/{jobCard}/parts',        [JobCardController::class, 'addPart'])->name('staff.job-cards.add-part');
    Route::post('/job-cards/{jobCard}/symptoms',     [JobCardController::class, 'updateSymptoms'])->name('staff.job-cards.symptoms');
    Route::get('/inventory',                         [SparePartController::class, 'index'])->name('staff.inventory');

    // Job Cards
    Route::post('/job-cards/{jobCard}/labour',              [JobCardController::class, 'addLabour'])->name('staff.job-cards.add-labour');
    Route::delete('/job-cards/labour/{labourCharge}',       [JobCardController::class, 'removeLabour'])->name('staff.job-cards.remove-labour');
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
});

// ----------------------------------------------------------------
// Individual
// ----------------------------------------------------------------
    Route::middleware(['auth', 'role:individual'])->prefix('customer')->group(function () {
    Route::get('/dashboard',              [CustomerController::class, 'index'])->name('customer.dashboard');
    Route::resource('appointments',       AppointmentController::class);
    Route::resource('vehicles',           VehicleController::class);
    Route::delete('/account/delete', [AccountRequestController::class, 'deleteOwnAccount'])->name('customer.account.delete');
    // Individual customers also get maintenance alerts
    Route::get('/maintenance',            [MaintenanceAlertController::class, 'index'])->name('customer.maintenance');
    Route::patch('/maintenance/{alert}/read', [MaintenanceAlertController::class, 'markRead'])->name('customer.maintenance.read');
});
