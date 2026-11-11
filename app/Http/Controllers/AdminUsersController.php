<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class AdminUsersController extends Controller
{
    public function index()
    {
        $users = User::with('kycSubmission')->latest()->paginate(20);

        return $this->adminShell(view('admin.users.index', compact('users'))->render(), 'Users');
    }

    public function edit(User $user)
    {
        return $this->adminShell(view('admin.users.edit', compact('user'))->render(), 'Edit user');
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

    protected function adminShell(string $content, string $title): Response
    {
        $response = app(AdminPanelController::class)->show('dashboard');
        $html = (string) $response->getContent();
        $containerStart = strpos($html, '<div class="container-fluid dashboard-container">');
        $columnMarker = '<div class="col-12 col-lg-8">';
        $columnStart = $containerStart === false ? false : strpos($html, $columnMarker, $containerStart);

        if ($columnStart !== false) {
            $columnEnd = $this->matchingDivEnd($html, $columnStart);
            if ($columnEnd !== false) {
                $html = substr($html, 0, $columnStart).$columnMarker.$content.substr($html, $columnEnd);
            }
        }

        $html = preg_replace(
            '/(<h1\b[^>]*\bclass="dashboard-header-title"[^>]*>\s*Admin panel · )<span class="text-green">.*?<\/span>/i',
            '$1<span class="text-green">'.e($title).'</span>',
            $html,
            1,
        ) ?? $html;
        $html = preg_replace(
            '/(<div class="dashboard-header-actions-title">\s*)Admin dashboard(\s*<\/div>)/i',
            '$1'.e($title).'$2',
            $html,
            1,
        ) ?? $html;
        $html = preg_replace(
            '/<a class="navi_dash-link" href="\/en\/admin\/users">/',
            '<a class="navi_dash-link active" href="/en/admin/users">',
            $html,
            1,
        ) ?? $html;

        return response($html)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }

    protected function matchingDivEnd(string $html, int $start): int|false
    {
        preg_match_all('/<\/?div\b[^>]*>/i', $html, $tags, PREG_OFFSET_CAPTURE, $start);
        $depth = 0;

        foreach ($tags[0] as [$tag, $offset]) {
            $depth += str_starts_with(strtolower($tag), '</div') ? -1 : 1;
            if ($depth === 0) {
                return $offset + strlen($tag);
            }
        }

        return false;
    }
}
