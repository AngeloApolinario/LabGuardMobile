<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Services\WebsiteApi;

/*
|--------------------------------------------------------------------------
| Web Routes (Mobile Client)
|--------------------------------------------------------------------------
*/

// Root redirect
Route::get('/', function () {
    return WebsiteApi::token()
        ? redirect()->route('profile.show')
        : redirect()->route('login');
});

// 1. Login Routes
Route::get('/login', function () {
    if (WebsiteApi::token()) {
        return redirect()->route('profile.show');
    }
    return view('auth.login');
})->name('login');

Route::post('/login', function (Request $request) {
    $request->validate([
        'email'    => 'required|email',
        'password' => 'required',
    ]);

    $response = WebsiteApi::client()->post('/login', [
        'email'    => $request->email,
        'password' => $request->password,
    ]);

    if ($response->successful()) {
        $data = $response->json();

        // 🔐 If 2FA is required by the website
        if (!empty($data['requires_2fa'])) {
            $userId = $data['user_id'] ?? $data['user']['id'] ?? $data['id'] ?? null;
            session([
                '2fa_user_id' => $userId,
                '2fa_user'    => $data['user'] ?? null,
            ]);

            return redirect()->route('two-factor.login');
        }

        // Standard Login: Store token & user data
        WebsiteApi::setToken($data['token']);
        session(['user' => $data['user'] ?? []]);

        return redirect()->route('profile.show');
    }

    return back()->withErrors([
        'email' => $response->json('message') ?? 'Invalid credentials.',
    ])->onlyInput('email');
})->name('login.post');

// 2. Two-Factor Challenge Routes (This was the missing route!)
Route::get('/two-factor-challenge', function () {
    if (! session()->has('2fa_user_id')) {
        return redirect()->route('login');
    }
    return view('auth.two-factor-challenge');
})->name('two-factor.login');

Route::post('/two-factor-challenge', function (Request $request) {
    $request->validate([
        'code'          => 'nullable|string',
        'recovery_code' => 'nullable|string',
    ]);

    // Send code to the website API for verification
    $response = WebsiteApi::client()->post('/two-factor/challenge', [
        'user_id'       => session('2fa_user_id'),
        'code'          => $request->code,
        'recovery_code' => $request->recovery_code,
    ]);

    if ($response->successful()) {
        $data = $response->json();

        // 1. Save Sanctum token
        WebsiteApi::setToken($data['token']);

        // 2. Save user session & clear temp 2FA data
        session(['user' => $data['user'] ?? session('2fa_user') ?? []]);
        session()->forget(['2fa_user_id', '2fa_user']);

        // 3. Go directly to profile
        return redirect()->route('profile.show');
    }

    return back()->withErrors([
        'code' => $response->json('message') ?? 'The provided two-factor authentication code was invalid.',
    ]);
})->name('two-factor.challenge.post');

// 3. Profile Screen
Route::get('/user/profile', function () {
    if (! WebsiteApi::token()) {
        return redirect()->route('login');
    }

    $response = WebsiteApi::client()->get('/user/profile');

    if (! $response->successful()) {
        WebsiteApi::clearToken();
        return redirect()->route('login');
    }

    $data = $response->json();
    $user = $data['user'] ?? $data;
    session(['user' => $user]);

    return view('profile.show', compact('user'));
})->name('profile.show');

// 4. Logout Route
Route::post('/logout', function () {
    WebsiteApi::client()->post('/logout');
    WebsiteApi::clearToken();
    session()->flush();

    return redirect()->route('login');
})->name('logout');
