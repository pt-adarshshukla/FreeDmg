<?php
/**
 * FreeDmg - Admin Logout Handler
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

logout_admin();
header("Location: login.php");
exit;
