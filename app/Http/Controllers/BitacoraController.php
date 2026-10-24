<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Consignment;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\Supplier;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class BitacoraController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless(auth()->user()?->hasRole('superadmin'), 403, 'Solo el superadmin puede ver la bitácora.');
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $f = $this->filtersFrom($request);
        $baseQuery = $this->buildQuery($f);

        $perPage = in_array($f['per_page'], [20, 50, 100], true) ? (int) $f['per_page'] : 20;
        $activities = (clone $baseQuery)->paginate($perPage)->withQueryString();

        $summary = $this->calculateSummary($f);
        $filterData = $this->filterData();

        return view('bitacora.index', [
            'f' => $f,
            'activities' => $activities,
            'summary' => $summary,
            'filterData' => $filterData,
        ]);
    }

    public function downloadPdf(Request $request)
    {
        $f = $this->filtersFrom($request);
        $activities = $this->buildQuery($f)->limit(1000)->get();
        $company = Company::first();
        $summary = $this->calculateSummary($f);

        $userName = $f['user_id'] ? (User::find($f['user_id'])?->name ?? 'Usuario') : 'Todos';

        $pdf = Pdf::loadView('bitacora.pdf', compact('activities', 'company', 'f', 'userName', 'summary'));
        $pdf->setPaper('letter', 'portrait');

        return $pdf->stream('Bitacora-Actividades-' . date('Ymd') . '.pdf');
    }

    private function filtersFrom(Request $request): array
    {
        $date = fn ($v, $default = null) => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $v) ? $v : $default;

        $from = $request->has('from')
            ? ($request->filled('from') ? $date($request->input('from')) : null)
            : now()->startOfMonth()->toDateString();

        $to = $request->has('to')
            ? ($request->filled('to') ? $date($request->input('to')) : null)
            : now()->toDateString();

        if ($from && $to && $from > $to) {
            [$from, $to] = [$to, $from];
        }

        return [
            'from' => $from,
            'to' => $to,
            'user_id' => $request->filled('user_id') ? (int) $request->input('user_id') : null,
            'log_name' => $request->filled('log_name') ? $request->input('log_name') : null,
            'event' => $request->filled('event') ? $request->input('event') : null,
            'buscar' => $request->filled('buscar') ? trim($request->input('buscar')) : null,
            'order' => in_array($request->input('order'), ['asc', 'desc'], true) ? $request->input('order') : 'desc',
            'per_page' => in_array((int) $request->input('per_page'), [20, 50, 100], true) ? (int) $request->input('per_page') : 20,
        ];
    }

    private function buildQuery(array $f)
    {
        return Activity::with(['causer', 'subject'])
            ->when($f['user_id'], fn ($q) => $q->where('causer_id', $f['user_id']))
            ->when($f['log_name'], fn ($q) => $q->where('log_name', $f['log_name']))
            ->when($f['event'], fn ($q) => $q->where('event', $f['event']))
            ->when($f['from'], fn ($q) => $q->whereDate('created_at', '>=', $f['from']))
            ->when($f['to'], fn ($q) => $q->whereDate('created_at', '<=', $f['to']))
            ->when($f['buscar'], function ($q) use ($f) {
                $term = $f['buscar'];
                $q->where(function ($sub) use ($term) {
                    $sub->where('description', 'like', "%{$term}%")
                        ->orWhere('properties', 'like', "%{$term}%")
                        ->orWhereHasMorph('causer', [User::class], fn ($u) => $u->where('name', 'like', "%{$term}%"))
                        ->orWhereHasMorph('subject', [Customer::class], fn ($c) => $c->where('name', 'like', "%{$term}%")->orWhere('document', 'like', "%{$term}%"))
                        ->orWhereHasMorph('subject', [Product::class], fn ($p) => $p->where('name', 'like', "%{$term}%")->orWhere('sku', 'like', "%{$term}%"))
                        ->orWhereHasMorph('subject', [Supplier::class], fn ($s) => $s->where('name', 'like', "%{$term}%"))
                        ->orWhereHasMorph('subject', [User::class], fn ($u) => $u->where('name', 'like', "%{$term}%"))
                        ->orWhereHasMorph('subject', [Order::class], fn ($o) => $o->whereHas('customer', fn ($c) => $c->where('name', 'like', "%{$term}%")))
                        ->orWhereHasMorph('subject', [Quotation::class], fn ($quo) => $quo->whereHas('customer', fn ($c) => $c->where('name', 'like', "%{$term}%")))
                        ->orWhereHasMorph('subject', [Consignment::class], fn ($con) => $con->whereHas('customer', fn ($c) => $c->where('name', 'like', "%{$term}%")));
                });
            })
            ->orderBy('id', $f['order']);
    }

    private function calculateSummary(array $f): array
    {
        $base = $this->buildQuery($f);

        return [
            'total' => (clone $base)->count(),
            'created' => (clone $base)->where('event', 'created')->count(),
            'updated' => (clone $base)->where('event', 'updated')->count(),
            'deleted' => (clone $base)->where('event', 'deleted')->count(),
            'auth' => (clone $base)->whereIn('event', ['login', 'logout', 'failed'])->count(),
            'users_count' => (clone $base)->whereNotNull('causer_id')->distinct('causer_id')->count('causer_id'),
        ];
    }

    private function filterData(): array
    {
        $knownModules = [
            'ventas', 'compras', 'gastos', 'consignaciones', 'cotizaciones', 'clientes',
            'productos', 'usuarios', 'empresa', 'sucursales', 'almacenes', 'proveedores',
            'categorias', 'categorias_gastos', 'cajas', 'metodos_pago', 'movimientos_stock',
            'promociones', 'liquidaciones_consignacion', 'autenticacion', 'general'
        ];
        $dbModules = Activity::distinct()->pluck('log_name')->filter()->toArray();
        $logNames = collect(array_unique(array_merge($knownModules, $dbModules)))->sort()->values();

        $eventOptions = [
            'created' => 'Creación',
            'updated' => 'Modificación',
            'deleted' => 'Eliminación',
            'login' => 'Inicio de Sesión',
            'logout' => 'Cierre de Sesión',
            'failed' => 'Acceso Fallido',
        ];

        // 1. Clientes
        $customerSuggestions = Customer::orderBy('name')
            ->limit(15)
            ->pluck('name')
            ->map(fn ($n) => ['type' => 'Cliente', 'text' => $n]);

        // 2. Usuarios
        $userSuggestions = User::orderBy('name')
            ->limit(10)
            ->pluck('name')
            ->map(fn ($n) => ['type' => 'Usuario', 'text' => $n]);

        // 3. Productos
        $productSuggestions = Product::orderBy('name')
            ->limit(10)
            ->pluck('name')
            ->map(fn ($n) => ['type' => 'Producto', 'text' => $n]);

        // 4. Proveedores
        $supplierSuggestions = Supplier::orderBy('name')
            ->limit(10)
            ->pluck('name')
            ->map(fn ($n) => ['type' => 'Proveedor', 'text' => $n]);

        // 5. Actividades recientes
        $recentActivitySuggestions = Activity::latest('id')
            ->limit(20)
            ->pluck('description')
            ->filter()
            ->unique()
            ->values()
            ->take(8)
            ->map(fn ($desc) => ['type' => 'Reciente', 'text' => $desc]);

        // 6. Módulos y acciones clave
        $systemKeywordSuggestions = collect([
            ['type' => 'Módulo', 'text' => 'Ventas'],
            ['type' => 'Módulo', 'text' => 'Compras'],
            ['type' => 'Módulo', 'text' => 'Gastos'],
            ['type' => 'Módulo', 'text' => 'Consignaciones'],
            ['type' => 'Módulo', 'text' => 'Cotizaciones'],
            ['type' => 'Módulo', 'text' => 'Clientes'],
            ['type' => 'Módulo', 'text' => 'Productos'],
            ['type' => 'Módulo', 'text' => 'Cajas'],
            ['type' => 'Acción', 'text' => 'Creación'],
            ['type' => 'Acción', 'text' => 'Modificación'],
            ['type' => 'Acción', 'text' => 'Eliminación'],
            ['type' => 'Seguridad', 'text' => 'Inicio de sesión'],
            ['type' => 'Seguridad', 'text' => 'Cierre de sesión'],
        ]);

        $suggestions = $customerSuggestions
            ->concat($userSuggestions)
            ->concat($systemKeywordSuggestions)
            ->concat($productSuggestions)
            ->concat($supplierSuggestions)
            ->concat($recentActivitySuggestions)
            ->values();

        return [
            'users' => User::orderBy('name')->pluck('name', 'id'),
            'logNames' => $logNames,
            'events' => $eventOptions,
            'suggestions' => $suggestions,
        ];
    }
}
