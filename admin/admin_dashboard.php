<?php
require_once dirname(__DIR__) . '/functions.php';
protect_page();
app_header('QuickCare - Admin Dashboard');
app_start('admin', 'dashboard');
render_dashboard("admin");
app_end();
?>
