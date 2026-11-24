<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Profil · Berkah Kreatif</title>
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family:'Plus Jakarta Sans', sans-serif; background:#f4f8fc; color:#1a2332; padding:24px 16px; }
    .container { max-width:520px; margin:0 auto; }
    h1 { font-size:1.4rem; margin-bottom:18px; color:#54051f; }
    .card { background:#fff; border:1.5px solid #ddeeff; border-radius:14px; padding:18px; margin-bottom:14px; }
    .field { margin-bottom:14px; }
    label { display:block; font-size:var(--bk-fs-2xs); font-weight:800; color:#3d5066; margin-bottom:5px; text-transform:uppercase; letter-spacing:.4px; }
    input, textarea { width:100%; padding:11px 13px; border:1.5px solid #ddeeff; border-radius:10px; font-family:inherit; font-size:var(--bk-fs-md); }
    input:focus, textarea:focus { outline:none; border-color:#85092d; }
    button { width:100%; padding:12px; background:#85092d; color:#fff; border:none; border-radius:99px; font-weight:800; cursor:pointer; }
    .alert { padding:11px 14px; border-radius:10px; font-weight:700; margin-bottom:14px; font-size:var(--bk-fs-sm); }
    .alert-success { background:#e6fff6; color:#047857; }
    .alert-error { background:#fff1f1; color:#b91c1c; }
    a.back { color:#85092d; font-weight:800; text-decoration:none; display:inline-block; margin-bottom:12px; }
</style>
@include('layouts.component._theme_head')
</head>
<body>
<div class="container" role="main">

    @php
        $dashboard = match($user->role ?? 'customer') {
            'admin'  => 'dashboard.admin',
            default  => 'customer.dashboard',
        };
    @endphp

    <a href="{{ route($dashboard) }}" class="back">← Kembali</a>
    <h1>Edit Profil</h1>

    @if(session('status') === 'profile-updated')
        <div class="alert alert-success">Profil berhasil diperbarui.</div>
    @endif

    @if($errors->any())
        <div class="alert alert-error">
            @foreach($errors->all() as $e) {{ $e }}<br> @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
        @csrf
        @method('patch')

        <div class="card">
            <div class="field">
                <label for="profile-name">Nama</label>
                <input id="profile-name" type="text" name="name" value="{{ old('name', $user->name) }}" required>
            </div>
            <div class="field">
                <label for="profile-email">Email</label>
                <input autocomplete="email" id="profile-email" type="email" name="email" value="{{ old('email', $user->email) }}" required>
            </div>
            <div class="field">
                <label for="profile-phone">Nomor HP</label>
                <input id="profile-phone" type="tel" name="phone" autocomplete="tel" value="{{ old('phone', $user->phone) }}">
            </div>
            <div class="field">
                <label for="profile-avatar">Foto Profil</label>
                <input id="profile-avatar" type="file" name="avatar" accept="image/*">
            </div>
            <button type="submit">Simpan Perubahan</button>
        </div>
    </form>

    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        @method('put')
        <div class="card">
            <h2 style="font-size:1rem; margin-bottom:12px; color:var(--theme-ink, #301722);">Ganti Kata Sandi</h2>
            <div class="field">
                <label for="profile-current-password">Kata Sandi Saat Ini</label>
                <input id="profile-current-password" type="password" name="current_password" autocomplete="current-password">
            </div>
            <div class="field">
                <label for="profile-new-password">Kata Sandi Baru</label>
                <input id="profile-new-password" type="password" name="password" autocomplete="new-password">
            </div>
            <div class="field">
                <label for="profile-confirm-password">Konfirmasi Kata Sandi Baru</label>
                <input id="profile-confirm-password" type="password" name="password_confirmation" autocomplete="new-password">
            </div>
            <button type="submit">Perbarui Kata Sandi</button>
        </div>
    </form>

</div>
@include('layouts.component._form_controls')
@include('layouts.component._ui_kit')
</body>
</html>
