@extends('catalog.layout')
@section('title', 'Beranda')
@section('content')
<div class="bk-dashboard">
<section class="bk-dashboard-welcome" data-bk-reveal="hero">
  <div><span class="bk-kicker">RUANG PELANGGAN</span><h1>Halo, {{ explode(' ', auth()->user()->name)[0] }}</h1><p>Semua yang kamu butuhkan untuk menyiapkan dan memantau momen istimewa, di satu tempat.</p><a href="{{ route('welcome') }}" class="btn">Jelajahi papan bunga <span aria-hidden="true">↗</span></a></div>
  <div class="bk-dashboard-flower" aria-hidden="true" data-bk-parallax="0.08"><span class="bk-i">@include('layouts.component._icon', ['name' => 'flower-2', 'size' => 24])</span></div>
</section>
<section class="bk-dashboard-preview" aria-labelledby="preview-title" data-bk-reveal="up">
  <div class="bk-section-heading"><div><span class="bk-kicker">KOLEKSI BERKAH KREATIF</span><h2 id="preview-title">Temukan papan untuk momenmu</h2></div><a href="{{ route('welcome') }}#koleksi">Lihat katalog →</a></div>
  <p class="muted">Beberapa pilihan dari katalog. Pilih papan untuk melihat detailnya.</p>
  <div class="grid bk-preview-grid" data-catalog-preview data-catalog-url="{{ route('welcome') }}" aria-live="polite" aria-busy="false">
    <p class="muted">Buka katalog untuk melihat pilihan papan bunga.</p>
    {{-- Kerangka setinggi kartu katalog: mengisi ruang selama data diambil
         (dashboard-preview.js), jadi bagian di bawah tidak melompat. Tetap
         `hidden` tanpa JavaScript, sehingga teks cadangan di atas yang tampil. --}}
    <div class="bk-preview-skeleton" data-preview-skeleton hidden aria-hidden="true">
      @for ($i = 0; $i < 4; $i++)
        <div class="bk-preview-skeleton__card">
          <div class="bk-preview-skeleton__media"></div>
          <div class="bk-preview-skeleton__body">
            <span class="bk-preview-skeleton__line bk-preview-skeleton__line--title"></span>
            <span class="bk-preview-skeleton__line bk-preview-skeleton__line--short"></span>
            <span class="bk-preview-skeleton__line bk-preview-skeleton__line--price"></span>
            <span class="bk-preview-skeleton__btn"></span>
          </div>
        </div>
      @endfor
    </div>
  </div>
  <a class="btn secondary" href="{{ route('welcome') }}#koleksi">Lihat semua papan</a>
</section>
<p class="muted">Sistem masih dalam pengujian dan belum untuk transaksi nyata.</p>
</div>
<script src="{{ asset('js/dashboard-preview.js') }}" defer></script>
@endsection
