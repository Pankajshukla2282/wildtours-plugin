<?php

defined('ABSPATH') || exit;

?>

<div class="wrap">

<h1>Plugin Settings</h1>

<form method="post" action="options.php">

<?php

settings_fields('pwt_settings_group');

do_settings_sections('pwt-settings');

submit_button();

?>

</form>

</div>