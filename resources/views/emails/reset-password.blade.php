@extends('emails.layout')

@section('content')
    <h1>Elige una contraseña nueva</h1>
    <p>Hola {{ $user->name }},</p>
    <p>Recibimos un pedido para restablecer la contraseña de tu cuenta en NOVA. Haz clic en el botón para elegir una nueva.</p>
    <p><a href="{{ $url }}" class="btn">Restablecer contraseña</a></p>
    <p class="muted">Si el botón no funciona, copia y pega este enlace en tu navegador:</p>
    <p class="muted" style="word-break: break-all;"><a href="{{ $url }}">{{ $url }}</a></p>
    <p class="muted">El enlace caduca en 60 minutos. Si expiró, vuelve a pedir uno desde el inicio de sesión.</p>
    <p class="muted">Si no pediste este cambio, puedes ignorar este mensaje.</p>
@endsection
