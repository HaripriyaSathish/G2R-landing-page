<?php
declare(strict_types=1);

final class Content
{
    private static ?array $schema = null;

    public static function schema(): array
    {
        if (self::$schema === null) {
            self::$schema = require __DIR__ . '/sections.php';
        }
        return self::$schema;
    }

    public static function def(string $slug): ?array
    {
        return self::schema()[$slug] ?? null;
    }

    public static function groupDef(string $slug, string $grp): ?array
    {
        return self::schema()[$slug]['groups'][$grp] ?? null;
    }

    public static function decode(?string $json): array
    {
        $d = json_decode((string) $json, true);
        return is_array($d) ? $d : [];
    }

    /** Normalise posted form values according to field definitions. */
    public static function sanitize(array $fields, array $input, array $existing = []): array
    {
        $out = $existing;
        foreach ($fields as $f) {
            if (empty($f['key'])) {
                continue;
            }
            $k = $f['key'];
            $v = $input[$k] ?? null;
            switch ($f['type']) {
                case 'checkbox':
                    $out[$k] = !empty($v);
                    break;
                case 'image':
                    $out[$k] = $v !== null && $v !== '' ? (int) $v : null;
                    break;
                case 'number':
                    $out[$k] = $v === null || $v === '' ? null : (float) $v;
                    break;
                case 'color':
                    $v = trim((string) $v);
                    $out[$k] = preg_match('/^#[0-9a-f]{3,8}$/i', $v) ? $v : '';
                    break;
                case 'tags':
                    $parts = array_filter(array_map('trim', explode(',', (string) $v)), 'strlen');
                    $out[$k] = array_values($parts);
                    break;
                case 'list':
                    $parts = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $v)), 'strlen');
                    $out[$k] = array_values($parts);
                    break;
                default:
                    $out[$k] = str_replace("\r\n", "\n", trim((string) $v));
            }
        }
        return $out;
    }

    /** Resolve stored values into a frontend-ready structure (image ids -> urls). */
    public static function present(array $fields, array $data): array
    {
        $out = [];
        foreach ($fields as $f) {
            if (empty($f['key'])) {
                continue;
            }
            $k = $f['key'];
            $v = $data[$k] ?? null;
            if ($f['type'] === 'image') {
                $m = media_find($v ? (int) $v : null);
                $out[$k] = $m ? [
                    'id' => (int) $m['id'],
                    'url' => media_url((int) $m['id']),
                    'alt' => $m['alt_text'],
                    'width' => $m['width'] ? (int) $m['width'] : null,
                    'height' => $m['height'] ? (int) $m['height'] : null,
                ] : null;
            } elseif ($f['type'] === 'list' && $k === 'states') {
                // "State | Tag" lines -> objects
                $out[$k] = array_map(static function ($line) {
                    [$name, $tag] = array_pad(array_map('trim', explode('|', (string) $line, 2)), 2, '');
                    return ['name' => $name, 'tag' => $tag];
                }, (array) ($v ?? []));
            } elseif (in_array($f['type'], ['tags', 'list'], true)) {
                $out[$k] = array_values((array) ($v ?? []));
            } elseif ($f['type'] === 'checkbox') {
                $out[$k] = (bool) $v;
            } else {
                $out[$k] = $v ?? '';
            }
        }
        return $out;
    }

    public static function items(string $slug, string $grp, bool $activeOnly = false): array
    {
        if ($activeOnly) {
            // Public pages: load every visible item once, then group in PHP
            static $active = null;
            if ($active === null) {
                $active = [];
                foreach (DB::all('SELECT * FROM section_items WHERE is_active = 1 ORDER BY sort_order, id') as $r) {
                    $active[$r['section_slug'] . "\0" . $r['grp']][] = $r;
                }
            }
            return $active[$slug . "\0" . $grp] ?? [];
        }
        $sql = 'SELECT * FROM section_items WHERE section_slug = ? AND grp = ?' . ($activeOnly ? ' AND is_active = 1' : '') . ' ORDER BY sort_order, id';
        return DB::all($sql, [$slug, $grp]);
    }

    /** Full public payload for the React frontend. */
    public static function sitePayload(): array
    {
        $s = settings_all();
        $img = static function ($id) {
            $m = media_find($id ? (int) $id : null);
            return $m ? ['id' => (int) $m['id'], 'url' => media_url((int) $m['id']), 'alt' => $m['alt_text']] : null;
        };

        $payload = [
            'site' => [
                'name'        => $s['site_name'] ?? '',
                'tagline'     => $s['site_tagline'] ?? '',
                'logo'        => $img($s['site_logo'] ?? null),
                'footer_logo' => $img($s['footer_logo'] ?? null),
                'favicon'     => $img($s['site_favicon'] ?? null),
                'phone'       => $s['contact_phone'] ?? '',
                'phone_alt'   => $s['contact_phone_alt'] ?? '',
                'email'       => $s['contact_email'] ?? '',
                'address'     => $s['contact_address'] ?? '',
                'map_url'     => $s['contact_map_url'] ?? '',
                'whatsapp'    => $s['whatsapp_number'] ?? '',
            ],
            'social' => array_values(array_filter([
                ['key' => 'linkedin',  'url' => $s['social_linkedin'] ?? ''],
                ['key' => 'instagram', 'url' => $s['social_instagram'] ?? ''],
                ['key' => 'facebook',  'url' => $s['social_facebook'] ?? ''],
                ['key' => 'youtube',   'url' => $s['social_youtube'] ?? ''],
                ['key' => 'twitter',   'url' => $s['social_twitter'] ?? ''],
            ], static fn ($x) => $x['url'] !== '')),
            'seo' => [
                'title'       => $s['seo_title'] ?? '',
                'description' => $s['seo_description'] ?? '',
                'keywords'    => $s['seo_keywords'] ?? '',
                'og_image'    => $img($s['seo_og_image'] ?? null),
            ],
            'theme' => [
                'primary'    => $s['theme_primary'] ?? '#111C50',
                'indigo'     => $s['theme_indigo'] ?? '#1D138C',
                'secondary'  => $s['theme_secondary'] ?? '#232A5A',
                'dark'       => $s['theme_dark'] ?? '#0D1338',
                'accent'     => $s['theme_accent'] ?? '#F36B16',
                'light'      => $s['theme_light'] ?? '#F6F7FC',
                'muted'      => $s['theme_muted'] ?? '#AEB5C6',
                'font_heading' => $s['theme_font_heading'] ?? 'Manrope',
                'font_body'    => $s['theme_font_body'] ?? 'Inter',
                'animations'   => ($s['theme_animations'] ?? '1') === '1',
            ],
            'sections' => [],
        ];

        $rows = DB::all('SELECT * FROM sections WHERE is_active = 1 ORDER BY sort_order, id');
        foreach ($rows as $row) {
            $def = self::def($row['slug']);
            if (!$def) {
                continue;
            }
            $section = [
                'slug'  => $row['slug'],
                'name'  => $row['name'],
                'order' => (int) $row['sort_order'],
                'data'  => self::present($def['fields'], self::decode($row['data'])),
                'items' => [],
            ];
            foreach ($def['groups'] as $grp => $gdef) {
                $section['items'][$grp] = array_map(
                    static fn ($it) => ['id' => (int) $it['id']] + self::present($gdef['fields'], self::decode($it['data'])),
                    self::items($row['slug'], $grp, true)
                );
            }
            $payload['sections'][] = $section;
        }
        // Header nav is always needed (footer reuses it) even if header section hidden.
        return $payload;
    }
}
