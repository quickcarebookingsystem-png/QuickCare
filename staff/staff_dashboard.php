<?php
require_once dirname(__DIR__) . '/functions.php';
protect_page();
app_header('QuickCare - Staff Dashboard');
app_start('staff', 'dashboard');
render_dashboard("staff");
app_end();
?>
