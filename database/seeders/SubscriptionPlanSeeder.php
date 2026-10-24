<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Básico',
                'description' => 'Ideal para pequeños negocios que están arrancando.',
                'price' => 190.00,
                'currency' => 'BOB',
                'billing_cycle' => 'monthly',
                'max_users' => 2,
                'features' => [
                    'Punto de venta (POS)',
                    'Control de inventario y kardex',
                    'Historial de ventas',
                    'Hasta 2 usuarios',
                    'Soporte por WhatsApp (horario hábil)',
                ],
                'active' => true,
            ],
            [
                'name' => 'Pro',
                'description' => 'Para negocios en crecimiento que necesitan control financiero completo.',
                'price' => 390.00,
                'currency' => 'BOB',
                'billing_cycle' => 'monthly',
                'max_users' => 8,
                'features' => [
                    'Todo lo del plan Básico',
                    'Compras y gestión de proveedores',
                    'Gastos operativos por categoría',
                    'Apertura y cierre de caja (arqueo)',
                    'Ventas a crédito con historial de abonos',
                    'Reportes exportables (Excel y PDF)',
                    'Hasta 8 usuarios',
                    'Soporte por WhatsApp 24/5',
                ],
                'active' => true,
            ],
            [
                'name' => 'Elite',
                'description' => 'Para negocios con consignaciones, pedidos y múltiples roles de usuario.',
                'price' => 690.00,
                'currency' => 'BOB',
                'billing_cycle' => 'monthly',
                'max_users' => null, // ilimitado
                'features' => [
                    'Todo lo del plan Pro',
                    'Consignaciones (entrega, liquidación y devolución)',
                    'Pedidos con anticipo y stock reservado',
                    'Escáner de código de barras / QR',
                    'Roles y permisos personalizados',
                    'Usuarios ilimitados',
                    'Soporte prioritario 24/7',
                ],
                'active' => true,
            ],
            [
                'name' => 'Pro Anual',
                'description' => 'Todo el Plan Pro con 2 meses gratis pagando anual.',
                'price' => 3900.00,
                'currency' => 'BOB',
                'billing_cycle' => 'yearly',
                'max_users' => 8,
                'features' => [
                    'Todo lo del plan Pro',
                    '2 meses gratis frente al pago mensual',
                    'Dominio .com personalizado',
                ],
                'active' => true,
            ],
            [
                'name' => 'Elite Anual',
                'description' => 'Todo el Plan Elite + Dominio .com Personalizado.',
                'price' => 6900.00,
                'currency' => 'BOB',
                'billing_cycle' => 'yearly',
                'max_users' => null,
                'features' => [
                    'Todo lo del plan Elite',
                    '2 meses gratis frente al pago mensual',
                    'Dominio .com personalizado',
                ],
                'active' => true,
            ],
            [
                'name' => 'Personalizado',
                'description' => 'Software a medida, adaptado 100% a las necesidades de tu negocio.',
                'price' => 0,
                'currency' => 'USD',
                'billing_cycle' => 'yearly',
                'max_users' => null,
                'features' => [
                    'Desarrollo de módulos exclusivos para tu rubro',
                    'Integraciones a medida (facturación, delivery, otros sistemas)',
                    'Consultoría directa con el desarrollador',
                    'Sin límite de usuarios ni funcionalidades',
                ],
                'active' => true,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(
                ['name' => $plan['name'], 'billing_cycle' => $plan['billing_cycle']],
                $plan
            );
        }
    }
}
