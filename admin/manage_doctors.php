<?php
require_once dirname(__DIR__) . '/functions.php';
protect_page();
app_header('QuickCare');
app_start('admin', 'doctors');
render_doctors("admin");
app_end();
?>
