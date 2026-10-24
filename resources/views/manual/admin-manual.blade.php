<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Manual de Funcionamiento del Sistema - Rol Administrador</title>
    <style>
        @page {
            margin-top: 55pt;
            margin-bottom: 45pt;
            margin-left: 35pt;
            margin-right: 35pt;
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 8.5pt;
            color: #1e293b;
            line-height: 1.4;
            background-color: #ffffff;
        }

        /* Encabezado fijo superior */
        .fixed-header {
            position: fixed;
            top: -42pt;
            left: 0;
            right: 0;
            width: 100%;
            border-bottom: 1.5pt solid #059669;
            padding-bottom: 4pt;
        }

        .fixed-header table {
            width: 100%;
            border-collapse: collapse;
        }

        .fixed-header td.title {
            font-size: 7.5pt;
            font-weight: bold;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .fixed-header td.subtitle {
            font-size: 7.5pt;
            font-weight: bold;
            color: #059669;
            text-align: right;
            text-transform: uppercase;
        }

        /* Pie de página fijo */
        .fixed-footer {
            position: fixed;
            bottom: -32pt;
            left: 0;
            right: 0;
            width: 100%;
            border-top: 1pt solid #cbd5e1;
            padding-top: 4pt;
        }

        .fixed-footer table {
            width: 100%;
            border-collapse: collapse;
        }

        .fixed-footer td.note {
            font-size: 7pt;
            color: #64748b;
        }

        .fixed-footer td.pages {
            font-size: 7.5pt;
            font-weight: bold;
            color: #0f172a;
            text-align: right;
        }

        .pagenum:before {
            content: "Página " counter(page);
        }

        /* Saltos de página controlados */
        .page-break {
            page-break-after: always;
        }

        .no-break {
            page-break-inside: avoid;
        }

        /* Portada Elegante */
        .cover {
            text-align: center;
            padding-top: 40pt;
            page-break-after: always;
        }

        .cover-tag {
            display: inline-block;
            background-color: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
            padding: 3pt 10pt;
            border-radius: 10pt;
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 20pt;
        }

        .cover-title {
            font-size: 22pt;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.2;
            margin: 0 0 8pt 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .cover-subtitle {
            font-size: 11pt;
            font-weight: 600;
            color: #059669;
            margin: 0 0 25pt 0;
        }

        .cover-divider {
            width: 60pt;
            height: 3pt;
            background-color: #059669;
            margin: 0 auto 30pt auto;
        }

        .cover-box {
            background-color: #f8fafc;
            border: 1pt solid #cbd5e1;
            border-radius: 6pt;
            width: 80%;
            margin: 0 auto 30pt auto;
            padding: 12pt 16pt;
            text-align: left;
        }

        .cover-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5pt;
        }

        .cover-table td {
            padding: 3.5pt 0;
        }

        .cover-table td.lbl {
            font-weight: bold;
            color: #475569;
            width: 38%;
        }

        .cover-table td.val {
            color: #0f172a;
            font-weight: 600;
        }

        .cover-note {
            font-size: 7.5pt;
            color: #64748b;
            line-height: 1.4;
            width: 85%;
            margin: 0 auto;
        }

        /* Encabezados de secciones */
        h1 {
            font-size: 13pt;
            font-weight: bold;
            color: #0f172a;
            border-bottom: 1.5pt solid #059669;
            padding-bottom: 3pt;
            margin-top: 14pt;
            margin-bottom: 8pt;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        h2 {
            font-size: 10pt;
            font-weight: bold;
            color: #047857;
            margin-top: 10pt;
            margin-bottom: 5pt;
            border-left: 2.5pt solid #059669;
            padding-left: 5pt;
        }

        h3 {
            font-size: 9pt;
            font-weight: bold;
            color: #1e293b;
            margin-top: 8pt;
            margin-bottom: 3pt;
        }

        p {
            margin: 0 0 6pt 0;
            text-align: justify;
        }

        ul, ol {
            margin: 0 0 6pt 0;
            padding-left: 15pt;
        }

        li {
            margin-bottom: 2pt;
        }

        /* Cajas de alerta y notas */
        .callout {
            border-radius: 4pt;
            padding: 6pt 8pt;
            margin: 6pt 0;
            font-size: 8pt;
            line-height: 1.35;
        }

        .callout-info {
            background-color: #f0fdf4;
            border-left: 3pt solid #10b981;
            color: #065f46;
        }

        .callout-warning {
            background-color: #fffbeb;
            border-left: 3pt solid #f59e0b;
            color: #92400e;
        }

        .callout-danger {
            background-color: #fef2f2;
            border-left: 3pt solid #ef4444;
            color: #991b1b;
        }

        .callout-title {
            font-weight: bold;
            display: block;
            margin-bottom: 2pt;
            text-transform: uppercase;
            font-size: 7.5pt;
        }

        /* Tablas de datos */
        table.grid {
            width: 100%;
            border-collapse: collapse;
            margin: 6pt 0 10pt 0;
            font-size: 8pt;
        }

        table.grid th {
            background-color: #0f172a;
            color: #ffffff;
            font-weight: bold;
            text-transform: uppercase;
            padding: 4pt 6pt;
            text-align: left;
            font-size: 7.5pt;
            border: 1pt solid #0f172a;
        }

        table.grid td {
            padding: 4pt 6pt;
            border: 1pt solid #cbd5e1;
            vertical-align: top;
        }

        table.grid tr:nth-child(even) td {
            background-color: #f8fafc;
        }

        /* Pasos numerados ordenados por tabla (sin fallos de float en DomPDF) */
        table.step-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6pt;
        }

        table.step-table td.num {
            width: 22pt;
            vertical-align: top;
            padding-top: 1pt;
        }

        table.step-table td.content {
            vertical-align: top;
            padding-bottom: 5pt;
        }

        .badge-step {
            background-color: #059669;
            color: #ffffff;
            font-weight: bold;
            font-size: 7pt;
            width: 15pt;
            height: 15pt;
            line-height: 15pt;
            text-align: center;
            border-radius: 50%;
            display: block;
        }

        .step-heading {
            font-weight: bold;
            color: #0f172a;
            font-size: 8.5pt;
            margin-bottom: 2pt;
        }

        .step-text {
            color: #334155;
            font-size: 8pt;
            line-height: 1.35;
        }

        /* Etiquetas */
        .tag {
            display: inline-block;
            padding: 1pt 4pt;
            border-radius: 2pt;
            font-size: 6.5pt;
            font-weight: bold;
            text-transform: uppercase;
        }

        .tag-green { background-color: #dcfce7; color: #15803d; border: 1pt solid #86efac; }
        .tag-red { background-color: #fee2e2; color: #b91c1c; border: 1pt solid #fca5a5; }
        .tag-blue { background-color: #e0e7ff; color: #3730a3; border: 1pt solid #a5b4fc; }

        /* Tabla de Índice */
        table.toc {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
            margin-top: 8pt;
        }

        table.toc td {
            padding: 3.5pt 0;
            border-bottom: 1pt dotted #cbd5e1;
        }

        table.toc td.ch {
            font-weight: bold;
            color: #059669;
            width: 30pt;
        }

        table.toc td.name {
            font-weight: 600;
            color: #1e293b;
        }

        table.toc td.target {
            text-align: right;
            color: #64748b;
            font-weight: bold;
            width: 50pt;
        }
    </style>
</head>
<body>

    <!-- Encabezado fijo -->
    <div class="fixed-header">
        <table>
            <tr>
                <td class="title">Manual de Funcionamiento &bull; Rol Administrador</td>
                <td class="subtitle">Sistema ERP / Gestión Comercial</td>
            </tr>
        </table>
    </div>

    <!-- Pie de página fijo -->
    <div class="fixed-footer">
        <table>
            <tr>
                <td class="note">Documento Operativo Oficial &bull; {{ $company->name ?? 'Sistema Comercial' }}</td>
                <td class="pages pagenum"></td>
            </tr>
        </table>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <!-- PORTADA                                                               -->
    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <div class="cover">
        <div class="cover-tag">Manual Operativo de Usuario</div>
        <div class="cover-title">Manual de Funcionamiento<br>Paso a Paso del Sistema</div>
        <div class="cover-subtitle">Guía de Operación Integral para el Rol de Administrador</div>
        <div class="cover-divider"></div>

        <div class="cover-box">
            <table class="cover-table">
                <tr>
                    <td class="lbl">Rol Asignado:</td>
                    <td class="val">Administrador / Administradora (No Superadmin)</td>
                </tr>
                <tr>
                    <td class="lbl">Empresa / Sistema:</td>
                    <td class="val">{{ $company->name ?? 'Sistema Comercial' }}</td>
                </tr>
                <tr>
                    <td class="lbl">Moneda Oficial:</td>
                    <td class="val">{{ $company->currency ?? 'BOB' }} (Bolivianos - Bs.)</td>
                </tr>
                <tr>
                    <td class="lbl">Versión del Manual:</td>
                    <td class="val">Versión 2.0 (Edición Revisada y Ordenada)</td>
                </tr>
                <tr>
                    <td class="lbl">Fecha de Emisión:</td>
                    <td class="val">{{ date('d/m/Y') }}</td>
                </tr>
                <tr>
                    <td class="lbl">Ámbito de Aplicación:</td>
                    <td class="val">Operaciones Diarias, Inventario, Ventas y Finanzas</td>
                </tr>
            </table>
        </div>

        <div class="cover-note">
            Este manual ha sido elaborado para guiar paso a paso al <strong>Administrador</strong> en el uso del sistema. Define con exactitud las acciones operativas autorizadas, flujos de trabajo, políticas de seguridad y diferencias frente al Superadmin.
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <!-- ÍNDICE DE CONTENIDOS                                                  -->
    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <div class="page-break">
        <h1>Índice de Contenidos</h1>
        <p>A continuación se resume la estructura temática del manual para consulta rápida:</p>

        <table class="toc">
            <tr>
                <td class="ch">Cap. 1</td>
                <td class="name">Introducción, Jerarquía y Alcance del Rol Administrador</td>
                <td class="target">Pág. 3</td>
            </tr>
            <tr>
                <td class="ch">Cap. 2</td>
                <td class="name">Acceso al Sistema y Términos de Licencia por Suscripción (SaaS)</td>
                <td class="target">Pág. 4</td>
            </tr>
            <tr>
                <td class="ch">Cap. 3</td>
                <td class="name">Panel de Control (Dashboard), Métricas y Entorno Visual</td>
                <td class="target">Pág. 4</td>
            </tr>
            <tr>
                <td class="ch">Cap. 4</td>
                <td class="name">Módulo de Ventas y Punto de Venta (POS) Paso a Paso</td>
                <td class="target">Pág. 5</td>
            </tr>
            <tr>
                <td class="ch">Cap. 5</td>
                <td class="name">Gestión de Clientes, Límites de Crédito y Cuentas por Cobrar</td>
                <td class="target">Pág. 6</td>
            </tr>
            <tr>
                <td class="ch">Cap. 6</td>
                <td class="name">Catálogo de Productos, Presentaciones, Fotos y Visor Ampliado</td>
                <td class="target">Pág. 7</td>
            </tr>
            <tr>
                <td class="ch">Cap. 7</td>
                <td class="name">Movimientos de Stock y Cálculo Bidireccional de Costos</td>
                <td class="target">Pág. 8</td>
            </tr>
            <tr>
                <td class="ch">Cap. 8</td>
                <td class="name">Compras de Mercadería y Gestión de Proveedores</td>
                <td class="target">Pág. 9</td>
            </tr>
            <tr>
                <td class="ch">Cap. 9</td>
                <td class="name">Finanzas, Cajas, Arqueos de Turno y Control de Gastos</td>
                <td class="target">Pág. 9</td>
            </tr>
            <tr>
                <td class="ch">Cap. 10</td>
                <td class="name">Cotizaciones (Proformas), Pedidos y Consignaciones</td>
                <td class="target">Pág. 10</td>
            </tr>
            <tr>
                <td class="ch">Cap. 11</td>
                <td class="name">Administración de Usuarios y Reglas de Seguridad Blindadas</td>
                <td class="target">Pág. 11</td>
            </tr>
            <tr>
                <td class="ch">Cap. 12</td>
                <td class="name">Configuración de Empresa, Sucursales y Código QR de Cobro</td>
                <td class="target">Pág. 12</td>
            </tr>
            <tr>
                <td class="ch">Cap. 13</td>
                <td class="name">Guía Rápida de Solución de Problemas y Preguntas Frecuentes</td>
                <td class="target">Pág. 12</td>
            </tr>
        </table>

        <div class="callout callout-info" style="margin-top: 15pt;">
            <span class="callout-title">Regla de Oro Operativa</span>
            Todo cobro en efectivo o QR requiere que la sucursal cuente con una <strong>Caja Abierta</strong> en la fecha actual. Nunca intente iniciar ventas sin haber aperturado la caja.
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <!-- CAPÍTULO 1: INTRODUCCIÓN Y JERARQUÍA                                  -->
    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <div class="no-break">
        <h1>Capítulo 1: Introducción y Alcance del Rol Administrador</h1>
        <p>El sistema ERP permite gobernar todos los procesos del negocio. Para salvaguardar la información y la estabilidad del servicio, el software distingue claramente entre la administración técnica de la plataforma y la gestión comercial de la empresa.</p>

        <h2>Jerarquía de Roles en el Sistema</h2>
        <table class="grid">
            <thead>
                <tr>
                    <th style="width: 25%;">Rol</th>
                    <th style="width: 50%;">Propósito y Responsabilidades</th>
                    <th style="width: 25%;">Nivel de Acceso</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Superadmin</strong></td>
                    <td>Propietario de la plataforma SaaS, control de suscripciones maestras y auditoría técnica forense (Bitácora).</td>
                    <td><span class="tag tag-red">Plataforma / Global</span></td>
                </tr>
                <tr>
                    <td><strong>Administrador (Usted)</strong></td>
                    <td>Gestión total de la empresa: ventas, inventarios, compras, precios, clientes, cajas, reportes y creación de cajeros/vendedores.</td>
                    <td><span class="tag tag-green">Gestión de Negocio</span></td>
                </tr>
                <tr>
                    <td><strong>Cajero / Vendedor</strong></td>
                    <td>Operación directa de mostrador: emisión de tickets, proformas y cobro de ventas en caja autorizada.</td>
                    <td><span class="tag tag-blue">Operativo Mostrador</span></td>
                </tr>
            </tbody>
        </table>

        <h2>Facultades Autorizadas vs. Restricciones de Seguridad</h2>
        <table class="grid">
            <thead>
                <tr>
                    <th style="width: 50%;">Lo que el Administrador PUEDE hacer ✔</th>
                    <th style="width: 50%;">Lo que está RESTRINGIDO al Administrador ❌</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Emitir ventas, anular ventas y aplicar descuentos.</td>
                    <td>No puede ver, listar, editar ni eliminar al Superadmin.</td>
                </tr>
                <tr>
                    <td>Crear y editar productos, unidades de medida y fotos.</td>
                    <td>No tiene acceso a la Bitácora de Auditoría del sistema.</td>
                </tr>
                <tr>
                    <td>Registrar compras, proveedores y movimientos de stock.</td>
                    <td>No puede eliminar Roles ni Permisos del sistema.</td>
                </tr>
                <tr>
                    <td>Abrir, supervisar y arquear Cajas por sucursal.</td>
                    <td>No puede registrar una nueva empresa ni borrar la actual.</td>
                </tr>
                <tr>
                    <td>Crear y dar de baja a cajeros, vendedores u otros admins.</td>
                    <td>No puede modificar planes maestros de suscripción SaaS.</td>
                </tr>
                <tr>
                    <td>Actualizar datos de la empresa, logo y QR de cobro.</td>
                    <td>No puede alterar la arquitectura de menús laterales.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <!-- CAPÍTULO 2: ACCESO Y TÉRMINOS Y CONDICIONES                           -->
    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <div class="no-break" style="margin-top: 15pt;">
        <h1>Capítulo 2: Acceso y Términos de Licencia por Suscripción</h1>
        <p>El ingreso al sistema se realiza a través de su navegador web con su correo electrónico y contraseña asignada.</p>

        <h2>Primer Ingreso y Aceptación de Términos</h2>
        <p>Si usted fue designado como Administrador por la administración central, al ingresar por primera vez se activará la pantalla de lectura obligatoria del contrato de suscripción:</p>

        <table class="step-table">
            <tr>
                <td class="num"><span class="badge-step">1</span></td>
                <td class="content">
                    <div class="step-heading">Inicio de Sesión:</div>
                    <div class="step-text">Ingrese su correo y contraseña en la pantalla de Login y haga clic en <em>"Iniciar Sesión"</em>.</div>
                </td>
            </tr>
            <tr>
                <td class="num"><span class="badge-step">2</span></td>
                <td class="content">
                    <div class="step-heading">Pantalla de Intercepción Obligatoria:</div>
                    <div class="step-text">El sistema lo redirigirá a <code>/terminos/pendiente</code>. Esta pantalla bloquea el resto del sistema hasta que tome una decisión formal.</div>
                </td>
            </tr>
            <tr>
                <td class="num"><span class="badge-step">3</span></td>
                <td class="content">
                    <div class="step-heading">Cláusula 4.2 sobre Modificaciones de Código:</div>
                    <div class="step-text">
                        En el contrato se estipula expresamente:
                        <div class="callout callout-warning" style="margin-top: 3pt;">
                            <em>"Una vez entregado el permiso de licencia del sistema bajo modalidad de suscripción, no se admitirán solicitudes de modificaciones de código fuente ni desarrollos adicionales como parte de la licencia estándar. Cualquier requerimiento de desarrollo a medida estará sujeto a cotización técnica independiente y costo extra."</em>
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="num"><span class="badge-step">4</span></td>
                <td class="content">
                    <div class="step-heading">Aceptación o Rechazo:</div>
                    <div class="step-text">
                        <ul>
                            <li><strong>Botón "Aceptar Términos":</strong> Registra su confirmación formal con fecha/hora y dirección IP. Su estado cambia a <strong>Confirmado</strong> y se desbloquea el acceso completo de forma permanente.</li>
                            <li><strong>Botón "Rechazar":</strong> Cierra su sesión inmediatamente y regresa al Login. No podrá operar hasta aceptar.</li>
                        </ul>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <!-- CAPÍTULO 3: DASHBOARD Y ENTORNO                                       -->
    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <div class="no-break" style="margin-top: 15pt;">
        <h1>Capítulo 3: Panel de Control (Dashboard) y Entorno</h1>
        <p>Al acceder, el Dashboard presenta un resumen ejecutivo de las métricas vitales del negocio:</p>

        <table class="grid">
            <thead>
                <tr>
                    <th style="width: 30%;">Indicador</th>
                    <th style="width: 70%;">Significado Operativo</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Ventas del Día</strong></td>
                    <td>Monto monetario total de ventas registradas en la fecha actual (contado y crédito).</td>
                </tr>
                <tr>
                    <td><strong>Ingresos en Caja</strong></td>
                    <td>Efectivo real y transferencias que ingresaron efectivamente a las cajas de las sucursales.</td>
                </tr>
                <tr>
                    <td><strong>Stock Crítico</strong></td>
                    <td>Número de productos cuyo inventario actual está igual o por debajo del stock mínimo.</td>
                </tr>
                <tr>
                    <td><strong>Cuentas por Cobrar</strong></td>
                    <td>Saldo total pendiente de cobro por ventas a crédito vigentes otorgadas a clientes.</td>
                </tr>
            </tbody>
        </table>

        <h2>Ajuste de Modo Oscuro / Modo Claro</h2>
        <p>En el encabezado superior derecho encontrará el botón con ícono de Sol / Luna. Haga clic para alternar entre modo claro y modo noche para trabajar con comodidad en turnos nocturnos.</p>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <!-- CAPÍTULO 4: MÓDULO DE VENTAS Y PUNTO DE VENTA (POS)                   -->
    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <div class="page-break">
        <h1>Capítulo 4: Módulo de Ventas y Punto de Venta (POS)</h1>
        <p>El Punto de Venta (POS) permite registrar ventas de mostrador de forma veloz, precisa y conectada con los inventarios.</p>

        <div class="callout callout-danger">
            <span class="callout-title">Requisito Obligatorio</span>
            La sucursal debe tener una <strong>Caja Abierta</strong> con fecha de hoy. Sin una caja abierta, el sistema no permitirá procesar cobros.
        </div>

        <h2>Paso a Paso para Emitir una Venta</h2>

        <table class="step-table">
            <tr>
                <td class="num"><span class="badge-step">1</span></td>
                <td class="content">
                    <div class="step-heading">Ingresar al POS:</div>
                    <div class="step-text">Vaya a <strong>Ventas &rarr; Ventas</strong> y haga clic en el botón verde superior <strong>"+ Nueva Venta"</strong>.</div>
                </td>
            </tr>
            <tr>
                <td class="num"><span class="badge-step">2</span></td>
                <td class="content">
                    <div class="step-heading">Verificar Sucursal y Almacén:</div>
                    <div class="step-text">En el panel lateral derecho, confirme la <strong>Sucursal</strong>, el <strong>Almacén</strong> (de donde sale la mercadería) y la <strong>Caja</strong> receptora.</div>
                </td>
            </tr>
            <tr>
                <td class="num"><span class="badge-step">3</span></td>
                <td class="content">
                    <div class="step-heading">Búsqueda e Inspección de Productos:</div>
                    <div class="step-text">
                        En la barra de búsqueda escriba el nombre, SKU o código de barras, o use el lector láser / cámara:
                        <ul>
                            <li><strong>Miniatura y Foto:</strong> El resultado muestra una imagen clara del producto.</li>
                            <li><strong>Clic para Ampliar (Lightbox):</strong> Al hacer clic en la foto, se abre el <strong>visor modal ampliado</strong> para ver la imagen en alta resolución sin agregar el ítem por accidente.</li>
                            <li><strong>Agregar al ticket:</strong> Haga clic en el texto o presione Enter para agregar la unidad a la venta.</li>
                        </ul>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="num"><span class="badge-step">4</span></td>
                <td class="content">
                    <div class="step-heading">Tabla de Productos en Venta (Columnas Separadas):</div>
                    <div class="step-text">
                        La tabla separa la información para evitar cualquier compresión de texto:
                        <ul>
                            <li><strong>Columna Foto (w-20):</strong> Miniatura centrada con clic para ampliar en modal.</li>
                            <li><strong>Columna Producto:</strong> Nombre completo, presentación y stock disponible en almacén.</li>
                            <li><strong>Columna Cantidad:</strong> Ajuste con botones <strong>+</strong> / <strong>&minus;</strong> o número manual.</li>
                            <li><strong>Columna Precio:</strong> Permite editar precio unitario (solo con permiso asignado).</li>
                            <li><strong>Columna Descuento:</strong> Descuento en porcentaje (%) o monto directo en Bs.</li>
                            <li><strong>Columna Subtotal:</strong> Total calculado de forma instantánea.</li>
                        </ul>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="num"><span class="badge-step">5</span></td>
                <td class="content">
                    <div class="step-heading">Selección o Creación de Cliente:</div>
                    <div class="step-text">Seleccione el cliente en el buscador derecho. Si es nuevo, use el botón <em>"+ Nuevo Cliente"</em> para crearlo sin perder el ticket en curso.</div>
                </td>
            </tr>
            <tr>
                <td class="num"><span class="badge-step">6</span></td>
                <td class="content">
                    <div class="step-heading">Modalidad de Pago y Cobro:</div>
                    <div class="step-text">
                        <ul>
                            <li><strong>Contado:</strong> Efectivo, Tarjeta o Transferencia.</li>
                            <li><strong>Cobro con QR:</strong> Muestra el <strong>Código QR oficial de la empresa</strong> y el monto exacto para que el cliente lo escanee con su app bancaria. Permite subir el comprobante.</li>
                            <li><strong>Crédito:</strong> Define fecha de vencimiento y anticipo (valida el cupo de crédito del cliente).</li>
                        </ul>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="num"><span class="badge-step">7</span></td>
                <td class="content">
                    <div class="step-heading">Confirmación y Recibo:</div>
                    <div class="step-text">Haga clic en <strong>"Cobrar e Imprimir"</strong>. El inventario se descuenta mediante el método <strong>FIFO</strong> y se genera el recibo oficial en PDF.</div>
                </td>
            </tr>
        </table>

        <h2>Anulación de Ventas</h2>
        <p>Si ocurre un error o devolución, ingrese al detalle de la venta (<strong>Ventas &rarr; Ver</strong>) y presione <strong>"Anular Venta"</strong>. El sistema reintegra el stock a su lote original y revierte el cobro en caja.</p>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <!-- CAPÍTULO 5: CLIENTES Y CUENTAS POR COBRAR                             -->
    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <div class="no-break" style="margin-top: 15pt;">
        <h1>Capítulo 5: Clientes, Créditos y Cuentas por Cobrar</h1>
        <p>Permite administrar la base de datos de compradores y vigilar la cartera de créditos otorgados.</p>

        <h2>Registro de Clientes</h2>
        <p>En <strong>Ventas &rarr; Clientes</strong>, presione <strong>"Nuevo Cliente"</strong>:</p>
        <ul>
            <li><strong>Razón Social / Nombre:</strong> Nombre oficial para facturación o ticket.</li>
            <li><strong>NIT / Cédula (CI):</strong> Identificación tributaria o personal.</li>
            <li><strong>Límite de Crédito (Bs):</strong> Cupo máximo de endeudamiento permitido. Si es 0.00, solo podrá comprar al contado.</li>
        </ul>

        <h2>Gestión de Cuentas por Cobrar</h2>
        <p>En <strong>Ventas &rarr; Cuentas por cobrar</strong> se listan las deudas activas:</p>
        <table class="step-table">
            <tr>
                <td class="num"><span class="badge-step">1</span></td>
                <td class="content">
                    <div class="step-heading">Verificar Saldo y Vencimiento:</div>
                    <div class="step-text">El sistema clasifica las ventas en <em>Vigentes</em> o <em>Vencidas</em>, indicando el saldo pendiente.</div>
                </td>
            </tr>
            <tr>
                <td class="num"><span class="badge-step">2</span></td>
                <td class="content">
                    <div class="step-heading">Registrar Amortización o Pago:</div>
                    <div class="step-text">Haga clic en <strong>"Cobrar"</strong>, ingrese el monto recibido, método de pago y caja receptora. Se emitirá el comprobante de amortización y disminuirá la deuda del cliente.</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <!-- CAPÍTULO 6: INVENTARIO, PRODUCTOS Y FOTOS                             -->
    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <div class="page-break">
        <h1>Capítulo 6: Inventario, Productos, Fotos y Visor Ampliado</h1>
        <p>Este módulo concentra la información técnica, precios, presentaciones e imágenes de los productos comercializados.</p>

        <h2>Creación de Productos</h2>
        <table class="step-table">
            <tr>
                <td class="num"><span class="badge-step">1</span></td>
                <td class="content">
                    <div class="step-heading">Nuevo Producto:</div>
                    <div class="step-text">Vaya a <strong>Inventario &rarr; Productos</strong> y haga clic en <strong>"+ Nuevo Producto"</strong>.</div>
                </td>
            </tr>
            <tr>
                <td class="num"><span class="badge-step">2</span></td>
                <td class="content">
                    <div class="step-heading">Datos Principales:</div>
                    <div class="step-text">Indique Categoría, Código SKU (único), Nombre comercial y suba la fotografía (JPG, PNG o WebP).</div>
                </td>
            </tr>
            <tr>
                <td class="num"><span class="badge-step">3</span></td>
                <td class="content">
                    <div class="step-heading">Unidades de Medida y Factores:</div>
                    <div class="step-text">Configure la unidad base (ej. "Unidad" factor 1) y presentaciones adicionales (ej. "Caja x 12" factor 12 con su propio precio).</div>
                </td>
            </tr>
            <tr>
                <td class="num"><span class="badge-step">4</span></td>
                <td class="content">
                    <div class="step-heading">Guardar:</div>
                    <div class="step-text">Presione el botón <strong>"Guardar Cambios"</strong> al pie del formulario.</div>
                </td>
            </tr>
        </table>

        <h2>Uso del Visor Ampliado de Fotografías (Lightbox)</h2>
        <p>En todas las vistas del sistema (tabla de inventario, detalle de producto, edición y ventas):</p>
        <ul>
            <li>Haga clic sobre cualquier foto para abrir el <strong>visor modal ampliado en alta resolución</strong>.</li>
            <li>Para cerrar el visor, haga clic en el botón <strong>✕</strong>, haga clic sobre el fondo oscuro o presione la tecla <strong>Escape</strong> en su teclado.</li>
        </ul>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <!-- CAPÍTULO 7: MOVIMIENTOS DE STOCK Y COSTOS                             -->
    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <div class="no-break" style="margin-top: 15pt;">
        <h1>Capítulo 7: Movimientos de Stock y Cálculo de Costos</h1>
        <p>Permite registrar entradas directas, mermas, ajustes de inventario y transferencias entre almacenes.</p>

        <h2>Tipos de Movimiento</h2>
        <table class="grid">
            <thead>
                <tr>
                    <th style="width: 25%;">Tipo</th>
                    <th style="width: 50%;">Efecto en Almacén</th>
                    <th style="width: 25%;">Exige Costo</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Entrada</strong></td>
                    <td>Ingreso de mercadería al inventario. Aumenta stock disponible.</td>
                    <td><span class="tag tag-green">Sí (Costo Requerido)</span></td>
                </tr>
                <tr>
                    <td><strong>Salida</strong></td>
                    <td>Baja por rotura, daño o vencimiento. Reduce stock disponible.</td>
                    <td><span class="tag tag-blue">No (Usa Costo FIFO)</span></td>
                </tr>
                <tr>
                    <td><strong>Transferencia</strong></td>
                    <td>Envío entre dos almacenes de la empresa. Salida en origen y entrada en destino.</td>
                    <td><span class="tag tag-blue">No (Costo Heredado)</span></td>
                </tr>
                <tr>
                    <td><strong>Ajuste</strong></td>
                    <td>Corrección manual tras conteo físico de inventario.</td>
                    <td><span class="tag tag-green">Depende del signo</span></td>
                </tr>
            </tbody>
        </table>

        <h2>Cálculo Bidireccional Inteligente de Costos (Entradas)</h2>
        <div class="callout callout-info">
            <span class="callout-title">Asistente de Costos Automático</span>
            En las entradas de stock, usted puede ingresar cualquiera de los dos valores y el sistema completará el otro automáticamente:
            <ul>
                <li><strong>Si escribe el Costo Total (ej. 300 Bs por 10 cajas de 12 u.):</strong> El sistema divide entre el total de unidades base y calcula el <strong>Costo Unitario</strong> mostrando la fórmula matemática en pantalla.</li>
                <li><strong>Si escribe el Costo Unitario (ej. 2.50 Bs):</strong> El sistema multiplica por la cantidad total de unidades y calcula el <strong>Costo Total</strong>.</li>
                <li><strong>Almacenamiento:</strong> Siempre se persiste en base de datos el <strong>Costo Unitario</strong> para alimentar el método FIFO.</li>
            </ul>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <!-- CAPÍTULO 8: COMPRAS Y PROVEEDORES                                     -->
    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <div class="no-break" style="margin-top: 15pt;">
        <h1>Capítulo 8: Compras y Gestión de Proveedores</h1>
        <p>El registro de compras asegura el abastecimiento y alimenta los lotes de costo de cada artículo.</p>

        <table class="step-table">
            <tr>
                <td class="num"><span class="badge-step">1</span></td>
                <td class="content">
                    <div class="step-heading">Registrar Proveedor:</div>
                    <div class="step-text">En <strong>Compras &rarr; Proveedores</strong>, guarde la Razón Social, NIT y teléfono de sus distribuidores.</div>
                </td>
            </tr>
            <tr>
                <td class="num"><span class="badge-step">2</span></td>
                <td class="content">
                    <div class="step-heading">Crear Orden de Compra:</div>
                    <div class="step-text">En <strong>Compras &rarr; Compras &rarr; "+ Nueva Compra"</strong>, seleccione Proveedor, Sucursal y Almacén receptor.</div>
                </td>
            </tr>
            <tr>
                <td class="num"><span class="badge-step">3</span></td>
                <td class="content">
                    <div class="step-heading">Carga de Mercadería:</div>
                    <div class="step-text">Agregue los artículos comprados indicando cantidad y costo unitario pactado.</div>
                </td>
            </tr>
            <tr>
                <td class="num"><span class="badge-step">4</span></td>
                <td class="content">
                    <div class="step-heading">Pago y Recepción:</div>
                    <div class="step-text">Indique si se canceló de contado (sale de caja) o a crédito (cuenta por pagar). El inventario queda disponible de inmediato con su nuevo lote FIFO.</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <!-- CAPÍTULO 9: FINANZAS, CAJAS Y GASTOS                                  -->
    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <div class="page-break">
        <h1>Capítulo 9: Finanzas, Cajas, Arqueos y Control de Gastos</h1>
        <p>El control financiero previene descuadres y fugas de efectivo en los puntos de venta.</p>

        <h2>Ciclo Diario de Caja</h2>
        <table class="step-table">
            <tr>
                <td class="num"><span class="badge-step">1</span></td>
                <td class="content">
                    <div class="step-heading">Apertura de Caja (Inicio de Turno):</div>
                    <div class="step-text">En <strong>Configuración &rarr; Cajas</strong>, seleccione su caja y presione <strong>"Abrir Caja"</strong>. Declare el monto de cambio inicial en efectivo.</div>
                </td>
            </tr>
            <tr>
                <td class="num"><span class="badge-step">2</span></td>
                <td class="content">
                    <div class="step-heading">Movimiento Diario:</div>
                    <div class="step-text">Cada venta suma ingresos; los gastos operativos menores descuentan fondos de la caja en tiempo real.</div>
                </td>
            </tr>
            <tr>
                <td class="num"><span class="badge-step">3</span></td>
                <td class="content">
                    <div class="step-heading">Cierre y Arqueo (Fin de Turno):</div>
                    <div class="step-text">Al finalizar la jornada, presione <strong>"Cerrar Caja"</strong>. El sistema compara el saldo teórico con el conteo físico real e imprime el acta de arqueo.</div>
                </td>
            </tr>
        </table>

        <h2>Registro de Gastos Menores</h2>
        <p>En <strong>Gastos &rarr; Gastos</strong>, registre desembolsos operativos (fletes, limpieza, insumos). Cada gasto descuenta automáticamente de la caja activa de la sucursal.</p>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <!-- CAPÍTULO 10: PROFORMAS, PEDIDOS Y CONSIGNACIONES                      -->
    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <div class="no-break" style="margin-top: 15pt;">
        <h1>Capítulo 10: Cotizaciones (Proformas), Pedidos y Consignaciones</h1>

        <h2>Cotizaciones / Proformas</h2>
        <p>Permite enviar presupuestos formales antes de concretar una venta:</p>
        <ul>
            <li>En <strong>Ventas &rarr; Proformas</strong>, cree una proforma definiendo cliente, productos y fecha de validez.</li>
            <li>Exporte o imprima el documento oficial en PDF.</li>
            <li>Si el cliente aprueba el presupuesto, abra la proforma y haga clic en <strong>"Convertir en Venta"</strong>: los ítems se cargarán directamente en el POS sin reescribirlos.</li>
        </ul>

        <h2>Consignaciones de Mercadería</h2>
        <p>Para clientes o distribuidores externos que reciben mercadería en depósito:</p>
        <ul>
            <li>En <strong>Ventas &rarr; Consignaciones</strong>, registre los productos entregados en consignación (el stock queda apartado).</li>
            <li>Al recibir el reporte de ventas del cliente, presione <strong>"Liquidar Consignación"</strong>: se cobra lo vendido y el saldo no vendido retorna al stock disponible.</li>
        </ul>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <!-- CAPÍTULO 11: ADMINISTRACIÓN DE USUARIOS Y SEGURIDAD                   -->
    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <div class="no-break" style="margin-top: 15pt;">
        <h1>Capítulo 11: Administración de Usuarios y Políticas de Seguridad</h1>
        <p>Como Administrador, usted gestiona las cuentas de los empleados (Cajeros, Vendedores y otros Administradores).</p>

        <h2>Crear Nuevos Usuarios Operativos</h2>
        <table class="step-table">
            <tr>
                <td class="num"><span class="badge-step">1</span></td>
                <td class="content">
                    <div class="step-heading">Acceso al Listado:</div>
                    <div class="step-text">Vaya a <strong>Seguridad &rarr; Usuarios</strong> y haga clic en <strong>"+ Nuevo Usuario"</strong>.</div>
                </td>
            </tr>
            <tr>
                <td class="num"><span class="badge-step">2</span></td>
                <td class="content">
                    <div class="step-heading">Datos y Contraseña:</div>
                    <div class="step-text">Complete Nombre, Correo y Contraseña de acceso (mínimo 8 caracteres).</div>
                </td>
            </tr>
            <tr>
                <td class="num"><span class="badge-step">3</span></td>
                <td class="content">
                    <div class="step-heading">Asignación de Roles:</div>
                    <div class="step-text">Marque el rol correspondiente (ej. <em>Cajero</em>, <em>Vendedor</em> o <em>Administrador</em>).</div>
                </td>
            </tr>
        </table>

        <div class="callout callout-danger">
            <span class="callout-title">Políticas de Seguridad Blindadas (Por Diseño)</span>
            <ul>
                <li><strong>El Superadmin es invisible e inmodificable para el Administrador:</strong> No aparece en el listado de usuarios y cualquier intento de acceder a su perfil arrojará <strong>403 Prohibido</strong>.</li>
                <li><strong>No puede asignar el rol Superadmin:</strong> La opción está eliminada de los selectores para el Administrador.</li>
                <li><strong>Protección de Roles:</strong> El Administrador no puede borrar roles ni permisos del sistema.</li>
                <li><strong>Bitácora Exclusiva:</strong> La auditoría de actividades es de supervisión forense exclusiva del Superadmin.</li>
            </ul>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <!-- CAPÍTULO 12: CONFIGURACIÓN DE EMPRESA Y CÓDIGO QR                     -->
    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <div class="page-break">
        <h1>Capítulo 12: Configuración de Empresa, Sucursales y Código QR</h1>
        <p>Los datos cargados en este módulo se imprimen en los comprobantes y se presentan en las pantallas de cobro.</p>

        <table class="step-table">
            <tr>
                <td class="num"><span class="badge-step">1</span></td>
                <td class="content">
                    <div class="step-heading">Editar Datos de Empresa:</div>
                    <div class="step-text">En <strong>Configuración &rarr; Empresa</strong>, haga clic en <strong>"Editar"</strong>.</div>
                </td>
            </tr>
            <tr>
                <td class="num"><span class="badge-step">2</span></td>
                <td class="content">
                    <div class="step-heading">Datos Comerciales y Fiscales:</div>
                    <div class="step-text">Actualice Razón Social, NIT, Teléfono de contacto, Dirección y Ciudad.</div>
                </td>
            </tr>
            <tr>
                <td class="num"><span class="badge-step">3</span></td>
                <td class="content">
                    <div class="step-heading">Logotipo Institucional:</div>
                    <div class="step-text">Suba el logo oficial en formato PNG o JPG con fondo transparente.</div>
                </td>
            </tr>
            <tr>
                <td class="num"><span class="badge-step">4</span></td>
                <td class="content">
                    <div class="step-heading">Carga del Código QR de Cobro Bancario:</div>
                    <div class="step-text">
                        Suba la imagen nítida del Código QR de su cuenta empresarial (Simple QR / Pago QR).
                        <div class="callout callout-info" style="margin-top: 3pt;">
                            <strong>Efecto en el POS:</strong> Al elegir cobro con QR en el Punto de Venta, este código QR se proyectará en la pantalla para que el cliente lo escanee con su banca móvil.
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="num"><span class="badge-step">5</span></td>
                <td class="content">
                    <div class="step-heading">Guardar Cambios:</div>
                    <div class="step-text">Presione el botón <strong>"Guardar Cambios"</strong> para activar la nueva información en todo el sistema.</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <!-- CAPÍTULO 13: PREGUNTAS FRECUENTES Y SOLUCIÓN DE PROBLEMAS             -->
    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <div class="no-break" style="margin-top: 15pt;">
        <h1>Capítulo 13: Preguntas Frecuentes y Solución de Problemas</h1>

        <h3>1. ¿Por qué el sistema no permite cobrar y dice "Caja Cerrada"?</h3>
        <p>Toda venta requiere registrar el dinero en una caja activa. Vaya a <em>Configuración &rarr; Cajas</em> y aperture la caja con el saldo inicial del día.</p>

        <h3>2. ¿Por qué un producto aparece en gris y no se puede agregar en el POS?</h3>
        <p>El producto no tiene stock disponible en el almacén seleccionado. Para habilitarlo, ingrese mercadería mediante una <strong>Entrada de Inventario</strong> o <strong>Compra</strong>.</p>

        <h3>3. ¿Cómo cierro la foto ampliada del producto en el POS o inventario?</h3>
        <p>Haga clic en la `✕` superior, haga clic en el fondo oscuro translúcido o presione la tecla <strong>Escape</strong> en su teclado.</p>

        <h3>4. ¿Por qué no puedo eliminar un Rol o Permiso?</h3>
        <p>Por políticas de seguridad, los roles y permisos del sistema están blindados para el Administrador y solo pueden ser modificados por el Superadmin.</p>

        <div class="callout callout-info" style="margin-top: 15pt; text-align: center;">
            <strong>&copy; {{ date('Y') }} {{ $company->name ?? 'Sistema de Gestión Comercial' }}. Todos los derechos reservados.</strong><br>
            Manual oficial elaborado para la correcta operación y control administrativo de la empresa.
        </div>
    </div>

</body>
</html>
