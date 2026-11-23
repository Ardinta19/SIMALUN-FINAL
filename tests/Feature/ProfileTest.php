<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_edit_profil_dapat_ditampilkan(): void
    {
        $user = User::factory()->create([
            'role' => 'customer',
            'phone' => '81234567890',
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/profile/edit');

        $response->assertOk();
    }

    public function test_alamat_pendek_profile_mengarahkan_ke_profil_sesuai_peran(): void
    {
        // /profile hanya punya PATCH dan DELETE, jadi GET dulu berakhir 405.
        // Alamat pendek ini sering diketik langsung atau dibuka dari penanda.
        $customer = User::factory()->create([
            'role' => 'customer',
            'phone' => '81234567890',
        ]);
        $this->actingAs($customer)
            ->get('/profile')
            ->assertRedirect(route('customer.profile'));

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)
            ->get('/profile')
            ->assertRedirect(route('admin.profile'));
    }

    public function test_tamu_yang_membuka_profile_diarahkan_ke_halaman_masuk(): void
    {
        $this->get('/profile')->assertRedirect(route('login'));
    }

    public function test_profil_dapat_diperbarui_dengan_nama_email_dan_phone(): void
    {
        $user = User::factory()->create([
            'role' => 'customer',
            'phone' => '81234567890',
        ]);

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Nama Baru',
                'email' => 'baru@example.com',
                'phone' => '81299990000',
            ]);

        $response->assertSessionHasNoErrors();

        $user->refresh();

        $this->assertSame('Nama Baru', $user->name);
        $this->assertSame('baru@example.com', $user->email);
        $this->assertSame('81299990000', $user->phone);
        $this->assertNull($user->email_verified_at);
    }

    public function test_status_verifikasi_email_tidak_berubah_ketika_email_tidak_diubah(): void
    {
        $user = User::factory()->create([
            'role' => 'customer',
            'phone' => '81234567890',
        ]);

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
            ]);

        $response->assertSessionHasNoErrors();

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_dapat_menghapus_akunnya_sendiri(): void
    {
        $user = User::factory()->create([
            'role' => 'customer',
            'phone' => '81234567890',
        ]);

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        // User pakai SoftDeletes — terhapus berarti deleted_at terisi.
        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_password_salah_membatalkan_penghapusan_akun(): void
    {
        $user = User::factory()->create([
            'role' => 'customer',
            'phone' => '81234567890',
        ]);

        $response = $this
            ->actingAs($user)
            ->from('/profile/edit')
            ->delete('/profile', [
                'password' => 'salah-bukan-password',
            ]);

        $response->assertSessionHasErrors('password');
        $this->assertNotNull($user->fresh());
    }
    public function test_profile_pages_show_rental_navigation_and_accurate_account_notice(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($customer)->get(route('customer.profile'))->assertOk()->assertSee('Alamat tersimpan dapat dipilih saat checkout sewa')
            ->assertSee(route('profile.edit'))->assertSee(route('customer.addresses.create'))->assertSee(route('logout'))
            ->assertSee('Keluar dari akun')->assertSee('aria-label="Navigasi utama"', false)->assertDontSee('ui-avatars.com');
        $this->get(route('profile.edit'))->assertOk()->assertSee('Data pesanan dan catatan transaksi dapat tetap tersimpan')->assertSee('for="profile-name"', false)->assertSee('for="profile-new-password"', false)->assertSee('aria-label="Navigasi utama"', false)->assertDontSee('Semua data pesanan akan ikut terhapus')->assertDontSee('ui-avatars.com');
        $this->actingAs($admin)->get(route('admin.profile'))->assertOk()->assertSee('Berkah Kreatif')->assertSee('Menunggu konfirmasi')->assertSee('Unit aktif')->assertSee(route('admin.rental.orders'))->assertSee(route('admin.rental.cashbook.index'))->assertDontSee('Tugaskan kurir')->assertDontSee('ui-avatars.com');
        $this->get(route('profile.edit'))->assertOk()->assertSee('Berkah Kreatif')->assertDontSee('ui-avatars.com');
    }
    public function test_customer_profile_shows_only_their_primary_address_and_has_accessible_logout(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $other = User::factory()->create(['role' => 'customer']);
        $customer->customerAddresses()->create(['label' => 'Rumah utama', 'recipient_name' => 'Penerima', 'full_address' => 'Jalan Mawar 8', 'is_primary' => true]);
        $other->customerAddresses()->create(['label' => 'Alamat rahasia', 'recipient_name' => 'Lain', 'full_address' => 'Jalan Rahasia', 'is_primary' => true]);
        $this->actingAs($customer)->get(route('customer.profile'))->assertOk()->assertSee('Rumah utama')
            ->assertSee('Jalan Mawar 8')->assertDontSee('Jalan Rahasia')
            ->assertSee('Keluar dari akun')->assertSee('Ya, keluar')->assertSee(route('logout'));
    }

    public function test_phone_uniqueness_is_checked_after_normalization(): void
    {
        $owner = User::factory()->create(['phone' => '81234567890']);
        $other = User::factory()->create(['phone' => '81399990000']);
        $this->actingAs($other)->from('/profile/edit')->patch('/profile', ['name' => $other->name, 'email' => $other->email, 'phone' => '+62 812-3456-7890'])
            ->assertSessionHasErrors('phone');
        $this->assertSame('81399990000', $other->fresh()->phone);
        $this->actingAs($owner)->patch('/profile', ['name' => $owner->name, 'email' => $owner->email, 'phone' => '0812-3456-7890'])
            ->assertSessionHasNoErrors();
        $this->assertSame('81234567890', $owner->fresh()->phone);
    }

    public function test_new_avatar_is_saved_before_the_old_avatar_is_removed(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('avatars/old.jpg', 'old');
        $user = User::factory()->create(['phone' => '81234567890', 'avatar' => 'avatars/old.jpg']);
        $this->actingAs($user)->patch('/profile', ['name' => $user->name, 'email' => $user->email, 'phone' => $user->phone, 'avatar' => UploadedFile::fake()->image('new.png')])->assertSessionHasNoErrors();
        Storage::disk('public')->assertMissing('avatars/old.jpg');
        Storage::disk('public')->assertExists($user->fresh()->avatar);
    }

    public function test_profile_and_address_forms_render_safely_after_non_scalar_retry(): void
    {
        $user = User::factory()->create(['role' => 'customer', 'phone' => '81234567890']);

        $this->actingAs($user)->from('/profile/edit')->patch('/profile', ['name' => ['Budi'], 'phone' => '81234567890'])
            ->assertRedirect('/profile/edit')->assertSessionHasErrors('name');
        $this->get('/profile/edit')->assertOk()->assertDontSee('Array');

        $this->actingAs($user)->from(route('customer.addresses.create'))->post(route('customer.addresses.store'), [
            'label' => ['Rumah'], 'recipient_name' => ['Budi'], 'full_address' => ['Jalan panjang nomor satu'], 'latitude' => ['-3.8'],
        ])->assertRedirect(route('customer.addresses.create'))->assertSessionHasErrors(['label', 'recipient_name', 'full_address']);
        $this->get(route('customer.addresses.create'))->assertOk()->assertDontSee('Array')->assertSee('name="label"', false);
    }
}
