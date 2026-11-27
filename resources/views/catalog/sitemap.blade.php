{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
{{-- Peta situs etalase: beranda dan tiap papan yang aktif. --}}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>{{ route('welcome') }}</loc>
        @if($products->max('updated_at'))<lastmod>{{ $products->max('updated_at')->toAtomString() }}</lastmod>@endif
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
@foreach($products as $product)
    <url>
        <loc>{{ route('catalog.show', $product) }}</loc>
        @if($product->updated_at)<lastmod>{{ $product->updated_at->toAtomString() }}</lastmod>@endif
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
@endforeach
</urlset>
