<?php

namespace App\Imports;

use App\Support\Bc;
use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ScanSessionImport implements ToCollection, WithHeadingRow
{
    public array $errors = [];

    public int $updatedCount = 0;

    public function __construct(protected ?int $userId = null) {}

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $line = $index + 2;
            $code = trim((string) ($row['codigo'] ?? ''));
            $quantity = Bc::num($row['cantidad_contada'] ?? 0);

            if ($code === '' || Bc::lte($quantity, 0)) {
                continue;
            }

            $product = Product::where('sku', $code)->first();

            if (! $product) {
                $this->errors[] = "Fila {$line}: no se encontró ningún producto con SKU '{$code}'.";

                continue;
            }

            try {
                DB::transaction(function () use ($product, $quantity, $line) {
                    $stockBefore = $product->stock;
                    $stockAfter = Bc::add($stockBefore, $quantity);

                    InventoryMovement::create([
                        'product_id' => $product->id,
                        'user_id' => $this->userId,
                        'type' => 'entrada',
                        'reason' => 'ajuste',
                        'quantity' => $quantity,
                        'unit_cost' => $product->purchase_price,
                        'stock_before' => $stockBefore,
                        'stock_after' => $stockAfter,
                        'reference' => null,
                        'notes' => 'Ajuste por conteo con lector de código (fila '.$line.')',
                        'movement_date' => now(),
                    ]);

                    $product->update(['stock' => $stockAfter]);
                });

                $this->updatedCount++;
            } catch (\Throwable $e) {
                $this->errors[] = "Fila {$line}: error al procesar '{$code}' — {$e->getMessage()}";
            }
        }
    }
}
