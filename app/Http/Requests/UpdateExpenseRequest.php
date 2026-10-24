<?php

namespace App\Http\Requests;

/**
 * Editar un gasto valida exactamente lo mismo que crearlo (el monto y la caja se pueden corregir).
 */
class UpdateExpenseRequest extends StoreExpenseRequest {}
