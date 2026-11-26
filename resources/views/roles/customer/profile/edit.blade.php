<!DOCTYPE html>
<html lang="id">
<head>
@php $asset = fn (string $path) => asset($path).(file_exists(public_path($path)) ? '?v='.filemtime(public_path($path)) : ''); @endphp
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
@include('layouts.component._page_shell')
<title>Ubah profil · Berkah Kreatif</title>
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
    :root {
        --blue-dark:#54051f; --blue-mid:#85092d; --blue-light:#b6435e;
        --blue-sky:#f8e9ed; --orange:#c2410c; --green:#087f5b; --red:#c62f2f;
        --ink:#321d24; --ink-mid:#6f5960; --ink-lt:#806975;
        --surface:#faf4f3; --border:#f0dfe1; --radius:16px; --radius-sm:10px;
    }
    *,*::before,*::after { margin:0; padding:0; box-sizing:border-box; -webkit-tap-highlight-color:transparent; }
    body {
        font-family:'Plus Jakarta Sans', sans-serif;
        background:var(--surface);
        color:var(--ink);
        min-height:100vh;min-height:100dvh;
        padding-bottom:calc(80px + env(safe-area-inset-bottom, 0px));
        overflow-x:clip;
    }

    .page-header {
        background:linear-gradient(135deg, var(--blue-dark) 0%, var(--blue-mid) 100%);
        padding:max(env(safe-area-inset-top, 0px), 16px) 20px 24px;
        position:sticky;
        top:0;
        z-index:100;
    }
    .header-row {
        display:flex;
        align-items:center;
        gap:12px;
        max-width:520px;
        margin:0 auto;
    }
    .btn-back {
        width:38px; height:38px;
        border-radius:50%;
        background:rgba(255,255,255,.15);
        border:1.5px solid rgba(255,255,255,.25);
        display:flex; align-items:center; justify-content:center;
        color:#fff;
        text-decoration:none;
        flex-shrink:0;
    }
    .btn-back svg { width:18px; height:18px; }
    .header-title {
        flex:1;
        font-weight:800;
        font-size:1.3rem;
        color:#fff;
        margin:0;
    }
    .header-sub {
        font-size:var(--bk-fs-2xs);
        font-weight:800;
        color:rgba(255,255,255,.65);
        margin-top:2px;
        letter-spacing:.5px;
    }

    .page-body { max-width:520px; margin:0 auto; padding:16px; }

    .alert {
        border-radius:var(--radius-sm);
        padding:12px 14px;
        font-size:var(--bk-fs-sm);
        font-weight:700;
        margin-bottom:14px;
    }
    .alert-success {
        background:#e6fff6;
        color:#047857;
        border:1.5px solid rgba(0,196,140,.3);
    }
    .alert-error {
        background:#fff1f1;
        color:#b91c1c;
        border:1.5px solid #fecaca;
    }

    .section-card {
        background:#fff;
        border-radius:var(--radius);
        border:1.5px solid var(--border);
        margin-bottom:14px;
        overflow:hidden;
        box-shadow:0 2px 12px rgba(84,5,31,.05);
    }
    .section-head {
        display:flex;
        align-items:center;
        gap:10px;
        padding:14px 16px;
        border-bottom:1.5px solid var(--border);
        background:linear-gradient(90deg, rgba(0,119,182,.04) 0%, transparent 100%);
    }
    .section-num {
        width:28px; height:28px;
        border-radius:50%;
        background:var(--blue-mid);
        color:#fff;
        font-weight:800;
        font-size:var(--bk-fs-sm);
        display:flex; align-items:center; justify-content:center;
        flex-shrink:0;
    }
    .section-title {
        font-weight:800;
        font-size:var(--bk-fs-md);
        color:var(--blue-dark);
    }
    .section-body { padding:16px; display:flex; flex-direction:column; gap:14px; }

    .avatar-wrap {
        display:flex;
        flex-direction:column;
        align-items:center;
        gap:10px;
        margin-bottom:6px;
    }
    .avatar-preview {
        width:96px; height:96px;
        border-radius:50%;
        object-fit:cover;
        border:3px solid var(--blue-sky);
        background:var(--blue-sky);
    }
    .avatar-action {
        font-size:var(--bk-fs-2xs);
        font-weight:800;
        color:var(--blue-mid);
        cursor:pointer;
        background:var(--blue-sky);
        padding:.45rem 1rem;
        border-radius:99px;
    }
    .avatar-action input { display:none; }

    .field-label {
        font-size:var(--bk-fs-2xs);
        font-weight:900;
        color:var(--ink-mid);
        letter-spacing:.4px;
        text-transform:uppercase;
        margin-bottom:6px;
    }
    .field-label .req { color:var(--red); }
    .field-input {
        width:100%;
        padding:12px 14px;
        border:1.5px solid var(--border);
        border-radius:var(--radius-sm);
        font-family:'Plus Jakarta Sans', sans-serif;
        font-size:var(--bk-fs-md);
        font-weight:600;
        color:var(--ink);
        background:#fff;
        outline:none;
        transition:border-color .2s, box-shadow .2s;
    }
    .field-input:focus {
        border-color:var(--blue-mid);
        box-shadow:0 0 0 3px rgba(0,119,182,.12);
    }
    .field-input.is-error {
        border-color:var(--red);
        box-shadow:0 0 0 3px rgba(239,68,68,.1);
    }
    .field-input::placeholder { color:var(--ink-lt); font-weight:600; }
    textarea.field-input { resize:none; min-height:80px; line-height:1.5; }
    .field-error {
        font-size:var(--bk-fs-2xs);
        font-weight:800;
        color:var(--red);
        margin-top:5px;
    }
    .field-hint {
        font-size:var(--bk-fs-2xs);
        font-weight:700;
        color:var(--ink-lt);
        margin-top:4px;
    }

    .btn-submit {
        width:100%;
        padding:13px;
        background:linear-gradient(135deg, var(--orange) 0%, #ff8c5a 100%);
        color:#fff;
        font-weight:900;
        font-size:var(--bk-fs-md);
        border:none;
        border-radius:99px;
        cursor:pointer;
        box-shadow:0 6px 18px rgba(255,107,53,.35);
        transition:transform .15s;
    }
    .btn-submit.secondary {
        background:linear-gradient(135deg, var(--blue-dark) 0%, var(--blue-mid) 100%);
        box-shadow:0 6px 18px rgba(0,119,182,.3);
    }
    .btn-submit:active { transform:scale(.97); }

    .btn-danger {
        width:100%;
        padding:11px;
        background:transparent;
        color:var(--red);
        border:1.5px solid var(--red);
        border-radius:99px;
        font-weight:800;
        font-size:var(--bk-fs-sm);
        cursor:pointer;
        transition:background-color .2s ease, color .2s ease;
    }
    .btn-danger:hover { background:var(--red); color:#fff; }

/* Tema gelap: halaman ini memakai variabel lokal (--surface/--card/--ink/--border)
   dan beberapa latar putih lepas, jadi tidak ikut membaik saat pengguna memilih
   tema gelap di header. Pemetaan di bawah hanya berlaku di halaman ini (tidak
   mengubah halaman lain yang juga memakai --ink untuk palet terangnya). */
html[data-theme="dark"] {
    --surface: #140f12;
    --card: #1d1619;
    --ink: #f6eef0;
    --ink-mid: #cbb9bf;
    --ink-lt: #ab989f;
    --border: #3a2b31;
    --blue-sky: #2a1c22;
}
html[data-theme="dark"] body { background: var(--surface); color: var(--ink); }
html[data-theme="dark"] .section-card,
html[data-theme="dark"] .field-input,
html[data-theme="dark"] .zone-pill,
html[data-theme="dark"] textarea.field-input {
    background: var(--card); color: var(--ink); border-color: var(--border);
}
html[data-theme="dark"] .alert-error { background: #3a1418; color: #ffb4b4; }
html[data-theme="dark"] .alert-success { background: #0f2b22; color: #8ff0cc; }
</style>
<link rel="stylesheet" href="{{ $asset('css/page-transitions.css') }}"><script src="{{ $asset('js/page-transitions.js') }}" defer></script>
@if($user->role === 'customer')<link rel="stylesheet" href="{{ $asset('css/customer-forms.css') }}"><link rel="stylesheet" href="{{ $asset('css/berkah-experience.css') }}">@endif
@include('layouts.component._theme_head')
</head>
<body @if($user->role === 'customer') class="bk-experience bk-customer bk-standalone" @endif>
@php
$oldText = function (string $key, $fallback = '') {
    $value = old($key, $fallback);
    return is_scalar($value) ? (string) $value : '';
};
@endphp
@if($user->role === 'customer')@include('catalog.customer-navigation')@endif

<header class="page-header">
    <div class="header-row">
        <a data-smart-back href="{{ route('customer.profile') }}" class="btn-back" aria-label="Kembali ke profil">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19 12H5M12 5l-7 7 7 7"/>
            </svg>
        </a>
        <div>
            <h1 class="header-title">Ubah profil</h1>
            <div class="header-sub">Perbarui data pribadi & alamat utama</div>
        </div>
    </div>
</header>

<main class="page-body" id="main-content" tabindex="-1">

    @if(session('status') === 'profile-updated')
        <div class="alert alert-success">Profil berhasil diperbarui.</div>
    @elseif(session('status') === 'address-saved')
        <div class="alert alert-success">Alamat utama berhasil disimpan.</div>
    @elseif(session('status') === 'password-updated')
        <div class="alert alert-success">Kata sandi berhasil diubah.</div>
    @endif

    @if($errors->any())
        <div class="alert alert-error" role="alert">
            Periksa kembali isian berikut:
            @foreach($errors->all() as $e) <br>• {{ $e }} @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
        @csrf
        @method('patch')

        <div class="section-card">
            <div class="section-head">
                <div class="section-num">1</div>
                <div class="section-title">Data pribadi</div>
            </div>
            <div class="section-body">
                <div class="avatar-wrap">
                    <img id="avatar-preview" class="avatar-preview"
                         src="{{ $user->avatar ? asset('storage/'.$user->avatar) : asset('images/logo-104.png') }}"
                         alt="Foto profil">
                    <label class="avatar-action">
                        Ubah Foto
                        <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" id="avatar-input">
                    </label>
                    <div class="field-hint">Maksimal 2 MB · format JPG, PNG, atau WebP</div>
                </div>

                @if($user->avatar)
                <div style="text-align:center; margin-top:4px;">
                    <button type="button" class="btn-danger" style="width:auto; padding:8px 18px; font-size:var(--bk-fs-2xs);" id="btn-delete-avatar">Hapus foto profil</button>
                </div>
                @endif

                <div>
                    <label class="field-label" for="profile-name">Nama Lengkap <span class="req">*</span></label>
                    <input placeholder="Contoh: Sari Wulandari" autocomplete="name" type="text" id="profile-name" name="name"
                           class="field-input {{ $errors->has('name') ? 'is-error' : '' }}"
                           value="{{ $oldText('name', $user->name) }}" required>
                    @error('name') <div class="field-error" role="alert">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="field-label" for="profile-email">Email <span style="font-weight:600;color:var(--ink-lt)">(opsional)</span></label>
                    <input placeholder="Contoh: nama@email.com" autocomplete="email" type="email" id="profile-email" name="email"
                           class="field-input {{ $errors->has('email') ? 'is-error' : '' }}"
                           value="{{ $oldText('email', $user->email) }}">
                    @error('email') <div class="field-error" role="alert">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="field-label" for="profile-phone">Nomor HP / WhatsApp <span class="req">*</span></label>
                    <input placeholder="Contoh: 0812 3456 7890" autocomplete="tel" type="tel" inputmode="tel" id="profile-phone" name="phone"
                           class="field-input"
                           value="{{ $oldText('phone', $user->phone) }}" required>
                    @error('phone') <div class="field-error" role="alert">{{ $message }}</div> @enderror
                </div>

                <button type="submit" class="btn-submit">Simpan data pribadi</button>
            </div>
        </div>
    </form>

    <form method="POST" action="{{ route('profile.primary-address') }}">
        @csrf

        <div class="section-card">
            <div class="section-head">
                <div class="section-num">2</div>
                <div class="section-title">{{ $primaryAddress ? 'Ubah alamat utama' : 'Tambah alamat utama' }}</div>
            </div>
            <div class="section-body">

                <div>
                    <label class="field-label" for="profile-address-label">Label <span class="req">*</span></label>
                    <input placeholder="Contoh: Rumah" type="text" id="profile-address-label" name="label"
                           class="field-input {{ $errors->has('label') ? 'is-error' : '' }}"
                           value="{{ $oldText('label', $primaryAddress->label ?? 'Rumah') }}"
                           required autocomplete="off">
                </div>

                <div>
                    <label class="field-label" for="profile-address-recipient">Nama Penerima <span class="req">*</span></label>
                    <input placeholder="Contoh: Ibu Sari Wulandari" type="text" id="profile-address-recipient" name="recipient_name"
                           class="field-input {{ $errors->has('recipient_name') ? 'is-error' : '' }}"
                           value="{{ $oldText('recipient_name', $primaryAddress->recipient_name ?? $user->name) }}"
                           required autocomplete="name">
                </div>

                <div>
                    <label class="field-label" for="profile-address-phone">No. HP Penerima</label>
                    <input placeholder="Contoh: 0812 3456 7890" autocomplete="tel" type="tel" inputmode="tel" id="profile-address-phone" name="phone"
                           class="field-input"
                           value="{{ $oldText('phone', $primaryAddress->phone ?? $user->phone) }}">
                </div>

                <div>
                    <label class="field-label" for="profile-address-full">Alamat Lengkap <span class="req">*</span></label>
                    <textarea placeholder="Contoh: Jl. Merdeka No. 12, RT 03, Palembang" id="profile-address-full" name="full_address"
                              class="field-input {{ $errors->has('full_address') ? 'is-error' : '' }}"
                              required autocomplete="street-address" rows="4">{{ $oldText('full_address', $primaryAddress->full_address ?? '') }}</textarea>
                </div>

                <div>
                    <label class="field-label" for="profile-address-notes">Catatan (opsional)</label>
                    <input placeholder="Contoh: Pagar hijau, titip ke satpam bila tidak ada orang." type="text" id="profile-address-notes" name="notes"
                           class="field-input"
                           value="{{ $oldText('notes', $primaryAddress->notes ?? '') }}" autocomplete="off">
                </div>

                <button type="submit" class="btn-submit secondary">
                    {{ $primaryAddress ? 'Simpan Perubahan Alamat' : 'Simpan Alamat Utama' }}
                </button>
            </div>
        </div>
    </form>

    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        @method('put')

        <div class="section-card">
            <div class="section-head">
                <div class="section-num">3</div>
                <div class="section-title">Ganti kata sandi</div>
            </div>
            <div class="section-body">
                <div>
                    <label class="field-label" for="profile-current-password">Kata sandi saat ini</label>
                    <input type="password" id="profile-current-password" name="current_password" class="field-input" autocomplete="current-password" placeholder="••••••••">
                    @error('current_password', 'updatePassword') <div class="field-error" role="alert">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="field-label" for="profile-new-password">Kata sandi baru</label>
                    <input type="password" id="profile-new-password" name="password" class="field-input" autocomplete="new-password" placeholder="Minimal 8 karakter">
                    @error('password', 'updatePassword') <div class="field-error" role="alert">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="field-label" for="profile-confirm-password">Konfirmasi kata sandi baru</label>
                    <input type="password" id="profile-confirm-password" name="password_confirmation" class="field-input" autocomplete="new-password" placeholder="Ulangi kata sandi baru">
                </div>

                <button type="submit" class="btn-submit secondary">Perbarui kata sandi</button>
            </div>
        </div>
    </form>

    @if($user->role === 'customer')
        <div class="section-card">
            <div class="section-head" style="background:var(--theme-danger-soft,#fff5f5);">
                <div class="section-num" style="background:var(--red);">!</div>
                <div class="section-title" style="color:var(--red);">Hapus akun</div>
            </div>
            <div class="section-body">
                <div class="field-hint" style="margin:0;">
                    Akun akan dinonaktifkan. Data pesanan dan catatan transaksi dapat tetap tersimpan untuk keperluan operasional.
                </div>
                <form method="POST" action="{{ route('profile.destroy') }}" id="form-delete-account">
                    @csrf
                    @method('delete')
                    <div>
                        <label class="field-label" for="profile-delete-password">Konfirmasi Kata Sandi <span class="req">*</span></label>
                        <input autocomplete="current-password" type="password" id="profile-delete-password" name="password" class="field-input" required placeholder="Masukkan kata sandi kamu">
                        @error('password') <div class="field-error" role="alert">{{ $message }}</div> @enderror
                    </div>
                    <button type="button" class="btn-danger" style="margin-top:12px;" id="btn-delete-account">Nonaktifkan akun</button>
                </form>
            </div>
        </div>
    @endif

</main>

@include('layouts.component.customer._confirm_modal')

<form method="POST" action="{{ route('profile.avatar.delete') }}" id="form-delete-avatar" style="display:none;">
    @csrf
    @method('DELETE')
</form>

<script>
(function() {
    var avatarInput = document.getElementById('avatar-input');
    if (avatarInput) {
        avatarInput.addEventListener('change', function(event) {
            var file = event.target.files[0];
            if (!file) return;

            if (file.size > 2 * 1024 * 1024) {
                showConfirmModal({
                    title: 'File Terlalu Besar',
                    message: 'Ukuran foto maksimal 2 MB. Silakan pilih foto yang lebih kecil.',
                    confirmText: 'Mengerti',
                    cancelText: 'Tutup',
                    type: 'warning',
                    onConfirm: function() {}
                });
                event.target.value = '';
                return;
            }

            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('avatar-preview').src = e.target.result;
            };
            reader.readAsDataURL(file);
        });
    }

    var btnDeleteAvatar = document.getElementById('btn-delete-avatar');
    if (btnDeleteAvatar) {
        btnDeleteAvatar.addEventListener('click', function() {
            showConfirmModal({
                title: 'Hapus foto profil?',
                message: 'Foto profil kamu akan dihapus dan diganti dengan avatar default.',
                confirmText: 'Ya, hapus',
                cancelText: 'Batal',
                type: 'warning',
                onConfirm: function() {
                    document.getElementById('form-delete-avatar').submit();
                }
            });
        });
    }

    var btnDelete = document.getElementById('btn-delete-account');
    if (btnDelete) {
        btnDelete.addEventListener('click', function() {
            var passwordInput = document.querySelector('#form-delete-account input[name="password"]');
            if (!passwordInput || !passwordInput.value) {
                passwordInput.focus();
                return;
            }
            showConfirmModal({
                title: 'Nonaktifkan akun?',
                message: 'Akun akan dinonaktifkan. Data pesanan dan catatan transaksi dapat tetap tersimpan. Lanjutkan?',
                confirmText: 'Ya, nonaktifkan',
                cancelText: 'Batal',
                type: 'danger',
                onConfirm: function() {
                    document.getElementById('form-delete-account').submit();
                }
            });
        });
    }
})();
</script>

@include('layouts.component.customer._navbar_customer', ['active' => 'profil'])

@if($user->role === 'customer')<script src="{{ $asset('js/berkah-experience.js') }}" defer></script>@endif
@include('layouts.component._form_controls')
@include('layouts.component._ui_kit')
</body>
</html>
