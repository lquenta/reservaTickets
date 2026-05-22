@extends('emails.layout')

@section('content')
    <h1>¡Tus tickets han sido confirmados!</h1>
    <p>Hola {{ $reservation->user->name }},</p>
    <p>Tu reserva para <strong>{{ $reservation->event->name }}</strong> ha sido autorizada.</p>
    <p><strong>Fecha:</strong> {{ $reservation->event->starts_at->translatedFormat('l d F Y, H:i') }}</p>
    <p><strong>Lugar:</strong> {{ $reservation->event->venue }}</p>
    <p>En el PDF adjunto encontrarás tus tickets con el QR para presentar en la entrada.</p>
@endsection
