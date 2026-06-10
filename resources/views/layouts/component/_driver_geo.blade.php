{{--
    Driver GPS sender (polling-based, no broadcasting).

    Usage:
        @include('layouts.component._driver_geo', ['locationUrl' => route('driver.orders.location', $order)])

    Requires a <meta name="csrf-token"> tag in the page <head>.
    Posts { lat, lng } to $locationUrl every ~15s while the page is open.
    Fails silently when geolocation permission is denied or unavailable.
--}}
@if(!empty($locationUrl))
<span data-driver-geo hidden data-location-url="{{ $locationUrl }}"></span>
<script>
(function () {
    var node = document.querySelector('[data-driver-geo]');
    if (!node || !('geolocation' in navigator)) {
        return;
    }

    var url = node.getAttribute('data-location-url');
    var tokenMeta = document.querySelector('meta[name="csrf-token"]');
    var csrfToken = tokenMeta ? tokenMeta.getAttribute('content') : null;
    if (!url || !csrfToken) {
        return;
    }

    var POLL_MS = 15000;
    var lastSentAt = 0;
    var inflight = false;

    function send(lat, lng) {
        if (inflight) {
            return;
        }
        inflight = true;
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ lat: lat, lng: lng }),
            credentials: 'same-origin',
            keepalive: true,
        }).catch(function () {
            // Fail silently — tracking is best-effort.
        }).finally(function () {
            inflight = false;
            lastSentAt = Date.now();
        });
    }

    function onPosition(pos) {
        if (!pos || !pos.coords) {
            return;
        }
        // Throttle to roughly one POST per poll window even if
        // watchPosition fires more frequently.
        if (Date.now() - lastSentAt < POLL_MS - 1000) {
            return;
        }
        send(pos.coords.latitude, pos.coords.longitude);
    }

    function onError() {
        // Permission denied / position unavailable — stay silent.
    }

    var geoOpts = { enableHighAccuracy: true, maximumAge: 10000, timeout: 12000 };

    if (typeof navigator.geolocation.watchPosition === 'function') {
        navigator.geolocation.watchPosition(onPosition, onError, geoOpts);
    } else {
        navigator.geolocation.getCurrentPosition(onPosition, onError, geoOpts);
        setInterval(function () {
            navigator.geolocation.getCurrentPosition(onPosition, onError, geoOpts);
        }, POLL_MS);
    }
})();
</script>
@endif
