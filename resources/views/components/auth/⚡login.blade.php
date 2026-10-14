<?php

namespace App\Livewire\Auth;

use Livewire\Component; // <-- MUST BE Livewire\Component
use App\Services\WebsiteApi;

class Login extends Component
{
    public $email = '';
    public $password = '';
    public $remember = false;

    public function login()
    {
        $this->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $response = WebsiteApi::client()->post('/login', [
            'email' => $this->email,
            'password' => $this->password,
        ]);

        if ($response->successful()) {
            $data = $response->json();

            if (!empty($data['requires_2fa'])) {
                session(['2fa_user_id' => $data['user_id']]);
                return redirect()->route('two-factor.login');
            }

            WebsiteApi::setToken($data['token']);
            session(['user' => $data['user']]);

            return redirect()->route('profile.show');
        }

        $this->addError('email', $response->json('message') ?? 'Invalid credentials.');
    }

    public function render()
    {
        return view('auth.login')->layout('layouts.guest');
    }
}
