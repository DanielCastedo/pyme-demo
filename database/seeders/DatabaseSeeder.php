<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Farmacia con UNA sola sucursal (un almacén y una caja). Primero lo básico del sistema (usuarios, roles,
     * menús, catálogos) y después una historia de ejemplo chica y coherente (ver cada seeder): medicamentos,
     * un botiquín (kit), compras, ventas, un pedido y una consignación.
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

            // Dependen de la sucursal
            WarehouseSeeder::class,
            CashboxSeeder::class,

            // ── Datos de ejemplo (el orden importa: cada paso usa lo del anterior) ──
            SupplierSeeder::class,
            CustomerSeeder::class,

            // Productos: simples, con presentación (Paracetamol Caja x100) y un kit (Botiquín = gasas + alcohol + paracetamol)
            ProductSeeder::class,
            ProductUnitSeeder::class,
            ProductComponentSeeder::class,

            // Stock y dinero: entradas a mano, una compra al contado, una a crédito y dos gastos
            StockMovementSeeder::class,
            PurchaseSeeder::class,
            ProductWarehouseSeeder::class, // stock mínimo (va después de tener stock)
            ExpenseSeeder::class,

            // Ventas: kit al contado, contado con descuento y crédito con anticipo; un pedido y una consignación
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
