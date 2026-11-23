<x-guest-layout :title="__('Verifikasi Email')">
    <h1>{{ __('Verifikasi email Anda') }}</h1>

    <p>
        {{ __('Terima kasih telah mendaftar! Sebelum memulai, silakan verifikasi alamat email Anda dengan mengklik tautan yang baru saja kami kirim. Jika tidak menerima email tersebut, kami akan dengan senang hati mengirim ulang.') }}
    </p>

    @if (session('status') == 'verification-link-sent')
        <p class="guest-success" role="status">
            {{ __('Tautan verifikasi baru telah dikirim ke alamat email yang Anda daftarkan.') }}
        </p>
    @endif

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf

        <div>
            <x-primary-button>
                {{ __('Kirim Ulang Email Verifikasi') }}
            </x-primary-button>
        </div>
    </form>

    <form method="POST" action="{{ route('logout') }}">
        @csrf

        <button type="submit" class="guest-link-btn">
            {{ __('Keluar') }}
        </button>
    </form>
</x-guest-layout>
