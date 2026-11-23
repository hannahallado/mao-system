<?php

class LandingController extends Controller
{
    public function index(): void
    {
        if (Auth::check()) {
            $this->redirect('/home');
        }

        // Skip the splash screen when coming back to log in (e.g. after logout)
        $showSplash = !isset($_GET['login']);

        $this->view('landing/index', ['showSplash' => $showSplash], null);
    }
}
