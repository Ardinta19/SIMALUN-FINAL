@extends('catalog.layout')
@section('title', 'Pesanan saya')
@section('content')<h1>Pesanan sewa</h1><div class="items">@forelse($orders as $order)<article class="card body"><h2>Pesanan {{ $order->reference }}</h2><p>{{ $order->units_count }} unit · {{ $order->created_at->format('d/m/Y H:i') }}</p><a class="btn secondary" href="{{ route('rental.orders.show', $order) }}">Lihat detail</a></article>@empty<p>Belum ada pesanan sewa.</p>@endforelse</div><div class="pagination">{{ $orders->links() }}</div>@endsection
