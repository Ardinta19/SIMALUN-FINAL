{{--
|--------------------------------------------------------------------------
| Berkah Kreatif — Strip berjalan: alur pemesanan + kanal kontak
|--------------------------------------------------------------------------
| Satu baris mendatar yang berisi lima langkah pemesanan (sesuai alur yang
| benar-benar berjalan di aplikasi) lalu tiga kanal kontak: Instagram, TikTok,
| dan WhatsApp. Baris ini bergerak ke kanan dan berhenti saat disentuh,
| diklik, atau difokuskan papan ketik; dua detik setelah interaksi terakhir
| ia lanjut sendiri. Pengendalinya `public/js/berkah-ribbon.js`.
|
| Tanpa JavaScript strip tetap terbaca: isinya deretan biasa yang bisa
| digulir mendatar, dan `prefers-reduced-motion` (atau `?gerak=mati`)
| membuatnya diam sama sekali.
|
| Kelas `bk-how` dipertahankan karena seluruh gaya langkah (termasuk tema
| gelap) sudah memakai selektor itu; `bk-ribbon` menambahkan tata letak
| berjalannya.
|--------------------------------------------------------------------------
--}}
@php
    $bkRibbonWa = \App\Support\WhatsApp::link(\App\Support\Laundry::whatsapp(), 'Halo, saya ingin bertanya soal sewa papan bunga.');
    $bkRibbonIg = \App\Support\Laundry::instagram();
    $bkRibbonTt = \App\Support\Laundry::tiktok();
    $bkRibbonSteps = [
        ['Pilih papan', 'Temukan bentuk yang cocok'],
        ['Atur tanggal & ucapan', 'Kirim sendiri atau diantar'],
        ['Admin konfirmasi', 'Jadwal dicek, ongkos disepakati'],
        ['Bayar & setujui desain', 'Unggah bukti, cek pratinjau'],
        ['Pantau sampai terpasang', 'Progres tampil di akunmu'],
    ];
@endphp
{{-- Isi strip sengaja digandakan oleh JavaScript, bukan di sini: pembaca
     layar tidak boleh mendengar langkah yang sama dua kali. --}}
<div class="bk-how bk-ribbon" role="region" aria-label="Cara memesan dan kontak kami" data-bk-ribbon>
    <div class="bk-ribbon__viewport" data-bk-ribbon-viewport>
        <div class="bk-ribbon__track" data-bk-ribbon-track>
            <div class="bk-ribbon__group" data-bk-ribbon-group>
                @foreach($bkRibbonSteps as $i => $step)
                    <div class="bk-ribbon__step">
                        <span>{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                        <p><strong>{{ $step[0] }}</strong><small>{{ $step[1] }}</small></p>
                    </div>
                @endforeach
                <span class="bk-ribbon__sep" aria-hidden="true"></span>
                @if($bkRibbonIg)
                    <a class="bk-ribbon__link" href="{{ $bkRibbonIg }}" target="_blank" rel="noopener noreferrer">
                        <span class="bk-ribbon__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="5"></rect><circle cx="12" cy="12" r="4"></circle><circle cx="17.2" cy="6.8" r="1" fill="currentColor" stroke="none"></circle></svg></span>
                        <span class="bk-ribbon__text"><strong>Instagram</strong><small>@berkahkreatif_</small></span>
                    </a>
                @endif
                @if($bkRibbonTt)
                    <a class="bk-ribbon__link" href="{{ $bkRibbonTt }}" target="_blank" rel="noopener noreferrer">
                        <span class="bk-ribbon__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M16.5 3h-2.6v10.9a2.6 2.6 0 1 1-2.2-2.6V8.6a5.4 5.4 0 1 0 4.8 5.4V8.4a6.2 6.2 0 0 0 3.5 1.1V6.9a3.6 3.6 0 0 1-3.5-3.9Z"></path></svg></span>
                        <span class="bk-ribbon__text"><strong>TikTok</strong><small>@berkahkreatif_</small></span>
                    </a>
                @endif
                @if($bkRibbonWa)
                    <a class="bk-ribbon__link" href="{{ $bkRibbonWa }}" target="_blank" rel="noopener noreferrer">
                        <span class="bk-ribbon__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.5 15.2L2 22l4.9-1.4A10 10 0 1 0 12 2Zm0 2a8 8 0 1 1-4.2 14.8l-.4-.2-2.6.7.7-2.5-.2-.4A8 8 0 0 1 12 4Zm-3 3.9c-.2 0-.5.1-.7.4-.3.3-.9 1-.9 2s.7 2 1 2.4c.3.4 1.6 2.4 3.9 3.3 1.9.8 2.3.6 2.7.6.4 0 1.3-.5 1.5-1.1.2-.6.2-1 .1-1.1l-1.1-.5c-.2-.1-.4-.1-.5.1l-.7.9c-.1.1-.3.2-.5.1a6.6 6.6 0 0 1-1.9-1.2 7.4 7.4 0 0 1-1.3-1.6c-.1-.2 0-.3.1-.5l.5-.6c.1-.2.1-.3 0-.5l-.6-1.4c-.1-.3-.2-.4-.3-.4h-.3Z"></path></svg></span>
                        <span class="bk-ribbon__text"><strong>WhatsApp</strong><small>{{ \App\Support\Laundry::phoneDisplay() }}</small></span>
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>
