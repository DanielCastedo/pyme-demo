<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use App\Models\Branch;
use App\Models\Cashbox;
use App\Models\Category;
use App\Models\Consignment;
use App\Models\Order;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductComponent;
use App\Models\ProductUnit;
use App\Models\ProductWarehouse;
use App\Models\Promotion;
use App\Models\StockMovement;
use App\Models\Type;
use App\Models\Warehouse;
use App\Policies\BranchPolicy;
use App\Policies\CashboxPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\ConsignmentPolicy;
use App\Policies\OrderPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\PaymentMethodPolicy;
use App\Policies\ProductComponentPolicy;
use App\Policies\ProductPolicy;
use App\Policies\ProductUnitPolicy;
use App\Policies\ProductWarehousePolicy;
use App\Policies\PromotionPolicy;
use App\Policies\StockMovementPolicy;
use App\Policies\TypePolicy;
use App\Policies\WarehousePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Type::class => TypePolicy::class,
        PaymentMethod::class => PaymentMethodPolicy::class,
        Category::class => CategoryPolicy::class,
        Product::class => ProductPolicy::class,
        Warehouse::class => WarehousePolicy::class,
        Branch::class => BranchPolicy::class,
        Promotion::class => PromotionPolicy::class,
        ProductUnit::class => ProductUnitPolicy::class,
        ProductWarehouse::class => ProductWarehousePolicy::class,
        ProductComponent::class => ProductComponentPolicy::class,
        StockMovement::class => StockMovementPolicy::class,
        Order::class => OrderPolicy::class,
        Consignment::class => ConsignmentPolicy::class,
        \App\Models\Unit::class => \App\Policies\UnitPolicy::class,
        \App\Models\Expense::class => \App\Policies\ExpensePolicy::class,
        \App\Models\ExpenseCategory::class => \App\Policies\ExpenseCategoryPolicy::class,
        \App\Models\Supplier::class => \App\Policies\SupplierPolicy::class,
        \App\Models\Purchase::class => \App\Policies\PurchasePolicy::class,
        Customer::class => CustomerPolicy::class,
        Cashbox::class => CashboxPolicy::class,
        \App\Models\Quotation::class => \App\Policies\QuotationPolicy::class,
        \App\Models\User::class => \App\Policies\UserPolicy::class,
        \Spatie\Permission\Models\Role::class => \App\Policies\RolePolicy::class,
        \App\Models\Role::class => \App\Policies\RolePolicy::class,
        \App\Models\Company::class => \App\Policies\CompanyPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}
