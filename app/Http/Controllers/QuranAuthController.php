<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class QuranAuthController extends Controller
{
    /**
     * Step 1: Redirect the user to the Quran Foundation login page.
     */
    public function redirect(Request $request)
    {
        $state = Str::random(40);
        $request->session()->put('oauth_state', $state);

        $query = http_build_query([
            'client_id' => config('services.quran.client_id'),
            'redirect_uri' => config('services.quran.redirect'),
            'response_type' => 'code',
            'scope' => 'openid profile email', // Adjust based on Quran Foundation scopes
            'state' => $state,
        ]);

        return redirect('https://oauth2.quran.foundation/oauth2/auth?'.$query);
    }

    /**
     * Step 2: Handle the Callback from the Quran Foundation
     */
    public function callback(Request $request)
    {
        // Security check against Cross-Site Request Forgery (CSRF)
        $savedState = $request->session()->pull('oauth_state');
        if (! $savedState || $savedState !== $request->state) {
            abort(403, 'Invalid state parameter.');
        }

        if ($request->has('error')) {
            return redirect(route('filament.admin.auth.login'))->with('error', 'Authentication rejected.');
        }

        // 1. Exchange the Authorization Code for an Access Token
        $tokenResponse = Http::asForm()->post('https://oauth2.quran.foundation/oauth2/token', [
            'grant_type' => 'authorization_code',
            'client_id' => config('services.quran.client_id'),
            'client_secret' => config('services.quran.client_secret'),
            'redirect_uri' => config('services.quran.redirect'),
            'code' => $request->code,
        ]);

        if ($tokenResponse->failed()) {
            return redirect(route('filament.admin.auth.login'))->with('error', 'Failed to retrieve access token.');
        }

        $accessToken = $tokenResponse->json()['access_token'];

        // 2. Fetch the User's Profile Info (OpenID Connect UserInfo endpoint)
        $userResponse = Http::withToken($accessToken)
            ->get('https://oauth2.quran.foundation/oidc/userinfo');

        if ($userResponse->failed()) {
            return redirect(route('filament.admin.auth.login'))->with('error', 'Failed to fetch user profile.');
        }

        $userData = $userResponse->json();

        // 3. Find or Create the User in your Database
        // Note: Make sure your User model allows mass assignment for these fields
        $user = User::updateOrCreate(
            ['email' => $userData['email']],
            [
                'name' => $userData['name'] ?? 'Quran User',
                // Generate a random secure password since they login via OAuth
                'password' => bcrypt(Str::random(24)),
                'email_verified_at' => now(),
                'role' => UserRole::Researcher,
            ]
        );

        // 4. Log the user into Filament
        Auth::login($user);

        // 5. Redirect them to the Filament Dashboard
        return redirect()->intended('/admin');
    }
}
