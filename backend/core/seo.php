<?php
declare(strict_types=1);

/**
 * Server-side SEO: everything in Admin → Site Settings → SEO is written into the page <head>
 * before it is sent, so Google, WhatsApp, LinkedIn and Facebook see it without running JavaScript.
 */

/** Public website address without trailing slash (setting, else the current request's address) */
function seo_site_url(): string
{
    $u = rtrim(trim((string) setting('seo_site_url', '')), '/');
    if ($u !== '' && preg_match('#^https?://#i', $u)) {
        return $u;
    }
    $https  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    return ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
}

/** Absolute URL of a media library image on the public site, or '' */
function seo_image_url($id): string
{
    $m = media_find($id ? (int) $id : null);
    return $m ? seo_site_url() . '/uploads/' . rawurlencode($m['file_name']) : '';
}

function seo_head_html(): string
{
    $site  = (string) setting('site_name', 'G2R Global Solutions');
    $title = trim((string) setting('seo_title', '')) ?: $site;
    $desc  = trim((string) setting('seo_description', ''));
    $keys  = trim((string) setting('seo_keywords', ''));
    $url   = seo_site_url() . '/';
    $img   = seo_image_url(setting('seo_og_image', ''));
    $imgM  = media_find((int) setting('seo_og_image', '0'));
    $icon  = media_find((int) setting('site_favicon', '0'));
    $logo  = seo_image_url(setting('site_logo', ''));
    $loc   = (string) setting('seo_locale', 'en_IN') ?: 'en_IN';
    $tw    = trim((string) setting('seo_twitter_handle', ''));
    $robots = setting('seo_noindex', '0') === '1' ? 'noindex, nofollow' : 'index, follow, max-image-preview:large';

    $t = [];
    $t[] = '<title>' . e($title) . '</title>';
    if ($desc !== '') $t[] = '<meta name="description" content="' . e($desc) . '">';
    if ($keys !== '') $t[] = '<meta name="keywords" content="' . e($keys) . '">';
    $t[] = '<meta name="robots" content="' . $robots . '">';
    $t[] = '<link rel="canonical" href="' . e($url) . '">';
    if ($icon) {
        $t[] = '<link rel="icon" href="/uploads/' . e(rawurlencode($icon['file_name'])) . '" type="' . e($icon['mime']) . '">';
        $t[] = '<link rel="apple-touch-icon" href="/uploads/' . e(rawurlencode($icon['file_name'])) . '">';
    }
    // Open Graph (WhatsApp, LinkedIn, Facebook)
    $t[] = '<meta property="og:type" content="website">';
    $t[] = '<meta property="og:site_name" content="' . e($site) . '">';
    $t[] = '<meta property="og:locale" content="' . e($loc) . '">';
    $t[] = '<meta property="og:url" content="' . e($url) . '">';
    $t[] = '<meta property="og:title" content="' . e($title) . '">';
    if ($desc !== '') $t[] = '<meta property="og:description" content="' . e($desc) . '">';
    if ($img !== '') {
        $t[] = '<meta property="og:image" content="' . e($img) . '">';
        if (!empty($imgM['width'])) {
            $t[] = '<meta property="og:image:width" content="' . (int) $imgM['width'] . '">';
            $t[] = '<meta property="og:image:height" content="' . (int) $imgM['height'] . '">';
        }
        $t[] = '<meta property="og:image:alt" content="' . e(alt_is_weak((string) ($imgM['alt_text'] ?? '')) ? $title : $imgM['alt_text']) . '">';
    }
    // X / Twitter
    $t[] = '<meta name="twitter:card" content="' . ($img !== '' ? 'summary_large_image' : 'summary') . '">';
    $t[] = '<meta name="twitter:title" content="' . e($title) . '">';
    if ($desc !== '') $t[] = '<meta name="twitter:description" content="' . e($desc) . '">';
    if ($img !== '') $t[] = '<meta name="twitter:image" content="' . e($img) . '">';
    if ($tw !== '') $t[] = '<meta name="twitter:site" content="' . e('@' . ltrim($tw, '@')) . '">';
    // Webmaster verification
    if ($g = trim((string) setting('seo_google_verification', ''))) $t[] = '<meta name="google-site-verification" content="' . e($g) . '">';
    if ($b = trim((string) setting('seo_bing_verification', ''))) $t[] = '<meta name="msvalidate.01" content="' . e($b) . '">';

    // Structured data (Google rich results / knowledge panel)
    $sameAs = array_values(array_filter(array_map(
        static fn ($k) => trim((string) setting($k, '')),
        ['social_linkedin', 'social_instagram', 'social_facebook', 'social_youtube', 'social_twitter']
    )));
    $ld = array_filter([
        '@context'    => 'https://schema.org',
        '@type'       => (string) setting('seo_business_type', 'ProfessionalService') ?: 'ProfessionalService',
        'name'        => $site,
        'url'         => $url,
        'logo'        => $logo ?: null,
        'image'       => $img ?: ($logo ?: null),
        'description' => $desc ?: null,
        'telephone'   => trim((string) setting('contact_phone', '')) ?: null,
        'email'       => trim((string) setting('contact_email', '')) ?: null,
        'address'     => ($addr = trim((string) setting('contact_address', ''))) !== ''
            ? ['@type' => 'PostalAddress', 'streetAddress' => preg_replace('/\s*\n\s*/', ', ', $addr), 'addressCountry' => 'IN']
            : null,
        'sameAs'      => $sameAs ?: null,
    ], static fn ($v) => $v !== null);
    $t[] = '<script type="application/ld+json">' . json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) . '</script>';

    // Google Analytics 4
    $ga = trim((string) setting('seo_ga_id', ''));
    if (preg_match('/^G-[A-Z0-9]{4,20}$/i', $ga)) {
        $t[] = '<script async src="https://www.googletagmanager.com/gtag/js?id=' . e($ga) . '"></script>';
        $t[] = "<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments)}gtag('js',new Date());gtag('config'," . json_encode($ga) . ");</script>";
    }
    return implode("\n    ", $t);
}

/** Put the SEO tags into the built index.html (replaces its static title/description) */
function seo_inject(string $html): string
{
    $html = preg_replace('#<title>.*?</title>\s*#is', '', $html, 1);
    $html = preg_replace('#<meta\s+name="description"[^>]*>\s*#i', '', $html, 1);
    return preg_replace('#</head>#i', '    ' . seo_head_html() . "\n  </head>", $html, 1);
}

function seo_robots_txt(): string
{
    if (setting('seo_noindex', '0') === '1') {
        return "User-agent: *\nDisallow: /\n";
    }
    return "User-agent: *\nAllow: /\nDisallow: /admin/\nDisallow: /api/\n\nSitemap: " . seo_site_url() . "/sitemap.xml\n";
}

function seo_sitemap_xml(): string
{
    $last = (string) (DB::value('SELECT MAX(updated_at) FROM sections') ?: date('Y-m-d'));
    $img  = seo_image_url(setting('seo_og_image', ''));
    return '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
        . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n"
        . '  <url>' . "\n"
        . '    <loc>' . e(seo_site_url() . '/') . '</loc>' . "\n"
        . '    <lastmod>' . e(date('Y-m-d', strtotime($last) ?: time())) . '</lastmod>' . "\n"
        . '    <changefreq>weekly</changefreq>' . "\n"
        . '    <priority>1.0</priority>' . "\n"
        . ($img !== '' ? '    <image:image><image:loc>' . e($img) . '</image:loc></image:image>' . "\n" : '')
        . '  </url>' . "\n"
        . '</urlset>' . "\n";
}
