<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Ferretería con tres sucursales: Central y Norte comparten el Almacén Central y Plan 3000 tiene su propio
     * depósito (una caja por sucursal). Primero lo básico del sistema (usuarios, roles, menús, catálogos) y después
     * una historia de ejemplo chica y coherente (ver cada seeder): herramientas, un juego de herramientas (kit),
     * compras, ventas en cada sucursal, un pedido y una consignación.
     * Todo se registra con los mismos servicios que usa el sistema, así el stock y el dinero cuadran.
     *
     * Para tener solo lo básico, comenta el bloque "Datos de ejemplo".
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            // ── Base del sistema ────────────────────────────────────────────
            // BranchSeeder va antes que UsuariosSeeder: la tabla "users" exige branche_id.
            BranchSeeder::class,
            CompanySeeder::class,
            PermissionSeeder::class,
            RolesSeeder::class,
            UsuariosSeeder::class,

            SubscriptionPlanSeeder::class,
            SubscriptionSeeder::class,
            PaymentSeeder::class,
            InvoiceSeeder::class,
            SidebarSeeder::class,

            // Catálogos base
            TypeSeeder::class,
            PaymentMethodSeeder::class,
            UnitSeeder::class,
            ExpenseCategorySeeder::class,
            CategorySeeder::class,

            // Dependen de las sucursales (Almacén Central compartido por Central y Norte)
            WarehouseSeeder::class,
            CashboxSeeder::class,

            // ── Datos de ejemplo (el orden importa: cada paso usa lo del anterior) ──
            SupplierSeeder::class,
            CustomerSeeder::class,

            // Productos: simples, con presentación (Tornillo Caja x100) y un kit (Juego de herramientas = destornilladores + alicate + tornillos)
            ProductSeeder::class,
            ProductUnitSeeder::class,
            ProductComponentSeeder::class,

            // Stock y dinero: entradas a mano, una compra al contado, una a crédito y dos gastos
            StockMovementSeeder::class,
            PurchaseSeeder::class,
            ProductWarehouseSeeder::class, // stock mínimo (va después de tener stock)
            ExpenseSeeder::class,

            // Ventas: kit, descuento, crédito y una venta en Norte (almacén compartido) y otra en Plan 3000; un pedido y una consignación
            OrderSeeder::class,
            PedidoSeeder::class,
            ConsignmentSeeder::class,

            // Promociones y proformas de ejemplo
            PromotionSeeder::class,
            PromotionProductSeeder::class,
            QuotationSeeder::class,

            // Otros ejemplos disponibles (sin incluir por defecto):
            // TransactionSeeder::class,
            // BitacoraDemoSeeder::class,
            // LoadTestSeeder::class,
        ]);
    }
}
