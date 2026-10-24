<?php

namespace Database\Seeders;

use App\Models\Type;
use Illuminate\Database\Seeder;

class TypeSeeder extends Seeder
{
    /**
     * Catálogo compartido de tipos: lo usan tanto las transacciones de caja
     * (transactions.type_id) como los movimientos de almacén (stock_movements.type_id).
     */
    public function run(): void
    {
        $types = [
            ['name' => 'Venta', 'code' => 'VTA', 'sign' => '+'],
            ['name' => 'Compra', 'code' => 'COM', 'sign' => '-'],
            ['name' => 'Gastos', 'code' => 'GTO', 'sign' => '-'],
            ['name' => 'Entrada de Stock', 'code' => 'ENT', 'sign' => '+'],
            ['name' => 'Salida de Stock', 'code' => 'SAL', 'sign' => '-'],
        ];

        foreach ($types as $type) {
            Type::updateOrCreate(['code' => $type['code']], $type);
        }

        $this->command->info('✅ Tipos de transacción y movimiento creados correctamente.');
    }
}
