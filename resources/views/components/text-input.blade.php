@props(['disabled' => false])

{{-- Kelas utilitas Tailwind (`border-gray-300`, `focus:ring-indigo-500`) sudah
     tidak punya CSS sejak build Tailwind dicabut. Kontrol ini ditata oleh
     `.guest-card input` di `layouts.guest`; kelas kosong hanya menambah
     atribut `class=""` pada keluaran. --}}
<input @disabled($disabled) {{ $attributes }}>
