<?php
class AuthController extends Controller
{
    /** Login lives in the landing page modal; this opens it. */
    public const LOGIN_URL = '/?login=1';

    /** Old /login links: there is no separate login page, open the modal. */
    public function showLogin(): void
    {
        $this->redirect(Auth::check() ? '/home' : self::LOGIN_URL);
    }

    public function login(): void
    {
        $data = $this->only(['email', 'username', 'password']);
        $data['identifier'] = trim((string) ($data['email'] ?? '')) ?: trim((string) ($data['username'] ?? ''));
        $back = self::LOGIN_URL;

        if (Auth::lockedOut()) {
            flash('danger', 'Too many failed attempts. Please wait a few minutes and try again.');
            $this->redirect($back);
        }

        $clean = $this->validate($data, [
            'identifier' => 'required|max:100',
            'password' => 'required',
        ], $back, ['identifier' => 'Username']);

        if (!Auth::attempt($clean['identifier'], (string) $data['password'])) {
            flash('danger', 'Invalid username or password.');
            flash_old(['email' => $clean['identifier'], 'username' => $clean['identifier']]);
            $this->redirect($back);
        }

        // One-time flag: the next dashboard page plays the entry animation
        $_SESSION['just_logged_in'] = true;
        $this->redirect('/home');
    }

    public function logout(): void
    {
        Auth::logout();
        // Back to the landing page with the login modal open
        $this->redirect(self::LOGIN_URL);
    }
}
