<?php
/**
 * Reusable UI builders. Every list / form / detail screen in AgriMap should be
 * assembled from these so all screens look and behave the same.
 *
 *   Ui::pageHead($title, $subtitle, $actionsHtml)
 *   Ui::filterBar($action, $fields, $values)        GET filters (search + selects)
 *   Ui::dataTable($columns, $rows, $options)        table inside the standard wrapper
 *   Ui::pagination($page, $path, $query)            "Showing x-y of n" + pager
 *   Ui::emptyState($title, $message, $actionHtml)
 *   Ui::badge($text, $type) / Ui::statusBadge($status)
 *   Ui::input() / Ui::select() / Ui::textarea()    form fields with label + error + old input
 *   Ui::section($title, $innerHtml)                 titled form section
 *
 * All text passed in is escaped here. Only values documented as "HTML" are inserted raw.
 */
class Ui
{
    /** status/value => badge colour. Add your own statuses here once, used everywhere. */
    private const STATUS_TYPES = [
        'active' => 'success', 'approved' => 'success', 'served' => 'success', 'completed' => 'success', 'ongoing' => 'info',
        'inactive' => 'muted', 'planned' => 'muted', 'identified' => 'muted', 'archived' => 'muted',
        'transferred' => 'warning', 'pending' => 'warning', 'validated' => 'info',
        'deceased' => 'danger', 'removed' => 'danger', 'rejected' => 'danger',
    ];

    public static function badge(string $text, string $type = 'muted'): string
    {
        return '<span class="badge badge-soft-' . e($type) . '">' . e($text) . '</span>';
    }

    public static function statusBadge(string $status, ?string $label = null): string
    {
        $type = self::STATUS_TYPES[strtolower($status)] ?? 'muted';
        return self::badge($label ?? ucfirst($status), $type);
    }

    /** $actionsHtml is HTML (buttons/links built by the caller). */
    public static function pageHead(string $title, ?string $subtitle = null, string $actionsHtml = ''): string
    {
        $html  = '<div class="page-head"><div><h1>' . e($title) . '</h1>';
        $html .= $subtitle ? '<p class="page-sub">' . e($subtitle) . '</p>' : '';
        $html .= '</div>';
        $html .= $actionsHtml !== '' ? '<div class="page-actions">' . $actionsHtml . '</div>' : '';
        return $html . '</div>';
    }

    public static function emptyState(string $title, string $message = '', string $actionHtml = '', string $icon = 'inbox'): string
    {
        return '<div class="empty-state"><span class="empty-icon" aria-hidden="true"><i class="bi bi-' . e($icon) . '"></i></span>'
            . '<div class="empty-title">' . e($title) . '</div>'
            . ($message !== '' ? '<p class="mb-2">' . e($message) . '</p>' : '')
            . $actionHtml . '</div>';
    }

    /**
     * Filter bar (GET form). $fields:
     *   ['type' => 'search', 'name' => 'q', 'placeholder' => '...']
     *   ['type' => 'select', 'name' => 'barangay_id', 'all' => 'All barangays', 'options' => [value => label]]
     * Selects submit automatically on change; the search box submits on Enter.
     */
    public static function filterBar(string $action, array $fields, array $values = []): string
    {
        // Sits on top of the table as the first part of one panel (see .filter-bar + .table-wrap)
        $html = '<form method="get" action="' . e(url($action)) . '" class="filter-bar" data-auto-submit role="search">';
        $active = false;
        foreach ($fields as $f) {
            $name = $f['name'];
            $val  = (string) ($values[$name] ?? '');
            $active = $active || $val !== '';
            if (($f['type'] ?? 'select') === 'search') {
                $html .= '<div class="filter-search"><i class="bi bi-search" aria-hidden="true"></i>'
                    . '<input type="search" class="form-control" name="' . e($name) . '" value="' . e($val)
                    . '" placeholder="' . e($f['placeholder'] ?? 'Search') . '" aria-label="' . e($f['placeholder'] ?? 'Search') . '"></div>';
                continue;
            }
            $html .= '<select class="form-select" name="' . e($name) . '" aria-label="' . e($f['all'] ?? $name) . '">';
            $html .= '<option value="">' . e($f['all'] ?? 'All') . '</option>';
            foreach ($f['options'] as $optValue => $label) {
                $sel = ((string) $optValue === $val) ? ' selected' : '';
                $html .= '<option value="' . e($optValue) . '"' . $sel . '>' . e($label) . '</option>';
            }
            $html .= '</select>';
        }
        $html .= '<button class="btn btn-outline-secondary btn-sm" type="submit">Search</button>';
        if ($active) {
            $html .= '<a class="btn btn-sm btn-link filter-clear" href="' . e(url($action)) . '"><i class="bi bi-x-lg" aria-hidden="true"></i> Clear filters</a>';
        }
        return $html . '</form>';
    }

    /**
     * Standard table. $columns: list of
     *   ['label' => 'Name', 'key' => 'name']                         plain (escaped) value
     *   ['label' => 'Status', 'render' => fn(array $row) => HTML]    custom cell; return HTML-safe string
     *   optional: 'class' => 'num' | 'table-actions'
     * $options: 'empty' => HTML to show when there are no rows.
     */
    public static function dataTable(array $columns, array $rows, array $options = []): string
    {
        if (!$rows) {
            return '<div class="table-wrap">' . ($options['empty'] ?? self::emptyState('No records found')) . '</div>';
        }
        $html = '<div class="table-wrap"><div class="table-scroll"><table class="table table-hover"><thead><tr>';
        foreach ($columns as $c) {
            $html .= '<th' . (!empty($c['class']) ? ' class="' . e($c['class']) . '"' : '') . '>' . e($c['label']) . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($columns as $c) {
                $cell = isset($c['render']) ? $c['render']($row) : e($row[$c['key']] ?? '');
                $html .= '<td' . (!empty($c['class']) ? ' class="' . e($c['class']) . '"' : '') . '>' . $cell . '</td>';
            }
            $html .= '</tr>';
        }
        return $html . '</tbody></table></div></div>';
    }

    /** $page = ['total','page','pages','per_page'] as returned by the model search/paginate methods. */
    public static function pagination(array $page, string $path, array $query = []): string
    {
        if ($page['total'] === 0) {
            return '';
        }
        unset($query['page']);
        $from = ($page['page'] - 1) * $page['per_page'] + 1;
        $to   = min($page['total'], $page['page'] * $page['per_page']);
        $link = fn (int $n) => e(url($path) . '?' . http_build_query($query + ['page' => $n]));

        // Bottom part of the list panel (see .table-wrap + .list-footer)
        $html  = '<div class="list-footer">';
        $html .= '<div class="text-muted small">Showing <strong>' . $from . '&ndash;' . $to . '</strong> of ' . number_format($page['total']) . '</div>';
        if ($page['pages'] > 1) {
            $html .= '<nav><ul class="pagination mb-0">';
            $html .= '<li class="page-item ' . ($page['page'] <= 1 ? 'disabled' : '') . '"><a class="page-link" href="' . $link(max(1, $page['page'] - 1)) . '">Previous</a></li>';
            $start = max(1, $page['page'] - 2);
            $end   = min($page['pages'], $page['page'] + 2);
            for ($i = $start; $i <= $end; $i++) {
                $html .= '<li class="page-item ' . ($i === $page['page'] ? 'active' : '') . '"><a class="page-link" href="' . $link($i) . '">' . $i . '</a></li>';
            }
            $html .= '<li class="page-item ' . ($page['page'] >= $page['pages'] ? 'disabled' : '') . '"><a class="page-link" href="' . $link(min($page['pages'], $page['page'] + 1)) . '">Next</a></li>';
            $html .= '</ul></nav>';
        }
        return $html . '</div>';
    }

    // ------------------------------------------------------------------ forms

    private static function fieldWrap(string $col, string $inner): string
    {
        return '<div class="' . e($col) . '">' . $inner . '</div>';
    }

    private static function label(string $name, string $label, bool $required): string
    {
        return '<label class="form-label" for="f_' . e($name) . '">' . e($label)
            . ($required ? ' <span class="text-danger">*</span>' : '') . '</label>';
    }

    private static function feedback(string $name, ?string $help): string
    {
        $err = errors($name);
        return ($err ? '<div class="invalid-feedback d-block">' . e($err) . '</div>' : '')
             . ($help && !$err ? '<div class="form-text">' . e($help) . '</div>' : '');
    }

    /**
     * Text-like input. $o: value, type, required, placeholder, help, readonly, maxlength, col (grid class), inputmode
     * Shows the previously submitted value (old input) first, then $o['value'].
     */
    public static function input(string $name, string $label, array $o = []): string
    {
        $value = old($name, $o['value'] ?? '');
        $cls   = 'form-control' . (errors($name) ? ' is-invalid' : '');
        $in = '<input class="' . $cls . '" id="f_' . e($name) . '" name="' . e($name) . '" type="' . e($o['type'] ?? 'text') . '"'
            . ' value="' . e($value) . '"'
            . (!empty($o['placeholder']) ? ' placeholder="' . e($o['placeholder']) . '"' : '')
            . (!empty($o['maxlength']) ? ' maxlength="' . (int) $o['maxlength'] . '"' : '')
            . (!empty($o['inputmode']) ? ' inputmode="' . e($o['inputmode']) . '"' : '')
            . (isset($o['min']) ? ' min="' . e($o['min']) . '"' : '')
            . (!empty($o['max']) ? ' max="' . e($o['max']) . '"' : '')
            . (!empty($o['step']) ? ' step="' . e($o['step']) . '"' : '')
            . (!empty($o['readonly']) ? ' readonly' : '') . '>';
        return self::fieldWrap($o['col'] ?? 'col-md-6',
            self::label($name, $label, !empty($o['required'])) . $in . self::feedback($name, $o['help'] ?? null));
    }

    /** $options: [value => label]. $o: value, required, help, col, placeholder (first empty option text). */
    public static function select(string $name, string $label, array $options, array $o = []): string
    {
        $value = (string) old($name, $o['value'] ?? '');
        $cls   = 'form-select' . (errors($name) ? ' is-invalid' : '');
        $in    = '<select class="' . $cls . '" id="f_' . e($name) . '" name="' . e($name) . '">';
        $in   .= '<option value="">' . e($o['placeholder'] ?? 'Select...') . '</option>';
        foreach ($options as $v => $text) {
            $in .= '<option value="' . e($v) . '"' . ((string) $v === $value ? ' selected' : '') . '>' . e($text) . '</option>';
        }
        $in .= '</select>';
        return self::fieldWrap($o['col'] ?? 'col-md-6',
            self::label($name, $label, !empty($o['required'])) . $in . self::feedback($name, $o['help'] ?? null));
    }

    public static function textarea(string $name, string $label, array $o = []): string
    {
        $cls = 'form-control' . (errors($name) ? ' is-invalid' : '');
        $in  = '<textarea class="' . $cls . '" id="f_' . e($name) . '" name="' . e($name) . '" rows="' . (int) ($o['rows'] ?? 3) . '">'
            . e(old($name, $o['value'] ?? '')) . '</textarea>';
        return self::fieldWrap($o['col'] ?? 'col-12',
            self::label($name, $label, !empty($o['required'])) . $in . self::feedback($name, $o['help'] ?? null));
    }

    /** Titled form section. $innerHtml = concatenated Ui::input()/select() output (they are grid columns). */
    public static function section(string $title, string $innerHtml, ?string $hint = null): string
    {
        return '<section class="form-section"><div class="form-section-title">' . e($title) . '</div>'
            . ($hint ? '<p class="text-muted small mb-3" style="margin-top:-6px;">' . e($hint) . '</p>' : '')
            . '<div class="row g-3">' . $innerHtml . '</div></section>';
    }

    /** Label/value pairs for detail pages: $pairs = [label => value-or-HTML-callable]. Plain values are escaped. */
    public static function detailList(array $pairs): string
    {
        $html = '<dl class="detail-list">';
        foreach ($pairs as $label => $value) {
            $text = is_callable($value) ? $value() : e(($value === null || $value === '') ? '—' : $value);
            $html .= '<div><dt>' . e($label) . '</dt><dd>' . $text . '</dd></div>';
        }
        return $html . '</dl>';
    }
}
