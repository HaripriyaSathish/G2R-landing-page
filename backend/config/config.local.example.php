<?php
// Copy this file to config.local.php and set your local MySQL details.
// config.local.php is ignored by git, so your password never reaches GitHub.
return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'g2r_landing',
        'user' => 'root',
        'pass' => 'your-mysql-password',
    ],
];
