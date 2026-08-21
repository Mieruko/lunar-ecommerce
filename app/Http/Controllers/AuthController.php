<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function showLogin() { return view('store.auth', ['mode' => 'login']); }
    public function showRegister() { return view('store.auth', ['mode' => 'register']); }
    public function login(Request $request) {
        $data = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        if (! Auth::attempt($data, $request->boolean('remember'))) return back()->withErrors(['email' => 'Email hoặc mật khẩu không đúng.'])->onlyInput('email');
        $request->session()->regenerate(); return redirect()->intended(route('account.dashboard'));
    }
    public function register(Request $request) {
        $data = $request->validate(['name' => ['required', 'string', 'max:150'], 'email' => ['required', 'email', 'unique:users'], 'phone' => ['nullable', 'string', 'max:30', 'unique:users'], 'password' => ['required', 'confirmed', Password::min(8)]]);
        $user = User::create($data); Auth::login($user); $request->session()->regenerate(); return redirect()->route('account.dashboard');
    }
    public function logout(Request $request) { Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken(); return redirect()->route('home'); }
}
