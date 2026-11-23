<?php
/** Home page after login: welcome banner + links to the six DFD processes (no charts). */
class HomeController extends Controller
{
    public function index(): void
    {
        $hour = (int) date('G');
        $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');

        $this->view('home/index', [
            'title'    => 'Dashboard', // same name as the sidebar link
            'user'     => Auth::user(),
            'role'     => Auth::isAgriculturist() ? 'Municipal Agriculturist' : 'Agricultural Technologist',
            'greeting' => $greeting,
            'tiles'    => [
                // [process, label, path, functional?, icon, description]
                ['1.0', 'Farmer/Fisherfolk Registry', '/farmers', true, 'people',
                    'Register and maintain the farmer and fisherfolk masterlist.'],
                ['2.0', 'Agricultural Resources', '/farms', false, 'basket',
                    'Record farms, fishery assets, livestock, equipment and organizations.'],
                ['3.0', 'GIS Mapping', '/map', false, 'geo-alt',
                    'View farm and fishery locations on an interactive map.'],
                ['4.0', 'Agricultural Monitoring', '/monitoring', false, 'activity',
                    'Track production, damage and pest incidents, and trainings.'],
                ['5.0', 'Programs and Assistance', '/programs', true, 'hand-thumbs-up',
                    'Manage programs, beneficiaries and assistance distribution.'],
                ['6.0', 'Reports', '/reports', true, 'bar-chart-line',
                    'Generate, print and export MAO reports.'],
            ],
        ]);
    }
}
