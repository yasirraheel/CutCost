<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'currentPassword' => ['required', 'string'],
            'newPassword' => ['required', 'string', 'min:8'],
            'confirmPassword' => ['required', 'same:newPassword'],
        ]);

        if (! Hash::check($validated['currentPassword'], $request->user()->password)) {
            return $this->settingsError('The current password is incorrect.', 'security');
        }

        $request->user()->forceFill([
            'password' => $validated['newPassword'],
        ])->save();

        return redirect('/en/my-settings#security')
            ->with('status', 'Your password has been updated.')
            ->with('settings_tab', 'security');
    }

    public function updatePreferences(Request $request)
    {
        $validated = $request->validate([
            'referral_code' => ['nullable', 'string', 'max:255'],
            'lang' => ['required', 'string', 'max:10', Rule::in($this->languages())],
            'payout_settings' => ['nullable', 'array'],
            'payout_settings.*' => ['integer', Rule::in([1, 2])],
        ]);

        $settings = array_map('intval', $validated['payout_settings'] ?? []);
        $request->user()->forceFill([
            'referral_code' => $validated['referral_code'] ?? null,
            'language' => $validated['lang'],
            'payout_low_amount' => in_array(1, $settings, true),
            'payout_convert_currency' => in_array(2, $settings, true),
        ])->save();

        return redirect('/en/my-settings#settings')
            ->with('status', 'Your preferences have been saved.')
            ->with('settings_tab', 'preferences');
    }

    public function deleteAccount(Request $request)
    {
        $validated = $request->validate([
            'confirmation' => ['required', 'in:Delete'],
        ]);

        if ($validated['confirmation'] !== 'Delete') {
            return $this->settingsError('Type Delete to confirm account deletion.', 'delete');
        }

        $user = $request->user();
        Auth::logout();
        $user->delete();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/en/');
    }

    public function revokeDevice(Request $request)
    {
        $validated = $request->validate([
            'session_id' => ['required', 'string', 'max:255'],
        ]);

        if ($validated['session_id'] === $request->session()->getId()) {
            return $this->settingsError('The current device cannot be revoked here. Use Logout instead.', 'security');
        }

        DB::table('sessions')
            ->where('id', $validated['session_id'])
            ->where('user_id', $request->user()->id)
            ->delete();

        return redirect('/en/my-settings#security')
            ->with('status', 'The selected device has been revoked.')
            ->with('settings_tab', 'security');
    }

    /** @return list<string> */
    public function languages(): array
    {
        return [
            'nb', 'da', 'de', 'de-CH', 'et', 'en', 'en-AU', 'en-CA', 'en-IE',
            'en-IS', 'en-MT', 'es', 'fr', 'fr-BE', 'fr-CA', 'fr-CH', 'fr-LU',
            'hr', 'it', 'lv', 'lt', 'hu', 'nl', 'nl-BE', 'pl', 'pt', 'ro',
            'sk', 'sl', 'fi', 'sv', 'de-AT', 'cs', 'el', 'bg',
        ];
    }

    protected function settingsError(string $message, string $tab)
    {
        return redirect('/en/my-settings#'.$this->fragment($tab))
            ->withErrors(['settings' => $message])
            ->with('settings_tab', $tab);
    }

    protected function fragment(string $tab): string
    {
        return match ($tab) {
            'preferences' => 'settings',
            'delete' => 'delete',
            default => 'security',
        };
    }
}
