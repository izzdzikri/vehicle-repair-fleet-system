<?php

namespace App\Console\Commands;

use App\Models\CompanyDocument;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckDocumentExpiry extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'documents:check-expiry';

    /**
     * The console command description.
     */
    protected $description = 'Scan company documents for upcoming expiry milestones (30, 14, 7, and 0 days) and dispatch idempotent notifications to admins.';

    /**
     * Target expiry milestones in days.
     */
    public const MILESTONES = [30, 14, 7, 0];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $admins = User::where('role', 'admin')
            ->where('status', 'active')
            ->get();

        if ($admins->isEmpty()) {
            $this->warn('No active admin users found to receive expiry notifications.');
            return self::SUCCESS;
        }

        $documents = CompanyDocument::whereNotNull('expiry_date')
            ->whereNull('deleted_at')
            ->get();

        $notificationsSent = 0;
        $today = Carbon::today();

        foreach ($documents as $document) {
            $expiry = $document->expiry_date->copy()->startOfDay();
            $daysRemaining = (int) $today->diffInDays($expiry, false);

            if (! in_array($daysRemaining, self::MILESTONES, true)) {
                continue;
            }

            $milestoneTag = $daysRemaining === 0 ? 'today' : "{$daysRemaining}d";
            $milestoneLabel = match ($daysRemaining) {
                0 => 'expires today',
                7 => 'expires in 7 days',
                14 => 'expires in 14 days',
                30 => 'expires in 30 days',
                default => "expires in {$daysRemaining} days",
            };

            $title = "Document Expiry: {$document->title} [{$milestoneTag}]";
            $message = $daysRemaining === 0
                ? "Company document '{$document->title}' (Ref: {$document->reference_no}) expires today ({$expiry->format('d M Y')}). Please review or renew."
                : "Company document '{$document->title}' (Ref: {$document->reference_no}) {$milestoneLabel} on {$expiry->format('d M Y')}.";
            $url = "/admin/documents/{$document->id}";

            foreach ($admins as $admin) {
                $alreadySent = Notification::where('user_id', $admin->id)
                    ->where('url', $url)
                    ->where('title', $title)
                    ->exists();

                if (! $alreadySent) {
                    Notification::send($admin->id, $title, $message, $url);
                    $notificationsSent++;
                }
            }
        }

        $this->info("Expiry check complete. Dispatched {$notificationsSent} notification(s) across {$admins->count()} admin(s).");

        return self::SUCCESS;
    }
}
