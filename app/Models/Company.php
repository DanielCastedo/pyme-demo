<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Company extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('empresa')
            ->setDescriptionForEvent(fn (string $eventName) => match ($eventName) {
                'created' => "Se registraron los datos de la empresa {$this->name}",
                'updated' => "Se actualizó la configuración de la empresa {$this->name}",
                'deleted' => "Se eliminó el registro de la empresa {$this->name}",
                default => "Acción {$eventName} en empresa {$this->name}",
            });
    }

    protected $fillable = [
        'name',
        'legal_name',
        'tax_id',
        'phone',
        'email',
        'address',
        'city',
        'country',
        'phone_country_code',
        'currency',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function activeSubscription(): ?Subscription
    {
        return $this->subscriptions()
            ->whereIn('status', ['active', 'trial'])
            ->latest('starts_at')
            ->first();
    }

    public function hasValidSubscription(): bool
    {
        $sub = $this->activeSubscription();

        if (! $sub) {
            return false;
        }

        if ($sub->status === 'trial') {
            return $sub->trial_ends_at && $sub->trial_ends_at->isFuture();
        }

        return $sub->ends_at && $sub->ends_at->isFuture();
    }
}
