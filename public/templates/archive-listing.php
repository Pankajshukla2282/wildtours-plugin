<?php

defined('ABSPATH') || exit;

get_header();

$currentPostType = get_query_var('post_type');
$queriedObject = get_queried_object();
$isSeasonTaxonomy = is_tax('pwt_season') || ($queriedObject instanceof WP_Term && $queriedObject->taxonomy === 'pwt_season');

if ($isSeasonTaxonomy && empty($currentPostType)) {
    $currentPostType = sanitize_text_field($_GET['content_type'] ?? 'pwt_package');
}

if (is_tax('pwt_package_category') || ($queriedObject instanceof WP_Term && $queriedObject->taxonomy === 'pwt_package_category')) {
    $currentPostType = 'pwt_package';
}

if (is_tax('pwt_safari_zone') || ($queriedObject instanceof WP_Term && $queriedObject->taxonomy === 'pwt_safari_zone')) {
    $currentPostType = 'pwt_safari';
}

if (is_tax('pwt_destination_category') || ($queriedObject instanceof WP_Term && $queriedObject->taxonomy === 'pwt_destination_category')) {
    $currentPostType = 'pwt_destination';
}

if ($currentPostType === 'pwt_package') {
    $title = __('Panna Tour Packages', 'wildtours-plugin');
    $filters = [
        'package_category' => get_terms(['taxonomy' => 'pwt_package_category', 'hide_empty' => true]),
        'season' => get_terms(['taxonomy' => 'pwt_season', 'hide_empty' => true]),
    ];
} elseif ($currentPostType === 'pwt_safari') {
    $title = __('Safari Experiences', 'wildtours-plugin');
    $filters = [
        'safari_zone' => get_terms(['taxonomy' => 'pwt_safari_zone', 'hide_empty' => true]),
        'season' => get_terms(['taxonomy' => 'pwt_season', 'hide_empty' => true]),
    ];
} else {
    $title = __('Destinations Around Panna', 'wildtours-plugin');
    $filters = [
        'destination_category' => get_terms(['taxonomy' => 'pwt_destination_category', 'hide_empty' => true]),
    ];
}

$actionUrl = $queriedObject instanceof WP_Term ? get_term_link($queriedObject) : get_post_type_archive_link($currentPostType);
?>
<main class="pwt-single-wrap">
    <section class="pwt-section">
        <header class="pwt-section-header">
            <h1><?php echo esc_html(is_tax() ? single_term_title('', false) : $title); ?></h1>
            <?php if ($queriedObject instanceof WP_Term && $queriedObject->description) : ?>
                <p><?php echo esc_html($queriedObject->description); ?></p>
            <?php endif; ?>
        </header>
        <form class="pwt-filter-bar" method="get" action="<?php echo esc_url(is_string($actionUrl) ? $actionUrl : home_url('/')); ?>">
            <div class="pwt-form-grid">
                <?php if ($isSeasonTaxonomy) : ?>
                    <label>
                        <span><?php esc_html_e('Content Type', 'wildtours-plugin'); ?></span>
                        <select name="content_type">
                            <option value="pwt_package" <?php selected($currentPostType, 'pwt_package'); ?>><?php esc_html_e('Packages', 'wildtours-plugin'); ?></option>
                            <option value="pwt_safari" <?php selected($currentPostType, 'pwt_safari'); ?>><?php esc_html_e('Safaris', 'wildtours-plugin'); ?></option>
                        </select>
                    </label>
                <?php endif; ?>
                <?php foreach ($filters as $queryVar => $terms) : ?>
                    <label>
                        <span><?php echo esc_html(ucwords(str_replace('_', ' ', $queryVar))); ?></span>
                        <select name="<?php echo esc_attr($queryVar); ?>">
                            <option value=""><?php esc_html_e('All', 'wildtours-plugin'); ?></option>
                            <?php foreach ($terms as $term) : ?>
                                <option value="<?php echo esc_attr($term->slug); ?>" <?php selected(sanitize_text_field($_GET[$queryVar] ?? ''), $term->slug); ?>><?php echo esc_html($term->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                <?php endforeach; ?>
            </div>
            <p><button type="submit" class="pwt-btn"><?php esc_html_e('Apply Filters', 'wildtours-plugin'); ?></button></p>
        </form>
    </section>

    <section class="pwt-section">
        <div class="pwt-cards-grid">
            <?php if (have_posts()) : ?>
                <?php while (have_posts()) : the_post(); ?>
                    <article class="pwt-card">
                        <?php if (has_post_thumbnail()) : ?>
                            <div class="pwt-card-image"><?php the_post_thumbnail('medium_large'); ?></div>
                        <?php endif; ?>
                        <div class="pwt-card-body">
                            <h3><?php the_title(); ?></h3>
                            <p><?php echo esc_html(wp_trim_words(get_the_excerpt() ?: get_the_content(null, false), 24)); ?></p>
                            <a class="pwt-text-link" href="<?php the_permalink(); ?>"><?php esc_html_e('View details', 'wildtours-plugin'); ?></a>
                        </div>
                    </article>
                <?php endwhile; ?>
            <?php else : ?>
                <p><?php esc_html_e('No results found for the selected filters.', 'wildtours-plugin'); ?></p>
            <?php endif; ?>
        </div>
        <?php the_posts_pagination(); ?>
    </section>
</main>
<?php
get_footer();
