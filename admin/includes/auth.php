<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

initDatabase();
ensureHomePageMenu();
seedWebsiteNavigation();
syncWebsiteNavigation();
requireLogin();
$user = currentUser();
