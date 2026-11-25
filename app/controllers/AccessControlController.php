<?php
/** Process System Management. */
class AccessControlController extends Controller
{
    public function index(): void
    {
        $this->view('system/access-control', [
            'title' => 'Access Control',
            'breadcrumbs' => [['Home', '/home'], ['System Management', null], ['Access Control', null]],
            'menu_active' => '/system/access-control',
        ]);
    }
}
