<?php
require_once __DIR__ . '/../functions.php';
protect_page();
app_header('Manage Time Slots');
app_start('admin', 'availability');
render_time_slots();
app_end();
?>
