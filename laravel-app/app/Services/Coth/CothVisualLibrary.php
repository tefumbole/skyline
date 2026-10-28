<?php

namespace App\Services\Coth;

use App\CothTable;

/**
 * Catalog of per-table 3D visual presets. Each preset applies to a subset
 * of capacities and seating styles. Admin picks a preset per table; the
 * renderer resolves it to a static PNG under /public/images/coth/presets.
 * If a preset-specific asset is missing we fall back to the base artwork
 * so the UI always has an image.
 */
class CothVisualLibrary
{
    /**
     * Returns the preset catalog. applies_to_capacities = [] means all
     * capacities. applies_to_styles = [] means any style.
     */
    public function presets()
    {
        return [
            [
                'slug' => 'walnut-navy',
                'name' => 'Walnut top · Navy chairs',
                'style' => 'standard',
                'applies_to_capacities' => [2, 3, 4, 6, 8, 10],
                'swatch' => ['#3b2a1a', '#1a2440', '#f3e9d2'],
            ],
            [
                'slug' => 'oak-teal',
                'name' => 'Oak top · Teal chairs',
                'style' => 'standard',
                'applies_to_capacities' => [2, 3, 4, 6, 8, 10],
                'swatch' => ['#7a5a30', '#1f5d5d', '#f3e9d2'],
            ],
            [
                'slug' => 'marble-cream',
                'name' => 'Marble top · Cream chairs',
                'style' => 'standard',
                'applies_to_capacities' => [2, 3, 4, 6, 8, 10],
                'swatch' => ['#e8e4da', '#efe3c6', '#c5a059'],
            ],
            [
                'slug' => 'cityview-high-walnut',
                'name' => 'City view · High table, walnut',
                'style' => 'high_table',
                'applies_to_capacities' => [4, 6],
                'swatch' => ['#3b2a1a', '#1a2440', '#c5a059'],
            ],
            [
                'slug' => 'cityview-high-oak',
                'name' => 'City view · High table, oak',
                'style' => 'high_table',
                'applies_to_capacities' => [4, 6],
                'swatch' => ['#7a5a30', '#efe3c6', '#c5a059'],
            ],
            [
                'slug' => 'couch-teal',
                'name' => 'Lounge couch · Teal',
                'style' => 'couch',
                'applies_to_capacities' => [4, 6],
                'swatch' => ['#1f5d5d', '#7a5a30', '#f3e9d2'],
            ],
            [
                'slug' => 'couch-navy',
                'name' => 'Lounge couch · Navy',
                'style' => 'couch',
                'applies_to_capacities' => [4, 6],
                'swatch' => ['#1a2440', '#3b2a1a', '#f3e9d2'],
            ],
        ];
    }

    /**
     * Preset metadata by slug, or null.
     */
    public function preset($slug)
    {
        foreach ($this->presets() as $row) {
            if ($row['slug'] === $slug) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Return the furniture style a table uses for art lookup.
     * couch overrides city-view high-table; is_high_table forces high art.
     */
    public function styleFor(CothTable $table)
    {
        if ((string) $table->seating_style === 'couch') {
            return 'couch';
        }
        if ((int) $table->is_high_table === 1 || ((int) $table->is_city_view === 1 && in_array((int) $table->capacity, [4, 6], true))) {
            return 'high_table';
        }

        return 'standard';
    }

    /**
     * Resolve the artwork URL to render for this table.
     * Looks for a per-capacity asset under the chosen preset, then falls back
     * to the default artwork under /images/coth/tables/{cap}.png or couch.png.
     */
    public function assetFor(CothTable $table)
    {
        $style = $this->styleFor($table);
        $cap = $this->normalizedCapacity($table);
        $preset = $table->visual_preset ? $this->preset($table->visual_preset) : null;

        if ($preset && $preset['style'] === $style) {
            foreach (['png', 'jpg', 'jpeg', 'webp'] as $ext) {
                $candidate = 'public/images/coth/presets/'.$preset['slug'].'/'.$cap.'.'.$ext;
                if (is_file(public_path($candidate))) {
                    return asset($candidate);
                }
            }
            foreach (['png', 'jpg', 'jpeg', 'webp'] as $ext) {
                $previewCandidate = 'public/images/coth/presets/'.$preset['slug'].'/preview.'.$ext;
                if (is_file(public_path($previewCandidate))) {
                    return asset($previewCandidate);
                }
            }
        }

        if ($style === 'couch') {
            return asset('public/images/coth/tables/couch.png');
        }

        return asset('public/images/coth/tables/'.$cap.'.png');
    }

    /**
     * Chip text under the preview image: "Couch", "City View · High Table", or ''.
     */
    public function chipFor(CothTable $table)
    {
        $style = $this->styleFor($table);
        if ($style === 'couch') {
            return 'Lounge couch';
        }
        if ($style === 'high_table' && (int) $table->is_city_view === 1) {
            return 'City View · High Table';
        }
        if ($style === 'high_table') {
            return 'High Table';
        }

        return '';
    }

    /**
     * Presets available for a given table (filtered by capacity + style).
     * Used by admin selects so the dropdown never shows nonsensical options
     * (e.g. a couch preset on a 10-seat standard table).
     */
    public function presetsForTable(CothTable $table)
    {
        $style = $this->styleFor($table);
        $cap = (int) $table->capacity;

        return array_values(array_filter($this->presets(), function ($p) use ($style, $cap) {
            if ($p['style'] !== $style) {
                return false;
            }
            if (! empty($p['applies_to_capacities']) && ! in_array($cap, $p['applies_to_capacities'], true)) {
                return false;
            }

            return true;
        }));
    }

    protected function normalizedCapacity(CothTable $table)
    {
        $cap = (int) $table->capacity;
        if (in_array($cap, [2, 3, 4, 6, 8, 10], true)) {
            return $cap;
        }
        if ($cap <= 2) {
            return 2;
        }
        if ($cap >= 10) {
            return 10;
        }

        return $cap % 2 === 0 ? $cap : $cap + 1;
    }
}
