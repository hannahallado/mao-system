<?php

class LandingController extends Controller
{
    public function index(): void
    {
        if (Auth::check()) {
            $this->redirect('/home');
        }

        $this->view('landing/index', [], null);
    }
}
