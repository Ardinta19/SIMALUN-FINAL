<?php

namespace App\Http\Controllers;

use App\Models\RentalOrder;
use App\Models\RentalOrderUnit;
use App\Models\RentalProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RentalFeedbackController extends Controller
{
    public function productLike(Request $request, RentalProduct $product)
    {
        abort_unless($product->is_active && $product->category?->is_active, 404);
        $data = $request->validate(['liked' => ['required', 'in:0,1']]);
        DB::transaction(function () use ($request, $product) {
            $product = RentalProduct::lockForUpdate()->findOrFail($product->id);
            abort_unless($product->is_active && $product->category?->is_active, 404);
            $likes = DB::table('rental_product_likes')->where('rental_product_id', $product->id)->where('user_id', $request->user()->id);
            if ($request->boolean('liked')) {
                if (! $likes->exists()) DB::table('rental_product_likes')->insert(['rental_product_id' => $product->id, 'user_id' => $request->user()->id, 'created_at' => now(), 'updated_at' => now()]);
            } else $likes->delete();
        }, 3);
        return back();
    }

    public function productRating(Request $request, RentalOrder $order, RentalOrderUnit $unit)
    {
        abort_unless($order->customer_id === $request->user()->id, 403);
        abort_unless($unit->rental_order_id === $order->id, 404);
        $data = $request->validate(['rating' => ['required', 'integer', 'min:1', 'max:5']]);
        DB::transaction(function () use ($unit, $request, $data) {
            $unit = RentalOrderUnit::lockForUpdate()->findOrFail($unit->id);
            if ($unit->status !== 'confirmed' || $unit->progress !== 'returned') throw ValidationException::withMessages(['rating' => 'Rating produk tersedia setelah unit selesai dan dikembalikan.']);
            if (DB::table('rental_product_reviews')->where('rental_order_unit_id', $unit->id)->exists()) throw ValidationException::withMessages(['rating' => 'Unit ini sudah diberi rating.']);
            DB::table('rental_product_reviews')->insert(['rental_order_unit_id' => $unit->id, 'rental_product_id' => $unit->rental_product_id, 'user_id' => $request->user()->id, 'rating' => $data['rating'], 'created_at' => now(), 'updated_at' => now()]);
        }, 3);
        return back()->with('success', 'Terima kasih, rating produk telah disimpan.');
    }

    public function orderRating(Request $request, RentalOrder $order)
    {
        abort_unless($order->customer_id === $request->user()->id, 403);
        $data = $request->validate(['rating' => ['required', 'integer', 'min:1', 'max:5']]);
        DB::transaction(function () use ($order, $request, $data) {
            $order = RentalOrder::lockForUpdate()->findOrFail($order->id);
            $units = $order->units()->lockForUpdate()->get();
            if (! $units->contains(fn ($u) => $u->status === 'confirmed' && $u->progress === 'returned') || $units->contains(fn ($u) => $u->status !== 'cancelled' && ! ($u->status === 'confirmed' && $u->progress === 'returned'))) throw ValidationException::withMessages(['rating' => 'Rating pesanan tersedia setelah seluruh unit selesai atau dibatalkan, dan setidaknya satu unit dikembalikan.']);
            if (DB::table('rental_order_reviews')->where('rental_order_id', $order->id)->exists()) throw ValidationException::withMessages(['rating' => 'Pesanan ini sudah diberi rating.']);
            DB::table('rental_order_reviews')->insert(['rental_order_id' => $order->id, 'user_id' => $request->user()->id, 'rating' => $data['rating'], 'created_at' => now(), 'updated_at' => now()]);
        }, 3);
        return back()->with('success', 'Terima kasih, rating pesanan telah disimpan.');
    }

    public function share(Request $request, RentalOrder $order)
    {
        abort_unless($order->customer_id === $request->user()->id, 403);
        $data = $request->validate(['enabled' => ['required', 'in:0,1']]);
        DB::transaction(function () use ($request, $order) {
            RentalOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $shares = DB::table('rental_order_like_shares')->where('rental_order_id', $order->id);
            if ($request->boolean('enabled')) {
                if (! $shares->exists()) DB::table('rental_order_like_shares')->insert(['rental_order_id' => $order->id, 'token' => Str::random(64), 'created_at' => now(), 'updated_at' => now()]);
            } else $shares->delete();
        }, 3);
        return back()->with('success', $request->boolean('enabled') ? 'Tautan suka publik siap dibagikan.' : 'Tautan suka publik dinonaktifkan.');
    }

    public function publicShare(string $token)
    {
        $share = DB::table('rental_order_like_shares')->where('token', $token)->first();
        abort_unless($share, 404);
        $likes = DB::table('rental_order_likes')->where('rental_order_id', $share->rental_order_id)->count();
        $liked = auth()->check() && DB::table('rental_order_likes')->where('rental_order_id', $share->rental_order_id)->where('user_id', auth()->id())->exists();
        return response()->view('rental.public-like', compact('token', 'likes', 'liked'))->header('Cache-Control', 'private, no-store')->header('Referrer-Policy', 'no-referrer')->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function orderLike(Request $request, string $token)
    {
        $data = $request->validate(['liked' => ['required', 'in:0,1']]);
        DB::transaction(function () use ($request, $token, $data) {
            $share = DB::table('rental_order_like_shares')->where('token', $token)->lockForUpdate()->first();
            abort_unless($share, 404);
            $likes = DB::table('rental_order_likes')->where('rental_order_id', $share->rental_order_id)->where('user_id', $request->user()->id);
            if ($request->boolean('liked')) {
                if (! $likes->exists()) DB::table('rental_order_likes')->insert(['rental_order_id' => $share->rental_order_id, 'user_id' => $request->user()->id, 'created_at' => now(), 'updated_at' => now()]);
            } else $likes->delete();
        }, 3);
        return back();
    }
}
