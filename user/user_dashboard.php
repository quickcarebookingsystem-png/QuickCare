<?php
require_once dirname(__DIR__) . '/functions.php';
protect_page();
app_header('QuickCare - User Dashboard');
app_start('user', 'dashboard');
render_dashboard("user");
app_end();
?>
