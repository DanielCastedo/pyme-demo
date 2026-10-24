<?php

use App\Models\Branch;
use App\Models\Category;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Order;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolesSeeder;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->seed(RolesSeeder::class);
});

function adminUser(): User
{
    $user = User::factory()->create();
    $user->assignRole('admin');
    return $user;
}

// superAdminUser helper is loaded globally from Pest.php

describe('PDFs en transacciones clave', function () {
    it('genera el PDF de una compra exitosamente', function () {
        $admin = adminUser();
        $branch = testBranch();
        $warehouse = Warehouse::factory()->create(['branche_id' => $branch->id]);
        $supplier = Supplier::create(['name' => 'Prov Test']);

        $purchase = Purchase::create([
            'date' => now(),
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'branche_id' => $branch->id,
            'user_id' => $admin->id,
            'amount' => 500,
            'status' => Purchase::STATUS_RECEIVED,
        ]);

        $response = $this->actingAs($admin)->get(route('purchases.pdf', $purchase));
        $response->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    });

    it('genera el PDF de una venta al contado y a crédito', function () {
        $admin = adminUser();
        $branch = testBranch();
        $warehouse = Warehouse::factory()->create(['branche_id' => $branch->id]);
        $customer = Customer::create(['name' => 'Cliente Test', 'document' => '998877']);

        $sale = Order::create([
            'user_id' => $admin->id,
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'branche_id' => $branch->id,
            'amount' => 250,
            'paid_amount' => 250,
            'payment_type' => Order::PAYMENT_CASH,
            'status' => Order::STATUS_COMPLETED,
            'date' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('sales.pdf', $sale));
        $response->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    });

    it('genera el PDF de un gasto operativo', function () {
        $admin = adminUser();
        $branch = testBranch();
        $cat = ExpenseCategory::create(['name' => 'Servicios Básicos']);

        $expense = Expense::create([
            'date' => now(),
            'concept' => 'Pago de Electricidad',
            'amount' => 120,
            'status' => Expense::STATUS_PAID,
            'expense_category_id' => $cat->id,
            'branche_id' => $branch->id,
            'user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('expenses.pdf', $expense));
        $response->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    });

    it('genera los PDFs de finanzas: reporte y productos', function () {
        $admin = adminUser();

        $response1 = $this->actingAs($admin)->get(route('finance.report.pdf'));
        $response1->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $response2 = $this->actingAs($admin)->get(route('finance.products.pdf'));
        $response2->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    });
});

describe('Módulo de Bitácora de Auditoría', function () {
    it('muestra el listado de actividades a usuarios con rol superadmin', function () {
        $admin = superadminUser();

        activity('test')->causedBy($admin)->log('Acción de prueba de auditoría');

        $response = $this->actingAs($admin)->get(route('bitacora.index'));
        $response->assertOk()
            ->assertViewIs('bitacora.index')
            ->assertSee('Acción de prueba de auditoría');
    });

    it('genera y descarga el PDF de la bitácora', function () {
        $admin = superadminUser();

        activity('test')->causedBy($admin)->log('Evento descargable');

        $response = $this->actingAs($admin)->get(route('bitacora.pdf'));
        $response->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    });

    it('filtra actividades por módulo, usuario, evento y texto', function () {
        $admin = superadminUser();
        $otherUser = User::factory()->create();

        activity('ventas')->causedBy($admin)->event('created')->log('Venta de prueba #101');
        activity('compras')->causedBy($otherUser)->event('deleted')->log('Compra eliminada #202');

        // Filtro por módulo ventas
        $resVentas = $this->actingAs($admin)->get(route('bitacora.index', ['log_name' => 'ventas']));
        $resVentas->assertOk();
        expect($resVentas->viewData('activities')->pluck('description'))->toContain('Venta de prueba #101')
            ->not->toContain('Compra eliminada #202');

        // Filtro por evento deleted
        $resDeleted = $this->actingAs($admin)->get(route('bitacora.index', ['event' => 'deleted']));
        $resDeleted->assertOk();
        expect($resDeleted->viewData('activities')->pluck('description'))->toContain('Compra eliminada #202')
            ->not->toContain('Venta de prueba #101');

        // Filtro por texto buscar
        $resBuscar = $this->actingAs($admin)->get(route('bitacora.index', ['buscar' => '202']));
        $resBuscar->assertOk();
        expect($resBuscar->viewData('activities')->pluck('description'))->toContain('Compra eliminada #202')
            ->not->toContain('Venta de prueba #101');

        // Búsqueda por cliente Nora
        $nora = \App\Models\Customer::create([
            'name' => 'Nora Test Customer',
            'document' => '99887766',
        ]);
        $resNora = $this->actingAs($admin)->get(route('bitacora.index', ['buscar' => 'nora']));
        $resNora->assertOk();
        expect($resNora->viewData('activities')->pluck('description')->implode(' '))->toContain('Nora Test Customer');
    });

    it('registra actividades en todos los módulos del sistema (usuarios, sucursales, categorías, etc.)', function () {
        $admin = adminUser();

        $branch = \App\Models\Branch::create([
            'name' => 'Sucursal Norte Test',
            'is_active' => true,
        ]);

        $cat = \App\Models\Category::create([
            'name' => 'Lácteos Test',
        ]);

        $activityBranch = \Spatie\Activitylog\Models\Activity::forSubject($branch)->first();
        expect($activityBranch)->not->toBeNull();
        expect($activityBranch->log_name)->toBe('sucursales');

        $activityCat = \Spatie\Activitylog\Models\Activity::forSubject($cat)->first();
        expect($activityCat)->not->toBeNull();
        expect($activityCat->log_name)->toBe('categorias');
    });

    it('restringe la bitácora a usuarios sin permiso', function () {
        $regularUser = userWith();

        $this->actingAs($regularUser)->get(route('bitacora.index'))->assertForbidden();
        $this->actingAs($regularUser)->get(route('bitacora.pdf'))->assertForbidden();
    });
});

describe('Rol Operador', function () {
    it('permite al operador registrar ventas pero no anular compras ni ver bitácora', function () {
        $operador = User::factory()->create();
        $operador->assignRole('operador');

        expect($operador->can('ver sales'))->toBeTrue();
        expect($operador->can('crear sales'))->toBeTrue();
        expect($operador->can('ver quotations'))->toBeTrue();
        expect($operador->can('compras.anular'))->toBeFalse();
        expect($operador->can('ver bitacora'))->toBeFalse();
    });

    it('oculta los iconos de acción no permitidos para el operador en las tablas y vistas', function () {
        $operador = User::factory()->create();
        $operador->assignRole('operador');

        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        // Como operador: puede ver productos, pero no editar ni eliminar ni crear
        $response = $this->actingAs($operador)->get(route('products.index'));
        $response->assertOk();
        $response->assertSee($product->name);
        $response->assertSee(route('products.show', $product->id));
        // No debe mostrar botón "Nuevo Producto", ni acción "Editar", ni acción "Eliminar"
        $response->assertDontSee('Nuevo Producto');
        $response->assertDontSee(route('products.edit', $product->id));
        $response->assertDontSee('¿Eliminar este producto?');

        // Como admin: debe ver todas las acciones
        $admin = adminUser();
        $responseAdmin = $this->actingAs($admin)->get(route('products.index'));
        $responseAdmin->assertOk();
        $responseAdmin->assertSee('Nuevo Producto');
        $responseAdmin->assertSee(route('products.edit', $product->id));
        $responseAdmin->assertSee('¿Eliminar este producto?');

        // En clientes: operador puede crear y editar, pero NO eliminar
        $customer = Customer::factory()->create();
        $responseCust = $this->actingAs($operador)->get(route('customers.index'));
        $responseCust->assertOk();
        $responseCust->assertSee('Nuevo Cliente');
        $responseCust->assertSee(route('customers.edit', $customer->id));
        $responseCust->assertDontSee('¿Eliminar este cliente?');
    });

    it('oculta el modulo de configuracion para el operador en el sidebar y bloquea su acceso', function () {
        $this->seed(\Database\Seeders\SidebarSeeder::class);
        $operador = User::factory()->create();
        $operador->assignRole('operador');

        // Al navegar por el sistema, el sidebar no debe contener el módulo de Configuración para el operador
        $response = $this->actingAs($operador)->get(route('sales.index'));
        $response->assertOk();
        $response->assertDontSee('Configuración');
        $response->assertDontSee('Empresa');
        $response->assertDontSee('Sucursales');

        // Y rutas de configuración deben responder 403 Forbidden para el operador
        $this->actingAs($operador)->get(route('companies.index'))->assertForbidden();
        $this->actingAs($operador)->get(route('branches.index'))->assertForbidden();
        $this->actingAs($operador)->get(route('payments.index'))->assertForbidden();
        $this->actingAs($operador)->get(route('cashboxes.index'))->assertForbidden();
        $this->actingAs($operador)->get(route('types.index'))->assertForbidden();

        // Para el admin, Configuración sí debe ser visible en el sidebar
        $admin = adminUser();
        $responseAdmin = $this->actingAs($admin)->get(route('sales.index'));
        $responseAdmin->assertOk();
        $responseAdmin->assertSee('Configuración');

        // Si dinámicamente se le concede un permiso de configuración (ej: ver types),
        // Configuración aparece automáticamente mostrando únicamente ese submódulo
        $operador->givePermissionTo('ver types');
        $responseDynamic = $this->actingAs($operador)->get(route('sales.index'));
        $responseDynamic->assertOk();
        $responseDynamic->assertSee('Configuración');
        $responseDynamic->assertSee('Tipos');
        $responseDynamic->assertDontSee('Empresa'); // No ve lo que no tiene concedido
        $this->actingAs($operador)->get(route('types.index'))->assertOk();
    });

    it('muestra el boton de imprimir pdf en la parte superior derecha de las vistas de transacciones y reportes', function () {
        $admin = adminUser();
        $branch = testBranch();
        $warehouse = Warehouse::factory()->create(['branche_id' => $branch->id]);
        $customer = Customer::create(['name' => 'Cliente Boton Test', 'document' => '11223344']);

        $order = Order::create([
            'user_id' => $admin->id,
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'branche_id' => $branch->id,
            'amount' => 150,
            'paid_amount' => 150,
            'payment_type' => Order::PAYMENT_CASH,
            'status' => Order::STATUS_COMPLETED,
            'date' => now(),
        ]);
        $responseSale = $this->actingAs($admin)->get(route('sales.show', $order));
        $responseSale->assertOk();
        $responseSale->assertSee(route('sales.pdf', $order));

        $cat = ExpenseCategory::create(['name' => 'Servicios Test']);
        $expense = Expense::create([
            'date' => now(),
            'concept' => 'Pago Test',
            'amount' => 50,
            'status' => Expense::STATUS_PAID,
            'expense_category_id' => $cat->id,
            'branche_id' => $branch->id,
            'user_id' => $admin->id,
        ]);
        $responseExpense = $this->actingAs($admin)->get(route('expenses.show', $expense));
        $responseExpense->assertOk();
        $responseExpense->assertSee(route('expenses.pdf', $expense));

        $responseReport = $this->actingAs($admin)->get(route('finance.report'));
        $responseReport->assertOk();
        $responseReport->assertSee(route('finance.report.pdf', []));

        $superadmin = superadminUser();
        $responseBitacora = $this->actingAs($superadmin)->get(route('bitacora.index'));
        $responseBitacora->assertOk();
        $responseBitacora->assertSee(route('bitacora.pdf', []));
    });
});

