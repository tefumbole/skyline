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
                'slug' => 'couch-gray',
                'name' => 'Curved couch · Shades of gray',
                'style' => 'couch',
                'applies_to_capacities' => [4, 6, 8, 10],
                'swatch' => ['#D5D5D5', '#6E6E6E', '#5C3A1E'],
                'tabletop_material' => 'Walnut',
                'tabletop_color' => '#5C3A1E',
                'upholstery_color' => '#D5D5D5',
                'cushion_colors' => 'Charcoal and silver gray',
                'trim_color' => '#C6A15B',
            ],
            [
                'slug' => 'couch-teal',
                'name' => 'Curved couch · Teal blue',
                'style' => 'couch',
                'applies_to_capacities' => [4, 6, 8, 10],
                'swatch' => ['#3E9AA8', '#0E4F55', '#5C3A1E'],
                'tabletop_material' => 'Walnut',
                'tabletop_color' => '#5C3A1E',
                'upholstery_color' => '#1F7A86',
                'cushion_colors' => 'Deep teal and aqua',
                'trim_color' => '#C6A15B',
            ],
            [
                'slug' => 'high-gray',
                'name' => 'High chairs · Shades of gray',
                'style' => 'high_table',
                'applies_to_capacities' => [2, 3, 4, 6],
                'swatch' => ['#D5D5D5', '#6E6E6E', '#C6A15B'],
                'tabletop_material' => 'Walnut',
                'tabletop_color' => '#5C3A1E',
                'upholstery_color' => '#D5D5D5',
                'cushion_colors' => 'Charcoal gray',
                'trim_color' => '#C6A15B',
            ],
            [
                'slug' => 'high-teal',
                'name' => 'High chairs · Teal blue',
                'style' => 'high_table',
                'applies_to_capacities' => [2, 3, 4, 6],
                'swatch' => ['#1F7A86', '#0E4F55', '#C6A15B'],
                'tabletop_material' => 'Walnut',
                'tabletop_color' => '#5C3A1E',
                'upholstery_color' => '#1F7A86',
                'cushion_colors' => 'Deep teal',
                'trim_color' => '#C6A15B',
            ],
            [
                'slug' => 'chair-gray',
                'name' => 'Regular chairs · Shades of gray',
                'style' => 'standard',
                'applies_to_capacities' => [4],
                'swatch' => ['#D5D5D5', '#6E6E6E', '#C6A15B'],
                'tabletop_material' => 'Walnut',
                'tabletop_color' => '#5C3A1E',
                'upholstery_color' => '#C8C8C8',
                'cushion_colors' => 'Gray',
                'trim_color' => '#C6A15B',
            ],
            [
                'slug' => 'chair-teal',
                'name' => 'Regular chairs · Teal blue',
                'style' => 'standard',
                'applies_to_capacities' => [4],
                'swatch' => ['#7EB8C4', '#1F7A86', '#C6A15B'],
                'tabletop_material' => 'Walnut',
                'tabletop_color' => '#5C3A1E',
                'upholstery_color' => '#7EB8C4',
                'cushion_colors' => 'Teal blue',
                'trim_color' => '#C6A15B',
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
     * Furniture style used for art and chips.
     * Couch and an explicit high table win. A city-view flag alone does not
     * turn a six-seat table into a high table.
     */
    public function styleFor(CothTable $table)
    {
        if ((string) $table->seating_style === 'couch') {
            return 'couch';
        }
        if ((string) $table->seating_style === 'high_table' || (int) $table->is_high_table === 1) {
            return 'high_table';
        }

        return 'standard';
    }

    /**
     * Stored colours for this table, falling back to the preset catalogue.
     */
    public function colorsFor(CothTable $table)
    {
        $preset = $table->visual_preset ? $this->preset($table->visual_preset) : null;

        return [
            'tabletop_material' => $table->tabletop_material ?: ($preset['tabletop_material'] ?? ''),
            'tabletop_color' => $table->tabletop_color ?: ($preset['tabletop_color'] ?? ''),
            'upholstery_color' => $table->upholstery_color ?: ($preset['upholstery_color'] ?? ''),
            'cushion_colors' => $table->cushion_colors ?: ($preset['cushion_colors'] ?? ''),
            'trim_color' => $table->trim_color ?: ($preset['trim_color'] ?? ''),
        ];
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
        $preset = $this->resolvePreset($style, $cap, $table->visual_preset);

        if ($preset) {
            foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
                $found = $this->imageAsset('coth/presets/'.$preset['slug'].'/'.$cap.'.'.$ext);
                if ($found) {
                    return $found;
                }
            }
            foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
                $found = $this->imageAsset('coth/presets/'.$preset['slug'].'/preview.'.$ext);
                if ($found) {
                    return $found;
                }
            }
        }

        if ($style === 'couch') {
            return $this->imageAsset('coth/tables/couch.png') ?: asset('public/images/coth/tables/couch.png');
        }

        return $this->imageAsset('coth/tables/'.$cap.'.png') ?: asset('public/images/coth/tables/'.$cap.'.png');
    }

    /**
     * Preview image for a template at a given seat count.
     */
    public function templateImage($slug, $capacity)
    {
        $cap = (int) $capacity;
        foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
            $found = $this->imageAsset('coth/presets/'.$slug.'/'.$cap.'.'.$ext);
            if ($found) {
                return $found;
            }
        }
        foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
            $found = $this->imageAsset('coth/presets/'.$slug.'/preview.'.$ext);
            if ($found) {
                return $found;
            }
        }

        return null;
    }

    /**
     * Public URL for a file under public/images, checking the real disk path.
     */
    protected function imageAsset($relative)
    {
        $relative = ltrim(str_replace('\\', '/', $relative), '/');
        $web = 'public/images/'.$relative;
        $disk = is_file(public_path('images/'.$relative)) ? public_path('images/'.$relative) : public_path($web);
        if (is_file($disk)) {
            return asset($web).'?v='.filemtime($disk);
        }

        return null;
    }

    /**
     * Chip text under the preview image: "Couch", "City View · High Table", or ''.
     */
    public function chipFor(CothTable $table)
    {
        $style = $this->styleFor($table);
        if ($style === 'couch') {
            return 'Curved couch';
        }
        if ($style === 'high_table' && (int) $table->is_city_view === 1) {
            return 'City View · High Table';
        }
        if ($style === 'high_table') {
            return 'High chairs';
        }

        return 'Regular chairs';
    }

    /**
     * Presets available for a given table (filtered by capacity + style).
     * Used by admin selects so the dropdown never shows nonsensical options
     * (e.g. a couch preset on a 10-seat standard table).
     */
    public function presetsForTable(CothTable $table)
    {
        return $this->choicesFor($this->styleFor($table), (int) $table->capacity);
    }

    /**
     * Library colours for one style and seat count.
     */
    public function choicesFor($style, $capacity)
    {
        $cap = (int) $capacity;

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

    /**
     * Keep a chosen colour when it belongs to this style and size.
     * Otherwise use the first library template that does.
     */
    public function resolvePreset($style, $capacity, $slug = null)
    {
        $choices = $this->choicesFor($style, $capacity);
        foreach ($choices as $row) {
            if ($row['slug'] === $slug) {
                return $row;
            }
        }

        return $choices ? $choices[0] : null;
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
