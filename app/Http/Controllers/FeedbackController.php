<?php
namespace App\Http\Controllers;

use App\Models\JobCard;
use App\Models\JobFeedback;
use App\Models\User;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function store(Request $request, JobCard $jobCard) {
        $request->validate([
            'rating'  => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $user = auth()->user();
        $jobCard->load('appointment');
        $ownerId = $jobCard->appointment->user_id ?? null;

        $allowed = false;
        if ($user->role === 'individual') {
            $allowed = $ownerId === $user->id;
        } elseif ($user->role === 'corporate') {
            $companyUserIds = User::where('company_id', $user->company_id)->pluck('id');
            $allowed = $ownerId && $companyUserIds->contains($ownerId);
        }
        abort_unless($allowed, 403, 'You do not have access to rate this job.');

        if ($jobCard->current_stage !== 'completed') {
            return back()->with('error', 'Feedback can only be submitted once the job is completed.');
        }

        JobFeedback::updateOrCreate(
            ['job_card_id' => $jobCard->id],
            ['rating' => $request->rating, 'comment' => $request->comment]
        );

        return back()->with('success', 'Thank you for your feedback!');
    }
}