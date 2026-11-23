<!doctype html>
<html lang="id"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>@yield('title') · Berkah Kreatif</title>
<link rel="icon" href="{{ asset('images/berkah-kreatif-logo.jpeg') }}"><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
@include('layouts.component._tokens')
<style>
*{box-sizing:border-box}body{margin:0;background:#faf4f3;color:#301722;font-family:'Plus Jakarta Sans',system-ui,-apple-system,'Segoe UI',sans-serif;line-height:1.65;padding-bottom:calc(94px + env(safe-area-inset-bottom,0px))}a{color:#85092d}.help-hero{background:linear-gradient(145deg,#54051f,#85092d 65%,#b6435e);color:#fff}.help-head{max-width:560px;margin:auto;padding:max(24px,env(safe-area-inset-top)) 20px 30px}.help-head a{color:#fff}.help-head h1{font-size:clamp(1.5rem,5vw,1.9rem);line-height:1.25;margin:16px 0 6px}.help-head p{margin:0;color:#ffebef}.help-main{max-width:560px;margin:auto;padding:20px 16px}.help-card{background:#fff;border:1px solid #f0dfe1;border-radius:18px;padding:18px;margin-bottom:14px;box-shadow:0 4px 16px #54051f0a}.help-card h2{font-size:1.05rem;line-height:1.35;margin:0 0 12px}.help-card p,.help-card li{font-size:var(--bk-fs-md);line-height:1.7}.help-card ol,.help-card ul{padding-left:1.3rem}.help-card li+li{margin-top:10px}.help-card details{border-top:1px solid #f0dfe1;padding:12px 0}.help-card summary{font-weight:700;cursor:pointer}.help-card details p{margin:10px 0 0}.help-notice{background:#fff3e8;color:#6c4425}.help-button{display:inline-flex;align-items:center;justify-content:center;min-height:48px;padding:10px 16px;border-radius:12px;background:#85092d;color:#fff;text-decoration:none;font-weight:700}.help-muted{color:#6f5a63}a:focus-visible,summary:focus-visible{outline:3px solid #bc5b75;outline-offset:3px}@media(max-width:350px){.help-head{padding-inline:16px}.help-main{padding-inline:12px}}
</style><link rel="stylesheet" href="{{ asset('css/page-transitions.css') }}"><script src="{{ asset('js/page-transitions.js') }}" defer></script>
@include('layouts.component._theme_head')
</head><body>
<header class="help-hero"><div class="help-head"><a data-smart-back class="bk-back" href="@yield('back')">← Kembali ke beranda</a><h1>@yield('heading')</h1><p>@yield('subtitle')</p></div></header>
<main class="help-main">@yield('content')</main>
@yield('navigation')
@include('layouts.component._form_controls')
@include('layouts.component._ui_kit')
</body></html>
