@extends('emails.layout')

@section('content')
    <h1>Verifica tu correo electrónico</h1>
    <p>Hola {{ $user->name }},</p>
    <p>Gracias por registrarte en NOVA. Haz clic en el botón siguiente para confirmar tu dirección de correo.</p>
    <p><a href="{{ $url }}" class="btn">Verificar correo</a></p>
    <p class="muted">Si el botón no funciona, copia y pega este enlace en tu navegador:</p>
    <p class="muted" style="word-break: break-all;"><a href="{{ $url }}">{{ $url }}</a></p>
    <p class="muted">El enlace caduca en 60 minutos. Si expiró, inicia sesión en <a href="{{ $loginUrl }}">NOVA</a> y pulsa «Reenviar enlace de verificación».</p>
    <p class="muted">Si no creaste una cuenta, puedes ignorar este mensaje.</p>
@endsection
