<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

initDatabase();
$appTimezone = getSetting('timezone', 'Asia/Jakarta');
date_default_timezone_set(in_array($appTimezone, timezone_identifiers_list(), true) ? $appTimezone : 'Asia/Jakarta');
ensureHomePageMenu();
seedWebsiteNavigation();
syncWebsiteNavigation();
requireLogin();
$user = currentUser();
