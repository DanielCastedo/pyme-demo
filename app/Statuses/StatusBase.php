<?php

namespace App\Statuses;

use App\Models\File;
use App\Models\Status;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;

abstract class StatusBase
{
    // Dejamos las variables vacias por defectos para forzar la definicion en las clases hijas
    // Definir los permisos del estado
    protected static string $authorization = '';

    // Definir origenes validos para el estado
    protected static array $validSourceStatuses = [];

    // Definir destinos validos para el estado
    protected static array $validFollowingStatuses = [];

    /**
     * @var array<string, Status> Caché interna para evitar múltiples consultas a la base de datos.
     */
    protected static array $statusIdCache = [];

    /**
     * Obtener el modelo de base de datos asociado a la clase de estado actual.
     *
     * @throws ModelNotFoundException
     */
    public static function toModel(): Status
    {
        if (! isset(self::$statusIdCache[static::class])) {
            self::$statusIdCache[static::class] = Status::where('function', static::class)->firstOrFail();
        }

        return self::$statusIdCache[static::class];
    }

    /**
     * Validar que tenga los permisos necesarios sobre el estado
     * abort aborta la trasaccion si no tiene autorizacion
     */
    public static function authorize(): HttpResponseException|bool
    {
        if (! Auth::user()->hasAuthorization(static::$authorization)) {
            abort(403, 'No tienes los permisos necesarios para realizar esta acción.');
        }

        return true;
    }

    /**
     * Validar que el usuario tengo autorizacion para el estado
     */
    public static function allowed(): bool
    {
        return Auth::user()->hasAuthorization(static::$authorization);
    }

    /**
     * Validar que el estado anterior pueda ir al siguiente estado
     * Aborta si el estado anterior no puede transicionar al siguiente estado
     */
    protected static function validateSourceStatus(File $file): void
    {
        $currentStatus = Status::findOrFail($file->current_status_id);

        // Obtenemos el nombre de la clase del estado actual
        $currentStatusClass = $currentStatus->function;
        if (! in_array($currentStatusClass, static::$validSourceStatuses)) {
            abort(400, 'El estado anterior no es valido para este cambio de estado.');
        }
    }

    /**
     * Obtener los estados origenes validos.
     */
    public static function getValidSourceStatuses(): array
    {
        return static::$validSourceStatuses ?? [];
    }

    /**
     * Obtener la autorización definida para el estado.
     */
    public static function getAuthorization(): string
    {
        return static::$authorization;
    }

    /**
     * Obtener los estados destinos validos.
     */
    public static function getValidFollowingStatuses(): array
    {
        return static::$validFollowingStatuses ?? [];
    }

    /**
     * Scope para obtener archivos listos para empaquetar.
     *
     * Filtra los archivos según los estados de origen autorizados del usuario
     * y que no estén en un paquete pendiente de aceptación.
     *
     * @param  Builder  $query  El query builder
     * @return Builder Query builder con los filtros aplicados
     */
    public static function scopeOrdersToPack(Builder $query): Builder
    {
        $user = Auth::user();

        // Obtener los estados origen autorizados para el usuario actual
        $sourceStatuses = Status::getSourceStatusesByStatus(self::toModel());
        $authorizedStatuses = Status::getAuthorizedForUser($user);

        // Filtrar solo los estados origen que el usuario tiene permiso de usar
        $authorizedSourceStatusIds = $sourceStatuses
            ->whereIn('function', $authorizedStatuses->pluck('function')->all())
            ->pluck('id')
            ->all();

        // Retornar consulta vacía si no hay estados autorizados
        if (empty($authorizedSourceStatusIds)) {
            return $query->whereRaw('1 = 0');
        }

        // Filtrar archivos con estado origen autorizado
        $query->whereIn('current_status_id', $authorizedSourceStatusIds);

        // Obtener los archivos que no están en un paquete o están en un paquete aceptado
        return $query->whereDoesntHave('packageDetails', function (Builder $sub) {
            $sub->whereHas('package', function (Builder $sub) {
                $sub->whereNull('accepted_at');
            });
        })->orderBy('id', 'asc');
    }
}
