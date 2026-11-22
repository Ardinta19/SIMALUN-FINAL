@php
$oldText = function (string $key, $fallback = '') {
    $value = old($key, $fallback);
    return is_scalar($value) ? (string) $value : '';
};
@endphp
<label>Kategori<select name="rental_category_id" required><option value="">Pilih kategori</option>@foreach($categories as $category)<option value="{{ $category->id }}" @disabled(! $category->is_active) @selected((string) $oldText('rental_category_id', $product?->rental_category_id) === (string) $category->id)>{{ $category->name }}{{ $category->is_active ? '' : ' (nonaktif)' }}</option>@endforeach</select></label>
<label>Nama papan<input name="name" value="{{ $oldText('name', $product?->name) }}" maxlength="160" required></label>
<label>Ukuran<input name="size" value="{{ $oldText('size', $product?->size) }}" maxlength="100"></label>
<label>Deskripsi<textarea name="description" maxlength="3000">{{ $oldText('description', $product?->description) }}</textarea></label>
<div class="row"><label>Harga dasar (Rp)<input name="base_price" type="number" min="0" max="100000000" value="{{ $oldText('base_price', $product?->base_price) }}" required></label><label>Perhitungan<select name="price_unit" required><option value="day" @selected($oldText('price_unit', $product?->price_unit) === 'day')>Per hari</option><option value="event" @selected($oldText('price_unit', $product?->price_unit) === 'event')>Per acara</option></select></label><label>Jumlah unit<input name="stock" type="number" min="0" max="10000" value="{{ $oldText('stock', $product?->stock ?? 1) }}" required></label></div>
<label>Foto contoh (JPG, PNG, WebP; maks. 5 MB)<input name="image" type="file" accept="image/jpeg,image/png,image/webp"></label>
