<x-guest-layout :title="__('Konfirmasi kata sandi')">
    <h1>{{ __('Konfirmasi kata sandi') }}</h1>

    <p>
        {{ __('Ini adalah area aman aplikasi. Silakan konfirmasi kata sandi kamu sebelum melanjutkan.') }}
    </p>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <div>
            <x-input-label for="password" :value="__('Kata sandi')" />

            <x-text-input id="password"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div>
            <x-primary-button>
                {{ __('Konfirmasi') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
