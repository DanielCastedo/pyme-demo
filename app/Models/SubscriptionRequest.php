<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionRequest extends Model
{
    use HasFactory;

    /**
     * "Lead" público: alguien que pidió información desde la landing (public.plans)
     * antes de tener una Company. Se convierte en Subscription real vía convertLead().
     */
    protected $fillable = [
        'subscription_plan_id',
        'name',
        'business_name',
        'phone',
        'email',
        'message',
        'status',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    protected function getCreatedAtLabelAttribute(): string
    {
        return $this->created_at->format('d/m/Y H:i');
    }
}
