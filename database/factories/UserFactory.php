<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Terapkan atribut hak istimewa (`role`, `is_active`,
     * `email_verified_at`) dengan `forceFill`.
     *
     * `User::$fillable` sengaja tidak memuat ketiga kolom itu supaya tidak
     * ada `User::create($request->all())` yang diam-diam membuat admin.
     * Efek sampingnya: `User::factory()->create(['role' => 'admin'])` —
     * pola yang dipakai puluhan tes — **diam-diam diabaikan** oleh mass
     * assignment, sehingga tes yang berniat menguji halaman admin
     * sebenarnya berjalan sebagai pelanggan dan berakhir 403.
     *
     * Factory adalah kode tes, bukan jalur masuk pengguna, jadi di sini
     * ketiga kolom itu diterapkan secara eksplisit. `fill()` tetap dipakai
     * untuk kolom biasa sehingga aturan fillable lainnya tidak dilanggar.
     *
     * @param  array<string, mixed>  $attributes
     * @return User
     */
    public function newModel(array $attributes = [])
    {
        $istimewa = array_intersect_key($attributes, array_flip(['role', 'is_active', 'email_verified_at']));

        $user = new User;
        $user->forceFill($istimewa);
        $user->fill(array_diff_key($attributes, $istimewa));

        return $user;
    }

    /** Akun admin untuk pengujian halaman panel. */
    public function admin(): static
    {
        return $this->state(['role' => 'admin']);
    }

    /** Akun pelanggan (bawaan skema). */
    public function customer(): static
    {
        return $this->state(['role' => 'customer']);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
