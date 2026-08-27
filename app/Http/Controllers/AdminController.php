<?php
namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\JobCard;
use App\Models\SparePart;
use App\Models\Appointment;
use App\Models\User;
use App\Models\Company;
use App\Models\JobType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    public function index() {
        $recentJobs = JobCard::with(['vehicle','staff','appointment'])->latest()->take(5)->get();

        $staffWorkload = User::where('role','staff')
            ->withCount(['jobCards as active_jobs' => function($q) {
                $q->where('current_stage','!=','completed');
            }])->get();

        return view('admin.dashboard', [
            'totalVehicles'     => Vehicle::count(),
            'totalUsers'        => User::count(),
            'pendingJobs'       => JobCard::where('current_stage','!=','completed')->count(),
            'lowStock'          => SparePart::whereColumn('stock','<=','min_stock')->count(),
            'todayAppointments' => Appointment::whereDate('date', today())->count(),
            'pendingAppts'      => Appointment::where('status','pending')->count(),
            'recentJobs'        => $recentJobs,
            'staffWorkload'     => $staffWorkload,
        ]);
    }

    // Users
    public function users(Request $request) {
    $search = trim((string) $request->input('search'));

    $base = User::query();
    if ($request->role)   $base->where('role',   $request->role);
    if ($request->status) $base->where('status', $request->status);
    if ($search !== '') {
        $base->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        });
    }

    // "All" tab always reflects the true grand total, unaffected by any
    // currently-applied role/status/search filter.
    $totalUsers = User::count();

    $users = $base->with('company')->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

    // Load all job types (for specialties management)
    $jobTypes = \App\Models\JobType::orderBy('category')->get();

    return view('admin.users', compact('users', 'jobTypes', 'search', 'totalUsers'));
    }

    public function showUser(User $user) {
        $user->load('company');
        $vehicles     = null;
        $assignedJobs = null;

        if (in_array($user->role, ['individual','corporate'])) {
            $vehicles = Vehicle::where('user_id', $user->id)->get();
        }
        if ($user->role === 'staff') {
            $assignedJobs = JobCard::with(['vehicle','appointment'])
                ->where('staff_id', $user->id)->latest()->get();
        }
        return view('admin.user-show', compact('user','vehicles','assignedJobs'));
    }

   public function updateUser(Request $request, User $user) {
    $request->validate([
        'name'       => 'required|string|max:100',
        'username'   => 'nullable|string|max:50|unique:users,username,'.$user->id,
        'email'      => 'required|email|unique:users,email,'.$user->id,
        'contact_no' => 'nullable|string|max:20',
        'status'     => 'required|in:active,inactive',
        'company_id' => 'nullable|exists:companies,id',
        'avatar'     => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
    ]);

    $data = [
        'name'       => $request->name,
        'username'   => $request->username,
        'email'      => $request->email,
        'contact_no' => $request->contact_no,
        'status'     => $request->status,
        'company_id' => $request->company_id,
    ];

    if ($request->hasFile('avatar')) {
        if ($user->avatar) Storage::disk('public')->delete($user->avatar);
        $data['avatar'] = $request->file('avatar')->store('avatars','public');
    }

    if ($request->filled('password')) {
        $request->validate([
            'password'              => 'min:6|confirmed',
            'password_confirmation' => 'required',
        ]);
        $data['password'] = Hash::make($request->password);
    }

    $user->update($data);
    return back()->with('success','User updated successfully.');
}

    public function storeUser(Request $request) {
        $request->validate([
            'name'       => 'required|string|max:100',
            'username'   => 'nullable|string|max:50|unique:users',
            'email'      => 'required|email|unique:users',
            'password'   => 'required|min:6',
            'role'       => 'required|in:admin,staff,corporate,individual',
            'contact_no' => 'nullable|string|max:20',
            'avatar'     => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $data = [
            'name'       => $request->name,
            'username'   => $request->username,
            'email'      => $request->email,
            'password'   => Hash::make($request->password),
            'role'       => $request->role,
            'contact_no' => $request->contact_no,
            'status'     => 'active',
        ];

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar')->store('avatars','public');
        }

        User::create($data);
        return back()->with('success','User created successfully.');
    }

    public function toggleStatus(User $user) {
        if ($user->id === auth()->id()) {
            return back()->with('error','You cannot deactivate your own account.');
        }
        $user->update([
            'status' => $user->status === 'active' ? 'inactive' : 'active'
        ]);
        return back()->with('success','User status updated.');
    }

    public function deleteUser(User $user) {
        if ($user->id === auth()->id()) {
            return back()->with('error','You cannot delete your own account.');
        }
        if ($user->avatar) Storage::disk('public')->delete($user->avatar);
        $user->delete();
        return back()->with('success','User deleted.');
    }

    // Companies
    public function companies() {
        $companies = Company::withCount('representatives')->get();
        return view('admin.companies', compact('companies'));
    }

    public function storeCompany(Request $request) {
        $request->validate([
            'name'             => 'required|string|max:100',
            'registration_no'  => 'nullable|string|max:50',
            'address'          => 'nullable|string',
            'phone'            => 'nullable|string|max:20',
            'email'            => 'nullable|email',
            'person_in_charge' => 'nullable|string|max:100',
        ]);
        Company::create($request->all());
        return back()->with('success','Company added.');
    }

    public function showCompany(Company $company) {
        $company->load('representatives');
        return view('admin.company-show', compact('company'));
    }

    // Job Types
    public function jobTypes() {
        $jobTypes = JobType::orderBy('category')->get();
        return view('admin.job-types', compact('jobTypes'));
    }

    public function storeJobType(Request $request) {
        $request->validate([
            'name'               => 'required|string|max:100',
            'category'           => 'required|string',
            'estimated_minutes'  => 'required|integer|min:5',
            'base_price'         => 'required|numeric|min:0',
        ]);
        JobType::create($request->all());
        return back()->with('success','Job type added.');
    }

    public function updateJobType(Request $request, \App\Models\JobType $jobType) {
    $request->validate([
        'name'               => 'required|string|max:100',
        'category'           => 'required|string|max:50',
        'estimated_minutes'  => 'required|integer|min:1',
        'base_price'         => 'required|numeric|min:0',
    ]);

    $jobType->update($request->only(['name','category','estimated_minutes','base_price','description']));

    return back()->with('success', 'Job type updated.');
    }

    public function deleteJobType(JobType $jobType) {
        $jobType->delete();
        return back()->with('success','Job type deleted.');
    }

    public function updateSpecialties(Request $request, \App\Models\User $user) {
    $request->validate([
        'specialties'   => 'nullable|array',
        'specialties.*' => 'exists:job_types,id',
    ]);

    $user->update(['specialties' => $request->specialties ?? []]);

    return back()->with('success', 'Staff specialties updated.');
    }   
}