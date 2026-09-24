<?php
namespace App\Http\Controllers;

use App\Models\AccountRequest;
use App\Models\User;
use App\Models\Company;
use App\Models\ActivityLog;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

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

        // Validation including pic_role. target_user_id is scoped to the
        // requester's own company so a PIC can't target an account outside
        // their own company (e.g. another company's PIC, or an admin).
        $request->validate([
            'type'           => 'required|in:add_pic,remove_pic,close_account',
            'pic_role'       => 'required_if:type,add_pic|nullable|in:primary,secondary',
            'target_name'    => 'required_if:type,add_pic|nullable|string|max:100',
            'target_email'   => 'required_if:type,add_pic|nullable|email|unique:users,email',
            'target_phone'   => 'nullable|string|max:20',
            'target_user_id' => [
                'required_if:type,remove_pic',
                'nullable',
                Rule::exists('users', 'id')->where(function ($query) use ($user) {
                    $query->where('company_id', $user->company_id)
                          ->where('role', 'corporate');
                }),
            ],
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

        // remove_pic: block up front if this would leave the company with
        // no active Primary PIC (or no active PIC at all), same guard the
        // approval step enforces below — catching it at submission time
        // saves the requester a wasted round trip through admin approval.
        if ($request->type === 'remove_pic' && $request->target_user_id) {
            $target = User::find($request->target_user_id);

            if ($target) {
                if ($target->isLastActivePicOfCompany()) {
                    return back()->with('error',
                        'This is the last active PIC for the company — removal request cannot be submitted.')->withInput();
                }
                if ($target->isLastActivePrimaryPicOfCompany()) {
                    return back()->with('error',
                        'This is the last active Primary PIC for the company. Add a new Primary PIC first before removing this one.')->withInput();
                }
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

    // Admin approves
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

            case 'remove_pic':
                if (!$accountRequest->target_user_id) {
                    return back()->with('error', 'No target user set for this removal request.');
                }

                $target = \App\Models\User::find($accountRequest->target_user_id);

                if (!$target) {
                    return back()->with('error', 'Target user no longer exists.');
                }

                // Never leave a company with zero active PICs at all.
                if ($target->isLastActivePicOfCompany()) {
                    return back()->with('error', 'Cannot remove — this is the last active PIC for the company.');
                }

                // Never leave a company with zero active Primary PICs,
                // even if Secondary/Viewer PICs remain — Secondary PICs
                // cannot book, manage vehicles, or submit account
                // requests (see User::canBook()/canManage()), so a
                // company left with only Viewers is effectively locked
                // out of self-service even though it still has "active"
                // users on paper.
                if ($target->isLastActivePrimaryPicOfCompany()) {
                    return back()->with('error',
                        'Cannot remove — this is the last active Primary PIC for the company. Approve an add_pic request for a new Primary before removing this one.');
                }

                $target->update(['status' => 'inactive']);
                break;

            case 'close_account':
                \App\Models\User::where('company_id', $accountRequest->company_id)
                    ->where('role', 'corporate')
                    ->update(['status' => 'inactive']);
                break;
        }

        $accountRequest->update(['status' => 'approved']);

        ActivityLog::record(
            'account_request.approved',
            "Approved {$accountRequest->type} request from ".($accountRequest->requester->name ?? 'unknown'),
            $accountRequest
        );

        Notification::send(
            $accountRequest->requested_by,
            'Account Request Approved',
            "Your ".str_replace('_',' ',$accountRequest->type)." request has been approved.",
            '/client/company'
        );

        return back()->with('success', 'Request approved and processed.');
    }

    // Admin rejects
    public function reject(AccountRequest $accountRequest) {
        $accountRequest->update(['status' => 'rejected']);

        ActivityLog::record(
            'account_request.rejected',
            "Rejected {$accountRequest->type} request from ".($accountRequest->requester->name ?? 'unknown'),
            $accountRequest
        );

        Notification::send(
            $accountRequest->requested_by,
            'Account Request Rejected',
            "Your ".str_replace('_',' ',$accountRequest->type)." request has been rejected.",
            '/client/company'
        );

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