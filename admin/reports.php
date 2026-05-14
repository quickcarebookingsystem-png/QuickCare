<?php
require_once dirname(__DIR__) . '/functions.php';
protect_page();
app_header('QuickCare');
app_start('admin', 'reports');
render_reports();
app_end();
?>
