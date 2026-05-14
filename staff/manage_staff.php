<?php
require_once dirname(__DIR__) . '/functions.php';
protect_page();
app_header('QuickCare');
app_start('admin', 'staff');
render_staff();
app_end();
?>
