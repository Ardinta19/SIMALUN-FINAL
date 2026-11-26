<div class="adm-field-grid">
    <div class="adm-field">
        <label for="cash-date-{{ $entry?->id ?? 'new' }}">Tanggal</label>
        <input id="cash-date-{{ $entry?->id ?? 'new' }}" type="date" name="entry_date" value="{{ $entry?->entry_date->toDateString() ?? now('Asia/Jakarta')->toDateString() }}" required>
    </div>
    <div class="adm-field">
        <label for="cash-type-{{ $entry?->id ?? 'new' }}">Jenis</label>
        <select id="cash-type-{{ $entry?->id ?? 'new' }}" name="entry_type">
            <option value="expense" @selected($entry?->entry_type === 'expense')>Pengeluaran</option>
            <option value="income" @selected($entry?->entry_type === 'income')>Pemasukan lain</option>
        </select>
    </div>
    <div class="adm-field">
        <label for="cash-cat-{{ $entry?->id ?? 'new' }}">Kategori bebas</label>
        <input id="cash-cat-{{ $entry?->id ?? 'new' }}" placeholder="Contoh: Bensin dan tol" name="category" maxlength="50" value="{{ $entry?->category }}" required>
    </div>
    <div class="adm-field">
        <label for="cash-amount-{{ $entry?->id ?? 'new' }}">Nominal (Rp)</label>
        <input id="cash-amount-{{ $entry?->id ?? 'new' }}" placeholder="Contoh: 150000" type="number" inputmode="numeric" name="amount" min="1" max="1000000000" value="{{ $entry?->amount }}" required>
    </div>
</div>
<div class="adm-field">
    <label for="cash-notes-{{ $entry?->id ?? 'new' }}">Catatan</label>
    <textarea id="cash-notes-{{ $entry?->id ?? 'new' }}" placeholder="Contoh: Bensin 100.000 dan tol 50.000 untuk antar unit." name="notes" maxlength="1000" required>{{ $entry?->notes }}</textarea>
</div>
<label class="adm-switch">
    <input type="checkbox" name="not_rental_payment" value="1">
    <span class="adm-switch__track" aria-hidden="true"></span>
    <span>Jika pemasukan: saya memastikan ini bukan pembayaran sewa yang sudah/akan dicatat melalui verifikasi.</span>
</label>
