<?php

defined('ABSPATH') || exit;

if (!current_user_can('manage_options')) {
    wp_die(esc_html__('You are not allowed to access this page.', 'wildtours-plugin'));
}

$notice = '';
$error = '';

if (($_POST['action'] ?? '') === 'pwt_quick_create_content') {
    check_admin_referer('pwt_quick_create_content');

    $postType = sanitize_key($_POST['post_type'] ?? '');
    $allowedTypes = [
        'pwt_destination',
        'pwt_safari',
        'pwt_package',
        'pwt_resort',
        'pwt_vehicle',
        'pwt_faq',
        'pwt_testimonial',
        'pwt_review',
    ];

    if (!in_array($postType, $allowedTypes, true)) {
        $error = __('Invalid content type selected.', 'wildtours-plugin');
    } else {
        $title = sanitize_text_field($_POST['title'] ?? '');
        $slug = sanitize_title($_POST['slug'] ?? '');
        $excerpt = sanitize_textarea_field($_POST['excerpt'] ?? '');
        $content = wp_kses_post($_POST['content'] ?? '');
        $status = sanitize_key($_POST['status'] ?? 'draft');
        $status = in_array($status, ['draft', 'publish'], true) ? $status : 'draft';

        if ($title === '') {
            $error = __('Title is required.', 'wildtours-plugin');
        } else {
            $postId = wp_insert_post([
                'post_type' => $postType,
                'post_title' => $title,
                'post_name' => $slug,
                'post_excerpt' => $excerpt,
                'post_content' => $content,
                'post_status' => $status,
            ], true);

            if (is_wp_error($postId)) {
                $error = __('Unable to create content. Please try again.', 'wildtours-plugin');
            } else {
                $metaFields = [
                    'regular_price',
                    'offer_price',
                    'duration',
                    'peak_multiplier',
                    'shoulder_multiplier',
                    'monsoon_multiplier',
                    'rating',
                    'guest_city',
                ];

                foreach ($metaFields as $field) {
                    if (isset($_POST[$field]) && $_POST[$field] !== '') {
                        update_post_meta($postId, $field, sanitize_text_field((string) $_POST[$field]));
                    }
                }

                if (isset($_POST['verified'])) {
                    update_post_meta($postId, 'verified', sanitize_key((string) $_POST['verified']) === '1' ? '1' : '0');
                }

                $featuredMedia = absint($_POST['featured_media'] ?? 0);
                if ($featuredMedia > 0) {
                    set_post_thumbnail($postId, $featuredMedia);
                }

                $taxonomyMap = [
                    'pwt_season' => 'pwt_season',
                    'pwt_activity' => 'pwt_activity',
                    'pwt_safari_zone' => 'pwt_safari_zone',
                    'pwt_vehicle_type' => 'pwt_vehicle_type',
                    'pwt_package_category' => 'pwt_package_category',
                    'pwt_destination_category' => 'pwt_destination_category',
                ];

                foreach ($taxonomyMap as $field => $taxonomy) {
                    if (!isset($_POST[$field])) {
                        continue;
                    }

                    $ids = array_values(array_filter(array_map('absint', array_map('trim', explode(',', sanitize_text_field((string) $_POST[$field]))))));
                    if (!empty($ids) && taxonomy_exists($taxonomy)) {
                        wp_set_object_terms($postId, $ids, $taxonomy);
                    }
                }

                $notice = sprintf(
                    /* translators: 1: content type, 2: post ID */
                    __('Content created successfully (%1$s, ID %2$d).', 'wildtours-plugin'),
                    esc_html($postType),
                    (int) $postId
                );
            }
        }
    }
}

$seedStatus = sanitize_key($_GET['pwt_seed_status'] ?? '');
$seedTerms = absint($_GET['pwt_seed_terms'] ?? 0);
$seedPosts = absint($_GET['pwt_seed_posts'] ?? 0);
$seedMedia = absint($_GET['pwt_seed_media'] ?? 0);
$seedProfile = sanitize_key($_GET['pwt_seed_profile'] ?? 'basic');

$taxonomyTerms = [
    'pwt_season' => get_terms(['taxonomy' => 'pwt_season', 'hide_empty' => false]),
    'pwt_activity' => get_terms(['taxonomy' => 'pwt_activity', 'hide_empty' => false]),
    'pwt_safari_zone' => get_terms(['taxonomy' => 'pwt_safari_zone', 'hide_empty' => false]),
    'pwt_vehicle_type' => get_terms(['taxonomy' => 'pwt_vehicle_type', 'hide_empty' => false]),
    'pwt_package_category' => get_terms(['taxonomy' => 'pwt_package_category', 'hide_empty' => false]),
    'pwt_destination_category' => get_terms(['taxonomy' => 'pwt_destination_category', 'hide_empty' => false]),
];

$latestMedia = get_posts([
    'post_type' => 'attachment',
    'post_status' => 'inherit',
    'posts_per_page' => 15,
]);
?>
<div class="wrap">
    <h1><?php esc_html_e('Quick Content Forms', 'wildtours-plugin'); ?></h1>
    <p><?php esc_html_e('Create destinations, packages, safaris, resorts, vehicles, FAQs, testimonials, and reviews without opening the block editor.', 'wildtours-plugin'); ?></p>

    <?php if ($notice) : ?>
        <div class="notice notice-success"><p><?php echo esc_html($notice); ?></p></div>
    <?php endif; ?>

    <?php if ($error) : ?>
        <div class="notice notice-error"><p><?php echo esc_html($error); ?></p></div>
    <?php endif; ?>

    <?php if ($seedStatus === 'success') : ?>
        <div class="notice notice-success">
            <p>
                <?php
                echo esc_html(
                    sprintf(
                        /* translators: 1: terms created, 2: posts created */
                        __('Starter content import completed. New terms: %1$d, new posts: %2$d.', 'wildtours-plugin'),
                        $seedTerms,
                        $seedPosts
                    )
                );
                ?>
            </p>
            <p>
                <?php
                echo esc_html(
                    sprintf(
                        /* translators: 1: profile name, 2: featured image assignments */
                        __('Profile: %1$s. Featured images assigned: %2$d.', 'wildtours-plugin'),
                        $seedProfile === 'full' ? __('Full', 'wildtours-plugin') : __('Basic', 'wildtours-plugin'),
                        $seedMedia
                    )
                );
                ?>
            </p>
        </div>
    <?php endif; ?>

    <div class="card" style="max-width: 920px; margin-top: 20px; padding: 14px 18px;">
        <h2 style="margin-top: 0;"><?php esc_html_e('Starter Content Import', 'wildtours-plugin'); ?></h2>
        <p><?php esc_html_e('Generate launch-ready baseline content. Existing posts with matching titles are skipped so you can run this safely multiple times.', 'wildtours-plugin'); ?></p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('pwt_seed_starter_content'); ?>
            <input type="hidden" name="action" value="pwt_seed_starter_content">

            <p>
                <label for="pwt_seed_profile"><strong><?php esc_html_e('Import Profile', 'wildtours-plugin'); ?></strong></label><br>
                <select id="pwt_seed_profile" name="seed_profile">
                    <option value="basic"><?php esc_html_e('Basic (essential launch content)', 'wildtours-plugin'); ?></option>
                    <option value="full"><?php esc_html_e('Full (includes resort, vehicle, review)', 'wildtours-plugin'); ?></option>
                </select>
            </p>

            <p><strong><?php esc_html_e('Include Content Types', 'wildtours-plugin'); ?></strong></p>
            <p>
                <label><input type="checkbox" name="seed_types[]" value="pwt_destination" checked> <?php esc_html_e('Destinations', 'wildtours-plugin'); ?></label><br>
                <label><input type="checkbox" name="seed_types[]" value="pwt_safari" checked> <?php esc_html_e('Safaris', 'wildtours-plugin'); ?></label><br>
                <label><input type="checkbox" name="seed_types[]" value="pwt_package" checked> <?php esc_html_e('Packages', 'wildtours-plugin'); ?></label><br>
                <label><input type="checkbox" name="seed_types[]" value="pwt_faq" checked> <?php esc_html_e('FAQs', 'wildtours-plugin'); ?></label><br>
                <label><input type="checkbox" name="seed_types[]" value="pwt_testimonial" checked> <?php esc_html_e('Testimonials', 'wildtours-plugin'); ?></label><br>
                <label><input type="checkbox" name="seed_types[]" value="pwt_review" checked> <?php esc_html_e('Reviews', 'wildtours-plugin'); ?></label><br>
                <label><input type="checkbox" name="seed_types[]" value="pwt_resort" checked> <?php esc_html_e('Resorts', 'wildtours-plugin'); ?></label><br>
                <label><input type="checkbox" name="seed_types[]" value="pwt_vehicle" checked> <?php esc_html_e('Vehicles', 'wildtours-plugin'); ?></label>
            </p>

            <p>
                <label for="pwt_seed_featured_media"><strong><?php esc_html_e('Featured Media ID (optional)', 'wildtours-plugin'); ?></strong></label><br>
                <input id="pwt_seed_featured_media" class="regular-text" type="number" min="0" name="seed_featured_media" placeholder="0">
            </p>
            <p>
                <label><input type="checkbox" name="seed_use_latest_media" value="1"> <?php esc_html_e('If no ID is provided, use latest uploaded image as featured image', 'wildtours-plugin'); ?></label>
            </p>

            <?php submit_button(__('Import Starter Content', 'wildtours-plugin'), 'secondary', 'submit', false); ?>
        </form>
    </div>

    <form method="post">
        <?php wp_nonce_field('pwt_quick_create_content'); ?>
        <input type="hidden" name="action" value="pwt_quick_create_content">

        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><label for="pwt_post_type"><?php esc_html_e('Content Type', 'wildtours-plugin'); ?></label></th>
                <td>
                    <select id="pwt_post_type" name="post_type" required>
                        <option value="pwt_destination">Destination</option>
                        <option value="pwt_safari">Safari</option>
                        <option value="pwt_package">Package</option>
                        <option value="pwt_resort">Resort / Homestay</option>
                        <option value="pwt_vehicle">Vehicle</option>
                        <option value="pwt_faq">FAQ</option>
                        <option value="pwt_testimonial">Testimonial</option>
                        <option value="pwt_review">Review</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="pwt_title"><?php esc_html_e('Title', 'wildtours-plugin'); ?></label></th>
                <td><input id="pwt_title" class="regular-text" type="text" name="title" required></td>
            </tr>
            <tr>
                <th scope="row"><label for="pwt_slug"><?php esc_html_e('Slug', 'wildtours-plugin'); ?></label></th>
                <td><input id="pwt_slug" class="regular-text" type="text" name="slug" placeholder="auto-if-empty"></td>
            </tr>
            <tr>
                <th scope="row"><label for="pwt_excerpt"><?php esc_html_e('Excerpt', 'wildtours-plugin'); ?></label></th>
                <td><textarea id="pwt_excerpt" class="large-text" rows="2" name="excerpt"></textarea></td>
            </tr>
            <tr>
                <th scope="row"><label for="pwt_content"><?php esc_html_e('Content', 'wildtours-plugin'); ?></label></th>
                <td><textarea id="pwt_content" class="large-text" rows="8" name="content"></textarea></td>
            </tr>
            <tr>
                <th scope="row"><label for="pwt_status"><?php esc_html_e('Status', 'wildtours-plugin'); ?></label></th>
                <td>
                    <select id="pwt_status" name="status">
                        <option value="draft">Draft</option>
                        <option value="publish">Publish</option>
                    </select>
                </td>
            </tr>
        </table>

        <h2><?php esc_html_e('Optional Meta Fields', 'wildtours-plugin'); ?></h2>
        <table class="form-table" role="presentation">
            <tr><th scope="row">Regular Price</th><td><input class="regular-text" type="text" name="regular_price"></td></tr>
            <tr><th scope="row">Offer Price</th><td><input class="regular-text" type="text" name="offer_price"></td></tr>
            <tr><th scope="row">Duration</th><td><input class="regular-text" type="text" name="duration"></td></tr>
            <tr><th scope="row">Peak Multiplier</th><td><input class="regular-text" type="text" name="peak_multiplier"></td></tr>
            <tr><th scope="row">Shoulder Multiplier</th><td><input class="regular-text" type="text" name="shoulder_multiplier"></td></tr>
            <tr><th scope="row">Monsoon Multiplier</th><td><input class="regular-text" type="text" name="monsoon_multiplier"></td></tr>
            <tr><th scope="row">Review Rating (1-5)</th><td><input class="regular-text" type="text" name="rating"></td></tr>
            <tr><th scope="row">Review Guest City</th><td><input class="regular-text" type="text" name="guest_city"></td></tr>
            <tr>
                <th scope="row">Review Verified</th>
                <td>
                    <select name="verified">
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">Featured Media ID</th>
                <td>
                    <input class="regular-text" type="number" min="0" name="featured_media">
                    <p class="description"><?php esc_html_e('Select from recent media IDs listed below.', 'wildtours-plugin'); ?></p>
                </td>
            </tr>
        </table>

        <h2><?php esc_html_e('Taxonomy IDs (comma separated)', 'wildtours-plugin'); ?></h2>
        <table class="form-table" role="presentation">
            <tr><th scope="row">Seasons</th><td><input class="regular-text" type="text" name="pwt_season" placeholder="45,46"></td></tr>
            <tr><th scope="row">Activities</th><td><input class="regular-text" type="text" name="pwt_activity" placeholder="49,50"></td></tr>
            <tr><th scope="row">Safari Zones</th><td><input class="regular-text" type="text" name="pwt_safari_zone" placeholder="41,42"></td></tr>
            <tr><th scope="row">Vehicle Types</th><td><input class="regular-text" type="text" name="pwt_vehicle_type" placeholder="43,44"></td></tr>
            <tr><th scope="row">Package Categories</th><td><input class="regular-text" type="text" name="pwt_package_category" placeholder="47,53"></td></tr>
            <tr><th scope="row">Destination Categories</th><td><input class="regular-text" type="text" name="pwt_destination_category" placeholder="51,52"></td></tr>
        </table>

        <?php submit_button(__('Create Content', 'wildtours-plugin')); ?>
    </form>

    <h2><?php esc_html_e('Reference: Taxonomy Term IDs', 'wildtours-plugin'); ?></h2>
    <?php foreach ($taxonomyTerms as $taxonomy => $terms) : ?>
        <h3><?php echo esc_html($taxonomy); ?></h3>
        <p>
            <?php
            if (is_wp_error($terms) || empty($terms)) {
                esc_html_e('No terms found.', 'wildtours-plugin');
            } else {
                $items = [];
                foreach ($terms as $term) {
                    $items[] = $term->name . ' (#' . $term->term_id . ')';
                }
                echo esc_html(implode(' | ', $items));
            }
            ?>
        </p>
    <?php endforeach; ?>

    <h2><?php esc_html_e('Reference: Recent Media', 'wildtours-plugin'); ?></h2>
    <p>
        <?php
        if (empty($latestMedia)) {
            esc_html_e('No media found yet.', 'wildtours-plugin');
        } else {
            $mediaItems = [];
            foreach ($latestMedia as $media) {
                $mediaItems[] = $media->post_title . ' (#' . $media->ID . ')';
            }
            echo esc_html(implode(' | ', $mediaItems));
        }
        ?>
    </p>

    <h2><?php esc_html_e('Fluent Form Field Keys for Auto Drafts', 'wildtours-plugin'); ?></h2>
    <p><?php esc_html_e('Use these exact field names in Fluent Forms to auto-create draft content:', 'wildtours-plugin'); ?></p>
    <p><strong><?php esc_html_e('Common', 'wildtours-plugin'); ?>:</strong> title, slug, excerpt, content, featured_media</p>
    <p><strong><?php esc_html_e('Package Meta', 'wildtours-plugin'); ?>:</strong> regular_price, offer_price, duration, peak_multiplier, shoulder_multiplier, monsoon_multiplier</p>
    <p><strong><?php esc_html_e('Review Meta', 'wildtours-plugin'); ?>:</strong> rating, guest_city, verified</p>
    <p><strong><?php esc_html_e('Taxonomy IDs', 'wildtours-plugin'); ?>:</strong> pwt_season, pwt_activity, pwt_safari_zone, pwt_vehicle_type, pwt_package_category, pwt_destination_category</p>
    <p><strong><?php esc_html_e('FAQ/Testimonial/Review selector', 'wildtours-plugin'); ?>:</strong> pwt_content_type = pwt_faq | pwt_testimonial | pwt_review</p>
</div>
