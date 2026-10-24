<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Database\Seeder;

class QuotationSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'admin@gmail.com')->first() ?? User::first();

        if (! $user) {
            $this->command->warn('No hay usuarios. Corre primero el UsuariosSeeder.');

            return;
        }

        $cotizaciones = [
            [
                'number' => 'COT-0001',
                'customer_name' => 'Clínica San Lucas S.R.L.',
                'customer_document' => '1023456029',
                'customer_phone' => '3-3221100',
                'notes' => 'Proforma de material de curación para el área de emergencias.',
                'items' => [
                    ['sku' => 'CUR-0001', 'quantity' => 50],
                    ['sku' => 'CUR-0002', 'quantity' => 12],
                    ['sku' => 'CUR-0003', 'quantity' => 5],
                ],
            ],
            [
                'number' => 'COT-0002',
                'customer_name' => 'Consultorio Dra. María Fernández',
                'customer_document' => '7788990',
                'customer_phone' => '76543210',
                'notes' => 'Proforma de medicamentos para el consultorio.',
                'items' => [
                    ['sku' => 'MED-0002', 'quantity' => 6],
                    ['sku' => 'VIT-0001', 'quantity' => 10],
                ],
            ],
        ];

        foreach ($cotizaciones as $data) {
            $customer = \App\Models\Customer::where('name', $data['customer_name'])->first();

            $quotation = Quotation::updateOrCreate(
                ['number' => $data['number']],
                [
                    'user_id' => $user->id,
                    'customer_id' => $customer?->id,
                    'date' => now()->toDateString(),
                    'expires_at' => now()->addDays(15)->toDateString(),
                    'status' => 'pending',
                    'amount' => 0,
                    'notes' => $data['notes'],
                ]
            );

            $quotation->items()->delete();
            $total = 0;

            foreach ($data['items'] as $itemData) {
                $product = Product::where('sku', $itemData['sku'])->with('units')->first();

                if (! $product) {
                    continue;
                }

                $unit = $product->units->firstWhere('is_base', true);

                if (! $unit) {
                    continue;
                }

                $subtotal = $unit->price * $itemData['quantity'];
                $total += $subtotal;

                $quotation->items()->create([
                    'product_id' => $product->id,
                    'product_unit_id' => $unit->id,
                    'conversion_factor' => $unit->factor,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $unit->price,
                    'subtotal' => $subtotal,
                ]);
            }

            $quotation->update(['amount' => $total]);
        }

        $this->command->info('✅ Cotizaciones de ejemplo creadas correctamente.');
    }
}
