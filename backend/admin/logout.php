<?php
declare(strict_types=1);
require dirname(__DIR__) . '/core/bootstrap.php';
start_session();
$_SESSION = [];
session_destroy();
redirect(admin_url('login.php'));
