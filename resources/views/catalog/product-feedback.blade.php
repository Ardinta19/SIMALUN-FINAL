<div class="bk-feedback-summary">
    <span aria-label="{{ $product->reviews_count ? 'Rata-rata '.number_format($product->reviews_avg_rating, 1, ',', '.').' dari 5, '.$product->reviews_count.' rating' : 'Belum ada rating' }}">★ {{ $product->reviews_count ? number_format($product->reviews_avg_rating, 1, ',', '.').' / 5 · '.$product->reviews_count.' rating' : 'Belum ada rating' }}</span>
    <span>♡ {{ $product->likes_count }} suka</span>
</div>
@if(auth()->check() && auth()->user()->role === 'customer')
<form method="post" action="{{ route('rental.product.like', $product) }}" class="bk-like-form">@csrf
    <input type="hidden" name="liked" value="{{ $product->liked_by_customer ? '0' : '1' }}">
    <button class="bk-like" type="submit" aria-pressed="{{ $product->liked_by_customer ? 'true' : 'false' }}" aria-label="{{ $product->liked_by_customer ? 'Batalkan suka' : 'Suka' }} {{ $product->name }}">{{ $product->liked_by_customer ? '♥ Disukai' : '♡ Suka' }}</button>
</form>
@elseif(!auth()->check())
<a class="bk-like" href="{{ route('login') }}">Masuk untuk menyukai</a>
@endif
