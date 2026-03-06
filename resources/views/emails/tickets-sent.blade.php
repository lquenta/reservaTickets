<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; line-height: 1.6; color: #1f2937; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        h1 { color: #6d28d9; }
    </style>
</head>
<body>
    <div class="container">
        <h1>¡Tus tickets han sido confirmados!</h1>
        <p>Hola {{ $reservation->user->name }},</p>
        <p>Tu reserva para <strong>{{ $reservation->event->name }}</strong> ha sido autorizada.</p>
        <p><strong>Fecha:</strong> {{ $reservation->event->starts_at->translatedFormat('l d F Y, H:i') }}</p>
        <p><strong>Lugar:</strong> {{ $reservation->event->venue }}</p>
        <p>En el PDF adjunto encontrarás tus tickets con el QR para presentar en la entrada.</p>
        <p>NOVA — Tickets de entrada 2026</p>
    </div>
</body>
</html>
