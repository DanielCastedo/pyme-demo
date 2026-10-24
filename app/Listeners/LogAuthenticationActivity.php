<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Events\Dispatcher;

class LogAuthenticationActivity
{
    public function handleLogin(Login $event): void
    {
        activity('auth')
            ->causedBy($event->user)
            ->withProperties([
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log("Inicio de sesión exitoso del usuario {$event->user->name}");
    }

    public function handleLogout(Logout $event): void
    {
        if ($event->user) {
            activity('auth')
                ->causedBy($event->user)
                ->withProperties([
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log("Cierre de sesión del usuario {$event->user->name}");
        }
    }

    public function handleFailed(Failed $event): void
    {
        activity('auth')
            ->causedBy($event->user)
            ->withProperties([
                'email' => $event->credentials['email'] ?? 'desconocido',
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log("Intento fallido de inicio de sesión con email " . ($event->credentials['email'] ?? 'desconocido'));
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            Login::class => 'handleLogin',
            Logout::class => 'handleLogout',
            Failed::class => 'handleFailed',
        ];
    }
}
