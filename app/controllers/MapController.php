<?php
/** Process 3.0 Map Farm and Fishery Locations (GIS). */
class MapController extends Controller
{
    /** One map page: farm and fishery layers are toggled on the page itself. */
    public function index(): void
    {
        $this->view('map/index', [
            'title'        => 'GIS Map',
            'preset'       => 'all',
            'needsLeaflet' => true,
            'breadcrumbs'  => [['Home', '/home'], ['GIS Map', null]],
            'features'     => $this->mapFeatures(),
        ]);
    }

    /** Old links (/map/farms, /map/fishery) now open the single map page. */
    public function farms(): void
    {
        $this->redirect('/map');
    }

    public function fishery(): void
    {
        $this->redirect('/map');
    }

    /**
     * Farms and fishery assets with their location (D2 + D3). Records without
     * coordinates are still counted so the page can say how many are unmapped.
     */
    private function mapFeatures(): array
    {
        $resources = RecordModule::module('resources')['tabs'];
        $features  = [];

        if (RecordModule::tableReady('farms')) {
            $rows = Model::raw(
                "SELECT f.id, f.farm_name, f.profile_id, f.commodity, f.water_source, f.latitude, f.longitude, f.boundary_geojson,
                        b.name AS barangay, CONCAT(p.last_name, ', ', p.first_name) AS owner
                 FROM farms f
                 JOIN barangays b ON b.id = f.barangay_id
                 JOIN profiles p ON p.id = f.profile_id
                 WHERE f.deleted_at IS NULL");
            $fields = $resources['farms']['fields'];
            foreach ($rows as $r) {
                $features[] = [
                    'id'         => 'farm-' . $r['id'],
                    'kind'       => 'farms',
                    'label'      => $r['farm_name'] . ' (' . $r['owner'] . ')',
                    'barangay'   => $r['barangay'],
                    'commodity'  => $fields['commodity']['options'][$r['commodity']] ?? $r['commodity'],
                    'category'   => $fields['water_source']['options'][$r['water_source']] ?? 'Farm',
                    'latitude'   => $this->coordinate($r['latitude'], -90, 90),
                    'longitude'  => $this->coordinate($r['longitude'], -180, 180),
                    'boundary'   => $this->geoJson($r['boundary_geojson']),
                    'recordUrl'  => url('/resources?tab=farms&q=' . rawurlencode($r['farm_name'])),
                    'profileUrl' => url('/farmers/' . (int) $r['profile_id']),
                ];
            }
        }

        if (RecordModule::tableReady('fishery_assets')) {
            $rows = Model::raw(
                "SELECT a.id, a.name, a.profile_id, a.asset_type, a.species, a.latitude, a.longitude,
                        b.name AS barangay, CONCAT(p.last_name, ', ', p.first_name) AS owner
                 FROM fishery_assets a
                 JOIN barangays b ON b.id = a.barangay_id
                 JOIN profiles p ON p.id = a.profile_id
                 WHERE a.deleted_at IS NULL");
            $fields = $resources['fishery']['fields'];
            foreach ($rows as $r) {
                $features[] = [
                    'id'         => 'fishery-' . $r['id'],
                    'kind'       => 'fishery',
                    'label'      => $r['name'] . ' (' . $r['owner'] . ')',
                    'barangay'   => $r['barangay'],
                    'commodity'  => $r['species'] ?: 'Not specified',
                    'category'   => $fields['asset_type']['options'][$r['asset_type']] ?? 'Fishery',
                    'latitude'   => $this->coordinate($r['latitude'], -90, 90),
                    'longitude'  => $this->coordinate($r['longitude'], -180, 180),
                    'boundary'   => null,
                    'recordUrl'  => url('/resources?tab=fishery&q=' . rawurlencode($r['name'])),
                    'profileUrl' => url('/farmers/' . (int) $r['profile_id']),
                ];
            }
        }

        return $features;
    }

    private function coordinate(mixed $value, float $min, float $max): ?float
    {
        if (!is_numeric($value)) {
            return null;
        }
        $coordinate = (float) $value;
        return is_finite($coordinate) && $coordinate >= $min && $coordinate <= $max
            ? $coordinate
            : null;
    }

    private function geoJson(mixed $value): ?array
    {
        if (!is_string($value) || $value === '') {
            return null;
        }
        $geometry = json_decode($value, true);
        $supportedTypes = ['Feature', 'FeatureCollection', 'Point', 'LineString', 'MultiPoint',
            'MultiLineString', 'Polygon', 'MultiPolygon', 'GeometryCollection'];
        return is_array($geometry) && in_array($geometry['type'] ?? null, $supportedTypes, true)
            ? $geometry
            : null;
    }
}
