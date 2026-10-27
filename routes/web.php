<?php

// routes/web.php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CashboxController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ConsignmentController;
use App\Http\Controllers\PayableController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\PurchasePaymentController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\StockOverviewController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DemoController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\PermisosController;
use App\Http\Controllers\ProductComponentController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\PosSearchController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductExcelController;
use App\Http\Controllers\ProductUnitController;
use App\Http\Controllers\PwaController;
use App\Http\Controllers\ProductWarehouseController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\PublicPlanController;
use App\Http\Controllers\PublicReceiptController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ReceivableController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SalePaymentController;
use App\Http\Controllers\RolePermisosController;
use App\Http\Controllers\SidebarController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\StockMovementExcelController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\SubscriptionPlanController;
use App\Http\Controllers\TermsController;
use App\Http\Controllers\TypeController;
use App\Http\Controllers\UsuariosController;
use App\Http\Controllers\VerificarController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| RUTAS PÚBLICAS
|--------------------------------------------------------------------------
*/

Route::redirect('/', '/login');

// PWA: nombre y logo salen de la empresa registrada (tabla companies)
Route::get('/manifest.webmanifest', [PwaController::class, 'manifest'])->name('pwa.manifest');
Route::get('/pwa/icon/{size}.png', [PwaController::class, 'icon'])->whereNumber('size')->name('pwa.icon');

Route::get('/planes', [PublicPlanController::class, 'index'])->name('public.plans');
Route::post('/planes/solicitar', [PublicPlanController::class, 'store'])->name('public.plans.request');

// Recibos en PDF sin sesión: el QR que se imprime en el recibo apunta acá (URL firmada, ver PublicReceiptController).
Route::middleware(['signed'])->prefix('recibo')->name('public.receipts.')->group(function () {
    Route::get('/venta/{sale}', [PublicReceiptController::class, 'sale'])->name('sale');
    Route::get('/pedido/{pedido}', [PublicReceiptController::class, 'pedido'])->name('pedido');
    Route::get('/consignacion/{consignment}', [PublicReceiptController::class, 'consignment'])->name('consignment');
    Route::get('/compra/{purchase}', [PublicReceiptController::class, 'purchase'])->name('purchase');
});

// Modo demostración: botón del login que restablece la base (migrate:fresh --seed). Solo con DEMO_MODE=true.
Route::post('/demo/restablecer', [DemoController::class, 'reset'])
    ->middleware(['guest', 'throttle:3,10'])
    ->name('demo.reset');

Route::controller(AuthController::class)->group(function () {
    Route::get('/registrar', 'create')->name('auth.create');
    Route::post('/registrar', 'store')->name('auth.store');
});

// /verificar: la vista "verificador.index" no existe y la clase se llama "verificarController" (minúscula) en su archivo,
// lo que falla en servidores Linux. Se reactiva cuando se rehaga esa pantalla.

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/suscripcion/planes', [SubscriptionController::class, 'plans'])->name('subscriptions.plans');

// Endpoint de versión de la app (para el aviso de actualización pendiente)
Route::get('/api/app-version', function () {
    $manifestPath = public_path('build/manifest.json');

    $version = file_exists($manifestPath)
        ? md5_file($manifestPath)
        : (string) filemtime(base_path('composer.lock'));

    return response()->json(['version' => $version]);
})->name('app.version');

/*
|--------------------------------------------------------------------------
| RUTAS DE SUSCRIPCIÓN (fuera del middleware 'subscription' para evitar loop)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {
    Route::get('/suscripcion/vencida', [SubscriptionController::class, 'expired'])->name('subscriptions.expired');
    Route::get('/suscripcion', [SubscriptionController::class, 'index'])->name('subscriptions.index');
    Route::post('/suscripcion/suscribir', [SubscriptionController::class, 'subscribe'])->name('subscriptions.subscribe');
    Route::post('/suscripcion/cancelar', [SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');
    Route::get('/suscripcion/facturas', [SubscriptionController::class, 'invoices'])->name('subscriptions.invoices');
    Route::get('/facturas/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');

    Route::middleware(['role:admin|superadmin'])->group(function () {
        Route::get('/suscripcion/pendientes', [SubscriptionController::class, 'pending'])->name('subscriptions.pending');
        Route::post('/suscripcion/{subscription}/aprobar', [SubscriptionController::class, 'approve'])->name('subscriptions.approve');
        Route::post('/suscripcion/{subscription}/rechazar', [SubscriptionController::class, 'reject'])->name('subscriptions.reject');

        // Nuevas rutas para leads públicos
        Route::post('/suscripcion-leads/{subscriptionRequest}/convertir', [SubscriptionController::class, 'convertLead'])->name('subscription-leads.convert');
        Route::put('/suscripcion-leads/{subscriptionRequest}', [SubscriptionController::class, 'updateLeadStatus'])->name('subscription-leads.update');
        Route::delete('/suscripcion-leads/{subscriptionRequest}', [SubscriptionController::class, 'destroyLead'])->name('subscription-leads.destroy');
    });
});

/*
|--------------------------------------------------------------------------
| RUTAS PROTEGIDAS (requieren auth + suscripción vigente)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'verified', 'subscription', 'terms'])->group(function () {

    // ── Términos y Condiciones ───────────────────────────────────────────────
    Route::get('terminos/pendiente', [TermsController::class, 'pending'])->name('terms.pending');
    Route::post('terminos/aceptar', [TermsController::class, 'accept'])->name('terms.accept');
    Route::post('terminos/rechazar', [TermsController::class, 'reject'])->name('terms.reject');
    Route::get('terminos-y-condiciones', [TermsController::class, 'index'])->name('terms.index');

    // ── Manual de Funcionamiento (Rol Administrador) ─────────────────────────
    Route::get('manual/administrador-pdf', function () {
        $pdfPath = public_path('manual_administrador_sistema.pdf');
        if (! file_exists($pdfPath)) {
            $company = \App\Models\Company::first();
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('manual.admin-manual', compact('company'))->setPaper('a4', 'portrait');
            $pdf->save($pdfPath);
        }
        return response()->download($pdfPath, 'Manual_Administrador_Sistema.pdf');
    })->name('manual.admin.pdf');

    // ── Seguridad (superadmin + admin: usuarios, roles y permisos) ────────────
    Route::middleware(['role:superadmin|admin'])->group(function () {
        Route::resource('usuarios', UsuariosController::class)->except(['create', 'show']);
        Route::resource('roles', RoleController::class)->except(['create']);
        Route::resource('permisos', PermisosController::class)->except(['create', 'show'])->parameters(['permisos' => 'permiso']);
        Route::resource('role-permisos', RolePermisosController::class)->only(['index', 'edit', 'update']);
    });

    // ── Seguridad (solo superadmin: sidebars) ──────────────────────────────────
    Route::middleware(['role:superadmin'])->group(function () {
        Route::resource('sidebars', SidebarController::class)->except(['create', 'show']);
    });

    // ── Seguridad / Auditoría: Bitácora (solo superadmin) ─────────────────────
    Route::middleware(['role:superadmin'])->group(function () {
        Route::get('bitacora', \App\Http\Controllers\BitacoraController::class . '@index')->name('bitacora.index');
        Route::get('bitacora/pdf', \App\Http\Controllers\BitacoraController::class . '@downloadPdf')->name('bitacora.pdf');
    });

    // ── Configuración: Empresa (admin + superadmin, vía permiso) ─────────
    Route::middleware(['permission:ver companies'])->group(function () {
        Route::resource('companies', CompanyController::class)->except(['create']);
    });

    // ── Inventario: Sucursales (admin + superadmin, vía permiso) ─────────
    Route::middleware(['permission:ver branches'])->group(function () {
        Route::resource('branches', BranchController::class)->except(['create']);
    });

    // ── Ventas: Pedidos (órdenes en espera con fecha de entrega; se confirman y pasan a venta) ─────────
    Route::middleware(['permission:ver orders'])->group(function () {
        Route::get('pedidos/{pedido}/pdf', [PedidoController::class, 'downloadPdf'])->middleware('permission:ventas.exportar')->name('pedidos.pdf');
        Route::resource('pedidos', PedidoController::class);
        Route::post('pedidos/{pedido}/confirm', [PedidoController::class, 'confirm'])->name('pedidos.confirm');
    });

    // ── POS: buscador de productos compartido por Ventas, Pedidos y Consignaciones ─────────
    // Sin permiso propio: cualquiera de esas 3 pantallas ya está protegida con el suyo, y este
    // buscador no muestra nada que esas pantallas no muestren ya.
    Route::get('pos/products/search', [PosSearchController::class, 'search'])->name('pos.products.search');
    Route::get('pos/customers/search', [PosSearchController::class, 'searchCustomers'])->name('pos.customers.search');

    // ── Ventas: registro rápido de ventas (orden + stock FIFO + cobro en caja) ─────────
    Route::middleware(['permission:ver sales'])->group(function () {
        Route::get('sales/{sale}/pdf', [SaleController::class, 'downloadPdf'])->middleware('permission:ventas.exportar')->name('sales.pdf');
        Route::resource('sales', SaleController::class)->only(['index', 'create', 'store', 'show']);
        Route::post('sales/{sale}/cancel', [SaleController::class, 'cancel'])->name('sales.cancel');
        Route::post('sales/{sale}/payments', [SalePaymentController::class, 'store'])->name('sales.payments.store');
        Route::delete('sales/{sale}/payments/{payment}', [SalePaymentController::class, 'destroy'])->name('sales.payments.destroy');
        Route::get('receivables', [ReceivableController::class, 'index'])->name('receivables.index');
    });

    // ── Ventas: Consignaciones (mercadería en manos del cliente; se liquida en venta + devolución) ─────────
    Route::middleware(['permission:ver consignments'])->group(function () {
        Route::get('consignments/{consignment}/pdf', [ConsignmentController::class, 'downloadPdf'])->middleware('permission:consignaciones.exportar')->name('consignments.pdf');
        Route::resource('consignments', ConsignmentController::class)->only(['index', 'create', 'store', 'show']);
        Route::post('consignments/{consignment}/settle', [ConsignmentController::class, 'settle'])->name('consignments.settle');
        Route::post('consignments/{consignment}/cancel', [ConsignmentController::class, 'cancel'])->name('consignments.cancel');
    });

    // ── Compras: entrada de mercadería al inventario (lotes FIFO), al contado o a crédito ─────────
    Route::middleware(['permission:ver purchases'])->group(function () {
        Route::get('purchases/{purchase}/pdf', [PurchaseController::class, 'downloadPdf'])->middleware('permission:compras.exportar')->name('purchases.pdf');
        Route::get('purchases/products/search', [PurchaseController::class, 'search'])->name('purchases.products.search');
        Route::resource('purchases', PurchaseController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update']);
        Route::post('purchases/{purchase}/cancel', [PurchaseController::class, 'cancel'])->name('purchases.cancel');
        Route::post('purchases/{purchase}/payments', [PurchasePaymentController::class, 'store'])->name('purchases.payments.store');
        Route::delete('purchases/{purchase}/payments/{payment}', [PurchasePaymentController::class, 'destroy'])->name('purchases.payments.destroy');
        Route::get('cuentas-por-pagar', [PayableController::class, 'index'])->name('payables.index');
    });

    // ── Finanzas: reportes de ventas, compras, gastos y ganancias ─────────
    Route::middleware(['permission:reportes.ver'])->prefix('finanzas')->name('finance.')->group(function () {
        Route::get('reportes', [FinanceController::class, 'report'])->name('report');
        Route::get('reportes/pdf', [FinanceController::class, 'reportPdf'])->middleware('permission:reportes.exportar')->name('report.pdf');
        Route::get('reportes/excel', [FinanceController::class, 'reportExcel'])->middleware('permission:reportes.exportar')->name('report.excel');
        Route::get('ganancias-por-producto', [FinanceController::class, 'products'])->name('products');
        Route::get('ganancias-por-producto/pdf', [FinanceController::class, 'productsPdf'])->middleware('permission:reportes.exportar')->name('products.pdf');
        Route::get('ganancias-por-producto/excel', [FinanceController::class, 'productsExcel'])->middleware('permission:reportes.exportar')->name('products.excel');
    });

    // ── Gastos: gastos del negocio (salen de una caja) y sus categorías ─────────
    Route::middleware(['permission:ver expenses'])->group(function () {
        Route::get('expenses/{expense}/pdf', [ExpenseController::class, 'downloadPdf'])->middleware('permission:gastos.exportar')->name('expenses.pdf');
        Route::resource('expenses', ExpenseController::class);
    });
    Route::middleware(['permission:ver expense-categories'])->group(function () {
        Route::resource('expense-categories', ExpenseCategoryController::class)->except(['create', 'show']);
    });

    // ── Compras: Proveedores ─────────
    Route::middleware(['permission:ver suppliers'])->group(function () {
        Route::resource('suppliers', SupplierController::class)->except(['create']);
    });

    // ── Ventas: Clientes (admin + superadmin, vía permiso) ─────────
    Route::middleware(['permission:ver customers'])->group(function () {
        Route::resource('customers', CustomerController::class)->except(['create']);
    });

    // ── Configuración: Tipos de transacción/movimiento (admin + superadmin, vía permiso) ─────────
    Route::middleware(['permission:ver types'])->group(function () {
        Route::resource('types', TypeController::class)->except(['create']);
    });

    // ── Ventas: Métodos de Pago (admin + superadmin, vía permiso) ─────────
    Route::middleware(['permission:ver payments'])->group(function () {
        Route::resource('payments', PaymentMethodController::class)
            ->except(['create'])
            ->parameters(['payments' => 'paymentMethod']);
    });

    // ── Configuración: Categorías de producto (admin + superadmin, vía permiso) ─────────
    Route::middleware(['permission:ver categories'])->group(function () {
        Route::resource('categories', CategoryController::class)->except(['create']);
    });

    // ── Inventario: Productos (admin + superadmin, vía permiso) ─────────
    Route::middleware(['permission:ver products'])->group(function () {
        Route::get('products/export', [ProductExcelController::class, 'export'])->middleware('permission:productos.exportar')->name('products.export');
        Route::get('products/template', [ProductExcelController::class, 'template'])->middleware('permission:productos.importar')->name('products.template');
        Route::post('products/import', [ProductExcelController::class, 'import'])->middleware('permission:productos.importar')->name('products.import');
        Route::resource('products', ProductController::class)->except(['create']);
    });

    // ── Inventario: stock por sucursal y almacén (vista de consulta) ─────────
    Route::middleware(['permission:ver product-warehouses'])->get('inventario/stock-por-sucursal', [StockOverviewController::class, 'index'])->name('stock-overview.index');

    // ── Inventario: catálogo de unidades de medida (Unidad, Caja, Docena...) ─────────
    Route::middleware(['permission:ver units'])->group(function () {
        Route::resource('units', UnitController::class)->except(['create', 'show']);
    });

    // ── Inventario: Unidades de Producto (admin + superadmin, vía permiso) ─────────
    Route::middleware(['permission:ver product-units'])->group(function () {
        Route::resource('product-units', ProductUnitController::class)->except(['create']);
    });

    // ── Finanzas: Cajas (admin + superadmin, vía permiso) ─────────
    Route::middleware(['permission:ver cashboxes'])->group(function () {
        Route::get('cashboxes/{cashbox}/totales/excel', [CashboxController::class, 'exportTotals'])->name('cashboxes.totals.export');
        Route::resource('cashboxes', CashboxController::class)->except(['create']);
    });

    // ── Inventario: Componentes de Producto / Kits (admin + superadmin, vía permiso) ─────────
    Route::middleware(['permission:ver product-components'])->group(function () {
        Route::resource('product-components', ProductComponentController::class)->except(['create']);
    });

    // ── Inventario: Almacenes (admin + superadmin, vía permiso) ─────────
    Route::middleware(['permission:ver warehouses'])->group(function () {
        Route::resource('warehouses', WarehouseController::class)->except(['create']);
    });

    // ── Inventario: Movimientos de Stock — Entradas/Salidas (admin + superadmin, vía permiso) ─────────
    Route::middleware(['permission:ver stock-movements'])->group(function () {
        Route::get('stock-movements/template', [StockMovementExcelController::class, 'template'])->middleware('permission:kardex.importar')->name('stock-movements.template');
        Route::post('stock-movements/import', [StockMovementExcelController::class, 'import'])->middleware('permission:kardex.importar')->name('stock-movements.import');
        Route::get('stock-movements/products/search', [StockMovementController::class, 'search'])->name('stock-movements.products.search');
        Route::resource('stock-movements', StockMovementController::class);
    });

    // ── Inventario: Stock por Almacén (clave compuesta, sin Route::resource) ─────────
    Route::middleware(['permission:ver product-warehouses'])->group(function () {
        Route::get('/product-warehouses', [ProductWarehouseController::class, 'index'])->name('product-warehouses.index');
        Route::post('/product-warehouses', [ProductWarehouseController::class, 'store'])->name('product-warehouses.store');
        Route::get('/product-warehouses/{combo}', [ProductWarehouseController::class, 'show'])->name('product-warehouses.show');
        Route::get('/product-warehouses/{combo}/edit', [ProductWarehouseController::class, 'edit'])->name('product-warehouses.edit');
        Route::put('/product-warehouses/{combo}', [ProductWarehouseController::class, 'update'])->name('product-warehouses.update');
        Route::delete('/product-warehouses/{combo}', [ProductWarehouseController::class, 'destroy'])->name('product-warehouses.destroy');
    });

    // ── Inventario: Promociones (admin + superadmin, vía permiso) ─────────
    Route::middleware(['permission:ver promotions'])->group(function () {
        Route::resource('promotions', PromotionController::class)->except(['create']);
    });


    // ── Ventas: Proformas / Cotizaciones (admin + superadmin, vía permiso) ─
    Route::middleware(['permission:ver quotations'])->group(function () {
        Route::get('/quotations/{quotation}/pdf', [QuotationController::class, 'downloadPdf'])->middleware('permission:proformas.exportar')->name('quotations.pdf');
        Route::patch('/quotations/{quotation}/status', [QuotationController::class, 'changeStatus'])->name('quotations.status');
        Route::resource('quotations', QuotationController::class);
    });

    // ── Configuración: Administrar Planes (solo superadmin) ──────────────
    Route::middleware(['role:superadmin'])->group(function () {
        Route::resource('subscription-plans', SubscriptionPlanController::class)->except(['create']);
    });

});

/*
|--------------------------------------------------------------------------
| FALLBACK 404 (siempre al final del archivo)
|--------------------------------------------------------------------------
*/

Route::fallback(fn () => view('pages.utility.404'));
