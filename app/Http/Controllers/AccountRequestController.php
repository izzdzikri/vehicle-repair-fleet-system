<?php
namespace App\Http\Controllers;

use App\Models\AccountRequest;
use App\Models\User;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AccountRequestController extends Controller
{
    // Admin views all pending requests
    public function index() {
        $requests = AccountRequest::with(['requester','company','targetUser'])
            ->orderByRaw("FIELD(status,'pending','approved','rejected')")
            ->latest()->get();
        return view('admin.account-requests', compact('requests'));
    }

    // Corporate PIC submits a request
    public function store(Request $request) {
        $user = auth()->user();

        // Secondary PIC cannot submit account management requests
        if ($user->pic_role === 'secondary') {
            return back()->with('error',
                'Secondary PIC cannot submit account requests. Please contact your Primary PIC.');
        }

        // Validation including pic_role
        $request->validate([
            'type'           => 'required|in:add_pic,remove_pic,close_account',
            'pic_role'       => 'required_if:type,add_pic|nullable|in:primary,secondary',
            'target_name'    => 'required_if:type,add_pic|nullable|string|max:100',
            'target_email'   => 'required_if:type,add_pic|nullable|email|unique:users,email',
            'target_phone'   => 'nullable|string|max:20',
            'target_user_id' => 'required_if:type,remove_pic|nullable|exists:users,id',
            'notes'          => 'nullable|string|max:500',
        ]);

        // Enforce PIC limits before submitting add_pic request
        if ($request->type === 'add_pic') {
            $companyId = $user->company_id;

            $currentPrimary   = User::where('company_id', $companyId)
                                ->where('role', 'corporate')
                                ->where('pic_role', 'primary')
                                ->count();
            $currentSecondary = User::where('company_id', $companyId)
                                ->where('role', 'corporate')
                                ->where('pic_role', 'secondary')
                                ->count();
            $total = $currentPrimary + $currentSecondary;

            if ($total >= 12) {
                return back()->with('error', 'Maximum 12 PICs reached for this company.');
            }
            if ($request->pic_role === 'primary' && $currentPrimary >= 2) {
                return back()->with('error', 'Maximum 2 Primary PICs allowed per company.');
            }
            if ($request->pic_role === 'secondary' && $currentSecondary >= 10) {
                return back()->with('error', 'Maximum 10 Secondary (Viewer) PICs allowed per company.');
            }
        }

        AccountRequest::create([
            'requested_by'   => $user->id,
            'company_id'     => $user->company_id,
            'type'           => $request->type,
            'pic_role'       => $request->pic_role,   // stored for approval
            'target_name'    => $request->target_name,
            'target_email'   => $request->target_email,
            'target_phone'   => $request->target_phone,
            'target_user_id' => $request->target_user_id,
            'notes'          => $request->notes,
            'status'         => 'pending',
        ]);

        return back()->with('success', 'Request submitted. Awaiting admin approval.');
    }

    // Admin approves (UPDATED with enhanced limits)
    public function approve(AccountRequest $accountRequest) {
        if ($accountRequest->status !== 'pending') {
            return back()->with('error', 'Request already processed.');
        }

        switch ($accountRequest->type) {
            case 'add_pic':
                $companyId = $accountRequest->company_id;

                $currentPrimary   = \App\Models\User::where('company_id', $companyId)
                                    ->where('role', 'corporate')
                                    ->where('pic_role', 'primary')
                                    ->count();
                $currentSecondary = \App\Models\User::where('company_id', $companyId)
                                    ->where('role', 'corporate')
                                    ->where('pic_role', 'secondary')
                                    ->count();
                $total = $currentPrimary + $currentSecondary;

                if ($total >= 12) {
                    return back()->with('error', 'Cannot approve — company already has 12 PICs.');
                }

                $requestedRole = $accountRequest->pic_role ?? 'secondary';

                if ($requestedRole === 'primary' && $currentPrimary >= 2) {
                    return back()->with('error', 'Cannot approve — already has 2 Primary PICs.');
                }
                if ($requestedRole === 'secondary' && $currentSecondary >= 10) {
                    return back()->with('error', 'Cannot approve — already has 10 Secondary PICs.');
                }

                \App\Models\User::create([
                    'name'       => $accountRequest->target_name,
                    'email'      => $accountRequest->target_email,
                    'password'   => \Illuminate\Support\Facades\Hash::make('password123'),
                    'role'       => 'corporate',
                    'status'     => 'active',
                    'contact_no' => $accountRequest->target_phone,
                    'company_id' => $companyId,
                    'pic_role'   => $requestedRole,
                ]);
                break;
        }

        $accountRequest->update(['status' => 'approved']);
        return back()->with('success', 'Request approved and processed.');
    }

    // Admin rejects
    public function reject(AccountRequest $accountRequest) {
        $accountRequest->update(['status' => 'rejected']);
        return back()->with('success', 'Request rejected.');
    }

    // Individual user: delete their own account
    public function deleteOwnAccount(Request $request) {
        $request->validate([
            'confirmation' => [
                'required',
                function ($attr, $value, $fail) {
                    if (strtolower(trim($value)) !== 'delete my account') {
                        $fail('You must type exactly: delete my account');
                    }
                }
            ]
        ]);

        $user = auth()->user();

        // Create a logged record then delete
        AccountRequest::create([
            'requested_by'   => $user->id,
            'company_id'     => null,
            'type'           => 'delete_account',
            'target_user_id' => $user->id,
            'status'         => 'approved',
            'notes'          => 'Self-deletion confirmed by user.',
        ]);

        auth()->logout();
        $user->delete();

        return redirect('/login')->with('success', 'Your account has been deleted.');
    }
}