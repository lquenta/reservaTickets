@extends('layouts.admin')

@section('title', 'Invitado de honor - '.$event->name)

@section('admin')
@include('admin.sales._client-form', [
    'event' => $event,
    'lookupUrl' => route('admin.events.honored-guest.lookup', $event),
    'submitRoute' => route('admin.events.honored-guest.start', $event),
    'title' => 'Invitado de honor',
    'submitLabel' => 'Continuar — elegir butacas',
])
@endsection
