<x-guest-layout :title="__('Kata Sandi Baru')">
    <h1>{{ __('Buat kata sandi baru') }}</h1>

    <p>
        {{ __('Masukkan kata sandi baru untuk akun Anda. Minimal delapan karakter dan mudah Anda ingat.') }}
    </p>

    <form method="POST" action="{{ route('password.update.reset') }}">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Kata Sandi Baru')" />
            <x-text-input id="password" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div>
            <x-input-label for="password_confirmation" :value="__('Ulangi Kata Sandi Baru')" />
            <x-text-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" />
        </div>

        <div>
            <x-primary-button>
                {{ __('Simpan Kata Sandi Baru') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
