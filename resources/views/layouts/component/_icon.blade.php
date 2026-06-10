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
     | The size-normalization and fallback logic are intentionally PURE and
     | DETERMINISTIC (no I/O, no global state) so they can be validated by a property
     | test (design Property 3).
     */

    /* -------------------------------------------------------------------------
     | Glyph map: canonical Lucide glyph name => inner SVG markup (viewBox 0 0 24 24).
     | Path geometry is Lucide line-icon data; the wrapping <svg> supplies the
     | uniform stroke attributes so every glyph renders identically.
     | ---------------------------------------------------------------------- */
    $glyphs = [
        // --- Navigation / structure ---
        'home'           => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
        'menu'           => '<line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/>',
        'arrow-left'     => '<line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>',
        'arrow-right'    => '<line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>',
        'chevron-left'   => '<polyline points="15 18 9 12 15 6"/>',
        'chevron-right'  => '<polyline points="9 18 15 12 9 6"/>',
        'chevron-down'   => '<polyline points="6 9 12 15 18 9"/>',
        'chevron-up'     => '<polyline points="18 15 12 9 6 15"/>',

        // --- People / account ---
        'user'           => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'user-plus'      => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/>',
        'log-out'        => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>',
        'settings'       => '<path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/>',
        'camera'         => '<path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/>',

        // --- Orders / commerce ---
        'clipboard-list' => '<rect x="8" y="2" width="8" height="4" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11h4"/><path d="M12 16h4"/><path d="M8 11h.01"/><path d="M8 16h.01"/>',
        'shopping-bag'   => '<path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/>',
        'package'        => '<line x1="16.5" y1="9.4" x2="7.5" y2="4.21"/><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>',
        'tag'            => '<path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/>',
        'wallet'         => '<path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4z"/>',
        'dollar-sign'    => '<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>',
        'credit-card'    => '<rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/>',
        'bar-chart'      => '<line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/>',

        // --- Logistics / location ---
        'truck'          => '<rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>',
        'map-pin'        => '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>',
        'map'            => '<polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/><line x1="8" y1="2" x2="8" y2="18"/><line x1="16" y1="6" x2="16" y2="22"/>',
        'navigation'     => '<polygon points="3 11 22 2 13 21 11 13 3 11"/>',

        // --- Time ---
        'clock'          => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
        'calendar'       => '<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',

        // --- Status / feedback ---
        'check'          => '<polyline points="20 6 9 17 4 12"/>',
        'check-circle'   => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',
        'alert-triangle' => '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
        'x-circle'       => '<circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>',
        'info'           => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>',
        'help-circle'    => '<circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
        'bell'           => '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>',
        'star'           => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',

        // --- Actions ---
        'plus'           => '<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>',
        'plus-circle'    => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/>',
        'minus'          => '<line x1="5" y1="12" x2="19" y2="12"/>',
        'x'              => '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>',
        'search'         => '<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>',
        'filter'         => '<polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>',
        'pencil'         => '<path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/>',
        'trash-2'        => '<polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/>',
        'eye'            => '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>',
        'eye-off'        => '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>',
        'refresh-cw'     => '<polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>',

        // --- Communication / documents ---
        'phone'          => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>',
        'message-circle' => '<path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>',
        'file-text'      => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>',

        // --- Designated fallback (Req 8.6) ---
        'circle-dot'     => '<circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="1"/>',
    ];

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
        'receipt'       => 'file-text',
        'audit'         => 'file-text',
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
