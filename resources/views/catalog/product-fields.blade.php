@php
    $oldText = function (string $key, $fallback = '') {
        $value = old($key, $fallback);
        return is_scalar($value) ? (string) $value : '';
    };
    $fieldId = 'product-'.($product?->id ?? 'new');
@endphp
<div class="adm-field">
    <label for="{{ $fieldId }}-category">Kategori</label>
    <select id="{{ $fieldId }}-category" name="rental_category_id" required>
        <option value="">Pilih kategori</option>
        @foreach($categories as $category)
            <option value="{{ $category->id }}" @disabled(! $category->is_active) @selected((string) $oldText('rental_category_id', $product?->rental_category_id) === (string) $category->id)>{{ $category->name }}{{ $category->is_active ? '' : ' (nonaktif)' }}</option>
        @endforeach
    </select>
</div>
<div class="adm-field">
    <label for="{{ $fieldId }}-name">Nama papan</label>
    <input placeholder="Contoh: Papan bunga duka cita" id="{{ $fieldId }}-name" name="name" value="{{ $oldText('name', $product?->name) }}" maxlength="160" required>
</div>
<div class="adm-field">
    <label for="{{ $fieldId }}-size">Ukuran</label>
    <input placeholder="Contoh: 180 × 90 cm" id="{{ $fieldId }}-size" name="size" value="{{ $oldText('size', $product?->size) }}" maxlength="100">
</div>
<div class="adm-field">
    <label for="{{ $fieldId }}-description">Deskripsi</label>
    <textarea placeholder="Contoh: Rangkaian mawar putih dengan pita duka." id="{{ $fieldId }}-description" name="description" maxlength="3000">{{ $oldText('description', $product?->description) }}</textarea>
</div>
<div class="adm-field-grid">
    <div class="adm-field">
        <label for="{{ $fieldId }}-base">Harga dasar (Rp)</label>
        <input placeholder="Contoh: 450000" id="{{ $fieldId }}-base" name="base_price" type="number" inputmode="numeric" min="0" max="100000000" value="{{ $oldText('base_price', $product?->base_price) }}" required>
    </div>
    <div class="adm-field">
        <label for="{{ $fieldId }}-unit">Perhitungan</label>
        <select id="{{ $fieldId }}-unit" name="price_unit" required>
            <option value="day" @selected($oldText('price_unit', $product?->price_unit) === 'day')>Per hari</option>
            <option value="event" @selected($oldText('price_unit', $product?->price_unit) === 'event')>Per acara</option>
        </select>
    </div>
    <div class="adm-field">
        <label for="{{ $fieldId }}-stock">Jumlah unit</label>
        <input placeholder="Contoh: 5" id="{{ $fieldId }}-stock" name="stock" type="number" inputmode="numeric" min="0" max="10000" value="{{ $oldText('stock', $product?->stock ?? 1) }}" required>
    </div>
</div>
<div class="adm-field">
    <label for="{{ $fieldId }}-image">Foto contoh (JPG, PNG, WebP; maks. 5 MB)</label>
    <input id="{{ $fieldId }}-image" name="image" type="file" accept="image/jpeg,image/png,image/webp">
</div>
