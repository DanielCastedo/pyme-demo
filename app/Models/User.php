<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use HasRoles;
    use SoftDeletes;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logExcept(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('usuarios')
            ->setDescriptionForEvent(fn (string $eventName) => match ($eventName) {
                'created' => "Se creó el usuario {$this->name} ({$this->email})",
                'updated' => "Se actualizó el usuario {$this->name}",
                'deleted' => "Se eliminó el usuario {$this->name}",
                default => "Acción {$eventName} en usuario {$this->name}",
            });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'branche_id',
        'is_designated_admin',
        'terms_accepted_at',
        'terms_ip',
        'terms_version',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_designated_admin' => 'boolean',
        'terms_accepted_at' => 'datetime',
    ];

    public function hasAcceptedTerms(): bool
    {
        return !is_null($this->terms_accepted_at);
    }

    public function mustAcceptTerms(): bool
    {
        return (bool) $this->is_designated_admin && is_null($this->terms_accepted_at);
    }

    public function getTermsStatusLabelAttribute(): string
    {
        if (!$this->is_designated_admin) {
            return 'No designado';
        }
        if ($this->terms_accepted_at) {
            return 'Confirmado (' . $this->terms_accepted_at->format('d/m/Y') . ')';
        }
        return 'Pendiente';
    }

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branche_id');
    }

    /**
     * Alias de la relación de permisos de Spatie mapeado como autorizaciones.
     * Mantiene compatibilidad con tu arquitectura.
     */
    public function authorizations(): MorphToMany
    {
        return $this->permissions();
    }

    /**
     * Verifica si el usuario tiene al menos una autorización (permiso).
     */
    public function hasAnyAuthorization(array|string $authorizations): bool
    {
        if (is_string($authorizations)) {
            $authorizations = [$authorizations];
        }
        $authorizations = array_map('trim', $authorizations);

        try {
            return $this->hasAnyPermission($authorizations);
        } catch (PermissionDoesNotExist $e) {
            return false; // Si algún permiso no existe en la BD, devuelve false
        }
    }

    /**
     * Verifica si el usuario tiene una autorización (permiso) en específico.
     */
    public function hasAuthorization(string $authorization): bool
    {
        try {
            return $this->hasPermissionTo(trim($authorization));
        } catch (PermissionDoesNotExist $e) {
            return false; // Si el permiso no existe en la BD, devuelve false
        }
    }
}
