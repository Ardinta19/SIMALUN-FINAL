@php
    /*
     |---------------------------------------------------------------------------
     | Shared Lucide Icon Partial  ( _icon.blade.php )
     |---------------------------------------------------------------------------
     | Feature: ui-ux-revamp  (Design System — Iconography)
     |
     | Usage:
     |   @include('layouts.component._icon', ['name' => 'bell', 'size' => 24, 'label' => 'Notifikasi'])
     |   @include('layouts.component._icon', ['name' => 'home'])   // decorative, default size 24
     |
     | Contract (Requirements 8.1, 8.2, 8.3, 8.4, 8.5, 8.6, 5.7, 5.8):
     |  - Emits exactly ONE inline Lucide <svg> from the single Lucide Icon_System
     |    (Req 8.1, 8.5) at a UNIFORM 2px nominal stroke (Req 8.2).
     |  - Renders ONLY at the allowed size set {16, 20, 24, 32} (Req 8.3); any other
     |    requested size is normalized to the NEAREST allowed value (ties resolve to
     |    the smaller allowed size).
     |  - Each conceptual action/status maps to EXACTLY ONE Lucide glyph reused on
     |    every role (Req 8.4) via the $aliases map below.
     |  - Unknown glyph names fall back to the designated 'circle-dot' glyph rather
     |    than importing another icon family (Req 8.6); the fallback still conforms
     |    to the 2px stroke and allowed size set.
     |  - Accessibility: when a non-empty $label is provided the icon is meaningful and
     |    receives role="img" + aria-label (Req 5.7); when omitted it is decorative and
     |    receives aria-hidden="true" (Req 5.8).
     |
     | The alias map, size-normalization, and fallback logic are PURE and
     | DETERMINISTIC (no I/O, no global state) so they can be validated by a
     | property test (design Property 3). The glyph table itself is one
     | generated include, read once per request.
     */

    /* -------------------------------------------------------------------------
     | Glyph map: canonical Lucide glyph name => inner SVG markup (viewBox 0 0 24 24).
     | Diambil dari paket resmi `lucide-static` (ISC) yang disalin ke
     | public/vendor/lucide/icons lalu dirakit menjadi _icon_glyphs.php oleh
     | tools/build-lucide-icons.mjs. Tidak ada CDN dan tidak ada unduhan saat
     | halaman dibuka; menambah ikon berarti menyalin satu SVG + menjalankan
     | skrip perakit, bukan menyalin geometri dengan tangan.
     | ---------------------------------------------------------------------- */
    $glyphs = require resource_path('views/layouts/component/_icon_glyphs.php');

    /* -------------------------------------------------------------------------
     | Glyph map (conceptual): each conceptual action/status binds to EXACTLY ONE
     | Lucide glyph, reused identically across Admin / Driver / Customer (Req 8.4).
     | Keys are the conceptual names callers use; values are canonical glyph names
     | present in $glyphs above.
     | ---------------------------------------------------------------------- */
    $aliases = [
        // navigation / structure
        'beranda'       => 'home',
        'dashboard'     => 'home',
        'back'          => 'arrow-left',
        'forward'       => 'arrow-right',
        'next'          => 'chevron-right',
        'prev'          => 'chevron-left',
        'expand'        => 'chevron-down',
        'collapse'      => 'chevron-up',

        // people / account
        'profile'       => 'user',
        'profil'        => 'user',
        'account'       => 'user',
        'add-user'      => 'user-plus',
        'logout'        => 'log-out',
        'keluar'        => 'log-out',
        'avatar'        => 'camera',

        // orders / commerce
        'orders'        => 'clipboard-list',
        'pesanan'       => 'clipboard-list',
        'tasks'         => 'clipboard-list',
        'tugas'         => 'clipboard-list',
        'cart'          => 'shopping-bag',
        'pesan'         => 'shopping-bag',
        'order-create'  => 'shopping-bag',
        'laundry'       => 'package',
        'finance'       => 'wallet',
        'money'         => 'dollar-sign',
        'payment'       => 'credit-card',
        'voucher'       => 'tag',
        'discount'      => 'tag',
        'analytics'     => 'bar-chart',
        'reports'       => 'bar-chart',
        'chart'         => 'bar-chart',

        // logistics / location
        'pickup'        => 'truck',
        'delivery'      => 'truck',
        'location'      => 'map-pin',
        'address'       => 'map-pin',
        'tracking'      => 'navigation',

        // time
        'time'          => 'clock',
        'pending'       => 'clock',
        'menunggu'      => 'clock',
        'date'          => 'calendar',

        // status / feedback
        'success'       => 'check-circle',
        'done'          => 'check',
        'selesai'       => 'check-circle',
        'warning'       => 'alert-triangle',
        'alert'         => 'alert-triangle',
        'error'         => 'x-circle',
        'danger'        => 'x-circle',
        'gagal'         => 'x-circle',
        'notif'         => 'bell',
        'notifications' => 'bell',
        'notifikasi'    => 'bell',
        'rating'        => 'star',
        'help'          => 'help-circle',
        'bantuan'       => 'help-circle',

        // actions
        'add'           => 'plus',
        'tambah'        => 'plus',
        'add-circle'    => 'plus-circle',
        'close'         => 'x',
        'tutup'         => 'x',
        'cari'          => 'search',
        'edit'          => 'pencil',
        'ubah'          => 'pencil',
        'delete'        => 'trash-2',
        'hapus'         => 'trash-2',
        'trash'         => 'trash-2',
        'view'          => 'eye',
        'lihat'         => 'eye',
        'refresh'       => 'refresh-cw',
        'loading'       => 'refresh-cw',

        // communication / documents
        'call'          => 'phone',
        'telepon'       => 'phone',
        'whatsapp'      => 'message-circle',
        'chat'          => 'message-circle',
        'report'        => 'file-text',
        'document'      => 'file-text',
        'receipt'       => 'receipt',
        'audit'         => 'file-text',

        // Pictograms — pengganti emoji, satu makna satu glyph.
        'flower'        => 'flower-2',
        'bunga'         => 'flower-2',
        'florist'       => 'flower',
        'sprout'        => 'sprout',
        'decoration'    => 'sparkles',
        'hias'          => 'sparkles',
        'celebrate'     => 'party-popper',
        'selamat'       => 'party-popper',
        'like'          => 'heart',
        'love'          => 'heart',
        'suka'          => 'heart',
        'email'         => 'mail',
        'surat'         => 'mail',
        'password'      => 'lock',
        'sandi'         => 'lock',
        'secure'        => 'lock-keyhole',
        'key'           => 'key-round',
        'akses'         => 'key-round',
        'qris'          => 'qr-code',
        'pindai'        => 'qr-code',
        'invoice'       => 'receipt',
        'kuitansi'      => 'receipt',
        'campus'        => 'graduation-cap',
        'kampus'        => 'graduation-cap',
        'archive'       => 'folder-open',
        'arsip'         => 'folder-open',
        'undo'          => 'undo-2',
        'kembalikan'    => 'undo-2',
        'reset'         => 'rotate-ccw',
        'ulang'         => 'rotate-ccw',
        'warning-solid' => 'alert-circle',
        'gambar'        => 'image-off',
    ];

    /* -------------------------------------------------------------------------
     | Pure, deterministic resolution (no I/O, no state).
     | ---------------------------------------------------------------------- */
    $allowedSizes = [16, 20, 24, 32];
    $fallbackGlyph = 'circle-dot';

    // 1) Resolve the conceptual name to a canonical glyph name.
    $requestedName = isset($name) ? strtolower(trim((string) $name)) : '';
    $glyphName = $aliases[$requestedName] ?? $requestedName;

    // 2) Substitute the designated fallback glyph for unknown names (Req 8.6).
    if (! array_key_exists($glyphName, $glyphs)) {
        $glyphName = $fallbackGlyph;
    }
    $inner = $glyphs[$glyphName];

    // 3) Normalize the requested size to the nearest allowed value (Req 8.3);
    //    ties resolve to the smaller allowed size. Default to 24 when unspecified.
    $requestedSize = (isset($size) && is_numeric($size)) ? (int) round((float) $size) : 24;
    $normalizedSize = $allowedSizes[0];
    $bestDistance = abs($requestedSize - $allowedSizes[0]);
    foreach ($allowedSizes as $candidate) {
        $distance = abs($requestedSize - $candidate);
        if ($distance < $bestDistance) {
            $bestDistance = $distance;
            $normalizedSize = $candidate;
        }
    }

    // 4) Accessibility: meaningful (labelled) vs decorative (Req 5.7 / 5.8).
    $accessibleLabel = (isset($label) && trim((string) $label) !== '') ? trim((string) $label) : null;
@endphp
<svg xmlns="http://www.w3.org/2000/svg" width="{{ $normalizedSize }}" height="{{ $normalizedSize }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-{{ $glyphName }}" @if($accessibleLabel) role="img" aria-label="{{ $accessibleLabel }}" @else aria-hidden="true" focusable="false" @endif>{!! $inner !!}</svg>
