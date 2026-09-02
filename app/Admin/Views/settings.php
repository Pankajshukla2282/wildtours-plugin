<?php defined('ABSPATH') || exit; ?>

<div class="wrap">

<h1>Plugin Settings</h1>

<?php if (isset($_GET['page']) && $_GET['page'] === 'pwt-settings'): ?>
<nav class="nav-tab-wrapper">
	<a href="?page=pwt-settings&tab=general" class="nav-tab<?php echo (isset($_GET['tab']) && $_GET['tab'] === 'general') ? ' nav-tab-active' : ''; ?>"><?php esc_html_e('General', 'wildtours-plugin'); ?></a>
	<a href="?page=pwt-settings&tab=payments" class="nav-tab<?php echo (isset($_GET['tab']) && $_GET['tab'] === 'payments') ? ' nav-tab-active' : ''; ?>"><?php esc_html_e('Payments', 'wildtours-plugin'); ?></a>
	<a href="?page=pwt-settings&tab=availability" class="nav-tab<?php echo (isset($_GET['tab']) && $_GET['tab'] === 'availability') ? ' nav-tab-active' : ''; ?>"><?php esc_html_e('Availability', 'wildtours-plugin'); ?></a>
</nav>
<?php endif; ?>

<form method="post" action="options.php">

<?php settings_fields('pwt_settings_group'); ?>

<?php do_settings_sections('pwt-settings'); ?>

<button type="submit" class="button button-primary"><?php esc_html_e('Save Changes', 'wildtours-plugin'); ?></button>

</form>

</div>