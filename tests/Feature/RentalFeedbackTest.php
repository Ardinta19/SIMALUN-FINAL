<?php

namespace Tests\Feature;

use App\Models\RentalCategory;
use App\Models\RentalOrder;
use App\Models\RentalProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class RentalFeedbackTest extends TestCase
{
    use RefreshDatabase;

    private function setupOrder(): array
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $other = User::factory()->create(['role' => 'customer']);
        $category = RentalCategory::create(['name' => 'Wisuda']);
        $product = RentalProduct::create(['rental_category_id' => $category->id, 'name' => 'Melati', 'base_price' => 100, 'price_unit' => 'day', 'stock' => 2]);
        $order = RentalOrder::create(['reference' => (string) Str::uuid(), 'customer_id' => $customer->id, 'confirmation_deadline' => now()->addDay()]);
        $unit = $order->units()->create(['rental_product_id' => $product->id, 'product_name' => 'Melati', 'unit_price' => 100, 'price_unit' => 'day', 'rental_amount' => 100, 'starts_on' => now()->addDays(3), 'event_on' => now()->addDays(3), 'ends_on' => now()->addDays(3), 'recipient_name' => 'Rahasia Penerima', 'greeting' => 'Ucapan rahasia', 'delivery_method' => 'delivery', 'delivery_address' => 'Alamat rahasia', 'return_method' => 'dropoff']);
        return [$customer, $other, $product, $order, $unit];
    }

    public function test_product_likes_are_idempotent_and_counts_are_public(): void
    {
        [$customer, $other, $product] = $this->setupOrder();
        $this->post(route('rental.product.like', $product), ['liked' => 1])->assertRedirect(route('login'));
        $this->actingAs($customer)->post(route('rental.product.like', $product), ['liked' => 1])->assertSessionHasNoErrors();
        $this->post(route('rental.product.like', $product), ['liked' => 1])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('rental_product_likes', 1);
        $this->get(route('catalog.show', $product))->assertOk()->assertSee('1 suka')->assertSee('aria-pressed="true"', false);
        $this->actingAs($other)->get(route('welcome'))->assertOk()->assertSee('1 suka')->assertSee('aria-pressed="false"', false);
        $this->actingAs($customer)->post(route('rental.product.like', $product), ['liked' => 0])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('rental_product_likes', 0);
    }

    public function test_only_owner_can_rate_returned_units_and_completed_orders_once(): void
    {
        [$customer, $other, $product, $order, $unit] = $this->setupOrder();
        $productUrl = route('rental.product.rating', [$order, $unit]);
        $orderUrl = route('rental.order.rating', $order);
        $this->actingAs($other)->post($productUrl, ['rating' => 5])->assertForbidden();
        $this->post($orderUrl, ['rating' => 5])->assertForbidden();
        $this->actingAs($customer)->post($productUrl, ['rating' => 5])->assertSessionHasErrors('rating');
        $this->post($orderUrl, ['rating' => 5])->assertSessionHasErrors('rating');
        $unit->update(['status' => 'confirmed', 'progress' => 'returned']);
        $this->get(route('rental.orders.unit', [$order, $unit]))->assertOk()->assertSee('Nilai produk ini')
            ->assertSee('name="rating"', false)->assertSee('rating-'.$unit->id.'-5', false)->assertSee('Simpan rating produk');
        $this->post($productUrl, ['rating' => 6])->assertSessionHasErrors('rating');
        $this->post($productUrl, ['rating' => 4])->assertSessionHasNoErrors();
        $this->post($productUrl, ['rating' => 5])->assertSessionHasErrors('rating');
        $this->post($orderUrl, ['rating' => 5])->assertSessionHasNoErrors();
        $this->post($orderUrl, ['rating' => 3])->assertSessionHasErrors('rating');
        $this->assertDatabaseCount('rental_product_reviews', 1);
        $this->assertDatabaseCount('rental_order_reviews', 1);
        $this->get(route('catalog.show', $product))->assertOk()->assertSee('4,0 / 5')->assertSee('1 rating');
    }

    public function test_order_rating_waits_for_every_unit_and_wrong_unit_cannot_be_rated(): void
    {
        [$customer, $other, $product, $order, $unit] = $this->setupOrder();
        $otherOrder = RentalOrder::create(['reference' => (string) Str::uuid(), 'customer_id' => $customer->id, 'confirmation_deadline' => now()->addDay()]);
        $unit->update(['status' => 'confirmed', 'progress' => 'returned']);
        $this->actingAs($customer)->post(route('rental.product.rating', [$otherOrder, $unit]), ['rating' => 5])->assertNotFound();
        $second = $order->units()->create(['rental_product_id' => $product->id, 'product_name' => 'Melati', 'unit_price' => 100, 'price_unit' => 'day', 'rental_amount' => 100, 'starts_on' => now()->addDays(3), 'event_on' => now()->addDays(3), 'ends_on' => now()->addDays(3), 'recipient_name' => 'Nama', 'greeting' => 'Selamat', 'delivery_method' => 'pickup', 'return_method' => 'dropoff']);
        $this->post(route('rental.order.rating', $order), ['rating' => 5])->assertSessionHasErrors('rating');
        $second->update(['status' => 'cancelled']);
        $this->post(route('rental.order.rating', $order), ['rating' => 5])->assertSessionHasNoErrors();
    }

    public function test_public_order_like_page_exposes_only_count_and_can_be_revoked(): void
    {
        [$customer, $other, $product, $order, $unit] = $this->setupOrder();
        $shareUrl = route('rental.order.share', $order);
        $this->actingAs($other)->post($shareUrl, ['enabled' => 1])->assertForbidden();
        $this->actingAs($customer)->post($shareUrl, ['enabled' => 1])->assertSessionHasNoErrors();
        $token = DB::table('rental_order_like_shares')->where('rental_order_id', $order->id)->value('token');
        $this->get(route('rental.orders.show', $order))->assertOk()->assertSee('data-copy-share', false)->assertSee('Salin tautan secara manual');
        $url = route('rental.likes.public', $token);
        $this->get($url)->assertOk()->assertHeader('Referrer-Policy', 'no-referrer')->assertSee('0 suka')
            ->assertSee('name="referrer" content="no-referrer"', false)->assertDontSee($order->reference)->assertDontSee('Rahasia Penerima')->assertDontSee('Ucapan rahasia')->assertDontSee('Alamat rahasia')->assertDontSee('Melati')->assertDontSee($customer->name);
        $this->actingAs($other)->post(route('rental.order.like', $token), ['liked' => 1])->assertSessionHasNoErrors();
        $this->post(route('rental.order.like', $token), ['liked' => 1])->assertSessionHasNoErrors();
        $this->get($url)->assertSee('1 suka');
        $this->actingAs($customer)->post($shareUrl, ['enabled' => 0])->assertSessionHasNoErrors();
        $this->get($url)->assertNotFound();
        $this->actingAs($other)->post(route('rental.order.like', $token), ['liked' => 1])->assertNotFound();
        $this->assertDatabaseCount('rental_order_likes', 1);
    }
}
