<?php
require_once dirname(__DIR__) . '/functions.php';
protect_page();
app_header('QuickCare');
app_start('staff', 'users');
render_users();
app_end();
?>
