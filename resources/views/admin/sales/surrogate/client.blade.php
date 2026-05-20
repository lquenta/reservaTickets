@extends($flow->layout ?? 'layouts.admin')

@section($flow->contentSection ?? 'admin')
@include('admin.sales._client-form', [
    'event' => $event,
    'flow' => $flow,
    'lookupUrl' => $flow->route('events.surrogate-sale.lookup', $event),
    'submitRoute' => $flow->route('events.surrogate-sale.start', $event),
    'title' => 'Venta surrogada',
    'submitLabel' => 'Continuar — elegir butacas',
])
@endsection
