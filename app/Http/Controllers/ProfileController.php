<?php

namespace App\Http\Controllers;

use App\Models\CustomerAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = Auth::user();

        $primaryAddress = null;
        if ($user->role === 'customer') {
            $primaryAddress = $user->customerAddresses()
                ->where('is_primary', true)
                ->first();
        }

        $view = match ($user->role) {
            'admin' => 'roles.admin.profile_edit',
            default => 'roles.customer.profile.edit',
        };

        if (! view()->exists($view)) {
            $view = 'roles.customer.profile.edit';
        }

        return view($view, [
            'user' => $user,
            'primaryAddress' => $primaryAddress,
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        // Validasi keunikan harus menggunakan format yang benar-benar disimpan.
        $phone = $request->input('phone');
        if (is_string($phone)) {
            $phone = preg_replace('/[\s\-\.]/', '', $phone);
            $request->merge(['phone' => preg_replace('/^(\+?62|0)/', '', $phone)]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => [
                'required',
                'string',
                'regex:/^8[0-9]{8,12}$/',
                Rule::unique('users')->ignore($user->id),
            ],
            'avatar' => ['nullable', 'image', 'mimetypes:image/jpeg,image/png,image/webp', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'phone.required' => 'Nomor HP wajib diisi.',
            'phone.regex' => 'Format nomor HP tidak valid (contoh: 0812 3456 7890).',
            'phone.unique' => 'Nomor HP sudah dipakai akun lain.',
        ]);

        $newAvatar = null;
        if ($request->hasFile('avatar')) {
            $newAvatar = $request->file('avatar')->store('avatars', 'public');
            if (! $newAvatar) throw ValidationException::withMessages(['avatar' => 'Foto tidak dapat disimpan. Silakan coba lagi.']);
            $validated['avatar'] = $newAvatar;
        }

        $oldAvatar = $user->avatar;
        $emailChanged = array_key_exists('email', $validated) && $validated['email'] !== $user->email;
        try {
            DB::transaction(function () use ($user, $validated, $emailChanged) {
                $user->fill($validated);
                if ($emailChanged) $user->email_verified_at = null;
                $user->save();
            });
        } catch (\Throwable $e) {
            if ($newAvatar) Storage::disk('public')->delete($newAvatar);
            throw $e;
        }
        if ($newAvatar && $oldAvatar && $oldAvatar !== $newAvatar
            && preg_match('~^avatars/[A-Za-z0-9_-]+\.(?:jpg|jpeg|png|webp)$~i', $oldAvatar)
            && ! \App\Models\User::withTrashed()->where('avatar', $oldAvatar)->exists()) {
            Storage::disk('public')->delete($oldAvatar);
        }

        return back()->with('status', 'profile-updated');
    }

    public function deleteAvatar()
    {
        $user = Auth::user();

        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->update(['avatar' => null]);

        return back()->with('status', 'avatar-deleted');
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = Auth::user();
        Auth::logout();
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function customerShow()
    {
        $user = Auth::user();

        $primaryAddress = $user->customerAddresses()
            ->where('is_primary', true)
            ->first();

        $totalAddresses = $user->customerAddresses()->count();

        return view('roles.customer.profile.show', compact('user', 'primaryAddress', 'totalAddresses'));
    }

    public function adminShow()
    {
        return view('roles.admin.profile', [
            'user' => Auth::user(),
            'rentalPending' => \App\Models\RentalOrderUnit::where('status', 'pending')->count(),
            'rentalActive' => \App\Models\RentalOrderUnit::where('status', 'confirmed')->where('progress', '!=', 'returned')->count(),
        ]);
    }

    public function savePrimaryAddress(Request $request)
    {
        $user = Auth::user();

        abort_unless($user->role === 'customer', 403);

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:80'],
            'recipient_name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'full_address' => ['required', 'string', 'min:10', 'max:1000'],
            'distance_km' => ['nullable', 'numeric', 'min:0', 'max:50'],
            'notes' => ['nullable', 'string', 'max:300'],
        ]);

        $validated['zone'] = 'A';
        $validated['is_primary'] = true;

        $primary = $user->customerAddresses()->where('is_primary', true)->first();

        if ($primary) {
            $primary->update($validated);
        } else {
            $user->customerAddresses()->where('is_primary', true)->update(['is_primary' => false]);
            $validated['customer_id'] = $user->id;
            CustomerAddress::create($validated);
        }

        return back()->with('status', 'address-saved');
    }
}
