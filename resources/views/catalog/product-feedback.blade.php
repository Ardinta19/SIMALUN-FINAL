<div class="bk-feedback" data-bk-feedback>
    <div class="bk-feedback-summary">
        <span aria-label="{{ $product->reviews_count ? 'Rata-rata '.number_format($product->reviews_avg_rating, 1, ',', '.').' dari 5, '.$product->reviews_count.' rating' : 'Belum ada rating' }}">★ {{ $product->reviews_count ? number_format($product->reviews_avg_rating, 1, ',', '.').' / 5 · '.$product->reviews_count.' rating' : 'Belum ada rating' }}</span>
        <span class="bk-like-total" data-bk-like-total aria-live="polite">♡ {{ $product->likes_count }} suka</span>
    </div>
    @if(auth()->check() && auth()->user()->role === 'customer')
    <form method="post" action="{{ route('rental.product.like', $product) }}" class="bk-like-form" data-bk-like-form>@csrf
        <input type="hidden" name="liked" value="{{ $product->liked_by_customer ? '0' : '1' }}">
        <button class="bk-like bk-heart" type="submit"
            data-bk-like
            data-bk-liked="{{ $product->liked_by_customer ? '1' : '0' }}"
            data-bk-label-on="Batalkan suka {{ $product->name }}"
            data-bk-label-off="Suka {{ $product->name }}"
            aria-pressed="{{ $product->liked_by_customer ? 'true' : 'false' }}"
            aria-label="{{ $product->liked_by_customer ? 'Batalkan suka' : 'Suka' }} {{ $product->name }}">
            <span class="bk-sr-only">{{ $product->liked_by_customer ? '♥ Disukai' : '♡ Suka' }}</span>
            <svg class="bk-heart__outline" viewBox="0 0 24 24" aria-hidden="true"><path d="M17.5,1.917a6.4,6.4,0,0,0-5.5,3.3,6.4,6.4,0,0,0-5.5-3.3A6.8,6.8,0,0,0,0,8.967c0,4.547,4.786,9.513,8.8,12.88a4.974,4.974,0,0,0,6.4,0C19.214,18.48,24,13.514,24,8.967A6.8,6.8,0,0,0,17.5,1.917Zm-3.585,18.4a2.973,2.973,0,0,1-3.83,0C4.947,16.006,2,11.87,2,8.967a4.8,4.8,0,0,1,4.5-5.05A4.8,4.8,0,0,1,11,8.967a1,1,0,0,0,2,0,4.8,4.8,0,0,1,4.5-5.05A4.8,4.8,0,0,1,22,8.967C22,11.87,19.053,16.006,13.915,20.313Z"></path></svg>
            <svg class="bk-heart__filled" viewBox="0 0 24 24" aria-hidden="true"><path d="M17.5,1.917a6.4,6.4,0,0,0-5.5,3.3,6.4,6.4,0,0,0-5.5-3.3A6.8,6.8,0,0,0,0,8.967c0,4.547,4.786,9.513,8.8,12.88a4.974,4.974,0,0,0,6.4,0C19.214,18.48,24,13.514,24,8.967A6.8,6.8,0,0,0,17.5,1.917Z"></path></svg>
            <svg class="bk-heart__burst" viewBox="0 0 100 100" aria-hidden="true"><polygon points="10,10 20,20"></polygon><polygon points="10,50 20,50"></polygon><polygon points="20,80 30,70"></polygon><polygon points="90,10 80,20"></polygon><polygon points="90,50 80,50"></polygon><polygon points="80,80 70,70"></polygon></svg>
        </button>
        <p class="bk-sr-only" role="status" aria-live="polite" data-bk-like-status></p>
    </form>
    @elseif(!auth()->check())
    <a class="bk-like" href="{{ route('login') }}">Masuk untuk menyukai</a>
    @endif
</div>
