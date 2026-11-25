<?php
/** System-wide office and reporting configuration. */
class SettingController extends Controller
{
    public function index(): void
    {
        $this->view('system/settings', [
            'title' => 'System Settings',
            'breadcrumbs' => [['Home', '/home'], ['System Management', null], ['System Settings', null]],
            'settings' => SystemSetting::current(),
            'menu_active' => '/system/settings',
        ]);
    }

    public function save(): void
    {
        $data = $this->only([
            'office_name', 'municipality', 'report_header', 'reporting_start', 'reporting_end',
            'wet_season_label', 'dry_season_label',
        ]);
        $clean = $this->validate($data, [
            'office_name' => 'required|max:120',
            'municipality' => 'required|max:120',
            'report_header' => 'required|max:200',
            'reporting_start' => 'required|date',
            'reporting_end' => 'required|date',
            'wet_season_label' => 'required|max:60',
            'dry_season_label' => 'required|max:60',
        ], '/system/settings', [
            'office_name' => 'Office name',
            'reporting_start' => 'Reporting period start',
            'reporting_end' => 'Reporting period end',
            'wet_season_label' => 'Wet season label',
            'dry_season_label' => 'Dry season label',
        ]);

        if ($clean['reporting_start'] > $clean['reporting_end']) {
            flash_errors(['reporting_end' => ['Reporting period end must be on or after the start date.']]);
            flash_old($data);
            $this->redirect('/system/settings');
        }

        SystemSetting::saveSettings($clean);
        flash('success', 'System settings saved.');
        $this->redirect('/system/settings');
    }
}
