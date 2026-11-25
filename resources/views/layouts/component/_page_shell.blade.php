{{-- Pengaman tata letak halaman mandiri: potong luapan mendatar, izinkan anak
     grid/flex menyusut, dan batasi media/tabel. Dipakai halaman yang punya
     <head> sendiri (auth, dasbor admin mandiri, alamat/profil, lembar cetak,
     halaman galat) supaya perilakunya sama dengan halaman ber-layout. --}}
<link rel="stylesheet" href="{{ asset('css/berkah-page-shell.css') }}?v={{ file_exists(public_path('css/berkah-page-shell.css')) ? filemtime(public_path('css/berkah-page-shell.css')) : '1' }}">
