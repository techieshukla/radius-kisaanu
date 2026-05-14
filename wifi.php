<?php
declare(strict_types=1);

$qs = isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== '' ? ('?' . $_SERVER['QUERY_STRING']) : '';
header('Location: /login' . $qs, true, 302);
exit;
