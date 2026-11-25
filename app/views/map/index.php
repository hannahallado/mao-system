<?php
$activeFeatures = array_values(array_filter(
    $features,
    static fn ($feature) => $preset === 'all' || $feature['kind'] === $preset
));
$barangays = [];
$commodities = [];
$categories = [];
$plottableCount = 0;
foreach ($activeFeatures as $feature) {
    $barangays[$feature['barangay']] = true;
    $commodities[$feature['commodity']] = true;
    $categories[$feature['category']] = true;
    if (($feature['latitude'] !== null && $feature['longitude'] !== null) || $feature['boundary'] !== null) {
        $plottableCount++;
    }
}
ksort($barangays);
ksort($commodities);
ksort($categories);
$presetName = $preset === 'farms' ? 'Farm' : ($preset === 'fishery' ? 'Fishery' : 'Farm and fishery');
?>
<div class="page-head">
    <div>
        <h1><?= e($title) ?></h1>
        <p class="page-sub">
            <?= $preset === 'farms' ? 'Farm locations across the municipality'
                : ($preset === 'fishery' ? 'Fishery locations across the municipality' : 'Farm and fishery locations across the municipality') ?>
        </p>
    </div>
    <div class="page-actions">
        <?php if ($preset !== 'all'): ?>
            <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('/map')) ?>">Map Overview</a>
        <?php endif; ?>
        <?php if ($preset !== 'farms'): ?>
            <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('/map/farms')) ?>">Farm Locations</a>
        <?php endif; ?>
        <?php if ($preset !== 'fishery'): ?>
            <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('/map/fishery')) ?>">Fishery Locations</a>
        <?php endif; ?>
    </div>
</div>

<section class="map-workspace" aria-label="Farm and fishery map">
    <aside class="map-controls card">
        <div class="map-panel-heading">
            <div>
                <h2>Map layers</h2>
                <p class="map-panel-subtitle">Choose what to display</p>
            </div>
            <span class="map-count" id="map-visible-count" aria-live="polite"><?= $plottableCount ?></span>
        </div>

        <div class="map-layer-list">
            <label class="map-layer-option" for="map-layer-farms">
                <input class="form-check-input" id="map-layer-farms" type="checkbox" value="farms"
                       data-default-checked="<?= $preset !== 'fishery' ? 'true' : 'false' ?>"
                    <?= $preset !== 'fishery' ? 'checked' : '' ?>>
                <span class="map-legend-dot map-legend-farm" aria-hidden="true"></span>
                <span>Farms</span>
                <span class="map-layer-total"><?= count(array_filter($features, static fn ($item) => $item['kind'] === 'farms')) ?></span>
            </label>
            <label class="map-layer-option" for="map-layer-fishery">
                <input class="form-check-input" id="map-layer-fishery" type="checkbox" value="fishery"
                       data-default-checked="<?= $preset !== 'farms' ? 'true' : 'false' ?>"
                    <?= $preset !== 'farms' ? 'checked' : '' ?>>
                <span class="map-legend-dot map-legend-fishery" aria-hidden="true"></span>
                <span>Fishery</span>
                <span class="map-layer-total"><?= count(array_filter($features, static fn ($item) => $item['kind'] === 'fishery')) ?></span>
            </label>
            <div class="map-location-reference">
                <span class="map-legend-dot map-legend-reference" aria-hidden="true"></span>
                <span>Pulupandan map reference</span>
            </div>
        </div>

        <div class="map-filter-heading">
            <h2>Filters</h2>
            <button type="button" class="map-reset-button" id="map-reset">Reset</button>
        </div>
        <div class="map-filter-list">
            <label for="map-filter-barangay">Barangay</label>
            <select class="form-select form-select-sm" id="map-filter-barangay">
                <option value="">All barangays</option>
                <?php foreach (array_keys($barangays) as $barangay): ?>
                    <option value="<?= e($barangay) ?>"><?= e($barangay) ?></option>
                <?php endforeach; ?>
            </select>

            <label for="map-filter-commodity">Commodity</label>
            <select class="form-select form-select-sm" id="map-filter-commodity">
                <option value="">All commodities</option>
                <?php foreach (array_keys($commodities) as $commodity): ?>
                    <option value="<?= e($commodity) ?>"><?= e($commodity) ?></option>
                <?php endforeach; ?>
            </select>

            <label for="map-filter-category">Category</label>
            <select class="form-select form-select-sm" id="map-filter-category">
                <option value="">All categories</option>
                <?php foreach (array_keys($categories) as $category): ?>
                    <option value="<?= e($category) ?>"><?= e($category) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="map-selection" aria-live="polite">
            <h2>Location details</h2>
            <div class="map-selection-empty" id="map-selection-empty">Select a marker or boundary to view its record.</div>
            <div class="map-selection-content" id="map-selection-content" hidden>
                <h3 id="map-detail-name"></h3>
                <dl>
                    <div><dt>Type</dt><dd id="map-detail-kind"></dd></div>
                    <div><dt>Category</dt><dd id="map-detail-category"></dd></div>
                    <div><dt>Barangay</dt><dd id="map-detail-barangay"></dd></div>
                    <div><dt>Commodity</dt><dd id="map-detail-commodity"></dd></div>
                    <div id="map-detail-owner-row" hidden><dt>Owner</dt><dd id="map-detail-owner"></dd></div>
                    <div id="map-detail-description-row" hidden><dt>Description</dt><dd id="map-detail-description"></dd></div>
                    <div id="map-detail-area-row" hidden><dt>Area / Capacity</dt><dd id="map-detail-area"></dd></div>
                    <div id="map-detail-status-row" hidden><dt>Status</dt><dd id="map-detail-status"></dd></div>
                </dl>
                <div class="map-detail-links">
                    <a class="btn btn-primary btn-sm" id="map-record-link" href="#">Open D2 record</a>
                    <a class="btn btn-outline-secondary btn-sm" id="map-profile-link" href="#" hidden>Open D1 profile</a>
                </div>
            </div>
        </div>
    </aside>

    <div class="map-card card">
        <div class="map-card-header">
            <div>
                <strong>Location map</strong>
                <span id="map-status" role="status" aria-live="polite">
                    <?= $activeFeatures
                        ? ($plottableCount ? $plottableCount . ' locations on map' : 'No records have map coordinates or boundaries')
                        : ($preset === 'farms' ? 'No farm records available'
                            : ($preset === 'fishery' ? 'No fishery records available' : 'No farm or fishery records available')) ?>
                </span>
            </div>
            <button type="button" class="btn btn-light btn-sm" id="map-fit">Fit locations</button>
        </div>
        <div class="map-canvas-wrap">
            <div id="location-map" class="location-map" role="application" aria-label="Interactive map of farm and fishery locations"></div>
            <div class="map-empty-state" id="map-empty-state" <?= $plottableCount ? 'hidden' : '' ?>>
                <div class="map-empty-icon" aria-hidden="true"><i class="bi bi-geo-alt"></i></div>
                <strong id="map-empty-title">
                    <?= $activeFeatures
                        ? 'No georeferenced locations'
                        : ($preset === 'farms' ? 'No farm records yet'
                            : ($preset === 'fishery' ? 'No fishery records yet' : 'No map records yet')) ?>
                </strong>
                <span id="map-empty-message">
                    <?= $activeFeatures
                        ? 'The map is centered on Pulupandan, Negros Occidental. Add valid coordinates or a GeoJSON boundary to ' . strtolower($presetName) . ' records.'
                        : ($preset === 'farms'
                            ? 'The map is centered on Pulupandan, Negros Occidental. Farm records will appear here once they are added.'
                            : ($preset === 'fishery'
                                ? 'The map is centered on Pulupandan, Negros Occidental. Fishery records will appear here once they are added.'
                                : 'The map is centered on Pulupandan, Negros Occidental. Farm and fishery records will appear here once they are added.')) ?>
                </span>
            </div>
        </div>
        <div class="map-attribution-note">
            Center reference: Pulupandan, Negros Occidental · Map tiles ©
            <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap contributors</a>
        </div>
    </div>
</section>

<script type="application/json" id="map-features"><?= json_encode($features, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) ?></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script src="<?= e(asset('js/map.js')) ?>" defer></script>
