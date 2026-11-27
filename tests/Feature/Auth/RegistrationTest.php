<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_registrasi_dapat_ditampilkan(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_user_baru_dapat_registrasi_dengan_data_lengkap(): void
    {
        $response = $this->post('/register', [
            'name' => 'Pengguna Tes',
            'gender' => 'L',
            'phone' => '81234567890',
            'email' => 'tes@example.com',
            'password' => 'rahasia12345',
            'password_confirmation' => 'rahasia12345',
            'address' => 'Jl. Mawar No. 7, Mayang Mangurai',
            'address_note' => 'Pagar hitam',
            'terms' => true,
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('customer.dashboard'));

        // Alamat utama awal otomatis dibuat di customer_addresses.
        $this->assertDatabaseHas('customer_addresses', [
            'full_address' => 'Jl. Mawar No. 7, Mayang Mangurai',
            'is_primary' => true,
        ]);
    }

    public function test_registrasi_gagal_tanpa_phone_atau_terms(): void
    {
        $response = $this->from('/register')->post('/register', [
            'name' => 'Tanpa Phone',
            'gender' => 'L',
            'email' => 'noo@example.com',
            'password' => 'rahasia12345',
            'password_confirmation' => 'rahasia12345',
            'address' => 'Jl. Apa No. 1',
        ]);

        $response->assertSessionHasErrors(['phone', 'terms']);
        $this->assertGuest();
    }
    public function test_soft_deleted_account_identifiers_cannot_cause_a_database_error_on_registration(): void
    {
        $old = User::factory()->create(['phone' => '81234567890', 'email' => 'old@example.com']);
        $old->delete();
        $this->from('/register')->post('/register', [
            'name' => 'Pengguna Tes', 'gender' => 'L', 'phone' => '081234567890',
            'email' => 'old@example.com', 'password' => 'rahasia12345', 'password_confirmation' => 'rahasia12345',
            'address' => 'Jl. Mawar No. 7, Jambi', 'terms' => true,
        ])->assertRedirect('/register')->assertSessionHasErrors([
            // Pesan harus menyebut akun yang dihapus, bukan menyuruh masuk ke
            // akun yang sudah tidak bisa dipakai.
            'phone' => 'Nomor HP ini tercatat pada akun yang sudah dihapus. Hubungi admin untuk memulihkan akunmu.',
            'email' => 'Email ini tercatat pada akun yang sudah dihapus. Hubungi admin untuk memulihkan akunmu.',
        ]);
        $this->assertGuest();
    }
}
