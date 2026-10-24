<div class="prose dark:prose-invert max-w-none text-gray-700 dark:text-gray-300 space-y-6 text-sm leading-relaxed">

    {{-- Encabezado del Contrato --}}
    <div class="p-4 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700">
        <h3 class="text-base font-bold text-slate-900 dark:text-white uppercase tracking-wide mb-1">
            CONTRATO DE LICENCIA DE SOFTWARE Y PRESTACIÓN DE SERVICIOS POR SUSCRIPCIÓN (SaaS)
        </h3>
        <p class="text-xs text-slate-600 dark:text-slate-400">
            Versión 1.0 · Aplicable a {{ $company->name ?? 'la Empresa' }} y sus administradores autorizados.
        </p>
    </div>

    {{-- Cláusula 1 --}}
    <div>
        <h4 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-1">
            CLÁUSULA PRIMERA: OBJETO Y CONCESIÓN DE LICENCIA
        </h4>
        <p class="text-gray-600 dark:text-gray-300">
            El presente contrato regula el acceso y uso operativo del software de gestión empresarial y punto de venta (en adelante, el <strong>"SISTEMA"</strong>), otorgado bajo la modalidad de suscripción periódica como servicio en la nube (SaaS - Software as a Service). Se concede al cliente y a los administradores autorizados una licencia temporal, intransferible y no exclusiva mientras se mantenga activa y al día la suscripción contratada.
        </p>
    </div>

    {{-- Cláusula 2 --}}
    <div>
        <h4 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-1">
            CLÁUSULA SEGUNDA: PROPIEDAD INTELECTUAL Y DERECHOS DE AUTOR
        </h4>
        <p class="text-gray-600 dark:text-gray-300">
            El SISTEMA, su arquitectura tecnológica, código fuente, algoritmos, bases de datos base, diseño gráfico y documentación asociada son propiedad intelectual exclusiva de la empresa proveedora. En ningún caso la concesión de licencia por suscripción implicará la transferencia, venta o cesión de la propiedad del código fuente ni del núcleo de la plataforma al cliente.
        </p>
    </div>

    {{-- Cláusula 3: REGLA ESENCIAL SOLICITADA POR EL USUARIO --}}
    <div class="not-prose p-5 rounded-xl bg-amber-50/90 dark:bg-slate-800/90 border-2 border-amber-300 dark:border-amber-500/70 shadow-sm">
        <div class="flex items-start gap-3.5">
            <svg class="w-6 h-6 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <div class="space-y-2">
                <h4 class="text-sm font-bold text-amber-800 dark:text-amber-400 uppercase tracking-wider">
                    CLÁUSULA TERCERA: ENTREGA DE LICENCIA Y CONDICIONES SOBRE MODIFICACIONES DE CÓDIGO
                </h4>
                <p class="text-xs sm:text-sm text-slate-800 dark:text-slate-100 leading-relaxed">
                    <strong class="text-amber-900 dark:text-amber-300 font-bold">1. Cierre de Alcance y Prohibición de Modificaciones Gratuitas:</strong> Una vez entregadas las credenciales de acceso y habilitado el permiso de licencia de uso del SISTEMA bajo el plan de suscripción, el cliente, la administración y sus usuarios dependientes <strong class="text-amber-900 dark:text-amber-300 font-bold">NO PODRÁN EXIGIR MODIFICACIONES, ADICIONES NI CAMBIOS EN EL CÓDIGO FUENTE</strong>, ni alteraciones en la lógica o flujos preestablecidos del sistema bajo el argumento de la suscripción base.
                </p>
                <p class="text-xs sm:text-sm text-slate-800 dark:text-slate-100 leading-relaxed">
                    <strong class="text-amber-900 dark:text-amber-300 font-bold">2. Costos Adicionales por Desarrollos Especiales:</strong> En caso de que el cliente requiera cambios estructurales, módulos personalizados, integraciones específicas con terceros o adaptaciones particulares no incluidas en la versión entregada, dichos requerimientos deberán formularse formalmente como solicitudes de desarrollo especial. Los mismos estarán sujetos a evaluación de factibilidad técnica previa y <strong class="text-amber-900 dark:text-amber-300 font-bold">TENDRÁN UN COSTO EXTRAORDINARIO E INDEPENDIENTE</strong>, el cual deberá ser presupuestado, aprobado y cancelado con anterioridad a su desarrollo e implantación.
                </p>
            </div>
        </div>
    </div>

    {{-- Cláusula 4 --}}
    <div>
        <h4 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-1">
            CLÁUSULA CUARTA: SUSCRIPCIÓN, FACTURACIÓN Y CONTINUIDAD DEL SERVICIO
        </h4>
        <p class="text-gray-600 dark:text-gray-300">
            El servicio se brinda por períodos prepagados según el plan contratado. La falta de pago oportuno de la cuota de renovación facultará al proveedor a suspender preventivamente el acceso a los módulos operativos hasta la regularización de la cuenta, sin perjuicio de la preservación de los datos almacenados por el período de gracia convenido.
        </p>
    </div>

    {{-- Cláusula 5 --}}
    <div>
        <h4 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-1">
            CLÁUSULA QUINTA: RESPONSABILIDAD DE LOS DATOS Y GESTIÓN DE ACCESOS
        </h4>
        <p class="text-gray-600 dark:text-gray-300">
            El cliente y su administración son los únicos y exclusivos responsables de la exactitud, veracidad y licitud de la información ingresada al SISTEMA (inventarios, precios, costos, transacciones de venta, compras y datos de clientes), así como del debido resguardo y confidencialidad de los nombres de usuario y contraseñas asignados al personal dependiente.
        </p>
    </div>

    {{-- Cláusula 6 --}}
    <div>
        <h4 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-1">
            CLÁUSULA SEXTA: DISPONIBILIDAD, MANTENIMIENTO Y SOPORTE
        </h4>
        <p class="text-gray-600 dark:text-gray-300">
            El proveedor se compromete a realizar sus mejores esfuerzos para garantizar una alta disponibilidad del servicio (mínimo 99.5% de disponibilidad mensual excluyendo mantenimientos programados informados con anticipación). El soporte técnico estándar cubre la resolución de incidencias o errores operativos de la plataforma, mas no la capacitación permanente ni la asistencia en sitio, salvo acuerdo en contrario.
        </p>
    </div>

    {{-- Cláusula 7 --}}
    <div>
        <h4 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-1">
            CLÁUSULA SÉPTIMA: VALIDEZ DE LA ACEPTACIÓN ELECTRÓNICA
        </h4>
        <p class="text-gray-600 dark:text-gray-300">
            La aceptación del presente documento mediante pulsación del botón electrónico de confirmación tiene plena validez jurídica vinculante para el cliente y la persona designada como administradora, registrándose la marca de tiempo (timestamp), la dirección IP y el identificador de usuario en la bitácora del sistema como constancia probatoria fehaciente.
        </p>
    </div>

</div>
