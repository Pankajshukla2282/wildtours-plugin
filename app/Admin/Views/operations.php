<div class="wrap">
    <h1><?php esc_html_e('Panna Wild Tour Operations', 'wildtours-plugin'); ?></h1>
    <div class="pwt-admin-cards">
    <?php
    global $wpdb;
    $t = Schema::tables();
    $counts = [
        'bookings' => (int)$wpdb->get_var("SELECT COUNT(*) FROM {$t['bookings']}"),
        'pending' => (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t['bookings']} WHERE status=%s",'pending')),
        'confirmed' => (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t['bookings']} WHERE status IN (%s,%s)",'confirmed','paid')),
        'customers' => (int)$wpdb->get_var("SELECT COUNT(*) FROM {$t['customers']}"),
        'payments' => (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t['payments']} WHERE status=%s",'paid')),
    ];
    foreach ($counts as $label=>$value):
    ?>
        <div class="pwt-admin-card"><strong><?php echo esc_html(number_format_i18n($value)); ?></strong><span><?php echo esc_html(ucwords(str_replace('_',' ',$label))); ?></span></div>
    <?php endforeach; ?>
    </div>
    <h2><?php esc_html_e('Quick Actions', 'wildtours-plugin'); ?></h2>
    <p>
        <a class="button button-primary" href="<?php echo esc_url(admin_url('edit.php?post_type=pwt_booking')); ?>"><?php esc_html_e('Manage Bookings', 'wildtours-plugin'); ?></a>
        <a class="button" href="<?php echo esc_url(admin_url('edit.php?post_type=pwt_room_unit')); ?>"><?php esc_html_e('Manage Rooms', 'wildtours-plugin'); ?></a>
        <a class="button" href="<?php echo esc_url(admin_url('edit.php?post_type=pwt_safari_schedule')); ?>"><?php esc_html_e('Manage Safari Schedules', 'wildtours-plugin'); ?></a>
        <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=pwt-availability')); ?>"><?php esc_html_e('Availability', 'wildtours-plugin'); ?></a>
        <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=pwt-pricing')); ?>"><?php esc_html_e('Pricing', 'wildtours-plugin'); ?></a>
    </p>
</div>