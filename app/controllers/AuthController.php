<?php
class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            $this->redirect('/home');
        }
        $this->view('auth/login', ['title' => 'Login'], 'auth');
    }

    public function login(): void
    {
        $data = $this->only(['email', 'username', 'password']);
        $data['identifier'] = trim((string) ($data['email'] ?? '')) ?: trim((string) ($data['username'] ?? ''));
        $back = '/login';

        if (Auth::lockedOut()) {
            flash('danger', 'Too many failed attempts. Please wait a few minutes and try again.');
            $this->redirect($back);
        }

        $clean = $this->validate($data, [
            'identifier' => 'required|max:100',
            'password' => 'required',
        ], $back);

        if (!Auth::attempt($clean['identifier'], (string) $data['password'])) {
            flash('danger', 'Invalid email or password.');
            flash_old(['email' => $clean['identifier'], 'username' => $clean['identifier']]);
            $this->redirect($back);
        }

        $this->redirect('/home');
    }

    public function logout(): void
    {
        Auth::logout();
        flash('success', 'You have been logged out.');
        $this->redirect('/login');
    }
}
