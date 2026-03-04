@extends('layouts.app')

@section('title', 'Términos y Condiciones')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="rounded-2xl border border-red-900/50 bg-black/60 backdrop-blur p-8 md:p-10">
        <h1 class="font-display text-3xl font-bold text-[#e50914] tracking-widest mb-2">TÉRMINOS Y CONDICIONES</h1>
        <p class="text-white/60 text-sm mb-8">Última actualización: {{ now()->translatedFormat('d \d\e F \d\e Y') }}</p>

        <div class="prose prose-invert prose-red max-w-none space-y-8 text-white/90 text-sm leading-relaxed">
            <section>
                <h2 class="text-lg font-semibold text-[#e50914] mb-2">1. Objeto y aceptación</h2>
                <p>Los presentes Términos y Condiciones regulan el uso del sitio web de reserva de entradas (en adelante, el «Sitio») operado por Nova. Al registrar una cuenta, realizar una reserva o utilizar el Sitio, usted acepta íntegramente estos términos. Si no está de acuerdo, no utilice el servicio.</p>
            </section>

            <section>
                <h2 class="text-lg font-semibold text-[#e50914] mb-2">2. Reserva de entradas</h2>
                <ul class="list-disc list-inside space-y-1 ml-2">
                    <li>La reserva de entradas a través del Sitio no constituye venta definitiva hasta que el comprobante de pago sea verificado y la reserva sea autorizada por el organizador.</li>
                    <li>Las reservas están sujetas a disponibilidad y pueden tener un tiempo límite para completar el pago; transcurrido dicho plazo, la reserva puede ser cancelada automáticamente.</li>
                    <li>Es responsabilidad del usuario proporcionar datos correctos (nombre, correo, butacas elegidas, etc.). Errores en los datos no obligan al organizador a modificar o reemitir entradas.</li>
                </ul>
            </section>

            <section>
                <h2 class="text-lg font-semibold text-[#e50914] mb-2">3. Deslindamiento de responsabilidad</h2>
                <p>Nova actúa como plataforma de reserva. En la medida permitida por la ley aplicable:</p>
                <ul class="list-disc list-inside space-y-1 ml-2 mt-2">
                    <li><strong>Eventos:</strong> No nos hacemos responsables por la realización, cancelación, posposición o modificación de los eventos, ni por daños o perjuicios derivados de los mismos. La relación contractual respecto al evento es entre el usuario y el organizador del evento.</li>
                    <li><strong>Pagos:</strong> No somos responsables por retrasos, rechazos o errores en los medios de pago externos (transferencias, pasarelas, etc.) ni por el uso que el organizador haga de los fondos.</li>
                    <li><strong>Uso del Sitio:</strong> El uso del Sitio es bajo su propio riesgo. No garantizamos disponibilidad ininterrumpida ni ausencia de errores técnicos.</li>
                </ul>
            </section>

            <section>
                <h2 class="text-lg font-semibold text-[#e50914] mb-2">4. Protección de datos y ciberseguridad</h2>
                <p>Sus datos personales se tratan conforme a nuestra política de privacidad y a la normativa aplicable (incl. RGPD/LOPDGDD donde corresponda). En particular:</p>
                <ul class="list-disc list-inside space-y-1 ml-2 mt-2">
                    <li>Recabamos solo los datos necesarios para la reserva, emisión de entradas y comunicación (nombre, email, datos de la reserva, comprobante de pago).</li>
                    <li>Implementamos medidas técnicas y organizativas razonables para proteger la confidencialidad e integridad de los datos (acceso restringido, cifrado, controles de seguridad).</li>
                    <li>No vendemos sus datos personales a terceros. Los datos pueden ser compartidos con el organizador del evento en la medida necesaria para la gestión de la reserva y del evento.</li>
                    <li>Usted es responsable de mantener la confidencialidad de su contraseña y de toda actividad en su cuenta.</li>
                </ul>
            </section>

            <section>
                <h2 class="text-lg font-semibold text-[#e50914] mb-2">5. Uso aceptable del Sitio</h2>
                <p>Queda prohibido utilizar el Sitio para:</p>
                <ul class="list-disc list-inside space-y-1 ml-2 mt-2">
                    <li>Actividades ilegales, fraudulentas o que vulneren derechos de terceros.</li>
                    <li>Intentar acceder no autorizadamente a sistemas, datos o cuentas de otros usuarios.</li>
                    <li>Introducir malware, realizar ataques de denegación de servicio o manipular el funcionamiento del Sitio.</li>
                    <li>Revender o usar las entradas con fines comerciales no autorizados por el organizador.</li>
                </ul>
                <p class="mt-2">El incumplimiento puede dar lugar a la cancelación de reservas, bloqueo de la cuenta y, en su caso, a las acciones legales que correspondan.</p>
            </section>

            <section>
                <h2 class="text-lg font-semibold text-[#e50914] mb-2">6. Propiedad intelectual</h2>
                <p>El Sitio, su diseño, marcas, logos y contenidos son propiedad de Nova o de sus licenciantes. No está permitida la reproducción o uso no autorizado sin consentimiento previo por escrito.</p>
            </section>

            <section>
                <h2 class="text-lg font-semibold text-[#e50914] mb-2">7. Modificaciones</h2>
                <p>Nos reservamos el derecho de modificar estos Términos y Condiciones. Los cambios serán efectivos desde su publicación en el Sitio. El uso continuado del servicio tras la publicación implica la aceptación de las nuevas condiciones. Para reservas ya realizadas, se aplicarán los términos vigentes en el momento de la reserva en lo que respecta a esa transacción.</p>
            </section>

            <section>
                <h2 class="text-lg font-semibold text-[#e50914] mb-2">8. Ley aplicable y resolución de conflictos</h2>
                <p>Estos términos se rigen por la ley boliviana (o la que corresponda al domicilio del titular del Sitio). Para cualquier controversia, las partes se someterán a los juzgos y tribunales competentes según la ley aplicable.</p>
            </section>

            <section>
                <h2 class="text-lg font-semibold text-[#e50914] mb-2">9. Contacto</h2>
                <p>Para consultas sobre estos Términos y Condiciones o sobre el tratamiento de sus datos, puede contactarnos a través del formulario de contacto disponible en el Sitio o en la sección «Contacto».</p>
            </section>
        </div>

        <div class="mt-10 pt-6 border-t border-red-900/50">
            <a href="{{ url()->previous() }}" class="inline-flex items-center gap-2 text-[#e50914] hover:text-red-400 transition font-medium">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Volver
            </a>
        </div>
    </div>
</div>
@endsection
