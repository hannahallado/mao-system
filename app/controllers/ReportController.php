<?php
/** Process 6.0 - Reports Center + one reusable report template (print + Excel). */
class ReportController extends Controller
{
    private function catalogue(): array
    {
        return require BASE_PATH . '/config/reports.php';
    }

    public function index(): void
    {
        $this->view('reports/index', [
            'title'       => 'Reports',
            'groups'      => $this->catalogue(),
            'breadcrumbs' => [['Home', '/home'], ['Reports', null]],
        ]);
    }

    /** Old group pages: the Reports Center lists every group already. */
    public function group(string $group): void
    {
        $this->redirect('/reports');
    }

    public function show(string $slug): void
    {
        $report = null;
        foreach ($this->catalogue() as $group) {
            if (isset($group['reports'][$slug])) {
                $report = ['title' => $group['reports'][$slug][0], 'description' => $group['reports'][$slug][1], 'group' => $group['title']];
            }
        }
        if (!$report || !isset(ReportService::DEFINITIONS[$slug])) {
            abort(404);
        }

        $missing = ReportService::missingTables($slug);
        $filters = ReportService::cleanFilters($slug, $_GET);
        $data    = $missing ? ['rows' => [], 'columns' => []] : ReportService::run($slug, $filters);

        $this->view('reports/show', [
            'title'       => $report['title'],
            'report'      => $report,
            'slug'        => $slug,
            'filters'     => $filters,
            'missing'     => $missing,
            'rows'        => $data['rows'],
            'columns'     => $data['columns'],
            'settings'    => Setting::all(),
            'breadcrumbs' => [['Home', '/home'], ['Reports', '/reports'], [$report['title'], null]],
            'scripts'     => ['js/reports.js'],
        ]);
    }
}
