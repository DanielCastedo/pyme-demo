<?php

namespace App\Services;

use App\Models\Cashbox;
use App\Models\CashSession;
use App\Models\User;
use App\Support\Bc;
use Illuminate\Support\Facades\DB;

/**
 * Apertura y cierre de caja por usuario.
 *
 * Abrir: el usuario recibe un fondo inicial para dar cambio (ej. Bs 100). Ese dinero ya está en la caja: no es una
 * venta ni un ingreso, así que no genera transacción ni cambia el saldo; solo se anota para el cuadre. Por eso sigue
 * formando parte de la caja y no aparece en las ventas.
 *
 * Cerrar: efectivo esperado = fondo + efectivo que entró − efectivo que salió en el turno; el usuario cuenta lo que
 * tiene y queda registrada la diferencia (sobrante o faltante). La diferencia no mueve dinero: queda para revisión.
 */
class CashSessionService
{
    /**
     * @throws \RuntimeException si el usuario ya tiene una caja abierta o el fondo no está disponible en la caja.
     */
    public function open(User $user, Cashbox $cashbox, int|float|string $openingAmount, ?string $note = null): CashSession
    {
        return DB::transaction(function () use ($user, $cashbox, $openingAmount, $note) {
            $cashbox = Cashbox::lockForUpdate()->findOrFail($cashbox->id);
            $amount = Bc::round($openingAmount, 2);

            if ($current = CashSession::openFor($user->id)) {
                throw new \RuntimeException("Ya tienes abierta la caja \"{$current->cashbox->name}\" desde el {$current->opened_at->format('d/m/Y H:i')}: ciérrala antes de abrir otra.");
            }

            if (Bc::lt($amount, 0)) {
                throw new \RuntimeException('El fondo inicial no puede ser negativo.');
            }

            // El fondo sale del efectivo que ya tiene la caja (descontando el de otros turnos abiertos en ella).
            $committed = Bc::sum(CashSession::open()->where('cashbox_id', $cashbox->id)->pluck('opening_amount'));
            $available = Bc::max(0, Bc::round(Bc::sub($cashbox->cash, $committed), 2));

            if (Bc::gt($amount, $available)) {
                throw new \RuntimeException(sprintf(
                    'La caja "%s" tiene %s en efectivo disponible para fondos; no alcanza para un fondo de %s.',
                    $cashbox->name,
                    number_format((float) $available, 2),
                    number_format((float) $amount, 2)
                ));
            }

            return CashSession::create([
                'cashbox_id' => $cashbox->id,
                'user_id' => $user->id,
                'opened_at' => now(),
                'opening_amount' => $amount,
                'opening_note' => filled($note) ? trim($note) : null,
            ]);
        });
    }

    /**
     * @throws \RuntimeException si el turno ya está cerrado o el efectivo contado no es válido.
     */
    public function close(CashSession $session, int|float|string $countedCash, User $closedBy, ?string $note = null): CashSession
    {
        return DB::transaction(function () use ($session, $countedCash, $closedBy, $note) {
            $session = CashSession::lockForUpdate()->findOrFail($session->id);
            $counted = Bc::round($countedCash, 2);

            if (! $session->isOpen()) {
                throw new \RuntimeException('Esta caja ya está cerrada.');
            }

            if (Bc::lt($counted, 0)) {
                throw new \RuntimeException('El efectivo contado no puede ser negativo.');
            }

            $expected = $session->summary()['expected_cash'];

            $session->update([
                'closed_at' => now(),
                'expected_cash' => $expected,
                'counted_cash' => $counted,
                'difference' => Bc::round(Bc::sub($counted, $expected), 2),
                'closing_note' => filled($note) ? trim($note) : null,
                'closed_by' => $closedBy->id,
            ]);

            return $session;
        });
    }
}
