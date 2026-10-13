@extends('layouts.app')

@section('title', 'Política de privacidad')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="rounded-2xl border border-purple-900/50 bg-black/60 backdrop-blur p-8 md:p-10">
        <h1 class="font-display text-3xl font-bold text-[#ff2daa] tracking-widest mb-2">POLÍTICA DE PRIVACIDAD</h1>
        <p class="text-white/80 mb-2">NOVA — reserva de entradas</p>
        <p class="text-white/60 text-sm mb-8">Última actualización: 13 de octubre de 2026</p>

        <div class="max-w-none space-y-8 text-white/90 text-sm leading-relaxed">
            <section>
                <h2 class="text-lg font-semibold text-[#ff2daa] mb-2">1. Quién es responsable</h2>
                <p>Esta política describe cómo NOVA, la plataforma de reserva de entradas de este sitio, accede, usa, guarda y comparte los datos de las personas que reservan tickets o entran con Google. Aplica solo a esta aplicación y a esta organización. No es un modelo genérico.</p>
                <p class="mt-2">Para consultas o para pedir la eliminación de tu cuenta puedes escribir por WhatsApp al +591 64066996 desde la sección <a href="{{ route('home') }}#contacto" class="text-[#ff2daa] hover:text-[#39ff14] underline underline-offset-2">Contacto</a>.</p>
            </section>

            <section>
                <h2 class="text-lg font-semibold text-[#ff2daa] mb-2">2. Datos de Google a los que accedemos</h2>
                <p>Si eliges «Continuar con Google», NOVA pide a Google únicamente lo necesario para identificarte:</p>
                <ul class="list-disc list-inside space-y-1 ml-2 mt-2">
                    <li>El identificador de tu cuenta de Google.</li>
                    <li>Tu nombre de perfil.</li>
                    <li>Tu correo electrónico.</li>
                    <li>La confirmación de que Google ya verificó ese correo.</li>
                </ul>
                <p class="mt-2">No pedimos acceso a Gmail, Drive, Calendar, contactos, fotos ni a ningún otro dato de tu cuenta de Google. No guardamos tu contraseña de Google. No conservamos el token de acceso ni el token de actualización de Google después de iniciar la sesión.</p>
                <p class="mt-2">En el inicio de sesión, el registro y algunas reservas, el sitio puede mostrar Google reCAPTCHA. En ese caso Google recibe la respuesta del verificador y la dirección IP de la solicitud para distinguir personas de intentos automáticos. NOVA no guarda ese resultado. El tratamiento que hace Google se rige por la política de Google.</p>
            </section>

            <section>
                <h2 class="text-lg font-semibold text-[#ff2daa] mb-2">3. Para qué usamos los datos de Google</h2>
                <ul class="list-disc list-inside space-y-1 ml-2">
                    <li>Crear tu cuenta de NOVA o abrir la cuenta que ya existe con ese mismo correo.</li>
                    <li>Dejarte entrar sin pedirte otra contraseña.</li>
                    <li>Mostrar tu nombre en la reserva y en las entradas.</li>
                    <li>Enviarte al correo de esa cuenta los mensajes de la reserva y las entradas.</li>
                    <li>Evitar cuentas duplicadas, asociando el identificador de Google a un solo usuario.</li>
                </ul>
                <p class="mt-2">No usamos los datos de Google para publicidad, para elaborar perfiles comerciales ni para venderlos. No los transferimos a redes publicitarias, intermediarios de datos ni agencias de crédito.</p>
            </section>

            <section>
                <h2 class="text-lg font-semibold text-[#ff2daa] mb-2">4. Cómo los guardamos</h2>
                <p>En la base de datos de NOVA quedan el nombre, el correo, el identificador de Google y la fecha en que el correo quedó verificado. La cuenta se crea con el rol de usuario. La sesión del navegador recuerda que entraste.</p>
                <p class="mt-2">Esos datos se conservan mientras la cuenta exista y mientras hagan falta para las reservas, las entradas y las obligaciones que correspondan. Puedes pedir que eliminemos la cuenta y el identificador de Google escribiendo al WhatsApp de contacto. Las reservas ya emitidas pueden conservarse el tiempo necesario para el evento y para acreditar la operación.</p>
            </section>

            <section>
                <h2 class="text-lg font-semibold text-[#ff2daa] mb-2">5. Con quién los compartimos</h2>
                <ul class="list-disc list-inside space-y-1 ml-2">
                    <li>Con Google, solo en el momento en que autorizas el ingreso o cuando reCAPTCHA comprueba la solicitud. Google ya administra esa cuenta.</li>
                    <li>Con el personal de NOVA y del evento, el nombre y el correo que hacen falta para entregar la entrada. No les entregamos el identificador de Google como un dato aparte.</li>
                    <li>Con el proveedor que aloja el sitio y con el servicio de correo que envía las entradas, únicamente para prestar ese servicio.</li>
                    <li>Con una autoridad, si una norma vigente nos obliga a entregarlos.</li>
                </ul>
                <p class="mt-2">No vendemos datos de usuarios de Google.</p>
            </section>

            <section>
                <h2 class="text-lg font-semibold text-[#ff2daa] mb-2">6. Otros datos de la reserva</h2>
                <p>Si te registras con el formulario, además pedimos cédula, teléfono y una contraseña. En la compra guardamos las butacas, el estado de la reserva y el comprobante de pago que subes. Esos datos no provienen de Google. Sirven para identificar la reserva, validar el pago y emitir la entrada. El comprobante lo revisa el personal de NOVA.</p>
            </section>

            <section>
                <h2 class="text-lg font-semibold text-[#ff2daa] mb-2">7. Cambios en el uso de datos de Google</h2>
                <p>Si NOVA cambia la forma en que accede, usa, guarda o comparte datos de usuarios de Google, actualizaremos esta página, cambiaremos la fecha de «Última actualización» y publicaremos el aviso en el sitio antes de aplicar el cambio. Si ya tienes cuenta, también te lo enviaremos al correo registrado. El uso de datos de Google seguirá limitado a lo que esta política describa en ese momento.</p>
            </section>

            <section>
                <h2 class="text-lg font-semibold text-[#ff2daa] mb-2">8. Tus opciones</h2>
                <p>Puedes entrar con correo y contraseña en lugar de Google. Puedes pedir acceso, corrección o eliminación de tus datos por el WhatsApp de la sección Contacto. Revocar el acceso de NOVA en tu cuenta de Google no borra sola la cuenta ya creada aquí; para eso hay que pedirnos la eliminación.</p>
            </section>
        </div>

        <div class="mt-10 pt-6 border-t border-purple-900/50">
            <a href="{{ url()->previous() }}" class="inline-flex items-center gap-2 text-[#ff2daa] hover:text-[#39ff14] transition font-medium">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Volver
            </a>
        </div>
    </div>
</div>
@endsection
