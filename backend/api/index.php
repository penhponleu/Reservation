<?php

// Vercel entry point: every request is sent here, then handled by Laravel.
// Pretend we are public/index.php so Laravel doesn't treat "/api" as the base folder.
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__.'/../public/index.php';

require __DIR__.'/../public/index.php';
