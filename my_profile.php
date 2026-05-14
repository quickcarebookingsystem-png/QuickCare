<?php
require_once __DIR__ . '/functions.php';
app_header('QuickCare');
$role = $_SESSION['QuickCare_role'] ?? 'user';
app_start($role, 'profile');
render_profile($role);
app_end();
?>
