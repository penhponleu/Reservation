<?php

// Vercel → every request comes here → run Laravel like public/index.php
// file is in /api → Laravel thinks "/api" is the base URL → /api/login becomes /login
$_SERVER['SCRIPT_NAME']     = '/index.php';
$_SERVER['PHP_SELF']        = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__.'/../public/index.php';

require __DIR__.'/../public/index.php';
