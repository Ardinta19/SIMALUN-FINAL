<?php

/**
 * Terjemahan untuk kunci `status` yang dikirim lewat session.
 *
 * Controller mengirim kunci pendek (`profile-updated`) karena view memeriksa
 * kunci itu untuk menampilkan pesan di tempat tertentu. Toast di kerangka admin
 * mencetak isi session apa adanya, jadi tanpa terjemahan ini admin melihat
 * tulisan "profile-updated" di layar.
 */
return [
    'profile-updated' => 'Profil berhasil diperbarui.',
    'avatar-deleted' => 'Foto profil dihapus.',
    'address-saved' => 'Alamat berhasil disimpan.',
    'password-updated' => 'Kata sandi berhasil diperbarui.',
    'verification-link-sent' => 'Tautan verifikasi baru sudah dikirim.',
];
