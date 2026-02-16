<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    /**
     * Register a new user.
     */
    public function register(array $data): User
    {
        return User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
    }

    /**
     * Attempt to authenticate the user.
     */
    public function login(array $credentials): bool
    {
        return Auth::attempt($credentials);
    }

    /**
     * Log the user out.
     */
    public function logout(): void
    {
        Auth::guard('web')->logout();
        
        session()->invalidate();
        session()->regenerateToken();
    }
}
