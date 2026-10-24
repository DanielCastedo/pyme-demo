<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Primero lo básico del sistema (usuarios, roles, menús, catálogos) y después una historia de ejemplo
     * chica y coherente (ver cada seeder): productos, un kit, compras, ventas, un pedido y una consignación.
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
            // ExpenseCategorySeeder::class,
            // CategorySeeder::class,

            // Dependen de sucursales
            WarehouseSeeder::class,
            CashboxSeeder::class,

            // // ── Datos de ejemplo (el orden importa: cada paso usa lo del anterior) ──
            // SupplierSeeder::class,
            // CustomerSeeder::class,

            // // Productos: simples, con presentación (Caja x100) y un kit (Mesa = patas + tablero + tornillos)
            // ProductSeeder::class,
            // ProductUnitSeeder::class,
            // ProductComponentSeeder::class,

            // // Stock y dinero: entradas a mano, una compra pagada desde la caja y dos gastos
            // StockMovementSeeder::class,
            // PurchaseSeeder::class,
            // ProductWarehouseSeeder::class, // stock mínimo (va después de tener stock)
            // ExpenseSeeder::class,

            // // Ventas: kit al contado, contado con descuento y crédito con anticipo; un pedido y una consignación
            // OrderSeeder::class,
            // PedidoSeeder::class,
            // ConsignmentSeeder::class,

            // // Otros ejemplos disponibles (sin incluir por defecto):
            // PromotionSeeder::class,
            // PromotionProductSeeder::class,
            // QuotationSeeder::class,
            // TransactionSeeder::class,
            // BitacoraDemoSeeder::class,
            // // LoadTestSeeder::class,
        ]);
    }
}
