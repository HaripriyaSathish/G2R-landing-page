<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
define('UPLOAD_PATH', BASE_PATH . '/uploads');

$GLOBALS['config'] = require BASE_PATH . '/config/config.php';
date_default_timezone_set($GLOBALS['config']['timezone'] ?? 'UTC');

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/Mailer.php';
require_once __DIR__ . '/Content.php';
require_once __DIR__ . '/seo.php';

function config(string $key, $default = null)
{
    $value = $GLOBALS['config'];
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return $value;
}
