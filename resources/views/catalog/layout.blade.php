<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>@yield('title', 'Katalog') · Berkah Kreatif</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
:root{--wine:#85092d;--dark:#54051f;--rose:#b63050;--paper:#fffaf8;--line:#f0dfe1;--ink:#301722}*{box-sizing:border-box}body{margin:0;background:#faf4f3;color:var(--ink);font-family:'Plus Jakarta Sans',sans-serif}a{color:var(--wine)}.hero{background:linear-gradient(145deg,var(--dark),var(--wine) 60%,#b6435e);color:white}.top{flex-wrap:wrap;max-width:1200px;margin:auto;padding:18px 24px;display:flex;align-items:center;gap:14px}.logo{width:52px;height:52px;object-fit:contain;border-radius:14px;background:white}.brand{font-weight:800;color:white;text-decoration:none;font-size:1.1rem}.tag{font-size:.7rem;opacity:.8;letter-spacing:.12em;text-transform:uppercase}.nav{margin-left:auto;display:flex;align-items:center;gap:16px;flex-wrap:wrap}.nav a{color:white;font-size:.85rem;font-weight:700;text-decoration:none}.intro{max-width:1200px;margin:auto;padding:36px 24px 54px}.intro h1{font-size:clamp(1.8rem,4vw,3rem);margin:0 0 10px}.intro p{max-width:620px;line-height:1.6;margin:0;color:#ffebef}.wrap{max-width:1200px;margin:auto;padding:24px}.card{background:white;border:1px solid var(--line);border-radius:20px;box-shadow:0 5px 20px #54051f0a;overflow:hidden}.grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:18px}.photo{width:100%;aspect-ratio:4/3;object-fit:cover;background:#f6e5e8}.placeholder{display:grid;place-items:center;color:#a46272;font-size:2rem}.body{padding:18px}.body h2,.body h3{margin:0 0 8px;font-size:1rem}.muted{color:#806975;font-size:.86rem;line-height:1.5}.price{font-weight:800;color:var(--wine)}.btn{display:inline-flex;justify-content:center;align-items:center;background:var(--wine);color:white;border:0;border-radius:12px;padding:12px 18px;font:inherit;font-weight:800;text-decoration:none;cursor:pointer}.btn.secondary{background:#f8e9ed;color:var(--wine)}.filters{display:flex;gap:10px;margin-bottom:22px;flex-wrap:wrap}input,select,textarea{font:inherit;color:var(--ink);border:1px solid #e8d6dc;border-radius:12px;padding:12px;background:white;max-width:100%}.filters input{flex:1;min-width:180px}label{display:block;font-size:.8rem;font-weight:800;margin:14px 0 6px}form.stack input,form.stack select,form.stack textarea{width:100%}.stack textarea{min-height:100px}.notice{border-radius:14px;padding:16px;background:#fff3e8;color:#6c4425;margin:18px 0}.row{display:flex;gap:12px;flex-wrap:wrap}.row>*{flex:1;min-width:150px}.details{display:grid;grid-template-columns:1fr 1fr;gap:24px}.pagination{margin:24px 0;overflow:auto}.admin-grid{display:grid;grid-template-columns:1fr 2fr;gap:20px}.comparison-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.unit-section{margin:12px 0;background:#fffcfb;border:1px solid var(--line);padding:16px;border-radius:16px}.unit-section>summary,.order-unit>summary{cursor:pointer;font-weight:800;overflow-wrap:anywhere}.unit-section>summary{color:var(--wine)}.unit-section[open]>summary{margin-bottom:14px}.card p,.card h2,.card h3{overflow-wrap:anywhere}.admin-grid .card{padding:20px}.items{display:grid;gap:14px}.items details{border:1px solid var(--line);padding:16px;border-radius:16px}.items summary{cursor:pointer;font-weight:800}footer{padding:30px;text-align:center;color:#806975;font-size:.8rem}@media(max-width:900px){.grid{grid-template-columns:repeat(3,minmax(0,1fr))}}@media(max-width:650px){.grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.wrap{padding:16px}.body{padding:12px}.top{padding:14px 16px}.nav{gap:10px}.nav a{font-size:.75rem}.intro{padding:28px 16px 36px}.details,.admin-grid,.comparison-grid{grid-template-columns:1fr}}@media(max-width:350px){.grid{grid-template-columns:1fr}.tag{display:none}}@media(prefers-reduced-motion:reduce){*,*::before,*::after{animation:none!important;scroll-behavior:auto!important}}

[data-open-dialog][hidden]{display:none!important}
/* Tipografi dan jarak untuk keterbacaan, tanpa mengubah pola kartu lama. */
body{font-family:'Plus Jakarta Sans',system-ui,-apple-system,'Segoe UI',sans-serif;line-height:1.6}
h1,h2,h3,h4{line-height:1.3;letter-spacing:-.02em}
.wrap h1{font-size:clamp(1.7rem,3.4vw,2.35rem);margin:0 0 16px}
.wrap h2{font-size:clamp(1.2rem,2.4vw,1.5rem);margin:1.5em 0 .65em}
.body h2{font-size:1.12rem;margin:0 0 .65em}
.body h3{font-size:1rem;margin:1.5em 0 .6em}
.body h4{font-size:.95rem;margin:1.3em 0 .5em}
.body p{margin:.65em 0;line-height:1.65}
.muted{color:#6f5a63;font-size:.9rem;line-height:1.6}
label{font-size:.875rem;font-weight:700;line-height:1.45;margin:16px 0 8px}
input,select,textarea{font-family:inherit;font-size:1rem;font-weight:400;line-height:1.5}
input:not([type=checkbox]):not([type=radio]),select{min-height:48px}
input[type=checkbox],input[type=radio]{accent-color:var(--wine)}
.btn{min-height:48px;line-height:1.35;font-size:.95rem;font-weight:700;text-align:center}
a:focus-visible,button:focus-visible,input:focus-visible,select:focus-visible,textarea:focus-visible,summary:focus-visible{outline:3px solid #bc5b75;outline-offset:3px}
.notice{line-height:1.65}.notice p:last-child{margin-bottom:0}
.cart-page{max-width:900px;margin-inline:auto}
.cart-item{display:grid;grid-template-columns:112px minmax(0,1fr);gap:18px;align-items:start}
.cart-image{width:112px;aspect-ratio:1;object-fit:cover;border-radius:14px;background:#f8e9ed}
.cart-item .cart-placeholder{display:grid;place-items:center;color:#a46272;font-size:2rem}
.cart-actions{display:flex;gap:12px;align-items:center;flex-wrap:wrap}
.cart-item .cart-number{font-size:.78rem;font-weight:700;color:#765f69;margin:0 0 4px}
.cart-item form{margin-top:12px}.cart-unit-actions{display:flex;flex-wrap:wrap;gap:8px}.cart-unit-actions .btn{min-height:44px}
.quantity-picker{display:flex;align-items:center;gap:8px}.quantity-picker input{width:84px;text-align:center}.quantity-picker button{min-width:48px;min-height:48px;border:1px solid #e8d6dc;border-radius:12px;background:#f8e9ed;color:#85092d;font-size:1.2rem;font-weight:800;cursor:pointer}.checkout-form{max-width:820px;margin-inline:auto}
.checkout-form [data-checkout-unit]{padding:clamp(18px,3vw,28px)}
.checkout-form [data-checkout-unit] h3{padding-top:18px;border-top:1px solid var(--line)}
.checkout-terms ul{padding-left:1.3em}.checkout-terms li{margin:.7em 0;line-height:1.65}
@media(max-width:420px){.cart-item{grid-template-columns:76px minmax(0,1fr);gap:12px}.cart-image{width:76px}.cart-actions .btn{width:100%}}
@media(max-width:650px){.top .nav{width:100%;margin-left:0;gap:6px 16px}.nav a{font-size:.85rem;padding:8px 0}.wrap h1{margin-top:4px}.row>*{min-width:min(100%,150px)}}
</style>
@if(!auth()->check() || auth()->user()->role === 'customer')
<link rel="stylesheet" href="{{ asset('css/berkah-experience.css') }}">
@endif
@if(!auth()->check() || auth()->user()->role === 'customer')<link rel="stylesheet" href="{{ asset('css/page-transitions.css') }}"><script src="{{ asset('js/page-transitions.js') }}" defer></script>@endif
</head><body @if(!auth()->check() || auth()->user()->role === 'customer') class="bk-experience {{ auth()->check() ? 'bk-customer' : 'bk-guest' }}" @endif>
@if(!auth()->check() || auth()->user()->role === 'customer')
@include('catalog.customer-navigation')
<div class="hero">@yield('hero')</div>
@else
<header class="hero"><div class="top"><a href="{{ route('welcome') }}"><img class="logo" src="{{ asset('images/berkah-kreatif-logo.jpeg') }}" alt="Logo Berkah Kreatif"></a><div><a class="brand" href="{{ route('welcome') }}">Berkah Kreatif</a><div class="tag">Papan bunga untuk momen istimewa</div></div><nav class="nav" aria-label="Navigasi utama"><a href="{{ route('welcome') }}">Katalog</a><a href="{{ route('cart.index') }}">Keranjang ({{ count(session('rental_cart', [])) }})</a>@auth @if(auth()->user()->role === 'admin')<a href="{{ route('admin.catalog.index') }}">Kelola katalog</a><a href="{{ route('admin.rental.orders') }}">Pesanan sewa</a><a href="{{ route('admin.rental.cashbook.index') }}">Buku kas</a>@elseif(auth()->user()->role === 'customer')<a href="{{ route('rental.orders.index') }}">Pesanan saya</a><a href="{{ route('customer.dashboard') }}">Dashboard</a>@else<a href="{{ route('dashboard') }}">Dashboard</a>@endif @else<a href="{{ route('login') }}">Masuk</a>@endauth</nav></div>@yield('hero')</header>
@endif
<main class="wrap" id="main-content" tabindex="-1">@if(session('success'))<div class="notice" role="status">{{ session('success') }}</div>@endif @if($errors->any())<div class="notice" role="alert"><strong>Mohon periksa kembali:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif @yield('content')</main><footer>© {{ date('Y') }} Berkah Kreatif</footer>
@auth
    @if(auth()->user()->role === 'customer')
        @php
            $customerNavActive = match (true) {
                request()->routeIs('customer.dashboard') => 'beranda',
                request()->routeIs('customer.notifications*') => 'notif',
                request()->routeIs('customer.profile', 'customer.addresses.*') => 'profil',
                request()->routeIs('rental.orders.*') => 'pesanan',
                request()->routeIs('customer.help') => '',
                default => 'pesan',
            };
        @endphp
        @include('layouts.component.customer._navbar_customer', ['active' => $customerNavActive])
    @endif
@endauth
@if(!auth()->check() || auth()->user()->role === 'customer')
<script src="{{ asset('js/berkah-experience.js') }}" defer></script>
@endif
</body></html>
