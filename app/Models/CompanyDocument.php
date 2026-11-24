<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyDocument extends Model
{
    use HasFactory, SoftDeletes;

    public const CATEGORIES = [
        'ssm_certificate' => 'SSM Certificate',
        'delivery_order' => 'Delivery Order',
        'other' => 'Other Document',
    ];

    protected $fillable = [
        'title',
        'category',
        'reference_no',
        'issue_date',
        'expiry_date',
        'notes',
        'file_path',
        'original_name',
        'mime',
        'size',
        'uploaded_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'size' => 'integer',
    ];

    // ----------------------------------------------------------------
    // Relationships
    // ----------------------------------------------------------------

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // ----------------------------------------------------------------
    // Accessors & Helpers
    // ----------------------------------------------------------------

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? ucfirst(str_replace('_', ' ', $this->category));
    }

    /**
     * Number of calendar days from today until expiry date.
     * Returns 0 if expiring today, negative if already expired, null if no expiry date.
     */
    public function getDaysUntilExpiryAttribute(): ?int
    {
        if (! $this->expiry_date) {
            return null;
        }

        $today = Carbon::today();
        $expiry = $this->expiry_date->copy()->startOfDay();

        return (int) $today->diffInDays($expiry, false);
    }

    /**
     * Expiry status: 'valid', 'expiring_soon', or 'expired'.
     */
    public function getExpiryStatusAttribute(): string
    {
        if (! $this->expiry_date) {
            return 'valid';
        }

        $days = $this->days_until_expiry;

        if ($days < 0) {
            return 'expired';
        }

        if ($days <= 30) {
            return 'expiring_soon';
        }

        return 'valid';
    }

    public function getExpiryStatusLabelAttribute(): string
    {
        if (! $this->expiry_date) {
            return 'Valid (No Expiry)';
        }

        $days = $this->days_until_expiry;

        if ($days < 0) {
            $abs = abs($days);
            return $abs === 1 ? 'Expired yesterday' : "Expired {$abs} days ago";
        }

        if ($days === 0) {
            return 'Expires today';
        }

        if ($days <= 30) {
            return $days === 1 ? 'Expires tomorrow' : "Expiring in {$days} days";
        }

        return 'Valid';
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = (int) $this->size;

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        }

        return number_format($bytes / 1024, 1).' KB';
    }

    public function isPdf(): bool
    {
        return $this->mime === 'application/pdf' || str_ends_with(strtolower($this->original_name), '.pdf');
    }

    public function isImage(): bool
    {
        return in_array($this->mime, ['image/jpeg', 'image/jpg', 'image/png']) ||
            (bool) preg_match('/\.(jpg|jpeg|png)$/i', $this->original_name);
    }

    // ----------------------------------------------------------------
    // Scopes
    // ----------------------------------------------------------------

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
              ->orWhere('reference_no', 'like', "%{$term}%")
              ->orWhere('original_name', 'like', "%{$term}%")
              ->orWhere('notes', 'like', "%{$term}%");
        });
    }

    public function scopeCategory(Builder $query, ?string $category): Builder
    {
        if (! $category || $category === 'all') {
            return $query;
        }

        return $query->where('category', $category);
    }

    public function scopeExpiryStatus(Builder $query, ?string $status): Builder
    {
        $today = Carbon::today()->toDateString();
        $in30Days = Carbon::today()->addDays(30)->toDateString();

        return match ($status) {
            'expired' => $query->whereNotNull('expiry_date')->whereDate('expiry_date', '<', $today),
            'expiring_soon' => $query->whereNotNull('expiry_date')
                ->whereDate('expiry_date', '>=', $today)
                ->whereDate('expiry_date', '<=', $in30Days),
            'valid' => $query->where(function (Builder $q) use ($in30Days) {
                $q->whereNull('expiry_date')
                  ->orWhereDate('expiry_date', '>', $in30Days);
            }),
            default => $query,
        };
    }

    public function scopeExpiringSoon(Builder $query, int $days = 30): Builder
    {
        $today = Carbon::today()->toDateString();
        $target = Carbon::today()->addDays($days)->toDateString();

        return $query->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '>=', $today)
            ->whereDate('expiry_date', '<=', $target);
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<', Carbon::today()->toDateString());
    }

    public function scopeValid(Builder $query): Builder
    {
        $in30Days = Carbon::today()->addDays(30)->toDateString();

        return $query->where(function (Builder $q) use ($in30Days) {
            $q->whereNull('expiry_date')
              ->orWhereDate('expiry_date', '>', $in30Days);
        });
    }
}
