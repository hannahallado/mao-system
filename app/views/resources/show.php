<?php
$id = (int) $row['id'];
$actions = '<a class="btn btn-outline-secondary btn-sm" href="' . e(url($c['path'])) . '">Back to list</a>'
    . ($canEdit ? '<a class="btn btn-primary btn-sm" href="' . e(url($c['path'] . '/' . $id . '/edit')) . '">Edit</a>' : '')
    . (Auth::isAgriculturist() ? ' <form method="post" action="' . e(url($c['path'] . '/' . $id . '/delete')) . '" style="display:inline;" onsubmit="return confirm(\'Remove this record? It is archived, not erased.\')">'
        . Csrf::field() . '<button type="submit" class="btn btn-outline-danger btn-sm">Remove</button></form>' : '');
?>
<?= Ui::pageHead($title, $c['singular'] . ' details', $actions) ?>
<div class="card"><div class="card-body">
    <?= Ui::detailList($pairs) ?>
</div></div>
<?= $extra ?>
