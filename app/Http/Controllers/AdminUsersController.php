<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RendersAdminShell;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AdminUsersController extends Controller
{
    use RendersAdminShell;

    public function index()
    {
        $users = User::with('kycSubmission')->latest()->paginate(20);

        return $this->adminShell(view('admin.users.index', compact('users'))->render(), 'Users', '/en/admin/users');
    }

    public function edit(User $user)
    {
        return $this->adminShell(view('admin.users.edit', compact('user'))->render(), 'Edit user', '/en/admin/users');
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:255', Rule::unique('users', 'username')->ignore($user->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in(['user', 'admin'])],
            'birthdate' => ['nullable', 'date', 'before_or_equal:today'],
            'phone' => ['nullable', 'string', 'max:50'],
            'intl_phone' => ['nullable', 'string', 'max:50'],
            'address1' => ['nullable', 'string', 'max:255'],
            'address2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'zip' => ['nullable', 'string', 'max:30'],
            'state' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'size:2'],
            'referral_code' => ['nullable', 'string', 'max:255'],
            'language' => ['required', 'string', 'max:10'],
            'payout_low_amount' => ['nullable', 'boolean'],
            'payout_convert_currency' => ['nullable', 'boolean'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        if ($user->is($request->user()) && $validated['role'] !== 'admin') {
            return back()->withInput()->withErrors(['role' => 'You cannot remove your own administrator role.']);
        }

        $user->forceFill([
            'name' => trim($validated['first_name'].' '.$validated['last_name']),
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'username' => $validated['username'] ?? null,
            'email' => $validated['email'],
            'role' => $validated['role'],
            'birthdate' => $validated['birthdate'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'intl_phone' => $validated['intl_phone'] ?? null,
            'address1' => $validated['address1'] ?? null,
            'address2' => $validated['address2'] ?? null,
            'city' => $validated['city'] ?? null,
            'zip' => $validated['zip'] ?? null,
            'state' => $validated['state'] ?? null,
            'country' => $validated['country'] ?? null,
            'referral_code' => $validated['referral_code'] ?? null,
            'language' => $validated['language'],
            'payout_low_amount' => $request->boolean('payout_low_amount'),
            'payout_convert_currency' => $request->boolean('payout_convert_currency'),
        ]);

        if (filled($validated['password'] ?? null)) {
            $user->password = $validated['password'];
        }

        $user->save();

        return redirect()->route('admin.users.edit', $user)->with('status', 'User information has been updated.');
    }

    public function destroy(User $user)
    {
        if ($user->is(Auth::user())) {
            return back()->withErrors(['user' => 'You cannot delete your own administrator account.']);
        }

        $user->delete();

        return redirect()->route('admin.users')->with('status', 'User has been deleted.');
    }
}
