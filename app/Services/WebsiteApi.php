<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class WebsiteApi
{
    protected static string $baseUrl = 'https://labguard.it.com/api';

    public static function token(): ?string
    {
        return Cache::get('auth_token') ?? session('auth_token');
    }

    public static function setToken(string $token): void
    {
        Cache::forever('auth_token', $token);
        session(['auth_token' => $token]);
    }

    public static function clearToken(): void
    {
        Cache::forget('auth_token');
        session()->forget('auth_token');
    }

    public static function client()
    {
        $token = self::token();

        // 1. Initialize client
        $http = Http::baseUrl(self::$baseUrl)
            ->withoutVerifying() // <-- BYPASS SELF-SIGNED SSL FOR LOCAL DEV
            ->acceptJson();

        // 2. Attach Sanctum token if present
        if ($token) {
            $http->withToken($token);
        }

        return $http;
    }
}
