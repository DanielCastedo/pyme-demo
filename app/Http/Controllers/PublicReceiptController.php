<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Consignment;
use App\Models\Order;
use App\Models\Purchase;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Recibos en PDF accesibles sin iniciar sesión, para el QR que se imprime en el propio recibo:
 * el cliente (o proveedor) lo escanea y ve el mismo PDF desde su celular. Las rutas van firmadas
 * (middleware "signed"): sin la firma no cargan, así que no alcanza con adivinar el número de venta/
 * pedido/consignación/compra para ver el recibo de otro.
 */
class PublicReceiptController extends Controller
{
    public function sale(Order $sale)
    {
        abort_if($sale->isPending(), 404);

        $sale->load('customer', 'user', 'warehouse', 'branch', 'items.product', 'items.productUnit', 'payments.transaction.paymentMethod', 'payments.transaction.cashbox');
        $company = Company::first();

        $pdf = Pdf::loadView('sales.pdf', compact('sale', 'company'));
        $pdf->setPaper('letter', 'portrait');

        return $pdf->stream("Venta-{$sale->id}.pdf");
    }

    public function pedido(Order $pedido)
    {
        abort_unless($pedido->isPedido(), 404);

        $pedido->load('customer', 'user', 'warehouse', 'branch', 'items.product', 'items.productUnit');
        $company = Company::first();

        $pdf = Pdf::loadView('pedidos.pdf', compact('pedido', 'company'));
        $pdf->setPaper('letter', 'portrait');

        return $pdf->stream("Pedido-{$pedido->id}.pdf");
    }

    public function consignment(Consignment $consignment)
    {
        $consignment->load('customer', 'warehouse', 'branch', 'user', 'items.product', 'items.productUnit');
        $company = Company::first();

        $pdf = Pdf::loadView('consignments.pdf', compact('consignment', 'company'));
        $pdf->setPaper('letter', 'portrait');

        return $pdf->stream("Consignacion-{$consignment->id}.pdf");
    }

    public function purchase(Purchase $purchase)
    {
        $purchase->load('supplier', 'items.product', 'items.productUnit', 'items.distributions.warehouse', 'receipts', 'warehouse', 'branch', 'user', 'transaction.cashbox', 'transaction.paymentMethod');
        $company = Company::first();

        $pdf = Pdf::loadView('purchases.pdf', compact('purchase', 'company'));
        $pdf->setPaper('letter', 'portrait');

        return $pdf->stream("Compra-{$purchase->id}.pdf");
    }
}
