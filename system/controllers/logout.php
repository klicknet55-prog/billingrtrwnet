<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Expires: Tue, 01 Jan 2000 00:00:00 GMT");
header("Pragma: no-cache");

run_hook('customer_logout'); #HOOK
if (session_status() == PHP_SESSION_NONE) session_start();

// Decide redirect target before clearing auth/session state.
$wasAdmin = !empty($_SESSION['aid']) || !empty($_COOKIE['aid']);
$wasCustomer = !empty($_SESSION['uid']) || !empty($_COOKIE['uid']);

$targetUrl = rtrim(APP_URL, '/') . '/';
if ($wasAdmin) {
	$targetUrl = rtrim(APP_URL, '/') . '/admin';
} else if ($wasCustomer) {
	$targetUrl = rtrim(APP_URL, '/') . '/';
}

Admin::removeCookie();
User::removeCookie();
session_destroy();
r2($targetUrl, 'warning', Lang::T('Logout Successful'));
