<?php

namespace Tests\Feature;

use App\Support\BackUrl;
use Illuminate\Http\Request;
use Tests\TestCase;

class BackUrlSafetyTest extends TestCase
{
    public function test_only_local_paths_and_same_origin_urls_are_accepted(): void
    {
        $fallback = route('customer.dashboard');
        foreach (['//evil.example/path', '/\\evil.example', "https://good.example\n.evil.example/", 'javascript://good.example/x', 'https://good.example:8443/x', 'https://user@good.example/x', 'http://good.example/x', 'https://evil.example/x', '%2F%2Fevil.example'] as $unsafe) {
            $request = Request::create('https://good.example/current', 'GET', ['back' => $unsafe]);
            $this->assertSame($fallback, BackUrl::resolve($request, 'customer.dashboard'), $unsafe);
        }
        foreach (['/pesanan?q=hello%20world', 'https://good.example/pesanan?q=1'] as $safe) {
            $request = Request::create('https://good.example/current', 'GET', ['back' => $safe]);
            $this->assertSame($safe, BackUrl::resolve($request, 'customer.dashboard'));
        }
    }

    public function test_error_pages_never_use_javascript_urls(): void
    {
        // Tombol "Kembali" dan "Muat Ulang" pernah memakai
        // href="javascript:...". Saat skrip diblokir atau CSP ketat, tautan itu
        // mati total. Sekarang tautan selalu menuju halaman nyata dan skrip
        // hanya memperhalus perilakunya.
        $views = glob(dirname(__DIR__, 2).'/resources/views/errors/*.blade.php') ?: [];
        $this->assertNotEmpty($views);

        foreach ($views as $view) {
            $source = (string) preg_replace('/\{\{--.*?--\}\}/s', '', (string) file_get_contents($view));
            $this->assertStringNotContainsString('javascript:', $source, basename($view).' masih memakai URL javascript:.');
        }

        $layout = (string) file_get_contents(dirname(__DIR__, 2).'/resources/views/errors/layout.blade.php');
        $this->assertStringContainsString('data-reload-page', $layout);
        $this->assertStringContainsString('data-smart-back', $layout);
        $this->assertStringContainsString('window.location.reload()', $layout);
    }

    public function test_referer_is_subject_to_the_same_origin_rules(): void
    {
        $request = Request::create('https://good.example/current', 'GET', [], [], [], ['HTTP_REFERER' => 'https://evil.example/']);
        $this->assertSame(route('customer.dashboard'), BackUrl::resolve($request, 'customer.dashboard'));
        $request->headers->set('referer', 'https://good.example/orders');
        $this->assertSame('https://good.example/orders', BackUrl::resolve($request, 'customer.dashboard'));
    }
}
