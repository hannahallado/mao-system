(() => {
    const dataElement = document.getElementById('map-features');
    const mapElement = document.getElementById('location-map');
    if (!dataElement || !mapElement) return;

    const features = JSON.parse(dataElement.textContent || '[]');
    const mapStatus = document.getElementById('map-status');
    const emptyState = document.getElementById('map-empty-state');
    const emptyTitle = document.getElementById('map-empty-title');
    const emptyMessage = document.getElementById('map-empty-message');
    const visibleCount = document.getElementById('map-visible-count');
    const selectionEmpty = document.getElementById('map-selection-empty');
    const selectionContent = document.getElementById('map-selection-content');
    const layerInputs = Array.from(document.querySelectorAll('.map-layer-option input'));
    const filters = {
        barangay: document.getElementById('map-filter-barangay'),
        commodity: document.getElementById('map-filter-commodity'),
        category: document.getElementById('map-filter-category'),
    };

    if (typeof L === 'undefined') {
        mapStatus.textContent = 'The map library could not be loaded. Check your internet connection and reload.';
        emptyTitle.textContent = 'Map unavailable';
        emptyMessage.textContent = 'The interactive map library did not load.';
        emptyState.hidden = false;
        return;
    }

    const map = L.map(mapElement, { scrollWheelZoom: false }).setView([10.5192005, 122.8034563], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap contributors</a>',
    }).addTo(map);
    const referenceTooltip = document.createElement('span');
    referenceTooltip.textContent = 'Pulupandan, Negros Occidental (map reference)';
    L.circleMarker([10.5192005, 122.8034563], {
        radius: 5,
        color: '#fff',
        weight: 2,
        fillColor: '#53665c',
        fillOpacity: 1,
    }).bindTooltip(referenceTooltip).addTo(map);

    const colors = { farms: '#26864a', fishery: '#2175b8' };
    const layers = {
        farms: L.layerGroup().addTo(map),
        fishery: L.layerGroup().addTo(map),
    };
    let visibleFeatures = [];
    let selectedKey = null;

    const featureKey = (feature) => `${feature.kind}:${feature.id}`;
    const hasGeometry = (feature) =>
        (feature.latitude !== null && feature.longitude !== null) || feature.boundary !== null;

    function clearSelection() {
        selectedKey = null;
        selectionContent.hidden = true;
        selectionEmpty.hidden = false;
    }

    function selectFeature(feature) {
        selectedKey = featureKey(feature);
        selectionEmpty.hidden = true;
        selectionContent.hidden = false;
        document.getElementById('map-detail-name').textContent = feature.label;
        document.getElementById('map-detail-kind').textContent =
            feature.kind === 'farms' ? 'Farm' : 'Fishery';
        document.getElementById('map-detail-category').textContent = feature.category;
        document.getElementById('map-detail-barangay').textContent = feature.barangay;
        document.getElementById('map-detail-commodity').textContent = feature.commodity;

        const detailFields = [
            ['owner', 'map-detail-owner'],
            ['description', 'map-detail-description'],
            ['area', 'map-detail-area'],
            ['status', 'map-detail-status'],
        ];
        detailFields.forEach(([field, valueId]) => {
            const value = feature[field];
            const row = document.getElementById(`${valueId}-row`);
            document.getElementById(valueId).textContent = value || '';
            row.hidden = !value;
        });

        const recordLink = document.getElementById('map-record-link');
        recordLink.href = feature.recordUrl;
        const profileLink = document.getElementById('map-profile-link');
        if (feature.profileUrl) {
            profileLink.href = feature.profileUrl;
            profileLink.hidden = false;
        } else {
            profileLink.removeAttribute('href');
            profileLink.hidden = true;
        }
    }

    function enabledLayerFeatures() {
        const enabledLayers = layerInputs
            .filter((input) => input.checked)
            .map((input) => input.value);
        return features.filter((feature) => enabledLayers.includes(feature.kind));
    }

    function refreshFilterOptions() {
        const fieldLabels = {
            barangay: 'All barangays',
            commodity: 'All commodities',
            category: 'All categories',
        };
        const activeFeatures = enabledLayerFeatures();
        for (const [field, select] of Object.entries(filters)) {
            const previousValue = select.value;
            const values = [...new Set(activeFeatures.map((feature) => feature[field]))]
                .filter(Boolean)
                .sort((a, b) => a.localeCompare(b));
            select.replaceChildren(new Option(fieldLabels[field], ''));
            values.forEach((value) => select.add(new Option(value, value)));
            if (values.includes(previousValue)) {
                select.value = previousValue;
            }
        }
    }

    function matchesFilters(feature) {
        const enabledLayers = layerInputs
            .filter((input) => input.checked)
            .map((input) => input.value);
        return enabledLayers.includes(feature.kind)
            && (!filters.barangay.value || feature.barangay === filters.barangay.value)
            && (!filters.commodity.value || feature.commodity === filters.commodity.value)
            && (!filters.category.value || feature.category === filters.category.value);
    }

    function fitVisibleFeatures() {
        const bounds = L.featureGroup(visibleFeatures).getBounds();
        if (bounds.isValid()) {
            map.fitBounds(bounds, { padding: [24, 24], maxZoom: 15 });
        }
    }

    function render() {
        layers.farms.clearLayers();
        layers.fishery.clearLayers();
        visibleFeatures = [];
        const matching = features.filter(matchesFilters);
        const plottable = matching.filter(hasGeometry);

        for (const feature of plottable) {
            const layer = layers[feature.kind];
            const color = colors[feature.kind];

            if (feature.boundary) {
                try {
                    const boundary = L.geoJSON(feature.boundary, {
                        style: { color, weight: 2, fillColor: color, fillOpacity: 0.16 },
                        onEachFeature: (_geoFeature, boundaryLayer) => {
                            boundaryLayer.on('click', () => selectFeature(feature));
                        },
                    });
                    boundary.addTo(layer);
                    visibleFeatures.push(boundary);
                } catch (error) {
                    console.error(`Unable to render boundary for ${feature.kind} ${feature.id}.`, error);
                }
            }

            if (feature.latitude !== null && feature.longitude !== null) {
                const marker = L.circleMarker([feature.latitude, feature.longitude], {
                    radius: 7,
                    color: '#fff',
                    weight: 2,
                    fillColor: color,
                    fillOpacity: 1,
                });
                marker.on('click', () => selectFeature(feature));
                const tooltip = document.createElement('span');
                tooltip.textContent = feature.label;
                marker.bindTooltip(tooltip);
                marker.addTo(layer);
                visibleFeatures.push(marker);
            }
        }

        if (selectedKey && !plottable.some((feature) => featureKey(feature) === selectedKey)) {
            clearSelection();
        }

        visibleCount.textContent = String(plottable.length);
        if (plottable.length) {
            emptyState.hidden = true;
            mapStatus.textContent = `${plottable.length} location${plottable.length === 1 ? '' : 's'} on map`
                + (matching.length > plottable.length
                    ? ` · ${matching.length - plottable.length} without coordinates or boundary`
                    : '');
        } else {
            emptyState.hidden = false;
            if (!matching.length) {
                const activeRecords = enabledLayerFeatures();
                const enabledLayers = layerInputs.filter((input) => input.checked);
                if (!enabledLayers.length) {
                    emptyTitle.textContent = 'No map layers selected';
                    emptyMessage.textContent = 'Turn on a map layer to display its locations.';
                    mapStatus.textContent = 'No map layers selected';
                } else if (!activeRecords.length) {
                    const selectedKinds = enabledLayers.map((input) => input.value);
                    const onlyFarms = selectedKinds.length === 1 && selectedKinds[0] === 'farms';
                    const onlyFishery = selectedKinds.length === 1 && selectedKinds[0] === 'fishery';
                    emptyTitle.textContent = onlyFarms ? 'No farm records yet'
                        : onlyFishery ? 'No fishery records yet' : 'No map records yet';
                    emptyMessage.textContent = onlyFarms ? 'Farm records will appear here once they are added.'
                        : onlyFishery ? 'Fishery records will appear here once they are added.'
                            : 'Farm and fishery records will appear here once they are added.';
                    mapStatus.textContent = onlyFarms ? 'No farm records available'
                        : onlyFishery ? 'No fishery records available' : 'No farm or fishery records available';
                } else {
                    emptyTitle.textContent = 'No matching locations';
                    emptyMessage.textContent = 'Change or reset the filters to see available locations.';
                    mapStatus.textContent = 'No records match the selected layers and filters';
                }
            } else {
                emptyTitle.textContent = 'No georeferenced locations';
                emptyMessage.textContent = 'The matching records have no valid latitude/longitude coordinates or GeoJSON boundary.';
                mapStatus.textContent = `${matching.length} matching record${matching.length === 1 ? '' : 's'} without map coordinates`;
            }
        }
    }

    layerInputs.forEach((input) => input.addEventListener('change', () => {
        refreshFilterOptions();
        render();
    }));
    Object.values(filters).forEach((filter) => filter.addEventListener('change', render));
    document.getElementById('map-reset').addEventListener('click', () => {
        layerInputs.forEach((input) => { input.checked = input.dataset.defaultChecked === 'true'; });
        Object.values(filters).forEach((filter) => { filter.value = ''; });
        refreshFilterOptions();
        clearSelection();
        render();
    });
    document.getElementById('map-fit').addEventListener('click', fitVisibleFeatures);

    refreshFilterOptions();
    render();
    fitVisibleFeatures();
    requestAnimationFrame(() => map.invalidateSize());
})();
