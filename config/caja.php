<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Apertura y cierre de caja
    |--------------------------------------------------------------------------
    |
    | Con true, para cobrar una venta el usuario tiene que tener su caja abierta (Ventas → Mi caja), así cada cobro
    | queda en su turno y se puede cuadrar al cerrar. Las pruebas automáticas lo apagan (phpunit.xml) salvo las que
    | prueban justamente esta regla.
    |
    */

    'require_open_session' => (bool) env('CASH_SESSION_REQUIRED', true),

    /*
    | Fondo inicial que se propone al abrir la caja (para dar cambio). El usuario lo puede cambiar.
    */

    'default_opening_amount' => 100,

];
